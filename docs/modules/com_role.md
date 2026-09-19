# com_role

> Manages user roles (name + description) that can be assigned to persons.

## Access

No per-module ACL check exists inside the module. Access is governed globally in
`site/index2.php`: any user with `GR_level == Group::USER` (`includes/tables/Group.php:37`)
is force-routed to `com_myprofile` (`site/index2.php:117-119`), so `com_role` is reachable
only by `ADMINISTRATOR` (5) and `SUPER_ADMINISTRATOR` (9). `MainFrame::getPath()` falls
back to `com_admin` for unknown options (`includes/Mainframe.php:69-84`, fallback `:78`).

## Entry point

- Option: `?option=com_role`
- Controller: `modules/com_role/role.index.php`
- View class: `HTML_role` in `role.html.php`

## Tasks

Main dispatch `switch ($task)` at `role.index.php:32`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | toolbar New (`role.html.php:43-46`, `:83`) | `role.index.php:33` → `editRole(null)` `:34` | — | none | `HTML_role::editRole()` | none |
| `edit` | row link `edit(id)` (`role.html.php:36-41`) | `role.index.php:37` → `editRole($rid)` `:38` | `$_REQUEST['RO_roleid']` `:26` | none (`RoleDAO::getRoleByID` `:88`) | `HTML_role::editRole()` | none |
| `editA` | toolbar Edit (`role.html.php:48-55`, `:90`) | `role.index.php:41` → `editRole(intval($cid[0]))` `:42` | `$_REQUEST['cid']` `:27` | none (`RoleDAO::getRoleByID` `:88`) | `HTML_role::editRole()` | none |
| `save` | toolbar Save (`role.html.php:206`, `:233`) | `role.index.php:45-48` → `saveRole($task)` `:47` | `$_POST` (bound) `:104` | `$database->insertObject`/`updateObject` → `role` `:108`,`:110` | redirect to list | flash + `Log::LEVEL_INFO` `:121-123`; redirect `:125` |
| `apply` | toolbar Apply (`role.html.php:203-205`, `:227`) | `role.index.php:45-48` → `saveRole($task)` `:47` | `$_POST` (bound) `:104` | `$database->insertObject`/`updateObject` → `role` `:108`,`:110` | redirect back to edit | flash + log `:115-117`; redirect to `task=edit` `:118` |
| `remove` | toolbar Delete (`role.html.php:57-66`, `:97`) | `role.index.php:50` → `removeRole($cid)` `:51` | `$_REQUEST['cid']` `:27` | `RoleDAO::removeRoleByID` → `role` `:163` | redirect to list `:169` | redirect if role not found `:141-143`; blocked if role has members (`RolememberDAO::getRolememberAndPersonsArrayByRoleID` `:144`, `Core::backWithAlert` `:161`); else flash + log `:164-166` |
| `cancel` | toolbar Cancel (`role.html.php:201`, `:240`) | `role.index.php:54` → `showRole()` `:55` | session UI settings `:70-71` | none | `HTML_role::showRoles()` | none |
| `default` (list) | direct `?option=com_role` | `role.index.php:58` → `showRole()` `:59` | session UI settings `:70-71`; `RoleDAO::getRoleCount` `:73`, `getRoleArray` `:74` | none | `HTML_role::showRoles()` | none |

Inner redirect `switch ($task)` in `saveRole()` at `role.index.php:113` decides the
post-save redirect: `case 'apply'` (`:114`, redirect to edit `:118`), `case 'save'` (`:120`,
falls through), `default` (`:124-125`, redirect to list). These are dispatch continuations
of the `save`/`apply` tasks, not separate entry tasks.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_role::showRoles()` | Paginated role list (name, description) with New/Edit/Delete toolbar and row checkboxes | `role.html.php:31` |
| `HTML_role::editRole()` | Role edit form (name, description) with Apply/Save/Cancel toolbar | `role.html.php:196` |

## Data touched

- DAOs: `RoleDAO` (`getRoleCount` `role.index.php:73`, `getRoleArray` `:74`, `getRoleByID`
  `:88`,`:141`, `removeRoleByID` `:163`); `RolememberDAO`
  (`getRolememberAndPersonsArrayByRoleID` `:144`).
- Persist on save uses `$database->insertObject("role", …)` / `updateObject("role", …)`
  directly (`role.index.php:108`,`:110`) — no `RoleDAO` save method.
- Tables: `role` (`includes/tables/Role.php`); read-only reference to `person`+`rolemember`
  via `RolememberDAO`.

## Forms & fields

**Edit form** — `adminForm`, `role.html.php:266`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `RO_name` | `role.RO_name` — role name (text, maxlength 255) | Client `Validator` `required` rule "Please enter role name" (`role.html.php:312`). No server-side check. |
| `RO_description` | `role.RO_description` — free-text description (text, maxlength 255) | none |
| `RO_roleid` (hidden) | PK; empty ⇒ insert (`$isNew` `role.index.php:105`) | none |

**List form** — `adminForm`, `role.html.php:122`: `cid[]` row checkboxes
(`role.html.php:156-158`), hidden `RO_roleid`, `task`, `boxchecked`. Empty selection blocked
client-side (`role.html.php:48-66`).

## Related flows & cross-module links

- Events fired: none.
- Deletion guard cross-links to `person`/`rolemember` data: a role with bound members cannot
  be deleted; members are listed in the alert (`role.index.php:144-161`).
- Redirects: after `apply` → `?option=com_role&task=edit` (`:118`); after `save`/`remove`
  and on not-found role → `?option=com_role` (`:125`,`:142`,`:169`).

## Source anchors

- Controller dispatch: `role.index.php:32-61`; inner save switch `:113-126`.
- Handlers: `showRole()` `:65-78`, `editRole()` `:83-94`, `saveRole()` `:99-127`,
  `removeRole()` `:132-171`.
- Views: `role.html.php:31` (list), `:196` (edit), validator `:312`.
- DAOs: `includes/dao/RoleDAO.php`, `includes/dao/RolememberDAO.php`; table
  `includes/tables/Role.php`.
- Access/routing: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`.
