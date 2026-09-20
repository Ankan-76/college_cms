<?php
// views/admin/delete-subject.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');

use Config\Database;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header('Location: subjects.php');
exit;
