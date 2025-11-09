<?php

namespace Hexlet\Code\Tests\Services;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Services\UrlCheckService;
use Hexlet\Code\Models\UrlCheck;
use Hexlet\Code\Services\HttpClient;
use Hexlet\Code\Services\PageParser;

class UrlCheckServiceTest extends TestCase
{
    private UrlCheckService $urlCheckService;
    private UrlCheck $urlCheckModel;
    private HttpClient $httpClient;
    private PageParser $pageParser;

    protected function setUp(): void
    {
        $this->urlCheckModel = $this->createMock(UrlCheck::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->pageParser = $this->createMock(PageParser::class);

        $this->urlCheckService = new UrlCheckService(
            $this->urlCheckModel,
            $this->httpClient,
            $this->pageParser
        );
    }

    public function testPerformCheckSuccess(): void
    {
        // Проверяет успешную проверку сайта
        $urlId = 1;
        $url = 'https://example.com';

        // Настраиваем успешный HTTP ответ
        $this->httpClient->method('fetchUrl')->willReturn([
            'success' => true,
            'status_code' => 200,
            'body' => '<html>Test content</html>'
        ]);

        // Настраиваем парсинг HTML
        $this->pageParser->method('parsePageContent')->willReturn([
            'h1' => 'Test Heading',
            'title' => 'Test Title',
            'description' => 'Test Description'
        ]);

        // Ожидаем сохранение проверки
        $this->urlCheckModel->expects($this->once())->method('saveUrlCheck');

        $result = $this->urlCheckService->performCheck($urlId, $url);

        $this->assertTrue($result['success']);
    }

    public function testPerformCheckHttpError(): void
    {
        // Проверяет проверку сайта с HTTP ошибкой (404, 500)
        $urlId = 1;
        $url = 'https://example.com/not-found';

        // Настраиваем HTTP ошибку
        $this->httpClient->method('fetchUrl')->willReturn([
            'success' => false,
            'status_code' => 404,
            'error' => 'Page not found'
        ]);

        // Парсинг не должен вызываться при ошибке
        $this->pageParser->expects($this->never())->method('parsePageContent');

        // Проверка все равно должна сохраниться
        $this->urlCheckModel->expects($this->once())->method('saveUrlCheck');

        $result = $this->urlCheckService->performCheck($urlId, $url);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['check_data']['status_code']);
    }

    public function testPerformCheckConnectionError(): void
    {
        // Проверяет проверку сайта с ошибкой подключения
        $urlId = 1;
        $url = 'https://unreachable-site.com';

        // Настраиваем ошибку подключения
        $this->httpClient->method('fetchUrl')->willReturn([
            'success' => false,
            'status_code' => 0,
            'error' => 'Connection failed'
        ]);

        // Парсинг не должен вызываться
        $this->pageParser->expects($this->never())->method('parsePageContent');

        // Проверка должна сохраниться с кодом 0
        $this->urlCheckModel->expects($this->once())->method('saveUrlCheck');

        $result = $this->urlCheckService->performCheck($urlId, $url);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['check_data']['status_code']);
    }

    public function testPerformCheckEmptyContent(): void
    {
        // Проверяет проверку сайта с пустым содержимым
        $urlId = 1;
        $url = 'https://empty-site.com';

        // Настраиваем успешный ответ с пустым телом
        $this->httpClient->method('fetchUrl')->willReturn([
            'success' => true,
            'status_code' => 200,
            'body' => ''
        ]);

        // Настраиваем парсинг пустого контента
        $this->pageParser->method('parsePageContent')->willReturn([
            'h1' => null,
            'title' => null,
            'description' => null
        ]);

        // Ожидаем сохранение проверки
        $this->urlCheckModel->expects($this->once())->method('saveUrlCheck');

        $result = $this->urlCheckService->performCheck($urlId, $url);

        $this->assertTrue($result['success']);
    }
}
