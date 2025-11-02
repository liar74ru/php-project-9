<?php

namespace Hexlet\Code\Services;

use Hexlet\Code\Models\UrlCheck;

class UrlCheckService
{
    public function __construct(
        private UrlCheck $urlCheckModel,
        private HttpClient $httpClient,
        private PageParser $pageParser
    ) {
    }

    public function performCheck(int $urlId, string $url): array
    {
        $result = $this->httpClient->fetchUrl($url);
        if (!$result['success']) {
            return [
                'success' => false,
                'check_data' => [
                    'status_code' => $result['status_code'],
                    'h1' => null,
                    'title' => null,
                    'description' => $result['error']
                ]
            ];
        }
        $body = mb_convert_encoding($result['body'], 'UTF-8', 'auto');
        $parsedData = $this->pageParser->parsePageContent($body);

        $checkData = [
                'status_code' => $result['status_code'],
                'h1' => $parsedData['h1'],
                'title' => $parsedData['title'],
                'description' => $parsedData['description']
            ];
        $this->urlCheckModel->save($urlId, $checkData);
        return [
            'success' => true,
            'check_data' => $checkData
        ];
    }
}
