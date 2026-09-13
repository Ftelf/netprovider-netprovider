<?php
/**
 * Base class for integration tests that exercise the real `Database` mysqli
 * wrapper against a live MySQL — NOT the DatabaseStub the unit tier uses.
 *
 * Environment-gated by design (Test philosophy: Isolated/Deterministic). The
 * unit tier must stay runnable with no MySQL, so when `NP_IT_DB_HOST` is unset
 * every integration test cleanly `markTestSkipped`s instead of erroring.
 *
 * Required env to activate:
 *   NP_IT_DB_HOST   host of a disposable MySQL (e.g. 127.0.0.1)
 *   NP_IT_DB_PORT   port (default 3306)
 *   NP_IT_DB_NAME   database name (default netprovider_test)
 *   NP_IT_DB_USER   user (default root)
 *   NP_IT_DB_PASS   password (default empty)
 *   NP_IT_SCHEMA    optional path to a schema .sql loaded once per process
 *                   (CREATE TABLE DDL). Defaults to the committed sql/schema.sql;
 *                   set to a non-file value if the target DB is pre-seeded.
 *
 * The canonical DDL ships in the repo at sql/schema.sql (data-free, derived from
 * the production dump — see sql/README.md). The integration tier loads it by
 * default, so only a database host is required to run.
 *
 * Isolation: the production code under test commits its own transactions, so an
 * outer-rollback strategy cannot revert it. Instead each test declares the
 * tables it owns in `$workingTables` and this base TRUNCATEs them before the
 * test body runs, so every test starts from empty tables and resets
 * AUTO_INCREMENT. The schema carries no FOREIGN KEY constraints, so truncation
 * order is immaterial.
 */

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class IntegrationTestCase extends PHPUnitTestCase
{
    protected ?Database $db = null;

    /** Loaded at most once per PHP process, guarded across all subclasses. */
    private static bool $schemaLoaded = false;

    /** Tables this test owns; TRUNCATEd before each test. Override per class. */
    protected array $workingTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('NP_IT_DB_HOST');
        if ($host === false || $host === '') {
            $this->markTestSkipped(
                'Integration DB not configured. Set NP_IT_DB_HOST/PORT/NAME/USER/PASS '
                . '(and optionally NP_IT_SCHEMA to a schema .sql) to run the integration tier.'
            );
        }

        $port = (int) (getenv('NP_IT_DB_PORT') ?: 3306);
        $name = getenv('NP_IT_DB_NAME') ?: 'netprovider_test';
        $user = getenv('NP_IT_DB_USER') ?: 'root';
        $pass = getenv('NP_IT_DB_PASS');
        $pass = ($pass === false) ? '' : $pass;

        // `Database` wraps `new mysqli($host,$user,$pass,$db)` with no port arg, so a
        // container mapped to a non-default host port is reached via the default-port ini.
        ini_set('mysqli.default_port', (string) $port);

        self::loadSchemaOnce($host, $user, $pass, $name, $port);

        $this->db = new Database($host, $user, $pass, $name);

        // Match the production dump's mode: permit the legacy '0000-00-00' zero-dates
        // the schema stores for open-ended HasCharge and unrealized ChargeEntry rows.
        $this->db->setQuery("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");
        $this->db->query();

        // Point the globals the DAOs and billing code read at the real connection.
        $GLOBALS['database'] = $this->db;
        $GLOBALS['eventCrossBar'] = null;

        $this->truncateWorkingTables();
    }

    private static function loadSchemaOnce(string $host, string $user, string $pass, string $name, int $port): void
    {
        if (self::$schemaLoaded) {
            return;
        }
        self::$schemaLoaded = true; // set first so a missing/failed schema is not retried per test

        // Default to the committed canonical schema; override with NP_IT_SCHEMA
        // to point at a different DDL, or set it to a non-file value to assume
        // the target DB is already seeded.
        $schemaPath = getenv('NP_IT_SCHEMA');
        if ($schemaPath === false || $schemaPath === '') {
            $schemaPath = dirname(__DIR__, 2) . '/sql/schema.sql';
        }
        if (!is_file($schemaPath)) {
            return; // target DB is assumed pre-seeded
        }

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new RuntimeException('NP_IT_SCHEMA load: cannot read ' . $schemaPath);
        }
        // Multi-statement DDL needs mysqli::multi_query, which the Database wrapper
        // does not expose; use a throwaway raw connection just for the load.
        $m = new mysqli($host, $user, $pass, $name, $port);
        if ($m->connect_errno) {
            throw new RuntimeException('NP_IT_SCHEMA load: connect failed: ' . $m->connect_error);
        }
        if (!$m->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'")) {
            $err = $m->error;
            $m->close();
            throw new RuntimeException('NP_IT_SCHEMA load: SET sql_mode failed: ' . $err);
        }
        if (!$m->multi_query($sql)) {
            $err = $m->error;
            $m->close();
            throw new RuntimeException('NP_IT_SCHEMA load failed: ' . $err);
        }
        do {
            if ($res = $m->store_result()) {
                $res->free();
            }
        } while ($m->more_results() && $m->next_result());
        if ($m->errno) {
            $err = $m->error;
            $m->close();
            throw new RuntimeException('NP_IT_SCHEMA load failed mid-stream: ' . $err);
        }
        $m->close();
    }

    protected function truncateWorkingTables(): void
    {
        foreach ($this->workingTables as $table) {
            $this->db->setQuery("TRUNCATE TABLE `$table`");
            $this->db->query();
        }
    }
}
