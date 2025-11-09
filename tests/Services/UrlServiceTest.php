<?php

namespace Hexlet\Code\Tests\Services;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Services\UrlService;
use PDO;
use PDOStatement;

class UrlServiceTest extends TestCase
{
    private UrlService $urlService;
    private PDO $pdo;
    private PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->urlService = new UrlService($this->pdo);
    }

    public function testFindAllWithLastChecks(): void
    {
        // Проверяет что сервис возвращает URL с данными последних проверок
        $expectedData = [
            [
                'id' => 1,
                'name' => 'https://example.com',
                'last_check_date' => '2023-01-01 10:00:00',
                'last_status_code' => 200
            ],
            [
                'id' => 2,
                'name' => 'https://google.com',
                'last_check_date' => null,
                'last_status_code' => null
            ]
        ];

        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($expectedData);

        $result = $this->urlService->findAllWithLastChecks();

        $this->assertCount(2, $result);
        $this->assertEquals('https://example.com', $result[0]['name']);
        $this->assertEquals(200, $result[0]['last_status_code']);
    }

    public function testFindAllWithLastChecksEmpty(): void
    {
        // Проверяет что сервис возвращает пустой массив когда URL нет
        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn([]);

        $result = $this->urlService->findAllWithLastChecks();

        $this->assertEmpty($result);
    }

    public function testFindAllWithLastChecksWithNullValues(): void
    {
        // Проверяет что сервис корректно обрабатывает null в данных проверок
        $expectedData = [
            [
                'id' => 1,
                'name' => 'https://example.com',
                'last_check_date' => null,
                'last_status_code' => null
            ]
        ];

        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn($expectedData);

        $result = $this->urlService->findAllWithLastChecks();

        $this->assertNull($result[0]['last_check_date']);
        $this->assertNull($result[0]['last_status_code']);
    }
}
