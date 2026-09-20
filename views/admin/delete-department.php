<?php
// views/admin/delete-department.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');

use Config\Database;
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        try {
            $stmt = $db->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Error handling could be improved here (e.g., using sessions to pass error back to departments.php)
            // But for now, we just proceed to redirect.
        }
    }
}

header("Location: departments.php");
exit;

