<?php

namespace Hexlet\Code\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class HttpClient
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function fetchUrl(string $url): array
    {
        try {
            $response = $this->client->request('GET', $url, [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; Page Analyzer Bot)'
                ]
            ]);

            return [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
                'error' => null
            ];
        } catch (ConnectException $e) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => 'connect_error'
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'body' => null,
                'error' => 'request_error'
            ];
        }
    }
}
