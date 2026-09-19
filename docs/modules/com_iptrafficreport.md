# com_iptrafficreport

> Per-IP data-traffic accounting report, bucketed by hour/day/month over a date range, with optional average-rate view.

## Access
No per-module ACL check exists in the module. Reachability is governed solely by
the global gate in `site/index2.php`: the request must pass session validation
(`site/index2.php:64-70`), and any user whose `GR_level == Group::USER` (level `0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`). So the screen is reachable by `Group::ADMINISTRATOR`
(`5`) and `Group::SUPER_ADMINISTRATOR` (`9`) only. Unknown options fall back to
`com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-83`). The menu
entry is under "Reports" (`modules/com_common/html_mainmenu.php:58`).

## Entry point
- Option: `?option=com_iptrafficreport`
- Controller: `modules/com_iptrafficreport/iptrafficreport.index.php`
- View class: `HTML_IpTrafficReport` in `iptrafficreport.html.php`

## Tasks
`$task` read at `iptrafficreport.index.php:28`; `switch` at `iptrafficreport.index.php:30`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `trafficReport` | `case 'trafficReport'` `iptrafficreport.index.php:31` | `showBankList()` `iptrafficreport.index.php:32` | — | none | — | **Broken/dead:** `showBankList()` is not defined in this module (no submitter in the view sets `task=trafficReport`); invoking this case would fatal-error. |
| default (list/report) | `default` `iptrafficreport.index.php:35`; every real request from the view (hidden `task` is empty, `iptrafficreport.html.php:241`) | `showTrafficReport()` `iptrafficreport.index.php:40-176` | `$_SESSION['UI_SETTINGS']['com_iptrafficreport']` filter/limit (`iptrafficreport.index.php:48-58`) | none | `HTML_IpTrafficReport::showTraffic()` (`iptrafficreport.index.php:175`) | none |

