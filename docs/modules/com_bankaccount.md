# com_bankaccount

> Manages bank accounts, downloads/uploads and imports bank statement ("printout") files into `bankaccountentry` rows, and matches those entries to person accounts (automatically or manually) to credit balances.

## Access

No per-module ACL check exists in the controller; any authenticated non-`USER` session reaches it. `index2.php` forces `GR_level == Group::USER` to `com_myprofile` before dispatch (`site/index2.php:117-119`), so `ADMINISTRATOR` (5) and `SUPER_ADMINISTRATOR` (9) reach `com_bankaccount` (`includes/tables/Group.php:37-39`). Within the module, the printout upload/download/import/entry-processing tasks additionally require `SUPER_ADMINISTRATOR` (`bankaccount.index.php:398,415,445,475,504`), and the "Edit bank account" toolbar is hidden for non-super users (`bankaccount.html.php:107`). Unknown options fall back to `com_admin` via `MainFrame::getPath()` (`includes/Mainframe.php:77-82`).

## Entry point

- Option: `?option=com_bankaccount`
- Controller: `modules/com_bankaccount/bankaccount.index.php`
- View class: `HTML_BankAccount` in `bankaccount.html.php`

## Tasks

| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| `editBA` | "Edit bank account" toolbar (super only) → `editBA()` → `submitform('editBA')` (`bankaccount.html.php:37-40,110-114`) | `bankaccount.index.php:44` → `editBankAccount($bid)` (`:254`) | `BankAccountDAO::getBankAccountByID()` (`:263`) | none | `HTML_BankAccount::editBankAccount()` (`bankaccount.html.php:504`) | none |
| `saveBA` | Edit form "Save" → `submitbutton('save')` → `submitform('saveBA')` (`bankaccount.html.php:516-519`) | `bankaccount.index.php:48` → `saveBankAccount('saveBA')` (`:291`) | `$_POST` bound to `BankAccount` (`:296`); `getBankAccountByID` on update (`:340`) | `insertObject`→`bankaccount` (`:326`) / `updateObject`→`bankaccount` (`:338`) | on start-balance parse error re-renders `editBankAccount()` (`:323`); else redirect | `appContext` message, `Log::LEVEL_INFO`; redirect `task=show` (`:358`); inner switch `:346-360` |
| `applyBA` | Edit form "Apply" → `submitbutton('apply')` → `submitform('applyBA')` (`bankaccount.html.php:513-516`) | `bankaccount.index.php:49` → `saveBankAccount('applyBA')` (`:291`) | `$_POST` bound to `BankAccount`; `getBankAccountByID` on update | `insertObject`/`updateObject`→`bankaccount` | on parse error re-renders `editBankAccount()` | `appContext` message, `Log::LEVEL_INFO`; redirect back to `task=editBA` (`:351`) |
| `cancelUploadBankList` | Upload form "Cancel" → `submitbutton('cancelUploadBankList')` (`bankaccount.html.php:1643`) | `bankaccount.index.php:53` → `showBankList($bid)` (`:366`) | `BankAccountDAO::getBankAccountByID()` (`:376`), `EmailListDAO::getEmailListArrayByBankAccountID()` (`:377`) | none (may reset `limitstart2` in session `:381`) | `HTML_BankAccount::showBankList()` (`bankaccount.html.php:790`) | none |
| `showBankList` | "Bank printouts" toolbar → `showBankList()` → `submitbutton('showBankList')` (`bankaccount.html.php:32-35,100`); also hidden `task=showBankList` (`:996`) | `bankaccount.index.php:54` → `showBankList($bid)` (`:366`) | `getBankAccountByID()`, `EmailListDAO::getEmailListArrayByBankAccountID()` | none | `HTML_BankAccount::showBankList()` (`bankaccount.html.php:790`) | none |
| `uploadBankLists` | Printouts toolbar "Upload new printouts" → `submitbutton('uploadBankLists')` (`bankaccount.html.php:834`) | `bankaccount.index.php:58` → `uploadBankLists($bid)` (`:394`) | `getBankAccountByID()` (`:403`) | none | `HTML_BankAccount::uploadBankLists()` (`bankaccount.html.php:1634`) | `SUPER_ADMINISTRATOR` guard → message + redirect (`:398-401`) |
| `downloadBankLists` | Printouts toolbar "Download new printouts" (only if `BA_datasource == DATASOURCE_EMAIL_CONTENT`) → `submitbutton('downloadBankLists')` (`bankaccount.html.php:844`) | `bankaccount.index.php:62` → `downloadBankLists($bid)` (`:411`) | `getBankAccountByID()` (`:420`) | `EmailBankAccountList::downloadNewAccountLists()` (`:426`) → downloads email attachments, persists `emaillist` | none (redirect `task=showBankList` `:435`) | super guard (`:415`); `appContext` messages, `Log::LEVEL_ERROR` on exception |
| `processBankLists` | Printouts toolbar "Run import" → `submitbutton('processBankLists')` (`bankaccount.html.php:854`) | `bankaccount.index.php:66` → `processBankLists($bid)` (`:441`) | `getBankAccountByID()` (`:450`) | `EmailBankAccountList::importBankAccountEntries()` (`:455`) → parses printouts into `bankaccountentry` | none (redirect `task=showBankList` `:465`) | super guard (`:445`); messages, `Log::LEVEL_ERROR` on exception |
| `processEntries` | Printouts toolbar "Entries workout" → `submitbutton('processEntries')` (`bankaccount.html.php:862`) | `bankaccount.index.php:70` → `proceedAccountEntries($bid)` (`:471`) | `getBankAccountByID()` (`:479`) | `AccountEntryUtil::proceedAccountEntries()` (`:484`) → updates `bankaccountentry`, `personaccount`; inserts `personaccountentry` | none (redirect `task=showBankList` `:494`) | super guard (`:475`); supervisor emails via `EmailUtil` (`AccountEntryUtil.php:81,90,104`); messages, log |
| `doUploadBankLists` | Upload form submit "Nahrát výpis" → `submitbutton('doUploadBankLists')` (`bankaccount.html.php:1641,1722`) | `bankaccount.index.php:74` → `doUploadBankLists($bid)` (`:500`) | `getBankAccountByID()` (`:509`), `$_FILES['banklistFile']` | `EmailBankAccountList::uploadBankList()` (`:533`) → stores printout / entries | none (redirect `task=uploadBankLists` `:541`) | super guard (`:504`); MIME-type validation vs `BA_datasourcetype` (`:511-527`); messages, `Log::LEVEL_ERROR` |
| `editBAE` | Redirect target from `saveBAE` validation errors (`bankaccount.index.php:619,624,632`) | `bankaccount.index.php:82` → `editBankAccountEntry($eid)` (`:547`) | `BankAccountEntryDAO::getBankAccountEntryByID()` (`:549`), `PersonDAO::getPersonWithAccountArray()` (`:555`) | none | `HTML_BankAccount::editBankAccountEntry()` (`bankaccount.html.php:1015`) | redirects to `com_bankaccount` if entry already `STATUS_PROCESSED` (`:552`) |
| `editBAEA` | Entry list "Edit account entry" toolbar → `editBAE()` → `submitbutton('editBAEA')` (`bankaccount.html.php:48-55,120`) | `bankaccount.index.php:86` → `editBankAccountEntries($cid)` (`:563`) | `getBankAccountEntryByID()` per `cid` (`:573`); single-selection delegates to `editBankAccountEntry` (`:566`) | none | `HTML_BankAccount::editBankAccountEntries()` (`bankaccount.html.php:1400`), or `editBankAccountEntry()` for one row | skips `STATUS_PROCESSED` entries (`:576`); redirect `com_bankaccount` if none editable (`:583`) |
| `saveBAE` | Single-entry edit "Save" → `submitbutton('save')` → `submitform('saveBAE')` (`bankaccount.html.php:1050,1058`) | `bankaccount.index.php:90` → `saveBankAccountEntry('saveBAE')` (`:592`) | `$_POST` bound to `BankAccountEntry` (`:597`); `getBankAccountEntryByID` (`:599`); `PersonAccountDAO::getPersonAccountByID` (`:647`); `PersonDAO::getPersonByPersonAccountID` (`:661`) | when `IDENTIFY_PERSONACCOUNT`: `updateObject`→`personaccount` (`:652`), `insertObject`→`personaccountentry` (`:655`), `updateObject`→`bankaccountentry` (`:659`); else `updateObject`→`bankaccountentry` (`:672`) | none (redirect `task=show` `:680`) | sets `BE_status=PROCESSED` (`:605`); amount format/positive/sum checks redirect to `editBAE` (`:616-633`); DB transaction + rollback (`:636,666,668`); messages, `Log::LEVEL_INFO` |
| `saveBAEA` | Batch edit "Save" → `submitbutton('save')` → `submitform('saveBAEA')` (`bankaccount.html.php:1414`) | `bankaccount.index.php:94` → `saveBankAccountEntries($cid,'saveBAEA')` (`:688`) | `$_POST['BE_identifycode']` (`:692`); `getBankAccountEntryByID` per `cid` (`:697`) | `updateObject`→`bankaccountentry` per entry, setting `BE_status=PROCESSED` + `BE_identifycode` (`:703-706`) | none (redirect `task=show` `:716`) | skips `STATUS_PROCESSED` (`:700`); messages, `Log::LEVEL_INFO` |
| `cancel` | Printouts list / entry-edit views "Cancel" → `submitform('cancel')` (`bankaccount.html.php:799-800,1027,1408`) | `bankaccount.index.php:98` → `showBankAccount($bid)` (`:107`) | `BankAccountDAO::getBankAccountArray()` (`:132`), `BankAccountEntryDAO::getBankAccountEntryArrayByBankAccountID()` (`:162`), `PersonAccountEntryDAO::getPersonNameArrayByBankAccountEntryID()` (`:240`) | none | `HTML_BankAccount::showEntries()` (`bankaccount.html.php:25`) | session filter persistence |
| _default_ (list) | Direct nav `?option=com_bankaccount`; also target of `task=show` redirects from `saveBA`/`saveBAE`/`saveBAEA` (no `show` case exists) | `bankaccount.index.php:102` → `showBankAccount($bid)` (`:107`) | `getBankAccountArray()`, `getBankAccountEntryArrayByBankAccountID()`, `PersonAccountEntryDAO::getPersonNameArrayByBankAccountEntryID()` | none | `HTML_BankAccount::showEntries()` (`bankaccount.html.php:25`) | session filter persistence, in-memory report totals |

