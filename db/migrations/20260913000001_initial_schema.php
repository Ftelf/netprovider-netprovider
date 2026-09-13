<?php

use Phinx\Migration\AbstractMigration;

/**
 * Initial schema (migration 001).
 *
 * Loads the canonical DDL from sql/schema.sql rather than re-declaring tables
 * here, so sql/schema.sql stays the single source of truth: quick-start setup,
 * the integration tier (NP_IT_SCHEMA), and this migration all apply the exact
 * same DDL. Subsequent schema changes are added as new, forward-only migrations.
 *
 * The migration strips the `DROP TABLE IF EXISTS` lines from that DDL before
 * running it: they make the manual quick-start (`mysql < sql/schema.sql`)
 * re-runnable, but a migration must never drop populated tables. See up().
 *
 * Irreversible by design: down() would drop every table (and all data). Rolling
 * back the initial schema is a destructive operation we do not automate.
 */
final class InitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../sql/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Cannot read sql/schema.sql');
        }
        // sql/schema.sql opens each table with `DROP TABLE IF EXISTS` so the
        // manual bootstrap is re-runnable. A migration must NOT carry those:
        // running db:migrate against an existing populated database (e.g. an
        // install adopting Phinx for the first time) would drop every table and
        // its data before recreating them empty. Strip the DROPs so this
        // migration only ever CREATEs — on a non-empty DB it then fails loudly
        // on the first existing table instead of destroying data.
        $sql = preg_replace('/^DROP TABLE IF EXISTS `[^`]+`;\R/m', '', $sql);
        // schema.sql is multi-statement DDL; execute() streams it as-is.
        $this->execute($sql);
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Refusing to auto-drop the initial schema. Drop the database manually if intended.'
        );
    }
}