Both `switch` cases (`trafficReport`, `default`) are listed — 0 gaps.

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_IpTrafficReport::showTraffic(&$ips, &$report, &$filter, &$pageNav)` | Filter row (search box, period select, from/to `CalendarPopup` dates, "Show average rate" checkbox); sortable-header `adminlist` (#, Surname, Firstname, Nickname, IP address, then one column per interval); per-row in/out bytes or Mbit/s cells; page-nav footer | `iptrafficreport.html.php:25-258` |

Client helpers: `filterChange()` copies dates and submits (`iptrafficreport.html.php:33-37`);
`sortChange(sort_key, sort_direction)` sets hidden sort fields and submits
(`iptrafficreport.html.php:39-43`); sort links pass `IpDAO` column constants
(`iptrafficreport.html.php:162-176`).

## Data touched
- DAOs:
  - `IpDAO::getIpWithPersonArray($sort, $search, $limitstart, $limit)` (`iptrafficreport.index.php:61,65`) — IP joined to owning person.
  - `IpDAO::getIpWithPersonArrayCount($search)` (`iptrafficreport.index.php:67`).
  - `IpAccountDAO::getIpAccountHourSumByIpID($ipid, $dateFrom, $dateTo, $divider)` (`iptrafficreport.index.php:124`).
  - `IpAccountDAO::getIpAccountDateSumByIpID(...)` (`iptrafficreport.index.php:135`).
  - `IpAccountDAO::getIpAccountMonthSumByIpID(...)` (`iptrafficreport.index.php:147`).
  - `IpDAO` sort-key constants: `IpDAO::data`, `IpDAO::PE_surname`, `IpDAO::PE_firstname`, `IpDAO::PE_nick`, `IpDAO::IP_address` (`includes/dao/IpDAO.php:24-29`).
  - `PersonDAO` is required (`iptrafficreport.index.php:21`) but no method is called directly here.
  - `IpAccountAbsDAO` is required (`iptrafficreport.index.php:24`) but no method is called here.
- Tables:
  - `ip` joined to `person` on `IP_personid=PE_personid` (`includes/dao/IpDAO.php:51`); row fields used: `IP_ipid`, `IP_address`, `PE_surname`, `PE_firstname`, `PE_nick` (`iptrafficreport.html.php:204-209`).
  - `ipaccount` — traffic accounting summed by `IpAccountDAO` (`includes/tables/IpAccount.php`); per-interval cells read `IA_bytes_in`/`IA_bytes_out` (`iptrafficreport.html.php:222,224`).

### Report logic
- Period defaults to `MONTHS` when not one of `HOURS`/`DAYS`/`MONTHS`
  (`iptrafficreport.index.php:108-112`).
- Interval columns built per period: hours 0-24 for one day (`:114-121`), each day
  in range (`:126-132`), or each month with month-end clamp (`:137-144`).
- Average-rate mode (`show_rate == "checked"`) passes a seconds divider
  (3600/86400/2592000) into the sum DAO calls (`:124,135,147`); the view then
  formats `NumberFormat::formatMbitps(...)` instead of `formatMB(...)`
  (`iptrafficreport.html.php:221-225`).
- When `sort_key == IpDAO::data` the full set is fetched, sorted in PHP by summed
  `bytes_sum` (local `cmp()`), then sliced to the page window
  (`iptrafficreport.index.php:60-63,151-173`).
- `date_from`/`date_to` parsed via `DateUtil`, defaulting to today-midnight on parse
  failure and swapped if inverted (`:79-106`).

## Forms & fields
Single form `adminForm` (`iptrafficreport.html.php:93`). Filters persist in
`$_SESSION['UI_SETTINGS']['com_iptrafficreport']['filter']` (read `iptrafficreport.index.php:48-54`).

| Field | Column/meaning | Validation |
|-------|----------------|------------|
| `filter[search]` (text) | free-text person search (matches many `PE_*` columns, `includes/dao/IpDAO.php:54`) (`iptrafficreport.html.php:98`) | none; auto-submits `onchange` |
| `filter[period]` (select) | bucket size; options `HOURS`/`DAYS`/`MONTHS` (`iptrafficreport.html.php:101-110`; labels set `iptrafficreport.index.php:75-77`) | none; server clamps to `MONTHS` if invalid (`:108-112`) |
| `date_from` (text) + `filter[date_from]` (hidden) | range start `dd.MM.yyyy` (`iptrafficreport.html.php:116-123`) | `CalendarPopup`; server `DateUtil` parse w/ default fallback (`:79-87`) |
| `date_to` (text) + `filter[date_to]` (hidden) | range end `dd.MM.yyyy` (`iptrafficreport.html.php:133-140`) | as above (`:89-97`) |
| `filter[show_rate]` (checkbox) | value `checked` → average-rate view (`iptrafficreport.html.php:150-151`) | none; auto-submits |
| `filter[sort_key]` (hidden) | `IpDAO` sort constant set by `sortChange()` (`iptrafficreport.html.php:244-245`) | none |
| `filter[sort_direction]` (hidden) | sort direction string (`iptrafficreport.html.php:246-247`) | none (only `data` path re-sorts; column ASC/DESC not otherwise applied server-side) |
| `option` / `task` / `boxchecked` / `hidemainmenu` (hidden) | routing/state (`iptrafficreport.html.php:240-243`) | none |

## Related flows & cross-module links
- Events fired (`EventCrossBar`): none.
- Redirects to other options: none.
- Shared DAOs: `IpDAO`, `IpAccountDAO` (and required-but-unused `PersonDAO`,
  `IpAccountAbsDAO`).

## Source anchors
- `modules/com_iptrafficreport/iptrafficreport.index.php:28-38` — task read + `switch`.
- `modules/com_iptrafficreport/iptrafficreport.index.php:40-176` — `showTrafficReport()`.
- `modules/com_iptrafficreport/iptrafficreport.index.php:108-149` — period bucketing + sum DAO calls.
- `modules/com_iptrafficreport/iptrafficreport.index.php:151-173` — in-PHP sort by total bytes.
- `includes/dao/IpDAO.php:24-29,47-87` — sort constants + join queries.
- `includes/dao/IpAccountDAO.php:85-135` — hour/date/month sum queries.
- `modules/com_iptrafficreport/iptrafficreport.html.php:98-153` — filter controls.
- `modules/com_iptrafficreport/iptrafficreport.html.php:200-236` — data rows / rate vs MB formatting.
- `site/index2.php:117-119` — USER force-redirect to `com_myprofile`.
