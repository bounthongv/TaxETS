<?php
require_once __DIR__ . "/../config.php";

$log_id = $_GET['log_id'] ?? '';
// Sanitize log_id to prevent directory traversal
$log_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $log_id);

// Check for .log or .csv file (duplicate reports use _duplicates.csv)
$log_path = __DIR__ . "/../data/logs/" . $log_id . ".log";
$csv_path = __DIR__ . "/../data/logs/" . $log_id . ".csv";

if ($log_id && file_exists($log_path)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="import_diagnostic_' . $log_id . '.txt"');
    readfile($log_path);
    exit;
} elseif ($log_id && file_exists($csv_path)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="duplicate_report_' . $log_id . '.csv"');
    readfile($csv_path);
    exit;
} else {
    die("File not found. It may have been deleted.");
}
