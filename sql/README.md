# Database schema & seed

Canonical SQL for netprovider. These files are the single source of truth for
the schema — the app quick-start, the integration tier, and Phinx migration 001
all apply `schema.sql`.

| File         | Purpose                                                                 |
|--------------|-------------------------------------------------------------------------|
| `schema.sql` | Structure only (no data). All 22 tables. utf8mb4 / utf8mb4_czech_ci.     |
| `seed.sql`   | Minimal loginable seed (one super-admin). Real setups only — not tests. |

## Quick-start (manual)

```bash
mysql -u root -p netprovider < sql/schema.sql   # structure
mysql -u root -p netprovider < sql/seed.sql     # admin/changeme login (change it!)
```

## Via Phinx (versioned)

```bash
vendor/bin/phinx migrate                    # applies schema.sql as migration 001
vendor/bin/phinx seed:run -s AdminSeeder    # optional: minimal admin login
```

Connection is resolved by `phinx.php` (env vars → `config/netprovider.ini` → defaults).

## Integration tests

`tests/Integration/IntegrationTestCase` defaults `NP_IT_SCHEMA` to `sql/schema.sql`,
so the integration tier needs only a database host. See `tests/Integration/README.md`.

## Regenerating from a fresh production dump

`schema.sql` was derived from a structure-only extract of a production `mysqldump`,
then transformed. To regenerate from a new dump `<dump>.sql`:

```bash
# 1. structure only (byte-safe extraction of CREATE TABLE blocks)
LC_ALL=C awk '
  /^DROP TABLE IF EXISTS/ { print; next }
  /^CREATE TABLE/ { inblock=1 }
  inblock { print }
  /^\) ENGINE/ { inblock=0; print "" }
' <dump>.sql > /tmp/structure_raw.sql

# 2. strip AUTO_INCREMENT offsets, normalise charset to utf8mb4
LC_ALL=C sed -e 's/ AUTO_INCREMENT=[0-9]\{1,\}//g' \
    -e 's/utf8mb3_czech_ci/utf8mb4_czech_ci/g' \
    -e 's/CHARSET=utf8mb3/CHARSET=utf8mb4/g' \
    /tmp/structure_raw.sql > /tmp/structure_utf8mb4.sql
```

Then re-attach the header/footer from the current `schema.sql`, drop the dead
`configuration` table if the dump still carries it, and verify:

- `grep -c 'CREATE TABLE' sql/schema.sql` → 22
- `grep -c 'utf8mb3\|AUTO_INCREMENT=' sql/schema.sql` → 0

**Never commit the raw production dump** — it contains customer PII and is
git-ignored (`daily_netprovider_*.sql`).
