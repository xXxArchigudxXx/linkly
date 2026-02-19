<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Config;
use PDO;
use RuntimeException;

/**
 * Singleton PDO connection to MySQL.
 * Lazy initialization with transaction support.
 */
final class Connection
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        // START_CONTRACT_getInstance
        // Intent: Singleton pattern - single DB connection per request
        // Input: None
        // Output: PDO instance (same on multiple calls)
        // END_CONTRACT_getInstance
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    public static function rollback(): void
    {
        $pdo = self::getInstance();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function createConnection(): PDO
    {
        $config = Config::getInstance();

        $host = $config->getRequired('DB_HOST');
        $port = $config->getInt('DB_PORT', 3306);
        $dbname = $config->getRequired('DB_NAME');
        $user = $config->getRequired('DB_USER');
        $password = $config->get('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        return $pdo;
    }
}