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

    public function testFindByCheaks(): void
    {
        // Arrange
        $expectedData = [
            ['id' => 1, 'url_id' => 1, 'status_code' => 200],
            ['id' => 2, 'url_id' => 2, 'status_code' => 404]
        ];

        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM url_checks ORDER BY created_at DESC')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlCheckModel->findByCheaks();

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByUrlId(): void
    {
        // Arrange
        $expectedData = [
            ['id' => 1, 'status_code' => 200, 'title' => 'Example'],
            ['id' => 2, 'status_code' => 200, 'title' => 'Example Updated']
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlCheckModel->findByUrlId(1);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByUrlIdEmpty(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([999]);

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        // Act
        $result = $this->urlCheckModel->findByUrlId(999);

        // Assert
        $this->assertEquals([], $result);
    }

    public function testFindLastCheck(): void
    {
        // Arrange
        $expectedData = [
            'id' => 1,
            'status_code' => 200,
            'created_at' => '2023-01-01 10:00:00'
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlCheckModel->findLastCheck(1);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindLastCheckWhenNoChecks(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->urlCheckModel->findLastCheck(1);

        // Assert
        $this->assertNull($result);
    }

    public function testSave(): void
    {
        // Arrange
        $urlId = 1;
        $data = [
            'status_code' => 200,
            'h1' => 'Test Heading',
            'title' => 'Test Title',
            'description' => 'Test Description'
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains(
                    'INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at)'
                )
            )
        ->willReturn($this->stmt);
        $this->stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($urlId, $data) {
                return $params[0] === $urlId
                    && $params[1] === $data['status_code']
                    && $params[2] === $data['h1']
                    && $params[3] === $data['title']
                    && $params[4] === $data['description']
                    && is_string($params[5]); // created_at
            }));

        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('5');

        // Act
        $result = $this->urlCheckModel->save($urlId, $data);

        // Assert
        $this->assertEquals(5, $result);
    }

    public function testSaveWithNullValues(): void
    {
        // Arrange
        $urlId = 1;
        $data = [
            'status_code' => 200
            // h1, title, description не указаны - должны быть null
        ];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO url_checks')) // ← Исправлено
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($urlId) {
                return $params[0] === $urlId
                    && $params[1] === 200
                    && $params[2] === null
                    && $params[3] === null
                    && $params[4] === null
                    && is_string($params[5]);
            }));

        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('6');

        // Act
        $result = $this->urlCheckModel->save($urlId, $data);

        // Assert
        $this->assertEquals(6, $result);
    }

    public function testGetLastCheckDate(): void
    {
        // Arrange
        $expectedDate = '2023-01-01 10:00:00';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT created_at FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['created_at' => $expectedDate]);

        // Act
        $result = $this->urlCheckModel->getLastCheckDate(1);

        // Assert
        $this->assertEquals($expectedDate, $result);
    }

    public function testGetLastCheckDateWhenNoChecks(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT created_at FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([999]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->urlCheckModel->getLastCheckDate(999);

        // Assert
        $this->assertNull($result);
    }
}
