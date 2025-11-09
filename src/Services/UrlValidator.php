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

        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            return ['errorMessage' => 'Некорректный URL'];
        }

        $host = $parsedUrl['host'];

        // Проверяем что схема указана (убираем избыточную проверку на string)
        if (!isset($parsedUrl['scheme'])) {
            return ['errorMessage' => 'Некорректный URL'];
        }

        $scheme = $parsedUrl['scheme'];

        // Проверка что хост не заканчивается на точку
        if (str_ends_with($host, '.')) {
            return ['errorMessage' => 'Некорректный URL'];
        }

        // Проверка TLD
        $lastDotPos = strrpos($host, '.');
        if ($lastDotPos === false || strlen($host) - $lastDotPos - 1 < 2) {
            return ['errorMessage' => 'Некорректный URL'];
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
