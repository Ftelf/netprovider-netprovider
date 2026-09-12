# CR-03 — Concurrency & Cron Reliability

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P1 (silent double-processing risk in production)
> **Effort:** S (1–2 weeks)
> **Source documents:** `docs/TECHNICAL.md` §CLI service lifecycle (lines 192–207), §Web request lifecycle (lines 149–188); `docs/USER_GUIDE.md` §Running billing (lines 159–184), §Common operational tasks; `README.md` §Running the CLI service (lines 123–143)

---

## 1. Problem statement

Documentation describes a system that runs scheduled work from cron without any concurrency guard. Several races and starvation modes are reachable in normal operation:

1. **No locking on the CLI service.** TECHNICAL §CLI service lifecycle says nothing about preventing two simultaneous `--proceed-payments` runs. If a daily 02:30 invocation runs long (large bank-statement backlog, slow POP3 server) and the operator manually launches another, both processes will:
   - Fetch the same POP3 messages (POP3 mailboxes are stateful — the second run sees fewer or zero messages depending on the server's `RETR + DELE` semantics, but `EmailList` rows can still get duplicated if the first run is mid-write).
   - Run `proceedCharges()` twice. The second run does not idempotently skip already-FINISHED entries (the spec says it walks every entry whose write-off date has passed), so write-off-dated entries that were FINISHED between read and write get re-deducted.
   - Race on `PersonAccount.PA_balance` updates with no row-level locking documented.
2. **`--proceed-payments` and `--proceed-networking` overlap.** Recommended cron: `--proceed-payments` daily at 02:30, `--proceed-networking` every 5 minutes (TECHNICAL line 207, README line 129). The networking sync runs concurrently with the billing pass at 02:30, 02:35, etc. The networking commander reads `HasCharge.HC_actualstate` while billing is mutating it; the result pushed to the device is a torn snapshot.
3. **No idempotency on POP3 ingestion.** TECHNICAL line 433 — "duplicate filename is a database-integrity bug". A retry after a partial failure (network blip after parse but before commit) re-downloads and re-parses, producing the duplicate the spec warns about.
4. **No graceful shutdown.** `services/service.php` is a long-running CLI; the spec does not describe `SIGTERM` handling. A cron job killed for over-running its slot leaves transactions open and `EmailList` rows half-written.
5. **No process-level visibility.** TECHNICAL §Logging only describes per-event log entries. There is no "service started"/"service finished" record with PID and duration; intermittent silent failures are hard to detect.
6. **Web request and CLI run concurrently.** A web operator clicking *Process bank lists* (`com_bankaccount processBankLists`) while cron `--proceed-payments` is running invokes the same code path against the same rows.
7. **Network commander idempotency.** TECHNICAL §Network commanders describes `synchronizeFilter()` and `ipFilterUp()` but does not document whether two simultaneous runs converge to the same iptables / RouterOS state, or whether one run's partial rule list overwrites the other.
8. **No retry semantics.** A POP3 fetch that times out half-way through is not resumable. Statement N+1..N+M never get fetched until the next cron tick, by which time more statements have arrived.

## 2. Affected docs / areas

- `docs/TECHNICAL.md` §CLI service lifecycle, §Network commanders
- `docs/USER_GUIDE.md` §Running billing, §Activating and deactivating service, §Troubleshooting and FAQ
- `README.md` §Running the CLI service
- `services/service.php`
- `includes/billing/ChargesUtil.php`, `AccountEntryUtil.php`, `EmailBankAccountList.php`
- `includes/net/CommanderCrossbar.php` and the platform commanders

## 3. Goals & non-goals

**Goals**

- A single named cron task can never run twice concurrently.
- Mutually-exclusive cron tasks (e.g. billing vs network sync) cannot overlap; one waits for the other.
- POP3 statement ingestion is idempotent across retries.
- Web operator-triggered processing and CLI cron processing share the same lock.
- Every CLI run has start/finish records with PID, duration, exit status.
- Graceful shutdown on `SIGTERM`: finish current iteration, commit, exit.
- Network commanders document their concurrency contract explicitly.

**Non-goals**

- Distributed locking across multiple application servers — the deployment target is single-host. A future multi-host CR can layer Redis/etcd on top.
- Replacing cron with a proper job scheduler (sysemd timers, Nomad, etc.) — out of scope.
- Backpressure or rate-limiting against the bank's POP3 server.

## 4. Proposed change

### 4.1 Named lock primitive

A `ServiceLock` helper in `includes/utils/`:

```php
ServiceLock::acquire(string $name, int $waitSeconds = 0, int $ttlSeconds = 3600): ServiceLock
$lock->touch();    // refresh TTL during long iterations
$lock->release();  // explicit release; destructor releases too
```

Implementation: row in a new `servicelock` table with `(SL_name PK, SL_pid, SL_host, SL_acquired, SL_expires)`. Acquisition is `INSERT … ON DUPLICATE KEY UPDATE` guarded by `SL_expires < NOW()` so a crashed holder does not deadlock the system.

Why DB rather than `flock(2)`: works the same for the web operator path (which lives behind PHP-FPM workers without shared file descriptors).

### 4.2 Lock taxonomy

| Lock name              | Held by                                                     |
| ---------------------- | ----------------------------------------------------------- |
| `payments.global`      | `--proceed-payments`, web `processBankLists`, web `processEntries` |
| `network.global`       | `--proceed-networking`, `--ip-filter-up`, `--ip-filter-down`, web "Network device → sync" |
| `accounting.global`    | `--ip-account`                                              |
| `cleanup.global`       | `--clean-up`                                                |

`payments.global` and `network.global` are **mutually exclusive** through a shared parent lock `payments_or_network`: any task that holds either also holds the parent. The parent is acquired first; a 5-min wait timeout. Timeout → log `LEVEL_WARNING` "skipped due to overlapping run", exit 0 (so cron does not flood the operator with mail).

### 4.3 POP3 ingestion idempotency

- New table `popfetchcheckpoint`:
  - `PFC_bankaccountid PK`, `PFC_lastuid varchar(255)`, `PFC_lastfetched datetime`.
- The fetcher uses POP3 `UIDL` (every modern POP3 server supports it). Messages with UIDs ≤ `PFC_lastuid` are skipped without download.
- After successful per-message processing, `PFC_lastuid` is updated in the same transaction that persists the `EmailList` row.
- Crash recovery: on next run, the same UIDs are re-listed; everything ≤ checkpoint is skipped; the half-written message is re-fetched and re-processed (the `EmailList` constructor's "no duplicate filename" check needs updating — see §4.5).

### 4.4 SIGTERM graceful shutdown

```php
$running = true;
pcntl_signal(SIGTERM, fn() => $running = false);
while ($running && $haveMoreWork()) {
    processOneItem();
    pcntl_signal_dispatch();
    $lock->touch();
}
```

The loop boundaries are per-statement-message for `--proceed-payments`, per-customer for `proceedCharges`, per-IP for the network commander.

### 4.5 Idempotent EmailList writes

- `EmailList` constructor's "duplicate filename throws" becomes "duplicate filename returns the existing row (and skips re-import)".
- The write path uses `INSERT … ON DUPLICATE KEY UPDATE EL_status = EL_status` (no-op) to take the row exclusively, then proceeds.

### 4.6 Service run audit trail

New table `servicerun`:

- `SR_id PK`, `SR_task` (string), `SR_pid`, `SR_host`, `SR_started`, `SR_finished` (nullable), `SR_exitcode` (nullable), `SR_messagecount`.

Populated on entry/exit of `Service::run()`. Browsable through a new `com_servicerun` admin module.

### 4.7 Network commander concurrency

- `CommanderCrossbar::synchronizeFilter()` documents that it requires `network.global` — and the entry point asserts the lock is held by the current process before mutating device state.
- Each commander's batch write becomes transactional from the device's perspective:
  - **RouterOS:** wrap rule mutations in a single API session; on error, replay the previous snapshot (cached in memory before the change).
  - **Linux:** generate the full rule set into a temporary file, atomically swap with `iptables-restore --noflush -T filter` after taking a backup.
- A new `--diff-network` flag prints the diff between desired and actual without applying.

### 4.8 Cron schedule guidance update

The README and USER_GUIDE recommended cron entries gain explicit advice:

```cron
# 02:30 daily — billing (parent lock blocks 5-minute network sync briefly)
30 2 * * *  /usr/bin/php /var/www/netprovider/services/service.php --proceed-payments

# every 5 minutes — network sync (skips silently if billing is running)
*/5 * * * *  /usr/bin/php /var/www/netprovider/services/service.php --proceed-networking
```

A note: skipped runs are visible in `com_servicerun` as exit code 75 (`EX_TEMPFAIL`).

## 5. Migration path

- Schema change is additive; can ship in any maintenance window.
- The web operator-triggered paths start respecting the lock immediately. Brief operator-visible delay (waiting on cron) is acceptable.
- POP3 checkpoint is opt-in: the first run after deployment seeds the checkpoint with the highest known UID and skips nothing further. Operators can manually reset to force a re-fetch.

## 6. Testing strategy

- **Unit tests:**
  - Lock acquisition succeeds when row absent.
  - Lock acquisition blocks (waits) when row present and unexpired.
  - Lock acquisition succeeds when row present but expired.
  - Lock TTL refresh extends expiry.
  - Parent-child lock taxonomy (payments_or_network).
- **Integration tests:**
  - Two `--proceed-payments` invocations launched 1 s apart; second exits 75 with no balance changes.
  - `--proceed-networking` launched while `--proceed-payments` holds the parent lock; networking exits 75.
  - `SIGTERM` mid-run: loop exits at the next iteration boundary; transaction commits; no half-written `EmailList` row.
  - POP3 server returns 4 messages; kill the process between messages 2 and 3; rerun resumes from message 3 (verified by `PFC_lastuid`).
- **Chaos drill (manual):**
  - In a staging environment, hammer `proceedNetworking` from cron every minute and run `proceedPayments` repeatedly; verify the `servicerun` table shows clean overlaps and zero balance corruption.

## 7. Open questions

- Should the `payments_or_network` parent be a write-write exclusion or read-write (network sync reads, billing writes)? Recommendation: write-write — the network commander sometimes mutates `IP` table rows (reverse counters), so treat it as a writer.
- TTL for stale locks: 1 hour is generous; for the high-frequency `network.global` (5-minute cron) this means a crashed run blocks for an hour. Recommendation: 10 min for network locks, 4 h for billing locks.
- Are POP3 mailboxes shared across multiple `bankaccount` rows? If yes, the per-account checkpoint over-skips; need per-account UID filter on the message subject. Recommendation: document the constraint, error if shared.

## 8. Effort estimate & risk

- **Effort:** S. ~1.5 person-weeks engineering plus 1 week soak time on a staging instance.
- **Risk highlights:**
  - Adding locking around the existing un-locked code reveals latent bugs (e.g. transactions held longer than expected). Bake on staging before flipping in production.
  - `iptables-restore` atomic swap differs across distros; Debian default works, busybox-style does not. Test on the operator's actual platform.
  - PHP `pcntl` extension is not always installed on shared hosting. Document the requirement; the loop falls back to non-graceful shutdown if `pcntl_*` is unavailable, with a warning logged at startup.

## 9. Out of scope

- Multi-host distributed locking.
- Replacing cron with a job scheduler.
- Backpressure / rate limiting on POP3.
- Async / parallel fan-out within a single service run (could process multiple bank accounts in parallel — separate CR).
