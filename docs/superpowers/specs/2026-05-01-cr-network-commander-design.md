# CR-04 — Network Commander Robustness

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P2 (operational rigidity, no immediate exploit)
> **Effort:** M (3 weeks)
> **Source documents:** `docs/TECHNICAL.md` §Network commanders (lines 475–523), §Extending the system → Switching to a third network platform (lines 729–731); `docs/USER_GUIDE.md` §Activating and deactivating service (lines 187–212), §Troubleshooting and FAQ (lines 333–335)

---

## 1. Problem statement

The network-commander layer works for the documented topology (single Linux router with iptables/tc, or a single MikroTik) but bakes in several assumptions that limit deployment flexibility and create silent-failure modes:

1. **One internet HasCharge per customer.** TECHNICAL line 495 — "the code uses `reset($hasCharges)` and groups every IP under that single charge." Customers with two simultaneous internet products (e.g. fibre + LTE backup) silently get one of them ignored. The spec calls this out explicitly but does not propose a fix.
2. **Hardcoded binary paths.** `Network Device Command sudo` and `Network Device Command iptables` defaults are `/usr/bin/sudo` and `/usr/local/sbin/iptables` (USER_GUIDE line 335). Container deployments and Alpine-based hosts use different paths; the operator has to discover this through trial and error.
3. **No drift detection.** The spec describes pushing rules but never reading back to confirm they took effect. If the device drops a rule (size limit, unrelated mutation by another tool), the system does not notice until a customer complains.
4. **No partial-failure recovery.** A `synchronizeFilter()` run that fails halfway through leaves the device in an indeterminate state. Spec says nothing about rollback or retry.
5. **Single device per platform.** TECHNICAL §CommanderCrossbar talks about "the network device" (singular). Multi-AP / multi-region deployments need fan-out, but the schema assumes one set of credentials in `netprovider.ini`.
6. **No dry-run / preview.** Operators cannot see "what would change" before changing it. Mistakes (e.g. accidental `--ip-filter-down`) take effect immediately.
7. **Counters reconciliation gap.** TECHNICAL line 514 — Linux counters parsed via `iptables -L -v -x -n` regex. Any iptables version that changes the column ordering breaks parsing silently — the regex either matches a different field or matches nothing and the counter reads as zero.
8. **Third-platform extensibility is shallow.** The "switching to a third network platform" section (line 729) only says "implement a class with the same public surface". No interface contract, no test fixture for new commanders.
9. **No bandwidth/QoS variation.** Every customer with the same `Internet` row gets identical `tc` parameters. A customer who wants temporary boost (storm, holiday plan) cannot be expressed without creating a new `Internet` tier and reassigning.
10. **Linux SSH password auth.** TECHNICAL line 511 — uses `phpseclib`/`ssh2`. The hardening checklist (line 667) suggests "switch to keys if possible" but does not surface key-based auth as a first-class config option.

## 2. Affected docs / areas

- `docs/TECHNICAL.md` §Network commanders, §Extending the system
- `docs/USER_GUIDE.md` §Activating and deactivating service, §Troubleshooting
- `config/netprovider.ini` — `[Network Device]` block expands
- `includes/net/CommanderCrossbar.php`, `LinuxCommander.php`, `RouterOSCommander.php`
- `localhost.sql` — small schema additions

## 3. Goals & non-goals

**Goals**

- Customer can have N internet `HasCharge` rows; commander applies them all.
- All command paths and credentials are configurable, with sane defaults documented per OS.
- Drift detection: scheduled compare of desired vs actual; any divergence triggers a `LEVEL_WARNING` log + supervisor email.
- `synchronizeFilter()` is transactional from the device's perspective (atomic apply, rollback on failure).
- `--diff-network` dry-run output for operators.
- Multi-device support: per-network mapping to a device profile.
- Formal `Commander` interface; reference fixture for testing third-party commanders.
- Counter parsing tolerant of iptables column-order changes (use `-x -n -v --line-numbers` with named columns, or switch to `iptables-save` parsing).
- SSH key auth as first-class option for `LinuxCommander`.
- Per-`HasCharge` QoS overrides for temporary boost.

**Non-goals**

- Building a full network controller (NetBox-style). NetProvider stays focused on per-customer accept rules + shaping.
- BGP / routing protocol integration.
- IPv6 support (separate CR — currently neither commander handles it).

