<?php

namespace Hexlet\Code\Services;

use DiDom\Document;
use DiDom\Element;

class PageParser
{
    public function parsePageContent(string $html): array
    {
        $document = new Document($html);

        return [
            'h1' => $this->trimText($this->extractH1($document)),
            'title' => $this->trimText($this->extractTitle($document)),
            'description' => $this->trimText($this->extractDescription($document))
        ];
    }

    private function extractH1(Document $document): string
    {
        $h1 = $document->first('h1');
        return ($h1 instanceof Element) ? $h1->text() : '';
    }

    private function extractTitle(Document $document): string
    {
        $title = $document->first('title');
        return ($title instanceof Element) ? $title->text() : '';
    }

    private function extractDescription(Document $document): string
    {
        $meta = $document->first('meta[name="description"]');
        return ($meta instanceof Element) ? ($meta->getAttribute('content') ?? '') : '';
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
