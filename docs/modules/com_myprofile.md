# com_myprofile

> End-customer self-service profile: shows the logged-in person's details, charges, payments, IPs, networks, messages and traffic, and lets them edit their own contact data and password.

## Access

- Authenticated session required; `index2.php` validates the DB-backed session and redirects to `index.php` on failure (`site/index2.php:64-70`).
- Regular users are forced here: when `$my->GR_level == Group::USER`, `$option` is overridden to `com_myprofile` regardless of the requested option (`site/index2.php:117-119`). `Group::USER = 0` (`includes/tables/Group.php:37`).
- Reachable by any authenticated level (linked from the "User agenda" menu, `modules/com_common/html_mainmenu.php:32`); higher levels are `Group::ADMINISTRATOR = 5` and `Group::SUPER_ADMINISTRATOR = 9` (`includes/tables/Group.php:38-39`).
- No per-module ACL gate: `MainFrame::getPath()` resolves `modules/com_myprofile/myprofile.index.php` and falls back to `com_admin` only if the file is missing (`includes/Mainframe.php:69-84`).
- The person shown/edited is always the session user (`$_SESSION['SE_personid']`), never a request parameter (`modules/com_myprofile/myprofile.index.php:67,155,170`).

## Entry point

- Option: `?option=com_myprofile`
- Controller: `modules/com_myprofile/myprofile.index.php`
- View class: `HTML_myprofile` in `myprofile.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `edit` | Toolbar "Edit" → `javascript:edit()` → `submitform('edit')` (`myprofile.html.php:49-52,88-91`) | `editPerson()` `myprofile.index.php:45-47,151-164` | `$_SESSION['SE_personid']`; `PersonDAO::getPersonByID` (`:157`) | none (masks password to `******` in memory, `:159-161`) | `HTML_myprofile::editMyProfile()` (`:163`) | none |
| `save` | Edit toolbar "Save" → `submitbutton('save')` → `submitform('save')` (`myprofile.html.php:754-756,776`) | `savePerson()` `myprofile.index.php:50-52,166-216` | `$_POST` (bound to `Person` via `database::bind`, `:175`); `$_POST[PE_password1]`,`$_POST[PE_password2]` (`:179-180`); `PersonDAO::getPersonByID` (`:172`) | `$database->updateObject("person", ...)` → `person` (`:209`) | `HTML_myprofile::editMyProfile()` on password mismatch (`:197`), else redirect | `Core::alert` on mismatch (`:189`); `$appContext->insertMessage` (`:212`); `$database->log` INFO (`:213`); `Core::redirect` to `com_myprofile` (`:215`) |
| `cancel` | Edit toolbar "Cancel" → `submitbutton('cancel')` → `submitform('cancel')` (`myprofile.html.php:749-750,783`) | `showPerson()` `myprofile.index.php:54-55,63-149` | see default row | none | `HTML_myprofile::showMyProfile()` (`:148`) | none |
| _default_ (no/other `task`, initial load) | Menu link `index2.php?option=com_myprofile` (`html_mainmenu.php:32`); traffic-month calendar submit (`myprofile.html.php:63-68`) | `showPerson()` `myprofile.index.php:58-60,63-149` | `PersonDAO::getPersonByID`, `PersonAccountDAO::getPersonAccountByID`, `BankAccountEntryDAO::getBankAccountEntryArray`, `PersonAccountEntryDAO::getPersonAccountEntryArrayByPersonAccountID`, `ChargeDAO::getChargeArray`, `InternetDAO::getInternetArray`, `HasChargeDAO::getHasChargeWithChargeWithPersonArrayByPersonID`, `ChargeEntryDAO::getChargeEntryArrayByHasChargeID`, `GroupDAO::getGroupByID`, `RolememberDAO::getRolememberAndRoleArrayByPersonID`, `IpDAO::getIpArrayByPersonID`, `NetworkDAO::getNetworkArrayByPersonID`, `MessageDAO::getMessageArray`, `IpAccountDAO::getIpAccountMonthSumByIpID` (`:69-118`); traffic month from `$_SESSION['UI_SETTINGS']['com_myprofile']['filter']['date_month']` (`:92`) | none | `HTML_myprofile::showMyProfile()` (`:148`) | none |

Switch is `myprofile.index.php:44-61`. Cases: `edit`, `save`, `cancel`, `default` — all covered.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_myprofile::showMyProfile()` | Read-only profile: personal data panel + tabbed panes (Login credentials, Role, Payments/charges with per-charge entry sub-tables, Incoming payments, IP addresses, Networks, Messages, Data traffic with month calendar); toolbar Edit/Logout | `myprofile.html.php:37-727` |
| `HTML_myprofile::editMyProfile()` | Editable form: personal data inputs + optional firm fields + Login-credentials tab (username disabled, password + confirmation); toolbar Save/Cancel; client validator | `myprofile.html.php:729-1014` |

