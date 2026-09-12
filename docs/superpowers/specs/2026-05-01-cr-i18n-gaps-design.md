# CR-06 — Internationalisation Gaps & Encoding

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P2 (operator pain in non-Czech deployments; latent data-loss risk for emoji/non-BMP characters)
> **Effort:** S–M (2 weeks)
> **Source documents:** `docs/TECHNICAL.md` §Internationalisation (lines 611–625), §Database schema (lines 736–760); `docs/USER_GUIDE.md` §What NetProvider does (line 41), §Sending notifications (lines 215–245)

---

## 1. Problem statement

NetProvider's gettext-based i18n covers most of the UI but has known holes that the documentation acknowledges yet does not plan around. Combined with the legacy `utf8` (3-byte) database encoding, the system has both feature gaps and silent-data-loss vectors:

1. **Hardcoded Czech strings outside `_()`.** TECHNICAL line 623 — "Some legacy strings inside `EmailBankAccountList`, `AccountEntryUtil` and the `templates/events/` files are hard-coded Czech and **not** wrapped in `_()`. Translating those means editing the source." Today these strings reach customers (payment reminders) and supervisors (error reports) regardless of `[UI] Locale`.
2. **`utf8` vs `utf8mb4` mismatch.** TECHNICAL §Database schema line 758 — connection runs `SET CHARACTER SET utf8` and `SET NAMES 'utf8'` (3-byte). MySQL `utf8` cannot store 4-byte sequences (emojis, less common CJK, mathematical symbols, some Czech diacritics in surnames written with combining characters). Storing such input silently truncates from the offending character onward — a known footgun.
3. **`messages.pot` extraction does not visit the templates directory.** `make locales` runs `xgettext` over `*.php`, missing strings inside `templates/events/*.txt`. Adding a translation locale therefore leaves all customer-facing email bodies untranslated.
4. **CHANGELOG is Czech-only** (see CR-05 §1.4). A non-Czech-reading operator must guess at release content.
5. **No locale fallback chain.** USER_GUIDE line 41 lists English and Czech. If `Locale = de_DE.UTF-8` is set without a German `.po`, the result depends on `setlocale` behavior on the host (usually returns identifier strings or POSIX defaults). No documented behavior, no warning.
6. **Date / number formatting inconsistencies.** Czech dates (`d.m.Y`), amounts (`1 234,56 Kč`) are produced by `Utils::*` helpers (TECHNICAL line 82), but the helpers are locale-aware only via PHP's `setlocale` — which silently degrades when the requested locale is missing.
7. **Locale missing on host.** The system locale `cs_CZ.UTF-8` must exist on the host (`locale -a`); if the operator ships a stripped Docker image without the locale-gen package, `setlocale` silently returns `false` and all translations fall through. No startup check.
8. **Translator workflow is undocumented.** TECHNICAL §Internationalisation describes the tools but not the process: where to put new locales, how to test mid-translation, when to expect catalog refreshes.
9. **No locale switcher.** End-users cannot choose their preferred language; everything follows the system-wide `[UI] Locale` setting.

## 2. Affected docs / areas

- `docs/TECHNICAL.md` §Internationalisation, §Database schema
- `docs/USER_GUIDE.md` §Sending notifications
- `Makefile` — extraction targets
- `includes/Database.php` — connection setup
- `includes/EmailBankAccountList.php`, `includes/billing/AccountEntryUtil.php`
- `templates/events/*.txt`
- `localhost.sql` — schema-level charset/collation

## 3. Goals & non-goals

**Goals**

- Every user-facing string in the codebase is wrapped in `_()` (or an equivalent localized helper).
- `make locales` extracts strings from `templates/events/` as well as `*.php`.
- Database connection and schema use `utf8mb4` with `utf8mb4_czech_ci` collation by default.
- Locale fallback chain: requested → English → POSIX, with a startup warning if any link is missing.
- Per-user locale preference (stored on `Person`).
- Documented translator workflow.
- A startup check at first request that `setlocale()` actually returned the requested locale; warn loudly if not.

**Non-goals**

- Adding new locales beyond English and Czech in this CR.
- Right-to-left language support.
- Currency formatting beyond the existing CZK / EUR cases.
- Pluralization rules beyond gettext's standard `ngettext`.

## 4. Proposed change

### 4.1 Wrap remaining hardcoded strings

- Audit `EmailBankAccountList`, `AccountEntryUtil`, every `templates/events/*.txt` for literal Czech.
- Replace inline Czech with `_("...")` call sites; the source becomes the canonical English string.
- Move email-template bodies into per-locale subdirectories: `templates/events/<locale>/<name>.txt`. The dispatcher picks the correct file based on the recipient's locale (see §4.5) with English fallback.
- Provide a "msgmerge before merge" check in CI: any new `_()` calls must already be reflected in `messages.pot`.

### 4.2 Extract templates with xgettext

- Update `Makefile` to add a second extraction pass over `templates/events/*.txt` using `xgettext --language=PHP --keyword=:` (the colon trick treats the file as a string). The result merges into `messages.pot`.
- Document the regex used to identify translatable spans in templates: any line not starting with `|TOKEN|` is candidate text.

### 4.3 utf8mb4 migration

