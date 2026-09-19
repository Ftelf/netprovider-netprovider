# com_personaccount

> Per-person financial account screen: shows balance, incoming payments and per-service charge entries, and drives manual payment entry plus the billing engine (blank-charge creation, charge processing, excuse/ignore/remove/refund).

## Access

No ACL check exists inside the controller; access is governed globally by `site/index2.php`. Any authenticated user whose `GR_level == Group::USER` (`0`) is force-routed to `com_myprofile` and can never reach this module (`site/index2.php:117-119`). `MainFrame::getPath()` resolves `?option=com_personaccount` to `modules/com_personaccount/personaccount.index.php`; a missing path silently falls back to `com_admin` (`includes/Mainframe.php:69-84`). So `ADMINISTRATOR` (`5`) and `SUPER_ADMINISTRATOR` (`9`) reach the module. Two tasks are additionally gated to `SUPER_ADMINISTRATOR` inside their handlers: `createBlankCharges` (`personaccount.index.php:267`) and `proceedCharges` (`personaccount.index.php:284`); their toolbar buttons are also hidden from non-super admins (`personaccount.html.php:87`), and the CASH payment source is offered only to super admins (`personaccount.html.php:1096`). `Group` levels defined at `includes/tables/Group.php:37-39`.

## Entry point

- Option: `?option=com_personaccount`
- Controller: `modules/com_personaccount/personaccount.index.php`
- View class: `HTML_PersonAccount` in `personaccount.html.php`

Request params read: `task`, `PE_personid` (`$pid`), `PN_personaccountentryid` (`$pnid`), `CE_chargeentryid` (`$ceid`), `cid[]` (`$cid`) (`personaccount.index.php:28-35`).

## Tasks

