<?php
// controllers/process_attendance.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('FACULTY');

require_once __DIR__ . '/AttendanceController.php';

use Controllers\AttendanceController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token (simplified for demo, should match what's in your framework)
    // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== 'dummy_csrf_token_for_demo') { ... }

    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $date = isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '';
    $attendanceData = isset($_POST['attendance']) ? $_POST['attendance'] : [];
    $facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];

    if (!$courseId || !$date || empty($attendanceData)) {
        $_SESSION['flash_error'] = 'Invalid attendance data submitted.';
        header("Location: ../views/faculty/take_attendance.php?course_id={$courseId}&date={$date}");
        exit;
    }

    $controller = new AttendanceController();
    $result = $controller->submitBatchAttendance($courseId, $date, $attendanceData, $facultyId);

    if ($result['success']) {
        $_SESSION['flash_success'] = $result['message'];
    } else {
        $_SESSION['flash_error'] = $result['message'];
    }

    // Redirect back to the take attendance page with the same filters
    header("Location: ../views/faculty/take_attendance.php?course_id={$courseId}&date={$date}");
    exit;
} else {
    // Invalid request method
    header('Location: ../views/faculty/take_attendance.php');
    exit;
}
