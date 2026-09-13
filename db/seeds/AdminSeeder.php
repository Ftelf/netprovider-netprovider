<?php

use Phinx\Seed\AbstractSeed;

/**
 * Minimal loginable seed — the structural rows from sql/seed.sql plus a
 * generated admin password. Applied via `vendor/bin/phinx seed:run -s AdminSeeder`.
 *
 * Loads sql/seed.sql (single source of truth for the rows), which creates the
 * admin account with NO password, then generates a strong random password, sets
 * it, and prints it once on stdout. No credential is ever committed to git.
 *
 * Intended for real bootstrap setups only; the integration tier seeds its own
 * data and never runs this. Safe to re-run — each run regenerates the password.
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

        // sql/seed.sql leaves the admin with no password (not loginable). Generate
        // a strong random password here so `composer db:seed` yields a usable admin
        // without ever committing a credential. The value is hex (bin2hex), so it is
        // safe to interpolate into SQL.
        $password = bin2hex(random_bytes(12)); // 24 hex chars
        $this->execute(sprintf(
            "UPDATE `person` SET `PE_password` = MD5('%s') WHERE `PE_username` = 'admin'",
            $password
        ));

        // Surface the generated credential exactly once.
        fwrite(STDOUT, sprintf(
            "\n[AdminSeeder] Admin account ready.\n"
            . "  username: admin\n"
            . "  password: %s\n"
            . "  Record this now and change it after first login — it will not be shown again.\n\n",
            $password
        ));
    }
}
