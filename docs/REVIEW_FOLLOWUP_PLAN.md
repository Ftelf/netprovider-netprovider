# Review Follow-up Plan (post-merge)

Pending work from the code reviews of the split PRs (`cr/1-test-harness-and-docs`,
`cr/2-audit-fixes`, `cr/3-chargesutil-billing`), all now merged to `master`. This pass was
deferred until after the three merges to avoid conflicts with a PR still in flight.

## Status

| Step | Item | Status |
|------|------|--------|
| 0 | Reconcile the `ChargesUtilTest.php` conflict | ✅ **Moot** — the merge preserved `testProceedChargesForPersonWithExactBalance()`; the exact-balance boundary is covered. |
| 1 | Enable strict PHPUnit config | ✅ **Done** — 3 blockers fixed; `failOnWarning`/`failOnDeprecation`/`failOnNotice`/`failOnRisky` all `true`; suite green with 0 warnings/deprecations. |
| 2 | Populate the integration suite | ⛔ **Open** — needs a live MySQL; see below. |
| 3a | Widen the email TLD cap | ✅ **Done** — `\w{1,4}` → `\w{2,}`; `testIsEmailAcceptsLongTlds`. |
| 3b | Pin the notify inclusive boundary | ✅ **Done** — `testDispatchNotifiesOnInclusiveThresholdBoundary`; mutation `>=`→`>` verified red. |
| 3c | Document the value-`0` notify semantics | ✅ **Done** — comment at `EventCrossBar.php` notify guard. |

### Step 1 — what landed

- `includes/event/EventCrossBar.php` — guard the template read with `is_file()` before
  `file_get_contents()` (the missing-path warning was the last `failOnWarning` blocker); the
  throw on a missing/empty template is unchanged.
- `includes/tables/Person.php` — declare `public $PA_personaccountid;`. It is a real column
  carried on the `Person` object when loaded via the `person ⋈ personaccount` accounting
  join (`PersonDAO::getPersonWithAccountArrayForAccounting`) and read by `AccountEntryUtil`;
  declaring it removes the dynamic-property deprecation without changing behavior.
- `includes/billing/bankParser/IsoSepaXmlParser/IsoSepaXmlParser.php` — declare
  `private $document = null;` (it was assigned/returned but never declared).
- `phpunit.xml` — flipped `failOnWarning`, `failOnNotice`, `failOnRisky` to `true` and added
  `failOnDeprecation="true"`.

---

## Step 2 — populate the integration suite (OPEN)

**Why:** `tests/Integration/` contains only `.gitkeep`. `phpunit.xml` declares an
`integration` testsuite pointing at that empty dir, so `--testsuite integration` reports
**"No tests executed!"** and exits 0 — a false success signal.

**Blocked on infrastructure:** writing *and verifying* these tests needs a live MySQL. The
environment where the rest of this plan was executed had no Docker daemon and no mysql
client, so real integration tests could not be authored against a running DB without shipping
unverified test code. Do this step where a disposable MySQL is available.

**Do:**
1. Stand up a disposable MySQL (Docker) seeded from `localhost.sql`. Note the two tables
   flagged as absent from `localhost.sql` (`handleevent`, `messageattachment`) — add DDL for
   them or scope the first integration tests away from those tables.
2. Write integration tests against the real `Database` wrapper (not `DatabaseStub`) for at
   least the billing read/write path (`ChargesUtil::proceedCharges` end to end) and one
   bank-import path (`AccountEntryUtil`), asserting persisted rows.
3. Gate them: skip cleanly (not fail) when the DB env var is absent, so the unit tier stays
   runnable without MySQL. Wire a `composer test:integration` script.
4. Keep tests Deterministic/Isolated: each test owns its fixture rows and tears them down
   (transaction rollback per test preferred).

**Verification gate for this step:**
```bash
php vendor/bin/phpunit --testsuite integration   # real tests run, not a no-op
```
