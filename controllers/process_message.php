<?php
// controllers/process_message.php — Handler for messaging operations
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/MessageController.php';

use Controllers\MessageController;

// Must be authenticated
require_auth();

$role = $_SESSION['role_name'] ?? '';
if (!in_array($role, ['STUDENT', 'FACULTY', 'ADMIN'])) {
    set_flash_message('Access denied.', 'error');
    redirect('/');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$controller = new MessageController();
$rolePath = strtolower($role);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ── Admin Broadcast Actions ───────────────────────────────────
if ($action === 'send_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed.', 'error');
        redirect('/views/admin/broadcasts.php');
    }

    if ($role !== 'ADMIN') {
        set_flash_message('Unauthorized.', 'error');
        redirect('/');
    }

    $content = $_POST['content'] ?? '';
    $adminId = (int) $_SESSION['user_id'];
    
    $targetType = $_POST['target_type'] ?? 'ALL';
    $validTargets = ['ALL', 'ALL_STUDENTS', 'ALL_FACULTY', 'STUDENT', 'FACULTY'];
    if (!in_array($targetType, $validTargets)) {
        $targetType = 'ALL';
    }
    
    $targetId = null;
    if ($targetType === 'STUDENT' && !empty($_POST['student_id'])) {
        $targetId = (int) $_POST['student_id'];
    } elseif ($targetType === 'FACULTY' && !empty($_POST['faculty_id'])) {
        $targetId = (int) $_POST['faculty_id'];
    }

    $priority = $_POST['priority'] ?? 'NORMAL';
    $isPinned = !empty($_POST['is_pinned']);

    if (!empty(trim($content))) {
        if ($controller->sendAdminBroadcast($adminId, $content, $targetType, $targetId, $priority, $isPinned)) {
            set_flash_message('Broadcast message sent successfully.', 'success');
        } else {
            set_flash_message('Failed to send broadcast.', 'error');
        }
    } else {
        set_flash_message('Message cannot be empty.', 'error');
    }

    redirect('/views/admin/broadcasts.php');
}

if ($action === 'unsend_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $broadcastId = (int) ($_POST['broadcast_id'] ?? 0);
    $unsend = isset($_POST['unsend']) ? (bool)$_POST['unsend'] : true;
    $success = $controller->unsendAdminBroadcast($broadcastId, $unsend);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message($unsend ? 'Broadcast announcement unsent.' : 'Broadcast announcement restored.', 'success');
    } else {
        set_flash_message('Failed to update broadcast status.', 'error');
    }
    redirect('/views/admin/broadcasts.php');
}

if ($action === 'delete_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $broadcastId = (int) ($_POST['broadcast_id'] ?? 0);
    $success = $controller->deleteAdminBroadcast($broadcastId);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message('Broadcast permanently deleted.', 'success');
    } else {
        set_flash_message('Failed to delete broadcast.', 'error');
    }
    redirect('/views/admin/broadcasts.php');
}

if ($action === 'batch_unsend_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $ids = $_POST['broadcast_ids'] ?? [];
    if (is_string($ids)) {
        $ids = explode(',', $ids);
    }

    $success = $controller->batchUnsendAdminBroadcasts((array)$ids, true);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message('Selected announcements unsent.', 'success');
    } else {
        set_flash_message('Failed to unsend selected announcements.', 'error');
    }
    redirect('/views/admin/broadcasts.php');
}

if ($action === 'batch_delete_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $ids = $_POST['broadcast_ids'] ?? [];
    if (is_string($ids)) {
        $ids = explode(',', $ids);
    }

    $success = $controller->batchDeleteAdminBroadcasts((array)$ids);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message('Selected announcements deleted.', 'success');
    } else {
        set_flash_message('Failed to delete selected announcements.', 'error');
    }
    redirect('/views/admin/broadcasts.php');
}

if ($action === 'toggle_pin_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $broadcastId = (int) ($_POST['broadcast_id'] ?? 0);
    $success = $controller->togglePinAdminBroadcast($broadcastId);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    redirect('/views/admin/broadcasts.php');
}

if ($action === 'edit_broadcast' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    if ($role !== 'ADMIN') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $broadcastId = (int) ($_POST['broadcast_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (empty($content)) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Content cannot be empty']);
            exit;
        }
        set_flash_message('Content cannot be empty.', 'error');
        redirect('/views/admin/broadcasts.php');
    }

    $success = $controller->editAdminBroadcast($broadcastId, $content);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message('Broadcast updated successfully.', 'success');
    } else {
        set_flash_message('Failed to update broadcast.', 'error');
    }
    redirect('/views/admin/broadcasts.php');
}

