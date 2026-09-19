# com_person

> Customer (person) management: create/edit/delete persons, assign user groups
> and roles, and attach billing charges (HasCharge) with their IP addresses.

## Access

No per-option ACL check exists inside the module. Reachability is governed
globally in `site/index2.php`: any authenticated session whose group level is
`Group::USER` (0) is force-routed to `com_myprofile`
(`site/index2.php:117-119`), so `com_person` is reachable only by
`Group::ADMINISTRATOR` (5) and `Group::SUPER_ADMINISTRATOR` (9)
(`includes/tables/Group.php:37-39`). `MainFrame::getPath()` maps
`?option=com_person` to `modules/com_person/person.index.php`, falling back to
`com_admin` if the file is missing (`includes/Mainframe.php:69-84`). The module
guards against direct inclusion via `defined('VALID_MODULE')`
(`person.index.php:18`).

## Entry point

- Option: `?option=com_person`
- Controller: `modules/com_person/person.index.php`
- View class: `HTML_person` in `person.html.php`

Request parameters read up front: `task`, `PE_personid` (`$pid`), `cid` (array),
`RM_rolememberid` (`$rmid`), `CH_chargeid` (`$chid`), `HC_haschargeid` (`$hcid`),
`RO_roleid` (`$rid`) (`person.index.php:40-50`).

## Tasks

