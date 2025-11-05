<?php

namespace Hexlet\Code\Services;

use Valitron\Validator;

class UrlValidator
{
    public function validate(string $url): array
    {
        $v = new Validator(['url' => $url]);
        $v->rule('required', 'url');
        $v->rule('url', 'url');
        $v->rule('lengthMax', 'url', 255);

        // Проверка на корректность URL
        if (!$v->validate()) {
            return [
                'errorMessage' => 'Некорректный URL'
            ];
        }

        // Используем parse_url один раз и проверяем все компоненты
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['host']) || !is_string($parsedUrl['host'])) {
            return [
                'errorMessage' => 'Некорректный URL: не удалось извлечь хост'
            ];
        }

        $host = $parsedUrl['host'];
        $scheme = $parsedUrl['scheme'] ?? 'https';

        // Проверяем что схема - строка
        if (!is_string($scheme)) {
            return [
                'errorMessage' => 'Некорректный URL: не удалось извлечь схему'
            ];
        }

        // Проверка что хост не заканчивается на точку
        if (str_ends_with($host, '.')) {
            return [
                'errorMessage' => 'Некорректный URL: хост не может заканчиваться точкой'
            ];
        }

        // Проверка что после последней точки есть хотя бы 2 символа (TLD)
        $lastDotPos = strrpos($host, '.');
        if ($lastDotPos === false || strlen($host) - $lastDotPos - 1 < 2) {
            return [
                'errorMessage' => 'Некорректный URL: домен верхнего уровня слишком короткий'
            ];
        }

        // Нормализация URL
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        return ['url' => $scheme . '://' . $host];
    }
}
