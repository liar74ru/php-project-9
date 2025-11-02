<?php

namespace Hexlet\Code\Services;

class PageParser
{
    public function parsePageContent(string $html): array
    {
        return [
            'h1' => $this->trimText($this->extractByRegex($html, '/<h1[^>]*>(.*?)<\/h1>/si')),
            'title' => $this->trimText($this->extractByRegex($html, '/<title[^>]*>(.*?)<\/title>/si')),
            'description' => $this->trimText(
                $this->extractByRegex(
                    $html,
                    '/<meta[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\'][^>]*>/si'
                )
            )
        ];
    }

// Метод для извлечения через регулярки
    private function extractByRegex(string $html, string $pattern): string
    {
        if (preg_match($pattern, $html, $matches)) {
            $text = trim(strip_tags($matches[1]));
            return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return '';
    }

    private function trimText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $text = trim($text);
        return mb_strlen($text) > 255 ? mb_substr($text, 0, 252) . '...' : $text;
    }
}
