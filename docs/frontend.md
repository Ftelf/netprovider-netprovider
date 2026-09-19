# Shared frontend layer

Developer reference for the **shared, server-rendered frontend** of NetProvider:
the page-chrome pipeline (`modules/com_common/`) and the client-side asset layer
(`site/js/`, `site/css/`). This is the layer you touch when changing look,
navigation, pagination, client-side validation, or page chrome — **not**
per-screen business logic (that lives in each module; see
[modules/README.md](modules/README.md)).

See also:

- [TECHNICAL.md → Web request lifecycle](TECHNICAL.md#web-request-lifecycle) — the
  full request flow this document renders into.
- [TECHNICAL.md → Modules (web UI)](TECHNICAL.md#modules-web-ui) — controller/view
  split and task conventions.
- [modules/README.md](modules/README.md) — per-module screen/field reference.

---

## Overview

Every authenticated page is assembled by `site/index2.php`, which wraps the
per-module output with a fixed sequence of shared includes. The login page is the
one exception — it is rendered by `site/index.php` through a single self-contained
include.

### Main app include order (`site/index2.php`)

`index2.php` bootstraps `Core`/`Database`, validates the session, persists UI
state, then emits the page in this exact order:

| Step | Include | Source | Notes |
|------|---------|--------|-------|
| 1 | `modules/com_common/html_start.php` | `site/index2.php:114` | `<!DOCTYPE>`, `<head>` (all `<script>`/`<link>` tags), opens `<body id="wrapper">` |
| 2 | `modules/com_common/html_header.php` | `site/index2.php:115` | Top band: version + vendor title |
| 3 | `modules/com_common/html_mainmenu.php` | `site/index2.php:122` | Logout/user box + JSCookMenu top menu. **Only** when `$_REQUEST['hidemainmenu'] === '0'` (`site/index2.php:121`) |
| 4 | module controller | `site/index2.php:137` (`require $mainframe->getPath()`) | Emits the message panel (`$mainframe->getMsgPanel()`, `site/index2.php:133`) then the module body |
| 5 | `modules/com_common/html_footer.php` | `site/index2.php:156` | Copyright + page-generation timer |
| 6 | `modules/com_common/html_debug.php` | `site/index2.php:159` | **Only** when `Core::SYSTEM_DEBUG` is true (`site/index2.php:158`) |
| 7 | `modules/com_common/html_end.php` | `site/index2.php:161` | `<noscript>` warning, closes `</body></html>` |

Note: `html_mainmenu.php` also renders its own contents only when
`$my->GR_level != Group::USER` (`modules/com_common/html_mainmenu.php:3`); regular
(customer) users get the surrounding `<div>` but no menu. Independently,
`index2.php:117-119` forces USER-level accounts onto `com_myprofile` regardless of
the requested option.

### Login page (`site/index.php`)

On a plain GET (no `$_POST['submit']`), `index.php` requires
`modules/com_common/login.php` (`site/index.php:115`). `login.php` is a **complete
standalone HTML document** (`modules/com_common/login.php:18-110`): its own
`<head>` linking only `css/login.css` (`:25`), an inline `setFocus()` script
(`:27-32`), and the login `<form name="loginForm" action="index.php" method="post">`
(`:53`). It does **not** use the `html_start`/`html_header`/… chain. On POST the
same file authenticates and redirects to `index2.php` (`site/index.php:36-106`).

---

## Shared render layer (`modules/com_common/`)

### `html_start.php` — document head + asset loading

`modules/com_common/html_start.php:17-44`. Emits the XHTML doctype, `<head>` with
`<title>` and `Content-Language` from `Core` properties (`:21-23`), then **all**
shared client assets, then opens `<body id="wrapper">` (`:44`). Script load order
(`:24-33`): `JSCookMenu.js`, `ThemeOffice/theme.js`, `tabs/tabpane.js`,
`functions.js`, `dtree.js`, `overlib.js`, `CalendarPopup.js`, `validator.js`,
`jquery-3.6.3.min.js`, `chosen.jquery.min.js`. Stylesheets (`:34-41`):
`template.css`, `icon.css`, `report.css`, `dtree.css`, `calendar-popup.css`,
`chosen.css`, `ThemeOffice/theme.css`, `tabs/tabpane.css`.

> Caveat: jQuery is loaded at `:32`, **after** `functions.js` (`:27`) and the other
> plugins but **before** `chosen.jquery.min.js` (`:33`). The legacy widgets
> (JSCookMenu, dtree, overlib, CalendarPopup, validator, functions.js) are plain
> DOM/no-jQuery, so order does not break them; Chosen is the only jQuery consumer
> and correctly loads after jQuery. If you add a jQuery-dependent script, place it
> after line 32.

No function/class definitions — pure template.

### `html_header.php` — top vendor band

`modules/com_common/html_header.php:17-22`. Renders `#header-top` containing the
version string `sprintf(_("version %s"), "3.0.0")` (`:19` — **version is
hard-coded here**) and the vendor name from `Core::UI_VENDOR` (`:20`). No logic.

### `html_mainmenu.php` — user box + top navigation menu

`modules/com_common/html_mainmenu.php`. Two responsibilities, both gated on
`$my->GR_level != Group::USER` (`:3`):

1. `#header-box` / `#module-status` (`:7-18`): logged-in session count
   (`SessionDAO::getSessionCount()`, `:4`), a logout link to
   `index2.php?option=logout` (`:13`), and the current user's name (`:16`).
2. The top menu. A JS array literal `myMenu` (`:22-63`) is built inline — each
   entry is `[iconHtml, label, url, target, tooltip, ...children]`; `_cmSplit`
   (`:25,34,40`) inserts separators. It is rendered by the JSCookMenu call
   `cmDraw('myMenuID', myMenu, 'hbr', cmThemeOffice, 'ThemeOffice')` (`:64`) into
   the placeholder `<div id="myMenuID">` (`:20`). Labels/tooltips are wrapped in
   `_()` and `addslashes()`-escaped for the JS string context.

**This array is the single source of truth for the top navigation.** Each leaf's
URL is an `index2.php?option=com_<feature>` link (`:28-61`). No PHP functions
defined; it is markup + an inline `<script>`.

### `html_footer.php` — footer + timing

`modules/com_common/html_footer.php:19-26`. Renders `#footer` with the copyright
line and `printf(gettext("Page generated in %s seconds"), $seconds)` where
`$seconds = round($mainframe->getTimer(), 3)` (`:16`). Reads global `$mainframe`.

### `html_end.php` — document close

`modules/com_common/html_end.php:16-18`. Emits a `<noscript>` "Javascript must be
enabled" warning and closes `</body></html>`. No logic.

### `html_debug.php` — request/session dump

`modules/com_common/html_debug.php:16-41`. Renders a `#debug` block with four
collapsible `<h5>`/`<pre>` panels that `print_r()` `$_SESSION`, `$_REQUEST`,
`$_GET`, `$_POST` (`:20,26,32,38`). Panels start `display:none` and are revealed by
inline `onclick` handlers (`:17,23,29,35`). Included only under
`Core::SYSTEM_DEBUG` (see include table above).

### `login.php` — login screen

`modules/com_common/login.php`. Standalone document described in
[Overview → Login page](#login-page-siteindexphp). Key markup: the form
(`:53`), username/password inputs (`:64-66`), submit button `name="submit"`
(`:67`). Only asset is `css/login.css` (`:25`); the only script is the inline
`setFocus()` (`:27-32`), invoked from `<body onload="setFocus();">` (`:35`).

### `PageNav.php` — pagination helper for list views

`modules/com_common/PageNav.php`. Class `PageNav` (`:23`), guarded by
`defined('VALID_MODULE') or die(...)` (`:18`). This is the standard widget every
list screen uses to render its "Show # / Records X to Y / page links" footer.

**Constructor** (`:39`):
`__construct($total, $limitstart, $limit, $suffix = "")`
- `$total` — total row count (int-cast, `:41`).
- `$limitstart` — zero-based offset of the first row on this page (clamped ≥ 0, `:42`).
- `$limit` — rows per page (clamped ≥ 1, `:43`).
- `$suffix` — appended to the generated field names (`limit`, `limitstart`), so a
  page with two independent lists can carry a second navigator. The bank-statement
  double-list page uses `'2'` (`modules/com_bankaccount/bankaccount.index.php:384`),
  matching the `limit2`/`limitstart2` state persisted in
  `site/index2.php:99-101`.

**Methods:**

| Method | Source | Returns / does |
|--------|--------|----------------|
| `getLimitBox()` | `:56` | `<select name="limit{suffix}">` of page sizes `5,10,15,20,25,30,50,100,200,500` (`:58`); `onchange` submits `document.adminForm` (`:69`). Emits a hidden `limitstart{suffix}=0` (`:72`) so changing page size returns to page 1 |
| `getPagesCounter()` | `:79` | Localized "Records %d to %d from %d" (`:89`), or "No record found" when `total == 0` (`:91`) |
| `getPagesLinks()` | `:99` | First/Previous/numbered/Next/Last links; injects an inline `goPage(suffix, limitStart)` JS helper (`:103-108`) that sets `document.adminForm['limitstart'+suffix]` and submits. Window of `$displayed_pages = 10` page numbers (`:110`) |
| `getListFooter()` | `:150` | Convenience wrapper: two centered `<div>`s combining `getPagesLinks()` + "Show #" + `getLimitBox()` + `getPagesCounter()`. **This is what list views call** |
| `rowNumber($i)` | `:166` | `$i + 1 + $this->limitstart` — the display row number for the i-th row on the current page |

**How modules use it:** the controller builds the navigator from the total count
and the session-persisted `limit`/`limitstart`, then the view echoes the footer and
per-row numbers. Example — `com_person`:

- `modules/com_person/person.index.php:129` — `$pageNav = new PageNav($total, $limitstart, $limit);`
- `modules/com_person/person.html.php:182` — `echo $pageNav->getListFooter();`
- `modules/com_person/person.html.php:215` — `echo $pageNav->rowNumber($i);`

The same triple appears in `com_internet`, `com_group`, `com_charge`,
`com_bankaccount`, `com_iptrafficreport`, `com_paymentreport`, etc.
`getLimitBox()`/`goPage()` both submit the form named **`adminForm`**, so a list
view must wrap its table in `<form name="adminForm">` for pagination to work.

---

## Client-side assets

All loaded from `html_start.php` (see above); `login.php` loads only `login.css`.

### `site/js/`

| File | Role |
|------|------|
| `jquery-3.6.3.min.js` | Vendor jQuery 3.6.3 (banner in file). Only consumer in-tree is Chosen |
| `chosen.jquery.min.js` | Vendor Chosen v1.8.7 — turns a `<select>` into a searchable, styled dropdown via `$(sel).chosen({...})` |
| `JSCookMenu.js` | Vendor JSCookMenu v2.0.4 — the top navigation menu engine; exposes `cmDraw(...)` / `_cmSplit` used by `html_mainmenu.php:64` |
| `dtree.js` | Vendor dTree 2.05 — collapsible tree navigation (`new dTree(...)`, `.add(...)`); used by `com_network`, `com_bankaccount`, `com_massmessages` |
| `overlib.js` | Vendor overLIB 4.21 — hover tooltips/popups (`overlib(...)`); used by `com_charge` |
| `CalendarPopup.js` | Vendor Matt Kruse date picker — `new CalendarPopup("caldiv")`, then `.select(field, anchor, format)` or `.showCalendar(anchor)`; date fields across `com_person`, `com_bankaccount`, `com_log`, `com_message`, `com_paymentreport`, `com_iptrafficreport`, `com_personaccount`, `com_myprofile` |
| `tabs/tabpane.js` | Vendor WebFX Tab Pane 1.02 — `new WebFXTabPane(element, ...)` builds tabbed panels; used by `com_person` (`person.html.php:617`) |
| `ThemeOffice/theme.js` | JSCookMenu "ThemeOffice" skin config: defines `cmThemeOffice` (icons/spacing/`delay:500`) and the split constants, referencing images under `js/ThemeOffice/` (`site/js/ThemeOffice/theme.js:2-44`) |
| `functions.js` | Project helper functions (below) |
| `validator.js` | Client-side form validation library (below) |

#### `functions.js` — shared helpers

`site/js/functions.js`. Most are legacy Dreamweaver/Joomla helpers; the ones that
matter for list/form screens all operate on the form named **`adminForm`**:

| Function | Source | Purpose |
|----------|--------|---------|
| `submitform(pressbutton)` | `:147` | Sets `adminForm.task.value = pressbutton` and submits; honors a form-level `onsubmit` (the validator hook) unless the task contains `cancel` (`:150`) |
| `submitbutton(pressbutton)` | `:140` | Default entry point that delegates to `submitform`; modules may override it |
| `checkAll(n, fldName)` | `:84` | Toggle-all for list checkboxes `cb0..cb{n-1}` driven by the `toggle` box; maintains `boxchecked` (`:102-106`) |
| `listItemTask(id, task)` | `:109` | Checks a single row's box, sets `boxchecked=1`, then `submitbutton(task)` — used for per-row actions |
| `isChecked(isitchecked)` | `:129` | Increments/decrements `adminForm.boxchecked` as a row box toggles |
| `hideMainMenu()` | `:125` | Sets `adminForm.hidemainmenu.value = 1` (pairs with the `hidemainmenu` gate in `index2.php:121`) |
| `getSelected(allbuttons)` | `:165` | Returns the value of the checked radio in a group |
| `setSelectedValue` / `getSelectedValue` / `chgSelectedValue` | `:1` / `:15` / `:27` | `<select>` value get/set helpers (use `eval('document.'+frmName)`) |
| `trim` / `ltrim` / `rtrim` | `:200` / `:174` / `:187` | String trimming |
| `showImageProps` / `applyImageProps` | `:42` / `:60` | Legacy image-picker helpers (no live caller in modules) |
| `MM_findObj` / `MM_swapImage` / `MM_swapImgRestore` / `MM_preloadImages` | `:204` / `:218` / `:229` / `:234` | Legacy Dreamweaver rollover helpers |

A large commented-out block of date helpers (`isInteger`, `isDate`, `isMonth`,
`isQuater`, `isYear`) sits at `:258-388` — **dead/disabled**, kept as source
comments only.

#### `validator.js` — client-side form validation

`site/js/validator.js` — vendor "JavaScript Form Validator v4.0" (JavaScript-Coder).
**The primary tool for adding client-side validation to a form.**

**API (how modules use it):**

1. `var v = new Validator("adminForm");` (`:18`) — binds a form by name; it hijacks
   the form's `onsubmit` (`:33`) so `submitform()`'s `onsubmit` check (`functions.js:150`)
   triggers validation on submit.
2. `v.addValidation(fieldName, descriptor, errorMsg [, condition]);` (`:155`) — attach
   one rule to a field. `condition` (optional 4th arg) is an `eval`'d expression;
   when it evaluates false the rule is skipped (`:419-423`).
3. Optional display modes: `v.EnableOnPageErrorDisplay()` (`:203`, per-field inline
   divs named `{form}_{field}_errorloc`) or `v.EnableOnPageErrorDisplaySingleBox()`
   (`:207`, one `{form}_errorloc` box); `v.EnableMsgsTogether()` (`:116`) collects
   all errors before showing. **Default display is a JS `alert()`** (`AlertMsgDisplayer`,
   `:247`) — and no module in this tree calls the OPED enablers, so validation
   failures surface as alert popups.

**Available rule descriptors** (dispatched in `validateInput`'s switch, `:876-992`;
`cmd=value` form for parameterized rules):

| Descriptor(s) | Source | Checks |
|---------------|--------|--------|
| `req` / `required` | `:877` | Non-empty; also honors a `getcal()` hook so a CalendarPopup field counts as filled (`TestRequiredInput`, `:662-678`) |
| `maxlen` / `maxlength=N` | `:882` | Length ≤ N |
| `minlen` / `minlength=N` | `:887` | Length ≥ N (only when non-empty) |
| `alnum` / `alphanumeric` | `:892` | `[A-Za-z0-9]` only |
| `alnum_s` / `alphanumeric_space` | `:897` | Alphanumeric + space |
| `num` / `numeric` / `dec` / `decimal` | `:902` | Matches `^[\-\+]?[\d\,]*\.?[\d]*$` |
| `alpha` / `alphabetic` | `:912` | `[A-Za-z]` only |
| `alpha_s` / `alphabetic_space` | `:917` | Alphabetic + space |
| `email` | `:922` | `validateEmail()` regex (`:460`) |
| `lt` / `lessthan=N`, `gt` / `greaterthan=N` | `:926` / `:931` | Numeric less/greater than N |
| `regexp=RE` | `:936` | Value must match RE |
| `dontselect=V` | `:940` | `<select>` must not have value V selected |
| `dontselectchk=V` / `shouldselchk=V` | `:944` / `:948` | Checkbox with value V must be off / on |
| `selmin=N` / `selmax=N` | `:952` / `:956` | Min/max number of checked boxes in a group |
| `selone` / `selone_radio` | `:960` | At least one radio in the group selected |
| `selectradio=V` / `dontselectradio=V` | `:965` / `:969` | Radio value V must / must not be selected |
| `eqelmnt=F` / `ltelmnt` / `leelmnt` / `gtelmnt` / `geelmnt` / `neelmnt` | `:974-981` | Compare this field against another field F (`TestComparison`, `:478`) |
| `req_file` | `:983` | Required (file input) |
| `file_extn=a;b;c` | `:987` | File extension must be one of the list |

> **Important gotcha for UI edits:** several modules call
> `addValidation(..., "date=dd.MM.yyyy", ...)` (e.g.
> `modules/com_person/person.html.php:880,1195,1200`), but **there is no `date`
> case in the switch** (`:876-992`). `validateInput` initializes `ret = true`
> (`:865`) and returns it for any unrecognized command, so `date=...` rules are
> **silent no-ops** — the date format is only enforced server-side / by the
> CalendarPopup picker, not by this library. Do not assume a `date=` rule blocks
> submission.

### `site/css/`

| File | Styles |
|------|--------|
| `template.css` | Global chrome: `body`, `#wrapper`, `#header-top`/`#header-box`, list tables (`table.adminlist`), forms, `.pagenav`, footer (`site/css/template.css`) |
| `login.css` | The standalone login screen — `#login-box`, `.login-form`, decorative `.t`/`.b`/`.m` corners (`site/css/login.css`) |
| `report.css` | Payment-report row status coloring: `table.adminlist tbody tr td.payment-report-status-*` classes (finished-in-time, overdue, pending, insufficient-funds, …) (`site/css/report.css:1-26`) |
| `icon.css` | The `.icon-48-*` sprite classes used on `<div class="header icon-48-...">` screen titles (e.g. `com_internet`, `com_bankaccount`) (`site/css/icon.css`) |
| `chosen.css` | Vendor Chosen widget styling (searchable selects); pairs with `chosen-sprite.png` |
| `dtree.css` | Vendor dTree styling: `.dtree`, node/hover/selected states (`site/css/dtree.css:7-`) |
| `calendar-popup.css` | **Empty file (0 bytes)** — placeholder for the CalendarPopup styling; the picker is styled inline/by defaults |
| `tabs/tabpane.css` | WebFX tab-pane styling: `.dynamic-tab-pane-control`, `.tab-row .tab`, `.tab.selected` (`site/js/tabs/tabpane.css`) |
| `ThemeOffice/theme.css` | JSCookMenu "ThemeOffice" skin: `.ThemeOfficeMenu`, `.ThemeOfficeSubMenu*`, `.ThemeOfficeMainItem*` (`site/js/ThemeOffice/theme.css`) |

---

## How views use the assets

Views are the `HTML_<feature>` classes in `<feature>.html.php`. They rely on the
assets already loaded by `html_start.php` (no per-page `<script src>` for the
shared libraries) and wire behavior with small inline `<script>` blocks. The whole
scheme hangs off a form named **`adminForm`** (`modules/com_person/person.html.php:123`),
which `functions.js` and `PageNav`'s generated JS both target.

- **List pagination.** Wrap the list `<table class="adminlist">`
  (`person.html.php:155`) in `<form name="adminForm">`, then
  `echo $pageNav->getListFooter();` (`person.html.php:182`) and
  `$pageNav->rowNumber($i)` per row (`person.html.php:215`). The controller creates
  the navigator (`person.index.php:129`).
- **Client validation.** In an edit view, after the form, emit an inline script:
  `var formValidator = new Validator("adminForm");` then one
  `formValidator.addValidation("FIELD", "rule", "<?php echo _('msg'); ?>");` per
  rule. Real examples: `modules/com_person/person.html.php:877-885`,
  `modules/com_bankaccount/bankaccount.html.php:773-779`,
  `modules/com_myprofile/myprofile.html.php:994`.
- **Chosen selects.** Give the `<select>` an id and call `.chosen({...})` after it:
  `$("#chosen-payment").chosen({ disable_search_threshold: 10, width: "97%" });`
  (`modules/com_paymentreport/paymentreport.html.php:147-150`).
- **Date pickers.** `var cal1x = new CalendarPopup("caldiv");`
  (`modules/com_person/person.html.php:309,1089`) then a trigger link
  `onclick="cal1x.select(document.adminForm.PE_birthdate,'anchor1x','dd.MM.yyyy'); return false;"`
  (`person.html.php:557`) or `cal1x.showCalendar('anchor1x')` (`person.html.php:1106`).
- **Screen title icon.** `<div class="header icon-48-<name>">` pulls a sprite from
  `icon.css` (`modules/com_internet/internet.html.php:102`).
- **Tabbed panes.** `new WebFXTabPane(document.getElementById("modules-cpanel-person"), 1)`
  (`modules/com_person/person.html.php:617`).

---

## Adding / changing UI — practical notes

- **Add or restyle a CSS rule.** Global chrome → `site/css/template.css`. Login
  screen → `site/css/login.css`. Payment-report status colors →
  `site/css/report.css`. Title-bar icons → `site/css/icon.css`. All shared
  stylesheets are linked once in `modules/com_common/html_start.php:34-41`; a new
  stylesheet must be added there (and `login.css` is linked separately in
  `login.php:25`).
- **Add client validation to a form.** In the view's inline script, reuse the
  existing `new Validator("adminForm")` block and add
  `formValidator.addValidation("<field>", "<rule>", "<message>");` using a rule from
  the [validator table](#validatorjs--client-side-validation). Remember `date=` is a
  no-op — enforce dates server-side. To surface errors inline instead of `alert()`,
  call `formValidator.EnableOnPageErrorDisplay()` and add
  `{adminForm}_{field}_errorloc` target divs.
- **Add or move a menu item.** Edit the `myMenu` array in
  `modules/com_common/html_mainmenu.php:22-63`. Each item is
  `[iconHtml, _("Label"), 'index2.php?option=com_<feature>', target, _("Tooltip")]`;
  nest child arrays for sub-menus and use `_cmSplit` for separators. Wrap all
  human text in `_()` + `addslashes()` as the surrounding entries do. Remember the
  whole menu only renders for non-USER groups (`:3`).
- **Wire list pagination for a new list screen.** Controller:
  `$pageNav = new PageNav($total, $limitstart, $limit [, '<suffix>']);` reading
  `limit`/`limitstart` from `$_SESSION['UI_SETTINGS'][$option]`
  (persisted in `site/index2.php:88-102`). View: put the table inside
  `<form name="adminForm">`, `echo $pageNav->getListFooter();`, and use
  `$pageNav->rowNumber($i)` for row numbers. For a second list on the same page,
  pass a `suffix` (e.g. `'2'`) so its `limit2`/`limitstart2` fields do not collide —
  the double-list bank-statement page does exactly this
  (`modules/com_bankaccount/bankaccount.index.php:384`).
- **Add a new shared script.** Register it in `modules/com_common/html_start.php`
  (`:24-33`). If it depends on jQuery, place the tag **after** line 32
  (`jquery-3.6.3.min.js`).
</content>
</invoke>
