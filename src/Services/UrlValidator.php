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

        if (
            filter_var($url, FILTER_VALIDATE_URL) === false
            || strlen($url) > 255
            || pathinfo($url, PATHINFO_EXTENSION) === ''
        ) {
            return [
                'errorMessage' => 'Некорректный URL'
            ];
        }
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(parse_url($url, PHP_URL_HOST));

        return ['url' => $scheme . '://' . $host];
    }
}
