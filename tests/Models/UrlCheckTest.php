<?php

namespace Hexlet\Code\Tests\Models;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Models\UrlCheck;
use PDO;
use PDOStatement;

class UrlCheckTest extends TestCase
{
    private UrlCheck $urlCheckModel;
    private PDO $pdo;
    private PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->stmt = $this->createMock(PDOStatement::class);

        $this->urlCheckModel = new UrlCheck($this->pdo);
    }

    public function testFindByUrlId(): void
    {
        // Проверяет что метод возвращает все проверки для сайта
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'url_id' => 1, 'status_code' => 200],
            ['id' => 2, 'url_id' => 1, 'status_code' => 404]
        ]);

        $result = $this->urlCheckModel->findByUrlId(1);

        $this->assertCount(2, $result);
        $this->assertEquals(200, $result[0]['status_code']);
    }

    public function testFindByUrlIdEmpty(): void
    {
        // Проверяет что возвращается пустой массив если проверок нет
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetchAll')->willReturn([]);

        $result = $this->urlCheckModel->findByUrlId(999);

        $this->assertEmpty($result);
    }

    public function testFindLastCheck(): void
    {
        // Проверяет что метод находит последнюю проверку сайта
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn([
            'id' => 5,
            'url_id' => 1,
            'status_code' => 200
        ]);

        $result = $this->urlCheckModel->findLastCheck(1);

        $this->assertEquals(200, $result['status_code']);
    }

    public function testFindLastCheckNotFound(): void
    {
        // Проверяет что возвращается null если проверок не было
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->stmt->method('fetch')->willReturn(false);

        $result = $this->urlCheckModel->findLastCheck(999);

        $this->assertNull($result);
    }

    public function testSaveUrlCheck(): void
    {
        // Проверяет что метод сохраняет проверку и возвращает её ID
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('lastInsertId')->willReturn('10');

        $checkData = [
            'status_code' => 200,
            'h1' => 'Заголовок страницы',
            'title' => 'Title страницы'
        ];

        $result = $this->urlCheckModel->saveUrlCheck(1, $checkData);

        $this->assertEquals(10, $result);
    }

    public function testSaveUrlCheckWithMissingData(): void
    {
        // Проверяет что метод работает с неполными данными
        $this->pdo->method('prepare')->willReturn($this->stmt);
        $this->stmt->method('execute')->willReturn(true);
        $this->pdo->method('lastInsertId')->willReturn('15');

        $checkData = [
            'status_code' => 404
            // h1, title, description отсутствуют
        ];

        $result = $this->urlCheckModel->saveUrlCheck(2, $checkData);

        $this->assertEquals(15, $result);
    }
}
