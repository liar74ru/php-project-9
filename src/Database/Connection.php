<?php

namespace Hexlet\Code\Database;

use PDO;
use RuntimeException;
use Dotenv\Dotenv;

class Connection
{
    public static function get(): PDO
    {
        self::loadEnvIfExists();

        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

        if (!$databaseUrl) {
            throw new RuntimeException('DATABASE_URL environment variable is not set');
        }

        $pdo = self::createFromUrl($databaseUrl);

        self::initializeDatabase($pdo);

        return $pdo;
    }

    private static function loadEnvIfExists(): void
    {
        $envPath = __DIR__ . '/../../';

        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();

            $dotenv->required('DATABASE_URL')->notEmpty();
        }
    }

    private static function createFromUrl(string $databaseUrl): PDO
    {
        $url = parse_url($databaseUrl);

        if (!$url) {
            throw new RuntimeException('Invalid DATABASE_URL format. Cannot parse URL: ' . $databaseUrl);
        }

        $required = ['user', 'pass', 'host', 'path'];
        foreach ($required as $component) {
            if (!isset($url[$component])) {
                throw new RuntimeException("Missing required component '{$component}' in DATABASE_URL");
            }
        }

        $username = $url['user'];
        $password = $url['pass'];
        $host = $url['host'];
        $port = $url['port'] ?? '5432';
        $dbName = ltrim($url['path'], '/');

        if (empty($username) || empty($password) || empty($host) || empty($dbName)) {
            throw new RuntimeException('One or more required database parameters are empty');
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    private static function initializeDatabase(PDO $pdo): void
    {
        try {
            $sqlPath = __DIR__ . '/../../database.sql';

            if (!file_exists($sqlPath)) {
                throw new RuntimeException('Database schema file not found: ' . $sqlPath);
            }

            $sql = file_get_contents($sqlPath);
            if ($sql === false) {
                throw new RuntimeException('Не удалось прочитать файл: ' . $sqlPath);
            }
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                error_log("Database initialization warning: " . $e->getMessage());
            }
        } catch (\Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
        }
    }
}
