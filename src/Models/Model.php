<?php

namespace Hexlet\Code\Models;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // 1. Сохранить строку
    public function insert(array $data): int
    {
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

    // 2. Найти все строки с сортировкой
    public function findAll(string $orderBy = 'id DESC'): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetchAll();
        return $result ?: [];
    }

    // 3. Найти строку по одному условию
    public function findOneBy(string $field, string $value, string $orderBy = ''): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} LIMIT 1";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    // 4. Найти все строки по условию
    public function findAllBy(string $field, string $value, string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetchAll();
        return $result ?: [];
    }
}
