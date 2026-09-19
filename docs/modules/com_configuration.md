# com_configuration

> Read-only viewer that dumps the parsed `config/netprovider.ini` grouped by section.

## Access
No per-module ACL check exists in the module. Reachability is governed solely by
the global gate in `site/index2.php`: the request must pass session validation
(`site/index2.php:64-70`), and any user whose `GR_level == Group::USER` (level `0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`). Consequently the screen is reachable by
`Group::ADMINISTRATOR` (`5`) and `Group::SUPER_ADMINISTRATOR` (`9`) only. Unknown
options fall back to `com_admin` via `MainFrame::getPath()`
(`includes/Mainframe.php:77-83`). The menu entry is under "Administration"
(`modules/com_common/html_mainmenu.php:50`).

## Entry point
- Option: `?option=com_configuration`
- Controller: `modules/com_configuration/configuration.index.php`
- View class: `HTML_Configuration` in `configuration.html.php`

## Tasks
The controller has **no `switch`** and reads no `task` parameter; it unconditionally
calls `showConfiguration()` (`configuration.index.php:11`).

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| (default / only) | any request to the option | `showConfiguration()` `configuration.index.php:13-18` | `config/netprovider.ini` via `parse_ini_file(..., true)` (`configuration.index.php:17`) | none | `HTML_Configuration::showConfiguration()` (`configuration.html.php:15`) | none |

Beyond the default render there are **no tasks** — no create/update/delete, no
save. The rendered `<form>` (`configuration.html.php:46`) carries only a hidden
`option` field (`configuration.html.php:81`), has no submit control, and posts
nowhere meaningful; values are printed read-only inside single quotes
(`configuration.html.php:68`).

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_Configuration::showConfiguration($conf)` | One `adminlist` table per INI section (`$k`), each row a `name` → `'value'` pair; sections separated by `<br/>` | `configuration.html.php:15-92` |

## Data touched
- DAOs: none.
- Tables: none. The data source is the file `config/netprovider.ini`, read through
  PHP `parse_ini_file()` (`configuration.index.php:17`), not the database. App root
  resolved via `Core::getAppRoot()` (`$core->getAppRoot()`, `configuration.index.php:17`).

## Forms & fields
Single form `adminForm` (`configuration.html.php:46`), read-only:

| Field | Column/meaning | Validation |
|-------|----------------|------------|
| `option` (hidden) | `com_configuration` routing marker | none (`configuration.html.php:81`) |

Section titles (`$k`) and each `name`/`value` pair come straight from the parsed
INI and are echoed as text, not editable inputs (`configuration.html.php:55,65,68`).

## Related flows & cross-module links
- Events fired (`EventCrossBar`): none.
- Redirects to other options: none.
- Shared DAOs: none.
- Reads the same INI file that `Core::getProperty()` exposes elsewhere
  (`configuration.index.php:17`).

## Source anchors
- `modules/com_configuration/configuration.index.php:11` — unconditional render call.
- `modules/com_configuration/configuration.index.php:17` — `parse_ini_file(getAppRoot()."config/netprovider.ini", true)`.
- `modules/com_configuration/configuration.html.php:15` — `showConfiguration()` view.
- `modules/com_configuration/configuration.html.php:49-79` — section/row rendering loop.
- `modules/com_configuration/configuration.html.php:81` — hidden `option` field.
- `site/index2.php:117-119` — USER force-redirect to `com_myprofile`.
- `includes/Mainframe.php:69-84` — `getPath()` routing / `com_admin` fallback.
