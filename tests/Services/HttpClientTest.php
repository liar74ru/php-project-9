<?php

namespace Hexlet\Code\Tests\Services;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Services\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
        // Arrange
        $url = 'https://example.com';
        $expectedBody = '<html>Test Content</html>';

        // Создаем мок StreamInterface вместо строки
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('__toString')
            ->willReturn($expectedBody);

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->isArray())
            ->willReturn($response);

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals($expectedBody, $result['body']);
        $this->assertNull($result['error']);
    }
    public function testFetchUrlWithConnectException(): void
    {
        // Arrange
        $url = 'https://unreachable-site.com';

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->willThrowException(new ConnectException(
                'Connection failed',
                $this->createMock(RequestInterface::class)
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('connect_error', $result['error']);
    }

    public function testFetchUrlWithRequestException(): void
    {
        // Arrange
        $url = 'https://example.com/not-found';

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->willThrowException(new RequestException(
                'Not Found',
                $request,
                $response
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('request_error', $result['error']);
    }

    public function testFetchUrlWithRequestExceptionWithResponse(): void
    {
        // Arrange
        $url = 'https://example.com/not-found';

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->isArray())
            ->willThrowException(new RequestException(
                'Not Found',
                $request,
                $response
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('request_error', $result['error']);
    }

    public function testFetchUrlWithRequestExceptionWithoutResponse(): void
    {
        // Arrange
        $url = 'https://invalid-protocol.com';

        $request = $this->createMock(RequestInterface::class);

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->isArray())
            ->willThrowException(new RequestException(
                'Invalid protocol',
                $request,
                null // Нет response
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('request_error', $result['error']);
    }

    public function testFetchUrlWithOtherException(): void
    {
        // Arrange
        $url = 'https://example.com';

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->isArray())
            ->willThrowException(new \InvalidArgumentException('Invalid URL'));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('unknown_error', $result['error']);
    }

    public function testFetchUrlWithTimeout(): void
    {
        // Arrange
        $url = 'https://slow-site.com';

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $url,
                $this->callback(function ($options) {
                    return $options['timeout'] === 10;
                })
            )
            ->willThrowException(new ConnectException(
                'Operation timed out',
                $this->createMock(RequestInterface::class)
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertNull($result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('connect_error', $result['error']);
    }

    public function testFetchUrlWithDifferentStatusCodes(): void
    {
        // Arrange
        $url = 'https://example.com/server-error';

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->isArray())
            ->willThrowException(new RequestException(
                'Internal Server Error',
                $request,
                $response
            ));

        // Act
        $result = $this->httpClient->fetchUrl($url);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['status_code']);
        $this->assertNull($result['body']);
        $this->assertEquals('request_error', $result['error']);
    }
}
