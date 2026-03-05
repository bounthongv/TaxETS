<?php
// config.php - Main configuration file

// Database constraints
define('DB_HOST', 'localhost'); // Update to your Ubuntu server IP if connecting remotely, or leave localhost if running PHP on the same server
define('DB_NAME', 'tax_ets');
define('DB_USER', 'root'); // Update with your database username
define('DB_PASS', ''); // Update with your database password

// System Constants
define('BASE_URL', '/Tax-ETS'); // Base URL path
define('APP_NAME', 'Tax-ETS (Phase 1)');

// Global Evaluation Date for Calculations
// "TODAY" or a specific "YYYY-MM-DD" date string
define('EVALUATION_DATE', 'TODAY');

?>
