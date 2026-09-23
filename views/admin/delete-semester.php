<?php
// views/admin/delete-semester.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('semesters');

use Config\Database;
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        try {
            $stmt = $db->prepare("DELETE FROM semesters WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Error handling could be improved here
        }
    }
}

header("Location: semesters.php");
exit;

