<?php
// controllers/process_material.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('FACULTY');
require_once __DIR__ . '/MaterialController.php';

use Controllers\MaterialController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$controller = new MaterialController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $file = $_FILES['material_file'] ?? null;

        if ($courseId > 0 && !empty($title) && $file) {
            $result = $controller->uploadMaterial($courseId, $facultyId, $title, $file);
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Please provide course, title, and select a file to upload.';
        }
        
        header('Location: ../views/faculty/study_materials.php');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $materialId = (int)$_GET['id'];
        $result = $controller->deleteMaterial($materialId, $facultyId);
        
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        
        header('Location: ../views/faculty/study_materials.php');
        exit;
    }
}

// Fallback redirect
header('Location: ../views/faculty/study_materials.php');
exit;
