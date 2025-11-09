<?php

namespace Hexlet\Code\Models;

use Carbon\Carbon;

class Url extends Model
{
    protected string $table = 'urls';

    public function findAllUrl(): array //findAll
    {
        return $this->findAll();
    }

    public function findByIdUrl(int $id): ?array
    {
        return $this->findOneBy('id', (string) $id);
    }

    public function findByNameUrl(string $name): ?array
    {
        return $this->findOneBy('name', $name);
    }

    public function saveNewUrl(string $name): int
    {
        $data = [
            'name' => $name,
            'created_at' => Carbon::now()->toDateTimeString()
        ];
        return (int) $this->insert($data);
    }
}
