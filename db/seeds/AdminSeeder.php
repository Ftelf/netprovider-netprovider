<?php

use Phinx\Seed\AbstractSeed;

/**
 * Minimal loginable seed — the same rows as sql/seed.sql, applied via
 * `vendor/bin/phinx seed:run -s AdminSeeder`.
 *
 * Loads sql/seed.sql so the seed has one source of truth. Intended for real
 * bootstrap setups only; the integration tier seeds its own data and never
 * runs this. Default credentials are admin / changeme — change immediately.
 */
final class AdminSeeder extends AbstractSeed
{
    public function run(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../sql/seed.sql');
        if ($sql === false) {
            throw new RuntimeException('Cannot read sql/seed.sql');
        }
        $this->execute($sql);
    }
}
