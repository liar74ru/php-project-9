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

    public function testFindAll(): void
    {
        // Arrange
        $expectedData = [
            ['id' => 1, 'name' => 'https://example.com'],
            ['id' => 2, 'name' => 'https://google.com']
        ];

        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM urls ORDER BY id DESC')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlModel->findAll();

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindExistingUrl(): void
    {
        // Arrange
        $expectedData = ['id' => 1, 'name' => 'https://example.com'];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM urls WHERE id = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([1]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlModel->find(1);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindNonExistingUrl(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM urls WHERE id = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([999]);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->urlModel->find(999);

        // Assert
        $this->assertNull($result);
    }

    public function testFindByName(): void
    {
        // Arrange
        $expectedData = ['id' => 1, 'name' => 'https://example.com'];

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM urls WHERE name = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with(['https://example.com']);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->urlModel->findByName('https://example.com');

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByNameNonExisting(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM urls WHERE name = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with(['https://nonexistent.com']);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->urlModel->findByName('https://nonexistent.com');

        // Assert
        $this->assertNull($result);
    }

    public function testSave(): void
    {
        // Arrange
        $url = 'https://example.com';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO urls (name, created_at) VALUES (?, ?)')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($url) {
                return $params[0] === $url
                    && is_string($params[1]); // created_at timestamp
            }));

        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1');

        // Act
        $result = $this->urlModel->save($url);

        // Assert
        $this->assertEquals(1, $result);
    }

    public function testExistsReturnsTrue(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT id FROM urls WHERE name = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with(['https://example.com']);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['id' => 1]);

        // Act
        $result = $this->urlModel->exists('https://example.com');

        // Assert
        $this->assertTrue($result);
    }

    public function testExistsReturnsFalse(): void
    {
        // Arrange
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT id FROM urls WHERE name = ?')
            ->willReturn($this->stmt);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with(['https://nonexistent.com']);

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->urlModel->exists('https://nonexistent.com');

        // Assert
        $this->assertFalse($result);
    }
}
