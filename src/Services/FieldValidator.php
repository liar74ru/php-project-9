<?php

namespace Hexlet\Code\Services;

use InvalidArgumentException;

class FieldValidator
{
    private array $tableSchemas;

    public function __construct()
    {
        $this->tableSchemas = [
            'urls' => ['id', 'name', 'created_at'],
            'url_checks' => ['id', 'url_id', 'status_code', 'h1', 'title', 'description', 'created_at'],
        ];
    }

    public function validateField(string $field, string $table): void
    {
        $allowedFields = $this->getTableColumns($table);
        if (!in_array($field, $allowedFields)) {
            throw new InvalidArgumentException("Field '{$field}' is not allowed in table '{$table}'");
        }
    }

    public function validateAllFields(array $data, string $table): void
    {
        foreach (array_keys($data) as $field) {
            $this->validateField($field, $table);
        }
    }

    public function getTableColumns(string $table): array
    {
        if (!isset($this->tableSchemas[$table])) {
            throw new InvalidArgumentException("Unknown table: '{$table}'");
        }

        return $this->tableSchemas[$table];
    }
}
