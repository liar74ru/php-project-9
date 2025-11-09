<?php

namespace Hexlet\Code\Services;

use PDO;

class UrlService
{
    public function __construct(
        private \PDO $db
    ) {
    }

    public function findAllWithLastChecks(): array
    {
        $sql = "
            SELECT DISTINCT ON (u.id)
                u.id,
                u.name, 
                uc.created_at as last_check_date,
                uc.status_code as last_status_code
            FROM urls u
            LEFT JOIN url_checks uc ON u.id = uc.url_id
        ORDER BY u.id DESC;
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }
}
