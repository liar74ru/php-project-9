<?php

namespace Hexlet\Code\Database;

use PDO;
use RuntimeException;
use Dotenv\Dotenv;

class Connection
{
    public static function get(): PDO
    {
        // Загружаем .env для локальной разработки (если файл существует)
        self::loadEnvIfExists();

        // 1. Пробуем DATABASE_URL из переменных окружения
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        
        if ($databaseUrl) {
            return self::createFromUrl($databaseUrl);
        }
        
        // 2. Fallback на отдельные переменные (для совместимости)
        return self::createFromSeparateVars();
    }

    private static function loadEnvIfExists(): void
    {
        $envPath = __DIR__ . '/../../';
        
        // Загружаем .env только если файл существует (локальная разработка)
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
            
            // Опционально: проверяем обязательные переменные только в .env
            $dotenv->required('DATABASE_URL')->notEmpty();
        }
        // На продакшене .env нет, но переменные уже установлены в Environment Variables
    }

    private static function createFromUrl(string $databaseUrl): PDO
    {
        // Парсим URL как в задании: {provider}://{user}:{password}@{host}:{port}/{db}
        $url = parse_url($databaseUrl);

        echo $url;
        
        if (!$url) {
            throw new RuntimeException('Invalid DATABASE_URL format. Cannot parse URL: ' . $databaseUrl);
        }

        // Проверяем обязательные компоненты
        $required = ['user', 'pass', 'host', 'path'];
        foreach ($required as $component) {
            if (!isset($url[$component])) {
                throw new RuntimeException("Missing required component '{$component}' in DATABASE_URL");
            }
        }

        // Извлекаем данные как в примере из задания
        $username = $url['user'];
        $password = $url['pass'];
        $host = $url['host'];
        $port = $url['port'] ?? '5432'; // Порт по умолчанию для PostgreSQL
        $dbName = ltrim($url['path'], '/');

        // Дополнительная проверка
        if (empty($username) || empty($password) || empty($host) || empty($dbName)) {
            throw new RuntimeException('One or more required database parameters are empty');
        }

        // Создаем DSN строку для PDO
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
        
        // Создаем подключение
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    }

    private static function createFromSeparateVars(): PDO
    {
        // Fallback на отдельные переменные (для максимальной совместимости)
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? null;
        $database = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? null;
        $username = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? null;
        $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? null;

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    }
}