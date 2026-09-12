# CR-01 — Security & Authentication Hardening

> **Status:** Draft for future consideration
> **Date:** 2026-05-01
> **Severity:** P0 (blocks any internet-facing deployment)
> **Effort:** L (4–6 weeks engineering + migration window)
> **Source documents:** `docs/TECHNICAL.md` §Security model (lines 645–688), `docs/USER_GUIDE.md` §First login (lines 63–82), `README.md` §Quick start (line 72 — "Heads up" warning)

---

## 1. Problem statement

The shipped documentation explicitly calls out several security weaknesses but treats them as "out of scope for this docs pass" (TECHNICAL.md line 687). Each weakness is independently exploitable; in aggregate they make the system unsafe for any deployment reachable from the public internet.

The concrete defects identified from documentation alone:

1. **Password hashing.** `Person.PE_password` is `MD5(plain)` (TECHNICAL §Authentication line 649; README line 70). MD5 is broken for password storage — pre-image attacks complete in seconds against rainbow-tables for any password under ~10 characters. No salt is used. No work factor.
2. **Session ID derivation.** `SE_sessionid = md5("$username$acl$logintime")` (TECHNICAL line 650; line 170 of the request-lifecycle diagram). The input space is small and partially known: username often public, ACL is one of three values, login time is a Unix timestamp ± a few seconds. Brute force against a captured cookie is feasible.
3. **Plain-text login.** USER_GUIDE line 77 documents that the login form posts the password unencrypted. TECHNICAL line 684 lists "Put the application behind HTTPS" only as a hardening checklist item rather than an enforced behavior.
4. **POP3 plaintext for bank statements.** TECHNICAL line 434 — "POP3 (port 110, plain — TLS is not currently used)". Bank statement traffic and POP3 credentials traverse the network in clear.
5. **No CSRF protection.** No mention anywhere in the docs of CSRF tokens, SameSite cookies, or origin checks for state-changing tasks. The module dispatcher accepts `task=remove`, `task=save` etc. on plain `$_POST` (TECHNICAL §Modules, lines 580–588).
6. **No login rate limiting.** USER_GUIDE line 81 only logs failures (`LEVEL_SECURITY`); no lockout, no progressive delay, no captcha. Brute force is bounded only by network throughput.
7. **Session IP binding fragility.** `SE_ip = REMOTE_ADDR` (TECHNICAL line 651). Any reverse proxy that does not forward the original client IP silently breaks the bind, and the docs only mention this as an FAQ aside (USER_GUIDE line 327). Mobile users on changing IPs are also affected.
8. **Plain-text secrets in `netprovider.ini`.** Database password, SMTP password, RouterOS password, SSH password — all plain (TECHNICAL §Configuration table). File permissions are advisory only ("Restrict file permissions … `chmod 640`", line 666).
9. **No password reset flow.** The only documented way to set a password is to insert an `MD5(...)` value directly into the database (README line 70).
10. **No multi-factor authentication option.** Not mentioned anywhere; the operator account is the system's most privileged role.

## 2. Affected docs / areas

- `docs/TECHNICAL.md` §Security model
- `docs/USER_GUIDE.md` §First login, §Troubleshooting and FAQ
- `README.md` §Quick start (the "Heads up" line)
- `config/netprovider.ini` (sample file)
- `templates/events/` (no password-reset template currently exists)
- Audit-log format (a new event type per security action)

## 3. Goals & non-goals

**Goals**

- Replace MD5 password storage with `password_hash()` / `password_verify()` (Argon2id default, bcrypt fallback).
- Replace session ID with cryptographically random 32-byte tokens (`random_bytes(32)`, hex-encoded).
- Make HTTPS mandatory at application layer (HSTS header, secure-cookie flag, refusal to render login over plain HTTP unless an explicit dev override is set).
- Switch POP3 to POP3S (port 995, TLS) with a documented fall-back for legacy banks.
- Add CSRF tokens to every state-changing form across modules.
- Add login rate limiting with progressive back-off (per-username + per-IP).
- Document and harden reverse-proxy IP forwarding (`X-Forwarded-For` allow-list).
- Provide a self-service password reset flow with email-token verification.

**Non-goals (YAGNI)**

- WebAuthn / FIDO2 hardware keys — desirable but separate CR.
- Federated SSO (OIDC, SAML) — out of scope for the small-ISP target.
- Encrypted-at-rest secrets (HashiCorp Vault, etc.) — file-permission hardening is sufficient for the deployment scale.
- IP allow-listing for the admin UI — operator-environment concern, not application-level.
- MFA — separate CR (CR-01b proposed below).

