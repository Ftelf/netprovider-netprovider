# CR-07 — Modernization: Composer, PHPUnit, Migrations, Mailer

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P2 (no immediate functional defect, but technical debt that blocks safe evolution)
> **Effort:** L (4–6 weeks)
> **Source documents:** `docs/TECHNICAL.md` §Runtime stack (lines 32–43), §Coding conventions (lines 691–699); `README.md` §Requirements (lines 43–48); `CHANGELOG.md` (Czech, indicating manual release management)

---

## 1. Problem statement

NetProvider runs on a stack that was modern in 2015. Its dependency, build, test, and database-schema management practices have not kept up. The shipped documentation tells the story:

1. **PEAR dependencies.** TECHNICAL line 40 — "PEAR packages: `Mail`, `Mail_Mime`, `Net_POP3`, `Net_SMTP`, `Net_IPv4`". PEAR is officially in maintenance-only mode since 2019; many of these packages have not seen a release in 5+ years. Modern alternatives (`PHPMailer`, Symfony `Mailer`, `Symfony Mime`, `webklex/php-imap`) are actively maintained, accept TLS configurations natively, and integrate with composer.
2. **No dependency manager.** README §Requirements lists PEAR packages to install; there is no `composer.json`. Operators install dependencies system-wide via `pear install`. New machines have to chase down compatible package versions manually. CI is not possible without a deterministic install.
3. **No automated tests.** No `tests/` directory, no `phpunit.xml`, no test fixtures anywhere in the repo. Every CR proposed in this audit relies on a "test strategy" section that cannot run today.
4. **No database migration framework.** TECHNICAL §Database schema says "the reference schema is `localhost.sql`". Schema evolution is manual: operators are expected to apply ALTER TABLE statements by hand. The codebase has no record of which schema version is in production. The CRs proposed (CR-01 through CR-06) all add schema deltas and have no shared mechanism to ship them.
5. **No prepared statements.** TECHNICAL line 238 — "The class is **not** parameterised; SQL is concatenated. All values must flow through `quote()`". One-off audit catches the obvious cases; subtle bugs (e.g. operator-controlled order-by columns) hide from a `quote()`-only review.
6. **Globals everywhere.** TECHNICAL line 695 — `$core`, `$database`, `$mainframe`, `$eventCrossBar`, `$my`, `$appContext` are explicit globals. Testing is hard; refactoring is even harder. Modern PHP idioms (constructor injection, PSR-11 container) are absent.
7. **No CI / CD.** No GitHub Actions / GitLab CI / Jenkinsfile. No automated linting, no automated test runs, no static analysis (PHPStan, Psalm).
8. **No release management.** CHANGELOG.md is Czech-only, no semantic versioning (README line 169 — "unstructured incremental version scheme").
9. **No environment isolation.** README quick start uses a system-wide PHP install. Docker / Vagrant / Lando flows are not documented; reproducing a developer environment is bespoke per machine.
10. **No code-style enforcement.** TECHNICAL line 692 — "Style: PSR-1-ish". No `phpcs.xml`, no `phpcs` invocation in CI. Drift accumulates.

## 2. Affected docs / areas

- `README.md`
- `docs/TECHNICAL.md` §Runtime stack, §Coding conventions, §Extending the system
- `Makefile`
- New top-level files: `composer.json`, `phpunit.xml`, `phpcs.xml`, `phpstan.neon`, `.github/workflows/*.yml`
- New directory: `tests/` (mirroring `includes/` structure)
- New directory: `migrations/` (versioned SQL/PHP migrations)
- `services/service.php` (gains a `--migrate` flag)

## 3. Goals & non-goals

**Goals**

- Composer-managed dependencies, with PEAR replaced by maintained equivalents.
- PHPUnit harness with at least one test per DAO, billing rule, and commander.
- Migration framework (Phinx, Doctrine Migrations, or a thin in-house tool) shipping every schema change versioned.
- Replace ad-hoc `mysqli` SQL concatenation with prepared statements where the value is user-influenced; keep `quote()` for legacy code paths during the transition.
- CI pipeline (GitHub Actions) running lint + tests + static analysis on every PR.
- Semantic versioning starting at the next release. Tag every release; auto-generate release notes from conventional-commits.
- Dockerfile + `docker-compose.yml` for a one-command developer environment.
- Code-style enforcement (`phpcs --standard=PSR12` with project-specific exceptions).

**Non-goals**

