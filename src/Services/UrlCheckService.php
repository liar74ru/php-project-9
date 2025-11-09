<?php

namespace Hexlet\Code\Services;

use Hexlet\Code\Models\UrlCheck;

class UrlCheckService
{
    public function __construct(
        private readonly UrlCheck $urlCheckModel,
        private readonly HttpClient $httpClient,
        private readonly PageParser $pageParser
    ) {
    }

    public function performCheck(int $urlId, string $url): array
    {
        $httpResult = $this->httpClient->fetchUrl($url);

        // Если запрос не удался
        if (!$httpResult['success']) {
            $checkData = [
                'status_code' => $httpResult['status_code'] ?? 0,
                'h1' => null,
                'title' => null,
                'description' => $httpResult['error'] ?? 'Unknown error'
            ];

            // Сохраняем только если есть статус код
            if ($httpResult['status_code'] !== null) {
                $this->urlCheckModel->saveUrlCheck($urlId, $checkData);
            }

            return ['success' => false, 'check_data' => $checkData];
        }

        // Успешный запрос - парсим и сохраняем
        $parsedData = $this->pageParser->parsePageContent($httpResult['body']);

        $checkData = [
            'status_code' => $httpResult['status_code'],
            'h1' => $parsedData['h1'],
            'title' => $parsedData['title'],
            'description' => $parsedData['description']
        ];

        $this->urlCheckModel->saveUrlCheck($urlId, $checkData);

        return [
            'success' => true,
            'check_data' => $checkData
        ];
    }
}
