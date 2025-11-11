<?php

namespace Hexlet\Code\Models;

use PDO;
use Hexlet\Code\Services\FieldValidator;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected FieldValidator $validator;

    public function __construct(PDO $db, FieldValidator $validator = null)
    {
        $this->db = $db;
        $this->validator = $validator ?? new FieldValidator();
    }

    public function insert(array $data): int
    {
        $this->validator->validateAllFields($data, $this->table);

        $filteredData = array_filter($data, fn($value) => $value !== null);
        $fields = array_keys($filteredData);
        $values = array_values($filteredData);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return (int)$this->db->lastInsertId();
    }

    public function findAll(string $orderBy = 'id'): array
    {
        $this->validator->validateField($orderBy, $this->table);

        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} DESC";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll();
        return $result ?: [];
    }

    public function findOneBy(string $field, string $value, string $orderBy = ''): ?array
    {
        $this->validator->validateField($field, $this->table);

        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";

        if ($orderBy) {
            $this->validator->validateField($orderBy, $this->table);
            $sql .= " ORDER BY {$orderBy} DESC";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAllBy(string $field, string $value, string $orderBy = ''): array
    {
        $this->validator->validateField($field, $this->table);

        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";

        if ($orderBy) {
            $this->validator->validateField($orderBy, $this->table);
            $sql .= " ORDER BY {$orderBy} DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetchAll();
        return $result ?: [];
    }
}
