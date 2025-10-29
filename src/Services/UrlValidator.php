<?php

namespace Hexlet\Code\Services;

use Valitron\Validator;

class UrlValidator
{
    public function validate(string $url): array
    {
        // Создаем валидатор
        $v = new Validator(['url' => $url]);

        // Правила валидации
        $v->rule('required', 'url')->message('URL не должен быть пустым');
        $v->rule('url', 'url')->message('Некорректный URL');
        $v->rule('lengthMax', 'url', 255)->message('URL не должен превышать 255 символов');

        // Дополнительная проверка что URL имеет схему
        $v->rule(function ($field, $value, $params, $fields) {
            return parse_url($value, PHP_URL_SCHEME) !== null;
        }, 'url')->message('URL должен содержать схему (http:// или https://)');

        if ($v->validate()) {
            return []; // Нет ошибок
        } else {
            // Берем первую ошибку
            $errors = $v->errors();
            $firstError = $this->getFirstError($errors);

            return [
                'errorMessage' => $firstError
            ];
        }
    }

    private function getFirstError(array $errors): string
    {
        foreach ($errors as $fieldErrors) {
            if (is_array($fieldErrors) && !empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return 'Произошла ошибка валидации';
    }

    // Дополнительный метод для нормализации URL
    public function normalizeUrl(string $url): string
    {
        // Убедимся что URL имеет схему
        if (!parse_url($url, PHP_URL_SCHEME)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}
