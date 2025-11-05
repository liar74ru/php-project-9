<?php

namespace Hexlet\Code\Services;

use Valitron\Validator;

class UrlValidator
{
    public function validateFormData(array $data): array
    {
        // Извлекаем URL из данных формы
        $url = $data['url']['name'] ?? '';

        if (empty(trim($url))) {
            return ['errorMessage' => 'URL не может быть пустым'];
        }

        return $this->validateUrl($url);
    }

    public function validateUrl(string $url): array
    {
        $v = new Validator(['url' => $url]);
        $v->rule('required', 'url');
        $v->rule('url', 'url');
        $v->rule('lengthMax', 'url', 255);

        if (!$v->validate()) {
            return ['errorMessage' => 'Некорректный URL'];
        }

        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['host']) || !is_string($parsedUrl['host'])) {
            return ['errorMessage' => 'Некорректный URL: не удалось извлечь хост'];
        }

        $host = $parsedUrl['host'];
        $scheme = $parsedUrl['scheme'] ?? 'https';

        if (!is_string($scheme)) {
            return ['errorMessage' => 'Некорректный URL: не удалось извлечь схему'];
        }

        // Проверка что хост не заканчивается на точку
        if (str_ends_with($host, '.')) {
            return ['errorMessage' => 'Некорректный URL: хост не может заканчиваться точкой'];
        }

        // Проверка TLD
        $lastDotPos = strrpos($host, '.');
        if ($lastDotPos === false || strlen($host) - $lastDotPos - 1 < 2) {
            return ['errorMessage' => 'Некорректный URL: домен верхнего уровня слишком короткий'];
        }

        // Нормализация
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        return [
            'url' => $scheme . '://' . $host,
            'originalUrl' => $url
        ];
    }
}
