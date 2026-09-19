# com_massmessages

> Bulk messaging screen: browse the network tree, pick IPs/recipients in the selected leaf subnets, then send an SMS blast to the chosen persons via the SMS gateway.

## Access

- Authenticated session required (`site/index2.php:64-70`).
- Regular users (`Group::USER = 0`) cannot reach it: they are forced to `com_myprofile` (`site/index2.php:117-119`, `includes/tables/Group.php:37`). In practice this is an administration-agenda screen, linked under "Administration" in the main menu (`modules/com_common/html_mainmenu.php:54`), reachable by `Group::ADMINISTRATOR = 5` / `Group::SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:38-39`).
- No per-module ACL gate beyond the session check; `MainFrame::getPath()` resolves the controller and falls back to `com_admin` only if missing (`includes/Mainframe.php:69-84`).

## Entry point

- Option: `?option=com_massmessages`
- Controller: `modules/com_massmessages/massmessages.index.php`
- View class: `HTML_massmessages` in `massmessages.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `newMessage` | Toolbar "New mass message" → `javascript:newMessage()` → `submitbutton('newMessage')` (`massmessages.html.php:47-50,67`) with selected `cid[]` (IP ids) | `newMessage($cid)` `massmessages.index.php:38-40,132-180` | `$_REQUEST['cid']` = `IP_ipid[]` (`:32,135`); `PersonDAO::getPersonByIPId` (`:136`); `IpDAO::getIpByID` (`:137`) | none | `HTML_massmessages::newMessage()` (`:179`) | none — partitions recipients into with-mobile / without-mobile / without-email / inactive / with-email (emails joined for clipboard) (`:172-177`) |
| `sendMessage` | New-message toolbar "New mass message" → `javascript:sendMessage()` → `submitbutton('sendMessage')` (`massmessages.html.php:260-263,300`) with `message` textarea + `pid[]` | `sendMessage()` `massmessages.index.php:42-44,185-225` | `$_REQUEST['message']` (`:189`); `$_REQUEST['pid']` = `PE_personid[]` (`:190`); `Core::SMS_USERNAME`/`SMS_PASSWORD` (`:192-193`); `PersonDAO::getPersonById` (`:197`) | none | none (redirect only) | Sends SMS per person via `ApiXml30::send_message($person->PE_tel, $message, ...)` (`:199-200`); `$database->log` INFO/ERROR + `$appContext->insertMessage` per result (`:204-220`); `Core::redirect` to `com_massmessages` (`:224`). Skips entirely when `message` is blank (`:195`) |
| _default_ (no/other `task`, initial load) | Menu link `index2.php?option=com_massmessages` (`html_mainmenu.php:54`); tree/network click `javascript:show(id)` → `submitform('show')` (`massmessages.html.php:39-45`) | `showNetwork($nid)` `massmessages.index.php:46-48,57-127` | `$_SESSION['UI_SETTINGS']['com_massmessages']['filter']['NE_networkid']` (`:63`); `NetworkDAO::getNetworkArray` (`:67`), `IpDAO::getIpArray` (`:70`), `PersonDAO::getPersonArray` (`:73`), `NetworkDAO::getFirstNetworkByParentNetworkID` (`:79`), `NetworkDAO::isLeafNetwork` (`:105`) | none | `HTML_massmessages::showNetwork()` (`:126`) | validates each IP against its subnet and logs ERROR on mismatch (`:94-96`) |

Switch is `massmessages.index.php:37-49`. Cases: `newMessage`, `sendMessage`, `default` — all covered. `$nid` comes from `$_REQUEST['NE_networkid']`, `$cid` from `$_REQUEST['cid']` coerced to array (`:31-35`).

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_massmessages::showNetwork()` | Left: JS `dTree` of the network hierarchy; right: selected-network summary (netmask/broadcast/owner) and a per-subnet grouped table of IPs with `cid[]` checkboxes; toolbar "New mass message" | `massmessages.html.php:32-252` |
| `HTML_massmessages::newMessage()` | SMS body `textarea` + four recipient tables (to-notify with `pid[]` checkboxes, without-mobile, without-email, inactive); toolbar Copy-emails-to-clipboard / New mass message / Cancel | `massmessages.html.php:254-536` |
| `HTML_massmessages::buildTree()` | Emits `dTree` `add()` JS calls for the network tree (recursive) | `massmessages.html.php:544-559` |

## Data touched

- DAOs: `NetworkDAO` (`getNetworkArray`, `getFirstNetworkByParentNetworkID`, `isLeafNetwork`), `IpDAO` (`getIpArray`, `getIpByID`), `PersonDAO` (`getPersonArray`, `getPersonByIPId`, `getPersonById`). Additional DAOs `require_once`'d but unused in the controller: `IpAccountDAO`, `IpAccountAbsDAO` (`massmessages.index.php:21-25`).
- Tables: none written. Read-only: `network`, `ip`, `person` via the DAOs above.
- External: SMS gateway `ApiXml30` (`includes/smsgateapi_sluzba_cz/apixml30.php`, required at `massmessages.index.php:26`); IPv4 helper `Net_IPv4` (`Net/IPv4.php`, `:28`).

## Forms & fields

`showNetwork` form (`massmessages.html.php:93-243`) posts hidden `option=com_massmessages`, `NE_networkid`, `PE_personid`, `IP_ipid`, `task`, `boxchecked`, `hidemainmenu`, and `filter[NE_networkid]`:

| Field | Column / meaning | Validation |
|-------|------------------|-----------|
| `cid[]` | selected `IP_ipid` per row (`:203-205`) | none server-side beyond array coercion (`index:32-35`); JS `checkAll`/`isChecked` toggle |
| `filter[NE_networkid]` | highlighted network id, persisted to session filter (`:241-242`) | none |

`newMessage` form (`massmessages.html.php:333-527`) posts hidden `option`, `task`, `boxchecked`, `hidemainmenu`:

| Field | Column / meaning | Validation |
|-------|------------------|-----------|
| `message` | SMS text body (`:336`) | server: send skipped if `trim($message) === ""` (`index:195`); no client `validator.js` rule |
| `pid[]` | selected `PE_personid` recipients (`:366-368`) | none |

Recipient partitioning helpers (`personsWithMobile`/`personsWithoutMobile`/`personsWithEmail`/`personsWithoutEmail`/`inactivePersons`) key off `PE_tel`, `PE_email` and `Person::STATUS_ACTIVE` (`index:142-176`).

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none.
- Redirects: `sendMessage` → `index2.php?option=com_massmessages` (`:224`).
- Shared DAOs: `NetworkDAO`, `IpDAO`, `PersonDAO` (also used by `com_network`, `com_myprofile`, `com_person`).
- Distinct from `com_message`: this module sends SMS via the gateway inline; `com_message` sends pending e-mail via `EmailUtil`.

## Source anchors

- Switch / dispatch: `massmessages.index.php:37-49`
- `showNetwork()`: `massmessages.index.php:57-127`
- `newMessage()`: `massmessages.index.php:132-180`
- `sendMessage()` (SMS blast): `massmessages.index.php:185-225`
- Tree builders: `massmessages.index.php:235-283` (`buildNetworkTree`, `findLeafSubnets`)
- `HTML_massmessages::showNetwork()`: `massmessages.html.php:32-252`
- `HTML_massmessages::newMessage()`: `massmessages.html.php:254-536`
- Access: `site/index2.php:117-119`; `includes/tables/Group.php:37-39`
