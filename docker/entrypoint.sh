#!/bin/sh
# Container entrypoint: block until MySQL is reachable, apply schema migrations
# (idempotent — Phinx tracks applied migrations in phinxlog), then run the CMD.
#
# Admin creation is intentionally NOT run here: `composer db:seed` regenerates the
# admin password on each run, so it stays a documented one-time manual step
# (see README "Run with Docker"). This entrypoint is safe to run on every start.
set -eu

DB_HOST="$(printenv NP_IT_DB_HOST 2>/dev/null || echo db)"
DB_PORT="$(printenv NP_IT_DB_PORT 2>/dev/null || echo 3306)"

echo "[entrypoint] waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
i=0
until php -r '
    $c = @mysqli_connect(
        getenv("NP_IT_DB_HOST") ?: "db",
        getenv("NP_IT_DB_USER") ?: "netprovider",
        getenv("NP_IT_DB_PASS") ?: "netprovider",
        "",
        (int) (getenv("NP_IT_DB_PORT") ?: 3306)
    );
    exit($c ? 0 : 1);
' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then
        echo "[entrypoint] ERROR: MySQL not ready after ~120s; giving up." >&2
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] MySQL is up."

echo "[entrypoint] applying migrations (phinx migrate) ..."
php vendor/bin/phinx migrate

echo "[entrypoint] starting: $*"
exec "$@"
