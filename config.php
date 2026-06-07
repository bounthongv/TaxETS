<?php
/**
 * Tax-ETS — Main configuration file (v2 with Docker env support)
 * ---------------------------------------------------------------------
 * Loading order (highest priority first):
 *   1. Real environment variables (set by Docker Compose from .env) — HIGHEST
 *   2. Hard-coded defaults
 *   3. Constants from config.sys (legacy XAMPP / bare-metal workflow) — LOWEST
 *
 *   defined('FOO') || define('FOO', $env)   ← this is the trick:
 *   if config.sys already defined FOO, leave it; otherwise use $env.
 *
 * DO NOT put real passwords in config.sys — that file is gitignored.
 * Real passwords belong in .env (also gitignored) or docker-compose.yml.
 */

// ---- DB credentials: env vars FIRST (set by Docker Compose / .env) ----
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_PORT') || define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'tax_ets');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');

// ---- Optional legacy local credentials file (lowest priority) ----
// config.sys is the XAMPP bare-metal fallback. It only defines values
// that haven't already been set by env vars above.
$localConfig = __DIR__ . '/config.sys';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

// ---- BASE_URL: env override, else auto-detect, else config.sys ----
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', $envBase);
    } else {
        // Auto-detect: usually empty string (app at web root)
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
}

// ---- System constants ----
defined('APP_NAME') || define('APP_NAME', 'Tax-ETS (Phase 1)');

// Global Evaluation Date for Calculations
// "TODAY" or a specific "YYYY-MM-DD" date string
define('EVALUATION_DATE', 'TODAY');
