<?php

namespace Hexlet\Code\Models;

use PDO;

class UrlCheck
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Найти все URL
     */
    public function findByCheaks(): array
    {
        $stmt = $this->db->query("SELECT * FROM url_checks ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUrlId(int $urlId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC");
        $stmt->execute([$urlId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLastCheck($urlId)
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$urlId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save($urlId, $data)
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $urlId,
            $data['status_code'] ?? null,
            $data['h1'] ?? null, 
            $data['title'] ?? null,
            $data['description'] ?? null
            ]);
    
        return (int)$this->db->lastInsertId();
    }   

    public function getLastCheckDate($urlId)
    {
        $sql = "SELECT created_at FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$urlId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['created_at'] : null;
    }
}