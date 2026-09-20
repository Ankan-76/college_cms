<?php
// views/admin/delete-timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        use Config\Database;
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT department_id, semester_id FROM timetables WHERE id = ?");
        $stmt->execute([$id]);
        $timetable = $stmt->fetch();
        
        if ($timetable) {
            $deleteStmt = $db->prepare("DELETE FROM timetables WHERE id = ?");
            $deleteStmt->execute([$id]);
            header('Location: timetables.php?department_id=' . $timetable['department_id'] . '&semester_id=' . $timetable['semester_id']);
            exit;
        }
    }
}
header('Location: timetables.php');
exit;