All 15 active `case` labels plus `default` in the `bankaccount.index.php:39-105` switch are covered. The two commented-out cases `newBA` (`:40`) and `removeB` (`:78`) are dead code. Inner switches at `:346-360` (`saveBankAccount`) and `:675-682` (`saveBankAccountEntry`) only pick redirect/message wording and are not top-level tasks.

## Views

| Method | Renders | Source |
|--------|---------|--------|
| `HTML_BankAccount::showEntries()` | Entry printout screen: bank-account picker + date/type/status/identification filters, global & filtered report totals, paged `bankaccountentry` list with per-row checkboxes | `bankaccount.html.php:25` |
| `HTML_BankAccount::editBankAccount()` | Bank account create/edit form (bank, account, currency, start balance, datasource, printout email); Apply/Save/Cancel toolbar | `bankaccount.html.php:504` |
| `HTML_BankAccount::showBankList()` | Imported printouts (`emaillist`) list with Upload/Download/Run-import/Entries-workout toolbar (super only) | `bankaccount.html.php:790` |
| `HTML_BankAccount::editBankAccountEntry()` | Single entry identification form: read-only entry detail + identification select + JS-built person-account allocation rows | `bankaccount.html.php:1015` |
| `HTML_BankAccount::editBankAccountEntries()` | Batch identification form for multiple entries (identification restricted to Unidentified/Internal/Ignore) | `bankaccount.html.php:1400` |
| `HTML_BankAccount::uploadBankLists()` | Printout file upload form (`banklistFile`, `MAX_FILE_SIZE`) | `bankaccount.html.php:1634` |

