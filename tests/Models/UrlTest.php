<?php

namespace Hexlet\Code\Tests\Models;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Models\Url;
use PDO;
use PDOStatement;

class UrlTest extends TestCase
{
    private Url $urlModel;
    private PDO $pdo;
    private PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->urlModel = new Url($this->pdo);
    }

    public function testFindAllUrl(): void
    {
        // Проверяет что метод findAllUrl возвращает все URL из базы
        $this->pdo->method('query')->willReturn($this->stmt);
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'https://example.com']
        ]);

        $result = $this->urlModel->findAllUrl();

        $this->assertCount(1, $result);
        $this->assertEquals('https://example.com', $result[0]['name']);
    }

    public function testFindByIdUrl(): void
    {
        // Проверяет поиск URL по ID (существующий ID)
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(['id' => 1, 'name' => 'https://example.com']);

        $result = $this->urlModel->findByIdUrl(1);

        $this->assertEquals('https://example.com', $result['name']);
    }

    public function testFindByIdUrlNotFound(): void
    {
        // Проверяет что при несуществующем ID возвращается null
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(false);

        $result = $this->urlModel->findByIdUrl(999);

        $this->assertNull($result);
    }

    public function testFindByNameUrl(): void
    {
        // Проверяет поиск URL по имени (существующее имя)
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(['id' => 1, 'name' => 'https://example.com']);

        $result = $this->urlModel->findByNameUrl('https://example.com');

        $this->assertEquals(1, $result['id']);
    }

    public function testSaveNewUrl(): void
    {
        // Проверяет сохранение нового URL в базу
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $result = $this->urlModel->saveNewUrl('https://example.com');

        $this->assertEquals(1, $result);
    }
}