## 4. Proposed change

### 4.1 Multiple internet HasCharges per customer

`CommanderCrossbar::__construct()` builds the in-memory map differently:

```
For each Person (active):
  For each HasCharge with active internet Charge:
    Look up IPs assigned to that HasCharge (not the person)
    Append a service entry under the network of each IP
```

This requires a new column `ip.IP_haschargeid` (FK to `hascharge`, nullable for legacy rows). When NULL, the IP is implicitly attached to the customer's first active internet `HasCharge` (current behavior). The migration is purely additive.

When two `HasCharge`s on the same customer both produce rules for the same IP, the commander combines:

- **Linux:** the slower of the two upload/download rate values wins (most-restrictive).
- **RouterOS:** queue tree adds a parent burstable container; the slowest child rate wins.

The spec documents this combination rule.

### 4.2 Configurable command paths

`[Network Device]` adds:

| Key                                | Default                  | Notes                                                |
| ---------------------------------- | ------------------------ | ---------------------------------------------------- |
| `Network Device Command sudo`      | `/usr/bin/sudo`           | Existing; remains as-is                              |
| `Network Device Command iptables`  | `/usr/sbin/iptables`      | Default updated to the Debian/Ubuntu standard path   |
| `Network Device Command tc`        | `/usr/sbin/tc`            | New                                                  |
| `Network Device Command ip`        | `/usr/sbin/ip`            | New (for `ip link` queries)                          |
| `Network Device Command ipset`     | `/usr/sbin/ipset`         | New (optional; CR-04b may use it)                    |

Path probing: on first connect, the Linux commander runs `which iptables tc ip` and warns at `LEVEL_WARNING` if the configured path differs from the discovered one.

### 4.3 SSH key authentication

`[Network Device]` adds:

| Key                                | Type   |
| ---------------------------------- | ------ |
| `Network Device SSH KeyFile`       | path   |
| `Network Device SSH KeyPassphrase` | string |
| `Network Device SSH HostKeyAlgorithms` | string (CSV) |

When `KeyFile` is set, password auth is skipped; the file must be `0600` and owned by the web/CLI user. `phpseclib` supports both modes.

### 4.4 Drift detection

A new CLI flag `--audit-network` runs:

1. Build the desired-state rule set (no apply).
2. Read the actual rule set from the device.
3. Diff.
4. Log every divergence at `LEVEL_WARNING`.
5. Email the supervisor if any divergence exists.

Recommended cron: hourly. The check holds the `network.global` lock from CR-03 so it cannot collide with `--proceed-networking`.

### 4.5 Transactional apply

- **Linux:** the existing line-by-line `iptables` calls are replaced by:
  1. Build the full rule set into a temp file in the standard `iptables-save` format.
  2. `iptables-save > /tmp/np-prev`.
  3. `iptables-restore --table filter < /tmp/np-new` (applies atomically).
  4. On failure, `iptables-restore --table filter < /tmp/np-prev`.
  5. Same pattern for `tc` (use `tc -batch < script` and capture exit code; rollback applies the previous batch).
- **RouterOS:** the existing API `write()` calls are replaced by:
  1. Snapshot `/ip/firewall/filter` rules where comment matches `NetProvider.*` into memory.
  2. Add new rules with comment `NetProvider.staging`.
  3. Atomically rename comments: staging → live, live → discard (one API call sequence per chain).
  4. Remove discard rules.
  5. On any error mid-flight, remove staging rules and leave live rules untouched.

### 4.6 Dry-run preview

`php services/service.php --proceed-networking --dry-run` emits the desired rule set as a unified diff against the current device state to stdout, applies nothing.

A web equivalent: **Network → Network device → Preview changes**. Shows the same diff in a `<pre>` block.

### 4.7 Multi-device support

Schema:

| Table          | New columns                                                              |
| -------------- | ------------------------------------------------------------------------ |
| `network`      | `NE_deviceprofileid` (FK)                                                |
| `deviceprofile` (new) | `DP_id PK`, `DP_name`, `DP_platform`, all `Network Device …` keys |