## Data touched

- DAOs: `BankAccountDAO` (`getBankAccountArray`, `getBankAccountByID`); `BankAccountEntryDAO` (`getBankAccountEntryArrayByBankAccountID`, `getBankAccountEntryByID`); `EmailListDAO` (`getEmailListArrayByBankAccountID`); `PersonDAO` (`getPersonWithAccountArray`, `getPersonByPersonAccountID`); `PersonAccountDAO` (`getPersonAccountByID`); `PersonAccountEntryDAO` (`getPersonNameArrayByBankAccountEntryID`). Helpers: `AccountEntryUtil` (`proceedAccountEntries`), `EmailBankAccountList` (`downloadNewAccountLists`, `importBankAccountEntries`, `uploadBankList`).
- Tables: `bankaccount` via `BankAccount` (`includes/tables/BankAccount.php`); `bankaccountentry` via `BankAccountEntry` (`includes/tables/BankAccountEntry.php`); `emaillist` (`EmailList`); `personaccount` (`PersonAccount`); `personaccountentry` (`PersonAccountEntry`); `person` (`Person`). Inserts/updates go directly through `$database->insertObject`/`updateObject` (no dedicated DAO write methods).

## Forms & fields

**`editBankAccount()` form** (`bankaccount.html.php:504-780`), bound to `BankAccount` via `database::bind($_POST, $bankAccount)` (`bankaccount.index.php:296`). On edit the bank/account/currency/start-balance inputs are disabled via `$flags` (`bankaccount.index.php:260-284`) and are explicitly nulled on update so only email/datasource fields change (`bankaccount.index.php:331-338`).

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `BA_bankname` | `BA_bankname` | Client `validator.js` `required` (`bankaccount.html.php:774`); disabled on edit |
| `BA_banknumber` | `BA_banknumber` bank code | Client `required` (`:775`); disabled on edit |
| `BA_accountname` | `BA_accountname` | Client `required` (`:776`); disabled on edit |
| `BA_accountnumber` | `BA_accountnumber` | Client `required` (`:777`); disabled on edit |
| `BA_iban` | `BA_iban` | Client `required` (`:778`); disabled on edit |
| `BA_currency` | `BA_currency` (select `BankAccount::$CURRENCY_ARRAY`) | none |
| `BA_startbalance` | `BA_startbalance` | Client `required` (`:779`); server `NumberFormat::parseMoney()` on new account, alert + re-render on failure (`bankaccount.index.php:302-325`) |
| `BA_datasource` | `BA_datasource` (select `$datasourceArray`) | none |
| `BA_datasourcetype` | `BA_datasourcetype` (select `$datasourceTypesArray`) | none |
| `BA_emailserver` / `BA_emailusername` / `BA_emailpassword` / `BA_emailsender` / `BA_emailsubject` | printout mailbox connection | none |
| `BA_bankaccountid` | hidden PK; empty = new | drives `$isNew` (`bankaccount.index.php:298`) |

