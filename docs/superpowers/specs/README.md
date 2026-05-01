# Change Request Index

Documentation-driven audit of NetProvider, dated **2026-05-01**. Each entry below is a self-contained design spec for future consideration — none of these have been scheduled yet. Specs were derived from `README.md`, `docs/TECHNICAL.md`, `docs/USER_GUIDE.md`, `CHANGELOG.md`, `LICENSE.md`, and `CLAUDE.md`. No source-code verification was performed; findings are doc-grounded.

## 2026-05-01 audit batch

| ID    | Slug                  | Severity | Effort | Spec                                                                    |
| ----- | --------------------- | :------: | :----: | ----------------------------------------------------------------------- |
| CR-01 | security-auth         | **P0**   | L      | [security-auth-design](2026-05-01-cr-security-auth-design.md)            |
| CR-02 | billing-logic         | **P1**   | M      | [billing-logic-design](2026-05-01-cr-billing-logic-design.md)            |
| CR-03 | concurrency-cron      | **P1**   | S      | [concurrency-cron-design](2026-05-01-cr-concurrency-cron-design.md)      |
| CR-04 | network-commander     | P2       | M      | [network-commander-design](2026-05-01-cr-network-commander-design.md)    |
| CR-05 | doc-inconsistencies   | P2       | S      | [doc-inconsistencies-design](2026-05-01-cr-doc-inconsistencies-design.md) |
| CR-06 | i18n-gaps             | P2       | S–M    | [i18n-gaps-design](2026-05-01-cr-i18n-gaps-design.md)                    |
| CR-07 | modernization         | P2       | L      | [modernization-design](2026-05-01-cr-modernization-design.md)            |

### Severity legend

- **P0** — Blocks safe production use; ship as soon as feasible.
- **P1** — Causes silent financial / state errors under realistic conditions.
- **P2** — Operational rigidity, technical debt, or doc-quality friction.

### Effort legend

- **S** — ≤ 2 person-weeks
- **M** — 2–4 person-weeks
- **L** — 4+ person-weeks

## Cross-spec dependencies

```
CR-07 (Modernization)
   │ provides composer + phpunit + migrations
   ▼
CR-01 (Security)            ← strongest dependency on the migration framework
CR-02 (Billing)             ← schema deltas need migrations
CR-03 (Concurrency)         ← schema delta (servicelock, popfetchcheckpoint, servicerun)
CR-04 (Network commander)   ← optional schema delta (deviceprofile, ip.IP_haschargeid)
CR-05 (Docs)                ← independent
CR-06 (i18n)                ← schema delta (PE_locale)
```

Recommended sequencing:

1. CR-05 (docs) — fastest, lowest risk, blocks nothing.
2. CR-07 (modernization plumbing) — enables every subsequent CR's tests and migrations.
3. CR-01 (security) — once migration framework in place.
4. CR-03 (concurrency) — small, important, independent of CR-02.
5. CR-02 (billing) — needs migration framework + tests; biggest functional change.
6. CR-06 (i18n) — gated on CR-07 for the migration of `utf8mb4`.
7. CR-04 (network commander) — operational improvement; lowest urgency.

## Related follow-ups (not yet specced)

- **CR-01b** — MFA / WebAuthn for operator accounts.
- **CR-05b** — Rename `HE_notifypersonid` (column rename + dispatcher correctness).
- **CR-05c** — Implement or remove `PaymentReceivedEvent`.
- **CR-08** — IPv6 support across both network commanders.
- **CR-09** — Multi-currency `PersonAccount` with FX conversion.
- **CR-10** — Pro-rated activation/deactivation in the billing engine.
- **CR-11** — Globals → PSR-11 container migration (Phase B from CR-07 §4.9).

## Methodology

- Specs follow the brainstorming-skill template: problem statement, affected areas, goals/non-goals, proposed change, migration path, testing strategy, open questions, effort/risk, out of scope.
- Each finding cites the source document and line number where applicable.
- This audit is documentation-only by request — no code verification was performed. CRs that propose code-level work flag any assumptions for verification during implementation.