- Full rewrite of the application. CRs 01–06 are the substantive functional changes; this CR is plumbing.
- Removing all globals at once — that breaks every module. Plan a slow migration to a PSR-11 container with adapters during transition.
- Switching to a framework (Symfony, Laravel). NetProvider stays plain PHP.
- Replacing MySQL with PostgreSQL.

## 4. Proposed change

### 4.1 Composer

`composer.json`:

```jsonc
{
    "name": "ftelf/netprovider",
    "type": "project",
    "license": "LGPL-2.1",
    "require": {
        "php": "^8.1",
        "ext-mysqli": "*",
        "ext-mbstring": "*",
        "ext-gettext": "*",
        "ext-zip": "*",
        "ext-openssl": "*",
        "phpmailer/phpmailer": "^6.9",
        "webklex/php-imap": "^5.5",
        "phpseclib/phpseclib": "^3.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.7",
        "robmorgan/phinx": "^0.16"
    },
    "autoload": {
        "psr-4": { "NetProvider\\": "includes/" }
    }
}
```

PEAR mapping:

| Old PEAR        | Replacement                          |
| --------------- | ------------------------------------ |
| `Mail`, `Mail_Mime`, `Net_SMTP` | `phpmailer/phpmailer`                |
| `Net_POP3`      | `webklex/php-imap` (POP3+IMAP)        |
| `Net_IPv4`      | Drop. The handful of call sites are replaced by inline PHP — `Utils::cidrToRange()` already exists per the docs. |

The `EmailUtil` and `EmailBankAccountList` classes get thin adapter facades so the rest of the code does not change overnight.

### 4.2 Migration framework (Phinx)

- Reference schema (`localhost.sql`) becomes the seed for migration `001_initial.sql`. Subsequent migrations are added per CR.
- `services/service.php --migrate` runs pending migrations.
- A `schemaversion` table records the versions applied. The web `index2.php` checks at startup whether all migrations are applied; if not, blocks login with a banner directing the operator to run `--migrate`.
- Down-migrations supported but optional.

Naming convention: `YYYYMMDDHHMMSS_short_slug.php` (Phinx default).

### 4.3 PHPUnit harness

- `tests/` directory mirrors `includes/`.
- `phpunit.xml` defines suites: `unit`, `integration` (the latter spins a MariaDB container via `docker-compose.test.yml`).
- Code coverage target after 12 months: 60% on `includes/`, 80% on `includes/billing/`. Initial PR seeds with the smoke tests called out in CRs 01–06.
- Fixtures: a `tests/fixtures/sql/seed.sql` builds a known-state database for integration tests.

### 4.4 Prepared-statement migration

- New helper `Database::prepare(string $sql, array $params): mysqli_stmt`. Existing `Database::query()` keeps working.
- New PR review checklist item: "Any new SQL with operator-influenced values uses `prepare()`."
- DAOs are migrated opportunistically; no big-bang rewrite. CR-02 (billing) is a natural place to start.

### 4.5 Continuous integration

`.github/workflows/ci.yml`:

- Triggers: push to `main`, every PR.
- Jobs:
  1. `lint` — `composer install`, `phpcs`, `phpstan analyse --level 5`.
  2. `unit` — `phpunit --testsuite unit`.
  3. `integration` — `docker-compose up -d mariadb`, run migrations, `phpunit --testsuite integration`.
  4. `i18n-coverage` — runs `make locales`, asserts no diff (catalogs committed).
  5. `docs-lint` — `markdownlint`, plus the cross-reference check from CR-05 §4.7.

Failed jobs block merge. PR template (also added) reminds contributors of the gates.

### 4.6 Docker / Compose

`Dockerfile`:

```dockerfile
FROM php:8.1.12-apache
RUN docker-php-ext-install mysqli mbstring gettext zip
RUN apt-get update && apt-get install -y locales \
    && sed -i '/cs_CZ.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction
RUN ln -sf /app/site /var/www/html
```

`docker-compose.yml` brings up `app`, `mariadb`, and an optional `mailcatcher`.

A second compose file `docker-compose.test.yml` adds `phpunit` and overrides envs for the integration suite.

### 4.7 Code-style enforcement

- `phpcs.xml` extends `PSR12` and adds rules for the project's existing conventions (table-prefix property names, `_`-prefix for internals).
- Pre-commit hook (via `pre-commit` framework) runs `phpcbf` on staged files before commit; CI runs `phpcs` strict-mode and fails the build on any violation.
- Initial run is large; one-shot PR formats the entire codebase. Subsequent diffs stay small.

### 4.8 Semantic versioning

