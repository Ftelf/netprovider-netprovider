# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PHP ISP billing and network management system. Manages customers (persons), subscriptions (charges), payments (bank account imports), and controls network QoS on Linux or MikroTik RouterOS devices.

## Commands

### Translations

```bash
make locales
```

Requires `xgettext`, `msginit`, `msgmerge`, `msgfmt` (gettext tools). Scans all `.php` files, updates `translation/messages.pot`, then merges and compiles `.po`/`.mo` for `en_US.UTF-8` and `cs_CZ.UTF-8`.

### Background service (CLI)

```bash
php services/service.php --proceed-payments       # import bank statements, run billing
php services/service.php --proceed-networking     # sync IP filter rules to network device
php services/service.php --ip-filter-down         # remove all IP rules
php services/service.php --ip-filter-up           # apply all IP rules
php services/service.php --ip-account             # collect traffic accounting
php services/service.php --clean-up               # compact old IP accounting data
```

## Architecture

### Entry points

- `site/index.php` — login page; authenticates via `PersonDAO`, creates `Session`, stores user in `$_SESSION['USER']`
- `site/index2.php` — main app; validates session, routes to modules, wraps output with header/footer
- `services/service.php` — cron/CLI service, no web session

### Request routing

`index2.php` reads `?option=com_xxx` and delegates to `modules/com_xxx/xxx.index.php`. `MainFrame::getPath()` resolves the path; unknown options fall back to `com_admin`. Regular users (`GR_level == Group::USER`) are always forced to `com_myprofile`.

### Core classes (`includes/`)

| File | Purpose |
|------|---------|
| `Core.php` | Reads `config/netprovider.ini`, sets locale/gettext, exposes `getProperty()` |
| `Database.php` | Thin `mysqli` wrapper: `setQuery`/`query`, `loadObject`, `loadObjectList`, `insertObject`, `updateObject`, `query_batch` |
| `Mainframe.php` | Holds `$database`, `$option`, timing; renders the message panel |
| `AppContext.php` | Stored in `$_SESSION['APP_CONTEXT']`; passes flash messages across redirects |

### Data layer pattern

- `includes/tables/*.php` — plain data objects; properties use table-column prefixes (e.g. `PE_personid`, `CH_amount`). No logic.
- `includes/dao/*DAO.php` — static methods only; use global `$database`. Convention: `getXxxArray()`, `getXxxByID()`, `removeXxx...()`.

### Billing pipeline (`includes/billing/`)

`ChargesUtil` is the core billing engine:
1. `createBlankChargeEntries()` — projects future `ChargeEntry` rows up to `BLANK_CHARGES_ADVANCE_COUNT` months ahead for every active `HasCharge`
2. `proceedCharges()` — deducts amounts from `PersonAccount.PA_balance`, marks entries FINISHED or PENDING_INSUFFICIENTFUNDS, fires `ChargePaymentDeadlineEvent` when overdue

`AccountEntryUtil` matches imported bank statement rows (`BankAccountEntry`) to persons and credits their accounts.

### Network control (`includes/net/`)

`CommanderCrossbar` builds a map of `{networkId → internet services with IPs}` then delegates to either `LinuxCommander` (iptables/tc via SSH) or `RouterOSCommander` (MikroTik API). Platform selected by `Network Device Platform` in config (`LINUX` or `ROUTEROS`).

### Event system (`includes/event/`)

`EventCrossBar` loads `HandleEvent` rows from DB on construction. `dispatchEvent()` matches event type, interpolates `|PLACEHOLDER|` tokens in templates under `templates/events/`, and sends email via `EmailUtil`.

### Module structure (`modules/`)

Each module has two files:
- `com_xxx/xxx.index.php` — business logic, reads `$_POST`/`$_GET`, calls DAOs
- `com_xxx/xxx.html.php` — HTML rendering, included by the index file

### Configuration

`config/netprovider.ini` — all runtime config (DB credentials, SMTP, SMS, network device connection). Read exclusively through `Core::getProperty()`.

### Internationalization

All user-facing strings wrapped in `_("...")`. Compiled `.mo` files live in `translation/<locale>/LC_MESSAGES/messages.mo`. Locale set from `UI.Locale` in config.

## Coding conventions

### Explicit branch per case in state/status logic

When code branches on a state or status enum (e.g. the `CE_status` accumulator in `ChargesUtil::proceedChargesForPerson`), keep **one explicit branch per case — including cases that do nothing**. Do not merge distinct statuses into a shared branch and do not delete a no-op branch in favour of a single catch-all comment. The per-case structure documents that every state was considered and lets a reader see all path variations at a glance; it should mirror the relevant decision table 1:1 (see `docs/billing.md §5.4`).

Express a "does nothing" case as a **real verdict**, not an empty body:

```php
// Good — the verdict is visible per status, flags stay bool
if ($status == FINISHED || $status == PENDING || $status == FREE) {
    $enabled = $enabled && true;                 // this status does not disable
} elseif ($status == INSUFFICIENT) {
    $enabled = $enabled && ($overdue <= $tolerance);
} elseif ($status == DISABLED) {
    $enabled = $enabled && false;                // forces off
}
// Unmatched statuses (e.g. ERROR) are intentionally neutral — flag unchanged.
```

Rules:
- Use explicit boolean logic — `$flag = $flag && <verdict>`. **Never** bitwise `&=` on logical flags (it makes them `int` and reads as intent); PHP has no `&&=`.
- A `&& true` branch is a deliberate "this case has no negative effect" verdict, kept for readability. Prefer this even where a linter would flag it as redundant.
- Leave unhandled statuses to fall through as neutral only when that is the documented intent, and say so in a comment.