The dispatch `switch ($task)` is at `person.index.php:52-107`. Every `case`
label plus the `default` is listed below.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | "New" toolbar button (`person.html.php:45-48,84`) | `person.index.php:53` → `editPerson(null)` (`:133`) | — (blank `Person`) | none | `HTML_person::editPerson()` | none |
| `edit` | Row link `edit(id)` (`person.html.php:38-43`) | `person.index.php:58` → `editPerson($pid)` (`:133`) | `PersonDAO::getPersonByID`, `RolememberDAO::getRolememberAndRoleArrayByPersonID`, `HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID`, `IpDAO::getIpArrayByPersonID`, `GroupDAO::getGroupArray`, `RoleDAO::getRoleArray`, `ChargeDAO::getChargeArray` (`:136-169`) | none | `HTML_person::editPerson()` (`:171`) | password masked to `******` (`:139`) |
| `cancelHasCharge` | "Cancel" on HasCharge form (`person.html.php:923-925`) | `person.index.php:57` (falls through to `:58`) → `editPerson($pid)` | same as `edit` | none | `HTML_person::editPerson()` | returns from HasCharge edit to person edit |
| `editA` | "Edit" toolbar w/ checkbox (`person.html.php:50-57,91`) | `person.index.php:62` → `editPerson(intval($cid[0]))` (`:133`) | same as `edit`, id = first checked `cid` | none | `HTML_person::editPerson()` | none |
| `save` | "Save" toolbar (`person.html.php:324,429`) | `person.index.php:66` → `savePerson('save')` (`:174`) | `$_POST` (bound to `Person`), `PersonDAO::getPersonByID`, `PersonDAO::getPersonWithGroupByUsername` (`:179-193`) | new: `insertObject personaccount` + `insertObject person` in txn (`:280-282`); existing: `updateObject person` (`:289`) → `personaccount`, `person` | `HTML_person::editPerson()` on validation error (`:251`) | `appContext` message + `Database::log` (`:300-302`); redirect to `com_person` list (`:304`) |
| `apply` | "Apply" toolbar (`person.html.php:321-323,422`) | `person.index.php:67` → `savePerson('apply')` (`:174`) | same as `save` | same as `save` | `HTML_person::editPerson()` on validation error | message + log (`:294-296`); redirect back to `task=edit` same person (`:297`) |
| `remove` | "Delete" toolbar w/ double confirm (`person.html.php:59-67,98`) | `person.index.php:71` → `removePerson($cid)` (`:308`) | `PersonDAO::getPersonByID`, `NetworkDAO::getNetworkArrayByPersonID`, `PersonAccountEntryDAO::getPersonAccountEntryArrayByPersonAccountID`, `IpDAO::getIpArrayByPersonID` (`:318-373`) | txn: `SessionDAO::removeSessionByPersonID`, `RolememberDAO::removeRolemembersByPersonID`, `MessageDAO::removeMessageByPersonID`, `LogDAO::removeLogByPersonID`, `IpDAO::removeIpByID` + `IpAccountDAO::removeIpAccountByIPID` + `IpAccountAbsDAO::removeIpAccountAbsByIPID`, `PersonAccountDAO::removePersonAccountByID`, `PersonDAO::removePersonByID` (`:361-384`) → `session`,`rolemember`,`message`,`log`,`ip`,`ipaccount`,`ipaccountabs`,`personaccount`,`person` | none (redirects) | blocks + `backWithAlert` if person has networks or account entries (`:323-355`); message + log per deletion; redirect to list (`:395`) |
| `cancel` | "Cancel" toolbar on person edit (`person.html.php:436`, `submitform('cancel')`) | `person.index.php:75` → `showPerson()` (`:109`) | list query (see `default`) | none | `HTML_person::showPersons()` | none |
| `addRole` | "Add" role button `addRole()` (`person.html.php:329-342,700`) | `person.index.php:79` → `addRole($pid,$rid)` (`:398`) | `PersonDAO::getPersonByID`, `RoleDAO::getRoleByID` (`:409-412`) | `insertObject rolemember` (`:420`) → `rolemember` | none (redirects) | if `$pid` null first calls `savePerson('apply')` (`:402-404`); message + log; redirect to `task=edit` (`:426`) |
| `removeRole` | "Remove" role button `removeRole()` (`person.html.php:344-355,685`) | `person.index.php:83` → `removeRole($pid,$rmid)` (`:429`) | `RolememberDAO::getRolememberByID`, `PersonDAO::getPersonByID`, `RoleDAO::getRoleByID` (`:436-442`) | `RolememberDAO::removeRolemembersByID` (`:444`) → `rolemember` | none (redirects) | message + log; redirect to `task=edit` (`:450`) |
| `newHasCharge` | "Add" new payment `newHasCharge()` (`person.html.php:379-387,816`) | `person.index.php:87` → `editHasCharge(null,$chid,$pid)` (`:460`) | `PersonDAO::getPersonByID`, `ChargeDAO::getChargeByID` (`:466-475`) | none | `HTML_person::editHasCharge()` (`:491`) | if `$pid` null first calls `savePerson('apply')` (`:462-464`); builds blank `HasCharge` STATUS_ENABLED (`:476-481`) |
| `editHasCharge` | Payment row link / action select (`person.html.php:365-370,389-402,739-740`) | `person.index.php:91` → `editHasCharge($hcid,null,$pid)` (`:460`) | `PersonDAO::getPersonByID`, `HasChargeDAO::getHasChargeByID`, `ChargeDAO::getChargeByID` (`:466-484`) | none | `HTML_person::editHasCharge()` (`:491`) | redirect to list if person mismatch or ambiguous args (`:485-489`) |
| `removeHasCharge` | Payment action select `removeHasCharge` (`person.html.php:397-401,792`) | `person.index.php:95` → `removeHasCharge($hcid)` (`:501`) | `HasChargeDAO::getHasChargeByID`, `PersonDAO::getPersonByID`, `PersonAccountDAO::getPersonAccountByID`, `ChargeDAO::getChargeByID`, `ChargeEntryDAO::getChargeEntryArrayByHasChargeID` (`:505-512`) | txn: `ChargeEntryDAO::removeChargeEntryByID` (per entry), `HasChargeDAO::removeHasChargeByID`, `updateObject personaccount` (`:524-529`) → `chargeentry`,`hascharge`,`personaccount` | none (redirects) | refunds FINISHED entries to `PA_balance` / `PA_outcome` (`:518-522`); message + log; redirect to `task=edit` (`:541`) |
| `saveHasCharge` | "Save" on HasCharge form (`person.html.php:929-931`) | `person.index.php:99` → `saveHasCharge('saveHasCharge')` (`:551`) | `$_POST` (bound to `HasCharge`), `PersonDAO::getPersonByID`, `ChargeDAO::getChargeByID` (`:555-564`) | new: `insertObject hascharge`; existing: `updateObject hascharge` (`:611-615`) → `hascharge` | `HTML_person::editHasCharge()` on date-format error (`:576,592,603`) | recomputes entries via `ChargesUtil::createOrRemoveChargeEntriesForPerson` + `proceedChargesForPerson` (`:617-619`); message + `getMessages()` + log; redirect to `task=edit` (`:634`) |
| `applyHasCharge` | "Apply" on HasCharge form (`person.html.php:926-928`) | `person.index.php:100` → `saveHasCharge('applyHasCharge')` (`:551`) | same as `saveHasCharge` | same as `saveHasCharge` | `HTML_person::editHasCharge()` on date error | same billing recompute; message + log; redirect back to `task=editHasCharge` same entry (`:627`) |
| `default` (list) | direct `?option=com_person`, filter submit, `cancel` | `person.index.php:104` → `showPerson()` (`:109`) | `PersonDAO::getPersonCount`, `PersonDAO::getPersonArray`, `GroupDAO::getGroupArray` (`:125-127`); filter/limit from `$_SESSION['UI_SETTINGS']['com_person']` (`:118-123`) | none | `HTML_person::showPersons()` (`:130`) | builds `PageNav` (`:129`) |