- Adopt SemVer starting with the next release tag, version `2.0.0` (the breaking changes from CR-01 and CR-02 justify the major bump).
- Tag every release.
- `release-please` (or a hand-rolled equivalent) generates GitHub Releases from conventional-commit history.
- The bilingual CHANGELOG (CR-05) becomes the canonical release log; SemVer tags reference the CHANGELOG section by version anchor.

### 4.9 Globals migration plan

Long-term roadmap, out of this CR's scope but documented here as a placeholder:

- Phase A (this CR): no behavioral change. Add a `Container` (PSR-11) singleton accessible from `Mainframe::container()`. Globals continue to work.
- Phase B (next CR after this): migrate one module at a time. Module controllers receive their dependencies via `container->get(...)`.
- Phase C: globals removed; `defined('VALID_MODULE')` becomes a constructor argument.

## 5. Migration path

| Phase | Action                                                                                |
| ----- | ------------------------------------------------------------------------------------- |
| 1     | Land `composer.json`, `Dockerfile`, CI skeleton. No functional change for operators.  |
| 2     | Replace PEAR Mail with PHPMailer behind the existing `EmailUtil` facade. Soak.        |
| 3     | Replace PEAR Net_POP3 with webklex/php-imap; deploy alongside CR-03 §4.3 changes.      |
| 4     | Land Phinx; convert `localhost.sql` into migration 001; ship CR-01 through CR-06 schema deltas as numbered migrations. |
| 5     | Roll out `phpcbf` formatter PR.                                                        |
| 6     | Cut version tag `2.0.0` once the above are merged.                                    |

Operators upgrading from `1.x` to `2.0.0` follow a documented runbook: backup → `composer install --no-dev` → `php services/service.php --migrate` → restart.

## 6. Testing strategy

- **Self-hosting test:** the CI pipeline of this CR is what tests this CR — once green, the harness itself is verified.
- **PHPMailer smoke test:** send a test email from the new `EmailUtil` facade to a `mailcatcher` instance in compose; assert delivery.
- **POP3/IMAP smoke test:** spin a Dovecot container; deliver a sample SEPA XML message; run the new `webklex/php-imap`-based fetcher; assert the same `EmailList` row would be produced as with the legacy PEAR path (regression-fixture compare).
- **Migration round-trip:** apply all migrations to an empty DB; compare with a `mysqldump` of the legacy `localhost.sql` reference; assert structural equivalence (column types, indexes, FKs). Any drift is an intentional CR-driven schema evolution; document it in the migration's docblock.

## 7. Open questions

- Phinx vs Doctrine Migrations vs in-house tool: Phinx is the simplest and matches the project's plain-PHP ethos. Recommendation: Phinx.
- Should the legacy `localhost.sql` be retained as a quick-start fallback after Phinx is in place? Recommendation: keep it, but mark it "regenerated from migrations" and add a CI check that it stays in sync.
- Which static-analysis level? PHPStan level 5 is realistic given the global-heavy code; level 8 is the long-term target. Start at 5 and ratchet.
- Composer install or vendor-in: ship `vendor/` in the repo (simpler ops) or require operators to run `composer install` (smaller repo). Recommendation: do not commit `vendor/`; the Docker image bakes it in for the typical operator.
- PSR-12 imposed across `includes/`: tabs vs 4-space — the legacy code uses 4 spaces; PSR-12 requires 4 spaces; no migration pain.

## 8. Effort estimate & risk

- **Effort:** L. ~4–5 person-weeks; can be parallelised with CRs 01 / 02 since the schema-migration framework unblocks both.
- **Risk highlights:**
  - PHPMailer behavioral differences (HTML vs plaintext defaults, charset handling) can subtly break customer-facing emails. Smoke-test extensively.
  - First Phinx migration on a production database with hand-applied schema drift may fail. Provide a "current schema diff" tool that compares production against `001_initial.sql` and either auto-fixes or reports drift.
  - Globals removal is multi-CR work; the placeholder phase plan above must not slow this CR.
  - Composer install in production must be deterministic — commit `composer.lock`.

## 9. Out of scope

- Replacing the application framework (no framework adoption).
- PostgreSQL / SQLite support.
- Switching to PSR-3 logging immediately (use the existing `Database::log()` facade for now; PSR-3 is a future CR).
- Async background workers (e.g. RabbitMQ, Redis-Queue).
- Container orchestration (k8s manifests, Helm). Compose is sufficient for the small-ISP target.
- Source-level documentation generators (phpDocumentor) — covered separately if needed.
