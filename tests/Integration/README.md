# Integration tests

These tests exercise the real `Database` mysqli wrapper and the real DAOs against
a live MySQL — unlike the unit tier, which stubs the database. They cover the
billing read/write path (`ChargesUtil::proceedCharges`) and the bank-import path
(`AccountEntryUtil::proceedAccountEntries`), asserting the rows that actually get
persisted.

## Gating

The tier is **environment-gated**. With no `NP_IT_DB_HOST` set, every test calls
`markTestSkipped`, so `composer test` / `composer test:unit` stay runnable with no
MySQL. The tests run only when a database is configured via env vars.

| Env var        | Required | Default            | Meaning                                        |
|----------------|----------|--------------------|------------------------------------------------|
| `NP_IT_DB_HOST`| yes      | —                  | MySQL host. Use `127.0.0.1` to force TCP.      |
| `NP_IT_DB_PORT`| no       | `3306`             | MySQL port.                                    |
| `NP_IT_DB_NAME`| no       | `netprovider_test` | Database/schema name.                          |
| `NP_IT_DB_USER`| no       | `root`             | User.                                          |
| `NP_IT_DB_PASS`| no       | *(empty)*          | Password.                                      |
| `NP_IT_SCHEMA` | no       | —                  | Path to a schema `.sql` loaded once per run. Omit if the DB is pre-seeded. |

No schema or dump is committed to the repo. Supply `NP_IT_SCHEMA` at run time.
The target MySQL should allow the legacy `0000-00-00` zero-dates the schema stores
(the tests set `sql_mode='NO_AUTO_VALUE_ON_ZERO'` on their own session; if you
pre-load the schema yourself, load it under the same relaxed mode).

## Producing a schema file

Generate structure-only DDL (no customer data) from any current database or dump:

```bash
# from a live database:
mysqldump --no-data -u <user> -p <database> > /tmp/np_it_schema.sql

# or extract just the CREATE TABLE blocks from an existing dump (byte-safe):
LC_ALL=C awk '
  /^DROP TABLE IF EXISTS/ { print; next }
  /^CREATE TABLE/ { inblock=1 }
  inblock { print }
  /^) ENGINE/ { inblock=0; print "" }
' <some-dump>.sql > /tmp/np_it_schema.sql
```

## Running

Disposable MySQL via Docker, then the suite:

```bash
docker run -d --name np-it-mysql \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=netprovider_test \
  -p 33066:3306 mysql:8.0 --sql-mode=NO_AUTO_VALUE_ON_ZERO

NP_IT_DB_HOST=127.0.0.1 NP_IT_DB_PORT=33066 NP_IT_DB_NAME=netprovider_test \
NP_IT_DB_USER=root NP_IT_DB_PASS=root NP_IT_SCHEMA=/tmp/np_it_schema.sql \
composer test:integration

docker rm -f np-it-mysql
```

## Isolation

The production code under test commits its own transactions, so an outer rollback
cannot revert it. Each test instead declares the tables it owns in `$workingTables`
and the base class `TRUNCATE`s them before the test body runs — every test starts
from empty tables with reset `AUTO_INCREMENT`.
