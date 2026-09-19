# Module reference

Per-module developer reference for the NetProvider web UI. One file per module
(`com_<feature>.md`). These are written for **making safe UI/frontend changes**:
every screen, task, and field is traceable to a `file:line` in source.

See also:

- [../TECHNICAL.md](../TECHNICAL.md) — architecture, request lifecycle, conventions.
- [../USER_GUIDE.md](../USER_GUIDE.md) — end-user workflows.
- [../frontend.md](../frontend.md) — shared render pipeline (`com_common`) and
  client-side layer (JS widgets, validation, CSS/theme).

## How the UI is wired (recap)

`site/index2.php` reads `?option=com_<feature>&task=<task>`, resolves the path via
`MainFrame::getPath()`, includes `modules/com_<feature>/<feature>.index.php`
(controller), which dispatches on `$_REQUEST['task']` and calls
`HTML_<feature>::<method>()` in `<feature>.html.php` (view). List state (`filter`,
`limit`, `limitstart`) is persisted per option in `$_SESSION['UI_SETTINGS']`.

## Document template (contract)

Every `com_<feature>.md` MUST follow this exact section order. Do not invent
content: if something is not present in source, write "none" — never guess.

```markdown
# com_<feature>

> One-sentence purpose.

## Access
Which `Group` levels reach this module; note redirects/forced routing (e.g. USER
forced to com_myprofile). Cite the ACL/getPath source. `file:line`.

## Entry point
- Option: `?option=com_<feature>`
- Controller: `modules/com_<feature>/<feature>.index.php`
- View class: `HTML_<feature>` in `<feature>.html.php`

## Tasks
| Task | Triggered by | Handler | Reads | Writes (DAO → table) | Renders | Side effects |
|------|--------------|---------|-------|----------------------|---------|--------------|
| ...  | button/link/default | `<feature>.index.php:LN` | `$_POST[...]` | `XxxDAO::m()` → `TABLE` | `HTML_x::view()` | events/email/net |

MUST list every `case` in the controller `switch` (plus the default/list case).
Coverage is checked against the source — 0 gaps.

## Views
| Method | Renders | Source |
|--------|---------|--------|
| `HTML_x::method()` | what the user sees | `<feature>.html.php:LN` |

## Data touched
- DAOs: `XxxDAO` (methods used)
- Tables: `Xxx` (`includes/tables/Xxx.php`)

## Forms & fields
Per form/screen: field name → column/meaning → validation (client `validator.js`
rule and/or server check). `file:line`.

## Related flows & cross-module links
Events fired (`EventCrossBar`), redirects to other options, shared DAOs.

## Source anchors
Key `file:line` references used above.
```

## Fidelity rules (all docs)

1. **Coverage** — every `switch` case in the controller appears in the Tasks table.
2. **Fidelity** — every claim is anchored to `file:line`; no invented tasks/fields.
3. **Traceability** — task → DAO → table → view method is explicit.
4. **Consistency** — identical section order across all files.
5. **Actionability** — a reader can locate the exact file+function to change a screen.

## Index

**Dispatch tasks** = distinct top-level `case` labels in the controller `switch`,
**excluding** the `default`/list branch and excluding nested post-save
redirect-selector switches (those inner `case 'save'`/`case 'apply'` lines choose
only the redirect wording and are documented as notes, not routes). Each module's
Tasks table additionally includes a `default` row per the contract.

| Module | Purpose | Controller size | Dispatch tasks |
|--------|---------|-----------------|----------------|
| [com_admin](com_admin.md) | Admin landing / dashboard | 71L | 1 |
| [com_bankaccount](com_bankaccount.md) | Bank statement import & entry matching | 717L | 15 |
| [com_changelog](com_changelog.md) | Changelog viewer | 40L | 0 |
| [com_charge](com_charge.md) | Charge (subscription) definitions | 239L | 7 |
| [com_configuration](com_configuration.md) | Runtime configuration screen (read-only) | 18L | 0 |
| [com_group](com_group.md) | User groups / access levels | 174L | 7 |
| [com_handleevent](com_handleevent.md) | Event handler configuration | 181L | 7 |
| [com_internet](com_internet.md) | Internet service definitions | 184L | 7 |
| [com_iptrafficreport](com_iptrafficreport.md) | IP traffic accounting report | 176L | 1 |
| [com_log](com_log.md) | System log viewer | 110L | 1 |
| [com_massmessages](com_massmessages.md) | Bulk messaging | 283L | 2 |
| [com_message](com_message.md) | Messages | 135L | 2 |
| [com_myprofile](com_myprofile.md) | End-customer self-service profile | 216L | 3 |
| [com_network](com_network.md) | Networks & QoS rules | 868L | 12 |
| [com_networkdevice](com_networkdevice.md) | Network device configuration | 113L | 1 |
| [com_paymentreport](com_paymentreport.md) | Payment report | 224L | 0 |
| [com_person](com_person.md) | Customers (persons), roles, charges | 638L | 15 |
| [com_personaccount](com_personaccount.md) | Person account, balance, charge entries | 577L | 16 |
| [com_role](com_role.md) | Roles | 171L | 7 |
| [com_scripts](com_scripts.md) | Maintenance scripts | 104L | 3 |

## Known UI defects surfaced during documentation

Real source issues found while documenting (candidates for the upcoming UI work,
not yet fixed):

- `com_iptrafficreport` — `case 'trafficReport'` calls undefined `showBankList()`
  (copy leftover from com_bankaccount); invoking it would fatal-error. No live
  trigger sets `task=trafficReport`.
- `com_changelog` — hidden `option` field is `com_scripts`, not `com_changelog`
  (harmless copy artifact; form is never submitted).
- `com_configuration` — screen is read-only; the `<form>` has no submit path.
- `com_log` — `log.html.php:149` hidden `filter[date_to]` uses
  `<?php $filter['date_to']; ?>` with no `echo`, so it renders empty.
- `com_bankaccount` — `editBankAccount()` Cancel submits undefined task
  `cancelHasCharge` (copy-paste artifact; falls through to default).
- Client validation — `validator.js` has no `date` case, so all `date=dd.MM.yyyy`
  rules are silent no-ops (see [../frontend.md](../frontend.md)).
