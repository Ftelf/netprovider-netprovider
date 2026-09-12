# CR-02 — Billing Logic Robustness

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P1 (silent financial errors possible)
> **Effort:** M (3–4 weeks)
> **Source documents:** `docs/TECHNICAL.md` §Billing engine (lines 361–425), §Refund and freebie handling (lines 420–425), §Domain model status enums (lines 339–358); `docs/USER_GUIDE.md` §Assigning an Internet service (lines 128–141), §Running billing (lines 159–184)

---

## 1. Problem statement

The billing engine works for the documented happy path — monthly internet subscriptions paid on time — but documentation reveals several edge cases where the behavior is undefined, inconsistent, or rigid in ways that are hard for operators to work around:

1. **Refund ambiguity for non-FINISHED removed entries.** TECHNICAL line 421 — "A charge entry that gets removed from outside its window is **refunded** to the customer's balance." But what about entries removed in `STATUS_TESTINGFREEOFCHARGE` (no funds were deducted), `STATUS_PENDING_INSUFFICIENTFUNDS` (funds were not deducted), or `STATUS_DISABLED` (excluded from billing)? The spec says nothing. Refunding any of these would over-credit the customer.
2. **Single billing period.** TECHNICAL line 348 + line 715 — `Charge::PERIOD_MONTHLY = 3` is the only period implemented. Quarterly and yearly subscriptions are common in Czech ISP pricing and require a code branch + `chargeentry` validation rule per period.
3. **VAT consistency not enforced.** TECHNICAL §Billing line 376 lists `CE_amount`, `CE_baseamount`, `CE_vat` as separate fields. The spec does not say that they must satisfy `amount = baseamount + vat` (or `baseamount * (1 + vat/100)` if `CE_vat` is a percentage). Operators editing `Charge` rows can drift the three fields out of sync; `chargeentry` rows projected from the drifted `Charge` carry the inconsistency forward.
4. **Multi-currency hazards.** `Charge.CH_currency` and `BankAccount.BA_currency` are both first-class fields, but `PersonAccount` is single-balance. There is no documented rule for what happens when a customer has two charges in different currencies, or when a payment arrives on a bank account whose currency differs from the customer's charge.
5. **Tolerance can permanently mask insolvency.** `Charge.CH_tolerance` (days) — a value larger than the period length means the customer is never switched off. No upper bound, no warning.
6. **`writeoffoffset` collision with billing day.** `chargeentry.CE_period_date` must have day = 1 for monthly periods (TECHNICAL line 716). `CE_writeoffoffset` (days) shifts the actual deduction — but if it exceeds the period length the deduction lands inside the next period and the FIFO ordering in `proceedCharges` is no longer monotonic. Spec does not document a bound.
7. **Sequence-clean gating is all-or-nothing.** TECHNICAL line 412 — "ENABLED + sequencePayed + actualEntryToBeEnabled → ENABLED". A single entry stuck `ERROR` in 2019 can keep the customer's actualstate `DISABLED` forever even though every subsequent entry is `FINISHED`. No documented way to "ignore prior to this date" beyond manually flipping each old entry.
8. **Statement-number continuity across year boundary.** TECHNICAL line 440 — "validates that statement numbers form a continuous sequence per year". But a brand-new bank-account row whose first observed statement is mid-year triggers a false gap from 1..N-1.
9. **Refund vs payment-return distinction.** USER_GUIDE table row "Refund a paid charge — User's accounts → Return payment" — the `returnPayment` task returns money to the customer balance, but does it also flip the source `chargeentry` back to `PENDING`? Not specified. If it does, the next billing run silently re-deducts.

## 2. Affected docs / areas

- `docs/TECHNICAL.md` §Billing engine, §Bank-statement ingestion, §Domain model
- `docs/USER_GUIDE.md` §Running billing, §Common operational tasks
- `includes/billing/ChargesUtil.php` (signatures unchanged but branch logic extends)
- `includes/billing/AccountEntryUtil.php`
- `includes/tables/Charge.php`, `ChargeEntry.php`, `PersonAccount.php`
- `localhost.sql` (new columns + constraint)

## 3. Goals & non-goals