Top-level dispatch `switch ($task)` at `personaccount.index.php:37-105`. Every case label plus `default` is listed below.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `createBlankCharges` | Toolbar "Create blank charges" (`personaccount.html.php:91`, super-admin only) | `personaccount.index.php:38` → `createBlankCharges()` :263 | `$my->GR_level` | via `ChargesUtil::createBlankChargeEntries()` → `INSERT`/`DELETE` on `chargeentry`, `UPDATE personaccount` (`ChargesUtil.php:47`,`155`,`230`,`234`) | redirect to `?option=com_personaccount` | Flash message; DB log; billing engine projects future charge entries |
| `proceedCharges` | Toolbar "Pay payables" (`personaccount.html.php:99`, super-admin only) | `personaccount.index.php:44` → `proceedCharges()` :280 | `$my->GR_level` | via `ChargesUtil::proceedCharges()` → `UPDATE chargeentry`, `UPDATE personaccount`, `UPDATE hascharge` (`ChargesUtil.php:243`,`388`,`390`,`492`) | redirect to `?option=com_personaccount` | Flash message; DB log; deducts balances, sets entry statuses. UI call passes no arg so `$fireDeadlineEvents` defaults `false` → `ChargePaymentDeadlineEvent` **not** fired (`ChargesUtil.php:243`,`364`; message "Messaging clients has been suppressed." :288) |
| `returnPayment` | "Return" action select on an incoming payment row (`personaccount.html.php:298`,`523`) | `personaccount.index.php:50` → `returnPayment($pid,$pnid)` :297 | `PE_personid`, `PN_personaccountentryid` | `PersonAccountEntryDAO::removePersonAccountEntryByID`, `UPDATE personaccount`, `UPDATE bankaccountentry` (`personaccount.index.php:313`,`323-324`,`344-345`,`357-358`) | redirect to `task=showDetail` | Transaction (`:306`,`366`,`368`); reverses balance/income; re-opens matched bank entry; flash + DB log |
| `freeCharge` | "Excuse" action select on a charge-entry row (`personaccount.html.php:313`,`715`) | `personaccount.index.php:56` → `freeCharge($ceid)` :378 | `CE_chargeentryid` | `UPDATE chargeentry` (`personaccount.index.php:398`) | redirect to `task=showDetail`; if entry status not PENDING/PENDING_INSUFFICIENTFUNDS, redirects to list (`:387-390`) | Sets entry to `STATUS_TESTINGFREEOFCHARGE`, zeroes amounts; flash + DB log |
| `ignoreCharge` | "Ignore" action select on a charge-entry row (`personaccount.html.php:315`,`716`) | `personaccount.index.php:62` → `ignoreCharge($ceid)` :419 | `CE_chargeentryid` | `UPDATE chargeentry` (`personaccount.index.php:439`) | redirect to `task=showDetail`; same PENDING guard (`:428-431`) | Sets entry to `STATUS_DISABLED`, zeroes amounts; flash + DB log |
| `removeCharge` | "Remove" action select on a charge-entry row (`personaccount.html.php:317`,`725`) | `personaccount.index.php:68` → `removeCharge($ceid)` :460 | `CE_chargeentryid` | `ChargeEntryDAO::removeChargeEntryByID`, `UPDATE personaccount` (`personaccount.index.php:482`,`484`) | redirect to `task=showDetail` | Transaction (`:480`,`485`,`487`); if entry was `STATUS_FINISHED`, refunds amount to balance/outcome (`:471-477`); flash + DB log |
| `cancelPAE` | "Cancel" on new-payment screen (`personaccount.html.php:966`) | `personaccount.index.php:74` → `showPersonAccountDetail($pid)` :176 | `PE_personid` | none | `HTML_PersonAccount::showPersonAccountDetail()` | none (read-only detail) |
| `showDetail` | Row link / `showDetail()` JS (`personaccount.html.php:38`,`198`) | `personaccount.index.php:75` → `showPersonAccountDetail($pid)` :176 | `PE_personid` | none | `HTML_PersonAccount::showPersonAccountDetail()` | none |
| `cancelEdit` | "Cancel" on account-edit screen (`personaccount.html.php:777`) | `personaccount.index.php:76` → `showPersonAccountDetail($pid)` :176 | `PE_personid` | none | `HTML_PersonAccount::showPersonAccountDetail()` | none |
| `showDetailA` | Toolbar "Edit" on list (`personaccount.html.php:45`,`109`) | `personaccount.index.php:80` → `showPersonAccountDetail((int)$cid[0])` :176 | `cid[]` | none | `HTML_PersonAccount::showPersonAccountDetail()` | none |
| `edit` | "Edit" on detail screen (`personaccount.html.php:285`,`356`) | `personaccount.index.php:84` → `editPersonAccount($pid)` :201 | `PE_personid` | none | `HTML_PersonAccount::editPersonAccountDetail()` | none |
| `apply` | "Apply" on account-edit screen (`personaccount.html.php:778`,`813`) | `personaccount.index.php:88` → `savePersonAccount($pid,'apply')` :213 | `$_POST` (PersonAccount fields via `Database::bind`) | `UPDATE personaccount` (`personaccount.index.php:239`) | redirect back to `task=edit` | Zeroes balance fields before save (`:220-223`); numeric-symbol guard redirects on invalid (`:233-237`); flash + DB log (inner `switch ($task)` :241-257) |
| `save` | "Save" on account-edit screen (`personaccount.html.php:781`,`820`) | `personaccount.index.php:89` → `savePersonAccount($pid,'save')` :213 | `$_POST` (PersonAccount fields) | `UPDATE personaccount` (`personaccount.index.php:239`) | redirect to `task=showDetail` | same as `apply`; different redirect (`:248-253`) |
| `newPAE` | Toolbar "New payment" on detail (`personaccount.html.php:288`,`348`) | `personaccount.index.php:93` → `editPersonAccountEntry($pid)` :510 | `PE_personid` | none | `HTML_PersonAccount::editPersonAccountEntry()` | none |
| `savePAE` | "Save" on new-payment screen (`personaccount.html.php:976`) | `personaccount.index.php:97` → `savePersonAccountEntry($pid)` :522 | `$_POST` (PersonAccountEntry fields) | `INSERT personaccountentry`, `UPDATE personaccount` (`personaccount.index.php:564-565`) | redirect to `task=showDetail`; on money/date parse error re-renders form (`:535-538`,`554-556`) | Transaction (`:563`,`566`,`569`); CASH adds to balance+income, DISCOUNT adds to balance only, else throws (`:541-548`); flash + DB log |
| `cancel` | "Cancel" on detail screen (`personaccount.html.php:280`,`363`) | `personaccount.index.php:101` (falls through to `default`) → `showPersonAccount()` :110 | session filter/limit | none | `HTML_PersonAccount::showEntries()` | none (list) |
| `default` | Any other/absent task | `personaccount.index.php:102` → `showPersonAccount()` :110 | `$_SESSION['UI_SETTINGS']['com_personaccount']` filter/limit; `PersonDAO`, `PersonAccountDAO` | none | `HTML_PersonAccount::showEntries()` | Builds duplicate-variable-symbol / missing-account warning messages (`:131-165`) |

