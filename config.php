<?php
// config.php - Main configuration file

// Optional local/server credential file. Keep config.sys out of git.
$localConfig = __DIR__ . '/config.sys';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// Database defaults for notebook testing. Override these in config.sys on each machine.
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_PORT') || define('DB_PORT', 3306);
defined('DB_NAME') || define('DB_NAME', 'tax_ets');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

// Auto-detect the app mount point unless a config file defines it explicitly.
if (!defined('BASE_URL')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptName = str_replace('\\', '/', $scriptName);
    $baseUrl = rtrim(dirname($scriptName), '/');

    // Pages live under /pages, but the app root is one level above that.
    if (strpos($scriptName, '/pages/') !== false) {
        $baseUrl = rtrim(dirname(dirname($scriptName)), '/');
    }

    if ($baseUrl === '/' || $baseUrl === '.') {
        $baseUrl = '';
    }
    define('BASE_URL', $baseUrl);
}

// System Constants
defined('APP_NAME') || define('APP_NAME', 'Tax-ETS (Phase 1)');

// Global Evaluation Date for Calculations
// "TODAY" or a specific "YYYY-MM-DD" date string
define('EVALUATION_DATE', 'TODAY');

?>
