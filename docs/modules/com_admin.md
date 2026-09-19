# com_admin

> Admin landing/dashboard: control-panel launcher, list of logged-in sessions (with force-logout), and the last 10 log entries.

## Access

No per-module ACL check exists inside the module. Access is governed globally in
`site/index2.php`: any user with `GR_level == Group::USER` (`includes/tables/Group.php:37`)
is force-routed to `com_myprofile` (`site/index2.php:117-119`), so `com_admin` is reachable
only by `ADMINISTRATOR` (5) and `SUPER_ADMINISTRATOR` (9). `com_admin` is also the default
landing option when none is supplied (`site/index2.php:40`) and the fallback for any unknown
option in `MainFrame::getPath()` (`includes/Mainframe.php:69-84`, fallback `:78`).

## Entry point

- Option: `?option=com_admin`
- Controller: `modules/com_admin/admin.index.php`
- View class: `HTML_admin` in `admin.html.php`

## Tasks

Main dispatch `switch ($task)` at `admin.index.php:31`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `force_logout` | "Force logout" link `force_logout(id)` (`admin.html.php:32-37`, `:203-205`) | `admin.index.php:32` → `force_logout($sid)` `:33` | `$_REQUEST['SE_sessionid']` `:29`; `SessionDAO::getSessionByID` `:56`; current `$_SESSION['SE_sessionid']` `:57` | `SessionDAO::removeSessionByID` → `session` `:60` | redirect to `com_admin` `:70` | skips own session (`:58`); flash + `Log::LEVEL_INFO` on success `:61-63`, `Log::LEVEL_WARNING` on exception `:65-67` |
| `default` (dashboard) | direct `?option=com_admin` (or no option) | `admin.index.php:36` → `show()` `:37` | `SessionDAO::getSessionArray` `:45`; `PersonDAO::getPersonArray` `:46`; `LogDAO::getLastLogArray(10)` `:47` | none | `HTML_admin::show()` `:49` | none |

The controller has a single `switch` with one `case '…'` (`force_logout`) plus `default`.
No inner switch exists.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_admin::show()` | Control-panel icon grid linking to other modules; "Logged in" tab (sessions table: user link, IP, ACL, seconds, Force-logout) and "Log" tab (last 10 log entries: logger name, date, message) | `admin.html.php:27` |

Control-panel icons link to: `com_person` (`admin.html.php:70`), `com_group` (`:79`),
`com_role` (`:89`), `com_bankaccount` (`:99`), `com_personaccount` (`:109`), `com_charge`
(`:119`), `com_network` (`:129`), `com_paymentreport` (`:139`), `com_scripts` (`:149`).
Each session row links to `com_person&task=edit` for that user (`admin.html.php:185`,`:191`).
Log rows label `LO_personid == 0` as "Cron" else resolve via `$persons` (`admin.html.php:236-239`).

## Data touched

- DAOs: `SessionDAO` (`getSessionArray` `admin.index.php:45`, `getSessionByID` `:56`,
  `removeSessionByID` `:60`); `PersonDAO` (`getPersonArray` `:46`); `LogDAO`
  (`getLastLogArray` `:47`).
- Tables: `session` (`includes/tables/Session.php`), `log` (`includes/tables/Log.php`),
  `person` (read-only, for logger/user names).

## Forms & fields

No editable/data-entry form. The `adminForm` (`admin.html.php:163`) carries only hidden
fields — `option`, `SE_sessionid`, `SE_personid`, `task`, `hidemainmenu`
(`admin.html.php:257-261`). The Force-logout link sets `SE_sessionid` via JS and submits
`task=force_logout` (`admin.html.php:32-37`). No `Validator` is attached.

## Related flows & cross-module links

- Events fired: none.
- Default/fallback landing page: served for missing option (`site/index2.php:40`) and for
  unknown options (`includes/Mainframe.php:78`).
- Cross-module navigation: the control-panel grid and session rows link to `com_person`,
  `com_group`, `com_role`, `com_bankaccount`, `com_personaccount`, `com_charge`,
  `com_network`, `com_paymentreport`, `com_scripts` (see Views).
- `force_logout` deletes another user's `session` row, ending their session on next request;
  it refuses to delete the current user's own session (`admin.index.php:57-58`).

## Source anchors

- Controller dispatch: `admin.index.php:31-39`.
- Handlers: `show()` `:41-50`, `force_logout()` `:52-71`.
- View: `admin.html.php:27` (`HTML_admin::show`).
- DAOs: `includes/dao/SessionDAO.php`, `includes/dao/LogDAO.php`,
  `includes/dao/PersonDAO.php`.
- Access/routing: `site/index2.php:40`,`:117-119`; `includes/Mainframe.php:69-84`.
