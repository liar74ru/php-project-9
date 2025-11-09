<?php

namespace Hexlet\Code\Models;

use Carbon\Carbon;

class UrlCheck extends Model
{
    protected string $table = 'url_checks';

    public function findByUrlId(int $urlId): array
    {
        $orderBy = 'created_at DESC';
        return $this->findAllBy('url_id', $urlId, $orderBy);
    }

    public function findLastCheck(int $urlId): ?array
    {
        return $this->findOneBy('url_id', $urlId, 'created_at DESC');
    }

    public function saveUrlCheck(int $urlId, array $data): int
    {
        $data = [
            'url_id' => $urlId,
            'status_code' => $data['status_code'] ?? null,
            'h1' => $data['h1'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => Carbon::now()->toDateTimeString()
            ];

        return (int) $this->insert($data);
    }
}
