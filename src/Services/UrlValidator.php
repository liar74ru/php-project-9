<?php

namespace Hexlet\Code\Services;

class UrlValidator
{
    public function validate(string $url): array
    {
        $result = [
            'url' => null,
            'errorMessage' => null
        ];
        // Проверка на корректность URL
        if (
            filter_var($url, FILTER_VALIDATE_URL) === false //проверка на валидность URL
            || strlen($url) > 255                           //проверка на длину
            || parse_url($url, PHP_URL_HOST) === null       //проверка на наличие хоста
        ) {
            return [
                'errorMessage' => 'Некорректный URL'
            ];
        }
        // Нормализация URL
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(parse_url($url, PHP_URL_HOST));

        return ['url' => $scheme . '://' . $host];
    }
}
