<?php
// controllers/process_feedback.php — POST handler for feedback operations
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/FeedbackController.php';

use Controllers\FeedbackController;

$controller = new FeedbackController();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// ── Action: Submit Feedback ─────────────────────────────────
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed (Invalid CSRF token). Please try again.', 'error');
        redirect('/feedback.php');
    }

    // Determine user role and ID from session if authenticated
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $userRole = 'GUEST';
    if (!empty($_SESSION['role_name'])) {
        $userRole = strtoupper($_SESSION['role_name']);
    }

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $category = sanitize($_POST['category'] ?? 'General');
    $subject = sanitize($_POST['subject'] ?? '');
    $rating = !empty($_POST['rating']) ? (int)$_POST['rating'] : null;
    $message = trim($_POST['message'] ?? '');

    // Allow user to indicate their role if guest, e.g., Visitor, Parent, Alumni
    if ($userRole === 'GUEST' && !empty($_POST['role_affinity'])) {
        $roleAffinity = sanitize($_POST['role_affinity']);
        if ($roleAffinity !== 'GUEST') {
            $category = $category . ' (' . $roleAffinity . ')';
        }
    }

    $data = [
        'user_id' => $userId,
        'user_role' => $userRole,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'category' => $category,
        'subject' => $subject,
        'rating' => $rating,
        'message' => $message,
    ];

    $success = $controller->submitFeedback($data);
    redirect('/feedback.php');

// ── Action: Update Status (Admin Only) ──────────────────────
} elseif ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('ADMIN');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Action failed: Invalid CSRF token.', 'error');
        redirect('/views/admin/view-feedback.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'NEW');
    $adminNotes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : null;

    if ($id <= 0) {
        set_flash_message('Invalid feedback ID.', 'error');
        redirect('/views/admin/view-feedback.php');
    }

    $controller->updateStatus($id, $status, $adminNotes);
    redirect('/views/admin/view-feedback.php');

// ── Action: Delete Feedback (Admin Only) ─────────────────────
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('ADMIN');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Action failed: Invalid CSRF token.', 'error');
        redirect('/views/admin/view-feedback.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        set_flash_message('Invalid feedback ID.', 'error');
        redirect('/views/admin/view-feedback.php');
    }

    $controller->deleteFeedback($id);
    redirect('/views/admin/view-feedback.php');

} else {
    redirect('/feedback.php');
}
