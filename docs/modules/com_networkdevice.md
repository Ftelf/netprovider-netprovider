# com_networkdevice

> Read-only view of the configured network device (Linux/RouterOS) plus a "Test login" action that opens a live connection and echoes device info.

## Access

No per-module ACL check exists in the controller. Reachability is governed only by the global session gate and the forced-routing rule in `site/index2.php`: any authenticated user whose session validates reaches the requested option, except `Group::USER` (`GR_level == 0`), who is unconditionally rewritten to `com_myprofile` (`site/index2.php:117-119`). Group levels are `USER = 0`, `ADMINISTRATOR = 5`, `SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:37-39`); therefore only `ADMINISTRATOR` and `SUPER_ADMINISTRATOR` reach `com_networkdevice`. `MainFrame::getPath()` resolves the controller path and falls back to `com_admin` for an unknown option (`includes/Mainframe.php:69-84`). The menu link is emitted at `modules/com_common/html_mainmenu.php:46`.

## Entry point

- Option: `?option=com_networkdevice`
- Controller: `modules/com_networkdevice/networkdevice.index.php`
- View class: `HTML_NetworkDevice` in `networkdevice.html.php`

## Tasks

The controller dispatches on `$_REQUEST['task']` (`networkdevice.index.php:26`, switch `28-36`).

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `testLogin` | `submitbutton('testLogin')` → `submitform('testLogin')` toolbar "Test login" (`networkdevice.html.php:31-36,52-58`) | `networkdevice.index.php:29` → `testLogin()` (`:56`) | `Core::getProperty` for `NETWORK_DEVICE_LOGIN`, `_HOST`, `_PLATFORM`, `_COMMAND_IPTABLES` (`:60-66`) | none | none — always `Core::redirect("index2.php?option=com_networkdevice")` (`:112`) | opens a live device connection: `LinuxCommander` runs `uname -a` and an iptables version/`find` probe over SSH (`:68-85`), or `RouterOSCommander` calls `/system/routerboard/print` + `/system/resource/print` over the MikroTik API (`:87-103`); results/errors pushed as flash messages via `$appContext->insertMessage` (`:70-109`); throws for unknown platform, caught into a failure flash (`:104-109`) |
| `default` (list) | any other/absent task; landing on `?option=com_networkdevice` | `networkdevice.index.php:33` → `showNetworkDevice()` (`:38`) | 8 `Core::getProperty` config values (`:43-50`) | none | `HTML_NetworkDevice::showNetworkDevice` (`:53`) | none |

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_NetworkDevice::showNetworkDevice()` | Read-only table of the device configuration (Platform, Host, Port, Login, Password, WAN Interface, Command sudo, Command iptables) and a single "Test login" toolbar button. No editable inputs. | `networkdevice.html.php:27-138` |

## Data touched

- DAOs: none.
- Tables: none.
- Configuration only, via `Core::getProperty()` on constants defined in `includes/Core.php:54-61`: `NETWORK_DEVICE_PLATFORM`, `NETWORK_DEVICE_HOST`, `NETWORK_DEVICE_PORT`, `NETWORK_DEVICE_LOGIN`, `NETWORK_DEVICE_PASSWORD`, `NETWORK_DEVICE_WAN_INTERFACE`, `NETWORK_DEVICE_COMMAND_SUDO`, `NETWORK_DEVICE_COMMAND_IPTABLES`. Values originate from `config/netprovider.ini`.

## Forms & fields

Single form `name="adminForm"` posting to `index2.php` (`networkdevice.html.php:79`). It carries only two hidden dispatch fields — `option` (`com_networkdevice`) and `task` (`:127-128`) — set by the `submitbutton('testLogin')` JS (`:31-36`). All configuration values are rendered as plain table cells, not form inputs (`:91-121`); there is no create/update path and therefore no client or server field validation. `testLogin` reads its values from `Core::getProperty`, not from POST.

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none.
- Redirects: `testLogin` always returns to `?option=com_networkdevice` after attempting the connection (`networkdevice.index.php:112`).
- Commanders: the controller directly `require_once`s and instantiates `LinuxCommander` and `RouterOSCommander` (`networkdevice.index.php:23-24,68,87`) with a "test" flag; it does not go through `CommanderCrossbar` (that orchestration path is used by the CLI networking service, `services/service.php --proceed-networking`).
- Configuration is managed elsewhere: this screen only displays `config/netprovider.ini` values (see `com_configuration`).

## Source anchors

- Dispatch switch: `modules/com_networkdevice/networkdevice.index.php:28-36`
- Handlers: `showNetworkDevice:38`, `testLogin:56` (platform branch `65-106`, exception handling `107-110`, redirect `112`)
- Commander requires/instantiation: `networkdevice.index.php:23-24,68,87`
- View: `networkdevice.html.php` — `showNetworkDevice:27`
- Config constants: `includes/Core.php:54-61`
- Access/routing: `site/index2.php:117-119`, `includes/Mainframe.php:69-84`, `includes/tables/Group.php:37-39`
