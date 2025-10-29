<?php

namespace Hexlet\Code\Database;

use PDO;
use RuntimeException;

class Connection
{
    public static function get(): PDO
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        
        if ($databaseUrl) {
            return self::createFromConnectionString($databaseUrl);
        }
        
        // Fallback на отдельные переменные
        return self::createFromSeparateVars();
    }

    private static function createFromConnectionString(string $connectionString): PDO
    {
        // Render.com использует формат: postgresql://[user]:[password]@[host]:[port]/[database]
        $pattern = '/^postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)$/';
        
        if (preg_match($pattern, $connectionString, $matches)) {
            $username = $matches[1];
            $password = $matches[2];
            $host = $matches[3];
            $port = $matches[4];
            $database = $matches[5];
            
            return self::createPdo($host, $port, $database, $username, $password);
        }
        
        throw new RuntimeException('Invalid DATABASE_URL format. Expected: postgresql://user:password@host:port/database');
    }

    private static function createFromSeparateVars(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '5432';
        $database = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? 'project9';
        $username = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'postgres_user';
        $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'postgres_password';

        return self::createPdo($host, $port, $database, $username, $password);
    }

    private static function createPdo(string $host, string $port, string $database, string $username, string $password): PDO
    {
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    }
}