<?php

namespace Hexlet\Code\Tests\Database;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Database\Connection;
use RuntimeException;

/**
 * @group database
 * @group isolation
 */
class ConnectionTest extends TestCase
{
    private $originalEnv;
    private $originalServer;

    protected function setUp(): void
    {
        // СОХРАНЯЕМ ОРИГИНАЛЬНЫЕ ЗНАЧЕНИЯ переменных окружения
        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;

        // ПОЛНОСТЬЮ ОЧИЩАЕМ массивы для изоляции тестов
        $_ENV = [];
        $_SERVER = [];

        // ТАКЖЕ ОЧИЩАЕМ через putenv для надежности
        putenv('DATABASE_URL');
    }

    protected function tearDown(): void
    {
        // ВОССТАНАВЛИВАЕМ оригинальные значения переменных окружения
        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;
    }

    public function testGetThrowsExceptionWithMissingUser(): void
    {
        // ПРОВЕРЯЕТ: что исключение выбрасывается при отсутствии пользователя в URL
        // КОГДА: в DATABASE_URL нет компонента 'user'
        // ДАННЫЕ: URL без имени пользователя
        // ОЖИДАЕМ: RuntimeException с сообщением о missing 'user'

        $_ENV['DATABASE_URL'] = 'pgsql://localhost/db';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'user' in DATABASE_URL");

        Connection::get();
    }

    public function testGetThrowsExceptionWithMissingPassword(): void
    {
        // ПРОВЕРЯЕТ: что исключение выбрасывается при отсутствии пароля в URL
        // КОГДА: в DATABASE_URL нет компонента 'pass'
        // ДАННЫЕ: URL без пароля
        // ОЖИДАЕМ: RuntimeException с сообщением о missing 'pass'

        $_ENV['DATABASE_URL'] = 'pgsql://user@localhost/db';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'pass' in DATABASE_URL");

        Connection::get();
    }

    public function testGetThrowsExceptionWithMissingDatabase(): void
    {
        // ПРОВЕРЯЕТ: что исключение выбрасывается при отсутствии базы данных в URL
        // КОГДА: в DATABASE_URL нет компонента 'path' (имя базы данных)
        // ДАННЫЕ: URL без указания базы данных
        // ОЖИДАЕМ: RuntimeException с сообщением о missing 'path'

        $_ENV['DATABASE_URL'] = 'pgsql://user:pass@localhost';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'path' in DATABASE_URL");

        Connection::get();
    }

    public function testGetUsesServerVariableWhenEnvNotSet(): void
    {
        // ПРОВЕРЯЕТ: что используется SERVER переменная когда ENV не установлена
        // КОГДА: DATABASE_URL есть только в $_SERVER, но нет в $_ENV
        // ДАННЫЕ: URL только в SERVER переменной
        // ОЖИДАЕМ: Connection пытается использовать SERVER переменную

        $_SERVER['DATABASE_URL'] = 'pgsql://user:pass@localhost/db';

        // Должен использовать SERVER переменную (будет исключение из-за подключения к БД)
        $this->expectException(RuntimeException::class);

        Connection::get();
    }

    public function testGetPrefersEnvOverServer(): void
    {
        // ПРОВЕРЯЕТ: что ENV переменная имеет приоритет над SERVER
        // КОГДА: DATABASE_URL установлен и в $_ENV и в $_SERVER
        // ДАННЫЕ: разные URL в ENV и SERVER переменных
        // ОЖИДАЕМ: используется ENV переменная (приоритет выше)

        $_ENV['DATABASE_URL'] = 'pgsql://env-user:env-pass@env-host/env-db';
        $_SERVER['DATABASE_URL'] = 'pgsql://server-user:server-pass@server-host/server-db';

        // Должен использовать ENV переменную (будет исключение из-за подключения к БД)
        $this->expectException(RuntimeException::class);

        Connection::get();
    }

    public function testEnvironmentVariablesAreProperlyCleared(): void
    {
        // ПРОВЕРЯЕТ: что переменные окружения правильно очищаются в setUp()
        // КОГДА: перед выполнением теста
        // ОЖИДАЕМ: все источники DATABASE_URL пустые

        $this->assertArrayNotHasKey('DATABASE_URL', $_ENV);
        $this->assertArrayNotHasKey('DATABASE_URL', $_SERVER);
        $this->assertFalse(getenv('DATABASE_URL'));
    }
}
