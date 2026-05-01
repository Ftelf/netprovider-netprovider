# NetProvider — User Guide

> ISP billing and network management system for small Internet Service Providers.

This guide is written for **operators and administrators** of a NetProvider instance — the people who manage customers, subscriptions, payments, and the network device. End-customers (regular users) only interact with their own profile page; their workflow is summarised in the [End-customer view](#end-customer-view) section at the end.

---

## Table of contents

1. [What NetProvider does](#what-netprovider-does)
2. [Roles and access levels](#roles-and-access-levels)
3. [First login](#first-login)
4. [Main menu reference](#main-menu-reference)
5. [Day-to-day workflows](#day-to-day-workflows)
   - [Onboarding a new customer](#onboarding-a-new-customer)
   - [Assigning an Internet service](#assigning-an-internet-service)
   - [Importing bank statements](#importing-bank-statements)
   - [Running billing](#running-billing)
   - [Activating and deactivating service](#activating-and-deactivating-service)
   - [Sending notifications](#sending-notifications)
6. [Reports](#reports)
7. [Concepts and glossary](#concepts-and-glossary)
8. [End-customer view](#end-customer-view)
9. [Common operational tasks](#common-operational-tasks)
10. [Troubleshooting and FAQ](#troubleshooting-and-faq)

---

## What NetProvider does

NetProvider is a self-hosted PHP web application that runs the back-office of a small ISP. In one system it handles:

- **Customer records** — personal details, contact info, login, group/role assignment, status (active/passive/discarded).
- **Subscriptions and one-off charges** — recurring monthly fees, entry fees, penalties; per-customer activation windows.
- **Customer-account ledger** — every customer has a virtual account with running balance, total income and total outcome.
- **Bank-statement import** — pulls statements over POP3 (Raiffeisenbank TXT/PDF or ISO-SEPA XML), parses them, and matches incoming payments to customers by variable symbol.
- **Network QoS control** — pushes IP filter and traffic-shaping rules to the WAN device so service for paid-up customers is allowed and the rest is shaped or dropped. Supports two platforms: Linux (`iptables` / `tc` over SSH) and MikroTik RouterOS (RouterOS API).
- **IP traffic accounting** — collects per-IP byte/packet counters from the network device for usage reports.
- **Event-driven email notifications** — sends pre-deadline reminders, late-payment notices and similar messages from configurable templates.
- **Internationalisation** — full UI in English (`en_US.UTF-8`) and Czech (`cs_CZ.UTF-8`).

The system is operated through a web UI plus a CLI service script that does the heavy automated work (typically driven by cron).

---

## Roles and access levels

Every login is a `Person` row with a `groupid`. Each `Group` has a numeric **level** (`GR_level`). NetProvider recognises three levels (defined in `includes/tables/Group.php`):

| Level constant       | Numeric value | Capabilities                                                                                                                                                |
| -------------------- | :-----------: | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `USER`               |       0       | End-customer. After login, automatically redirected to **My profile** — can see their own data, services, account balance, and traffic stats. No menu.      |
| `ADMINISTRATOR`      |       5       | Operator. Full access to customers, charges, payments, network settings, reports and notifications.                                                         |
| `SUPER_ADMINISTRATOR`|       9       | Same UI as Administrator, plus access to scripts, configuration view and the full audit log. Intended for the system owner.                                 |

`USER` accounts are forced to `com_myprofile` regardless of which URL they request — see `site/index2.php` lines 117-119.

In addition to the level, finer-grained permissions can be granted via **Roles** (`com_role`) which are a label attached to a person. They are used inside individual modules to gate operations (for example, only members of a billing role may run `proceedCharges`).

---

## First login

### Prerequisites

Before you can log in for the first time:

1. The PHP web server must be running and serving `site/` as the document root for the public URL.
2. `config/netprovider.ini` must point at a reachable MySQL database.
3. The database must be loaded from `localhost.sql` (or restored from a backup).
4. At least one `Person` with `GR_level = 9` (super-administrator) must exist with a known password.

### Logging in

1. Open the site root in a browser. You will be redirected to `index.php`, which renders the login form.
2. Enter the **Username** and **Password** for an administrator account. Passwords must be at least six characters long; they are MD5-hashed before being compared with the stored hash.
3. On success, you are redirected to `index2.php` and given a session cookie named `NETPROVIDER`. Sessions time out after 30 minutes of inactivity.

If the login form rejects you, the database log records a `Security` level entry with the offending username and the source IP — useful when you suspect a brute-force attempt.

---

## Main menu reference

The top-bar menu is generated in `modules/com_common/html_mainmenu.php`. The structure below mirrors it section by section, and lists the URL parameter used to enter each module.

| Section                | Item                          | URL                                  | Purpose                                                              |
| ---------------------- | ----------------------------- | ------------------------------------ | -------------------------------------------------------------------- |
| **User agenda**        | Users                         | `?option=com_person`                 | Browse, edit, create, deactivate customers                           |
|                        | User groups                   | `?option=com_group`                  | Define groups (`USER`, admin tiers, custom)                          |
|                        | Roles and responsibilities    | `?option=com_role`                   | Permission roles                                                     |
|                        | My profile                    | `?option=com_myprofile`              | Logged-in user's profile                                             |
| **Financial**          | Bank accounts                 | `?option=com_bankaccount`            | Configure bank accounts; import / upload statements                  |
|                        | User's accounts               | `?option=com_personaccount`          | Per-customer ledger; manual debits and credits                       |
|                        | Payment templates             | `?option=com_charge`                 | Define charges (subscriptions, one-offs, penalties)                  |
| **Services**           | Internet services             | `?option=com_internet`               | Define service tiers (down/up rates, ceiling, priority)              |
| **Network**            | IP networks                   | `?option=com_network`                | Define IP ranges and assign IPs to customers                         |
|                        | Network device                | `?option=com_networkdevice`          | View status / sync rules to the QoS device                           |
| **Administration**     | Scripts                       | `?option=com_scripts`                | Run on-demand maintenance scripts                                    |
|                        | Configuration                 | `?option=com_configuration`          | View `netprovider.ini` settings                                      |
|                        | Log                           | `?option=com_log`                    | Browse the audit/event log                                           |
|                        | Messages                      | `?option=com_message`                | Send a message to one customer                                       |
|                        | Event handlers                | `?option=com_handleevent`            | Configure automated email notifications and templates                |
|                        | Mass messages                 | `?option=com_massmessages`           | Bulk email or SMS                                                    |
| **Reports**            | Payment report                | `?option=com_paymentreport`          | Aggregated income / outstanding view                                 |
|                        | IP data traffic report        | `?option=com_iptrafficreport`        | Per-IP, per-period bandwidth report                                  |
| **Help**               | Changelog                     | `?option=com_changelog`              | Read `CHANGELOG.md` in-app                                           |

Inside each module the page header is followed by a filter bar, a table, and per-row actions (`new`, `edit`, `save`, `apply`, `cancel`, `remove`). Every list page remembers your filter, page size and pagination position per-user (stored serialized in `Person.PE_uistate`).

---

## Day-to-day workflows

### Onboarding a new customer

1. Open **User agenda → Users** (`com_person`).
2. Click **New**. Fill in the mandatory fields — username, password, group (typically `USER`), first name, surname, address, contact details. The system supports physical persons and (if `Allow firm registration` is enabled in `netprovider.ini`) registered companies with `IČ`/`DIČ` (Czech tax IDs).
3. On save, NetProvider creates:
   - A `person` record with `PE_status = STATUS_ACTIVE`.
   - A linked `personaccount` row that holds the customer's running balance, with the **variable symbol** used for matching incoming bank payments.
4. Optionally assign **Roles** to the customer in the same form.
5. Optionally assign **IP addresses** under **Network → IP networks**, picking an `IP_address` and `IP_dns`. An IP belongs to exactly one network and one customer.

The customer can now log in to their profile, but no service is active yet — that requires a charge.

### Assigning an Internet service

A customer becomes "billable" for a service via a `HasCharge` record (subscription assignment).

1. Define the **Internet service** once under **Services → Internet services** — name, download rate / ceiling, upload rate / ceiling, priority. The numbers are in kbit/s and are passed verbatim to `tc` (Linux) or to a queue tree entry (RouterOS).
2. Define a **Payment template** (`Charge`) under **Financial → Payment templates** — period (currently only **Monthly** is supported in the schema), base amount, VAT, total amount, currency, tolerance days, write-off offset days, type (`Internet payment`, `Entry fee`, `Penalty`, etc.). Optionally link the charge to an Internet service (`CH_internetid`).
3. Open the customer's profile → **Charges** tab and click **New charge assignment**. Pick the charge template, the **start date** (day must be `1` for monthly charges) and optionally the **end date** (also day `1`, or leave empty for open-ended). Set status:
   - `Activated` (`STATUS_ENABLED`) — normal mode; the system enables/disables the service based on whether the customer has paid.
   - `Service is always activated` (`STATUS_FORCE_ENABLED`) — bypass the payment check and keep the service on. Useful for credit-card-paying customers or freebies.
   - `Service is always deactivated` (`STATUS_FORCE_DISABLED`) — keep the service off regardless of payments.
   - `Deactivated` (`STATUS_DISABLED`) — exclude this `HasCharge` from billing entirely.
4. After saving, the next billing run will create projected charge entries (six months ahead by default — `Blank charges advance count` in `netprovider.ini`). When a charge entry's write-off date passes, the system attempts to deduct the amount from the customer's balance.

### Importing bank statements

NetProvider recognises three statement formats out of the box, all originating from Raiffeisenbank: legacy `_*.TXT`, `Vypis_*.PDF`, and ISO-SEPA `Vypis_*.XML` (optionally `.XML.ZIP`). See `includes/billing/bankParser/`.

There are two ingestion paths:

**Email pull (automated).** Configure the bank account under **Financial → Bank accounts** with:

- `BA_emailserver`, `BA_emailusername`, `BA_emailpassword` — POP3 credentials.
- `BA_emailsender`, `BA_emailsubject` — the From and Subject substrings used to recognise statement emails.
- `BA_datasourcetype` — one of TXT, PDF or ISO SEPA XML.

The `--proceed-payments` service run (see [Running billing](#running-billing)) will fetch new emails, decode attachments, validate that the account number and currency match the configured bank account, and store each statement in the `emaillist` table. Continuity is verified by statement number — gaps trigger a critical email to the supervisor.

**Manual upload.** On the bank-account detail page, use **Upload bank list** to attach a TXT/PDF/XML statement. Same parsing pipeline, no POP3 needed.

After a statement is stored, parsing produces individual `bankaccountentry` rows with status `STATUS_PENDING`. They become customer payments only after the matching pass runs (see next section).

### Running billing

Billing is automated through the CLI service. Three operations participate:

```bash
# 1. Pull new statements + match payments + create+process charge entries
php services/service.php --proceed-payments
```

This single command does everything a daily cron job needs:

- Connects to each configured bank-account mailbox over POP3, downloads new statements, parses them, persists `bankaccountentry` rows.
- Runs the **payment matcher** (`AccountEntryUtil`): for each pending statement entry it tries to find a person whose `PA_variablesymbol`, `PA_constantsymbol` and `PA_specificsymbol` match the row's symbols. Found and active → credit the customer's balance, create a `personaccountentry` audit row, mark the statement entry as `PROCESSED`. Ambiguous (multiple persons match) or missing → email the supervisor, leave the row pending.
- Calls `ChargesUtil::createBlankChargeEntries()` — projects future monthly entries up to the **advance** horizon for every active `HasCharge`, and removes any entries that fall outside `[HC_datestart, HC_dateend]`.
- Calls `ChargesUtil::proceedCharges(true)` — for each active customer, walks every charge entry whose write-off date has passed and:
  - if balance ≥ amount → deduct, mark `FINISHED`, set `realize_date`;
  - if balance < amount → mark `PENDING_INSUFFICIENTFUNDS`, dispatch a `ChargePaymentDeadlineEvent` so the configured email handler can send a reminder.
- Recomputes each `HasCharge.HC_actualstate` (`ENABLED` / `DISABLED`) based on whether the current period is paid up and whether outstanding entries are still inside the configured `tolerance` window.

The recommended cron schedule is **once per day, off-peak**:

```cron
30 2 * * *  /usr/bin/php /var/www/netprovider/services/service.php --proceed-payments
```

The other CLI flags do narrower work — see the [Common operational tasks](#common-operational-tasks) table.

### Activating and deactivating service

NetProvider does not push rules to the WAN device automatically when a `HasCharge` flips state — the network state is reconciled by a separate run:

```bash
php services/service.php --proceed-networking
```

Internally this calls `CommanderCrossbar::ipFilterUp()`, which delegates to the platform-specific commander selected by `Network Device Platform` in the config:

- `LINUX` → `LinuxCommander`: opens an SSH connection and runs `iptables` + `tc` to build accept-rules and HTB classes for every IP that belongs to a customer with at least one currently `ENABLED` Internet `HasCharge`.
- `ROUTEROS` → `RouterOSCommander`: connects to the MikroTik over the RouterOS API on port 8729 (TLS) and rewrites `/ip/firewall/filter` chains plus the queue tree.

The recommended cron entry for the network sync is **every few minutes**:

```cron
*/5 * * * *  /usr/bin/php /var/www/netprovider/services/service.php --proceed-networking
```

To force everything off (for example before maintenance) or back on:

```bash
php services/service.php --ip-filter-down
php services/service.php --ip-filter-up
```

In the web UI the same operation is exposed under **Network → Network device**.

### Sending notifications

NetProvider emits **events** during billing. Each event type can have one or more `HandleEvent` rows describing how to react.

Currently implemented:

- `TYPE_CHARGE_PAYMENT_DEADLINE` — fired when a charge entry could not be paid due to insufficient funds. Configurable fields:
  - `HE_status` — Enabled/Disabled.
  - `HE_notifydaysbeforeturnoff` — only fire if the customer is at most this many days away from being switched off (i.e. inside the tolerance window).
  - `HE_notifypersonid` — if set, send the email to this back-office address instead of the customer (useful for "warn the operator" rules).
  - `HE_emailsubject`, `HE_templatepath` — Subject and the path (relative to `templates/events/`) of the body template.

The body template is plain text with `|PLACEHOLDER|` tokens that are replaced at send time:

```
|PERSON_NAME|, |CHARGE_NAME|, |CHARGE_BASEAMOUNT|, |CHARGE_VAT|,
|CHARGE_AMOUNT|, |CHARGE_CURRENCY|, |CHARGE_PERIOD|,
|CHARGE_PERIOD_DATE|, |CHARGE_WRITE_OFF|, |CHARGE_SWITCH_OFF|
```

The shipped templates live in `templates/events/`:

- `notify_user_on_invoice_created.txt`
- `notify_user_on_payment_delay.txt`
- `notify_user_on_payment_delay_2021.txt`
- `notify_user_on_payment_delay_2024.txt`
- `notify_user_on_payment_delay_newsletter.txt`

Edit them directly on disk (after backing them up) and reference them from the relevant `HandleEvent` row.

For ad-hoc, non-templated mailings the **Messages** and **Mass messages** modules send via SMTP using the `[SMTP]` block of `netprovider.ini`.

---

## Reports

### Payment report (`com_paymentreport`)

Aggregates `chargeentry` rows over a chosen period and displays paid / pending / overdue totals per customer and per charge type. Useful at month-end to find the customers who are still in arrears.

### IP data traffic report (`com_iptrafficreport`)

Combines fresh per-IP counters (table `ipaccount`) with the long-term roll-up (`ipaccountabs`) to chart up/down bytes over a period. The roll-up is maintained automatically by `--clean-up`:

- Records older than two months are grouped by month.
- Records older than one month are grouped by day.
- Anything more recent stays at original sampling.

In the customer-facing **My profile** page the same figures are rendered as a personal usage chart.

---

## Concepts and glossary

| Term                | What it means in NetProvider                                                                                                                                |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Person**          | A customer or operator. One row per login.                                                                                                                  |
| **Group**           | A class of users (`USER`, `ADMINISTRATOR`, `SUPER_ADMINISTRATOR`). Drives top-level access.                                                                  |
| **Role**            | A finer-grained label attached to a person. Used for module-level permissions.                                                                              |
| **PersonAccount**   | The customer's virtual ledger: balance, total income, total outcome, and matching symbols (variable / constant / specific).                                 |
| **Charge**          | A payment template — what is billed, how much, in what currency, with what period and tolerance.                                                            |
| **HasCharge**       | "This customer has this charge between these dates." The actual subscription assignment.                                                                    |
| **ChargeEntry**     | A single billing line for one period of one HasCharge — the system creates these in advance and processes them when their write-off date arrives.            |
| **BankAccount**     | A configured bank account, including how to fetch its statements and what symbols belong to it.                                                              |
| **BankAccountEntry**| A single transaction inside a parsed bank statement.                                                                                                         |
| **EmailList**       | A parsed bank statement (one PDF / TXT / XML file).                                                                                                          |
| **PersonAccountEntry** | A credit posted to a customer ledger — typically created from a matched `BankAccountEntry`, but cash and discount entries are also supported.            |
| **Internet**        | A service tier: down/up rate, ceiling, priority. Linked from a `Charge`.                                                                                     |
| **Network**         | An IP range. IPs live inside networks and belong to one customer.                                                                                            |
| **HandleEvent**     | "When event X fires, do Y" — typically send an email rendered from a template.                                                                               |
| **Variable symbol** | The Czech standard payment identifier; NetProvider uses it as the primary key when matching bank rows to customers.                                          |
| **Tolerance**       | Days after the write-off date during which an unpaid charge is still treated as paid (service stays on).                                                     |
| **Write-off offset**| Days after the period start when a charge attempt is made.                                                                                                    |

---

## End-customer view

When a `USER`-level account logs in, the system jumps directly to **My profile** (`com_myprofile`). They see:

- their basic profile data and a form to update non-critical fields (phone, email);
- their list of charges and the status of each (active, deactivated, last paid period);
- their account balance, last payments, and downloadable invoice if enabled;
- their IP addresses and a usage chart from the IP-accounting roll-up.

End-customers cannot see other customers, the audit log, or the configuration.

---

## Common operational tasks

| Task                                   | How                                                                                                                          |
| -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Pull statements + run billing          | `php services/service.php --proceed-payments` (cron daily)                                                                   |
| Push current state to the QoS device   | `php services/service.php --proceed-networking` (cron every 5 min)                                                           |
| Force everything off                   | `php services/service.php --ip-filter-down`                                                                                  |
| Force everything on                    | `php services/service.php --ip-filter-up`                                                                                    |
| Sample IP traffic counters             | `php services/service.php --ip-account` (cron hourly)                                                                        |
| Compact old IP-accounting samples      | `php services/service.php --clean-up` (cron weekly)                                                                          |
| Recompile UI translations              | `make locales`                                                                                                                |
| Force-log-out a session                | **Administration → Log/Sessions** → click *Force logout*                                                                     |
| Manually credit a customer (e.g. cash) | **Financial → User's accounts** → customer → **New** entry, source = Cash or Discount                                        |
| Issue a one-off charge (e.g. install)  | **Financial → Payment templates** → `Type = Entry fee` → assign via `HasCharge`                                              |
| Refund a paid charge                   | **User's accounts → Return payment** on the relevant `chargeentry`                                                           |
| Mark a bank row as "ignore"            | Open the row in **Bank accounts → bank list** → identification = *Ignore*                                                    |
| Add a new statement format             | Implement a parser under `includes/billing/bankParser/` and register it in `BankParserFactory`                                |

---

## Troubleshooting and FAQ

**"Cannot connect to database" on every page.** The database credentials in `[Database]` of `netprovider.ini` are wrong, or MySQL is not reachable. Check `mysql -h <host> -u <user> -p`. The web error is intentionally vague; the underlying mysqli error is logged to the syslog `NetProvider` facility for the CLI service.

**Login keeps redirecting to the login form.** The session cookie name is fixed (`NETPROVIDER`); make sure the browser accepts it. Sessions also expire after 30 minutes. If only some users hit this, look at `session.SE_ip` — the IP must equal `$_SERVER['REMOTE_ADDR']`. A reverse proxy that rewrites the source IP will break the check; either expose the real client IP or move the proxy.

**Statements arrive but customers are not credited.** Check the bank-account list page — pending rows that did not match a customer have `identification = Waiting for identification`. Causes: variable symbol typo, wrong customer status (must be Active), more than one customer claiming the same symbol (duplicate detected — supervisor is emailed). Fix the customer or split the bank row manually.

**A customer was switched off even though they paid.** The most likely cause is a write-off offset / tolerance mismatch. Open the customer's chargeentries — every one that should have been settled must show status `Finished` and a non-empty `realize_date`. If the entry is still `Pending`, check that the daily `--proceed-payments` job actually runs (`grep NetProvider /var/log/syslog`).

**RouterOS sync fails with "API login failed".** Confirm the API service is enabled on port 8729 with TLS, that the NetProvider host can reach the device on that port, and that the `Network Device Login` user has at least the `read,write,api,ftp,sniff,dude,romon,test` policies.

**Linux iptables sync fails.** The configured `Network Device Login` user must be able to run the configured `Network Device Command iptables` via the configured `Network Device Command sudo` without a password. The shipped defaults are `/usr/bin/sudo` and `/usr/local/sbin/iptables`.

**Critical errors arrive by email even when the system is healthy.** Set `SMTP Send EMail on critical error = false` in `netprovider.ini` while you investigate. The address is `SMTP Supervisor EMail`.

**Where is the audit log?** Database table `log` (`Administration → Log` in the UI), and additionally syslog facility `LOG_DAEMON` with tag `NetProvider` for CLI runs. Levels are `Log`, `Debug`, `Info`, `Warning`, `Error`, `Critical`, `Security`.

**Why do I sometimes see `Internal system error / Supervisor has been informed`?** The web index2 catches every uncaught exception, logs it, optionally emails the supervisor and shows the generic message. Set `Debug = true` in `[System]` to see the stack trace in the browser instead — only do this on a non-public instance.
