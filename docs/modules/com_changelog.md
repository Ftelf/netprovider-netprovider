# com_changelog

> Displays the raw contents of the repository `CHANGELOG.md` in a scrollable pane.

## Access
No per-module ACL check exists in the module. Reachability is governed solely by
the global gate in `site/index2.php`: the request must pass session validation
(`site/index2.php:64-70`), and any user whose `GR_level == Group::USER` (level `0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`). So the screen is reachable by `Group::ADMINISTRATOR`
(`5`) and `Group::SUPER_ADMINISTRATOR` (`9`) only. Unknown options fall back to
`com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-83`). The menu
entry is under "Help" (`modules/com_common/html_mainmenu.php:61`).

## Entry point
- Option: `?option=com_changelog`
- Controller: `modules/com_changelog/changelog.index.php`
- View class: `HTML_changelog` in `changelog.html.php`

## Tasks
The controller has **no `switch`** and reads no `task` parameter; it unconditionally
calls `show()` (`changelog.index.php:23`).

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| (default / only) | any request to the option | `show()` `changelog.index.php:30-40` | `CHANGELOG.md` via `fopen`/`fread` (`changelog.index.php:34-37`) | none | `HTML_changelog::showChangelog()` (`changelog.index.php:39`) | none |

Beyond the default render there are **no tasks** — no create/update/delete.

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_changelog::showChangelog($changelogText)` | Toolbar header plus a fixed-height (400px) scrollable `<div>` containing the changelog text inside a `<pre>` block | `changelog.html.php:28-85` |

## Data touched
- DAOs: none.
- Tables: none. Data source is the file `CHANGELOG.md` at the app root, read via
  `$core->getAppRoot() . "CHANGELOG.md"` (`changelog.index.php:34`).

## Forms & fields
Single form `adminForm` (`changelog.html.php:59`), non-interactive. It contains only
hidden fields and no submit control:

| Field | Column/meaning | Validation |
|-------|----------------|------------|
| `option` (hidden) | value `com_scripts` (`changelog.html.php:71`) | none |
| `task` (hidden) | empty (`changelog.html.php:72`) | none |
| `hidemainmenu` (hidden) | `0` (`changelog.html.php:73`) | none |
| `filter[void]` (hidden) | `0` (`changelog.html.php:74`) | none |

Note: the hidden `option` is `com_scripts`, not `com_changelog`
(`changelog.html.php:71`) — a copy artifact; the form is never submitted so it has
no runtime effect. The changelog body itself is echoed raw (`changelog.html.php:65`).

## Related flows & cross-module links
- Events fired (`EventCrossBar`): none.
- Redirects to other options: none.
- Shared DAOs: none.

## Source anchors
- `modules/com_changelog/changelog.index.php:23` — unconditional `show()` call.
- `modules/com_changelog/changelog.index.php:34-37` — `CHANGELOG.md` file read.
- `modules/com_changelog/changelog.index.php:39` — view dispatch.
- `modules/com_changelog/changelog.html.php:28` — `showChangelog()` view.
- `modules/com_changelog/changelog.html.php:64-65` — scrollable pane / `<pre>` output.
- `modules/com_changelog/changelog.html.php:71-74` — hidden form fields.
- `site/index2.php:117-119` — USER force-redirect to `com_myprofile`.