## 4. Proposed change

### 4.1 Password storage migration

**New columns on `person`:**

| Column            | Type           | Purpose                                                            |
| ----------------- | -------------- | ------------------------------------------------------------------ |
| `PE_password_v2`  | `varchar(255)` | Output of `password_hash($plain, PASSWORD_ARGON2ID)`               |
| `PE_password_alg` | `tinyint`     | `1 = MD5 legacy`, `2 = Argon2id` — drives migration logic           |

**Login path (PersonDAO):**

```
if (PE_password_alg == 1) {           // legacy
    if (md5(plain) === PE_password) {
        rehash to Argon2id, set PE_password_v2, PE_password_alg = 2
    } else { reject }
} else if (PE_password_alg == 2) {
    if (password_verify(plain, PE_password_v2)) {
        if (password_needs_rehash(...)) rehash inline
    } else { reject }
}
```

**Operator one-time migration tool.** New CLI flag `--rehash-passwords` walks every `person` row with `PE_password_alg = 1`. Because the plaintext is unknown, this script cannot rehash without input — instead it forces a password reset on next login by setting `PE_password_alg = 2` and `PE_password_v2 = NULL`, the user is then bounced through the reset flow described in §4.5.

### 4.2 Session ID hardening

- Replace `md5("$username$acl$logintime")` with `bin2hex(random_bytes(32))` (64 hex chars, 256 bits of entropy).
- Cookie attributes: `Secure; HttpOnly; SameSite=Lax; Path=/`. The `Secure` flag is enforced unconditionally; the dev override mentioned in §4.3 also controls this.
- Session table grows by one column `SE_useragent` (varchar 512); a UA mismatch invalidates the session (best-effort, defeated by attacker reproducing the UA, but raises the bar).
- Session timeout remains 30 min sliding (currently hardcoded — moves to `[Security] Session idle timeout` in `netprovider.ini`).

### 4.3 HTTPS enforcement

- New `[Security]` block in `netprovider.ini`:
  - `Require HTTPS` (bool, default true)
  - `Allow plain HTTP for development` (bool, default false)
- When `Require HTTPS = true` and `$_SERVER['HTTPS']` is empty/`off`:
  - `site/index.php` and `site/index2.php` issue `301` to the same URL with `https://`.
  - HSTS header `Strict-Transport-Security: max-age=31536000; includeSubDomains` on every response.
- The dev override is loud: an `LEVEL_WARNING` log entry on every request, plus a banner in the UI.

### 4.4 POP3S for bank statement ingestion

- `BankAccount` table: add `BA_emailprotocol` (`POP3 = 1`, `POP3S = 2`, default 2).
- `EmailBankAccountList::downloadNewAccountLists()` chooses port + TLS based on `BA_emailprotocol`.
- Migration: existing rows default to `POP3S`; operators who genuinely cannot reach POP3S downgrade per row through the bank-account edit form. The form shows a warning when POP3 is selected.
- POP3S validates the server certificate against the system trust store. A new `BA_emailcafile` column (varchar 255, nullable) lets operators pin a CA bundle.

### 4.5 CSRF tokens

- New helper `Csrf::getToken()` stores a 32-byte token in `$_SESSION['CSRF']` (per session, regenerated every 24 h or on privilege escalation).
- `html_*` view helpers emit `<input type="hidden" name="_csrf" value="...">` on every form.
- `MainFrame::dispatch()` validates `$_POST['_csrf']` against the session token for every task that mutates state. Bare `$_GET` reads are exempt; `$_POST` and any `task=remove` / `task=save` etc. are not. Mismatches log `LEVEL_SECURITY` and redirect to the previous page with the standard alert.
- AJAX endpoints (file upload, mass message, scripts) read the token from a `X-NetProvider-CSRF` header.

### 4.6 Login rate limiting

- New table `loginattempt`:
  - `LA_id`, `LA_username`, `LA_ip`, `LA_time`, `LA_success` (bool).
- Policy in `[Security]`:
  - `Login rate window seconds` (default 900)
  - `Login rate failures before lockout` (default 5)
  - `Login rate lockout seconds` (default 900)
- Both per-username and per-IP buckets are checked. After lockout, login form returns the standard "invalid credentials" message — no enumeration of the lockout state.
- A new `clean-up` sub-task purges `loginattempt` rows older than `Login rate window seconds * 4`.

### 4.7 Reverse-proxy IP forwarding

