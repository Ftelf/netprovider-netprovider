# com_scripts

> Maintenance scripts screen: run the QoS/IP-filter operations (apply, remove, synchronize) against the configured network device and show the command output in a console panel.

## Access

- Authenticated session required (`site/index2.php:64-70`).
- Regular users (`Group::USER = 0`) cannot reach it — forced to `com_myprofile` (`site/index2.php:117-119`, `includes/tables/Group.php:37`). Administration-agenda screen, linked under "Administration" in the main menu (`modules/com_common/html_mainmenu.php:49`); reachable by `Group::ADMINISTRATOR = 5` / `Group::SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:38-39`).
- No per-module ACL gate beyond the session check; `MainFrame::getPath()` resolves the controller with `com_admin` fallback if missing (`includes/Mainframe.php:69-84`).

## Entry point

- Option: `?option=com_scripts`
- Controller: `modules/com_scripts/scripts.index.php`
- View class: `HTML_scripts` in `scripts.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `ipfilteron` | Toolbar "IP filter on" → `submitbutton('ipfilteron')` → `submitform('ipfilteron')` (`scripts.html.php:40-41,64`) | `ipFilterOn()` `scripts.index.php:27-29,49-66` | `CommanderCrossbar` build reads active persons + their internet charges/IPs/networks (`CommanderCrossbar.php:33-80`) | none (no DB tables) | `HTML_scripts::showScripts("IP filter on", $results)` (`:65`) | Applies IP-filter/QoS rules to the network device via `CommanderCrossbar::ipFilterUp()` → `commander->getIPFilterUp()` (`CommanderCrossbar.php:92-95`); exceptions captured into `$results` (`:57-63`) |
| `ipfilteroff` | Toolbar "IP filter off" → `submitbutton('ipfilteroff')` → `submitform('ipfilteroff')` (`scripts.html.php:42-43,72`) | `ipFilterOff()` `scripts.index.php:31-33,68-85` | as above | none | `HTML_scripts::showScripts("IP filter off", $results)` (`:84`) | Removes IP-filter rules via `CommanderCrossbar::ipFilterDown()` → `commander->getIPFilterDown()` (`CommanderCrossbar.php:87-90`); exceptions captured (`:76-82`) |
| `synchronizeFilter` | Toolbar "Synchronize IP filter" → `submitbutton('synchronizeFilter')` → `submitform('synchronizeFilter')` (`scripts.html.php:44-45,80`) | `synchronizeFilter()` `scripts.index.php:35-37,87-104` | as above | none | `HTML_scripts::showScripts("Synchronize IP filter", $results)` (`:103`) | Reconciles device rules with desired state via `CommanderCrossbar::synchronizeFilter()` → `commander->synchronizeFilter()` (`CommanderCrossbar.php:82-85`); exceptions captured (`:95-101`) |
| _default_ (no/other `task`, initial load) | Menu link `index2.php?option=com_scripts` (`html_mainmenu.php:49`) | `show()` `scripts.index.php:39-41,44-47` | none | none | `HTML_scripts::showScripts('', [])` (empty console) (`:46`) | none |

Switch is `scripts.index.php:26-42`. Cases: `ipfilteron`, `ipfilteroff`, `synchronizeFilter`, `default` — all covered. `$task` read via `$_REQUEST['task'] ?? null` (`:24`).

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_scripts::showScripts()` | Toolbar with the three run buttons; header showing the run command (or `N/A`); black console `div` printing each `$results` row as up to three colored lines (`[0]` green, `[1]` purple, `[2]` orange) | `scripts.html.php:30-134` |

## Data touched

- DAOs: none called directly by the controller. Indirectly, `CommanderCrossbar::__construct()` uses `PersonDAO::getPersonArray`, `HasChargeDAO::getHasChargeWithInternetChargeOnlyByPersonID`, `IpDAO::getIpArrayByPersonID`, `NetworkDAO::getNetworkByID` (`includes/net/CommanderCrossbar.php:38-70`).
- Tables: none written or read by this module's controller/view.
- Network device: `CommanderCrossbar` selects `LinuxCommander` or `RouterOSCommander` based on `Core::NETWORK_DEVICE_PLATFORM` (`LINUX`/`ROUTEROS`), throwing on any other value (`CommanderCrossbar.php:72-79`). The three tasks map to the same operations available on the CLI service (`services/service.php --ip-filter-up|--ip-filter-down|--proceed-networking`).

## Forms & fields

`HTML_scripts::showScripts` renders a single form (`scripts.html.php:106-125`) with only hidden fields `option=com_scripts`, `task`, `hidemainmenu`. There are no user-input fields — actions are driven entirely by the toolbar buttons setting `task` via `submitform(...)` (`scripts.html.php:33-47`). Validation: none.

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none.
- Redirects: none — each handler renders `HTML_scripts::showScripts` in place with the command output.
- Shared components: `CommanderCrossbar` and its `LinuxCommander`/`RouterOSCommander` back-ends (also driven from `services/service.php` and `com_network`).

## Source anchors

- Switch / dispatch: `scripts.index.php:26-42`
- `show()`: `scripts.index.php:44-47`
- `ipFilterOn()`: `scripts.index.php:49-66`
- `ipFilterOff()`: `scripts.index.php:68-85`
- `synchronizeFilter()`: `scripts.index.php:87-104`
- `HTML_scripts::showScripts()`: `scripts.html.php:30-134`
- `CommanderCrossbar` (construct + operations): `includes/net/CommanderCrossbar.php:33-101`
- Access: `site/index2.php:117-119`; `includes/tables/Group.php:37-39`
