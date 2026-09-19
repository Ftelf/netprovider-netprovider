# com_internet

> CRUD screen for internet service templates (name, guaranteed/maximum up/down rates, priority) that charges bind to for QoS.

## Access

No ACL check exists inside the controller; access is governed globally by `site/index2.php`. Any user whose `GR_level == Group::USER` (`0`) is force-routed to `com_myprofile` and cannot reach this module (`site/index2.php:117-119`), so only `ADMINISTRATOR` (`5`) and `SUPER_ADMINISTRATOR` (`9`) reach it. `MainFrame::getPath()` resolves `?option=com_internet` to `modules/com_internet/internet.index.php`, falling back to `com_admin` if missing (`includes/Mainframe.php:69-84`). No per-task role gating exists in this module. `Group` levels at `includes/tables/Group.php:37-39`.

## Entry point

- Option: `?option=com_internet`
- Controller: `modules/com_internet/internet.index.php`
- View class: `HTML_internet` in `internet.html.php`

Request params read: `task`, `IN_internetid` (`$iid`), `cid[]` (`$cid`, defaults `[0]`) (`internet.index.php:24-29`).

## Tasks

Top-level dispatch `switch ($task)` at `internet.index.php:31-57`. Every case label plus `default` is listed below.

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | Toolbar "New" / `newI()` (`internet.html.php:40`,`79`) | `internet.index.php:32` → `editInternet(null)` :78 | none | none | `HTML_internet::editInternet()` with blank `Internet` | none |
| `edit` | Row link / `edit(id)` (`internet.html.php:33`,`149`) | `internet.index.php:36` → `editInternet($iid)` :78 | `IN_internetid` | none | `HTML_internet::editInternet()` | none |
| `editA` | Toolbar "Edit" / `editA()` (`internet.html.php:45`,`86`) | `internet.index.php:40` → `editInternet(intval($cid[0]))` :78 | `cid[]` | none | `HTML_internet::editInternet()` | none |
| `save` | "Save" toolbar (`internet.html.php:279`) | `internet.index.php:44` → `saveInternet('save')` :92 | `$_POST` (Internet fields via `database::bind`), `IN_dnl_rate_cb`, `IN_upl_rate_cb` | `INSERT internet` (new) or `UPDATE internet` (existing) (`internet.index.php:127`,`129`) | on numeric error re-renders `editInternet`; else redirect to `?option=com_internet` | Flash message + DB log (inner `switch ($task)` :132-145); `save` falls through to `default` redirect |
| `apply` | "Apply" toolbar (`internet.html.php:272`) | `internet.index.php:45` → `saveInternet('apply')` :92 | same as `save` | same as `save` (`:127`,`129`) | redirect to `task=edit&IN_internetid=...` | Flash message + DB log (`:133-138`) |
| `remove` | Toolbar "Delete" / `remove()` (with confirm) (`internet.html.php:54`,`93`) | `internet.index.php:49` → `removeInternet($cid)` :151 | `cid[]` | `InternetDAO::removeInternetByID` per id (`internet.index.php:176`) | redirect to `?option=com_internet`; empty selection → `Core::backWithAlert` (`:155-157`) | Per id: refuses delete + `Core::alert` if template has bound charges (`InternetDAO::getInternetChargesArrayByID`, `:162-174`); otherwise deletes, flash + DB log |
| `cancel` | "Cancel" toolbar (`internet.html.php:286`) | `internet.index.php:53` (falls through to `default`) → `showInternet()` :60 | session limit | none | `HTML_internet::showInternet()` | none (list) |
| `default` | Any other/absent task | `internet.index.php:54` → `showInternet()` :60 | `$_SESSION['UI_SETTINGS']['com_internet']` limit/limitstart; `InternetDAO` | none | `HTML_internet::showInternet()` | none (paginated list) |

