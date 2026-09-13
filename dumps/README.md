# dumps/

Local-only landing zone for production database dumps.

**Never commit a dump.** Dumps contain real customer PII. Everything in this
folder is git-ignored (see `.gitignore`) except this README and that ignore
file, so any dump you place here — whatever its name — stays out of git.

## Uses

- Restoring a working copy of the app locally (`mysql netprovider < dumps/<dump>.sql`).
- Regenerating the canonical structure-only `sql/schema.sql` from a fresh dump —
  see [../sql/README.md](../sql/README.md) for the byte-safe extraction steps.

The stripped, data-free schema in `sql/schema.sql` is what belongs in the repo;
the raw dump never does.
