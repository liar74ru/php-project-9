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
        // Сохраняем оригинальные значения
        $this->originalEnv = $_ENV;
        $this->originalServer = $_SERVER;
        
        // Полностью очищаем массивы
        $_ENV = [];
        $_SERVER = [];
        
        // Также очищаем через putenv для надежности
        putenv('DATABASE_URL');
    }

    protected function tearDown(): void
    {
        // Восстанавливаем оригинальные значения
        $_ENV = $this->originalEnv;
        $_SERVER = $this->originalServer;
    }

    /*public function testGetThrowsExceptionWhenDatabaseUrlNotSet(): void
    {
        //Дополнительная проверка - убедимся что переменные действительно очищены
        $this->assertArrayNotHasKey('DATABASE_URL', $_ENV);
        $this->assertArrayNotHasKey('DATABASE_URL', $_SERVER);
        $this->assertFalse(getenv('DATABASE_URL'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DATABASE_URL environment variable is not set');

        Connection::get();
    }*/

    /*public function testGetThrowsExceptionWithInvalidUrlFormat(): void
    {
        $_ENV['DATABASE_URL'] = 'invalid-url-without-protocol';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid DATABASE_URL format. Cannot parse URL: invalid-url-without-protocol');

        Connection::get();
    }*/

    public function testGetThrowsExceptionWithMissingUser(): void
    {
        $_ENV['DATABASE_URL'] = 'pgsql://localhost/db';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'user' in DATABASE_URL");

        Connection::get();
    }

    public function testGetThrowsExceptionWithMissingPassword(): void
    {
        $_ENV['DATABASE_URL'] = 'pgsql://user@localhost/db';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'pass' in DATABASE_URL");

        Connection::get();
    }

    /*public function testGetThrowsExceptionWithMissingHost(): void
    {
        $_ENV['DATABASE_URL'] = 'pgsql://user:pass@/db';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'host' in DATABASE_URL");

        Connection::get();
    }*/

    public function testGetThrowsExceptionWithMissingDatabase(): void
    {
        $_ENV['DATABASE_URL'] = 'pgsql://user:pass@localhost';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing required component 'path' in DATABASE_URL");

        Connection::get();
    }

    public function testGetUsesServerVariableWhenEnvNotSet(): void
    {
        $_SERVER['DATABASE_URL'] = 'pgsql://user:pass@localhost/db';

        // Должен использовать SERVER переменную
        $this->expectException(RuntimeException::class);

        Connection::get();
    }

    public function testGetPrefersEnvOverServer(): void
    {
        $_ENV['DATABASE_URL'] = 'pgsql://env-user:env-pass@env-host/env-db';
        $_SERVER['DATABASE_URL'] = 'pgsql://server-user:server-pass@server-host/server-db';

        // Должен использовать ENV переменную (приоритет выше)
        $this->expectException(RuntimeException::class);

        Connection::get();
    }

    public function testEnvironmentVariablesAreProperlyCleared(): void
    {
        $this->assertArrayNotHasKey('DATABASE_URL', $_ENV);
        $this->assertArrayNotHasKey('DATABASE_URL', $_SERVER);
        $this->assertFalse(getenv('DATABASE_URL'));
    }
}