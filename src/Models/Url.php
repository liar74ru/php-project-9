<?php

namespace Hexlet\Code\Models;

use PDO;
use Carbon\Carbon;

class Url
{
    private PDO $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM urls ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM urls WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM urls WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    public function save(string $name): int
    {
        $stmt = $this->db->prepare("INSERT INTO urls (name, created_at) VALUES (?, ?)");
        $stmt->execute([$name, Carbon::now()->toDateTimeString()]);
        return (int) $this->db->lastInsertId();
    }
    public function exists(string $name): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM urls WHERE name = ?");
        $stmt->execute([$name]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
