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
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || empty($host)) {
            return [
                'errorMessage' => 'Некорректный URL: не удалось извлечь хост'
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
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
        $host = strtolower(parse_url($url, PHP_URL_HOST));

        return ['url' => $scheme . '://' . $host];
    }
}
