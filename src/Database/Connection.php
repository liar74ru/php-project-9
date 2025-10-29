<?php

namespace Hexlet\Code\Database;

use PDO;
use RuntimeException;
use Dotenv\Dotenv;

class Connection
{
    public static function get(): PDO
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $dotenv->required('DATABASE_URL');

        if (!isset($_ENV['DATABASE_URL'])) {
            throw new RuntimeException('DATABASE_URL is not set in environment variables.');
        }

        $databaseUrl = parse_url($_ENV['DATABASE_URL']);

        $required = ['user', 'pass', 'host', 'port', 'path'];
        foreach ($required as $component) {
            if (!isset($databaseUrl[$component])) {
                throw new RuntimeException("Не хватает компонента {$component} в DATABASE_URL");
            }
        }

        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'];
        $dbname = ltrim($databaseUrl['path'], '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
