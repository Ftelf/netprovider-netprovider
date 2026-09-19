# com_network

> Tree-based management of IP networks/subnets and the individual IP addresses assigned to persons within them.

## Access

No per-module ACL check exists in the controller. Reachability is governed only by the global session gate and the forced-routing rule in `site/index2.php`: any authenticated user whose session validates reaches the requested option, except `Group::USER` (`GR_level == 0`), who is unconditionally rewritten to `com_myprofile` (`site/index2.php:117-119`). Group levels are `USER = 0`, `ADMINISTRATOR = 5`, `SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:37-39`); therefore only `ADMINISTRATOR` and `SUPER_ADMINISTRATOR` reach `com_network`. `MainFrame::getPath()` resolves the controller path and falls back to `com_admin` for an unknown option (`includes/Mainframe.php:69-84`). The menu link is emitted at `modules/com_common/html_mainmenu.php:45`.

## Entry point

- Option: `?option=com_network`
- Controller: `modules/com_network/network.index.php`
- View class: `HTML_Network` in `network.html.php`

## Tasks

The controller dispatches on `$_REQUEST['task']` (`network.index.php:29`, switch `38-81`). Request params read up front: `NE_networkid` → `$nid` (`:30`), `IP_networkid` → `$inid` (`:31`, unused after read), `IP_ipid` → `$iid` (`:32`), `cid` array → `$cid` (`:33-36`). The two save handlers (`saveIP`, `saveNetwork`) contain their own inner switches on `$task` that only select the post-save redirect/flash message — those cases are listed below as separate rows for full coverage.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `newI` | `newI()` JS → `submitbutton('newI')` toolbar "New IP address" (`network.html.php:68-71,181-189`) | `network.index.php:39` → `editIP(null, $nid)` (`:228`) | `NetworkDAO::getNetworkByID`, `NetworkDAO::isLeafNetwork`, `PersonDAO::getPersonArray`, `IpDAO::getIpArray` | none | `HTML_Network::editIP` (`:281`) | redirects to list + logs ERROR if target not a leaf network or network full (`:246-251,273-276`) |
| `newN` | `newN()` JS → `submitbutton('newN')` toolbar "New network" (`network.html.php:73-76,118-126`) | `network.index.php:43` → `editNetwork($task, $nid)` (`:290`) | `NetworkDAO::getNetworkByID`, `NetworkDAO::getNetworkArrayByParentNetworkID`, `PersonDAO::getPersonArray`; free-subnet enumeration via `getFreeSubNetworks`/`isAnyFreeSubNetworks` | none | `HTML_Network::editNet` (`:360`) | redirects + logs ERROR if no free subnet space (`:339-341,349-352`); sets `$flags['NE_net']` to `LIST`/`TEXTBOX` |
| `editI` | `editI(id)` JS → `submitform('editI')`, IP address link (`network.html.php:46-51,392,404`) | `network.index.php:47` → `editIP($iid, $nid)` (`:228`) | `IpDAO::getIpByID`, `NetworkDAO::getNetworkByID`, `NetworkDAO::isLeafNetwork`, `PersonDAO::getPersonArray`, `IpDAO::getIpArray` | none | `HTML_Network::editIP` (`:281`) | same leaf/full-network guards as `newI` (`:246-276`) |
| `editN` | `editN()` JS → `submitform('editN')` toolbar "Edit network" (`network.html.php:53-58,139-147`) | `network.index.php:51` → `editNetwork($task, $nid)` (`:290`) | `NetworkDAO::getNetworkByID` (network + parent), `NetworkDAO::getNetworkArrayByParentNetworkID`, `PersonDAO::getPersonArray` | none | `HTML_Network::editNet` (`:360`) | `$flags['NE_net'] = "DISABLED"` (address not editable on edit) (`:356`) |
| `editA` | `editIA()` JS → `submitbutton('editA')` toolbar "Edit IP address" (checkbox selection) (`network.html.php:78-85,202-210`) | `network.index.php:55` → `editIP($cid[0], null)` (`:228`) | same as `editI`; edits first checked `cid` row | none | `HTML_Network::editIP` (`:281`) | leaf/full-network guards (`:246-276`) |
| `saveI` | `submitbutton('save')` → `submitform('saveI')` on IP edit form (`network.html.php:476-478`) | `network.index.php:59` → `saveIP($task, $iid, $nid)` (`:370`) | `Database::bind($_POST, $ip)`, `NetworkDAO::getNetworkByID`, `NetworkDAO::isLeafNetwork`, `PersonDAO::getPersonByID` | `Database::insertObject`/`updateObject` → `ip` (`:391,393`) | none (redirects) | flash + INFO log, redirect to list (inner switch `:406-412`); ERROR log + redirect if non-leaf (`:383-388`) |
| `applyI` | `submitbutton('apply')` → `submitform('applyI')` on IP edit form (`network.html.php:473-475`) | `network.index.php:60` → `saveIP($task, $iid, $nid)` (`:370`) | same as `saveI` | `Database::insertObject`/`updateObject` → `ip` (`:391,393`) | none (redirects) | flash + INFO log, redirect back to `editI` (stay on form) (inner switch `:400-405`) |
| `saveN` | `submitbutton('save')` → `submitform('saveN')` on network edit form (`network.html.php:695-697`) | `network.index.php:64` → `saveNetwork($task)` (`:421`) | `Database::bind($_POST, $network)`, `NetworkDAO::getNetworkByID`, `NetworkDAO::getNetworkArrayByParentNetworkID`, `PersonDAO::getPersonByID` | `Database::insertObject`/`updateObject` → `network` (`:489,491`) | none (redirects) | format/containment/collision validation (`:433-471`); tamper check on edit (`:477-485`); flash + INFO log, redirect to parent network (inner switch `:504-510`) |
| `applyN` | `submitbutton('apply')` → `submitform('applyN')` on network edit form (`network.html.php:692-694`) | `network.index.php:65` → `saveNetwork($task)` (`:421`) | same as `saveN` | `Database::insertObject`/`updateObject` → `network` (`:489,491`) | none (redirects) | same validation as `saveN`; flash + INFO log, redirect back to `editN` (stay on form) (inner switch `:498-503`) |
| `removeI` | `removeI()` JS → `submitbutton('removeI')` toolbar "Delete IP address" (confirm dialog) (`network.html.php:87-95,223-231`) | `network.index.php:69` → `removeIp($cid)` (`:519`) | `IpDAO::getIpByID`, `PersonDAO::getPersonByID` | `IpDAO::removeIpByID` → `ip`; `IpAccountDAO::removeIpAccountByIPID` → `ipaccount`; `IpAccountAbsDAO::removeIpAccountAbsByIPID` → `ipaccountabs` (`:533-535`) | none (redirects) | per-IP flash + INFO log; `backWithAlert` if no `cid` selected (`:522-523`) |
| `removeN` | `removeN()` JS → `submitbutton('removeN')` toolbar "Delete network" (confirm dialog) (`network.html.php:97-101,160-168`) | `network.index.php:73` → `removeNetwork($nid)` (`:549`) | `NetworkDAO::getNetworkByID`, `PersonDAO::getPersonByID`, `NetworkDAO::isLeafNetwork`, `IpDAO::isAnyIpInNetwork` | `NetworkDAO::removeNetworkByID` → `network` (`:571`) | none (redirects) | refuses + ERROR log if non-leaf (`:557-562`) or if network still holds IPs (`:565-570`); sets `$_SESSION['UI_SETTINGS']['com_network']['filter']['NE_networkid']` to parent (`:577`) |
| `cancel` | `submitbutton('cancel')` / `submitform('cancel')` on edit forms (`network.html.php:462-465,681-684`) | `network.index.php:77` (shares `default`) → `showNetwork($nid)` (`:87`) | see `default` | none | `HTML_Network::showNetwork` | returns to list |
| `default` (list) | any other/absent task; landing on `?option=com_network` | `network.index.php:78` → `showNetwork($nid)` (`:87`) | `NetworkDAO::getNetworkArray`, `IpDAO::getIpArray`, `PersonDAO::getPersonArray`, `NetworkDAO::getFirstNetworkByParentNetworkID`, `NetworkDAO::isLeafNetwork`, `NetworkDAO::getNetworkArrayByParentNetworkID` | none | `HTML_Network::showNetwork` (`:219`) | validates each IP is inside its subnet and logs ERROR mismatches (`:133-135`); reads filter/limit from `$_SESSION['UI_SETTINGS']['com_network']` (`:96-101`) |
| `applyI` (inner) | reached inside `saveIP` after a successful save when `$task == 'applyI'` | `network.index.php:400` | `$task` | none | none (redirects) | flash + INFO log; `Core::redirect` back to `editI` (`:401-405`) |
| `saveI` (inner) | reached inside `saveIP` after a successful save when `$task == 'saveI'` | `network.index.php:406` | `$task` | none | none (redirects) | flash + INFO log, falls through to `default` redirect to list (`:407-412`) |
| `applyN` (inner) | reached inside `saveNetwork` after a successful save when `$task == 'applyN'` | `network.index.php:498` | `$task` | none | none (redirects) | flash + INFO log; `Core::redirect` back to `editN` (`:499-503`) |
| `saveN` (inner) | reached inside `saveNetwork` after a successful save when `$task == 'saveN'` | `network.index.php:504` | `$task` | none | none (redirects) | flash + INFO log, falls through to `default` redirect to parent network (`:505-510`) |

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_Network::showNetwork()` | Two-pane list: left = `dTree` JavaScript network tree; right = selected-network summary (address/netmask/broadcast/description/owner) and a paginated table of IP addresses (optionally grouped by subnet header when `filter[netheaders]` is checked). Toolbar buttons enabled/disabled per `$flags`. | `network.html.php:32-444` |
| `HTML_Network::editIP()` | IP edit form: read-only parent-network panel, IPv4 address (free-text when the subnet is `<`/24, otherwise a `<select>` of available addresses), DNS, and owner `<select>`; Apply/Save/Cancel toolbar. | `network.html.php:453-662` |
| `HTML_Network::editNet()` | Network edit form: read-only parent panel, subnet address widget (hidden+disabled / textbox / `<select>` per `$flags['NE_net']`), description, owner `<select>`, and a table of sibling subnets; Apply/Save/Cancel toolbar. | `network.html.php:673-908` |
| `HTML_Network::buildTree()` | Helper (no standalone screen): recursively emits `dTree` `d.add(...)` JS calls for the network tree; invoked from `showNetwork` (`network.html.php:287`). | `network.html.php:915-926` |

## Data touched

- DAOs:
  - `NetworkDAO` — `getNetworkArray`, `getNetworkByID`, `getFirstNetworkByParentNetworkID`, `getNetworkArrayByParentNetworkID`, `isLeafNetwork`, `removeNetworkByID` (`includes/dao/NetworkDAO.php`)
  - `IpDAO` — `getIpArray`, `getIpByID`, `removeIpByID`, `isAnyIpInNetwork` (`includes/dao/IpDAO.php`)
  - `IpAccountDAO` — `removeIpAccountByIPID` (`includes/dao/IpAccountDAO.php:160`)
  - `IpAccountAbsDAO` — `removeIpAccountAbsByIPID` (`includes/dao/IpAccountAbsDAO.php:76`)
  - `PersonDAO` — `getPersonArray`, `getPersonByID`
  - `Database` (direct) — `bind`, `insertObject("ip"|"network")`, `updateObject("ip"|"network")` (`network.index.php:375,391,393,428,489,491`)
- Tables:
  - `Network` (`includes/tables/Network.php`) — columns `NE_networkid`, `NE_parent_networkid`, `NE_personid`, `NE_net`, `NE_description`
  - `Ip` (`includes/tables/Ip.php`) — columns `IP_ipid`, `IP_networkid`, `IP_personid`, `IP_address`, `IP_dns`
  - `ipaccount`, `ipaccountabs` — traffic-accounting rows cascaded on IP delete (write-only from this module, via the two removal DAOs above)

## Forms & fields

All three screens post to `index2.php` as `name="adminForm"`; `submitform`/`submitbutton` set the hidden `task` field then submit.

**List (`showNetwork`, `network.html.php:265-435`)**

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `filter[netheaders]` (checkbox) | UI-only; toggles per-subnet header rows; auto-submits on change (`:269-270`) | none; persisted to `$_SESSION['UI_SETTINGS']['com_network']['filter']` |
| `cid[]` (checkboxes) | selected `IP_ipid`s for edit/delete (`:399-401`) | client: `editIA`/`removeI` alert if `boxchecked == 0` (`:78-95`); `removeI` also `window.confirm` (`:91`) |
| `NE_networkid` / `filter[NE_networkid]` (hidden) | currently selected network id (`:427,433-434`) | none |
| `IP_ipid`, `PE_personid`, `task`, `boxchecked`, `hidemainmenu` (hidden) | dispatch/selection state (`:428-432`) | none |

**IP edit (`editIP`, `network.html.php:537-653`)**

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `IP_address` (text or `<select>`) | `Ip.IP_address`; `<select>` of free addresses when subnet is `>=`/24, else free text (`:581-599`) | client: `submitbutton` alerts if `IP_address == "0"` (`:468-469`); server: `saveIP` aborts + ERROR-logs if target network is not a leaf (`network.index.php:383-388`) |
| `IP_dns` (text) | `Ip.IP_dns` (`:604`) | none |
| `IP_personid` (`<select>`) | `Ip.IP_personid` owner (`:625-636`) | client: alert if `IP_personid == "0"` (`:470-471`) |
| `IP_networkid`, `NE_networkid`, `IP_ipid`, `option`, `task`, `hidemainmenu` (hidden) | binding/dispatch state (`:647-652`) | none |
| `PE_firstname`, `PE_surname` (disabled text, `:550,556,561`) | display-only mirror of parent network / owner; **disabled, not submitted** — reused input names, not real bindings | n/a |

**Network edit (`editNet`, `network.html.php:755-899`)**

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `NE_net` (hidden+disabled / text / `<select>`) | `Network.NE_net`; widget chosen by `$flags['NE_net']`: `DISABLED` on edit (hidden mirror + disabled box), `TEXTBOX` for `<`/24 new subnet, `LIST` (`<select>` of `possibleNetworkArray`) for `>=`/24 new subnet (`:808-829`) | client: alert if `NE_net == "0"` (`:687-688`); server: `Net_IPv4::parseAddress` format check (`network.index.php:433,441`), subnet-of-parent + non-identical + collision checks on new (`:445-471`), tamper check that posted `NE_net` matches DB on edit (`:477-485`) |
| `NE_description` (text) | `Network.NE_description` (`:834`) | none |
| `NE_personid` (`<select>`) | `Network.NE_personid` owner (`:840-852`) | client: alert if `NE_personid == "0"` (`:689-690`) |
| `NE_parent_networkid`, `NE_networkid`, `option`, `task`, `hidemainmenu` (hidden) | binding/dispatch state (`:893-898`) | none |
| `void` (disabled text, `:768,774,780,786`) | display-only parent-network fields; **disabled, not submitted** | n/a |

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none — this module dispatches no events.
- Cross-module redirect: the `editP(id)` JS handler rewrites the form's `option` to `com_person`, sets `PE_personid`, and submits `task=edit`, jumping to the person editor from any owner link (`network.html.php:60-66,288,315,346,410`).
- Intra-module redirects: `applyI`/`applyN` redirect back to the edit form to keep editing (`network.index.php:404,502`); `saveN`/`removeN` redirect to the parent network view (`:509,561`); `removeN` also seeds the session filter to the parent before redirecting (`:577-578`).
- Network-device side effects: none from the UI. QoS/filter rules are pushed to the device only by the CLI service (`services/service.php --proceed-networking`) via `CommanderCrossbar` (`includes/net/CommanderCrossbar.php`); the web module writes DB rows only.
- Shared DAOs: `PersonDAO` (owner lists), `NetworkDAO`/`IpDAO` (also used by `com_person`, `com_personaccount`, `com_iptrafficreport`).

## Source anchors

- Dispatch switch: `modules/com_network/network.index.php:38-81`
- Handlers: `showNetwork:87`, `editIP:228`, `editNetwork:290`, `saveIP:370` (inner switch `399-413`), `saveNetwork:421` (inner switch `497-511`), `removeIp:519`, `removeNetwork:549`
- Subnet math helpers: `buildNetworkTree:589`, `findLeafSubnets:621`, `getFreeSubNetworks:647`, `subNetworkPermutation:700`, `subNetworkPermutationByRange:727`, `isAnyFreeSubNetworks:764`, `isSpaceForSubNetwork:818`
- Views: `network.html.php` — `showNetwork:32`, `editIP:453`, `editNet:673`, `buildTree:915`
- Access/routing: `site/index2.php:117-119`, `includes/Mainframe.php:69-84`, `includes/tables/Group.php:37-39`
- DAO/tables: `includes/dao/NetworkDAO.php`, `includes/dao/IpDAO.php`, `includes/dao/IpAccountDAO.php:160`, `includes/dao/IpAccountAbsDAO.php:76`, `includes/tables/Network.php`, `includes/tables/Ip.php`
