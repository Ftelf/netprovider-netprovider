# com_log

> Paginated, filterable viewer for the system audit `log` table, with per-row delete.

## Access
No per-module ACL check exists in the module. Reachability is governed solely by
the global gate in `site/index2.php`: the request must pass session validation
(`site/index2.php:64-70`), and any user whose `GR_level == Group::USER` (level `0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`). So the screen is reachable by `Group::ADMINISTRATOR`
(`5`) and `Group::SUPER_ADMINISTRATOR` (`9`) only. Unknown options fall back to
`com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-83`). The menu
entry is under "Administration" (`modules/com_common/html_mainmenu.php:51`).

## Entry point
- Option: `?option=com_log`
- Controller: `modules/com_log/log.index.php`
- View class: `HTML_log` in `log.html.php`

## Tasks
`$task` read at `log.index.php:25`; dispatched by `switch` at `log.index.php:32`.
`$cid` (checkbox array) read/normalised at `log.index.php:27-30`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `remove` | `case 'remove'` `log.index.php:33`; JS `remove()` → `submitbutton('remove')` (`log.html.php:40-49,94`) | `removeLog($cid)` `log.index.php:98-110` | `$_REQUEST['cid']` (`log.index.php:27`) | `LogDAO::removeLogByID($id)` → `log` (`log.index.php:106`) | — (redirects) | alerts if selection empty via `Core::backWithAlert` (`log.index.php:102`); `Core::redirect("index2.php?option=com_log")` (`log.index.php:108`) |
| default (list) | `default` `log.index.php:37`; any non-`remove` request | `showLog()` `log.index.php:44-93` | `$_SESSION['UI_SETTINGS']['com_log']` limit/limitstart/filter (`log.index.php:49-55`) | none | `HTML_log::showLog()` (`log.index.php:92`) | none |

Every `case` in the controller `switch` is listed above (`remove`, `default`) — 0 gaps.

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_log::showLog(&$logs, &$persons, &$pageNav, &$filter)` | Delete toolbar; filter row (log-level select, from/to date inputs with `CalendarPopup`, user select); `adminlist` table of #/checkbox/Time/Level/Action-taken-by/Description rows; page-nav footer | `log.html.php:25-255` |

Client helpers in the view: `remove()` confirm+submit (`log.html.php:40-49`),
`editP(id)` re-targets the form to `com_person` edit (`log.html.php:32-38`),
`filterChange()` copies date fields and submits (`log.html.php:51-55`).

## Data touched
- DAOs:
  - `LogDAO::getLogCount($logLevel, $personid, $dateFrom, $dateTo)` (`log.index.php:86`)
  - `LogDAO::getLogArray($logLevel, $personid, $dateFrom, $dateTo, $limitstart, $limit)` (`log.index.php:87`)
  - `LogDAO::getPersonArrayWhenInLog()` (`log.index.php:89`) — persons that appear in the log
  - `LogDAO::removeLogByID($id)` (`log.index.php:106`)
- Tables:
  - `log` (`includes/tables/Log.php`) — columns `LO_logid`, `LO_datetime`, `LO_level`, `LO_personid`, `LO_log` (queried in `LogDAO`, `includes/dao/LogDAO.php:28-71,108-117`).
  - `person` (`includes/tables/Person.php`) — joined for logger name (`PE_personid`, `PE_firstname`, `PE_surname`) in `getPersonArrayWhenInLog()` (`includes/dao/LogDAO.php:78`).

Date handling: `date_from`/`date_to` parsed with `DateUtil` and swapped if inverted
(`log.index.php:57-84`); `date_to` is bumped by one day so the `<` upper bound is
inclusive (`log.index.php:82-84`).

## Forms & fields
Single form `adminForm` (`log.html.php:119`). Filters are persisted per option in
`$_SESSION['UI_SETTINGS']['com_log']['filter']` (read at `log.index.php:52-55`).

| Field | Column/meaning | Validation |
|-------|----------------|------------|
| `filter[log_level]` (select) | log severity filter; `0` = all; options from `Log::$LEVEL_ARRAY` (`log.html.php:124-133`; `includes/tables/Log.php:41-58`) | none; auto-submits `onchange` |
| `date_from` (text) + `filter[date_from]` (hidden) | lower time bound; `dd.MM.yyyy` (`log.html.php:135-142`) | client `CalendarPopup` picker; server parses via `DateUtil`, silently ignores parse failure (`log.index.php:60-63`) |
| `date_to` (text) + `filter[date_to]` (hidden) | upper time bound; `dd.MM.yyyy` (`log.html.php:148-154`) | as above (`log.index.php:65-68`) |
| `filter[personid]` (select) | actor filter; `0` = all users; options from `$persons` (`log.html.php:161-171`) | none; auto-submits `onchange` |
| `cid[]` (checkbox) | selected `LO_logid`s for delete (`log.html.php:217-219`) | JS `remove()` requires `boxchecked != 0` (`log.html.php:40-49`) |
| `option` / `LO_logid` / `PE_personid` / `task` / `boxchecked` / `hidemainmenu` (hidden) | routing + selection state (`log.html.php:239-244`) | none |

Note: the hidden `filter[date_to]` input at `log.html.php:149` prints its value via
`<?php $filter['date_to']; ?>` **without `echo`**, so that hidden field renders empty
(the visible `date_to` text input at line 151 is populated correctly).

## Related flows & cross-module links
- Events fired (`EventCrossBar`): none.
- Redirects: `remove` redirects back to `index2.php?option=com_log` (`log.index.php:108`);
  empty selection triggers `Core::backWithAlert` (`log.index.php:102`).
- Cross-module: each Level/Actor cell links via JS `editP()` to
  `com_person` edit for the logging person (`log.html.php:32-38,224,227`).
- Shared DAOs: `LogDAO`, `PersonDAO` (required at `log.index.php:21-22`; only
  `LogDAO` methods are invoked).

## Source anchors
- `modules/com_log/log.index.php:25-40` — task read + `switch` dispatch.
- `modules/com_log/log.index.php:44-93` — `showLog()` (filters, dates, DAO calls, pageNav).
- `modules/com_log/log.index.php:98-110` — `removeLog()` delete + redirect.
- `includes/dao/LogDAO.php:24-71` — count/list queries against `log`.
- `includes/dao/LogDAO.php:74-82` — `getPersonArrayWhenInLog()` join.
- `includes/dao/LogDAO.php:108-117` — `removeLogByID()`.
- `modules/com_log/log.html.php:124-172` — filter controls.
- `modules/com_log/log.html.php:176-238` — log table + footer.
- `includes/tables/Log.php:41-58` — level constants / `$LEVEL_ARRAY`.
- `site/index2.php:117-119` — USER force-redirect to `com_myprofile`.
