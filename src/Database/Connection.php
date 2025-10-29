<?php

namespace Hexlet\Code\Database;

use PDO;
use RuntimeException;

class Connection
{
    public static function get(): PDO
    {
        // 1. Пробуем DATABASE_URL
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        
        if ($databaseUrl) {
            return self::createFromUrl($databaseUrl);
        }
        
        // 2. Пробуем отдельные переменные
        return self::createFromSeparateVars();
    }

    private static function createFromUrl(string $databaseUrl): PDO
    {
        $url = parse_url($databaseUrl);

        $required = ['user', 'pass', 'host', 'path'];
        foreach ($required as $component) {
            if (!isset($url[$component])) {
                throw new RuntimeException("Missing {$component} in DATABASE_URL");
            }
        }

        $username = $url['user'];
        $password = $url['pass'];
        $host = $url['host'];
        $port = $url['port'] ?? '5432';
        $dbname = ltrim($url['path'], '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    }

    private static function createFromSeparateVars(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '5432';
        $database = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'project9';
        $username = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'postgres_user';
        $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'postgres_password';

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    }
}