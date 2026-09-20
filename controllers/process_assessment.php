<?php
// controllers/process_assessment.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('FACULTY');
require_once __DIR__ . '/AssessmentController.php';

use Controllers\AssessmentController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$controller = new AssessmentController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $maxMarks = isset($_POST['max_marks']) ? (int)$_POST['max_marks'] : 100;

        if ($courseId > 0 && !empty($title) && $maxMarks > 0) {
            $result = $controller->createAssessment($courseId, $facultyId, $title, $maxMarks);
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Please provide valid course, title, and max marks.';
        }
        
        // Redirect back with course_id pre-selected
        $redirectUrl = '../views/faculty/manage_marks.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $assessmentId = (int)$_GET['id'];
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        
        $result = $controller->deleteAssessment($assessmentId, $facultyId);
        
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        
        $redirectUrl = '../views/faculty/manage_marks.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Fallback redirect
header('Location: ../views/faculty/manage_marks.php');
exit;
