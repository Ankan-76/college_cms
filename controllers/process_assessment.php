<?php
// controllers/process_assessment.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('FACULTY');
require_once __DIR__ . '/AssessmentController.php';

use Controllers\AssessmentController;

$facultyId = (int)($_SESSION['faculty_profile_id'] ?? $_SESSION['user_id']);
$controller = new AssessmentController();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ══════════════════════════════════════════════════════════
// 1. ACTION: EXPORT CSV
// ══════════════════════════════════════════════════════════
if ($action === 'export_csv') {
    $assessmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

    $assessment = $controller->getAssessmentById($assessmentId, $facultyId);
    if (!$assessment) {
        $_SESSION['flash_error'] = 'Assessment not found or access denied.';
        header('Location: ../views/faculty/manage_marks.php' . ($courseId ? '?course_id=' . $courseId : ''));
        exit;
    }

    $students = $controller->getStudentsWithMarks($assessmentId, (int)$assessment['course_id']);
    $maxMarks = (float)$assessment['max_marks'];

    $cleanTitle = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $assessment['title']);
    $cleanCourse = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $assessment['course_code']);
    $filename = "Marks_{$cleanCourse}_{$cleanTitle}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    $passMarks = isset($assessment['pass_marks']) ? (float)$assessment['pass_marks'] : round($maxMarks * 0.4);

    // Assessment Info Header
    fputcsv($output, ['Course:', $assessment['course_code'] . ' - ' . $assessment['course_name']]);
    fputcsv($output, ['Assessment:', $assessment['title']]);
    fputcsv($output, ['Maximum Marks:', $maxMarks]);
    fputcsv($output, ['Pass Marks:', $passMarks]);
    fputcsv($output, ['Exported Date:', date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row

    // Table Header
    fputcsv($output, [
        'Sl No',
        'Roll Number',
        'Registration Number',
        'Student Name',
        'Email Address',
        'Marks Obtained',
        'Max Marks',
        'Percentage (%)',
        'Result',
        'Remarks'
    ]);

    $sl = 1;
    foreach ($students as $s) {
        $marksObt = $s['marks_obtained'] !== null ? (float)$s['marks_obtained'] : null;
        $pct = ($marksObt !== null && $maxMarks > 0) ? round(($marksObt / $maxMarks) * 100, 1) : '';
        $result = '';
        if ($marksObt !== null) {
            $result = ($marksObt >= $passMarks) ? 'PASS' : 'FAIL';
        } else {
            $result = 'UNGRADED';
        }

        fputcsv($output, [
            $sl++,
            $s['roll_number'],
            $s['registration_number'],
            $s['name'],
            $s['email'],
            $marksObt !== null ? $marksObt : 'N/A',
            $maxMarks,
            $pct !== '' ? $pct . '%' : 'N/A',
            $result,
            $s['remarks'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

// ══════════════════════════════════════════════════════════
// 2. POST ACTIONS (Strict CSRF Token Protected)
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedToken)) {
        $_SESSION['flash_error'] = 'Security verification failed (Invalid CSRF Token). Please reload and try again.';
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        header('Location: ../views/faculty/manage_marks.php' . ($courseId ? '?course_id=' . $courseId : ''));
        exit;
    }

    // A. CREATE ASSESSMENT
    if ($action === 'create') {
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $maxMarks = isset($_POST['max_marks']) ? (int)$_POST['max_marks'] : 100;
        $passMarks = isset($_POST['pass_marks']) ? (int)$_POST['pass_marks'] : (int)round($maxMarks * 0.4);

        if ($courseId > 0 && !empty($title) && $maxMarks > 0) {
            if ($passMarks > $maxMarks) {
                $_SESSION['flash_error'] = 'Pass marks cannot exceed maximum marks.';
            } else {
                $result = $controller->createAssessment($courseId, $facultyId, $title, $maxMarks, $passMarks);
                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                } else {
                    $_SESSION['flash_error'] = $result['message'];
                }
            }
        } else {
            $_SESSION['flash_error'] = 'Please provide a valid course, assessment title, and positive maximum marks.';
        }

        header('Location: ../views/faculty/manage_marks.php?course_id=' . $courseId);
        exit;
    }

    // B. UPDATE ASSESSMENT
    if ($action === 'update') {
        $assessmentId = isset($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0;
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $maxMarks = isset($_POST['max_marks']) ? (int)$_POST['max_marks'] : 100;
        $passMarks = isset($_POST['pass_marks']) ? (int)$_POST['pass_marks'] : (int)round($maxMarks * 0.4);

        if ($assessmentId > 0 && !empty($title) && $maxMarks > 0) {
            if ($passMarks > $maxMarks) {
                $_SESSION['flash_error'] = 'Pass marks cannot exceed maximum marks.';
            } else {
                $result = $controller->updateAssessment($assessmentId, $facultyId, $title, $maxMarks, $passMarks);
                if ($result['success']) {
                    $_SESSION['flash_success'] = $result['message'];
                } else {
                    $_SESSION['flash_error'] = $result['message'];
                }
            }
        } else {
            $_SESSION['flash_error'] = 'Please provide valid assessment details.';
        }

        header('Location: ../views/faculty/manage_marks.php?course_id=' . $courseId);
        exit;
    }

    // C. BATCH SAVE MARKS
    if ($action === 'save_marks') {
        $assessmentId = isset($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0;
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $marks = isset($_POST['marks']) && is_array($_POST['marks']) ? $_POST['marks'] : [];
        $remarks = isset($_POST['remarks']) && is_array($_POST['remarks']) ? $_POST['remarks'] : [];

        if ($assessmentId > 0) {
            $result = $controller->saveBatchMarks($assessmentId, $facultyId, $marks, $remarks);
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid assessment reference.';
        }

        header("Location: ../views/faculty/manage_marks.php?course_id={$courseId}&assessment_id={$assessmentId}");
        exit;
    }

    // D. DELETE ASSESSMENT (POST form submission)
    if ($action === 'delete') {
        $assessmentId = isset($_POST['assessment_id']) ? (int)$_POST['assessment_id'] : 0;
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

        $result = $controller->deleteAssessment($assessmentId, $facultyId);
        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header('Location: ../views/faculty/manage_marks.php?course_id=' . $courseId);
        exit;
    }
}

// ══════════════════════════════════════════════════════════
// 3. GET ACTIONS (Fallback Support with Warnings)
// ══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $assessmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    // Validate GET CSRF token if provided
    $token = $_GET['csrf_token'] ?? '';
    if (!empty($token) && !verify_csrf_token($token)) {
        $_SESSION['flash_error'] = 'Security verification failed.';
        header('Location: ../views/faculty/manage_marks.php?course_id=' . $courseId);
        exit;
    }

    $result = $controller->deleteAssessment($assessmentId, $facultyId);
    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
    } else {
        $_SESSION['flash_error'] = $result['message'];
    }

    header('Location: ../views/faculty/manage_marks.php?course_id=' . $courseId);
    exit;
}

// Fallback redirect
header('Location: ../views/faculty/manage_marks.php');
exit;
