<?php
// controllers/process_leave.php — POST handler for leave operations
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/LeaveController.php';

use Controllers\LeaveController;

// Must be authenticated
require_auth();

$action = $_POST['action'] ?? '';
$controller = new LeaveController();

// ── Apply Leave (Student or Faculty) ─────────────────────────
if ($action === 'apply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        $role = strtolower($_SESSION['role_name']);
        redirect("/views/{$role}/apply_leave.php");
    }

    $role = $_SESSION['role_name']; // STUDENT or FACULTY
    if (!in_array($role, ['STUDENT', 'FACULTY'])) {
        set_flash_message('Only students and faculty can apply for leave.', 'error');
        redirect('/');
    }

    $userId = (int) $_SESSION['user_id'];
    $leaveType = $_POST['leave_type'] ?? 'CASUAL';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $customSubject = $_POST['custom_subject'] ?? '';

    // Handle supporting document uploads (up to 3 files)
    $supportingDocs = [];
    $uploadDir = __DIR__ . '/../uploads/leaves/';
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/msword', 
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (!empty($_FILES['supporting_docs']['name'][0])) {
        $fileCount = min(count($_FILES['supporting_docs']['name']), 3);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['supporting_docs']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileType = $_FILES['supporting_docs']['type'][$i];
            $fileSize = $_FILES['supporting_docs']['size'][$i];
            $originalName = $_FILES['supporting_docs']['name'][$i];

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                set_flash_message("File '{$originalName}' has an unsupported format. Allowed: PDF, JPG, PNG, WebP, DOC, DOCX.", 'error');
                $rolePath = strtolower($role);
                redirect("/views/{$rolePath}/apply_leave.php");
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                set_flash_message("File '{$originalName}' exceeds the 5MB size limit.", 'error');
                $rolePath = strtolower($role);
                redirect("/views/{$rolePath}/apply_leave.php");
            }

            // Generate unique filename
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $safeName = 'leave_' . $userId . '_' . time() . '_' . $i . '.' . $ext;
            $destPath = $uploadDir . $safeName;

            if (move_uploaded_file($_FILES['supporting_docs']['tmp_name'][$i], $destPath)) {
                $supportingDocs[] = [
                    'file' => $safeName,
                    'name' => $originalName,
                    'size' => $fileSize,
                ];
            }
        }
    }

    $controller->applyLeave($userId, $role, $leaveType, $startDate, $endDate, $reason, $customSubject, $supportingDocs);

    $rolePath = strtolower($role);
    redirect("/views/{$rolePath}/my_leaves.php");
}

// ── Review Leave (Admin Only) ────────────────────────────────
if ($action === 'review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        redirect('/views/admin/leave_requests.php');
    }

    require_role('ADMIN');

    $leaveId = (int) ($_POST['leave_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $adminId = (int) $_SESSION['user_id'];

    $controller->reviewLeave($leaveId, $status, $remarks, $adminId);
    redirect('/views/admin/leave_requests.php');
}

// ── Delete Leave (Student or Faculty) ─────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        $role = strtolower($_SESSION['role_name']);
        redirect("/views/{$role}/my_leaves.php");
    }

    $role = $_SESSION['role_name'];
    if (!in_array($role, ['STUDENT', 'FACULTY'])) {
        set_flash_message('Unauthorized action.', 'error');
        redirect('/');
    }

    $leaveId = (int) ($_POST['leave_id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    $leave = $controller->getLeaveForUser($leaveId, $userId, $role);

    if ($leave) {
        // Delete associated files to clear storage
        if (!empty($leave['supporting_docs'])) {
            $docs = json_decode($leave['supporting_docs'], true);
            if (is_array($docs)) {
                $uploadDir = __DIR__ . '/../uploads/leaves/';
                foreach ($docs as $doc) {
                    $filePath = $uploadDir . basename($doc['file']);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        // Delete from database
        if ($controller->deleteLeave($leaveId, $userId, $role)) {
            set_flash_message('Leave request deleted successfully.', 'success');
        } else {
            set_flash_message('Failed to delete leave request.', 'error');
        }
    } else {
        set_flash_message('Leave request not found or unauthorized.', 'error');
    }

    $rolePath = strtolower($role);
    redirect("/views/{$rolePath}/my_leaves.php");
}

// Fallback
redirect('/');