| Step | Action                                                                                                                                                |
| ---- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1    | New `localhost.sql` ships `utf8mb4` / `utf8mb4_czech_ci`.                                                                                              |
| 2    | `Database::__construct` runs `SET NAMES 'utf8mb4'` and `SET CHARACTER SET utf8mb4`.                                                                   |
| 3    | A migration script (one-shot) walks every table:<br>`ALTER TABLE … CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci;`                         |
| 4    | Audit every `VARCHAR(>191)` column — InnoDB row-length limit changes with utf8mb4 (4 bytes per char). Adjust to `VARCHAR(191)` or switch to `TEXT` per case. |
| 5    | Verify indexes on long columns (`PE_username`, `PE_email`, etc.) still fit. Re-create with `ROW_FORMAT=DYNAMIC` if necessary.                          |

The migration must be reversible (snapshot the database first; the conversion takes minutes on small ISP databases).

### 4.4 Locale fallback + startup check

`Core::__construct` after `setlocale` returns:

```php
$result = setlocale(LC_ALL, $configured);
if ($result === false || $result === 'POSIX' || $result === 'C') {
    $fallback = setlocale(LC_ALL, 'en_US.UTF-8');
    syslog(LOG_WARNING, "NetProvider: locale '$configured' not available; falling back to '$fallback'");
    // emit a UI banner for super-admins
}
```

Operator gets a clear hint that `locale-gen $configured` (or apt install of locale package) is required.

### 4.5 Per-user locale

- New column `person.PE_locale` (varchar 32, nullable). When NULL, use system default.
- When sending an event-driven email, dispatcher picks the recipient's locale and selects the matching template subdirectory.
- Self-service language switcher in `com_myprofile`.

### 4.6 Translator workflow doc

New section in `docs/TECHNICAL.md` §Internationalisation:

1. Add the locale to `APPLICATION_LOCALES` in `Makefile`.
2. `make locales` produces `translation/<locale>/LC_MESSAGES/messages.po`.
3. Translate `messages.po` (Poedit, gtranslator, or any text editor — UTF-8).
4. Re-run `make locales` to compile `messages.mo`.
5. Mirror email templates: `cp -r templates/events/en_US.UTF-8 templates/events/<locale>` and translate each file.
6. Set `Locale = <locale>` (or per-user `PE_locale`).

### 4.7 CI check for translation completeness

- `msgfmt --statistics` per locale; threshold (e.g. 95% translated) gates merge.
- Untranslated strings list is posted as a comment on the PR.

## 5. Migration path

| Phase | Action                                                                                  |
| ----- | --------------------------------------------------------------------------------------- |
| 1     | Wrap hardcoded strings; ship updated `messages.pot`. No user-visible change yet.        |
| 2     | utf8mb4 migration during a maintenance window. Backups taken first.                     |
| 3     | Per-user locale rolled out; UI defaults to system locale.                                |
| 4     | Email templates moved into per-locale dirs; dispatcher updated.                          |
| 5     | Translator-workflow documentation published; community translators invited.             |

## 6. Testing strategy

- **Unit tests:**
  - `Core` startup check returns a warning when `setlocale` falls back.
  - Email dispatcher selects the right template directory given a recipient's `PE_locale` and a missing-locale fallback to English.
  - `Database::__construct` connection issues `SET NAMES 'utf8mb4'`.
- **Integration tests:**
  - Round-trip an emoji-bearing customer name (e.g. `"Jan 🎉 Novák"`) through the customer form, the database, and a payment-reminder email; verify byte-perfect storage and rendering.
  - Insert a 4-byte CJK character in `IN_description`; verify retrieval is identical.
- **Locale audits:**
  - Run `xgettext` after every PR; CI fails if `msgfmt --statistics` drops below threshold.
  - Native Czech reviewer signs off on email-template translations.

## 7. Open questions

- `utf8mb4_czech_ci` vs `utf8mb4_unicode_520_ci`: the former is Czech-specific (correct sorting for `ch` digraph), the latter is broader but slightly mis-sorts Czech. Recommendation: `utf8mb4_czech_ci` for the Czech-default deployment; the operator picks via the install script.
- Should `PE_locale` accept any well-formed locale string or only locales for which a `.mo` exists? Recommendation: accept any; on first request the fallback chain in §4.4 logs a warning if the locale resolves to English instead.
- Email templates per locale or one template with `_()` calls inline? Recommendation: per-locale directories — translators don't need PHP gettext skills.

## 8. Effort estimate & risk

- **Effort:** S–M. ~2 person-weeks engineering, plus translator time (Czech revalidation: 1 day; English review: 1 day).
- **Risk highlights:**
  - utf8mb4 migration is the highest-risk item. A failed `ALTER TABLE` on a large table can lock the system for minutes; schedule with the operator.
  - Long-string indexes (`PE_username` etc.) may hit InnoDB key length limits after conversion. Pre-flight script catches these and proposes column shrinks.
  - Per-user locale changes the cron-driven email dispatcher behavior; ensure the dispatcher reads `PE_locale` lazily, not at request start.

## 9. Out of scope

- Adding non-CZ/EN locales upstream (community-driven).
- RTL support.
- ICU MessageFormat / advanced pluralization.
- Translation memory / TMX integration.
- Currency conversion or per-locale currency format negotiation (CR-02 §4.4 covers single-currency-per-customer; this CR does not extend further).
