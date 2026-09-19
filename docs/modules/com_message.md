# com_message

> Message list: browse/filter queued and sent notifications, delete selected messages (and their attachments), and flush pending e-mails through `EmailUtil`.

## Access

- Authenticated session required (`site/index2.php:64-70`).
- Regular users (`Group::USER = 0`) cannot reach it — forced to `com_myprofile` (`site/index2.php:117-119`, `includes/tables/Group.php:37`). Administration-agenda screen, linked under "Administration" in the main menu (`modules/com_common/html_mainmenu.php:52`); reachable by `Group::ADMINISTRATOR = 5` / `Group::SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:38-39`).
- No per-module ACL gate beyond the session check; `MainFrame::getPath()` resolves the controller with `com_admin` fallback if missing (`includes/Mainframe.php:69-84`).

## Entry point

- Option: `?option=com_message`
- Controller: `modules/com_message/message.index.php`
- View class: `HTML_message` in `message.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `remove` | Toolbar "Delete" → `javascript:remove()` → confirm dialog → `submitbutton('remove')` (`message.html.php:36-44,104`) with `cid[]` | `removeMessage($cid)` `message.index.php:35-37,110-123` | `$_REQUEST['cid']` = `ME_messageid[]` (`:29,110`) | `MessageDAO::removeMessageByID` → `message` (DELETE, `:118`); `MessageAttachmentDAO::removeAttachmentMessageByMessageID` → `message_attachment` (DELETE, `:119`) | none (redirect) | `Core::backWithAlert` if `count($cid) < 1` (`:114`); `Core::redirect` to `com_message` after deletes (`:121`) |
| `send` | Toolbar "Send" → `javascript:send()` → `submitbutton('send')` (`message.html.php:32-34,97`) | `send()` `message.index.php:39-41,127-135` | (via `EmailUtil`) `MessageDAO::getPendingMessageArray`, `PersonDAO::getPersonByID`, `MessageAttachmentDAO::getMessageAttachmentArrayForAttachmentForMessageID` | `EmailUtil::sendMessages()` → `$database->updateObject("message", ...)` sets `ME_status` (`EmailUtil.php:121-128`) | none (redirect) | Sends e-mail per pending message; sets `ME_status` to `STATUS_SENDED` on success / `STATUS_CANNOT_BE_SEND` on failure and re-throws (`EmailUtil.php:106-133`); `$database->log`; `Core::redirect` to `com_message` (`:134`) |
| _default_ (no/other `task`, initial load) | Menu link `index2.php?option=com_message` (`html_mainmenu.php:52`); filter change → `document.adminForm.submit()` (`message.html.php:46-50,160`); pager | `showMessage()` `message.index.php:43-45,50-105` | list state `limit`/`limitstart` + `filter[date_from|date_to|personid]` from `$_SESSION['UI_SETTINGS']['com_message']` (`:55-60`); `MessageDAO::getMessageCount` (`:89`), `MessageDAO::getMessageArray` (`:90`), `MessageAttachmentDAO::getMessageAttachmentNamesArrayForAttachmentForMessageID` (`:93`), `PersonDAO::getPersonArray` (`:101`) | none | `HTML_message::showMessage()` (`:104`) | builds `PageNav` (`:103`); normalizes/swaps date range (`:73-87`) |

Switch is `message.index.php:34-46`. Cases: `remove`, `send`, `default` — all covered. `$mid` (`ME_messageid`) and `$cid` are read at `:28-32` (`$cid` coerced to `array(0)` when not an array).

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_message::showMessage()` | Filter bar (date-from/date-to calendars + person select), paged message table (#, `cid[]` checkbox, date, recipient link → `com_person` edit, subject, body, attachment text, localized status); toolbar Send/Delete | `message.html.php:25-264` |

## Data touched

- DAOs: `MessageDAO` (`getMessageCount`, `getMessageArray`, `removeMessageByID`; plus `getPendingMessageArray` via `EmailUtil`), `MessageAttachmentDAO` (`getMessageAttachmentNamesArrayForAttachmentForMessageID`, `removeAttachmentMessageByMessageID`; plus `getMessageAttachmentArrayForAttachmentForMessageID` via `EmailUtil`), `PersonDAO` (`getPersonArray`; plus `getPersonByID` via `EmailUtil`). `MessageAttachmentDAO` is not explicitly `require_once`'d by this controller (relied on being already loaded); explicit requires are `MessageDAO`, `PersonDAO`, `DateUtil`, `EmailUtil` (`message.index.php:21-25`).
- Tables: `message` (`includes/tables/Message.php`) — DELETE on `remove`, UPDATE `ME_status` on `send`; `message_attachment` — DELETE on `remove`. `person` read-only.
- Utilities: `EmailUtil` (`includes/net/email/EmailUtil.php`), `DateUtil`, `PageNav` (`modules/com_common/PageNav.php`, required at `:53`).

## Forms & fields

Single form (`HTML_message::showMessage`, `message.html.php:129-254`) posts hidden `option=com_message`, `ME_messageid`, `PE_personid`, `task`, `boxchecked`, `hidemainmenu`:

| Field | Column / meaning | Validation |
|-------|------------------|-----------|
| `date_from` + `filter[date_from]` | list filter start date (`dd.MM.yyyy`) (`:133-139`) | none client-side; server `DateUtil::parseDate` in try/catch, range auto-swapped if inverted (`index:64-87`) |
| `date_to` + `filter[date_to]` | list filter end date (`:146-152`) | as above; `+1 day` added to make range inclusive (`index:85-87`) |
| `filter[personid]` | recipient filter (person select, `0` = any) (`:159-172`) | none; submits on change |
| `cid[]` | selected `ME_messageid` for delete (`:219-221`) | client `remove()` blocks when `boxchecked == 0` and confirms (`:36-44`); server `Core::backWithAlert` if empty (`index:113-115`) |
| `limit` / `limitstart` | pagination, persisted in session UI settings (`index:55-56`; `site/index2.php:94-97`) | none |

The recipient cell links to `com_person` edit via `editP(id)` which rewrites `option` to `com_person` (`message.html.php:52-58,205,226`).

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none directly. Note `EmailUtil::sendMessages()` sends the queued messages that other flows enqueue.
- Redirects: `remove` and `send` → `index2.php?option=com_message` (`:121,:134`); recipient link → `com_person` edit (`message.html.php:52-58`).
- Shared DAOs: `MessageDAO`/`MessageAttachmentDAO` (queue produced by billing/event flows), `PersonDAO`.
- Distinct from `com_massmessages`: this module flushes pending **e-mail** via `EmailUtil`; `com_massmessages` sends **SMS** inline.

## Source anchors

- Switch / dispatch: `message.index.php:34-46`
- `showMessage()`: `message.index.php:50-105`
- `removeMessage()`: `message.index.php:110-123`
- `send()`: `message.index.php:127-135`
- `EmailUtil::sendMessages()`: `includes/net/email/EmailUtil.php:106-134`
- `HTML_message::showMessage()`: `message.html.php:25-264`
- Access: `site/index2.php:117-119`; `includes/tables/Group.php:37-39`