if ($action === 'export_broadcasts' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($role !== 'ADMIN') {
        set_flash_message('Unauthorized.', 'error');
        redirect('/');
    }

    $format = strtolower($_GET['format'] ?? 'txt');
    $broadcasts = $controller->getAdminBroadcasts();

    if ($format === 'txt') {
        $filename = "admin_broadcasts_log_" . date('Ymd_His') . ".txt";
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "========================================================\n";
        echo "COLLEGE CMS - OFFICIAL ADMIN BROADCASTS LOG\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Total Announcements: " . count($broadcasts) . "\n";
        echo "========================================================\n\n";

        foreach ($broadcasts as $idx => $b) {
            $num = $idx + 1;
            $target = $b['target_type'];
            if ($target === 'ALL') $targetStr = 'Everyone';
            elseif ($target === 'ALL_STUDENTS') $targetStr = 'All Students';
            elseif ($target === 'ALL_FACULTY') $targetStr = 'All Faculty';
            else $targetStr = $target . ' (ID: ' . $b['target_id'] . ($b['target_name'] ? ' - ' . $b['target_name'] : '') . ')';

            $status = $b['is_unsent'] ? '[UNSENT]' : '[ACTIVE]';
            if ($b['is_pinned']) $status .= ' [PINNED]';

            echo "#{$num} | {$b['created_at']} | Priority: {$b['priority']} {$status}\n";
            echo "Author: {$b['admin_name']} (Admin) | Target: {$targetStr}\n";
            echo "Message:\n" . $b['content'] . "\n";
            echo "--------------------------------------------------------\n";
        }
        exit;
    } else {
        // Printable HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Admin Broadcasts Log</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; color: #1e293b; line-height: 1.5; }
                .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 24px; }
                .header h1 { margin: 0; font-size: 20px; color: #0f172a; }
                .header p { margin: 4px 0 0; font-size: 12px; color: #64748b; }
                .item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
                .meta { font-size: 11px; color: #64748b; margin-bottom: 8px; display: flex; gap: 12px; }
                .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; background: #e0e7ff; color: #4338ca; }
                .badge-urgent { background: #ffe4e6; color: #be123c; }
                .badge-unsent { background: #f1f5f9; color: #64748b; text-decoration: line-through; }
                .content { font-size: 13px; white-space: pre-wrap; word-break: break-word; }
            </style>
        </head>
        <body onload="window.print()">
            <div class="header">
                <h1>College CMS &bull; Admin Broadcast Announcements Log</h1>
                <p>Generated on <?= date('M j, Y, g:i A') ?> &bull; Total: <?= count($broadcasts) ?> records</p>
            </div>
            <?php foreach ($broadcasts as $b): ?>
                <div class="item">
                    <div class="meta">
                        <span class="badge <?= $b['priority'] === 'URGENT' ? 'badge-urgent' : '' ?>"><?= htmlspecialchars($b['priority']) ?></span>
                        <?php if ($b['is_unsent']): ?><span class="badge badge-unsent">UNSENT</span><?php endif; ?>
                        <?php if ($b['is_pinned']): ?><span class="badge">PINNED</span><?php endif; ?>
                        <span><strong>Target:</strong> <?= htmlspecialchars($b['target_type']) ?></span>
                        <span><strong>Date:</strong> <?= date('M j, Y, g:i A', strtotime($b['created_at'])) ?></span>
                    </div>
                    <div class="content"><?= htmlspecialchars($b['content']) ?></div>
                </div>
            <?php endforeach; ?>
        </body>
        </html>
        <?php
        exit;
    }
}

// ── Send Message ─────────────────────────────────────────────
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
            exit;
        }
        set_flash_message('Form validation failed.', 'error');
        redirect("/views/{$rolePath}/messages.php");
    }

    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $content = $_POST['content'] ?? '';
    $replyToId = !empty($_POST['reply_to_id']) ? (int) $_POST['reply_to_id'] : null;

    $success = $controller->sendMessage($conversationId, $userId, $role, $content, $replyToId);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    redirect("/views/{$rolePath}/messages.php?conv=" . $conversationId);
}

// ── Start / Open Conversation ────────────────────────────────
if ($action === 'start_conversation' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed.', 'error');
        redirect("/views/{$rolePath}/messages.php");
    }

    $contactId = (int) ($_POST['contact_id'] ?? 0);
    $contactType = strtoupper(trim($_POST['contact_type'] ?? ''));

    if ($role === 'STUDENT') {
        if ($contactType === 'STUDENT') {
            $convId = $controller->getOrCreatePeerConversation($userId, $contactId);
        } else {
            $convId = $controller->getOrCreateConversation($userId, $contactId);
        }
    } else {
        if ($contactType === 'FACULTY') {
            $convId = $controller->getOrCreateFacultyConversation($userId, $contactId);
        } else {
            $convId = $controller->getOrCreateConversation($contactId, $userId);
        }
    }

    if ($convId) {
        redirect("/views/{$rolePath}/messages.php?conv=" . $convId);
    } else {
        set_flash_message('Failed to start conversation. Please check permissions.', 'error');
        redirect("/views/{$rolePath}/messages.php");
    }
}

