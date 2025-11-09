<?php

namespace Hexlet\Code\Tests\Services;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Services\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class HttpClientTest extends TestCase
{
    private HttpClient $httpClient;
    private Client $guzzleClient;

    protected function setUp(): void
    {
        $this->guzzleClient = $this->createMock(Client::class);
        $this->httpClient = new HttpClient($this->guzzleClient);
    }

    public function testFetchUrlSuccess(): void
    {
        // Проверяет успешный запрос к сайту
        $url = 'https://example.com';

        // Создаем мок StreamInterface
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('<html>Content</html>');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream); // Возвращаем StreamInterface

        $this->guzzleClient->method('request')->willReturn($response);

        $result = $this->httpClient->fetchUrl($url);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('<html>Content</html>', $result['body']);
    }

    public function testFetchUrlConnectionError(): void
    {
        // Проверяет ошибку подключения (сайт недоступен)
        $url = 'https://unreachable-site.com';

        $this->guzzleClient->method('request')
            ->willThrowException(new ConnectException('Connection failed', $this->createMock(RequestInterface::class)));

        $result = $this->httpClient->fetchUrl($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('connect_error', $result['error']);
    }

    public function testFetchUrlHttpError(): void
    {
        // Проверяет HTTP ошибку (404, 500 и т.д.)
        $url = 'https://example.com/not-found';

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->guzzleClient->method('request')
            ->willThrowException(new RequestException('Not Found', $request, $response));

        $result = $this->httpClient->fetchUrl($url);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertEquals('request_error', $result['error']);
    }

    public function testFetchUrlUnknownError(): void
    {
        // Проверяет обработку неизвестных ошибок
        $url = 'https://example.com';

        $this->guzzleClient->method('request')
            ->willThrowException(new \InvalidArgumentException('Invalid URL'));

        $result = $this->httpClient->fetchUrl($url);

        $this->assertFalse($result['success']);
        $this->assertEquals('unknown_error', $result['error']);
    }
}
