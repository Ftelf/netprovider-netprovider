# com_charge

> Defines reusable charge (subscription) templates — name, amount/VAT, period, tolerance, type and optional bound Internet service — that person subscriptions and billing draw from.

## Access

No per-module ACL check exists in the controller; any authenticated non-`USER` session reaches it. `index2.php` forces `GR_level == Group::USER` to `com_myprofile` before dispatch (`site/index2.php:117-119`), so only `ADMINISTRATOR` (5) and `SUPER_ADMINISTRATOR` (9) effectively see `com_charge` (`includes/tables/Group.php:37-39`). Unknown/invalid options fall back to `com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-82`).

## Entry point

- Option: `?option=com_charge`
- Controller: `modules/com_charge/charge.index.php`
- View class: `HTML_charge` in `charge.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `new` | Toolbar "New" → `newCH()` → `submitbutton('new')` (`charge.html.php:46-49,86`) | `charge.index.php:34` → `editCharge(null)` (`:86`) | none (builds blank `Charge`, defaults `CH_tolerance=7`, `CH_currency="CZK"`); `InternetDAO::getInternetArray()` | none | `HTML_charge::editCharge()` (`charge.html.php:245`) | none |
| `edit` | Row name link → `edit(id)` → `submitform('edit')` (`charge.html.php:39-44,163`) | `charge.index.php:38` → `editCharge($chid)` (`:86`) | `ChargeDAO::getChargeByID()`; `InternetDAO::getInternetArray()` | none | `HTML_charge::editCharge()` (`charge.html.php:245`) | none |
| `editA` | Toolbar "Edit" → `editA()` → `submitbutton('editA')` (`charge.html.php:51-58`) | `charge.index.php:42` → `editCharge(intval($cid[0]))` (`:86`) | `ChargeDAO::getChargeByID()`; `InternetDAO::getInternetArray()` | none | `HTML_charge::editCharge()` (`charge.html.php:245`) | none |
| `save` | Toolbar "Save" → `submitbutton('save')` (`charge.html.php:341`) | `charge.index.php:46` → `saveCharge('save')` (`:108`) | `$_POST` bound to `Charge` (`:113`) | `$database->insertObject`/`updateObject` → `charge` (`:165,167`) | on parse error re-renders `HTML_charge::editCharge()` (`:125,139,152`); else redirect | `appContext` message, `Log::LEVEL_INFO`; redirect `?option=com_charge` (`:182`); inner switch `:170-183` |
| `apply` | Toolbar "Apply" → `submitbutton('apply')` (`charge.html.php:334`) | `charge.index.php:47` → `saveCharge('apply')` (`:108`) | `$_POST` bound to `Charge` (`:113`) | `$database->insertObject`/`updateObject` → `charge` (`:165,167`) | on parse error re-renders `HTML_charge::editCharge()` | `appContext` message, `Log::LEVEL_INFO`; redirect back to `task=edit` (`:175`) |
| `remove` | Toolbar "Delete" → `remove()` (JS confirm) → `submitbutton('remove')` (`charge.html.php:60-68,100`) | `charge.index.php:51` → `removeCharge($cid)` (`:189`) | `ChargeDAO::getChargeByID()`, `ChargeDAO::getUsedChargeArray()` (`:198,200`) | `ChargeDAO::removeChargeByID()` → `charge` (`:217`) | none (redirect list `:223`, or `Core::backWithAlert` if in use `:215`) | `Log::LEVEL_WARNING` when blocked / `LEVEL_INFO` when deleted; `appContext` message |
| `cancel` | Toolbar "Cancel" → `submitbutton('cancel')` (`charge.html.php:347`) | `charge.index.php:55` → `showCharge()` (`:66`) | `ChargeDAO::getChargeCount()`, `ChargeDAO::getChargeArray()`, `InternetDAO::getInternetArray()` (`:74,75,77`) | none | `HTML_charge::showCharges()` (`charge.html.php:32`) | none |
| _default_ (list) | Direct nav `?option=com_charge` (also target of `save`/`remove` redirects) | `charge.index.php:59` → `showCharge()` (`:66`) | `ChargeDAO::getChargeCount()`, `ChargeDAO::getChargeArray()`, `InternetDAO::getInternetArray()` | none | `HTML_charge::showCharges()` (`charge.html.php:32`) | none |

All 7 `case` labels plus `default` in the `charge.index.php:33-62` switch are covered. Inner switches at `:170-183` (inside `saveCharge`) only choose redirect/message wording and are not top-level tasks.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_charge::showCharges()` | Paged list of charge templates with New/Edit/Delete toolbar; columns adapt when VAT-payer specifics enabled | `charge.html.php:32` |
| `HTML_charge::editCharge()` | Create/edit form with Apply/Save/Cancel toolbar; JS shows Internet-service block when type = Internet payment | `charge.html.php:245` |

