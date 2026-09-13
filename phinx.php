<?php
/**
 * Phinx configuration for netprovider schema migrations.
 *
 * Connection resolution order (first hit wins per field):
 *   1. Environment variables — used by CI and the integration tier:
 *        NP_IT_DB_HOST / NP_IT_DB_PORT / NP_IT_DB_NAME / NP_IT_DB_USER / NP_IT_DB_PASS
 *   2. config/netprovider.ini [Database] — the same credentials the app uses.
 *   3. Hard defaults (localhost / netprovider).
 *
 * This keeps a single credential source for local runs (the app's ini) while
 * letting a disposable test database be targeted purely through env vars.
 */

$ini = [];
$iniPath = __DIR__ . '/config/netprovider.ini';
if (is_file($iniPath)) {
    $parsed = parse_ini_file($iniPath, true);
    if (is_array($parsed) && isset($parsed['Database'])) {
        $ini = $parsed['Database'];
    }
}

$env = static function (string $key): ?string {
    $v = getenv($key);
    return ($v === false || $v === '') ? null : $v;
};

$host = $env('NP_IT_DB_HOST') ?? ($ini['Database Host'] ?? '127.0.0.1');
$name = $env('NP_IT_DB_NAME') ?? ($ini['Database Name'] ?? 'netprovider');
$user = $env('NP_IT_DB_USER') ?? ($ini['Database Username'] ?? 'root');
$pass = $env('NP_IT_DB_PASS') ?? ($ini['Database Password'] ?? '');
$port = (int) ($env('NP_IT_DB_PORT') ?? 3306);

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds'      => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'default',
        'default'                 => [
            'adapter'   => 'mysql',
            'host'      => $host,
            'name'      => $name,
            'user'      => $user,
            'pass'      => $pass,
            'port'      => $port,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_czech_ci',
        ],
    ],
    'version_order' => 'creation',
];
