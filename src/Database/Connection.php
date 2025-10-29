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

        // Инициализируем таблицы при первом подключении
        self::initializeDatabase($pdo);

        return $pdo;
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

    private static function initializeDatabase(PDO $pdo): void
    {
        try {
            $sqlPath = __DIR__ . '/../../database.sql';

            if (!file_exists($sqlPath)) {
                throw new RuntimeException('Database schema file not found: ' . $sqlPath);
            }

            $sql = file_get_contents($sqlPath);
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            // Игнорируем ошибки "table already exists", логируем остальные
            if (strpos($e->getMessage(), 'already exists') === false) {
                error_log("Database initialization warning: " . $e->getMessage());
                // Не бросаем исключение дальше, чтобы приложение могло работать
                // даже если таблицы уже созданы
            }
        } catch (\Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            // Продолжаем работу, даже если инициализация не удалась
        }
    }
}