## Data touched

- DAOs: `ChargeDAO` (`getChargeCount`, `getChargeArray`, `getChargeByID`, `getUsedChargeArray`, `removeChargeByID`); `InternetDAO` (`getInternetArray`). `HasChargeDAO` is `require_once`d (`charge.index.php:22`) but no method is called directly in the controller.
- Tables: `charge` via `Charge` (`includes/tables/Charge.php`). Inserts/updates are done directly through `$database->insertObject`/`updateObject`, not through a DAO write method.

## Forms & fields

`HTML_charge::editCharge()` form (`charge.html.php:245-611`), all fields bound to `Charge` via `database::bind($_POST, $charge)` (`charge.index.php:113`):

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `CH_name` | `CH_name` template name | Client: `submitbutton()` alerts if `trim` empty (`charge.html.php:280`). No server check. |
| `CH_description` | `CH_description` description | Client: alert if `trim` empty (`charge.html.php:282`). No server check. |
| `CH_priority` | `CH_priority` (select 10..-10) | none |
| `CH_baseamount` | `CH_baseamount` amount without VAT (only when VAT specifics enabled) | Server `NumberFormat::parseMoney()`; on failure `Core::alert` + re-render (`charge.index.php:131-141`) |
| `CH_vat` | `CH_vat` VAT % (only when VAT specifics enabled) | Server `NumberFormat::parseMoney()`; on failure alert + re-render (`charge.index.php:144-153`) |
| `CH_amount` | `CH_amount` amount with VAT / amount | Server `NumberFormat::parseMoney()`; on failure alert + re-render (`charge.index.php:117-127`) |
| `CH_currency` | `CH_currency` (select from `BankAccount::$CURRENCY_ARRAY`) | none (`charge.html.php:443`) |
| `CH_period` | `CH_period` write-off period (select `Charge::$PERIOD_ARRAY`) | none (`charge.html.php:457`) |
| `CH_writeoffoffset` | `CH_writeoffoffset` (select -360..359) | none (`charge.html.php:471`) |
| `CH_tolerance` | `CH_tolerance` payment-deadline days (select 0..360 from `getToleranceArray()`) | none (`charge.html.php:487`) |
| `CH_type` | `CH_type` (select `Charge::$TYPE_ARRAY`) | Server: `CH_internetid` forced `null` when type ≠ `TYPE_INTERNET_PAYMENT` (`charge.index.php:160-162`) |
| `CH_internetid` | `CH_internetid` bound Internet service (select) | Shown/hidden client-side by `updateTypeSelect()`; nulled server-side unless Internet payment |
| `CH_chargeid` | hidden PK; empty = new record | drives `$isNew` branch (`charge.index.php:114`) |

When VAT specifics are disabled, `CH_baseamount` is set to `CH_amount` and `CH_vat` to `0` server-side (`charge.index.php:155-158`). `showCharges()` is display-only (no editable fields).

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none.
- Redirects: `apply` → `task=edit`; `save`/`default` → charge list; `remove` → charge list; parse-error paths re-render `editCharge`.
- Cross-module: `InternetDAO::getInternetArray()` pulls `com_internet` service definitions; `ChargeDAO::getUsedChargeArray()` joins `hascharge`+`person` to block deletion of charges bound to subscriptions (`com_person`). `CH_currency` options are sourced from `BankAccount::$CURRENCY_ARRAY` (`charge.html.php:443`). Charges are consumed downstream by the billing engine (`ChargesUtil`) and by `AccountEntryUtil` (amount matching, `includes/billing/AccountEntryUtil.php:87`).

## Source anchors

- Controller switch: `modules/com_charge/charge.index.php:33-62`
- Handlers: `showCharge` `:66`, `editCharge` `:86`, `saveCharge` `:108`, `removeCharge` `:189`, `getToleranceArray` `:230`
- Views: `HTML_charge::showCharges` `charge.html.php:32`, `HTML_charge::editCharge` `charge.html.php:245`
- DAO: `includes/dao/ChargeDAO.php` (`getChargeByID:62`, `getUsedChargeArray:51`, `removeChargeByID:75`)
- Table: `includes/tables/Charge.php` (`$TYPE_ARRAY:92`, `$PERIOD_ARRAY:75`)
- Access: `site/index2.php:117-119`, `includes/Mainframe.php:77-82`, `includes/tables/Group.php:37-39`