**`editBankAccountEntry()` form** (`bankaccount.html.php:1015-1394`):

| Field | Column / meaning | Validation |
|-------|------------------|------------|
| `BE_datetime`,`BE_writeoff_date`,`BE_note`,`BE_accountname`,`BE_accountnumber`,`BE_banknumber`,`BE_variablesymbol`,`BE_constantsymbol`,`BE_specificsymbol`,`BE_amount`,`BE_charge`,`BE_message`,`BE_typeoftransaction` | corresponding `bankaccountentry` columns, display only | `disabled` (not submitted) |
| `BE_comment` | `BE_comment` editable note | none |
| `BE_identifycode` | `BE_identifycode` (select `$IDENTIFICATION_ARRAY`) | Client blocks `IDENTIFY_UNIDENTIFIED` on save (`:1030`); showing person-account block on `IDENTIFY_PERSONACCOUNT` |
| `PN_personaccountid[]` | per-person allocation amounts (JS-built rows) | Client: number format + sum == `BE_amount` (`:1030-1059`); server: `parseMoney`, non-negative, sum match, else redirect to `editBAE` (`bankaccount.index.php:616-633`) |
| `BA_bankaccountid`,`BE_bankaccountentryid` | hidden context | — |

**`editBankAccountEntries()` batch form** (`bankaccount.html.php:1400-1628`): `cid[]` hidden per entry (`:1475`); `BE_identifycode` select limited to Unidentified/Internal/Ignore (`:1592-1596`); client blocks `IDENTIFY_UNIDENTIFIED` (`:1411`).

