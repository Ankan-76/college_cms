<?php
// controllers/process_assignment.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/AssignmentController.php';

use Controllers\AssignmentController;

$controller = new AssignmentController();

// ── Faculty: Create Assignment ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'create') {
        require_role('FACULTY');
        $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
        
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $maxMarks = isset($_POST['max_marks']) ? (int)$_POST['max_marks'] : 100;
        $deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : '';
        $refFile = $_FILES['reference_file'] ?? null;
        
        if ($courseId > 0 && !empty($title) && !empty($deadline)) {
            $result = $controller->createAssignment($courseId, $facultyId, $title, $description, $maxMarks, $deadline, $refFile);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = 'Please provide course, title, and deadline.';
        }
        
        $redirectUrl = '../views/faculty/assignments.php';
        if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    // ── Student: Submit Assignment ───────────────────────
    if ($_POST['action'] === 'submit') {
        require_role('STUDENT');
        $studentId = $_SESSION['user_id'];
        
        $assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
        $file = $_FILES['submission_file'] ?? null;
        
        if ($assignmentId > 0 && $file) {
            $result = $controller->submitAssignment($assignmentId, $studentId, $file);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = 'Please select a file to submit.';
        }
        
        header('Location: ../views/student/assignments.php');
        exit;
    }
    
    // ── Faculty: Grade Submission ────────────────────────
    if ($_POST['action'] === 'grade') {
        require_role('FACULTY');
        $gradedBy = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
        
        $submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
        $marks = isset($_POST['marks']) ? (float)$_POST['marks'] : 0;
        $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
        $assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
        
        if ($submissionId > 0) {
            $result = $controller->gradeSubmission($submissionId, $marks, $feedback, $gradedBy);
            $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = 'Invalid submission.';
        }
        
        $redirectUrl = '../views/faculty/view_submissions.php';
        if ($assignmentId > 0) $redirectUrl .= '?assignment_id=' . $assignmentId;
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// ── Faculty: Delete Assignment (GET) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    require_role('FACULTY');
    $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
    
    $assignmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    if ($assignmentId > 0) {
        $result = $controller->deleteAssignment($assignmentId, $facultyId);
        $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'];
    }
    
    $redirectUrl = '../views/faculty/assignments.php';
    if ($courseId > 0) $redirectUrl .= '?course_id=' . $courseId;
    header('Location: ' . $redirectUrl);
    exit;
}

// Fallback
header('Location: ../views/faculty/assignments.php');
exit;
