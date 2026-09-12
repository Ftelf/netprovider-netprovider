# Testing NetProvider

This document explains how the test suite is organised, how to run it, and how to extend it.

## Stack

- **PHPUnit 10** — runner and assertions.
- **Mockery 1.6** — flexible test doubles, used for MySQL `mysqli`, the inner platform commander, and `EmailUtil`.
- **php-code-coverage** — coverage reporting (requires Xdebug or PCOV in coverage mode).

The project predates Composer; tests bring their own `composer.json` and a custom bootstrap that emulates the legacy globals (`$core`, `$database`).

## One-time setup

```bash
composer install
```

That installs PHPUnit, Mockery, and friends under `vendor/`.

PHP 8.1+ with the following extensions: `mysqli`, `mbstring`, `gettext` (or the polyfill kicks in), `openssl`, `simplexml`. Coverage requires Xdebug 3 (`XDEBUG_MODE=coverage`) or PCOV.

## Running tests

```bash
# All tests
composer test

# Only the unit suite
composer test:unit

# Pretty test names
composer test:testdox

# Coverage to build/coverage/index.html (and a text summary)
composer test:coverage
```

You can also call PHPUnit directly:

```bash
vendor/bin/phpunit
vendor/bin/phpunit --filter ChargesUtilTest
vendor/bin/phpunit tests/Unit/Utils
```

## Layout

```
tests/
├── bootstrap.php                  # boots $core, polyfills gettext, registers autoloader
├── TestCase.php                   # base class — fresh DatabaseStub per test, Mockery teardown
├── Stubs/
│   ├── CoreStub.php               # in-memory Core (no INI / locale)
│   ├── DatabaseStub.php           # in-memory Database (recordedQueries, seed*, inserts/updates)
│   ├── MailStubs.php              # PEAR Mail / Mail_mime stand-ins
│   └── pear-shim/                 # files reachable via include_path: Mail.php, Mail/mime.php, Net/IPv4.php
├── Fixtures/
│   └── bank/                      # ISO-SEPA (camt.053) XML statement samples
└── Unit/
    ├── Utils/                     # DateUtil, NumberFormat, DiacriticsUtil, Utils
    ├── Core/                      # AppContext, MainFrame, CoreStub
    ├── DAO/                       # PersonDAO, ChargeDAO, IpDAO, ... + SimpleDAOsTest
    ├── Billing/                   # ChargesUtil, AccountEntryUtil, IsoSepaXmlParser, RBTXTParser, BankParserFactory
    ├── Net/                       # CommanderCrossbar, LinuxCommander, RouterOSCommander
    ├── Event/                     # EventCrossBar, Event value objects
    ├── Tables/                    # getLocalizedX() helpers across table objects
    ├── Modules/                   # ModuleStructureTest — every com_* has index + html
    └── DatabaseTest.php           # mysqli wrapper (mocked mysqli)
```

## Conventions

Every test extends the project's `TestCase`. `setUp()`:

1. installs a fresh `DatabaseStub` as global `$database`,
2. resets `$core` properties to `CoreStub::defaults()` so config tweaks don't leak between tests.

`tearDown()` calls `Mockery::close()`.

### Driving a DAO

`DatabaseStub` exposes seed helpers and an inspection surface:

```php
$this->db->seedResult(7);            // for next loadResult()
$this->db->seedObject($personRow);   // for next loadObject(&$obj)
$this->db->seedObjectList([...]);    // for next loadObjectList()

PersonDAO::getPersonByID(7);         // call the DAO

$this->db->lastQuery();              // inspect SQL
$this->db->updates;                  // inspect updateObject() captures
$this->db->inserts;                  // inspect insertObject() captures
```

### Mocking SSH / RouterOS / Mail

For commanders, the constructor connects to the remote device. Tests use
`ReflectionClass::newInstanceWithoutConstructor()` and inject a mocked
inner commander:

```php
$commander = Mockery::mock();
$commander->shouldReceive('synchronizeFilter')->once()->andReturn(['ok']);

$r = new ReflectionClass(CommanderCrossbar::class);
$cb = $r->newInstanceWithoutConstructor();
$p = $r->getProperty('commander'); $p->setAccessible(true);
$p->setValue($cb, $commander);
```

`AccountEntryUtil` and `EventCrossBar` follow the same pattern for the
`emailUtil` field. PEAR's `Mail.php` is unavailable in tests; the
`tests/Stubs/pear-shim/` directory provides minimal stand-ins so that
files which `require_once "Mail.php"` still load.

### Bank statement fixtures

Place new fixtures under `tests/Fixtures/bank/`. Read them with:

```php
$xml = file_get_contents(NP_TESTS_ROOT . 'Fixtures/bank/yourfile.xml');
```

## Coverage notes

The coverage configuration (`phpunit.xml`) excludes vendored bundles
that aren't ours to test:

- `includes/PdfParser/`
- `includes/smsgateapi_sluzba_cz/`
- `includes/net/routeros_api.class.php`
- `includes/net/SSH2.php`
- `includes/net/email/MimeDecode.php`

Run `composer test:coverage`; the HTML report lands in `build/coverage/`.

## Adding tests

1. Pick the right directory under `tests/Unit/`.
2. Extend `TestCase`.
3. Seed `$this->db` with whatever the code under test reads, in the
   exact order it issues queries.
4. Drive the unit, then assert against `$this->db->updates`,
   `$this->db->inserts`, return values, or thrown exceptions.

## What's NOT covered

- **PEAR `Net_POP3` ingestion** (`EmailBankAccountList`) — would need an
  integration test against a real mailbox.
- **Real SSH/RouterOS sessions** — covered only at the validation
  boundary (missing host / login / password). End-to-end network
  control belongs in an integration suite against test hardware.
- **PDF bank statement parsing** (`RBPDFParser`) — uses a vendored
  PDF library; no fixture is included.
- **Module entry points** — only structural (file-presence + routing).
  Full request-cycle coverage needs an HTTP smoke layer, e.g. a
  PHP-FPM container running against a seeded MySQL.

## Troubleshooting

- `Run composer install first — vendor/autoload.php missing.` — exactly that.
- `Class "Mail" not found` when EmailUtil is loaded — your
  `include_path` is missing `tests/Stubs/pear-shim`. Run via PHPUnit
  rather than calling the file directly.
- Tests pass individually but fail when run together — usually a
  global-state leak. Reset `$core` properties at the top of the
  offending test, or move the mutation to `setUp()`.