**Goals**

- Make refund semantics explicit per `ChargeEntry::STATUS_*`.
- Add quarterly and yearly billing periods.
- Enforce VAT consistency at write time.
- Define a multi-currency strategy (recommendation: single-currency-per-customer with explicit reject on mismatch).
- Add operator-visible warnings for tolerance/writeoffoffset configurations that disable the switch-off mechanism.
- Add a "billing baseline date" so old `ERROR` / `PENDING` entries can be ignored without flipping them one by one.
- Statement-number continuity check honors the bank-account onboarding date.
- Make the `returnPayment` flow explicit in spec and code: refund-only vs refund-and-revoke.

**Non-goals**

- Fully multi-currency PersonAccount with FX conversion — too large for this CR; tracked separately.
- Pro-rated period billing on mid-period activation/deactivation — useful but not blocking; separate CR.
- Tax engine for non-Czech VAT regimes.

## 4. Proposed change

### 4.1 Refund matrix on entry removal

`ChargesUtil::removeChargeEntriesOutOfScope()` (note: TECHNICAL spells it "removeChangeEntriesOutOfScope" — typo, fixed in CR-05) becomes table-driven:

| `CE_status` at removal time   | Refund balance? | Audit row                                                    |
| ----------------------------- | :-------------: | ------------------------------------------------------------ |
| `FINISHED`                    | yes             | `personaccountentry { source = REFUND_REMOVED_ENTRY }`       |
| `PENDING`                     | no              | `log { LEVEL_INFO, "removed pending entry" }`                |
| `PENDING_INSUFFICIENTFUNDS`   | no              | as above                                                     |
| `TESTINGFREEOFCHARGE`         | no              | as above                                                     |
| `DISABLED`                    | no              | as above                                                     |
| `ERROR`                       | no              | `log { LEVEL_WARNING, "removed errored entry" }` + supervisor email |

The matrix is documented in `docs/TECHNICAL.md` §Refund handling.

### 4.2 New billing periods

| Period constant         | Value | Day-of-period rule                         |
| ----------------------- | :---: | ------------------------------------------ |
| `Charge::PERIOD_MONTHLY`  | 3     | `datestart` day must be 1                  |
| `Charge::PERIOD_QUARTERLY`| 6     | `datestart` day must be 1, month in {1,4,7,10} |
| `Charge::PERIOD_YEARLY`   | 12    | `datestart` day must be 1, month must be 1 |

`ChargesUtil::createOrRemoveChargeEntriesForPerson()` and `proceedChargesForPerson()` switch over the period constant; the existing `match` style fits naturally.

`Charge::getLocalizedPeriod()` is updated.

The advance horizon (`Blank charges advance count`, currently in months) becomes `Blank charges advance periods` (count of periods of the relevant `Charge`). For backward compat the old key is read as a synonym when the new key is absent.

### 4.3 VAT consistency

A new `BeforeSave` hook on `ChargeDAO` and `ChargeEntryDAO` validates:

```
abs((baseamount + vat) - amount) < 0.01
```

(currency rounding tolerance). The check runs on every insert/update. Failure throws `Exception("VAT inconsistency: $base + $vat != $total")`. The web form catches and surfaces the error inline.

A migration script (one-shot) flags every existing `chargeentry` where the equation does not hold, writes them to the audit log at `LEVEL_WARNING`, and emails the supervisor. No automatic fix — operators reconcile manually.

### 4.4 Single-currency-per-customer rule

- New column `personaccount.PA_currency` (varchar 3, default `CZK`).
- `ChargeDAO::insert` and `HasChargeDAO::insert` reject if `Charge.CH_currency != PersonAccount.PA_currency`.
- `AccountEntryUtil` rejects payments whose `BankAccount.BA_currency` does not match `PA_currency` and emails the supervisor.
- A future CR may introduce per-currency sub-balances; this CR explicitly does not.

### 4.5 Tolerance and writeoffoffset bounds

- `Charge.CH_tolerance` warns the operator (UI only) when it exceeds half the period length. Edit allowed but the form shows a yellow banner.
- `Charge.CH_writeoffoffset` is hard-rejected if it exceeds the period length minus 1 day.
- `chargeentry` save inherits the same checks.

