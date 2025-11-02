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
        // Arrange
        $urlId = 1;
        $url = 'https://example.com';
        
        $httpResult = [
            'success' => true,
            'status_code' => 200,
            'content' => '<html><head><title>Test Title</title></head><body><h1>Test Heading</h1></body></html>'
        ];
        
        $parsedData = [
            'h1' => 'Test Heading',
            'title' => 'Test Title',
            'description' => 'Test Description'
        ];
        
        $this->httpClient->expects($this->once())
            ->method('fetchUrl')
            ->with($url)
            ->willReturn($httpResult);
            
        $this->pageParser->expects($this->once())
            ->method('parse')
            ->with($httpResult['content'])
            ->willReturn($parsedData);
            
        $this->urlCheckModel->expects($this->once())
            ->method('save')
            ->with($urlId, [
                'status_code' => 200,
                'h1' => 'Test Heading',
                'title' => 'Test Title',
                'description' => 'Test Description'
            ])
            ->willReturn(5);

        // Act
        $result = $this->urlCheckService->performCheck($urlId, $url);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Проверка завершена успешно', $result['message']);
    }

    public function testPerformCheckWithHttpError(): void
    {
        // Arrange
        $urlId = 1;
        $url = 'https://example.com';
        
        $httpResult = [
            'success' => false,
            'status_code' => 500,
            'error' => 'Connection failed'
        ];
        
        $this->httpClient->expects($this->once())
            ->method('fetchUrl')
            ->with($url)
            ->willReturn($httpResult);

        $this->pageParser->expects($this->never())->method('parse');
        $this->urlCheckModel->expects($this->never())->method('save');

        // Act
        $result = $this->urlCheckService->performCheck($urlId, $url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['check_data']['status_code']);
        $this->assertEquals('Connection failed', $result['check_data']['description']);
        $this->assertNull($result['check_data']['h1']);
        $this->assertNull($result['check_data']['title']);
    }

    public function testPerformCheckWith404Error(): void
    {
        // Arrange
        $urlId = 1;
        $url = 'https://example.com/not-found';
        
        $httpResult = [
            'success' => false,
            'status_code' => 404,
            'error' => 'Page not found'
        ];
        
        $this->httpClient->expects($this->once())
            ->method('fetchUrl')
            ->with($url)
            ->willReturn($httpResult);

        $this->pageParser->expects($this->never())->method('parse');
        $this->urlCheckModel->expects($this->never())->method('save');

        // Act
        $result = $this->urlCheckService->performCheck($urlId, $url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['check_data']['status_code']);
        $this->assertEquals('Page not found', $result['check_data']['description']);
    }

    public function testPerformCheckWithTimeoutError(): void
    {
        // Arrange
        $urlId = 1;
        $url = 'https://slow-website.com';
        
        $httpResult = [
            'success' => false,
            'status_code' => 0,
            'error' => 'Request timeout'
        ];
        
        $this->httpClient->expects($this->once())
            ->method('fetchUrl')
            ->with($url)
            ->willReturn($httpResult);

        $this->pageParser->expects($this->never())->method('parse');
        $this->urlCheckModel->expects($this->never())->method('save');

        // Act
        $result = $this->urlCheckService->performCheck($urlId, $url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['check_data']['status_code']);
        $this->assertEquals('Request timeout', $result['check_data']['description']);
    }

    public function testPerformCheckWithEmptyContent(): void
    {
        // Arrange
        $urlId = 1;
        $url = 'https://empty-page.com';
        
        $httpResult = [
            'success' => true,
            'status_code' => 200,
            'content' => ''
        ];
        
        $parsedData = [
            'h1' => null,
            'title' => null,
            'description' => null
        ];
        
        $this->httpClient->expects($this->once())
            ->method('fetchUrl')
            ->with($url)
            ->willReturn($httpResult);
            
        $this->pageParser->expects($this->once())
            ->method('parse')
            ->with('')
            ->willReturn($parsedData);
            
        $this->urlCheckModel->expects($this->once())
            ->method('save')
            ->with($urlId, [
                'status_code' => 200,
                'h1' => null,
                'title' => null,
                'description' => null
            ])
            ->willReturn(6);

        // Act
        $result = $this->urlCheckService->performCheck($urlId, $url);

        // Assert
        $this->assertTrue($result['success']);
    }
}