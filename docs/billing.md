# Billing — `ChargesUtil` canonical business logic

> **Status:** authoritative specification of *intended* billing behavior for `includes/billing/ChargesUtil.php`.
> Where the current code diverges from the intended rule, the intended rule wins here and the divergence is recorded in [§8 Divergence register](#8-divergence-register). **Tests are written against this document, not against the code** — a test that reds against a divergence is proving the bug, not failing.
>
> Scope: `ChargesUtil` only. The money-in side (`AccountEntryUtil`, `bankParser`) that funds `PersonAccount.PA_balance` is out of scope here and treated as an external precondition. See [`TECHNICAL.md`](TECHNICAL.md#bank-statement-ingestion) for that overview.

---

## 1. Purpose

`ChargesUtil` is the billing engine. It has two responsibilities, run as separate CLI passes by `services/service.php --proceed-payments`:

1. **Projection** — `createBlankChargeEntries()` materializes the *money a customer will owe*: it generates one `ChargeEntry` per billing period, ahead of time, for each active recurring charge, and removes entries that no longer fall inside the charge's validity window.
2. **Collection + service-state** — `proceedCharges()` does two things per charge: (a) it *collects* due entries against the customer's prepaid balance (`PA_balance`), marking each `FINISHED` or `PENDING_INSUFFICIENTFUNDS`; and (b) it derives `HasCharge.HC_actualstate` (**ENABLED / DISABLED**) — the single flag the network layer (`CommanderCrossbar`) reads to decide whether the customer's internet stays on.

The system is **prepaid**: money arrives into `PA_balance` first (bank import), and billing deducts from it. A customer is disconnected when their unpaid periods fall outside tolerance, not when a bill is issued.

---

## 2. Domain vocabulary

Values below are the authoritative enum set (from `includes/tables/*.php`). Any value not listed does not exist.

### Charge (`includes/tables/Charge.php`) — the recurring-fee template
| Field | Meaning |
|---|---|
| `CH_period` | Billing cadence. **Only `PERIOD_MONTHLY = 3` exists** (`$PERIOD_ARRAY` has this single member). See [§7](#7-constraints--invariants). |
| `CH_amount` / `CH_baseamount` / `CH_vat` / `CH_currency` | Money charged per period; copied onto each `ChargeEntry` at projection time. |
| `CH_writeoffoffset` | **Collection delay.** Days after a period starts before that period's entry is *attempted for payment*. |
| `CH_tolerance` | **Disconnection grace.** Days of overdue an *unpaid* period may accumulate while the service still stays ENABLED. Distinct from `writeoffoffset`; see [§6](#6-date-semantics-writeoffoffset-vs-tolerance). |
| `CH_type` | `UNSPECIFIED`/`INTERNET_PAYMENT`/`ENTRY_FEE`/`PENALTY`. Does **not** affect billing math in `ChargesUtil`. |

### HasCharge (`includes/tables/HasCharge.php`) — a charge assigned to a person
| Concept | Values |
|---|---|
| `HC_status` (operator intent) | `DISABLED=0`, `ENABLED=1`, `FORCE_DISABLED=2`, `FORCE_ENABLED=3` |
| `HC_actualstate` (computed, drives QoS) | `DISABLED=0`, `ENABLED=1` |
| `HC_datestart` / `HC_dateend` | Validity window. Open-ended end is stored as `0000-00-00` → `DateUtil::getTime() === null`. `datestart` must land on day-1 of a month. |

### ChargeEntry (`includes/tables/ChargeEntry.php`) — one period's bill
| `CE_status` | Meaning |
|---|---|
| `PENDING=2` | Owed, not yet collected (or not yet due for write-off). |
| `FINISHED=1` | Paid — amount deducted from `PA_balance`. |
| `PENDING_INSUFFICIENTFUNDS=3` | Due, attempted, but balance too low. Accrues `CE_overdue` days. |
| `TESTINGFREEOFCHARGE=4` | Free period; keeps service on without deduction. |
| `DISABLED=5` | Excluded from billing; treated as a clean (non-blocking) period. |
| `ERROR=6` | Error marker. See [§8 D8](#8-divergence-register) — currently **neutral** in the state machine. |

Other fields: `CE_period_date` (day-1 of the billed month), `CE_writeoffoffset` (snapshot of `CH_writeoffoffset`), `CE_realize_date` (payment date, `0000-00-00` until paid), `CE_overdue` (days late at collection).

### Person / PersonAccount
- `Person.PE_status`: `PASSIVE=0`, `ACTIVE=1`, `DISCARTED=9`.
- `PersonAccount`: `PA_balance` (prepaid funds), `PA_income` (credited from bank), `PA_outcome` (cumulative billed). Billing mutates `PA_balance` and `PA_outcome`.

### DateUtil semantics (`includes/utils/DateUtil.php`) — relied on throughout
- `before()` / `after()` are **strict** (`<` / `>`) and **throw** if either side's timestamp is `null`. Every comparison against a possibly-open `dateEnd` must be null-guarded first — this is why the code checks `getTime() != null` before comparing.
- `!$a->before($b)` therefore means `a >= b` (inclusive); `!$a->after($b)` means `a <= b`.

---

## 3. Entry points and person selection

| Method | Person set | Status gate |
|---|---|---|
| `createBlankChargeEntries()` | `PersonDAO::getPersonWithAccountArray()` (persons that have an account) | Per person: processed only if `PE_status == ACTIVE`, unless caller passes `ignoreStatuses`. |
| `proceedCharges($fireDeadlineEvents=false)` | `PersonDAO::getPersonArray()` (all persons) | `ACTIVE` → full pipeline; `PASSIVE`/`DISCARTED` → force every `HC_actualstate` to `DISABLED`. |

`createOrRemoveChargeEntriesForPerson($person, $ignoreStatuses=false, $enableMessagesForEntries=false)` is the per-person unit, also callable directly by the admin UI to recompute one customer on demand (`ignoreStatuses` = recompute regardless of person/charge status; `enableMessagesForEntries` = emit human-readable per-entry log lines).

The `Charge` templates are loaded **once** in the constructor (`ChargeDAO::getChargeArray()`, keyed by charge id) and reused for the whole pass.

---

## 4. Projection — `createBlankChargeEntries` / `createOrRemoveChargeEntriesForPerson`

For each in-scope person, for each of their `HasCharge` rows:

1. **Status gate.** Skip the `HasCharge` unless `HC_status ∈ {ENABLED, FORCE_ENABLED, FORCE_DISABLED}` (i.e. skip only `DISABLED`), unless `ignoreStatuses`.
2. **Charge existence.** If `HC_chargeid` has no matching `Charge`, log and **skip this charge** (intended: `continue`; see [§8 D1](#8-divergence-register)).
3. **Date sanity.** If `dateEnd` is set and `dateStart > dateEnd`, log + skip. Monthly charges additionally require `dateStart` and (if set) `dateEnd` to fall on **day 1**; otherwise log + skip.
4. **Projection window (monthly).**
   - `mDateMax` = first day of the current month + `BLANK_CHARGES_ADVANCE_COUNT` months (config, default **6**), time zeroed.
   - `mEndDate` = `min(dateEnd, mDateMax)` when `dateEnd` is set, else `mDateMax`.
   - Walk `floatingDate` from `dateStart` forward by 1 month **while `floatingDate <= mEndDate`**. For each month with no existing entry, INSERT a `ChargeEntry` with the `Charge`'s money fields, `CE_period_date = floatingDate`, `CE_realize_date = 0000-00-00`, `CE_overdue = 0`, `CE_status = PENDING`. Each insert is its own transaction.
   - Existing entries are matched by exact `CE_period_date` timestamp (built by `validateChangeEntriesAndBuildMap`, which drops entries whose period date isn't day-1). Projection is **idempotent**: re-running never duplicates a period.
5. **Removal of out-of-scope entries** (`removeChangeEntriesOutOfScope`, one transaction for the whole person-charge): delete every existing entry whose `CE_period_date < dateStart` OR (`dateEnd` set AND `> dateEnd`). **Refund rule:** if a deleted entry was `FINISHED`, reverse its money — `PA_balance += CE_amount`, `PA_outcome -= CE_amount`. Entries in any other status moved no money, so nothing is reversed. The account is persisted once at the end of this step.

> Net effect: projection keeps `chargeentry` rows exactly equal to the set of months in `[dateStart, min(dateEnd, now+advance)]`, and shrinking a charge's window refunds already-collected money for the dropped months.

---

## 5. Collection + service-state — `proceedChargesForPerson`

Only runs the full pipeline for `ACTIVE` persons. If the person's account can't be loaded, log and skip the person. For each `HasCharge`:

### 5.1 Pre-filters (set state, then `continue`)
- `HC_status == DISABLED` → set `HC_actualstate = DISABLED`, skip.
- `now < HC_datestart` (charge not started) → set `HC_actualstate = DISABLED`, skip.

### 5.2 Is the charge in its present window?
`chargeIsInPresent` = `dateEnd` is open (`null`) **OR** (monthly) `now < dateEnd + 1 month`. If not in present, the charge is past its end → final state is `DISABLED` (see 5.5).

### 5.3 Collection (money out)
Load entries sorted by period. For each entry, with `writeOffDate = period + CE_writeoffoffset days`:

- **Attempt only when `now >= writeOffDate`** and `CE_status ∈ {PENDING, PENDING_INSUFFICIENTFUNDS}`. Entries whose write-off is still in the future are left untouched.
- `overdue = floor((now − writeOffDate) / 1 day)`.
- **If `PA_balance < CE_amount`:** set `CE_status = PENDING_INSUFFICIENTFUNDS`, `CE_overdue = overdue`. If `fireDeadlineEvents`, dispatch a `ChargePaymentDeadlineEvent` carrying the person, charge, hasCharge, entry, and the period/writeoff/tolerance dates.
- **Else (funds available):** `PA_balance -= CE_amount`, `PA_outcome += CE_amount`, `CE_realize_date = now`, `CE_status = FINISHED`, and `CE_overdue = 0` if this was a first-attempt `PENDING`, otherwise the computed `overdue` (records that an earlier attempt had failed).
- Persist account + entry (per-entry transaction; see [§8 D2/D3](#8-divergence-register)).

### 5.4 Service-state accumulation
Two boolean accumulators, both start `true`: `sequencePayed` (are all *past* periods clean?) and `actualEntryToBeEnabled` (is the *current* period clean?). **Only entries whose period has already started (`now >= period`) contribute** — future entries are ignored. A period is "present" when `now < period + 1 month`, otherwise "past".

| Entry period | Entry status | Effect |
|---|---|---|
| Present | `FINISHED` / `PENDING` / `TESTINGFREEOFCHARGE` | keeps `actualEntryToBeEnabled` true |
| Present | `PENDING_INSUFFICIENTFUNDS` | `actualEntryToBeEnabled &&= (CE_overdue <= CH_tolerance)` |
| Present | `DISABLED` | `actualEntryToBeEnabled = false` |
| Past | `FINISHED` / `PENDING` / `TESTINGFREEOFCHARGE` / `DISABLED` | keeps `sequencePayed` true |
| Past | `PENDING_INSUFFICIENTFUNDS` | `sequencePayed &&= (CE_overdue <= CH_tolerance)` |
| Present or Past | `ERROR` | **no effect** — neutral (see [§8 D8](#8-divergence-register)) |

### 5.5 Final `HC_actualstate` decision table
Evaluated after the entry walk. First matching row wins:

| Condition | New `HC_actualstate` |
|---|---|
| `chargeIsInPresent` is false (charge ended) | `DISABLED` |
| `HC_status == FORCE_ENABLED` | `ENABLED` |
| `HC_status == FORCE_DISABLED` | `DISABLED` |
| no `ChargeEntry` rows at all | `DISABLED` |
| `HC_status == ENABLED` **and** `sequencePayed && actualEntryToBeEnabled` | `ENABLED` |
| `HC_status == ENABLED` otherwise | `DISABLED` |

The row is written only if the computed state differs from the stored `HC_actualstate`.

> **Intent:** a customer stays ENABLED only when every past period is clean *and* the current period is paid or still within `CH_tolerance` days of overdue. `FORCE_*` overrides billing entirely. A charge past its `dateEnd` is always DISABLED.

### 5.6 Non-active persons
`PASSIVE` / `DISCARTED`: every `HasCharge` is forced to `HC_actualstate = DISABLED`. No collection occurs.

---

## 6. Date semantics: `writeoffoffset` vs `tolerance`

These are two independent offsets, both measured from the period start (`CE_period_date`, day-1 of the month):

- **`writeoffoffset`** delays *collection*: the entry is not charged until `now >= period + writeoffoffset`. It shifts *when money is taken*.
- **`tolerance`** delays *disconnection*: an unpaid (`PENDING_INSUFFICIENTFUNDS`) period keeps the service ENABLED while `overdue <= tolerance`, where `overdue` is counted **from `writeOffDate`**, not from the period start.

Effective disconnection point ≈ `period + writeoffoffset + tolerance` days. `writeoffoffset` never affects the enable/disable decision directly; `tolerance` never affects collection.

---

## 7. Constraints & invariants

1. **Monthly is the only supported period.** `$PERIOD_ARRAY` contains only `PERIOD_MONTHLY`. All period-specific logic is guarded by `CH_period == PERIOD_MONTHLY`; any other value silently produces no projection, no present-window computation, and no state change. This is a *constraint*, not a live bug (no other period can exist), but it means adding a period requires touching three sites — see [§8 D6](#8-divergence-register).
2. **Period dates are day-1 of a month.** Enforced on `HasCharge` dates and re-checked on entries; violating rows are logged and skipped.
3. **Projection is idempotent** and bounded by `BLANK_CHARGES_ADVANCE_COUNT` months ahead.
4. **Prepaid model:** billing only ever *reduces* `PA_balance` (or refunds on window-shrink). Funding `PA_balance` is external.
5. **Open-ended charge** = `dateEnd` stored as `0000-00-00` (`getTime() === null`); such a charge is always "in present".

---

## 8. Divergence register

Suspected bugs / smells where the **code** deviates from, or is fragile against, the intended logic above. Recorded, **not fixed** this round. Each is a candidate red test.

| ID | Location | Observed behavior | Intended behavior | Impact |
|---|---|---|---|---|
| **D1** | `createOrRemoveChargeEntriesForPerson`, `ChargesUtil.php:84` | ✅ **FIXED** — was `return` (abandoned **all remaining** `HasCharge` rows of the person); now `continue`, matching the sibling code at `:275`. | Skip only the offending charge (`continue`). | Resolved: one bad `chargeid` no longer halts projection for the rest of the customer's services. |
| **D2** | `proceedChargesForPerson`, `ChargesUtil.php:357-376` | ✅ **FIXED** — the mutated `PA_balance`/`PA_outcome` and the entry's `CE_status`/`CE_overdue`/`CE_realize_date` are snapshotted before the deduction and restored in the `catch` after `rollback()`, so no phantom state survives a failed transaction. | Balance changes must not survive a rolled-back transaction. | Resolved: a mid-loop DB failure no longer leaks a wrong balance into subsequent charges. Per-entry writes (D3) are unchanged. |
| **D3** | `proceedChargesForPerson`, `ChargesUtil.php:368` | `personaccount` is UPDATEd once **per entry** inside the loop, even on the insufficient-funds path where the balance did not change. | One account write per person after the entry walk. | N redundant writes per person; harder to reason about atomicity. Efficiency, not correctness. |
| **D4** | `removeChangeEntriesOutOfScope`, `ChargesUtil.php:215` | The "Removing … not between %s and %s" message formats `dateEnd` even when it is open (`null` timestamp), yielding a misleading placeholder date. | Format an open end as "∞"/blank, or omit. | Log/UI cosmetic only. |
| **D5** | `proceedChargesForPerson`, `ChargesUtil.php:399-425` | State flags use bitwise `&=` on boolean/int operands; `&= true` is a no-op read as intent. Flags end up `int` (0/1), not `bool`. | Use `&&=` / explicit boolean logic. | Works today (operands are 0/1); type-loose and easy to misread. Robustness. |
| **D6** | Guards at `ChargesUtil.php:104, 309, 385` | Non-monthly periods are silently ignored across projection, present-window, and state logic. | If a new period is added, all three sites must handle it, else that charge is invisibly un-billed and forced off. | Latent — no non-monthly period exists today. Documented constraint. |
| **D7** | `createBlankChargeEntries` vs `proceedCharges` | Projection reads `getPersonWithAccountArray()`; collection reads `getPersonArray()` then loads the account per active person. | (Consistent enough — noted so test fixtures target the right query.) | Test-fixture concern, not a defect. |
| **D8** | `proceedChargesForPerson`, `ChargesUtil.php:391-427` | `CE_status == ERROR` is handled in **neither** the present nor the past branch, so it leaves both accumulators unchanged (neutral). | **Unreachable today:** no code path assigns `STATUS_ERROR` — it is written by neither `ChargesUtil` nor the operator UI (`com_personaccount`), and is only *read* for display in `com_paymentreport:194`. Neutral treatment is therefore the documented current behavior. Fail-safe (`ERROR → DISABLED`) would be optional defensive hardening, not a fix. | None in practice; an `ERROR` entry can only arise from a manual DB edit. Reclassified from bug to latent/defensive. |

---

## 9. Product-owner rulings

Resolved on 2026-09-12:

1. **Refund on window-shrink (§4.5): CONFIRMED intended.** Reducing a charge's `dateEnd` (or advancing `dateStart`) refunds already-collected `FINISHED` months back to `PA_balance` and reverses `PA_outcome`. The current code is correct; this is now a locked rule, and a green test.
2. **D8 — `ERROR` neutrality:** confirmed that `STATUS_ERROR` is never assigned by any code path, so neutral handling is the documented current behavior. Fail-safe conversion to `DISABLED` is deferred as optional defensive hardening, not a required fix.
3. **D1 — abandon-remaining-charges: FIXED.** `ChargesUtil.php:84` changed from `return` to `continue`; a missing `chargeid` now skips only that charge. Covered by `testBadChargeIdSkipsOnlyThatChargeNotTheRest`.
4. **D2 — balance survives rollback: FIXED.** The collection block now snapshots the account/entry fields it mutates and restores them on `rollback()`, so a phantom balance can't leak into the next entry. Minimal revert-on-failure (not full per-person atomicity) was chosen to preserve partial-success semantics and the existing per-entry writes. Covered by `testRollbackDuringCollectionLeavesBalanceUncorrupted`.

This document is now the oracle for the `ChargesUtil` test suite.