Note — nested (non-dispatch) `case` labels: `savePerson` contains an inner
`switch ($task)` with `case 'apply'`/`case 'save'` (`person.index.php:293,299`)
and `saveHasCharge` contains `case 'applyHasCharge'`/`case 'saveHasCharge'`
(`:622,629`). These re-examine the same `$task` string only to choose the
post-save redirect target; they are not separate dispatch routes.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_person::showPersons()` | Person list with filter (search/group/status), paginated table, New/Edit/Delete toolbar | `person.html.php:30-284` |
| `HTML_person::editPerson()` | Person edit form: personal data + tabbed panel (Login credentials, Role, Payments, IP addresses); Apply/Save/Cancel toolbar | `person.html.php:296-899` |
| `HTML_person::editHasCharge()` | HasCharge (payment service) edit form: read-only charge details + editable start/end date and status | `person.html.php:908-1206` |

## Data touched

- DAOs:
  - `PersonDAO` — `getPersonCount`, `getPersonArray`, `getPersonByID`,
    `getPersonWithGroupByUsername`, `removePersonByID`
  - `GroupDAO` — `getGroupArray`
  - `RoleDAO` — `getRoleArray`, `getRoleByID`
  - `RolememberDAO` — `getRolememberAndRoleArrayByPersonID`, `getRolememberByID`,
    `removeRolemembersByPersonID`, `removeRolemembersByID`
  - `ChargeDAO` — `getChargeArray`, `getChargeByID`
  - `HasChargeDAO` — `getHasChargeWithChargeWithPersonArrayByPersonID`,
    `getHasChargeByID`, `removeHasChargeByID`
  - `ChargeEntryDAO` — `getChargeEntryArrayByHasChargeID`, `removeChargeEntryByID`
  - `IpDAO` — `getIpArrayByPersonID`, `removeIpByID`
  - `IpAccountDAO` — `removeIpAccountByIPID`
  - `IpAccountAbsDAO` — `removeIpAccountAbsByIPID`
  - `PersonAccountDAO` — `getPersonAccountByID`, `removePersonAccountByID`
  - `PersonAccountEntryDAO` — `getPersonAccountEntryArrayByPersonAccountID`
  - `NetworkDAO` — `getNetworkArrayByPersonID`
  - `SessionDAO` — `removeSessionByPersonID`
  - `MessageDAO` — `removeMessageByPersonID`
  - `LogDAO` — `removeLogByPersonID`
  - `ChargesUtil` — `createOrRemoveChargeEntriesForPerson`,
    `proceedChargesForPerson`, `getMessages` (`includes/billing/ChargesUtil.php`)
  - Direct `Database` writes: `insertObject`/`updateObject` on `personaccount`,
    `person`, `rolemember`, `hascharge` (`person.index.php:280-289,420,529,612-614`)
- Tables:
  - `Person` (`includes/tables/Person.php`) — statuses `STATUS_PASSIVE=0`,
    `STATUS_ACTIVE=1`, `STATUS_DISCARTED=9` (`:144-146`)
  - `PersonAccount` (`includes/tables/PersonAccount.php`) — created on new person,
    balance adjusted on HasCharge removal
  - `Group` (`includes/tables/Group.php`)
  - `HasCharge` (`includes/tables/HasCharge.php`) — statuses `STATUS_DISABLED=0`,
    `STATUS_ENABLED=1`, `STATUS_FORCE_DISABLED=2`, `STATUS_FORCE_ENABLED=3`
    (`:49-52`); `Charge` fields (`CH_*`) carried via the HasCharge⋈Charge join
  - Indirectly written by delete/refund: `rolemember`, `chargeentry`, `ip`,
    `ipaccount`, `ipaccountabs`, `session`, `message`, `log`

## Forms & fields

### Person list / filter (`HTML_person::showPersons`, `person.html.php:123-274`)

| Field | Meaning | Validation |
|-------|---------|-----------|
| `filter[search]` | free-text search over name/nick/username/email/phone/etc. (LIKE across many `PE_*` columns, `PersonDAO.php:31-33`) | none; auto-submits on change (`:128`) |
| `filter[group]` | filter by `PE_groupid`; `0` = all (`:130-139`) | none |
| `filter[status]` | filter by `PE_status`; `-1` = all (`:142-151`) | none |
| `cid[]` | checked person ids for Edit/Delete (`:218-220`) | client: `boxchecked` must be > 0 else alert (`:51-52,60-61`) |

### Person edit (`HTML_person::editPerson`, `person.html.php:461-867`)

Text inputs bound to `Person` columns: `PE_status` (select), `PE_ic`, `PE_dic`,
`PE_shortcompanyname`, `PE_companyname` (only when `Core::ALLOW_FIRM_REGISTRATION`,
`:486-509`), `PE_firstname`, `PE_surname`, `PE_nick`, `PE_gender` (select
muž/žena), `PE_degree_prefix`, `PE_degree_suffix`, `PE_birthdate`, `PE_email`,
`PE_icq`, `PE_tel`, `PE_secondary_phone_number`, `PE_address`, `PE_city`,
`PE_zip`, `PE_username`, `PE_password1`/`PE_password2`, `PE_groupid` (select).
`PE_lastloggedin` and `PE_registerdate` are display-only (`:602-608`).

Client validation (`validator.js`, `person.html.php:876-897`):

| Field | Rule |
|-------|------|
| `PE_birthdate` | `date=dd.MM.yyyy` (`:880`) |
| `PE_username` | `alphanumeric`, `minlength=3` (`:881-882`) |
| `PE_email` | `email` (`:883`) |
| `PE_password1` | `minlength=6` (`:885`) |
| `PE_password1`/`PE_password2` | `passwordMatchValidator` — must match (`:884,887-896`) |

Server checks in `savePerson` (`person.index.php:174-306`):

- Username uniqueness — `getPersonWithGroupByUsername`; alert "Username already
  exists" and re-render on collision (`:191-201`).
- Password: both `******` or both empty → keep unchanged (`PE_password=null`);
  equal → `md5()`; otherwise alert "User passwords are not same" and re-render
  (`:203-211`).
- Birthdate parsed with `DateUtil`; on failure set to `DB_NULL_DATE` (`:256-261`).
- New person: also creates a `PersonAccount` (currency `CZK`, zeroed) inside a
  transaction before inserting the person (`:263-287`).

### Role sub-panel (`person.html.php:663-709`)

`hasRoles` select (assigned, value `RM_rolememberid`) and `roles` select
(available, value `RO_roleid`); JS sets hidden `RM_rolememberid` / `RO_roleid`
and submits `removeRole` / `addRole` (`:329-355`). Client requires a selection
else alert (`:334-335,349-350`).

### Payment (HasCharge) sub-panel + form

New-payment select `CH_chargeid` (`person.html.php:808-815`); action select per
row submits `editHasCharge`/`removeHasCharge` (`:787-793`).

HasCharge edit form (`HTML_person::editHasCharge`, `person.html.php:992-1183`):

| Field | Column / meaning | Validation |
|-------|------------------|-----------|
| `HC_datestart` | charge start (month picker when `CH_period == PERIOD_MONTHLY`) | client `required` + `date=MM/yyyy` (`:1194-1195`); server parse w/ `DateUtil::FORMAT_MONTHLY`, message on failure (`:589,595`) |
| `HC_dateend` | charge end (empty = continuous) | client `date=MM/yyyy` when monthly (`:1200`); server parse, message on failure (`:570,597-607`) |
| `HC_status` | select over `HasCharge::$STATUS_ARRAY` (`:1157-1165`) | none |
| hidden `HC_haschargeid`, `PE_personid`, `HC_personid`, `HC_chargeid` | identity carried to `saveHasCharge` (`:1178-1181`) | — |

Charge detail inputs on this form are `disabled` (display-only, all named
`_CH_name`, `:1005-1064`) and are not submitted.

## Related flows & cross-module links

- Events fired (`EventCrossBar` / `dispatchEvent`): **none** from this module.
  `saveHasCharge` calls `ChargesUtil::proceedChargesForPerson($person)`
  (`person.index.php:619`); that path dispatches `ChargePaymentDeadlineEvent`
  only when its `$fireDeadlineEvents` flag is true (`ChargesUtil.php:364-369`),
  and this module calls it with the default (`false`), so no event is emitted.
- Cross-module redirect: the IP-address row link switches `option` to
  `com_network` and submits `editI` (`person.html.php:357-363`) — IP editing is
  delegated to `com_network`.
- Redirects within module: `apply`/`addRole`/`removeRole`/`removeHasCharge`/
  `applyHasCharge` return to `task=edit`/`task=editHasCharge` with
  `hidemainmenu=1`; `save`/`remove`/`cancel` return to the list.
- Shared DAOs with other modules: `PersonAccountDAO`/`PersonAccountEntryDAO`
  (com_personaccount), `ChargeDAO`/`HasChargeDAO`/`ChargeEntryDAO` (com_charge),
  `NetworkDAO`/`IpDAO` (com_network), `GroupDAO` (com_group),
  `RoleDAO`/`RolememberDAO` (com_role).

## Source anchors

- Dispatch switch: `modules/com_person/person.index.php:52-107`
- `showPerson`: `person.index.php:109-131`
- `editPerson`: `person.index.php:133-172`
- `savePerson`: `person.index.php:174-306`
- `removePerson`: `person.index.php:308-396`
- `addRole` / `removeRole`: `person.index.php:398-451`
- `editHasCharge`: `person.index.php:460-492`
- `removeHasCharge`: `person.index.php:501-542`
- `saveHasCharge`: `person.index.php:551-638`
- View `showPersons`: `person.html.php:30-284`
- View `editPerson`: `person.html.php:296-899`
- View `editHasCharge`: `person.html.php:908-1206`
- Access routing: `site/index2.php:117-119`, `includes/Mainframe.php:69-84`,
  `includes/tables/Group.php:37-39`
- Tables: `includes/tables/Person.php`, `includes/tables/HasCharge.php`,
  `includes/tables/Group.php`, `includes/tables/PersonAccount.php`
- Billing invocation: `includes/billing/ChargesUtil.php:57,253,364-369,512`