### 4.6 Billing baseline date

- New column `personaccount.PA_billingbaselinedate` (date, nullable).
- `proceedCharges` ignores `chargeentry` rows whose `CE_period_date < PA_billingbaselinedate` for the purposes of the "sequence clean" check, but they remain visible in reports.
- Operator UI: in `com_personaccount` detail, a button "Set baseline = today" (super-admin only). Audit-log writes `LEVEL_SECURITY` with the old and new value.

### 4.7 Statement-number continuity

- New column `bankaccount.BA_continuitystartno` (int, nullable). Defaults to NULL.
- The continuity check skips statement numbers below `BA_continuitystartno`. When NULL, the check uses the lowest statement number ever observed for that bank account (auto-baselined).
- Year boundary: tracked per `(year, bankaccountid)` tuple. The first statement of a new year is its own sequence start; gaps are computed within the year.

### 4.8 `returnPayment` semantics

- Two distinct operator actions:
  - **Refund only.** Credits balance. `chargeentry` stays `FINISHED`. Used when the customer is owed money but the original charge stands.
  - **Revoke charge.** Credits balance and flips `chargeentry.CE_status = DISABLED`. The next billing run does not re-deduct. Used for cancellations / billing errors.
- The web form prompts the operator for which action; the audit log records the choice.

## 5. Migration path

| Phase | Action                                                                                                                  |
| ----- | ----------------------------------------------------------------------------------------------------------------------- |
| 1     | Schema migration: add new columns. Backfill `PA_currency = 'CZK'`. Reject scripts on mixed-currency rows (manual fix). |
| 2     | Deploy code with new periods, VAT check, refund matrix. The new periods are off by default — admin has to opt in.       |
| 3     | Operator training + UI walk-through of the refund-vs-revoke distinction.                                                 |
| 4     | Enable VAT check in enforcing mode after a 7-day warn-only window.                                                       |

## 6. Testing strategy

- **Unit tests:**
  - Refund matrix: parametrised over every `CE_status` value, asserting balance delta and audit-log entry.
  - Period generators: monthly, quarterly, yearly. Edge cases: leap year, end-of-month entries, daylight-saving boundary at the period rollover (CET → CEST in March).
  - VAT validator: rounding edge cases, negative VAT (refunds), zero-VAT internet.
  - Continuity check: synthetic statement sequences with gaps, year rollovers, late-baselined accounts.
- **Property-based tests** (recommended — simple table-driven harness):
  - For any random sequence of {credit, charge, freebie, error, removal}, the customer balance equals the sum of credits minus the sum of finished deductions plus the sum of refunds for removed-finished entries. Run 10 000 iterations.
- **Manual scenarios:**
  - Customer with quarterly charge starting mid-year, two failed deductions, then payment + recovery.
  - Customer who was over-charged through stale `chargeentry` after a `Charge` row was edited.

## 7. Open questions

- Should the VAT check accept `0.01 CZK` rounding tolerance or be exact-decimal (`bcmath`)? Recommendation: bcmath, but only after CR-07 introduces the dependency manager.
- Is "billing baseline date" per `PersonAccount` or per `HasCharge`? Recommendation: per `PersonAccount` for simplicity; tracked at HasCharge granularity if a real customer case requires it.
- Should quarterly/yearly billing horizons obey the same 6-month-ahead `Blank charges advance count`, or be in periods? Recommendation: in periods (clearer for operators).

## 8. Effort estimate & risk

- **Effort:** M. ~3 person-weeks engineering, plus 1 week migration / training.
- **Risk highlights:**
  - VAT enforcement breaks existing rows that were already inconsistent. Run a dry-run report first; fix manually.
  - Adding new periods without robust tests risks double-billing on the period boundary.
  - Single-currency rule rejects rare but legitimate mixed-currency customers (corporate clients invoiced in EUR) — coordinate with sales before enabling.

## 9. Out of scope

- True multi-currency `PersonAccount` with FX conversion.
- Pro-rated activation / deactivation.
- Cash-flow forecasting from projected `chargeentry` rows.
- Invoicing PDF generation overhaul.
