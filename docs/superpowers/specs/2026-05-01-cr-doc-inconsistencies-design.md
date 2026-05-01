# CR-05 — Documentation Inconsistencies & Gaps

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P2 (no functional defect, but creates onboarding friction and operator confusion)
> **Effort:** S (1 week)
> **Source documents:** `docs/TECHNICAL.md`, `docs/USER_GUIDE.md`, `README.md`, `CHANGELOG.md`, `LICENSE.md`

---

## 1. Problem statement

The shipped documentation set (`README.md`, `docs/TECHNICAL.md`, `docs/USER_GUIDE.md`, `CHANGELOG.md`, `LICENSE.md`, `CLAUDE.md`) contains a number of typos, internal contradictions, missing details, and ambiguous statements that compound during onboarding. The findings below are sourced from a docs-only audit and grouped by the document they live in.

### 1.1 TECHNICAL.md

1. **Typo: `removeChangeEntriesOutOfScope` (line 421)**. Should be `removeChargeEntriesOutOfScope`. The neighbouring narrative refers to "charge entries", and the function describes operating on `chargeentry` rows.
2. **Conflict on advance horizon units (lines 376, 385).** Diagram says "now + 'Blank charges advance count' months" — implying the unit is months, hardcoded. Below, line 385 confirms "default … is **6** months". The unit-implicit naming ties the property to the monthly-only billing regime; documenting it as months is correct today but inconsistent with the future quarterly/yearly support proposed in CR-02.
3. **Missing config keys (line 145).** "The `[Network Device]` block has two keys (`IP accounting`, `IP filter`) that are referenced by class constants in `Core` … but are not present in the shipped sample `netprovider.ini`." The remediation ("add them when running the Linux commander") is reactive — the sample should ship with all keys present, commented.
4. **Web request lifecycle session timeout is hardcoded (line 172).** "`SessionDAO::removeTimeoutedSession(1800)`" — 1800 seconds is documented but the constant is not surfaced as configurable. CR-01 §4.2 promotes it to `[Security] Session idle timeout`; this spec captures the doc gap.
5. **`HE_notifypersonid` ambiguity (line 548).** "If `HE_notifypersonid` is set, the destination is the configured back-office address." But the column name implies it stores a Person FK (i.e. an internal user to notify). Either the column is misnamed, or the behavior is misdocumented. Code-level resolution lives in CR-05b (out of scope here); the doc must at minimum disambiguate.
6. **PaymentReceivedEvent dead code (line 533).** Class exists but the corresponding `HandleEvent::TYPE_PAYMENT_RECEIVED` is "currently commented out". Either ship it or remove the dangling class; doc should at least mark it as deprecated.
7. **utf8 vs utf8mb4 (line 758).** The dump uses `utf8` (3-byte). Modern MySQL recommends `utf8mb4`. The doc says "make sure the application connection is also `utf8mb4`" but `Database::__construct` runs `SET CHARACTER SET utf8` and `SET NAMES 'utf8'`. Self-contradiction.
8. **`db->query_batch` rollback semantics (line 220).** "wrapped in START TRANSACTION/COMMIT" but does not say what happens on PHP fatal error (no automatic ROLLBACK on script termination unless explicitly wired).
9. **`Database::insertObject` "INSERT … RETURNING" comment (line 224).** MySQL does not support `RETURNING`. The class actually uses `mysqli_insert_id()`. Doc inaccuracy.
10. **`MainFrame::getPath()` fallback (line 186).** "falls back to `com_admin` when an unknown module is requested." This silent reroute can mask typo bugs in module-name URLs and surprise an admin. Doc should advise a 404 alternative or a visible warning.
11. **Counter parsing fragility (line 514).** "parsed against `IPTABLES_LIST_ENTRY` regex" — the doc lists this as fact without flagging the iptables-version sensitivity discussed in CR-04 §1.7.

### 1.2 USER_GUIDE.md

1. **Sessions time out after 30 minutes (line 78).** Matches the hardcoded constant but disagrees with any operator who tries to lengthen it through config — there is no config key today.
2. **POP3 statement-number gap email at year boundary (line 153).** "Continuity is verified by statement number — gaps trigger a critical email to the supervisor." No mention of the per-year reset; first statement of new year may legitimately not be `N+1` of last year's last.
3. **Cron schedule recommendation (lines 178–183).** Daily 02:30 is fine for billing, but the network sync at every 5 minutes (line 202) collides with the billing pass — see CR-03. Doc currently presents both as independent.
4. **Manual cash credit example (line 315).** "**New** entry, source = Cash or Discount". Operators expect the source list to also include "Refund — removed entry" once CR-02 ships; doc should be updated then.
5. **Refund a paid charge (line 317).** "**User's accounts → Return payment** on the relevant `chargeentry`". Does not state whether the charge entry is flipped back to PENDING (re-billed next run). CR-02 §4.8 fixes the underlying behavior; the doc reflects the resolution.
6. **Reverse-proxy IP binding FAQ (line 327).** Buried in FAQ; should be promoted to the security model section because it changes how login rate limiting and audit IPs work (see CR-01).

