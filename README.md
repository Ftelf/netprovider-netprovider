# NetProvider

[![PHP](https://img.shields.io/badge/PHP-8.1.12-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: LGPL-2.1](https://img.shields.io/badge/license-LGPL--2.1-blue.svg)](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)
[![i18n: en, cs](https://img.shields.io/badge/i18n-en%20%7C%20cs-brightgreen)](translation/)

> Self-hosted ISP back-office: customers, subscriptions, payments, QoS — in one PHP web app.

NetProvider is a small but complete operational system for an Internet Service Provider. It manages customer records, subscription plans, recurring charges, bank-statement-based payment matching, and pushes IP filter and traffic-shaping rules to a Linux gateway or a MikroTik RouterOS router. The UI is available in English and Czech.

---

## Table of contents

1. [Features](#features)
2. [Quick start](#quick-start)
3. [Project layout](#project-layout)
4. [Configuration](#configuration)
5. [Running the CLI service](#running-the-cli-service)
6. [Translations](#translations)
7. [Documentation](#documentation)
8. [Versioning and changelog](#versioning-and-changelog)
9. [License](#license)

---

## Features

- **Customer management** — persons, groups, roles, contact info, status (active / passive / discarded), per-user UI preferences.
- **Flexible billing** — payment templates with VAT, tolerance windows and write-off offsets; monthly subscriptions, entry fees, penalties.
- **Customer ledger** — running balance, total income / outcome, manual cash and discount entries.
- **Bank-statement ingestion** — pulls statements over POP3, parses Raiffeisenbank TXT/PDF and ISO-SEPA XML, matches incoming payments to customers by variable / constant / specific symbols.
- **Network QoS control** — rebuilds firewall + traffic-shaping rules on Linux (iptables + tc over SSH) or MikroTik (RouterOS API/TLS).
- **IP traffic accounting** — collects per-IP byte/packet counters, compacts them into long-term rollups for reports.
- **Event-driven email notifications** — payment-deadline reminders rendered from configurable templates with `|TOKEN|` substitution.
- **Audit log** — every administrative action lands in the `log` table with severity levels from `INFO` to `SECURITY`.
- **Internationalisation** — gettext-based, `en_US.UTF-8` and `cs_CZ.UTF-8` shipped.

---

## Quick start

### Requirements

- PHP **8.1.12** with `mysqli`, `mbstring`, `gettext`, `zip`, `openssl` extensions
- MySQL / MariaDB
- PEAR packages: `Mail`, `Mail_Mime`, `Net_POP3`, `Net_SMTP`, `Net_IPv4`
- GNU `gettext` (only when you maintain translations)

### Install

```bash
# 1. Get the source
git clone https://example.com/netprovider.git
cd netprovider

# 2. Create the database and load the reference schema
mysql -u root -p -e "CREATE DATABASE netprovider DEFAULT CHARACTER SET utf8 COLLATE utf8_czech_ci"
mysql -u root -p -e "CREATE USER 'netprovider'@'localhost' IDENTIFIED BY 'netprovider'"
mysql -u root -p -e "GRANT ALL ON netprovider.* TO 'netprovider'@'localhost'"
mysql -u netprovider -p netprovider < localhost.sql

# 3. Configure
cp config/netprovider.ini config/netprovider.ini.bak     # keep an unmodified copy
$EDITOR config/netprovider.ini                           # at minimum set DB / SMTP / Network Device

# 4. Point your web server at site/ and open http://<host>/
```

Create your first super-administrator account by inserting a row in `person` with `MD5(<password>)` as `PE_password` and a `groupid` whose `GR_level = 9`. After the first login, manage users from the web UI.

> **Heads up.** Passwords are hashed with `MD5` and the session ID is also a short MD5. Both are unsuitable for any internet-facing deployment without a hardening pass — see [TECHNICAL.md → Security model](docs/TECHNICAL.md#security-model).

---

## Project layout

```
config/                   netprovider.ini — single runtime config
includes/                 PHP source: Core, DB, billing, net, dao, tables, utils
modules/com_*             Web modules — controllers + views
services/service.php      CLI service (cron entry)
site/                     Web document root (index.php login, index2.php app)
templates/events/         Email body templates with |TOKEN| substitution
translation/              gettext catalogs
docs/                     User and technical documentation
localhost.sql             Reference schema dump
Makefile                  `make locales` — translation pipeline
CHANGELOG.md              Czech changelog
LICENSE.md                LGPL-2.1
```

---

## Configuration

All runtime configuration lives in `config/netprovider.ini` and is read by `Core::getProperty()`. The shipped file documents every key. The most important blocks:

```ini
[Database]
Database Host     = "127.0.0.1"
Database Name     = "netprovider"
Database Username = "netprovider"
Database Password = "netprovider"

[UI]
Title  = "Your ISP — back office"
Vendor = "Your ISP"
Locale = "en_US.UTF-8"            ; or cs_CZ.UTF-8

[Network Device]
Network Device Platform = "ROUTEROS" ; or LINUX
Network Device Host     = "192.168.88.1"
Network Device Port     = "8729"
Network Device Login    = "admin"
Network Device Password = "..."
```

For the full reference (every key, every module, every cron command) see [docs/TECHNICAL.md](docs/TECHNICAL.md#bootstrapping-and-configuration).

---

## Running the CLI service

`services/service.php` is the cron entry point. Recommended schedule:

```cron
30 2  * * *  php /var/www/netprovider/services/service.php --proceed-payments
*/5  * * * *  php /var/www/netprovider/services/service.php --proceed-networking
0    * * * *  php /var/www/netprovider/services/service.php --ip-account
30 3 * * 0  php /var/www/netprovider/services/service.php --clean-up
```

| Flag                    | What it does                                                                |
| ----------------------- | --------------------------------------------------------------------------- |
| `--proceed-payments`    | Pull statements, match payments, run billing                                |
| `--proceed-networking`  | Push current state to the QoS device                                        |
| `--ip-filter-up`        | Force every customer rule on (without billing)                              |
| `--ip-filter-down`      | Tear down every customer rule                                                |
| `--ip-account`          | Sample per-IP traffic counters into `ipaccount`                             |
| `--clean-up`            | Compact old `ipaccount` rows                                                 |

Logs go to syslog (`LOG_DAEMON`, tag `NetProvider`) and to the database `log` table. Critical errors are emailed to `SMTP Supervisor EMail` when `SMTP Send EMail on critical error = true`.

---

## Translations

```bash
make locales
```

Scans every `.php` file with `xgettext`, refreshes `translation/messages.pot`, merges and compiles `.po` and `.mo` for `en_US.UTF-8` and `cs_CZ.UTF-8`. Add new locales by extending `APPLICATION_LOCALES` in the `Makefile`.

---

## Documentation

| Document                                                          | Audience                                                             |
| ----------------------------------------------------------------- | -------------------------------------------------------------------- |
| **[docs/USER_GUIDE.md](docs/USER_GUIDE.md)**                      | Operators / administrators — workflows, menu reference, FAQ          |
| **[docs/TECHNICAL.md](docs/TECHNICAL.md)**                        | Developers / SREs — architecture, data model, billing engine, security |
| **[CHANGELOG.md](CHANGELOG.md)**                                  | Historical release notes (Czech)                                     |
| **[CLAUDE.md](CLAUDE.md)**                                        | AI tooling guidance for working in the repo                          |
| **[TESTING.md](TESTING.md)**                                      | Test suite — install, run, layout, conventions                       |

---

## Versioning and changelog

The project follows an unstructured incremental version scheme; releases are recorded chronologically in [CHANGELOG.md](CHANGELOG.md) (Czech). Recent themes have been ISO-SEPA XML statement support, MikroTik RouterOS rewrite, IP-accounting compaction and the event-driven notifier.

---

## License

NetProvider is distributed under the **GNU Lesser General Public License, version 2.1**. The full text belongs in [LICENSE.md](LICENSE.md) — currently the file is a placeholder; restore the upstream LGPL-2.1 text before redistributing.

The file headers reference:

```
@author   Lukas Dziadkowiec <i.ftelf@gmail.com>
@license  http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
@link     https://www.ovjih.net
```
