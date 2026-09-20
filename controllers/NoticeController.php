<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;

class NoticeController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Broadcasting systems and attachment uploader routing securely configured tracking uploads/notices schemas safely mapping binary transfers.

    /**
     * Retrieve notices meant for a specific role or ALL.
     *
     * @param string $role The target role (e.g., 'FACULTY', 'STUDENT').
     * @return array List of notices.
     */
    public function getNoticesForRole(string $role): array {
        try {
            $stmt = $this->db->prepare("
                SELECT n.*, a.name as author
                FROM notices n
                JOIN admins a ON n.created_by = a.id
                WHERE n.target_role IN ('ALL', ?)
                ORDER BY n.is_pinned DESC, n.created_at DESC
            ");
            $stmt->execute([strtoupper($role)]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error fetching notices for role {$role}: " . $e->getMessage());
            return [];
        }
    }
}
