# NetProvider — Technical Documentation

> Reference for developers and operators who need to read, modify or extend the NetProvider codebase.

This document complements the higher-level [User Guide](USER_GUIDE.md). It covers the runtime architecture, request flow, data model, billing engine, network commanders, event system, module conventions and security considerations of the system.

---

## Table of contents

1. [Runtime stack](#runtime-stack)
2. [Repository layout](#repository-layout)
3. [Bootstrapping and configuration](#bootstrapping-and-configuration)
4. [Web request lifecycle](#web-request-lifecycle)
5. [CLI service lifecycle](#cli-service-lifecycle)
6. [Data layer](#data-layer)
7. [Domain model](#domain-model)
8. [Billing engine](#billing-engine)
9. [Bank-statement ingestion](#bank-statement-ingestion)
10. [Network commanders](#network-commanders)
11. [Event system](#event-system)
12. [Modules (web UI)](#modules-web-ui)
13. [Internationalisation](#internationalisation)
14. [Logging](#logging)
15. [Security model](#security-model)
16. [Coding conventions](#coding-conventions)
17. [Extending the system](#extending-the-system)
18. [Database schema](#database-schema)

---

## Runtime stack

| Component        | Required version / spec                                                                                            |
| ---------------- | ------------------------------------------------------------------------------------------------------------------ |
| PHP              | **8.1.12** (declared in every file's docblock; uses `match`, typed properties, `??=`, `enum`-style class constants) |
| Web server       | Any SAPI that exposes `site/` (Apache 2.4 / Nginx + PHP-FPM)                                                       |
| Database         | MySQL 5.x or MariaDB 10.x with `utf8` charset                                                                      |
| PHP extensions   | `mysqli`, `mbstring`, `gettext`, `zip` (SEPA ZIP statements), `openssl` (TLS to RouterOS API)                      |
| PEAR packages    | `Mail`, `Mail_Mime`, `Net_POP3`, `Net_SMTP`, `Net_IPv4`                                                            |
| Build tools      | `make` + GNU `gettext` (`xgettext`, `msginit`, `msgmerge`, `msgfmt`) for translation maintenance                   |
| External devices | Either a Linux host reachable via SSH (with `iptables`) or a MikroTik router with the API service enabled          |

---

## Repository layout

```
netprovider-netprovider/
├── CHANGELOG.md                  Czech changelog, oldest -> newest
├── CLAUDE.md                     Repo guidance for AI tooling
├── LICENSE.md                    LGPL 2.1 (file kept as a placeholder)
├── Makefile                      Translation pipeline only
├── README.md                     Project front-matter
├── sql/                          Canonical schema.sql + seed.sql (see sql/README.md)
├── db/                           Phinx migrations (db/migrations) & seeders (db/seeds)
├── config/
│   └── netprovider.ini           Single source of runtime config
├── debug/                        Ad-hoc debug scripts (developer-only)
├── includes/
│   ├── Core.php                  Configuration loader + locale bootstrap
│   ├── Database.php              Mysqli wrapper, the only data-access primitive
│   ├── Mainframe.php             Per-request dispatcher
│   ├── AppContext.php            Cross-redirect flash storage
│   ├── EmailBankAccountList.php  POP3 fetcher + statement persistence
│   ├── billing/
│   │   ├── ChargesUtil.php       Charge creation + processing engine
│   │   ├── AccountEntryUtil.php  Bank row -> customer balance matcher
│   │   └── bankParser/           One subfolder per supported statement format
│   ├── dao/                      Static DAOs (one per table)
│   ├── tables/                   Plain data objects (one per table)
│   ├── event/                    Event bus and event types
│   ├── net/
│   │   ├── CommanderCrossbar.php Platform selector
│   │   ├── commander/            Linux + RouterOS implementations
│   │   ├── email/                EmailUtil, MimeDecode
│   │   ├── routeros_api.class.php Pure-PHP RouterOS API client
│   │   └── SSH2.php              SSH wrapper used by LinuxCommander
│   ├── html/                     Shared HTML helpers
│   ├── PdfParser/                Bundled PDF text extractor (for RB PDF)
│   ├── smsgateapi_sluzba_cz/     Vendor SMS gateway client
│   ├── tables/                   See above
│   └── utils/                    Date, number, diacritics, misc helpers
├── modules/                      One folder per web module (com_*)
│   └── com_<feature>/
│       ├── <feature>.index.php   Controller (request handling)
│       └── <feature>.html.php    View (markup)
├── services/
│   └── service.php               CLI cron entry point
├── site/
│   ├── index.php                 Login form
│   ├── index2.php                Authenticated dispatcher
│   ├── logout.php
│   ├── download.php              Authorised file download
│   └── css/, js/, images/, img/  Static assets
├── templates/
│   └── events/                   Email body templates with |PLACEHOLDER| tokens
└── translation/
    ├── messages.pot              Aggregate template
    ├── en_US.UTF-8/LC_MESSAGES/  English catalog
    └── cs_CZ.UTF-8/LC_MESSAGES/  Czech catalog
```

---

## Bootstrapping and configuration

### `Core` (`includes/Core.php`)

`Core::__construct()` is called at the top of both web entry points and the CLI script. It:

1. Computes `$_appRoot = realpath(dirname(__FILE__) . '/../') . '/'` so that all later requires use absolute paths.
2. Forces `mb_*` encoding to UTF-8.
3. Parses `config/netprovider.ini` with `parse_ini_file($path, false)` (no sections; flat property map).
4. Reads `UI.Locale`, exports `LC_ALL`, `LANG`, `LANGUAGE`, calls `setlocale(LC_ALL, $locale)`.
5. Binds the `messages` text-domain to `translation/`.

All later config access goes through `Core::getProperty($name)` and `Core::getBooleanProperty($name)`. Properties that are missing throw a `PropertyException`. The class also exposes static helpers `Core::redirect()`, `Core::alert()`, `Core::backWithAlert()` that emit small JavaScript snippets — all redirects in the codebase go through them.

### `netprovider.ini` reference

| Section          | Property                              | Type     | Used by                                             |
| ---------------- | ------------------------------------- | -------- | --------------------------------------------------- |
| `Database`       | `Database Host`                       | string   | `Database::__construct`                             |
|                  | `Database Name`                       | string   | `Database::__construct`                             |
|                  | `Database Username`                   | string   | `Database::__construct`                             |
|                  | `Database Password`                   | string   | `Database::__construct`                             |
| `Financial`      | `Blank charges advance count`         | int      | `ChargesUtil::__construct`                           |
|                  | `Enable VAT Payer specifics`          | bool     | UI/templates                                         |
|                  | `Allow firm registration`             | bool     | `com_person` validation                              |
| `SMTP`           | `SMTP Server`, `SMTP Port`, `SMTP Auth`, `SMTP Username`, `SMTP Password`, `SMTP From` | mixed    | `EmailUtil`                                          |
|                  | `SMTP Supervisor EMail`               | string   | Critical-error notifications                         |
|                  | `SMTP Send EMail on critical error`   | bool     | `service.php`, `index2.php`, `EmailBankAccountList`  |
| `UI`             | `Title`, `Vendor`                     | string   | `html_header.php`                                    |
|                  | `Locale`                              | string   | `Core::__construct`                                  |
| `SMS`            | `SMS Username`, `SMS Password`        | string   | `includes/smsgateapi_sluzba_cz/`                     |
| `Network Device` | `Network Device Platform`             | enum (`LINUX`, `ROUTEROS`) | `CommanderCrossbar::__construct`           |
|                  | `Network Device Host` / `Port` / `Login` / `Password` | string | Selected commander                                   |
|                  | `Network Device WAN interface`        | string   | `LinuxCommander`                                     |
|                  | `Network Device Command sudo`         | path     | `LinuxCommander`                                     |
|                  | `Network Device Command iptables`     | path     | `LinuxCommander`                                     |
|                  | `Network Device IP accounting`        | bool     | `LinuxCommander` (constant referenced)               |
|                  | `Network Device IP filter`            | bool     | `LinuxCommander`                                     |
| `System`         | `Debug`                               | bool     | `index2.php` exception rendering, `html_debug.php`   |

The `[Network Device]` block has two keys (`IP accounting`, `IP filter`) that are referenced by class constants in `Core` (`NETWORK_DEVICE_IP_ACCOUNTING`, `NETWORK_DEVICE_IP_FILTER`) but are not present in the shipped sample `netprovider.ini` — add them when running the Linux commander.

---

## Web request lifecycle

```
                ┌────────────────────┐
  HTTP GET /    │   site/index.php   │  → renders login form (modules/com_common/login.php)
                └─────────┬──────────┘
                          │ POST submit
                          ▼
                  PersonDAO::getPersonWithGroupByUsername
                          │   (md5(pass) == PE_password ?)
                          ▼
                Insert Session row, populate $_SESSION
                          │
                          ▼
                Redirect 302 → site/index2.php
                          │
                          ▼
   ┌──────────────────────────────────────────────────────────────┐
   │                       site/index2.php                         │
   │ 1. new Core() + Database()                                    │
   │ 2. session_start, validate against `session` table:           │
   │       md5("$user$acl$time") == SE_sessionid                   │
   │       SessionDAO::checkSession()                              │
   │       SessionDAO::removeTimeoutedSession(1800)                │
   │ 3. SessionDAO::updateSessionTimeout()                          │
   │ 4. EventCrossBar()  ← loaded once per request                 │
   │ 5. Pull AppContext + flash messages from $_SESSION            │
   │ 6. Persist UI filter/limit/limitstart from $_POST             │
   │ 7. Force USER-level groups onto com_myprofile                 │
   │ 8. require html_start, html_header, html_mainmenu             │
   │ 9. MainFrame::getPath() → modules/com_<x>/<x>.index.php       │
   │10. require module index → it switches on $_REQUEST['task']    │
   │11. Catch every Exception → log, supervisor email, alert       │
   │12. require html_footer (+ html_debug if Debug=true) + html_end│
   └──────────────────────────────────────────────────────────────┘
```

`MainFrame::getPath()` falls back to `com_admin` when an unknown module is requested. The constant `VALID_MODULE` is defined inside that method; every module file starts with `defined('VALID_MODULE') or die(...)` to prevent direct access.

UI state is persisted **per user** by serializing `$_SESSION['UI_SETTINGS']` into `Person.PE_uistate` on every request (see `index2.php` lines 85-112). On the next login the state is unserialized with `['allowed_classes' => false]`.

---

## CLI service lifecycle

`services/service.php` is a long-running CLI entry. It opens syslog (`LOG_DAEMON` facility, tag `NetProvider`), parses `$argv` flags into a `Service` object and dispatches:

| Flag                      | Method                | Effect                                                                    |
| ------------------------- | --------------------- | ------------------------------------------------------------------------- |
| `--proceed-payments`      | `payments()`          | POP3 fetch → parse statements → match to customers → run charges          |
| `--proceed-networking`    | `proceedNetworking()` | Alias for `ipFilterUp()` — push current state to the network device       |
| `--ip-filter-down`        | `ipFilterDown()`      | Drop all customer IP rules                                                |
| `--ip-filter-up`          | `ipFilterUp()`        | (Re)apply customer IP rules                                               |
| `--ip-account`            | `ipAccount()`         | Sample per-IP byte/packet counters                                        |
| `--clean-up`              | `cleanUp()`           | Compact `ipaccount` rows older than 1–2 months                            |

Any uncaught exception is logged at `LEVEL_ERROR`, optionally emailed to the supervisor (`SMTP Send EMail on critical error`), and the process exits non-zero.

There is no built-in scheduling — the operator is expected to run `services/service.php` from cron with the appropriate flags.

---

## Data layer

### `Database` (`includes/Database.php`)

Thin OO wrapper around `mysqli`. Single connection per request; instantiated in each entry point and stored in `$database` (a global). Public surface:

```php
new Database($host, $user, $pass, $db);    // throws on failure; sets utf8
$db->setQuery($sql);                        // remember a query string
$db->query($sql = null);                    // run; throws on error
$db->query_batch(array $sqlArray);          // wrapped in START TRANSACTION/COMMIT
$db->startTransaction(); $db->commit(); $db->rollback();
$db->loadObject(&$obj);                     // first row -> $obj (existing properties only)
$db->loadObjectList($keyField = '');        // list of stdClass / typed objects
$db->insertObject($table, &$obj, $pkField); // plain INSERT, then reads mysqli insert_id into $obj->$pkField
$db->updateObject($table, &$obj, $pkField, $updateNulls = true);
$db->log($text, $level = 0);                // INSERT into `log` table
$db->quote($text);                          // single-quoted, escaped
$db->getQuery(); $db->explain();            // diagnostics for the debug panel
```

Key behaviours to be aware of:

- `insertObject` / `updateObject` skip properties whose name starts with `_` and skip arrays/objects. They use `get_object_vars` reflection — every column you want persisted must be a public property on the table class.
- `loadObject` requires the target object's properties to match column names exactly (no aliasing).
- `query()` throws `Exception("Error no: …  SQL:…")` on failure — callers wrap in `try/catch`.
- The class is **not** parameterised; SQL is concatenated. All values must flow through `quote()` (DAOs already do this — see below).

### DAOs (`includes/dao/*DAO.php`)

One static class per table. Convention:

```php
class XxxDAO {
    public static function getXxxArray(...): array;          // list
    public static function getXxxByID(int $id): Xxx;         // single
    public static function getXxx<Filter>(...): array;       // filtered lists
    public static function removeXxx<Filter>(...): void;     // delete
}
```

DAOs build their SQL strings, call `$database->setQuery()` then `loadObjectList()` / `loadObject()`. They are intentionally trivial and contain no business logic.

### Table objects (`includes/tables/*.php`)

POPOs whose property names match table columns prefixed with the two/three-letter table abbreviation:

| Class               | Prefix | Table              |
| ------------------- | :----: | ------------------ |
| `Person`            |  `PE`  | `person`           |
| `PersonAccount`     |  `PA`  | `personaccount`    |
| `PersonAccountEntry`|  `PN`  | `personaccountentry` |
| `Group`             |  `GR`  | `group`            |
| `Role`              |  `RO`  | `role`             |
| `Rolemember`        |  `RM`  | `rolemember`       |
| `Charge`            |  `CH`  | `charge`           |
| `HasCharge`         |  `HC`  | `hascharge`        |
| `ChargeEntry`       |  `CE`  | `chargeentry`      |
| `BankAccount`       |  `BA`  | `bankaccount`      |
| `BankAccountEntry`  |  `BE`  | `bankaccountentry` |
| `EmailList`         |  `EL`  | `emaillist`        |
| `Internet`          |  `IN`  | `internet`         |
| `Network`           |  `NE`  | `network`          |
| `Ip`                |  `IP`  | `ip`               |
| `IpAccount`         |  `IA`  | `ipaccount`        |
| `IpAccountAbs`      |  `IB`  | `ipaccountabs`     |
| `Log`               |  `LO`  | `log`              |
| `Message`           |  `ME`  | `message`          |
| `MessageAttachment` |  `MA`  | `messageattachment`|
| `HandleEvent`       |  `HE`  | `handleevent`      |
| `Session`           |  `SE`  | `session`          |

Each table class typically defines `STATUS_*`, `TYPE_*` etc. as `public const` and a `getLocalized<Status>($code)` static helper that uses gettext (`_(...)`).

---

## Domain model

```
            ┌──────────┐ 1   1 ┌────────────────┐
            │  Group   │◄──────┤     Person     │
            └──────────┘       └─────┬──────────┘
                                     │1
                                     │1
                                ┌────▼─────────────┐
                                │  PersonAccount   │
                                └──┬───────────────┘
                                   │1
                                   │*
                       ┌───────────▼─────────────┐
                       │  PersonAccountEntry      │
                       └─────────────┬────────────┘
                                     │* (BE_personaccountentryid)
                       ┌─────────────▼────────────┐
                       │   BankAccountEntry       │
                       └────────────┬─────────────┘
                                    │*
                                    │1
                              ┌─────▼──────┐
                              │ BankAccount│
                              └─────┬──────┘
                                    │1   *
                              ┌─────▼──────┐
                              │  EmailList │  (parsed statement file)
                              └────────────┘

         ┌──────────┐ *   1 ┌──────────────┐ 1   * ┌────────────┐
         │ Internet │◄──────┤    Charge    │◄──────┤  HasCharge │──┐
         └──────────┘       └──────────────┘        └─────┬──────┘  │
                                                          │ *       │ *
                                                  ┌───────▼──────┐  │
                                                  │ ChargeEntry  │  │
                                                  └──────────────┘  │
                                                                    │ HC_personid
                                                                    ▼
                                                                ┌────────┐
                                                                │ Person │
                                                                └────────┘

                ┌─────────┐ 1   * ┌──────┐
                │ Network │◄──────┤  Ip  │── 1 → Person
                └─────────┘       └──┬───┘
                                     │ *
                                ┌────▼──────────┐ ─── compaction ───► IpAccountAbs
                                │  IpAccount    │
                                └───────────────┘
```

Status enums are integers, not enum strings:

| Class & enum                         | Values                                                                                              |
| ------------------------------------ | --------------------------------------------------------------------------------------------------- |
| `Person::STATUS_*`                   | `PASSIVE = 0`, `ACTIVE = 1`, `DISCARTED = 9`                                                        |
| `Group::USER / ADMINISTRATOR / SUPER_ADMINISTRATOR` | `0 / 5 / 9`                                                                          |
| `HasCharge::STATUS_*`                | `DISABLED = 0`, `ENABLED = 1`, `FORCE_DISABLED = 2`, `FORCE_ENABLED = 3`                            |
| `HasCharge::ACTUALSTATE_*`           | `DISABLED = 0`, `ENABLED = 1`                                                                       |
| `ChargeEntry::STATUS_*`              | `FINISHED = 1`, `PENDING = 2`, `PENDING_INSUFFICIENTFUNDS = 3`, `TESTINGFREEOFCHARGE = 4`, `DISABLED = 5`, `ERROR = 6` |
| `Charge::PERIOD_MONTHLY`             | `3` (only period currently implemented in `ChargesUtil`)                                            |
| `Charge::TYPE_*`                     | `UNSPECIFIED`, `INTERNET_PAYMENT`, `ENTRY_FEE`, `PENALTY` (1..4)                                    |
| `BankAccount::DATASOURCE_*`          | `MANUAL = 1`, `EMAIL_CONTENT = 2`                                                                   |
| `BankAccount::DATASOURCE_TYPE_*`     | `RB_ATTACHMENT_TXT = 1`, `RB_ATTACHMENT_PDF = 4`, `ISO_SEPA_XML = 5`                                |
| `BankAccountEntry::STATUS_*`         | `PENDING = 0`, `PROCESSED = 1`                                                                      |
| `BankAccountEntry::IDENTIFY_*`       | `UNIDENTIFIED = 0`, `PERSONACCOUNT = 1`, `INTERNALTRANSACTION = 2`, `IGNORE = 3`                    |
| `BankAccountEntry::TYPE_*`           | 31 codes covering every Czech banking transaction type — see the source for the full list          |
| `PersonAccountEntry::SOURCE_*`       | `BANKACCOUNT = 1`, `CASH = 2`, `DISCOUNT = 3`                                                        |
| `HandleEvent::TYPE_*`                | `CHARGE_PAYMENT_DEADLINE = 1` (the only type currently implemented)                                 |
| `Log::LEVEL_*`                       | `UNSPECIFIED = 0`, `LOG = 1`, `DEBUG = 2`, `INFO = 3`, `WARNING = 4`, `ERROR = 5`, `CRITICAL = 6`, `SECURITY = 7` |

---

## Billing engine

### Class: `ChargesUtil` (`includes/billing/ChargesUtil.php`)

Two public entry points:

#### `createBlankChargeEntries()`

```
For every Person with PA_* set:
  For every HasCharge of that Person:
    If status not in {ENABLED, FORCE_ENABLED, FORCE_DISABLED}: skip
    Look up the Charge template (ChargeDAO::getChargeArray() cached on construction)
    Validate dates (datestart day must be 1; dateend before datestart -> log+skip)
    Compute mEndDate = min(dateend, now + 'Blank charges advance count' months)
    For each month between datestart and mEndDate:
      If no ChargeEntry exists for that period_date:
        INSERT chargeentry { CE_amount, CE_baseamount, CE_vat, CE_currency,
                             CE_writeoffoffset, CE_period_date, CE_realize_date=NULL,
                             CE_overdue=0, CE_status=PENDING }
    Remove ChargeEntries that fall outside [datestart, dateend]:
      If a removed entry was already FINISHED, refund its amount to PersonAccount
```

The default for `Blank charges advance count` is **6** months — kept high so monthly invoices can be generated months ahead.

#### `proceedCharges($fireDeadlineEvents = false)`

```
For every Person:
  If STATUS != ACTIVE → forcibly DISABLE every HasCharge.actualstate, continue
  Load PersonAccount
  For every HasCharge:
    If HC_status == DISABLED → set actualstate=DISABLED, continue
    If now < HC_datestart   → set actualstate=DISABLED, continue
    Determine if charge is in the present period (PERIOD_MONTHLY: dateend+1 month)
    Sort ChargeEntries by date and walk them:
       writeOffDate = period_date + writeoffoffset days
       toleranceDate = period_date + Charge.tolerance days
       If now ≥ writeOffDate AND status in {PENDING, PENDING_INSUFFICIENTFUNDS}:
         If balance >= amount:
           balance -= amount; outcome += amount
           realize_date = now; status = FINISHED
         Else:
           status = PENDING_INSUFFICIENTFUNDS; overdue = days(now - writeOffDate)
           If $fireDeadlineEvents: dispatch ChargePaymentDeadlineEvent
       Update sequencePayed / actualEntryToBeEnabled bits based on status and
       whether the entry's period is currently active.
    Decide HasCharge.actualstate from:
       FORCE_ENABLED / FORCE_DISABLED → that state
       no entries → DISABLED
       ENABLED + sequencePayed + actualEntryToBeEnabled → ENABLED
       otherwise → DISABLED
    Persist when changed.
```

The activation logic is conservative on purpose: a customer is only ENABLED when **every** prior period is paid (the "sequence is clean") **and** the current period is paid or in-tolerance. Outliers are absorbed by `Charge.CH_tolerance` in days.

### Refund and freebie handling

- A charge entry that gets removed from outside its window is **refunded** to the customer's balance (`removeChangeEntriesOutOfScope`).
- Operators can mark a single entry as `STATUS_TESTINGFREEOFCHARGE` to keep the service active without deduction (handled in `proceedCharges`).
- `STATUS_DISABLED` on an entry excludes it from billing; the system still considers the sequence clean.

---

## Bank-statement ingestion

### `EmailBankAccountList` (`includes/EmailBankAccountList.php`)

Responsible for moving statement files from a POP3 mailbox into the `emaillist` table.

1. Constructor pulls the existing `EL_name` list from the database; throws if duplicates exist (a duplicate filename is a database-integrity bug, not a normal case).
2. `downloadNewAccountLists()` opens POP3 (port 110, plain — TLS is not currently used), iterates messages, decodes each with `Mail_mimeDecode`, filters by `BA_emailsender` / `BA_emailsubject` substrings, and for each attachment:
   - matches the filename against one of three regexes (TXT, PDF, SEPA-XML);
   - extracts year + statement number from the filename;
   - validates account number + currency against `BA_accountnumber` / `BA_currency`;
   - calls `BankParserFactory::parse()` to extract individual entries;
   - persists `EmailList` row (status `PENDING`).
3. After the per-message loop, validates that statement numbers form a continuous sequence per year. Gaps are logged at `LEVEL_ERROR` and emailed to the supervisor.

`importBankAccountEntries()` is a separate pass that walks `EL_status = PENDING|ERROR` lists, parses them again, and writes the individual `bankaccountentry` rows in a single transaction.

### Parsers (`includes/billing/bankParser/`)

| Folder              | Format                                                                |
| ------------------- | --------------------------------------------------------------------- |
| `RBTXTParser/`      | Legacy Raiffeisenbank fixed-width TXT statement                       |
| `RBPDFParser/`      | Raiffeisenbank PDF statement (uses bundled `includes/PdfParser/`)     |
| `IsoSepaXmlParser/` | ISO 20022 / SEPA `Vypis_*.XML` (and `.XML.ZIP`) — the modern format   |

`BankParserFactory::__construct($datasourcetype, $blob)` selects the implementation via `BankAccount::DATASOURCE_TYPE_*` and exposes:

```php
$factory->parse();              // throws on malformed input
$doc = $factory->getDocument(); // ['ACCOUNT_NUMBER', 'BANK_NUMBER', 'CURRENCY',
                                 //  'LIST_NO', 'LIST_DATE_FROM', 'LIST_DATE_TO',
                                 //  'LIST' => BankAccountEntry[]]
```

### Matching (`AccountEntryUtil`)

Runs after import. For each `BankAccountEntry` with `BE_status = PENDING`:

- Internal-bank moves (`TYPE_DIFFENTTRANSACTIONCHARGE`, `TYPE_POSITIVEINCREASE`) are flagged `IDENTIFY_INTERNALTRANSACTION` and finalized.
- An empty variable symbol is left alone (so the operator can decide manually).
- `PersonDAO::getPersonWithAccountArrayForAccounting($vs, $cs, $ss)` searches by symbols.
- Multiple matches → email supervisor; row stays pending.
- Zero matches but the amount equals a known `CH_amount` → email supervisor with the most likely service.
- Single inactive customer match → email supervisor; row stays pending.
- Single active match → credit the customer, link `BE_personaccountentryid → PN_personaccountentryid`, mark `STATUS_PROCESSED`. All in one transaction (`startTransaction` / `commit` / `rollback`).

---

## Network commanders

### `CommanderCrossbar`

`__construct()` builds an in-memory map:

```
networks[networkid] = Network {
   …,
   INTERNET_SERVICES = {
       haschargeid => {
           PE_firstname, PE_surname,
           IN_dnl_rate, IN_dnl_ceil, IN_upl_rate, IN_upl_ceil, IN_prio,
           IN_description,
           IPS = [{IP_address, IP_dns}, …]
       }
   }
}
```

It looks at every active `Person`, gets their **internet-only** `HasCharge`s with `getHasChargeWithInternetChargeOnlyByPersonID`, then their `IP`s. Note the implicit assumption: **a customer has at most one active internet HasCharge** — the code uses `reset($hasCharges)` and groups every IP under that single charge. Adding a second active internet charge per customer requires changing this loop.

The constructor then instantiates `LinuxCommander` or `RouterOSCommander` based on `Network Device Platform`.

Public surface:

| Method                | Result type      | What it does                                               |
| --------------------- | ---------------- | ---------------------------------------------------------- |
| `synchronizeFilter()` | `array`          | Diff and reconcile rules (used by accounting path)         |
| `ipFilterUp()`        | `array` (echoed) | Apply rules so paid customers are accepted                 |
| `ipFilterDown()`      | `array` (echoed) | Tear down all rules                                        |
| `accountIP()`         | `void`           | Read counters, write `ipaccount` rows (ROUTEROS only — see below) |

Only accept/reject IP filtering is implemented. `CommanderCrossbar` gathers per-customer rate/ceiling values (`IN_dnl_*` / `IN_upl_*`) into an in-memory map, but **neither commander consumes them** — there is no `tc`, HTB, or `/queue` traffic shaping anywhere in `includes/net/`.

### `LinuxCommander`

- Connects via SSH (`includes/net/SSH2.php`, which uses the `phpseclib`/`ssh2` extension idioms).
- Uses two iptables chains: `FILTER-IN` (downstream) and `FILTER-OUT` (upstream).
- For each customer IP, emits an ACCEPT rule via `iptables`. The rate/ceiling values are ignored — no `tc` class/filter or HTB shaping is emitted.
- Requires the configured user to run `Network Device Command sudo Network Device Command iptables` without password.
- `accountIP()` is commented out on this platform, so `--ip-account` on `LINUX` calls an undefined method and fatals. IP accounting works only on `ROUTEROS`.

### `RouterOSCommander`

- Pure-PHP `RouterosApi` client with TLS (`ssl = true`) on port 8729.
- Uses two firewall chains `FILTER-IN` / `FILTER-OUT` (constants `RouterOSCommander::FILTER_IN` / `FILTER_OUT`) under `/ip/firewall/filter` — accept/reject rules only, no queue tree.
- Reads counters with `/ip/firewall/filter/print ?=chain=FILTER-IN ?=action=accept =stats=` (this is what backs `accountIP()`).
- Writes rules in batches via `routerosApi->write()` / `read()`.
- Login throws `Exception` with a localized message on failure; missing config keys also throw with explicit messages.

---

## Event system

`includes/event/` defines:

- `Event` — abstract base.
- `ChargePaymentDeadlineEvent` — fired by `ChargesUtil::proceedCharges` for late entries; carries `Person`, `Charge`, `HasCharge`, `ChargeEntry`, period date, write-off date, tolerance date.
- `PaymentReceivedEvent` — defined but the corresponding `HandleEvent::TYPE_PAYMENT_RECEIVED` is currently commented out.
- `EventCrossBar` — the singleton bus.

`EventCrossBar::__construct()`:

1. Loads every `HandleEvent` row.
2. Pre-loads each handler's template from `templates/events/<HE_templatepath>`.
3. Throws if a template file is missing.

`dispatchEvent($event)`:

1. Picks handlers whose `HE_type` matches the event class **and** whose `HE_status = ENABLED`.
2. If `HE_notifydaysbeforeturnoff` is set, fires only when the customer is within that window of being switched off (computed from `toleranceDate - now` in days).
3. Runs `mb_ereg_replace` for each `|TOKEN|` in the template (full token list in [User Guide → Sending notifications](USER_GUIDE.md#sending-notifications)).
4. Routes the message via `EmailUtil::queueMessage()` and flushes with `sendMessages()`. Failures are logged at `LEVEL_ERROR`.

If `HE_notifypersonid` is set, the destination is the configured back-office address — useful for "tell the operator" rules. Otherwise the customer themselves.

Adding a new event type:

1. Subclass `Event` with the fields the handler needs.
2. Add a constant to `HandleEvent::TYPE_*` and update `getLocalizedType()`.
3. Add a branch in `EventCrossBar::dispatchEvent()` that handles `instanceof YourEvent`.
4. Drop a body template into `templates/events/`.
5. Insert a `handleevent` row through the **Event handlers** module.

---

## Modules (web UI)

### Conventions

Each module lives in `modules/com_<feature>/` and consists of two files:

- `<feature>.index.php` — controller. Reads `$_REQUEST['task']`, dispatches via `switch`, calls DAOs and the appropriate `HTML_<feature>::method()` view function.
- `<feature>.html.php` — view. Defines a class `HTML_<feature>` whose static methods render templates (header, list, edit form, etc.).

Every controller starts with:

```php
defined('VALID_MODULE') or die(_("Direct access into this section is not allowed"));
```

The constant is set inside `MainFrame::getPath()` so the file cannot be hit directly from the web.

### Standard task names

Across modules, these task strings are conventional:

| Task           | Meaning                                                    |
| -------------- | ---------------------------------------------------------- |
| (empty)        | List view                                                  |
| `new`          | Render empty form                                          |
| `edit`         | Render form for an existing record                         |
| `editA`        | Apply (save) without leaving the editor                    |
| `apply`        | Save changes and stay on the form                          |
| `save`         | Save changes and return to the list                        |
| `cancel`       | Discard changes and return to the list                     |
| `remove`       | Delete the selected record(s)                              |

Domain-specific tasks (a few examples):

| Module                | Domain tasks                                                                                       |
| --------------------- | -------------------------------------------------------------------------------------------------- |
| `com_person`          | `addRole`, `removeRole`, `newHasCharge`, `editHasCharge`, `removeHasCharge`, `saveHasCharge`, `applyHasCharge`, `cancelHasCharge` |
| `com_charge`          | (only the standard set)                                                                            |
| `com_personaccount`   | `createBlankCharges`, `proceedCharges`, `returnPayment`, `freeCharge`, `ignoreCharge`, `removeCharge`, `newPAE`, `savePAE`, `showDetail` |
| `com_bankaccount`     | `editBA`, `saveBA`, `applyBA`, `showBankList`, `uploadBankLists`, `downloadBankLists`, `processBankLists`, `processEntries`, `editBAE`, `saveBAE` |

### Navigation

Top-level menu items map to `?option=com_<feature>` and the dispatcher resolves them through `MainFrame::getPath()`. See [User Guide → Main menu reference](USER_GUIDE.md#main-menu-reference) for the full list.

### Persisting list state

`index2.php` stores `filter`, `limit`, `limitstart` (and a secondary `limit2` / `limitstart2` for the bank-statement double-list page) into `$_SESSION['UI_SETTINGS'][$option]`. After every request the entire `UI_SETTINGS` is `serialize()`d into `Person.PE_uistate`. On next login the value is `unserialize()`d with `['allowed_classes' => false]` to defuse object-injection attacks.

---

## Internationalisation

- All user-facing strings are wrapped in `_("...")` (or the equivalent `gettext()` call).
- `Core::__construct` sets up the text-domain `messages` rooted at `<APP_ROOT>/translation`.
- The shipped catalogs are `cs_CZ.UTF-8` (Czech) and `en_US.UTF-8` (English).
- The build target `make locales` extracts `messages.pot`, merges into each catalog and compiles `.mo`. It refuses to run if the gettext binaries are missing.
- Default locale comes from `[UI] Locale`. Switching the system to a new language is a matter of:
  1. Adding the locale to `APPLICATION_LOCALES` in `Makefile`.
  2. `make locales`.
  3. Translating the new `.po` file.
  4. Setting `Locale = ...` in `netprovider.ini`.

Some legacy strings inside `EmailBankAccountList`, `AccountEntryUtil` and the `templates/events/` files are hard-coded Czech and **not** wrapped in `_()`. Translating those means editing the source.

---

## Logging

Two destinations:

- **Database** — `Database::log($text, $level)` inserts a `log` row with the current `SE_personid`, the text and the level. Browsable through **Administration → Log**.
- **Syslog** (CLI service only) — `openlog("NetProvider", LOG_PERROR, LOG_DAEMON)` then `syslog($priority, $msg)`. Levels are mapped manually (`LOG_INFO`, `LOG_ERR`).

Log levels (defined in `includes/tables/Log.php`):

```
0 UNSPECIFIED   1 LOG     2 DEBUG    3 INFO
4 WARNING       5 ERROR   6 CRITICAL 7 SECURITY
```

Critical errors additionally trigger an email to `SMTP Supervisor EMail` if `SMTP Send EMail on critical error = true`.

---

## Security model

### Authentication

- Login is username + password. Passwords are stored as `md5()` hashes — **this is a known weakness** and is unsuitable for any new deployment without a migration to a modern hash. A password rotation should be planned before production use.
- The web session ID is `md5("$username$acl$logintime")` (see `site/index.php` line 66) — also weak; prefer `random_bytes(32)` if rewriting.
- The session record is bound to `SE_ip = REMOTE_ADDR`. A reverse proxy must forward the real client IP for the bind to be meaningful.

### Authorisation

- Group level (`GR_level`) gates the menu and forces `USER`-level accounts onto `com_myprofile`.
- Roles (`role` / `rolemember`) are queried inside individual modules where finer-grained checks are needed.
- Every module entry point must guard against direct hit with `defined('VALID_MODULE') or die(...)`.

### Database access

- One MySQL user, full DDL+DML on the `netprovider` database (per the shipped schema). The credentials in `netprovider.ini` should never be the root user.
- All SQL is concatenated; injection is avoided by going through `Database::quote()` consistently in DAOs.

### Network device credentials

- `Network Device Login` / `Network Device Password` are stored in plain text in `netprovider.ini`. Restrict file permissions to the web/CLI user (`chmod 640`, owner = web user, group = ops).
- The RouterOS connection uses TLS (port 8729). The Linux SSH connection uses password auth — switch to keys if possible.

### Input validation

- Limits on form fields are enforced in the views; the controllers re-bind via `Database::bindArrayToObject` (only existing properties accepted) before persistence.
- File uploads (manual statement upload) are content-validated by the parser before storage.

### Audit and forensics

- The `log` table records every administrative action (create / update / delete / login attempt). Filter by `LO_level = LEVEL_SECURITY` to find authentication failures and `level = LEVEL_CRITICAL` for unhandled exceptions.

### Hardening checklist

When deploying:

- Set `Debug = false`.
- Lock down `config/netprovider.ini` permissions.
- Put the application behind HTTPS — the login form posts the password in clear.
- Disable PHP `display_errors` at the SAPI level.
- Move `services/service.php` invocation to a dedicated cron user with read access to the codebase and write access to the syslog.
- Migrate password storage to `password_hash()`/`password_verify()` (out of scope for this docs pass).

---

## Coding conventions

- **PHP version:** 8.1.12. Files use `match`, typed properties, named constants, `string|null` parameters.
- **Style:** PSR-1-ish. Class names PascalCase, methods camelCase, constants ALL_CAPS, properties prefixed with table abbreviation (`PE_`, `CH_`, etc.). Internal Database properties use a leading `_`.
- **Globals:** `$core`, `$database`, `$mainframe`, `$eventCrossBar`, `$my`, `$appContext` are used as globals across modules. Acknowledge them with `global` at the top of any function that needs them.
- **DAO/table split:** Tables hold no logic; DAOs hold only static methods that talk to `$database`. Business logic goes into `includes/billing/`, `includes/net/` or the module controllers.
- **Transactions:** Multi-row writes are wrapped in `startTransaction` / `commit` / `rollback`. Failures must be logged via `$database->log(...)` and surfaced through `$this->_messages[]` where applicable.
- **Errors:** Throw `Exception` subclasses. The dispatcher catches them, logs at `LEVEL_ERROR`, optionally emails the supervisor and either dumps the trace (if `Debug = true`) or returns the user to the previous page with a localized alert.
- **HTML output:** All view files require `defined('VALID_MODULE')`. Templates render directly (no templating engine). Use `Utils::getParam($_REQUEST, 'k', $default)` rather than reading `$_REQUEST` directly.

---

## Extending the system

### Adding a new module

1. `mkdir modules/com_widget`.
2. Create `widget.index.php` (controller) and `widget.html.php` (view) following the existing modules.
3. Add a menu entry to `modules/com_common/html_mainmenu.php` if the module should appear in the navigation.
4. Add the module to the localization template by running `make locales`.

### Adding a new charge period

1. Add a new constant to `Charge::PERIOD_*` and update `getLocalizedPeriod()`.
2. Extend `ChargesUtil::createOrRemoveChargeEntriesForPerson()` and `proceedChargesForPerson()` with the new branch — they currently switch only on `PERIOD_MONTHLY`.
3. Update the `chargeentry` validation: change "day must be 1" to whatever makes sense for the new period.

### Adding a new bank-statement format

1. Create `includes/billing/bankParser/MyParser/` with a `parse()` method that returns the `getDocument()` shape described above.
2. Add a `BankAccount::DATASOURCE_TYPE_*` constant.
3. Update `BankParserFactory` to dispatch the new constant.
4. Update `EmailBankAccountList::processXxx()` (or add a new one) to recognise the filename pattern and read the body.

### Adding a new event type

See [Event system → Adding a new event type](#event-system).

### Switching to a third network platform

`CommanderCrossbar::__construct()` is the single place to extend. Implement a class with the same public surface as `LinuxCommander` / `RouterOSCommander`, register it under a new `Network Device Platform` value.

---

## Database schema

The canonical schema is `sql/schema.sql` — data-free DDL derived from the final
production dump (MySQL 8.0) and normalised to `utf8mb4` / `utf8mb4_czech_ci`. It
ships **22 tables**:

```
bankaccount, bankaccountentry,
charge, chargeentry,
emaillist,
group, handleevent, hascharge,
internet, ip, ipaccount, ipaccountabs,
log, message, messageattachment,
network, person, personaccount, personaccountentry,
role, rolemember, session
```

This is the single source of truth: the quick-start, the integration tier
(default `NP_IT_SCHEMA`), and Phinx migration `001` all apply this file. It
includes `handleevent` and `messageattachment` — both required by the code
(`EventCrossBar::__construct()` reads `handleevent` on every web request) — and
drops the dead `configuration` table (no code reference). Naming is consistent
with the table classes; see [Table objects](#table-objects-includestablesphp).

Schema evolution is versioned with [Phinx](https://phinx.org): migration `001`
seeds from `sql/schema.sql`, and each subsequent change is a new forward-only
migration under `db/migrations`. To set up or regenerate, see `sql/README.md`:

```bash
mysql -u root -p -e "CREATE DATABASE netprovider DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci"
mysql -u root -p netprovider < sql/schema.sql       # or: composer db:migrate
mysql -u root -p netprovider < sql/seed.sql         # or: composer db:seed
```

The schema targets `utf8mb4_czech_ci`, and `Database::__construct` matches it by
calling `mysqli::set_charset('utf8mb4')` (throwing on failure). `set_charset` is
used rather than a raw `SET NAMES` query because it also updates the charset
mysqli uses for `escape_string()`, keeping `Database::quote()` correct.

`sql/seed.sql` creates a super-administrator (`admin`) with its group and backing
`personaccount`, but with **no password** — the account is not loginable until one
is set, so no credential is committed. `composer db:seed` generates a random
password and prints it once; a manual load requires setting the password by hand.
To seed manually from scratch, insert a `person` row with a known `MD5(password)`,
a group with `GR_level = 9`, and a `personaccount` row.

---

*For end-user / operator-facing instructions, see [USER_GUIDE.md](USER_GUIDE.md).*
*For project metadata and quick start, see [README.md](../README.md).*
