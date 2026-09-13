<?php
/**
 * NetProvider PHPUnit bootstrap.
 *
 * Project predates Composer and uses globals ($core, $database) plus
 * top-of-file `require_once $core->getAppRoot() . "includes/..."` chains.
 * Bootstrap therefore:
 *   1. Defines project paths.
 *   2. Installs Composer autoloader for vendored test deps.
 *   3. Stubs gettext _() if extension missing in test env.
 *   4. Boots CoreStub as global $core BEFORE any app class loads.
 *   5. Registers autoloader for project classes (includes/, modules/).
 */

declare(strict_types=1);

// Pin the timezone so all date math is deterministic regardless of the ambient
// php.ini `date.timezone`. Time-based tests (e.g. the ChargePaymentDeadline
// notify boundary) compare exact day counts; under a DST-observing default zone
// a fall-back transition inside the window would shift a span by an hour and
// flake the assertion. UTC has no DST, so N calendar days is always N*86400s.
date_default_timezone_set('UTC');

define('NP_PROJECT_ROOT', realpath(__DIR__ . '/..') . '/');
define('NP_TESTS_ROOT', __DIR__ . '/');

// Composer autoload (PHPUnit, Mockery)
$composer = NP_PROJECT_ROOT . 'vendor/autoload.php';
if (!file_exists($composer)) {
    fwrite(STDERR, "Run `composer install` first — vendor/autoload.php missing.\n");
    exit(1);
}
require_once $composer;

// Polyfill gettext if extension missing
if (!function_exists('_')) {
    function _($s) { return $s; }
    function gettext($s) { return $s; }
    function bindtextdomain($d, $p) { return $p; }
    function bind_textdomain_codeset($d, $c) { return $c; }
    function textdomain($d) { return $d; }
}

// Test stubs
require_once NP_TESTS_ROOT . 'Stubs/CoreStub.php';
require_once NP_TESTS_ROOT . 'Stubs/DatabaseStub.php';
require_once NP_TESTS_ROOT . 'TestCase.php';
require_once NP_TESTS_ROOT . 'Integration/IntegrationTestCase.php';

// Boot global $core (needed by classes that `require_once $core->getAppRoot()`)
global $core;
$core = new CoreStub(NP_PROJECT_ROOT);

// Some legacy files (e.g. ChargeDAO) use `require_once "BankAccountDAO.php"`
// with a relative path — make those resolvable in tests by extending include_path.
// Also resolve PEAR `Mail.php` / `Net/IPv4.php` against the test pear-shim.
set_include_path(implode(PATH_SEPARATOR, [
    get_include_path(),
    NP_PROJECT_ROOT . 'includes',
    NP_PROJECT_ROOT . 'includes/dao',
    NP_PROJECT_ROOT . 'includes/tables',
    NP_PROJECT_ROOT . 'includes/utils',
    NP_PROJECT_ROOT . 'includes/billing',
    NP_PROJECT_ROOT . 'includes/event',
    NP_PROJECT_ROOT . 'includes/net',
    NP_PROJECT_ROOT . 'includes/net/email',
    NP_TESTS_ROOT . 'Stubs/pear-shim',
]));

// Autoload application classes on demand by scanning known dirs
spl_autoload_register(function ($class) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $dirs = [
            NP_PROJECT_ROOT . 'includes',
            NP_PROJECT_ROOT . 'includes/tables',
            NP_PROJECT_ROOT . 'includes/dao',
            NP_PROJECT_ROOT . 'includes/utils',
            NP_PROJECT_ROOT . 'includes/billing',
            NP_PROJECT_ROOT . 'includes/billing/bankParser',
            NP_PROJECT_ROOT . 'includes/billing/bankParser/RBTXTParser',
            NP_PROJECT_ROOT . 'includes/billing/bankParser/IsoSepaXmlParser',
            NP_PROJECT_ROOT . 'includes/billing/bankParser/RBPDFParser',
            NP_PROJECT_ROOT . 'includes/event',
            NP_PROJECT_ROOT . 'includes/net',
            NP_PROJECT_ROOT . 'includes/net/commander',
            NP_PROJECT_ROOT . 'includes/net/email',
            NP_PROJECT_ROOT . 'includes/html',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (glob("$dir/*.php") as $file) {
                // Key by lower-case name: filenames don't always match class
                // casing (e.g. Mainframe.php defines class MainFrame).
                $name = strtolower(basename($file, '.php'));
                if (!isset($cache[$name])) {
                    $cache[$name] = $file;
                }
            }
        }
    }
    $key = strtolower($class);
    if (isset($cache[$key])) {
        require_once $cache[$key];
    }
});
