<?php

namespace Hexlet\Code\Models;

use PDO;
use Hexlet\Code\Services\Validator;

class Url
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM urls ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE id = ?");
        $stmt->execute([$id]);
        $urlData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $urlData ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE name = ?");
        $stmt->execute([$name]);
        $urlData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $urlData ?: null;
    }

    public function save(string $name): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO urls (name, created_at) VALUES (?, NOW())");
        $stmt->execute([$name]);
        return (int)$this->pdo->lastInsertId();
    }

    public function validate(array $data): array
    {
        return Validator::validateUrl($data);
    }

    public function validateUrlString(string $url): array
    {
        return Validator::validateUrlSimple($url);
    }
}