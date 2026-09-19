# com_handleevent

> Configures event handlers (type, enabled flag, notify target, email subject/template) consumed by `EventCrossBar`.

## Access

No per-module ACL check exists inside the module. Access is governed globally in
`site/index2.php`: any user with `GR_level == Group::USER` (`includes/tables/Group.php:37`)
is force-routed to `com_myprofile` (`site/index2.php:117-119`), so `com_handleevent` is
reachable only by `ADMINISTRATOR` (5) and `SUPER_ADMINISTRATOR` (9). `MainFrame::getPath()`
falls back to `com_admin` for unknown options (`includes/Mainframe.php:69-84`, fallback
`:78`).

## Entry point

- Option: `?option=com_handleevent`
- Controller: `modules/com_handleevent/handleevent.index.php`
- View class: `HTML_handleevent` in `handleevent.html.php`

## Tasks

Main dispatch `switch ($task)` at `handleevent.index.php:30`.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | toolbar New (`handleevent.html.php:40-43`, `:80`) | `handleevent.index.php:31` → `editHandleEvent(null)` `:32` | `PersonDAO::getPersonArray` `:93`; template dir `templates/events/*.txt` `:95-103` | none | `HTML_handleevent::editHandleEvent()` | none |
| `edit` | row link `edit(id)` (`handleevent.html.php:33-38`) | `handleevent.index.php:35` → `editHandleEvent($heid)` `:36` | `$_REQUEST['HE_handleeventid']` `:24`; `HandleEventDAO::getHandleEventByID` `:88`; persons `:93`; templates `:95-103` | none | `HTML_handleevent::editHandleEvent()` | none |
| `editA` | toolbar Edit (`handleevent.html.php:45-52`, `:87`) | `handleevent.index.php:39` → `editHandleEvent(intval($cid[0]))` `:40` | `$_REQUEST['cid']` `:25`; `getHandleEventByID` `:88`; persons/templates | none | `HTML_handleevent::editHandleEvent()` | none |
| `save` | toolbar Save (`handleevent.html.php:223`, `:250`) | `handleevent.index.php:43-46` → `saveHandleEvent($task)` `:45` | `$_POST` (bound) `:120` | `$database->insertObject`/`updateObject` → `handleevent` `:129`,`:131` | redirect to list | `HE_notifypersonid==0`⇒`null` `:122-124`; flash + `Log::LEVEL_INFO` `:142-144`; redirect `:146` |
| `apply` | toolbar Apply (`handleevent.html.php:220-222`, `:244`) | `handleevent.index.php:43-46` → `saveHandleEvent($task)` `:45` | `$_POST` (bound) `:120` | `$database->insertObject`/`updateObject` → `handleevent` `:129`,`:131` | redirect back to edit | flash + log `:136-138`; redirect to `task=edit` `:139` |
| `remove` | toolbar Delete (`handleevent.html.php:54-63`, `:94`) | `handleevent.index.php:48` → `removeHandleEvent($cid)` `:49` | `$_REQUEST['cid']` `:25`; `getHandleEventByID` `:162` | `HandleEventDAO::removeHandleEventByID` → `handleevent` `:164` | redirect to list `:169` | no dependency guard; flash + log `:165-167` |
| `cancel` | toolbar Cancel (`handleevent.html.php:218`, `:257`) | `handleevent.index.php:52` → `showHandleEvent()` `:53` | session UI settings `:68-69` | none | `HTML_handleevent::showHandleEvents()` | none |
| `default` (list) | direct `?option=com_handleevent` | `handleevent.index.php:56` → `showHandleEvent()` `:57` | session UI settings `:68-69`; `HandleEventDAO::getHandleEventCount` `:71`, `getHandleEventArray` `:72`; `PersonDAO::getPersonArray` `:74` | none | `HTML_handleevent::showHandleEvents()` | none |