## Data touched

- DAOs: `PersonDAO` (`getPersonByID`), `PersonAccountDAO` (`getPersonAccountByID`), `PersonAccountEntryDAO` (`getPersonAccountEntryArrayByPersonAccountID`), `BankAccountEntryDAO` (`getBankAccountEntryArray`), `ChargeDAO` (`getChargeArray`), `InternetDAO` (`getInternetArray`), `HasChargeDAO` (`getHasChargeWithChargeWithPersonArrayByPersonID`), `ChargeEntryDAO` (`getChargeEntryArrayByHasChargeID`), `GroupDAO` (`getGroupByID`), `RolememberDAO` (`getRolememberAndRoleArrayByPersonID`), `IpDAO` (`getIpArrayByPersonID`), `NetworkDAO` (`getNetworkArrayByPersonID`), `MessageDAO` (`getMessageArray`), `IpAccountDAO` (`getIpAccountMonthSumByIpID`). Additional DAOs are `require_once`'d but unused in this controller: `SessionDAO`, `LogDAO`, `IpAccountAbsDAO` (`myprofile.index.php:21-39`).
- Tables: `person` (`includes/tables/Person.php`) — read via `PersonDAO`, written directly via `$database->updateObject("person", ...)` (`:209`). All other tables are read-only through the DAOs above (`person_account`, `person_account_entry`, `bank_account_entry`, `charge`, `internet`, `has_charge`, `charge_entry`, `group`, `rolemember`/`role`, `ip`, `network`, `message`, `ip_account`).

## Forms & fields

Edit form (`HTML_myprofile::editMyProfile`, `myprofile.html.php:808-984`) posts to `index2.php` with hidden `option=com_myprofile`, `task`, `PE_personid` (`:980-982`):

| Field | Column / meaning | Validation |
|-------|------------------|-----------|
| `PE_firstname` | first name | client `required` (`:995`) |
| `PE_surname` | surname | client `required` (`:996`) |
| `PE_birthdate` | birthdate (`dd.MM.yyyy`) | client `date=dd.MM.yyyy` (`:997`); server re-parse, on failure set to `DateUtil::DB_NULL_DATE` (`:202-207`) |
| `PE_email` | e-mail | client `email` (`:998`) |
| `PE_password1` | new password | client `minlength=6` (`:1000`); server: must equal `PE_password2` else `Core::alert` + re-render (`:184-199`) |
| `PE_password2` | password confirmation | client `passwordMatchValidator` (`:999,1002-1011`); server match check (`:186-199`) |
| `PE_ic`, `PE_dic`, `PE_shortcompanyname`, `PE_companyname` | firm identifiers | rendered only when `Core::ALLOW_FIRM_REGISTRATION` (`:819-842`); no explicit validation |
| `PE_nick`, `PE_gender`, `PE_degree_prefix`, `PE_degree_suffix`, `PE_icq`, `PE_tel`, `PE_secondary_phone_number`, `PE_address`, `PE_city`, `PE_zip` | contact/personal fields | none |
| `void` (login name) | display only | `disabled`, not submitted (`:959-961`) |

Server-side, `PE_username` is force-nulled before save so a self-edit can never change the login name (`:177`). Password handling: both `******`/blank leaves password unchanged (`:184-185`); equal non-blank → `md5()` (`:186-187`); mismatch aborts save (`:188-199`).

Traffic tab (in `showMyProfile`) posts hidden `filter[date_month]` + `date_month` to re-query the accounting month (`myprofile.html.php:654-657`), persisted in `$_SESSION['UI_SETTINGS']['com_myprofile']['filter']`.

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none.
- Redirects: `save` → `index2.php?option=com_myprofile` (`:215`); toolbar Logout → `index2.php?option=logout` (`myprofile.html.php:95`).
- Shared DAOs with other modules: `PersonDAO`, `MessageDAO`, `NetworkDAO`, `IpDAO`, `ChargeDAO`/`HasChargeDAO`/`ChargeEntryDAO`, `PersonAccountDAO`/`PersonAccountEntryDAO`, `BankAccountEntryDAO`, `IpAccountDAO`.
- Global routing: `index2.php` forces `Group::USER` into this module (`site/index2.php:117-119`).

## Source anchors

- Switch / dispatch: `myprofile.index.php:44-61`
- `showPerson()`: `myprofile.index.php:63-149`
- `editPerson()`: `myprofile.index.php:151-164`
- `savePerson()`: `myprofile.index.php:166-216`
- Direct `person` write: `myprofile.index.php:209`
- `HTML_myprofile::showMyProfile()`: `myprofile.html.php:37-727`
- `HTML_myprofile::editMyProfile()`: `myprofile.html.php:729-1014`
- Client validation: `myprofile.html.php:994-1011`
- Forced routing / access: `site/index2.php:117-119`; `includes/Mainframe.php:69-84`; `includes/tables/Group.php:37-39`