Note: `grep "case '"` returns 9 hits; 7 are the top-level dispatch labels above (`:32-53`), and the other 2 are the nested `switch ($task)` inside `saveInternet()` (`apply` :133, `save` :139). Plus `default` (`:54`) = 8 top-level branches, all documented.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_internet::showInternet()` | Paginated template list: name, guaranteed/maximum down & up (kbps, `-1`→"AUTO"), priority, description; New/Edit/Delete toolbar | `internet.html.php:28` |
| `HTML_internet::editInternet()` | Template form: name, description, guaranteed/maximum up/down rates with Auto checkboxes, priority 0–9 select; Apply/Save/Cancel toolbar; client-side numeric validation | `internet.html.php:210` |

## Data touched

- DAOs: `InternetDAO` — `getInternetCount()`, `getInternetArray()`, `getInternetByID()`, `getInternetChargesArrayByID()`, `removeInternetByID()` (`includes/dao/InternetDAO.php`)
- Tables: `Internet` (`includes/tables/Internet.php`) — columns `IN_internetid`, `IN_name`, `IN_description`, `IN_dnl_rate`, `IN_dnl_ceil`, `IN_upl_rate`, `IN_upl_ceil`, `IN_prio` (`:23-51`). `getInternetChargesArrayByID` joins `charge` on `CH_internetid` to detect bound payment services (`InternetDAO.php`).

## Forms & fields

**Template form** (`editInternet`, `internet.html.php:210`) — posts to `saveInternet`:

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `IN_name` | Template name | Client: non-empty (`:232`). Server: none explicit |
| `IN_description` | Description | Client: non-empty (`:234`) |
| `IN_dnl_rate` | Guaranteed download (kbps); `-1` = AUTO | Client: integer ≥ 0 unless AUTO checkbox (`:236`). Server: if `IN_dnl_rate_cb==1` set to `-1` (`:102-104`); must be numeric else error (`:108-110`) |
| `IN_dnl_rate_cb` | AUTO checkbox for download rate; disables `IN_dnl_rate` | client `dnl_cb()` (`:249`); server `:102` |
| `IN_dnl_ceil` | Maximum download (kbps) | Client: integer, `IN_dnl_rate_temp > 0` (`:238`). Server: numeric (`:111-113`) |
| `IN_upl_rate` | Guaranteed upload (kbps); `-1` = AUTO | Client: integer ≥ 0 unless AUTO checkbox (`:240`). Server: if `IN_upl_rate_cb==1` set to `-1` (`:105-107`); numeric (`:114-116`) |
| `IN_upl_rate_cb` | AUTO checkbox for upload rate; disables `IN_upl_rate` | client `upl_cb()` (`:253`); server `:105` |
| `IN_upl_ceil` | Maximum upload (kbps) | Client: integer > 0 (`:242`). Server: numeric (`:117-119`) |
| `IN_prio` | Priority 0–9 (`<select>`) | none (bounded by option list `:372`) |
| `IN_internetid` | Hidden id; empty on new (drives insert vs update, `:98`) | — |

On any server numeric failure the collected `$errorArray` is shown via `Core::alert` and the form is re-rendered (`internet.index.php:120-124`).

**List** (`showInternet`, `internet.html.php:28`): no filter fields; pagination limit/limitstart from `$_SESSION['UI_SETTINGS']['com_internet']` (`internet.index.php:65-66`). Checkbox column `cid[]` feeds `editA`/`remove`.

## Related flows & cross-module links

- **Events fired**: none.
- **Cross-module coupling**: `com_charge` — a template cannot be deleted while a `charge` references it via `CH_internetid`; `removeInternet` lists up to 3 bound charge names and aborts (`internet.index.php:162-174`). Charges bind these templates for network QoS applied by `includes/net/` commanders.
- **Redirects**: all mutating tasks `Core::redirect()` within the module (`?option=com_internet` or `&task=edit`). No cross-module redirects.

## Source anchors

- Dispatch switch: `internet.index.php:31-57`
- Handlers: `showInternet` :60, `editInternet` :78, `saveInternet` :92 (inner switch :132-145), `removeInternet` :151
- Views: `internet.html.php:28`,`210`
- Client validation: `internet.html.php:215-247`
- ACL/routing: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`; `includes/tables/Group.php:37-39`
- DAO: `includes/dao/InternetDAO.php`
- Table: `includes/tables/Internet.php:23-51`
