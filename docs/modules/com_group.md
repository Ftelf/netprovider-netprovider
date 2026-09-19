# com_group

> Manages user groups and their access level (USER / ADMINISTRATOR / SUPER_ADMINISTRATOR).

## Access

No per-module ACL check exists inside the module. Access is governed globally in
`site/index2.php`: any logged-in user whose `GR_level == Group::USER` (`Group::USER = 0`,
`includes/tables/Group.php:37`) is force-routed to `com_myprofile`
(`site/index2.php:117-119`), so `com_group` is reachable only by `ADMINISTRATOR` (5) and
`SUPER_ADMINISTRATOR` (9). `MainFrame::getPath()` resolves the controller path and falls
back to `com_admin` for any unknown option (`includes/Mainframe.php:69-84`, fallback at
`:78`). The `GR_acl` column is unused — the controller always forces it to `0`
(`group.index.php:111`).

## Entry point

- Option: `?option=com_group`
- Controller: `modules/com_group/group.index.php`
- View class: `HTML_group` in `group.html.php`

## Tasks

Main dispatch `switch ($task)` at `group.index.php:32`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | toolbar New (`group.html.php:43-46`, `:83`) | `group.index.php:33` → `editGroup(null)` `:34` | — | none | `HTML_group::editGroup()` | none |
| `edit` | row link `edit(id)` (`group.html.php:36-41`) | `group.index.php:37` → `editGroup($gid)` `:38` | `$_REQUEST['GR_groupid']` `:26` | none (`GroupDAO::getGroupByID` `:88`) | `HTML_group::editGroup()` | none |
| `editA` | toolbar Edit (`group.html.php:48-55`, `:90`) | `group.index.php:41` → `editGroup(intval($cid[0]))` `:42` | `$_REQUEST['cid']` `:27` | none (`GroupDAO::getGroupByID` `:88`) | `HTML_group::editGroup()` | none |
| `save` | toolbar Save (`group.html.php:210`, `:237`) | `group.index.php:45-48` → `saveGroup($task)` `:47` | `$_POST` (bound) `:104` | `$database->insertObject`/`updateObject` → `group` `:114`,`:116` | redirect to list | flash message + `Log::LEVEL_INFO` `:127-129`; redirect `:131` |
| `apply` | toolbar Apply (`group.html.php:207-209`, `:230`) | `group.index.php:45-48` → `saveGroup($task)` `:47` | `$_POST` (bound) `:104` | `$database->insertObject`/`updateObject` → `group` `:114`,`:116` | redirect back to edit | flash message + log `:121-123`; redirect to `task=edit` `:124` |
| `remove` | toolbar Delete (`group.html.php:57-66`, `:97`) | `group.index.php:50` → `removeGroup($cid)` `:51` | `$_REQUEST['cid']` `:27` | `GroupDAO::removeGroupByID` → `group` `:166` | redirect to list `:172` | blocked if group has members (`PersonDAO::getPersonArrayByGroupID` `:149`, `Core::backWithAlert` `:164`); else flash + log `:167-169` |
| `cancel` | toolbar Cancel (`group.html.php:205`, `:244`) | `group.index.php:54` → `showGroup()` `:55` | session UI settings `:70-71` | none | `HTML_group::showGroups()` | none |
| `default` (list) | direct `?option=com_group` | `group.index.php:58` → `showGroup()` `:59` | session UI settings `:70-71`; `GroupDAO::getGroupCount` `:73`, `getGroupArray` `:74` | none | `HTML_group::showGroups()` | none |

Inner redirect `switch ($task)` in `saveGroup()` at `group.index.php:119` decides the
post-save redirect: `case 'apply'` (`:120`, redirect to edit `:124`), `case 'save'`
(`:126`, falls through), `default` (`:130-131`, redirect to list). These are dispatch
continuations of the `save`/`apply` tasks above, not separate entry tasks.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_group::showGroups()` | Paginated group list (name, localized user level) with New/Edit/Delete toolbar and row checkboxes | `group.html.php:31` |
| `HTML_group::editGroup()` | Group edit form (name, user-level select) with Apply/Save/Cancel toolbar | `group.html.php:199` |

## Data touched

- DAOs: `GroupDAO` (`getGroupCount` `group.index.php:73`, `getGroupArray` `:74`,
  `getGroupByID` `:88`,`:147`, `removeGroupByID` `:166`); `PersonDAO`
  (`getPersonArrayByGroupID` `:149`).
- Persist on save uses `$database->insertObject("group", …)` / `updateObject("group", …)`
  directly (`group.index.php:114`,`:116`) — no `GroupDAO` save method.
- Tables: `group` (`includes/tables/Group.php`); read-only reference to `person` via
  `PersonDAO::getPersonArrayByGroupID`.

## Forms & fields

**Edit form** — `adminForm`, `group.html.php:269`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `GR_name` | `group.GR_name` — group name (text, maxlength 255) | Client `Validator` `required` rule "Please enter group name" (`group.html.php:327`). No server-side check. |
| `GR_level` | `group.GR_level` — user level; `<select>` populated from `Group::$LEVEL_ARRAY` (`group.html.php:293-301`; levels in `Group.php:41-45`) | none |
| `GR_groupid` (hidden) | PK; empty ⇒ insert (`$isNew` `group.index.php:106`) | none |
| `GR_acl` | Commented out in view (`group.html.php:286-289`); controller forces `0` (`group.index.php:111`) | not rendered |

**List form** — `adminForm`, `group.html.php:122`: `cid[]` row checkboxes
(`group.html.php:156-158`), hidden `GR_groupid`, `task`, `boxchecked`. Empty selection is
blocked client-side (`editA`/`remove` alerts, `group.html.php:48-66`).

## Related flows & cross-module links

- Events fired: none.
- Deletion guard cross-links to `com_person` data: a group bound to persons cannot be
  deleted; the members are listed in the alert (`group.index.php:149-164`).
- Redirects: after `apply` → `?option=com_group&task=edit` (`:124`); after `save`/`remove`
  → `?option=com_group` (`:131`,`:172`).

## Source anchors

- Controller dispatch: `group.index.php:32-61`; inner save switch `:119-132`.
- Handlers: `showGroup()` `:65-78`, `editGroup()` `:83-94`, `saveGroup()` `:99-133`,
  `removeGroup()` `:138-174`.
- Views: `group.html.php:31` (list), `:199` (edit), validator `:327`.
- DAO: `includes/dao/GroupDAO.php`; table `includes/tables/Group.php` (levels `:37-45`,
  `getLocalizedLevel` `:47-55`).
- Access/routing: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`.
