<?php

namespace TaxETS\Services;

use PDO;
use PDOException;

class Database
{
    private static ?Database $_instance = null;
    private PDO $connection;

    private function __construct()
    {
        // Assuming config.php is in the src directory
        $configPath = __DIR__ . '/../config.php';
        if (!file_exists($configPath)) {
            throw new \Exception("Config file not found at: " . $configPath);
        }
        require_once $configPath;

        // Ensure $dbConfig is available from config.php
        if (!isset($dbConfig) || !is_array($dbConfig)) {
            throw new \Exception("Database configuration (\$dbConfig) not found or invalid in config.php");
        }

        $host = $dbConfig['host'] ?? 'localhost';
        $dbname = $dbConfig['dbname'] ?? 'taxets';
        $user = $dbConfig['user'] ?? 'root';
        $password = $dbConfig['password'] ?? '';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