Note: `grep "case '"` returns 18 hits in this file; 16 are the top-level dispatch labels above (`:38-101`), and the remaining 2 are the nested `switch ($task)` inside `savePersonAccount()` (`apply` :242, `save` :248) — not dispatch tasks. Plus `default` (`:102`) = 17 top-level branches, all documented.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_PersonAccount::showEntries()` | Account list: search + status + balance filters, per-person balances/income/outcome/VS/KS/SS, toolbar (super-admin: create-blank-charges, pay-payables; all: edit), conflicting-VS/missing-account message box | `personaccount.html.php:31` |
| `HTML_PersonAccount::showPersonAccountDetail()` | Account detail: account summary, "Incoming payments" table (with Return action), "Service payments" table nesting per-charge-entry rows with Excuse/Ignore/Remove actions (columns conditional on `ENABLE_VAT_PAYER_SPECIFICS`) | `personaccount.html.php:271` |
| `HTML_PersonAccount::editPersonAccountDetail()` | Account-edit form: read-only balances + editable variable/constant/specific symbols with enable checkboxes; Apply/Save/Cancel toolbar | `personaccount.html.php:768` |
| `HTML_PersonAccount::editPersonAccountEntry()` | New-payment form: source select (CASH shown to super-admin only, DISCOUNT), amount, date (calendar popup), comment; Save/Cancel toolbar | `personaccount.html.php:945` |

## Data touched

- DAOs:
  - `PersonAccountDAO` — `getPersonAccountArray()`, `getPersonAccountByID()` (`includes/dao/PersonAccountDAO.php:31`,`42`)
  - `PersonDAO` — `getPersonArray()`, `getPersonByID()`, `getPersonByPersonAccountID()`
  - `PersonAccountEntryDAO` — `getPersonAccountEntryArrayByPersonAccountID()`, `getPersonAccountEntryByID()`, `getPersonAccountEntryArrayByBankAccountEntryID()`, `removePersonAccountEntryByID()`
  - `BankAccountEntryDAO` — `getBankAccountEntryArray()`, `getBankAccountEntryByID()`
  - `ChargeEntryDAO` — `getChargeEntryByID()`, `getChargeEntryArrayByHasChargeID()`, `removeChargeEntryByID()`
  - `ChargeDAO` — `getChargeArray()`, `getChargeByID()`
  - `HasChargeDAO` — `getHasChargeWithChargeWithPersonArrayByPersonID()`, `getHasChargeByID()`
  - `ChargesUtil` (billing engine) — `createBlankChargeEntries()`, `proceedCharges()` (`includes/billing/ChargesUtil.php:47`,`243`)
- Tables:
  - `PersonAccount` (`includes/tables/PersonAccount.php`) — columns `PA_personaccountid`, `PA_currency`, `PA_startbalance`, `PA_balance`, `PA_income`, `PA_outcome`, `PA_variablesymbol`, `PA_constantsymbol`, `PA_specificsymbol` (`:23-55`)
  - `PersonAccountEntry` (`includes/tables/PersonAccountEntry.php`) — sources `SOURCE_BANKACCOUNT=1`, `SOURCE_CASH=2`, `SOURCE_DISCOUNT=3` (`:53-55`)
  - `ChargeEntry` (`includes/tables/ChargeEntry.php`) — statuses `STATUS_FINISHED=1`, `STATUS_PENDING=2`, `STATUS_PENDING_INSUFFICIENTFUNDS=3`, `STATUS_TESTINGFREEOFCHARGE=4`, `STATUS_DISABLED=5`, `STATUS_ERROR=6` (`:65-70`)
  - `chargeentry`, `hascharge`, `bankaccountentry` (written via billing engine / returnPayment)

## Forms & fields

**Account edit form** (`editPersonAccountDetail`, `personaccount.html.php:768`) — posts to `savePersonAccount`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `PA_variablesymbol` | Variable symbol; disabled unless `_CB_PA_variablesymbol` checked | Server: if checkbox off, forced to `0` (`:229`); all three symbols must be numeric or redirect back to edit (`:233-237`). Client toggles disabled state via `vs()` (`:786`) |
| `PA_constantsymbol` | Constant symbol | as above (`:230`,`233-237`); client `cs()` (`:790`) |
| `PA_specificsymbol` | Specific symbol | as above (`:231`,`233-237`); client `ss()` (`:794`) |
| `_CB_PA_variablesymbol` / `_CB_PA_constantsymbol` / `_CB_PA_specificsymbol` | Enable flags for the three symbols | server-read at `:229-231` |
| `PA_personaccountid`, `PE_personid` | Hidden identity | balance/income/outcome/startbalance nulled before save (`:220-223`) so they cannot be edited |

**New-payment form** (`editPersonAccountEntry`, `personaccount.html.php:945`) — posts to `savePersonAccountEntry`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `PN_source` | Payment source: CASH (super-admin only, `:1096-1100`) or DISCOUNT (`:1102`) | Server: CASH → balance+income, DISCOUNT → balance; unknown source throws (`:541-548`) |
| `PN_amount` | Money amount | Client: must parse as float, comma→dot (`:969-971`). Server: `NumberFormat::parseMoney`; on failure `Core::alert` + re-render (`:533-538`) |
| `PN_date` | Payment date | Client: `isDate(...,'dd.MM.yyyy')` (`:972`). Server: `DateUtil::parseDate` FORMAT_DATE; on failure alert + re-render (`:551-557`) |
| `PN_comment` | Free-text comment | none |

**List filters** (`showEntries`, `personaccount.html.php:134`) — submit on change, persisted in `$_SESSION['UI_SETTINGS']['com_personaccount']['filter']`:

| Field | Meaning | Source |
|-------|---------|--------|
| `filter[search]` | Name search passed to `PersonDAO::getPersonArray` | `:138`, read `:118` |
| `filter[status]` | Person status filter (`Person::$STATUS_ARRAY`) | `:141-151`, read `:119` |
| `filter[bilance]` | Balance filter: 1=negative, 2=non-zero, 3=positive (client-side unset in `showPersonAccount`) | `:154-160`, applied `:143-151` |

Incoming-payment and charge-entry action selects are not form fields but JS-driven task submits (`personAccountEntryAction` :294, `changeEntryAction` :309); the charge-entry `Remove` option is shown only for FINISHED/PENDING/PENDING_INSUFFICIENTFUNDS/TESTINGFREEOFCHARGE/DISABLED, and `Excuse`/`Ignore` only for PENDING/PENDING_INSUFFICIENTFUNDS (`:712-728`).

## Related flows & cross-module links

- **Billing engine (`ChargesUtil`)**: `createBlankCharges` / `proceedCharges` delegate to `includes/billing/ChargesUtil.php`. `proceedChargesForPerson` can fire `ChargePaymentDeadlineEvent` via `$eventCrossBar->dispatchEvent()` (`ChargesUtil.php:369`) — but only when `$fireDeadlineEvents` is true; the UI path calls `proceedCharges()` with no argument, so events/email are suppressed here (`personaccount.index.php:287`).
- **Events fired directly by this module**: none (`EventCrossBar` is only reached transitively through `ChargesUtil` and not on the UI code path).
- **Redirects**: all mutating tasks `Core::redirect()` back to `?option=com_personaccount` (list) or `&task=showDetail`/`&task=edit` within the module. No cross-module redirects.
- **Shared data with**: `com_person` / `com_charge` (persons, charges, hascharges) and `com_bankaccount` (`bankaccountentry` re-opened on `returnPayment`, `personaccount.index.php:308-337`).

## Source anchors

- Dispatch switch: `personaccount.index.php:37-105`
- Handlers: `showPersonAccount` :110, `showPersonAccountDetail` :176, `editPersonAccount` :201, `savePersonAccount` :213 (inner switch :241-257), `createBlankCharges` :263, `proceedCharges` :280, `returnPayment` :297, `freeCharge` :378, `ignoreCharge` :419, `removeCharge` :460, `editPersonAccountEntry` :510, `savePersonAccountEntry` :522
- Views: `personaccount.html.php:31`,`271`,`768`,`945`
- ACL/routing: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`; `includes/tables/Group.php:37-39`
- Billing engine: `includes/billing/ChargesUtil.php:47`,`243`,`369`
- Enums: `includes/tables/PersonAccountEntry.php:53-55`; `includes/tables/ChargeEntry.php:65-70`
- DAO: `includes/dao/PersonAccountDAO.php:31`,`42`,`55`