- `[Security] Trusted proxy CIDRs` — comma-separated list (default empty, i.e. direct exposure).
- When `REMOTE_ADDR` matches one of the listed CIDRs and `X-Forwarded-For` is present, take the **right-most** entry that is not in the trusted CIDR list as the client IP. Otherwise use `REMOTE_ADDR`.
- This trusted IP is what `SE_ip` binds against, what login rate limiting buckets on, and what the audit log records.

### 4.8 Password reset flow

- Self-service URL `index.php?reset=1`:
  1. User enters username (no enumeration: response is always "if the account exists, instructions sent").
  2. System inserts row in new `passwordreset` table (`PR_personid`, `PR_token`, `PR_expires`, `PR_used`).
  3. Email rendered from a new template `templates/events/password_reset.txt` (English + Czech).
  4. The link `index.php?reset=2&token=...` validates and shows the new-password form. On submit, hash is stored, token marked used, an `LEVEL_SECURITY` log entry is written.
- Tokens expire after 60 minutes; reuse is rejected.

### 4.9 Audit log additions

- New `Log::LEVEL_*` values are not needed; existing `LEVEL_SECURITY` covers all events.
- New event sources: `auth.login_failed_rate_limited`, `auth.password_changed`, `auth.password_reset_requested`, `auth.password_reset_consumed`, `auth.session_invalidated_ua_mismatch`, `csrf.token_mismatch`.
- These are free-form strings prefixed at the call site; the existing `log` table needs no schema change.

## 5. Migration path

| Phase | Duration | Backward compat                                                                                  |
| ----- | -------- | ------------------------------------------------------------------------------------------------ |
| 1     | week 1–2 | Add new columns. Login path supports both legacy MD5 and Argon2id. Existing sessions still valid. |
| 2     | week 3   | HTTPS enforcement in monitoring mode (warn-only). CSRF tokens emitted but not yet validated.      |
| 3     | week 4   | Hard cut-over: HTTPS enforced, CSRF validated, rate limiting active. Force-rotate all sessions.   |
| 4     | week 5   | Bulk password-reset email to every user account. Old MD5 hashes become unusable after first reset. |
| 5     | week 6   | Drop `PE_password` column once telemetry shows no `PE_password_alg = 1` rows for 14 days.         |

Operators are notified through the audit log at the start of each phase; the UI displays a deployment phase banner for super-administrators.

## 6. Testing strategy

- **Unit tests** (PHPUnit — see CR-07 Modernization for the harness):
  - Password rehash on login when `PE_password_alg = 1`.
  - `password_needs_rehash` triggers re-storage.
  - Session ID generation has the documented length and entropy distribution.
  - CSRF validation rejects missing / mismatched tokens.
  - Rate-limit bucket increment / reset / lockout behavior.
  - Reverse-proxy IP resolution against representative `X-Forwarded-For` strings.
- **Integration tests** (against a throwaway MySQL container):
  - Full login → session → privileged action → logout cycle with HTTPS on.
  - POP3S end-to-end against a Dovecot test instance with a self-signed cert + custom CA file.
  - Password reset email link → reset → re-login.
- **Manual verification:**
  - OWASP ZAP baseline scan against the deployed instance with `Require HTTPS = true`.
  - `nmap -sV` against the host shows port 80 redirecting and port 443 only.

## 7. Open questions

- Argon2id parameters — accept PHP defaults (memory 65536 KB, time 4, threads 1) or tune for the target hardware? Recommendation: start with defaults, measure login latency, raise memory if < 500 ms.
- Should the password reset form require the old password when invoked from inside an active session? Recommendation: yes (the self-service flow described in §4.8 is for forgotten passwords; in-session change is a separate page that does require the old password).
- How to handle service accounts that never log in interactively (the cron user)? Recommendation: a `PE_systemaccount` flag (bool) exempts those rows from the password-reset bulk email and from rate limiting.

## 8. Effort estimate & risk

- **Effort:** L. ~4 person-weeks engineering, plus a 1–2 week migration window with operator hand-holding.
- **Risk highlights:**
  - Bulk password reset email blocks all logins for everyone who misses the email — coordinate with billing-cycle customer comms.
  - HTTPS enforcement breaks any embedded customer-facing widget that still hits `http://`. Catalog those before phase 3.
  - POP3S fallback to POP3 is the most operationally fragile path; some Czech banks historically deprecated POP3S only on certain mailbox tiers.

## 9. Out of scope

- MFA / 2FA: tracked as CR-01b (future).
- Hardware-token (WebAuthn) login.
- Encrypted-at-rest secrets store.
- API tokens for headless integrations (no such integrations exist today).
- IP allow-listing of the admin UI (deployment-environment concern).