### 1.3 README.md

1. **Cron entries cosmetic spacing (lines 128–131).** `30 2  * * *` (extra space), `*/5  * * * *` (extra space). Cron tolerates it but the inconsistency is sloppy.
2. **License placeholder (line 176).** "currently the file is a placeholder; restore the upstream LGPL-2.1 text before redistributing." This is itself a compliance defect — every distribution is technically non-conformant.
3. **Quick start `localhost.sql` import as `netprovider` user (line 61).** The shipped `localhost.sql` may include `CREATE TABLE` statements requiring privileges the `netprovider` user lacks under typical grants. Doc should advise running the schema import as root, then granting.

### 1.4 CHANGELOG.md

1. **Czech-only.** Limits non-Czech contributors. Cross-references in TECHNICAL.md (line 50: "Czech changelog, oldest -> newest") acknowledge this without proposing a fix.
2. **No semantic versions.** README line 169 — "unstructured incremental version scheme". Combined with the Czech-only changelog, downstream consumers cannot tell which release is which.

### 1.5 LICENSE.md

1. **Placeholder.** README itself flags this. Compliance fix needed.

### 1.6 Cross-document

1. **CLAUDE.md, TECHNICAL.md, USER_GUIDE.md repeat overlapping information.** The advance horizon ("six months ahead") is restated in all three; a future change requires editing three files. Single source of truth desirable.
2. **No `docs/CHANGES.md` or `docs/ROADMAP.md`** capturing the CR queue these specs introduce. After this CR ships, an index entry under `docs/superpowers/specs/README.md` is needed.
3. **No data-flow diagrams beyond ASCII.** TECHNICAL §Domain model is ASCII only. Renderable diagrams (Mermaid) help newcomers.

## 2. Affected docs

- `README.md`
- `docs/TECHNICAL.md`
- `docs/USER_GUIDE.md`
- `CHANGELOG.md`
- `LICENSE.md`
- `CLAUDE.md`
- `config/netprovider.ini` (sample file — referenced by docs)

## 3. Goals & non-goals

**Goals**

- Every typo and internal contradiction listed in §1 is fixed.
- The sample `netprovider.ini` ships with every config key present (uncommented if required, commented if optional).
- Single-source-of-truth: defaults for `Blank charges advance count`, session timeout, etc. are stated in `netprovider.ini` only; other docs reference it.
- CHANGELOG is bilingual (English + Czech) starting with the upcoming release.
- LICENSE.md contains the upstream LGPL-2.1 text verbatim.
- A new `docs/superpowers/specs/README.md` indexes CR queue items with status.
- Mermaid versions of the domain model and request lifecycle diagrams in addition to the ASCII originals.
- Doc-lint pre-commit hook (markdownlint + a custom rule for "all `[Section] Key` references in TECHNICAL.md exist in the sample ini").

**Non-goals**

- Translating the entire `CHANGELOG.md` history retroactively. Only future entries are bilingual; an upfront translation is a separate effort.
- Replacing the docs format (Markdown stays).
- Generating docs from code annotations (no annotations exist; would couple poorly).

## 4. Proposed change

### 4.1 Typo and accuracy fixes

| Doc / line                              | Change                                                                                       |
| --------------------------------------- | -------------------------------------------------------------------------------------------- |
| TECHNICAL.md line 421                   | `removeChangeEntriesOutOfScope` → `removeChargeEntriesOutOfScope`.                            |
| TECHNICAL.md line 224                   | `INSERT … RETURNING` → "INSERT then read `mysqli_insert_id()` into `$obj->$pkField`".       |
| TECHNICAL.md line 220                   | Add note: "Wraps the batch in `START TRANSACTION` / `COMMIT`. Failures throw and abort the batch; **PHP fatal errors during the batch leave the transaction open** — wrap callers in `try/finally` to call `rollback()` defensively." |
| TECHNICAL.md line 758                   | Recommend `utf8mb4` and document the application's current `utf8` setting as a known gap (cross-link to CR-06).        |
| TECHNICAL.md line 186                   | Add note: "The `com_admin` fallback is intentional; consider opening a 404 if the operator hits a typo'd URL — see CR-05 §1.1.10." |
| TECHNICAL.md line 533                   | Mark `PaymentReceivedEvent` "deprecated, awaiting CR-05b".                                  |
| TECHNICAL.md §HE_notifypersonid         | Disambiguate: "Despite the column name, `HE_notifypersonid` currently routes to the configured back-office address — the field is reused as a flag rather than a foreign key. CR-05b will rename the column to `HE_notifybackoffice`." |
| README.md cron block                    | Normalize whitespace.                                                                        |
| README.md license note                  | After the file is restored (see §4.4), drop the "currently a placeholder" sentence.          |
| README.md quick start                   | Add note: "Run `mysql -u root -p netprovider < localhost.sql` as a privileged user; grant `netprovider`@`localhost` minimal DML rights afterwards."     |