`CommanderCrossbar` reads the device profile for each `Network` and instantiates one commander per profile, then dispatches per-network. Backward-compat: when no profiles exist, the legacy `[Network Device]` block in `netprovider.ini` is treated as the implicit default profile.

### 4.8 Formal Commander interface

```php
interface Commander {
    public function buildPlan(array $networkServices): CommanderPlan;
    public function preview(CommanderPlan $plan): string;
    public function apply(CommanderPlan $plan): CommanderResult;
    public function readCounters(): array;
    public function teardown(): CommanderResult;
}
```

`CommanderPlan` is a value object the test fixture can construct directly. `CommanderResult` carries `success`, `appliedCount`, `errors[]`. Third-party commanders implement the interface and register through a new `Network Device Platform` value plus a class-name in `[Network Device] Custom Commander Class`.

### 4.9 Resilient counter parsing

- **Linux:** parse `iptables-save -c` output (per-rule counters are first-class fields, position-stable) instead of `iptables -L`.
- **RouterOS:** continue using `=stats=` API field; documented as stable.

### 4.10 Per-HasCharge QoS overrides

`HasCharge` adds optional columns `HC_qos_dnl_rate`, `HC_qos_upl_rate`, `HC_qos_priority`. When set, override the linked `Internet` row's values. Use case: temporary boost or throttle without creating a new tier.

## 5. Migration path

| Phase | Action                                                                                   |
| ----- | ---------------------------------------------------------------------------------------- |
| 1     | Schema additions (additive). Default device profile auto-created.                         |
| 2     | Deploy code. Multi-HasCharge handling off by default; flip on per-customer basis.         |
| 3     | Switch Linux commander to `iptables-restore`. Roll out on staging first; soak 7 days.     |
| 4     | Enable drift detection cron in audit-only mode for 14 days; review warnings; tune.        |
| 5     | Migrate operators away from `LinuxCommander` password auth to key auth; deprecate password. |

## 6. Testing strategy

- **Unit tests** against `CommanderPlan` builder:
  - Multi-HasCharge customer plan combination.
  - Per-HasCharge QoS override beats `Internet` defaults.
  - Inactive HasCharge excluded.
- **Fixture tests** with a fake `Commander`:
  - Roundtrip plan → preview → apply → readCounters.
  - Apply failure triggers rollback; counters unchanged.
- **Integration tests** with real targets (in CI):
  - Linux: a containerized iptables host (Alpine + nftables-iptables compat).
  - RouterOS: CHR (Cloud Hosted Router) free-tier VM.
  - Tests cover full state transitions: empty → 100 customers → drift insertion (manual `iptables -A`) → audit detects → next sync corrects.
- **Manual chaos:**
  - Inject a manually-added rule that conflicts with NetProvider's. Run `--audit-network`; expect a single warning per drift; run `--proceed-networking`; expect the conflicting rule preserved (NetProvider only manages rules with its own comment prefix).

## 7. Open questions

- For multi-device: when a `Person` has IPs on networks belonging to different device profiles, should the customer be ENABLED on all or none? Recommendation: per-IP enablement; one device's failure does not block the others.
- Comment-prefix scheme for "rules NetProvider owns" — `NetProvider:` prefix is human-readable but increases rule size. Worth it for the multi-tool coexistence story.
- Does the `Network Device Custom Commander Class` mechanism need an autoloader change? Recommendation: register through a manual `require_once` in a new `includes/net/commanderRegistry.php`; CR-07 introduces composer and replaces this.

## 8. Effort estimate & risk

- **Effort:** M. ~3 person-weeks plus 1 week soak.
- **Risk highlights:**
  - `iptables-restore` differs in subtle ways from line-by-line `iptables`. Operator-specific rule sets (e.g. NAT) may not survive a faithful restore. Test on real production rule sets before rollout.
  - RouterOS atomic swap is genuinely tricky; if the API session drops mid-rename, manual cleanup of `NetProvider.staging` rules may be required.
  - Multi-HasCharge combination rules ("most restrictive wins") may surprise operators who currently rely on the silent fallback to the first charge. Communicate clearly.

## 9. Out of scope

- IPv6 support — separate CR.
- BGP / advanced routing.
- Per-direction rate sculpting beyond what `tc HTB` / RouterOS queue trees provide.
- Wireless (Wi-Fi) controller integration.
- Per-MAC accounting.