// ── Clear Chat (For Me) ──────────────────────────────────────
if ($action === 'clear_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $success = $controller->clearChat($conversationId, $userId, $role);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($success) {
        set_flash_message('Chat history cleared for your account.', 'success');
    } else {
        set_flash_message('Failed to clear chat.', 'error');
    }
    redirect("/views/{$rolePath}/messages.php?conv=" . $conversationId);
}

// ── Delete For Me (Single or Batch) ──────────────────────────
if ($action === 'delete_for_me' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $messageIds = $_POST['message_ids'] ?? [];
    if (!is_array($messageIds)) {
        $messageIds = [(int)$messageIds];
    }
    $messageIds = array_filter(array_map('intval', $messageIds));

    $success = $controller->deleteForMe($messageIds, $userId, $role);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'deleted_count' => count($messageIds)]);
    exit;
}

// ── Unsend Messages (Author-only, Delete for Everyone) ────────
if ($action === 'unsend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $messageIds = $_POST['message_ids'] ?? [];
    if (!is_array($messageIds)) {
        $messageIds = [(int)$messageIds];
    }
    $messageIds = array_filter(array_map('intval', $messageIds));

    $success = $controller->unsendMessages($messageIds, $userId, $role);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// ── Toggle Pin ───────────────────────────────────────────────
if ($action === 'toggle_pin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $messageId = (int) ($_POST['message_id'] ?? 0);
    $success = $controller->togglePinMessage($messageId, $userId, $role);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// ── Toggle Star ──────────────────────────────────────────────
if ($action === 'toggle_star' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $messageId = (int) ($_POST['message_id'] ?? 0);
    $isStarred = $controller->toggleStarMessage($messageId, $userId, $role);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'is_starred' => $isStarred]);
    exit;
}

// ── Get Starred Messages ─────────────────────────────────────
if ($action === 'get_starred' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    $starred = $controller->getStarredMessages($userId, $role);
    echo json_encode(['success' => true, 'starred' => $starred]);
    exit;
}

// ── Toggle Reaction ──────────────────────────────────────────
if ($action === 'toggle_reaction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
        exit;
    }

    $messageId = (int) ($_POST['message_id'] ?? 0);
    $reaction = trim($_POST['reaction'] ?? '');
    $success = $controller->toggleReaction($messageId, $userId, $role, $reaction);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// ── Export Transcript ────────────────────────────────────────