**`showEntries()` filter form** (`bankaccount.html.php:146-258`): `BA_bankaccountid` (account picker), `filter[date_from]`/`filter[date_to]`, `filter[entryTypeOfTransaction]`, `filter[entryStatusOfTransaction]`, `filter[entryIdentifyCodeOfTransaction]` — all persisted in `$_SESSION['UI_SETTINGS']['com_bankaccount']['filter']` and applied in `showBankAccount()` (`bankaccount.index.php:121-125,199-221`).

**`uploadBankLists()` form** (`bankaccount.html.php:1634-1749`): `banklistFile` (file input), `MAX_FILE_SIZE` hidden (`:1689`). Server validates uploaded MIME type against the account's `BA_datasourcetype` (`bankaccount.index.php:511-527`).

## Related flows & cross-module links

- Events fired (`EventCrossBar`): none. Entry auto-matching (`processEntries`) instead sends supervisor notifications directly through `EmailUtil` for duplicate/unknown/inactive-user payments (`includes/billing/AccountEntryUtil.php:81,90,104`).
- Redirects: saves redirect to `task=show` (handled by the module default); import/download/process/entry-workout redirect back to `task=showBankList`; guard failures and already-`PROCESSED` entries redirect to `com_bankaccount`.
- Cross-module / shared DAOs: `PersonDAO`, `PersonAccountDAO`, `PersonAccountEntryDAO` (shared with `com_person` / `com_personaccount`); `EmailListDAO`; `AccountEntryUtil` uses `ChargeDAO::getChargeArray()` to guess likely charge for unmatched amounts (`AccountEntryUtil.php:50,87`). Manual matching in `saveBAE` credits `personaccount.PA_balance`/`PA_income`, the same accounts driven by the billing pipeline.
- Note: the `editBankAccount()` "Cancel" button submits `submitform('cancelHasCharge')` (`bankaccount.html.php:512`), which is not a defined case and therefore falls through to the module `default` (`showBankAccount`).

## Source anchors

- Controller switch: `modules/com_bankaccount/bankaccount.index.php:39-105`
- Handlers: `showBankAccount:107`, `editBankAccount:254`, `saveBankAccount:291`, `showBankList:366`, `uploadBankLists:394`, `downloadBankLists:411`, `processBankLists:441`, `proceedAccountEntries:471`, `doUploadBankLists:500`, `editBankAccountEntry:547`, `editBankAccountEntries:563`, `saveBankAccountEntry:592`, `saveBankAccountEntries:688`
- Views: `bankaccount.html.php` — `showEntries:25`, `editBankAccount:504`, `showBankList:790`, `editBankAccountEntry:1015`, `editBankAccountEntries:1400`, `uploadBankLists:1634`
- DAOs: `includes/dao/BankAccountDAO.php`, `includes/dao/BankAccountEntryDAO.php`, `includes/dao/EmailListDAO.php`
- Auto-matching: `includes/billing/AccountEntryUtil.php:45-146`
- Tables: `includes/tables/BankAccountEntry.php` (`$STATUS_ARRAY:204`, `$IDENTIFICATION_ARRAY:223`, `$TYPE_ARRAY:129`), `includes/tables/BankAccount.php`
- Access: `site/index2.php:117-119`, `includes/Mainframe.php:77-82`, `includes/tables/Group.php:37-39`, super-only guards `bankaccount.index.php:398,415,445,475,504`
