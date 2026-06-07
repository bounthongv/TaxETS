<?php
// includes/db.php - Database connection helper
require_once __DIR__ . '/../config.php';

function getDbConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        // MySQL 8.0 default strict mode (ONLY_FULL_GROUP_BY) breaks
        // our older GROUP BY queries that select non-aggregated columns.
        // Disabling it at session level maintains backward compatibility.
        $pdo->exec("SET SESSION sql_mode = ''");
        return $pdo;
    } catch (\PDOException $e) {
        // Suppress displaying actual error to user for security
        die("Database connection failed. Please check your config.php settings.");
    }
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?"
    );
    $stmt->execute([DB_NAME, $tableName]);
    return (bool) $stmt->fetchColumn();
}
?>