if ($action === 'export_transcript') {
    $conversationId = (int) ($_GET['conv'] ?? 0);
    $format = strtolower($_GET['format'] ?? 'txt');

    $data = $controller->exportTranscript($conversationId, $userId, $role);
    if (!$data) {
        set_flash_message('Unable to export conversation transcript.', 'error');
        redirect("/views/{$rolePath}/messages.php");
    }

    $conv = $data['conversation'];
    $messages = $data['messages'];
    $party1 = $conv['s1_name'] ?? $conv['student_name'] ?? 'User 1';
    $party2 = $conv['s2_name'] ?? $conv['faculty_name'] ?? 'User 2';
    $dateStr = date('Y-m-d_His');

    if ($format === 'txt') {
        $filename = "Chat_Transcript_{$party1}_and_{$party2}_{$dateStr}.txt";
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        header('Content-Type: text/plain; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        echo "=======================================================================\n";
        echo "                    COLLEGE CMS - CHAT TRANSCRIPT                     \n";
        echo "=======================================================================\n";
        echo "Participant 1: " . $party1 . "\n";
        echo "Participant 2: " . $party2 . "\n";
        echo "Type:          " . ($conv['conv_type'] ?? '1-on-1') . "\n";
        echo "Exported At:   " . $data['exported_at'] . " (" . $data['exported_by_role'] . ")\n";
        echo "Total Msgs:    " . count($messages) . "\n";
        echo "=======================================================================\n\n";

        foreach ($messages as $msg) {
            $sender = $msg['sender_name'] ?? $msg['sender_type'];
            $time = date('Y-m-d H:i:s', strtotime($msg['created_at']));
            $replyNote = !empty($msg['reply_to_sender_name']) ? " [Replying to {$msg['reply_to_sender_name']}]" : '';
            $status = $msg['is_unsent'] ? ' (UNSENT)' : '';
            echo "[{$time}] {$sender}{$replyNote}{$status}:\n";
            echo "  " . str_replace("\n", "\n  ", $msg['content']) . "\n\n";
        }
        exit;
    } else {
        // Printable HTML format
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Chat Transcript: <?= htmlspecialchars($party1) ?> & <?= htmlspecialchars($party2) ?></title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 40px; color: #1e293b; line-height: 1.5; }
                .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { margin: 0 0 10px 0; font-size: 24px; color: #0f172a; }
                .meta-table { font-size: 14px; margin-top: 10px; border-collapse: collapse; }
                .meta-table td { padding: 4px 12px 4px 0; }
                .msg { margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
                .msg-header { font-size: 12px; color: #64748b; margin-bottom: 4px; }
                .sender { font-weight: bold; color: #334155; }
                .badge { display: inline-block; padding: 2px 6px; font-size: 10px; font-weight: bold; border-radius: 4px; background: #e0e7ff; color: #4338ca; }
                .quote { margin: 6px 0; padding: 6px 12px; background: #f8fafc; border-left: 3px solid #cbd5e1; font-size: 12px; color: #64748b; }
                .content { font-size: 14px; white-space: pre-wrap; word-break: break-word; }
                .print-btn { background: #4f46e5; color: #fff; padding: 8px 16px; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
                @media print { .no-print { display: none; } body { margin: 20px; } }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <a href="<?= BASE_URL ?>/views/<?= $rolePath ?>/messages.php?conv=<?= $conversationId ?>" style="color: #4f46e5; text-decoration: none; font-size: 14px;">&larr; Back to Messages</a>
                <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
            </div>
            <div class="header">
                <h1>Academic Chat & Mentoring Transcript</h1>
                <table class="meta-table">
                    <tr><td><strong>Participant 1:</strong></td><td><?= htmlspecialchars($party1) ?></td></tr>
                    <tr><td><strong>Participant 2:</strong></td><td><?= htmlspecialchars($party2) ?></td></tr>
                    <tr><td><strong>Exported At:</strong></td><td><?= htmlspecialchars($data['exported_at']) ?> by <?= htmlspecialchars($role) ?></td></tr>
                    <tr><td><strong>Total Messages:</strong></td><td><?= count($messages) ?></td></tr>
                </table>
            </div>
            <div>
                <?php foreach ($messages as $msg): ?>
                    <div class="msg">
                        <div class="msg-header">
                            <span class="sender"><?= htmlspecialchars($msg['sender_name'] ?? $msg['sender_type']) ?></span>
                            <span class="badge"><?= htmlspecialchars($msg['sender_type']) ?></span>
                            &bull; <?= date('M j, Y, g:i A', strtotime($msg['created_at'])) ?>
                        </div>
                        <?php if (!empty($msg['reply_to_content'])): ?>
                            <div class="quote">
                                <strong>Replying to <?= htmlspecialchars($msg['reply_to_sender_name'] ?? 'User') ?>:</strong>
                                <?= htmlspecialchars(mb_substr($msg['reply_to_content'], 0, 120)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="content <?= $msg['is_unsent'] ? 'style="font-style: italic; color: #94a3b8;"' : '' ?>">
                            <?= htmlspecialchars($msg['content']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ── Fetch Messages (AJAX poll) ───────────────────────────────
if ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $conversationId = (int) ($_GET['conv'] ?? 0);
    $messages = $controller->getMessages($conversationId, $userId, $role);
    $pinned = $controller->getPinnedMessages($conversationId, $userId, $role);

    echo json_encode([
        'success' => true, 
        'messages' => $messages,
        'pinned' => $pinned
    ]);
    exit;
}

// ── Fetch Conversations List (AJAX) ──────────────────────────
if ($action === 'fetch_conversations' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $conversations = $controller->getConversations($userId, $role);

    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

// Fallback
redirect("/views/{$rolePath}/messages.php");
