<?php
// views/admin/delete-assignment.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('subject_assignments');

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM course_assignments WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header('Location: subject_assignments.php');
exit;