### 4.2 Sample `netprovider.ini` completeness

- Add every key referenced anywhere in the codebase or docs, with a sensible default (where one exists) and a one-line comment.
- Group `[Security]` is introduced (see CR-01).
- A new top-of-file comment lists which keys are required vs optional, and which sections own which behaviors.

### 4.3 Single-source-of-truth defaults

- Defaults table is owned by `netprovider.ini` (the sample). Other docs link to the sample and never restate the value.
- The CHANGELOG entry that ships this CR notes that future changes should update only the sample.
- A doc-lint check (§4.7) enforces the rule.

### 4.4 LICENSE restoration

- Replace the placeholder with the upstream LGPL-2.1 text from <https://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt>, verbatim.
- Verify the README badge points to the same URL.

### 4.5 Bilingual CHANGELOG

- Two top-level files: `CHANGELOG.en.md` and `CHANGELOG.cs.md`. The legacy `CHANGELOG.md` becomes a stub linking to both.
- Each release adds an entry to both files. PR template (introduced in CR-07 Modernization) gates the merge on both files being touched.

### 4.6 CR queue index

- New file `docs/superpowers/specs/README.md` lists every CR with: ID, slug, severity, effort, status, target release.
- Each spec carries a YAML front-matter block enabling automated ingestion.

### 4.7 Doc-lint hook

- `pre-commit` hook (introduced in CR-07) runs:
  - `markdownlint` with project rules (`.markdownlint.json`).
  - Custom Python script that parses every `[Section] Key` reference in `docs/*.md` and verifies each appears in `config/netprovider.ini` (sample). Mismatch → exit 1.
  - Custom check that any `INI key` change in `netprovider.ini` is also reflected in the relevant docs section. Bidirectional.

### 4.8 Mermaid renditions

- Add Mermaid versions of:
  - Domain model (TECHNICAL §Domain model)
  - Web request lifecycle (TECHNICAL §Web request lifecycle)
  - Billing pipeline (TECHNICAL §Billing engine)
- Keep the ASCII originals immediately above. Markdown viewers that do not render Mermaid still see something useful.

### 4.9 Reverse-proxy section promotion

- USER_GUIDE FAQ entry on `SE_ip` moves to TECHNICAL §Security model as a first-class subsection. The FAQ keeps a one-line cross-link.

## 5. Migration path

Documentation-only changes; no schema or code impact. Single PR is sufficient. Reviewers should sign off on each subsection in §4.

## 6. Testing strategy

- `markdownlint` on every changed file before merge.
- Custom `[Section] Key` cross-check script run in CI.
- Mermaid renditions verified by viewing in GitHub/GitLab preview.
- Bilingual CHANGELOG verified by a non-Czech-speaking reviewer.
- LICENSE.md byte-compared against the upstream text.

## 7. Open questions

- Do operators who use the sample `netprovider.ini` overwrite it on upgrade or hand-merge? If overwrite, the new keys disappear. Recommendation: ship sample as `netprovider.ini.dist`; the install step copies to `netprovider.ini` only if the latter does not exist.
- Should the spec index file live in `docs/superpowers/specs/README.md` or `docs/CHANGES.md`? Recommendation: keep at `docs/superpowers/specs/README.md` to colocate with the specs themselves; reference from a top-level `docs/README.md`.
- Mermaid licensing in static-site previews: GitHub renders natively, GitLab requires a setting flip; document the fact.

## 8. Effort estimate & risk

- **Effort:** S. ~1 person-week.
- **Risk highlights:**
  - Bilingual CHANGELOG creates a two-files-to-sync trap; mitigated by the PR template gate.
  - LICENSE substitution must be done carefully — replacing with the wrong LGPL variant (3.0) would silently change project terms.

## 9. Out of scope

- Renaming `HE_notifypersonid` (CR-05b — schema change, separate spec).
- Implementing or removing `PaymentReceivedEvent` (CR-05b).
- Translating CHANGELOG history retroactively.
- Producing API reference docs from code (no doc-comments to harvest).
- Replacing Markdown with reStructuredText / docs-as-code stack.
