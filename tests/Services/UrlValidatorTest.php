<?php

namespace Hexlet\Code\Tests\Services;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Services\UrlValidator;

// Исправлено на правильное имя класса

class UrlValidatorTest extends TestCase
{
    private UrlValidator $urlValidator;

    protected function setUp(): void
    {
        // Создаем реальный экземпляр, а не mock
        $this->urlValidator = new UrlValidator();
    }

    public function testValidateUrlSuccess(): void
    {
        // Arrange
        $url = 'https://example.com';

        // Act
        $result = $this->urlValidator->validate($url);

        // Assert
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('https://example.com', $result['url']);
    }

    public function testValidateUrlInvalidFormat(): void
    {
        // Arrange
        $url = 'invalid-url';

        // Act
        $result = $this->urlValidator->validate($url);

        // Assert
        $this->assertArrayHasKey('errorMessage', $result);
        $this->assertEquals('Некорректный URL', $result['errorMessage']);
    }

    public function testValidateUrlHostEndsWithDot(): void
    {
        // Arrange
        $url = 'https://example.com.';

        // Act
        $result = $this->urlValidator->validate($url);

        // Assert
        $this->assertArrayHasKey('errorMessage', $result);
        $this->assertEquals('Некорректный URL: хост не может заканчиваться точкой', $result['errorMessage']);
    }

    public function testValidateUrlShortTld(): void
    {
        // Arrange
        $url = 'https://example.c';

        // Act
        $result = $this->urlValidator->validate($url);

        // Assert
        $this->assertArrayHasKey('errorMessage', $result);
        $this->assertEquals('Некорректный URL: домен верхнего уровня слишком короткий', $result['errorMessage']);
    }
}