Inner redirect `switch ($task)` in `saveHandleEvent()` at `handleevent.index.php:134`
decides the post-save redirect: `case 'apply'` (`:135`, redirect to edit `:139`),
`case 'save'` (`:141`, falls through), `default` (`:145-146`, redirect to list). These are
dispatch continuations of the `save`/`apply` tasks, not separate entry tasks.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_handleevent::showHandleEvents()` | Paginated list (enabled/type localized, name, notify person or "Event origin", notify-days, template file) with New/Edit/Delete toolbar and checkboxes | `handleevent.html.php:28` |
| `HTML_handleevent::editHandleEvent()` | Handler edit form (status, type, name, notify person, notify-days select, email subject, template select, description) with Apply/Save/Cancel toolbar | `handleevent.html.php:213` |

## Data touched

- DAOs: `HandleEventDAO` (`getHandleEventCount` `handleevent.index.php:71`,
  `getHandleEventArray` `:72`, `getHandleEventByID` `:88`,`:162`, `removeHandleEventByID`
  `:164`); `PersonDAO` (`getPersonArray` `:74`,`:93`).
- Persist on save uses `$database->insertObject("handleevent", …)` /
  `updateObject("handleevent", …, true, false)` directly (`handleevent.index.php:129`,`:131`)
  — no `HandleEventDAO` save method.
- Filesystem: templates enumerated from `templates/events/*.txt` (`handleevent.index.php:95-103`,
  local `EndsWith()` helper `:173-181`).
- Tables: `handleevent` (`includes/tables/HandleEvent.php`); read-only reference to `person`
  via `PersonDAO`.

## Forms & fields

**Edit form** — `adminForm`, `handleevent.html.php:283`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `HE_status` | `handleevent.HE_status` — enabled flag; `<select>` from `HandleEvent::$STATUS_ARRAY` (DISABLED=0/ENABLED=1, `HandleEvent.php:57-63`) | none (`handleevent.html.php:297-305`) |
| `HE_type` | `handleevent.HE_type` — event type; `<select>` from `HandleEvent::$TYPE_ARRAY` (only `TYPE_CHARGE_PAYMENT_DEADLINE=1`; `TYPE_PAYMENT_RECEIVED` commented out, `HandleEvent.php:74-80`) | none (`handleevent.html.php:310-318`) |
| `HE_name` | `handleevent.HE_name` — handler name (text, maxlength 255) | Client `Validator` `required` "Please enter event handler name" (`handleevent.html.php:408`) |
| `HE_notifypersonid` | `handleevent.HE_notifypersonid` — notify target; `<select>` of persons, option `0` = "Event origin" ⇒ stored `null` (`handleevent.html.php:330-341`; `0`→`null` at `handleevent.index.php:122-124`) | none |
| `HE_notifydaysbeforeturnoff` | `handleevent.HE_notifydaysbeforeturnoff` — threshold days; `<select>` of integers −60..60 (`handleevent.html.php:347-355`) | none |
| `HE_emailsubject` | `handleevent.HE_emailsubject` — email subject (text, maxlength 255) | none |
| `HE_templatepath` | `handleevent.HE_templatepath` — template filename; `<select>` from `templates/events/*.txt` (`handleevent.html.php:367-375`) | none |
| `HE_description` | `handleevent.HE_description` — description (text, maxlength 255) | none |
| `HE_handleeventid` (hidden) | PK; empty ⇒ insert (`$isNew` `handleevent.index.php:126`) | none |

**List form** — `adminForm`, `handleevent.html.php:119`: `cid[]` row checkboxes
(`:162-164`), hidden `HE_handleeventid`, `task`, `boxchecked`. Empty selection blocked
client-side (`:45-63`).

## Related flows & cross-module links

- Events fired by this module: none. The module only *configures* rows in `handleevent`;
  those rows are consumed at runtime by `EventCrossBar` (`includes/event/EventCrossBar.php`),
  which is constructed on every request (`site/index2.php:80`).
- `EventCrossBar` loads all `handleevent` rows and their template files at construction
  (`EventCrossBar.php:34-47`; missing/empty template throws `:43-45`). `dispatchEvent()`
  fires only for `ChargePaymentDeadlineEvent` when `HE_type == TYPE_CHARGE_PAYMENT_DEADLINE`
  and `HE_status == STATUS_ENABLED` (`EventCrossBar.php:58-62`); notifies when
  `HE_notifydaysbeforeturnoff >= daysBeforeTurnOff` (`:73`), interpolates `|PLACEHOLDER|`
  tokens (`:75-84`), resolves recipient from `HE_notifypersonid` or the event's person
  (`:86-90`), and emails via `EmailUtil` (`:92-99`).
- Redirects: after `apply` → `?option=com_handleevent&task=edit` (`:139`); after
  `save`/`remove` → `?option=com_handleevent` (`:146`,`:169`).

## Source anchors

- Controller dispatch: `handleevent.index.php:30-59`; inner save switch `:134-147`.
- Handlers: `showHandleEvent()` `:63-78`, `editHandleEvent()` `:83-110`,
  `saveHandleEvent()` `:115-148`, `removeHandleEvent()` `:153-171`, `EndsWith()` `:173-181`.
- Views: `handleevent.html.php:28` (list), `:213` (edit), validator `:408`.
- DAO: `includes/dao/HandleEventDAO.php`; table `includes/tables/HandleEvent.php`
  (status `:57-63`, type `:74-80`).
- Consumer: `includes/event/EventCrossBar.php`.
- Access/routing: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`.
