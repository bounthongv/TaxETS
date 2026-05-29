<?php
require_once __DIR__ . "/../config.php";

$log_id = $_GET['log_id'] ?? '';
// Sanitize log_id to prevent directory traversal
$log_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $log_id);

$log_path = __DIR__ . "/../data/logs/" . $log_id . ".log";

if ($log_id && file_exists($log_path)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="import_diagnostic_' . $log_id . '.txt"');
    readfile($log_path);
    exit;
} else {
    die("Log not found. It may have been deleted or the batch has no errors.");
}
