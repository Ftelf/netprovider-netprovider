# com_paymentreport

> Month-by-month matrix of each person's monthly charges and their per-period payment status, with column summaries and totals.

## Access
No per-module ACL check exists in the module. Reachability is governed solely by
the global gate in `site/index2.php`: the request must pass session validation
(`site/index2.php:64-70`), and any user whose `GR_level == Group::USER` (level `0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`). So the screen is reachable by `Group::ADMINISTRATOR`
(`5`) and `Group::SUPER_ADMINISTRATOR` (`9`) only. Unknown options fall back to
`com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-83`). The menu
entry is under "Reports" (`modules/com_common/html_mainmenu.php:57`).

## Entry point
- Option: `?option=com_paymentreport`
- Controller: `modules/com_paymentreport/paymentreport.index.php`
- View class: `HTML_PaymentReport` in `paymentreport.html.php`

## Tasks
`$task` read at `paymentreport.index.php:29`; `switch` at `paymentreport.index.php:31`.
The `switch` has **only a `default` case** — no named cases.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| default (only) | `default` `paymentreport.index.php:32`; every request (view's hidden `task` is empty, `paymentreport.html.php:613`) | `showPaymentReport()` `paymentreport.index.php:37-190` | `$_SESSION['UI_SETTINGS']['com_paymentreport']['filter']` + limit/limitstart (`paymentreport.index.php:45-55`) | none | `HTML_PaymentReport::showPayments()` (`paymentreport.index.php:189`) | none |

The single `default` case is listed — 0 gaps. There are **no** create/update/delete
tasks.

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_PaymentReport::showPayments(&$messages, &$charges, &$paymentReport, &$report, &$filter, &$pageNav)` | Optional message box (unterminated-payment warnings); filter block (Chosen multiselect of charges, search, person-status/payment-status/actual-state selects, from/to month `CalendarPopup`); main `adminlist` matrix (person rows × month columns, colored per status); six summary rows (Payed, Payed with delay, Pending, Delayed, Excused count, Total income); status legend; page-nav footer | `paymentreport.html.php:28-626` |

The month picker returns `MM/yyyy` via `myMonthReturn1x/2x` and submits
(`paymentreport.html.php:45-64`). Row status icons are chosen inline by
`PE_status` / `HC_actualstate` `switch`/`if` blocks in the view
(`paymentreport.html.php:321-349`).

## Data touched
- DAOs:
  - `PersonDAO::getPersonWithAccountArray($search, 0, $PE_status, null, null)` (`paymentreport.index.php:57`) — persons + account fields (`PA_balance`, `PA_variablesymbol`).
  - `ChargeDAO::getChargeArray()` (`paymentreport.index.php:58`) — all charges; filtered in PHP to `Charge::PERIOD_MONTHLY` (`:60-64`; `includes/tables/Charge.php:73`).
  - `HasChargeDAO::getHasChargeReportArray($pid, $chargeids, $HC_status, $HC_actualstate, $dateFrom, $dateTo)` (`paymentreport.index.php:116`).
  - `ChargeEntryDAO::getChargeEntryArrayByHasChargeID($hcid, $dateFrom, $dateTo, 'CE_period_date')` (`paymentreport.index.php:120`).
  - `GroupDAO` is required (`paymentreport.index.php:22`) but no method is called here.
- Tables:
  - `person` + person-account (`includes/tables/Person.php`) — `PE_personid`, `PE_firstname`, `PE_surname`, `PE_status`, `PA_balance`, `PA_variablesymbol` (`paymentreport.html.php:357-363`).
  - `charge` (`includes/tables/Charge.php`) — `CH_chargeid`, `CH_name`, `CH_period`, `CH_amount`, `CH_currency`.
  - `hascharge` (`includes/tables/HasCharge.php`) — joined `person`+`hascharge`+`charge` in `getHasChargeReportArray` (`includes/dao/HasChargeDAO.php:85-113`); fields `HC_haschargeid`, `HC_status`, `HC_actualstate`, `HC_datestart`, `HC_dateend`, `CH_tolerance`.
  - `chargeentry` (`includes/tables/ChargeEntry.php`) — `CE_status`, `CE_overdue`, `CE_amount`, keyed by `CE_period_date`.

### Report / status logic
- Date range: `date_from`/`date_to` parsed as `FORMAT_MONTHLY`; on parse failure
  defaults to `now-3 months` … `now+2 months` (day 1, midnight); swapped if inverted
  (`paymentreport.index.php:81-113`).
- For each person, monthly `HasCharge` rows are loaded and their `ChargeEntry` rows
  attached; persons with at least one matching charge are kept (`:115-125`).
- Month buckets and per-month summary accumulators (`payed`, `payedWithDelay`,
  `delayed`, `pending`, `free`) are initialised (`:129-143`).
- Each (hasCharge × month) cell is classified by `chargeEntryToStyle()`
  (`paymentreport.index.php:192-224`), mapping `ChargeEntry::CE_status` +
  `CE_overdue` (vs `CH_tolerance`) to a `PaymentReportStyles::*` CSS class and adding
  to the month summary. The mapping (`includes/tables/ChargeEntry.php:65-70`;
  `includes/html/css/PaymentReportStyles.php:14-22`):

  | Condition | Style | Summary bucket |
  |-----------|-------|----------------|
  | `STATUS_ERROR` | (none — commented "never used so far", `:194-195`) | none |
  | `STATUS_FINISHED` & `overdue == 0` | `STATUS_FINISHED_IN_TIME` | `payed` |
  | `STATUS_FINISHED` & `overdue > 0` | `STATUS_FINISHED_OVERDUE` | `payedWithDelay` |
  | `STATUS_PENDING` | `STATUS_PENDING` | `pending` |
  | `STATUS_PENDING_INSUFFICIENTFUNDS` & `overdue <= CH_tolerance` | `STATUS_PENDING_INSUFFICIENT_FUNDS` | `delayed` |
  | `STATUS_PENDING_INSUFFICIENTFUNDS` & `overdue > 0` | `STATUS_PENDING_INSUFFICIENT_FUNDS_OVERDUE` | `delayed` |
  | `STATUS_TESTINGFREEOFCHARGE` | `STATUS_FREE_OF_CHARGE` | `free` |
  | else | `STATUS_OTHER` | none |

- Outside a charge's `HC_datestart`..`HC_dateend` window a cell is
  `STATUS_HAS_NO_CHARGE`; within window but no entry present it is
  `STATUS_PENDING_PAYMENT_NOT_CREATED` (`paymentreport.index.php:158-175`).
- A person whose charges never matched any entry is dropped and added to
  `$messages` as an "Has unterminated payment" link to `com_person` edit
  (`paymentreport.index.php:178-182`).
- `Total income` row sums payed+payedWithDelay+pending+delayed per month
  (`paymentreport.html.php:545`).

## Forms & fields
Single form `adminForm` (`paymentreport.html.php:116`). Filters persist in
`$_SESSION['UI_SETTINGS']['com_paymentreport']['filter']` (read `paymentreport.index.php:45-51`).

| Field | Column/meaning | Validation |
|-------|----------------|------------|
| `filter[CH_chargeid][]` (multi-select, Chosen) | which monthly charges to include; options from `$charges` (`paymentreport.html.php:131-151`); non-array reset to `[]` (`paymentreport.index.php:66-68`) | none; auto-submits |
| `filter[search]` (text) | free-text person search passed to `PersonDAO::getPersonWithAccountArray` (`paymentreport.html.php:155-157`) | none; auto-submits |
| `filter[PE_status]` (select) | person status filter; `-1` = all; options `Person::$STATUS_ARRAY` (`paymentreport.html.php:159-169`; `includes/tables/Person.php:144-151`) | none; auto-submits |
| `filter[HC_status]` (select) | payment (hascharge) status; `-1` = all; `HasCharge::$STATUS_ARRAY` (`paymentreport.html.php:204-214`; `includes/tables/HasCharge.php:49-58`) | none; auto-submits |
| `filter[HC_actualstate]` (select) | actual-state filter; `-1` = all; `HasCharge::$ACTUALSTATE_ARRAY` (`paymentreport.html.php:217-227`; `includes/tables/HasCharge.php:72-77`) | none; auto-submits |
| `filter[date_from]` (text, id `date_from`) | range start month `MM/yyyy` (`paymentreport.html.php:175-178`) | month `CalendarPopup`; server `DateUtil` `FORMAT_MONTHLY` parse w/ default (`paymentreport.index.php:84-93`) |
| `filter[date_to]` (text, id `date_to`) | range end month `MM/yyyy` (`paymentreport.html.php:188-191`) | as above (`paymentreport.index.php:95-104`) |
| `option` / `task` / `boxchecked` / `hidemainmenu` (hidden) | routing/state (`paymentreport.html.php:612-615`) | none |

## Related flows & cross-module links
- Events fired (`EventCrossBar`): none.
- Redirects to other options: none. The message box emits inline links to
  `index2.php?option=com_person&task=edit&PE_personid=...` for persons with
  unterminated payments (`paymentreport.index.php:179`).
- View respects `Core::ENABLE_VAT_PAYER_SPECIFICS` for the amount column header
  ("Amount with VAT" vs "Amount") via `$core->getProperty(...)`
  (`paymentreport.html.php:31,286-292`).
- Shared DAOs: `PersonDAO`, `ChargeDAO`, `HasChargeDAO`, `ChargeEntryDAO` (and
  required-but-unused `GroupDAO`).

## Source anchors
- `modules/com_paymentreport/paymentreport.index.php:29-35` — task read + `default`-only `switch`.
- `modules/com_paymentreport/paymentreport.index.php:37-190` — `showPaymentReport()` build.
- `modules/com_paymentreport/paymentreport.index.php:57-64` — person/charge fetch + monthly filter.
- `modules/com_paymentreport/paymentreport.index.php:115-184` — matrix assembly + drop-and-warn.
- `modules/com_paymentreport/paymentreport.index.php:192-224` — `chargeEntryToStyle()` status→style map.
- `includes/dao/HasChargeDAO.php:79-113` — report join query.
- `includes/dao/ChargeEntryDAO.php:43` — charge-entry-by-hascharge query.
- `includes/dao/PersonDAO.php:150` — `getPersonWithAccountArray`.
- `includes/html/css/PaymentReportStyles.php:14-22` — status CSS class constants.
- `modules/com_paymentreport/paymentreport.html.php:131-227` — filter controls.
- `modules/com_paymentreport/paymentreport.html.php:276-555` — matrix + summary rows.
- `site/index2.php:117-119` — USER force-redirect to `com_myprofile`.
