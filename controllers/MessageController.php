<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

use Config\Database;
use PDO;

class MessageController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all conversations for a user (Student or Faculty) with contact details,
     * unread count, and last message preview, honoring personal cleared_at and delete flags.
     * Supports both Faculty-Student and Peer-to-Peer (Classmate) conversations.
     *
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return array
     */
    public function getConversations(int $userId, string $role): array {
        try {
            $sql = "
                SELECT 
                    c.*,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ?) THEN c.user2_id
                        ELSE c.user1_id
                    END as contact_id,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ?) THEN c.user2_type
                        ELSE c.user1_type
                    END as contact_role,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ?) THEN c.user1_cleared_at
                        ELSE c.user2_cleared_at
                    END as my_cleared_at,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ?) THEN 1
                        ELSE 2
                    END as my_user_num,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ? AND c.user2_type = 'FACULTY') THEN t2.name
                        WHEN (c.user1_id = ? AND c.user1_type = ? AND c.user2_type = 'STUDENT') THEN s2.name
                        WHEN (c.user2_id = ? AND c.user2_type = ? AND c.user1_type = 'FACULTY') THEN t1.name
                        WHEN (c.user2_id = ? AND c.user2_type = ? AND c.user1_type = 'STUDENT') THEN s1.name
                    END as contact_name,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ? AND c.user2_type = 'FACULTY') THEN t2.profile_pic
                        WHEN (c.user1_id = ? AND c.user1_type = ? AND c.user2_type = 'STUDENT') THEN s2.profile_pic
                        WHEN (c.user2_id = ? AND c.user2_type = ? AND c.user1_type = 'FACULTY') THEN t1.profile_pic
                        WHEN (c.user2_id = ? AND c.user2_type = ? AND c.user1_type = 'STUDENT') THEN s1.profile_pic
                    END as contact_pic,
                    CASE 
                        WHEN (c.user1_id = ? AND c.user1_type = ? AND c.user2_type = 'STUDENT') THEN s2.roll_number
                        WHEN (c.user2_id = ? AND c.user2_type = ? AND c.user1_type = 'STUDENT') THEN s1.roll_number
                        ELSE NULL
                    END as contact_roll
                FROM conversations c
                LEFT JOIN teachers t1 ON c.user1_id = t1.id AND c.user1_type = 'FACULTY'
                LEFT JOIN students s1 ON c.user1_id = s1.id AND c.user1_type = 'STUDENT'
                LEFT JOIN teachers t2 ON c.user2_id = t2.id AND c.user2_type = 'FACULTY'
                LEFT JOIN students s2 ON c.user2_id = s2.id AND c.user2_type = 'STUDENT'
                WHERE (c.user1_id = ? AND c.user1_type = ?)
                   OR (c.user2_id = ? AND c.user2_type = ?)
                ORDER BY c.last_message_at DESC, c.created_at DESC
            ";

            $params = [
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role,
                $userId, $role
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $conversations = $stmt->fetchAll();

            // Fetch last message and unread count for each conversation
            foreach ($conversations as &$conv) {
                $cId = (int)$conv['id'];
                $isUser1 = ((int)$conv['my_user_num'] === 1);
                $clearedAt = $conv['my_cleared_at'];
                $delCol = $isUser1 ? 'deleted_by_user1' : 'deleted_by_user2';

                // Last Message
                $stmtMsg = $this->db->prepare("
                    SELECT content 
                    FROM messages 
                    WHERE conversation_id = ?
                      AND (? IS NULL OR created_at > ?)
                      AND {$delCol} = 0
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmtMsg->execute([$cId, $clearedAt, $clearedAt]);
                $lastMsg = $stmtMsg->fetchColumn();
                $conv['last_message'] = $lastMsg !== false ? $lastMsg : 'No messages yet';

                // Unread Count
                $stmtUnread = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE conversation_id = ?
                      AND NOT (sender_type = ? AND sender_id = ?)
                      AND is_read = 0
                      AND is_unsent = 0
                      AND (? IS NULL OR created_at > ?)
                      AND {$delCol} = 0
                ");
                $stmtUnread->execute([$cId, $role, $userId, $clearedAt, $clearedAt]);
                $conv['unread_count'] = (int)$stmtUnread->fetchColumn();
            }

            return $conversations;
        } catch (\PDOException $e) {
            error_log("DB Error fetching conversations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get or create a conversation between a student and a faculty member.
     *
     * @param int $studentId
     * @param int $facultyId
     * @return int|null conversation ID
     */
    public function getOrCreateConversation(int $studentId, int $facultyId): ?int {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM conversations 
                WHERE (user1_id = ? AND user1_type = 'STUDENT' AND user2_id = ? AND user2_type = 'FACULTY')
                   OR (student_id = ? AND faculty_id = ?)
            ");
            $stmt->execute([$studentId, $facultyId, $studentId, $facultyId]);
            $conv = $stmt->fetch();

            if ($conv) {
                return (int) $conv['id'];
            }

            $stmt = $this->db->prepare("
                INSERT INTO conversations (conv_type, user1_id, user1_type, user2_id, user2_type, student_id, faculty_id) 
                VALUES ('FACULTY_STUDENT', ?, 'STUDENT', ?, 'FACULTY', ?, ?)
            ");
            $stmt->execute([$studentId, $facultyId, $studentId, $facultyId]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("DB Error creating conversation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get or create a peer-to-peer conversation between two students (Classmates).
     * Strict check: Both students must belong to the exact same department and semester.
     *
     * @param int $student1Id
     * @param int $student2Id
     * @return int|null conversation ID or null if not eligible classmates
     */
    public function getOrCreatePeerConversation(int $student1Id, int $student2Id): ?int {
        try {
            if ($student1Id === $student2Id) {
                return null;
            }

            // Verify both students exist, are ACTIVE, and share department_id & semester_id
            $stmt = $this->db->prepare("
                SELECT id, department_id, semester_id, status 
                FROM students 
                WHERE id IN (?, ?) AND status = 'ACTIVE'
            ");
            $stmt->execute([$student1Id, $student2Id]);
            $students = $stmt->fetchAll();

            if (count($students) !== 2) {
                return null;
            }

            $s1 = $students[0];
            $s2 = $students[1];
            if ($s1['department_id'] !== $s2['department_id'] || $s1['semester_id'] !== $s2['semester_id']) {
                // Not in the same department/semester
                return null;
            }

            // Order user IDs consistently
            $u1 = min($student1Id, $student2Id);
            $u2 = max($student1Id, $student2Id);

            // Check if conversation exists
            $check = $this->db->prepare("
                SELECT id FROM conversations 
                WHERE conv_type = 'PEER_STUDENT'
                  AND user1_id = ? AND user1_type = 'STUDENT'
                  AND user2_id = ? AND user2_type = 'STUDENT'
            ");
            $check->execute([$u1, $u2]);
            $conv = $check->fetch();

            if ($conv) {
                return (int) $conv['id'];
            }

            // Create new peer conversation
            $ins = $this->db->prepare("
                INSERT INTO conversations (conv_type, user1_id, user1_type, user2_id, user2_type, student_id, faculty_id) 
                VALUES ('PEER_STUDENT', ?, 'STUDENT', ?, 'STUDENT', ?, NULL)
            ");
            $ins->execute([$u1, $u2, $u1]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("DB Error creating peer conversation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get or create a conversation between two faculty members across the college.
     *
     * @param int $fac1Id
     * @param int $fac2Id
     * @return int|null conversation ID
     */
    public function getOrCreateFacultyConversation(int $fac1Id, int $fac2Id): ?int {
        try {
            if ($fac1Id === $fac2Id) {
                return null;
            }

            // Verify both teachers exist
            $checkTeachers = $this->db->prepare("SELECT id FROM teachers WHERE id IN (?, ?)");
            $checkTeachers->execute([$fac1Id, $fac2Id]);
            if (count($checkTeachers->fetchAll()) !== 2) {
                return null;
            }

            $u1 = min($fac1Id, $fac2Id);
            $u2 = max($fac1Id, $fac2Id);

            $stmt = $this->db->prepare("
                SELECT id FROM conversations 
                WHERE conv_type = 'FACULTY_FACULTY' 
                  AND user1_id = ? AND user1_type = 'FACULTY' 
                  AND user2_id = ? AND user2_type = 'FACULTY'
            ");
            $stmt->execute([$u1, $u2]);
            $conv = $stmt->fetch();

            if ($conv) {
                return (int) $conv['id'];
            }

            $ins = $this->db->prepare("
                INSERT INTO conversations (conv_type, user1_id, user1_type, user2_id, user2_type, student_id, faculty_id) 
                VALUES ('FACULTY_FACULTY', ?, 'FACULTY', ?, 'FACULTY', NULL, NULL)
            ");
            $ins->execute([$u1, $u2]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("DB Error creating faculty conversation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all messages in a conversation, honoring soft clear, delete for me,
     * quoting, pinned status, starred status, and aggregated reactions.
     *
     * @param int    $conversationId
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return array
     */
    public function getMessages(int $conversationId, int $userId, string $role): array {
        try {
            // Verify membership & determine user index (1 or 2)
            $check = $this->db->prepare("SELECT * FROM conversations WHERE id = ?");
            $check->execute([$conversationId]);
            $conv = $check->fetch();
            if (!$conv) {
                return [];
            }

            $userNum = 0;
            $clearedAt = null;
            if ((int)$conv['user1_id'] === $userId && $conv['user1_type'] === $role) {
                $userNum = 1;
                $clearedAt = $conv['user1_cleared_at'] ?? $conv['student_cleared_at'] ?? null;
            } elseif ((int)$conv['user2_id'] === $userId && $conv['user2_type'] === $role) {
                $userNum = 2;
                $clearedAt = $conv['user2_cleared_at'] ?? $conv['faculty_cleared_at'] ?? null;
            } elseif ($role === 'STUDENT' && (int)$conv['student_id'] === $userId) {
                $userNum = 1;
                $clearedAt = $conv['student_cleared_at'] ?? null;
            } elseif ($role === 'FACULTY' && (int)$conv['faculty_id'] === $userId) {
                $userNum = 2;
                $clearedAt = $conv['faculty_cleared_at'] ?? null;
            } else {
                return []; // Unauthorized
            }

            $delCol = ($userNum === 1) ? 'deleted_by_user1' : 'deleted_by_user2';

            // Mark unread messages from the other party as read
            $markRead = $this->db->prepare("
                UPDATE messages SET is_read = 1 
                WHERE conversation_id = ? AND NOT (sender_type = ? AND sender_id = ?) AND is_read = 0
            ");
            $markRead->execute([$conversationId, $role, $userId]);

            // Query messages
            $sql = "
                SELECT m.*,
                    CASE 
                        WHEN m.sender_type = 'STUDENT' THEN s.name 
                        WHEN m.sender_type = 'FACULTY' THEN t.name 
                    END as sender_name,
                    CASE 
                        WHEN m.sender_type = 'STUDENT' THEN s.profile_pic 
                        WHEN m.sender_type = 'FACULTY' THEN t.profile_pic 
                    END as sender_pic,
                    p.content as reply_to_content,
                    p.sender_type as reply_to_sender_type,
                    CASE 
                        WHEN p.sender_type = 'STUDENT' THEN ps.name 
                        WHEN p.sender_type = 'FACULTY' THEN pt.name 
                    END as reply_to_sender_name,
                    (SELECT COUNT(*) FROM starred_messages sm WHERE sm.message_id = m.id AND sm.user_id = ? AND sm.user_type = ?) as is_starred
                FROM messages m
                LEFT JOIN students s ON m.sender_type = 'STUDENT' AND m.sender_id = s.id
                LEFT JOIN teachers t ON m.sender_type = 'FACULTY' AND m.sender_id = t.id
                LEFT JOIN messages p ON m.reply_to_id = p.id
                LEFT JOIN students ps ON p.sender_type = 'STUDENT' AND p.sender_id = ps.id
                LEFT JOIN teachers pt ON p.sender_type = 'FACULTY' AND p.sender_id = pt.id
                WHERE m.conversation_id = ?
                  AND (? IS NULL OR m.created_at > ?)
                  AND m.{$delCol} = 0
                ORDER BY m.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $role, $conversationId, $clearedAt, $clearedAt]);
            $messages = $stmt->fetchAll();

            if (empty($messages)) {
                return [];
            }

            // Fetch aggregated reactions
            $msgIds = array_column($messages, 'id');
            $reactionsMap = $this->getReactionsForMessages($msgIds, $userId, $role);

            foreach ($messages as &$msg) {
                $mId = (int) $msg['id'];
                $msg['reactions'] = $reactionsMap[$mId]['counts'] ?? [];
                $msg['user_reactions'] = $reactionsMap[$mId]['user_reactions'] ?? [];
                $msg['is_starred'] = (bool) $msg['is_starred'];
                $msg['is_pinned'] = (bool) $msg['is_pinned'];
                $msg['is_unsent'] = (bool) $msg['is_unsent'];

                if ($msg['is_unsent']) {
                    $msg['content'] = 'This message was unsent';
                }
            }

            return $messages;
        } catch (\PDOException $e) {
            error_log("DB Error fetching messages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send a new message in a conversation.
     *
     * @param int      $conversationId
     * @param int      $senderId
     * @param string   $senderType STUDENT or FACULTY
     * @param string   $content    The message text
     * @param int|null $replyToId  Optional ID of message being replied to
     * @return bool
     */
    public function sendMessage(int $conversationId, int $senderId, string $senderType, string $content, ?int $replyToId = null): bool {
        try {
            $content = trim($content);
            if (empty($content)) {
                return false;
            }

            // Verify sender is a participant
            $check = $this->db->prepare("
                SELECT id FROM conversations 
                WHERE id = ? 
                  AND (
                      (user1_id = ? AND user1_type = ?) OR
                      (user2_id = ? AND user2_type = ?) OR
                      (student_id = ? AND ? = 'STUDENT') OR
                      (faculty_id = ? AND ? = 'FACULTY')
                  )
            ");
            $check->execute([$conversationId, $senderId, $senderType, $senderId, $senderType, $senderId, $senderType, $senderId, $senderType]);
            if (!$check->fetch()) {
                return false;
            }

            // Validate reply_to_id if provided
            if ($replyToId) {
                $checkReply = $this->db->prepare("SELECT id FROM messages WHERE id = ? AND conversation_id = ?");
                $checkReply->execute([$replyToId, $conversationId]);
                if (!$checkReply->fetch()) {
                    $replyToId = null;
                }
            }

            // Insert message
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, sender_type, sender_id, content, reply_to_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$conversationId, $senderType, $senderId, $content, $replyToId]);

            // Update conversation last_message_at
            $update = $this->db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?");
            $update->execute([$conversationId]);

            return true;
        } catch (\PDOException $e) {
            error_log("DB Error sending message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all chat history for the current user in a conversation (soft clear).
     *
     * @param int    $conversationId
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return bool
     */
    public function clearChat(int $conversationId, int $userId, string $role): bool {
        try {
            $stmt = $this->db->prepare("SELECT * FROM conversations WHERE id = ?");
            $stmt->execute([$conversationId]);
            $conv = $stmt->fetch();
            if (!$conv) {
                return false;
            }

            if ((int)$conv['user1_id'] === $userId && $conv['user1_type'] === $role) {
                $update = $this->db->prepare("UPDATE conversations SET user1_cleared_at = NOW(), student_cleared_at = IF(user1_type='STUDENT', NOW(), student_cleared_at) WHERE id = ?");
                $update->execute([$conversationId]);
                return true;
            } elseif ((int)$conv['user2_id'] === $userId && $conv['user2_type'] === $role) {
                $update = $this->db->prepare("UPDATE conversations SET user2_cleared_at = NOW(), faculty_cleared_at = IF(user2_type='FACULTY', NOW(), faculty_cleared_at) WHERE id = ?");
                $update->execute([$conversationId]);
                return true;
            }

            return false;
        } catch (\PDOException $e) {
            error_log("DB Error clearing chat: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete selected messages for the current user only ("Delete for me").
     *
     * @param array  $messageIds
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return bool
     */
    public function deleteForMe(array $messageIds, int $userId, string $role): bool {
        try {
            if (empty($messageIds)) {
                return false;
            }

            $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
            $params = array_merge(
                [$userId, $role, $userId, $role],
                $messageIds,
                [$userId, $role, $userId, $role]
            );

            $sql = "
                UPDATE messages m
                JOIN conversations c ON m.conversation_id = c.id
                SET m.deleted_by_user1 = IF(c.user1_id = ? AND c.user1_type = ?, 1, m.deleted_by_user1),
                    m.deleted_by_user2 = IF(c.user2_id = ? AND c.user2_type = ?, 1, m.deleted_by_user2)
                WHERE m.id IN ({$placeholders})
                  AND ((c.user1_id = ? AND c.user1_type = ?) OR (c.user2_id = ? AND c.user2_type = ?))
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (\PDOException $e) {
            error_log("DB Error deleteForMe: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsend selected messages (Delete for everyone).
     *
     * @param array  $messageIds
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return bool
     */
    public function unsendMessages(array $messageIds, int $userId, string $role): bool {
        try {
            if (empty($messageIds)) {
                return false;
            }

            $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
            $params = $messageIds;
            $params[] = $role;
            $params[] = $userId;

            $sql = "
                UPDATE messages 
                SET is_unsent = 1, unsent_at = NOW()
                WHERE id IN ({$placeholders})
                  AND sender_type = ?
                  AND sender_id = ?
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error unsendMessages: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle Pin/Unpin for a message in a conversation.
     *
     * @param int    $messageId
     * @param int    $userId
     * @param string $role
     * @return bool
     */
    public function togglePinMessage(int $messageId, int $userId, string $role): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE messages m
                JOIN conversations c ON m.conversation_id = c.id
                SET m.is_pinned = IF(m.is_pinned = 1, 0, 1),
                    m.pinned_at = IF(m.is_pinned = 1, NULL, NOW())
                WHERE m.id = ? 
                  AND ((c.user1_id = ? AND c.user1_type = ?) OR (c.user2_id = ? AND c.user2_type = ?))
            ");
            $stmt->execute([$messageId, $userId, $role, $userId, $role]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error togglePinMessage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all active pinned messages for a conversation.
     *
     * @param int    $conversationId
     * @param int    $userId
     * @param string $role
     * @return array
     */
    public function getPinnedMessages(int $conversationId, int $userId, string $role): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM conversations WHERE id = ?");
            $stmt->execute([$conversationId]);
            $conv = $stmt->fetch();
            if (!$conv) {
                return [];
            }

            $userNum = 0;
            $clearedAt = null;
            if ((int)$conv['user1_id'] === $userId && $conv['user1_type'] === $role) {
                $userNum = 1;
                $clearedAt = $conv['user1_cleared_at'] ?? $conv['student_cleared_at'] ?? null;
            } elseif ((int)$conv['user2_id'] === $userId && $conv['user2_type'] === $role) {
                $userNum = 2;
                $clearedAt = $conv['user2_cleared_at'] ?? $conv['faculty_cleared_at'] ?? null;
            } else {
                return [];
            }

            $delCol = ($userNum === 1) ? 'deleted_by_user1' : 'deleted_by_user2';

            $stmt = $this->db->prepare("
                SELECT m.id, m.content, m.sender_type, m.sender_id, m.pinned_at, m.created_at,
                    CASE 
                        WHEN m.sender_type = 'STUDENT' THEN s.name 
                        WHEN m.sender_type = 'FACULTY' THEN t.name 
                    END as sender_name
                FROM messages m
                LEFT JOIN students s ON m.sender_type = 'STUDENT' AND m.sender_id = s.id
                LEFT JOIN teachers t ON m.sender_type = 'FACULTY' AND m.sender_id = t.id
                WHERE m.conversation_id = ?
                  AND m.is_pinned = 1
                  AND m.is_unsent = 0
                  AND m.{$delCol} = 0
                  AND (? IS NULL OR m.created_at > ?)
                ORDER BY m.pinned_at DESC
            ");
            $stmt->execute([$conversationId, $clearedAt, $clearedAt]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error getPinnedMessages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Toggle Star / Bookmark on a message for the current user.
     *
     * @param int    $messageId
     * @param int    $userId
     * @param string $role
     * @return bool  true if now starred, false if unstarred
     */
    public function toggleStarMessage(int $messageId, int $userId, string $role): bool {
        try {
            $check = $this->db->prepare("
                SELECT id FROM starred_messages 
                WHERE message_id = ? AND user_id = ? AND user_type = ?
            ");
            $check->execute([$messageId, $userId, $role]);
            $existing = $check->fetch();

            if ($existing) {
                $del = $this->db->prepare("DELETE FROM starred_messages WHERE id = ?");
                $del->execute([$existing['id']]);
                return false;
            } else {
                $ins = $this->db->prepare("
                    INSERT INTO starred_messages (message_id, user_id, user_type) 
                    VALUES (?, ?, ?)
                ");
                $ins->execute([$messageId, $userId, $role]);
                return true;
            }
        } catch (\PDOException $e) {
            error_log("DB Error toggleStarMessage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all starred messages for the current user.
     *
     * @param int    $userId
     * @param string $role
     * @return array
     */
    public function getStarredMessages(int $userId, string $role): array {
        try {
            $stmt = $this->db->prepare("
                SELECT sm.id as star_id, sm.created_at as starred_at,
                    m.id as message_id, m.conversation_id, m.content, m.created_at as message_created_at,
                    m.sender_type, m.sender_id,
                    CASE 
                        WHEN m.sender_type = 'STUDENT' THEN s.name 
                        WHEN m.sender_type = 'FACULTY' THEN t.name 
                    END as sender_name
                FROM starred_messages sm
                JOIN messages m ON sm.message_id = m.id
                JOIN conversations c ON m.conversation_id = c.id
                LEFT JOIN students s ON m.sender_type = 'STUDENT' AND m.sender_id = s.id
                LEFT JOIN teachers t ON m.sender_type = 'FACULTY' AND m.sender_id = t.id
                WHERE sm.user_id = ? AND sm.user_type = ?
                  AND m.is_unsent = 0
                  AND (
                      (c.user1_id = ? AND c.user1_type = ? AND m.deleted_by_user1 = 0 AND (c.user1_cleared_at IS NULL OR m.created_at > c.user1_cleared_at))
                      OR
                      (c.user2_id = ? AND c.user2_type = ? AND m.deleted_by_user2 = 0 AND (c.user2_cleared_at IS NULL OR m.created_at > c.user2_cleared_at))
                  )
                ORDER BY sm.created_at DESC
            ");
            $stmt->execute([$userId, $role, $userId, $role, $userId, $role]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error getStarredMessages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Toggle an emoji reaction on a message.
     *
     * @param int    $messageId
     * @param int    $userId
     * @param string $role
     * @param string $reaction
     * @return bool
     */
    public function toggleReaction(int $messageId, int $userId, string $role, string $reaction): bool {
        try {
            $allowed = ['👍', '❤️', '❓', '💡', '✅', '👏', '🔥'];
            if (!in_array($reaction, $allowed)) {
                return false;
            }

            // Verify message exists and user is participant
            $check = $this->db->prepare("
                SELECT m.id FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                WHERE m.id = ? 
                  AND ((c.user1_id = ? AND c.user1_type = ?) OR (c.user2_id = ? AND c.user2_type = ?))
                  AND m.is_unsent = 0
            ");
            $check->execute([$messageId, $userId, $role, $userId, $role]);
            if (!$check->fetch()) {
                return false;
            }

            // Check if reaction already given
            $existing = $this->db->prepare("
                SELECT id FROM message_reactions 
                WHERE message_id = ? AND user_id = ? AND user_type = ? AND reaction = ?
            ");
            $existing->execute([$messageId, $userId, $role, $reaction]);
            $row = $existing->fetch();

            if ($row) {
                $del = $this->db->prepare("DELETE FROM message_reactions WHERE id = ?");
                $del->execute([$row['id']]);
            } else {
                $ins = $this->db->prepare("
                    INSERT INTO message_reactions (message_id, user_id, user_type, reaction) 
                    VALUES (?, ?, ?, ?)
                ");
                $ins->execute([$messageId, $userId, $role, $reaction]);
            }
            return true;
        } catch (\PDOException $e) {
            error_log("DB Error toggleReaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Internal helper to fetch aggregated reactions for an array of message IDs.
     */
    private function getReactionsForMessages(array $messageIds, int $userId, string $role): array {
        if (empty($messageIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $this->db->prepare("
            SELECT message_id, reaction, user_id, user_type
            FROM message_reactions
            WHERE message_id IN ({$placeholders})
        ");
        $stmt->execute($messageIds);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $mId = (int) $r['message_id'];
            $emoji = $r['reaction'];
            if (!isset($map[$mId])) {
                $map[$mId] = ['counts' => [], 'user_reactions' => []];
            }
            if (!isset($map[$mId]['counts'][$emoji])) {
                $map[$mId]['counts'][$emoji] = 0;
            }
            $map[$mId]['counts'][$emoji]++;

            if ((int)$r['user_id'] === $userId && $r['user_type'] === $role) {
                $map[$mId]['user_reactions'][] = $emoji;
            }
        }
        return $map;
    }

    /**
     * Export conversation transcript as formatted data.
     *
     * @param int    $conversationId
     * @param int    $userId
     * @param string $role
     * @return array|null
     */
    public function exportTranscript(int $conversationId, int $userId, string $role): ?array {
        try {
            $conv = $this->getConversation($conversationId);
            if (!$conv) {
                return null;
            }

            // Verify access
            $isParticipant = (
                ((int)$conv['user1_id'] === $userId && $conv['user1_type'] === $role) ||
                ((int)$conv['user2_id'] === $userId && $conv['user2_type'] === $role) ||
                ($role === 'STUDENT' && (int)$conv['student_id'] === $userId) ||
                ($role === 'FACULTY' && (int)$conv['faculty_id'] === $userId)
            );
            if (!$isParticipant) {
                return null;
            }

            $messages = $this->getMessages($conversationId, $userId, $role);
            return [
                'conversation' => $conv,
                'messages' => $messages,
                'exported_at' => date('Y-m-d H:i:s'),
                'exported_by_role' => $role,
            ];
        } catch (\PDOException $e) {
            error_log("DB Error exportTranscript: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get total unread message count for a user (for header badge).
     *
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return int
     */
    public function getUnreadCount(int $userId, string $role): int {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt
                FROM messages m
                JOIN conversations c ON m.conversation_id = c.id
                WHERE (
                    (c.user1_id = ? AND c.user1_type = ? AND m.deleted_by_user1 = 0 AND (c.user1_cleared_at IS NULL OR m.created_at > c.user1_cleared_at))
                    OR
                    (c.user2_id = ? AND c.user2_type = ? AND m.deleted_by_user2 = 0 AND (c.user2_cleared_at IS NULL OR m.created_at > c.user2_cleared_at))
                )
                AND NOT (m.sender_type = ? AND m.sender_id = ?)
                AND m.is_read = 0
                AND m.is_unsent = 0
            ");
            $stmt->execute([$userId, $role, $userId, $role, $role, $userId]);
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0);
        } catch (\PDOException $e) {
            error_log("DB Error fetching unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get list of users that the current user can message.
     * Students → (1) Assigned Faculty + (2) Classmates (same department & semester).
     * Faculty  → Students enrolled in their assigned courses.
     *
     * @param int    $userId
     * @param string $role STUDENT or FACULTY
     * @return array
     */
    public function getContactableUsers(int $userId, string $role): array {
        try {
            if ($role === 'STUDENT') {
                // First get student's department_id and semester_id
                $studentInfoStmt = $this->db->prepare("
                    SELECT s.department_id, s.semester_id, d.dept_name, sem.semester_number
                    FROM students s
                    LEFT JOIN departments d ON s.department_id = d.id
                    LEFT JOIN semesters sem ON s.semester_id = sem.id
                    WHERE s.id = ?
                ");
                $studentInfoStmt->execute([$userId]);
                $studentInfo = $studentInfoStmt->fetch();

                if (!$studentInfo) {
                    return [];
                }

                $deptId = (int)$studentInfo['department_id'];
                $semId = (int)$studentInfo['semester_id'];

                // 1. Assigned Faculty
                $stmtFaculty = $this->db->prepare("
                    SELECT DISTINCT t.id, t.name, t.email, t.profile_pic, t.designation as subtitle,
                           'FACULTY' as role, 'Faculty' as category
                    FROM teachers t
                    JOIN course_assignments ca ON t.id = ca.faculty_id
                    JOIN courses c ON ca.course_id = c.id
                    WHERE c.department_id = ? AND c.semester_id = ?
                    ORDER BY t.name ASC
                ");
                $stmtFaculty->execute([$deptId, $semId]);
                $facultyList = $stmtFaculty->fetchAll();

                // 2. Classmates (Same Department & Semester)
                $stmtClassmates = $this->db->prepare("
                    SELECT s.id, s.name, s.email, s.profile_pic, 
                           CONCAT('Roll: ', s.roll_number) as subtitle,
                           'STUDENT' as role, 'Classmate' as category, s.roll_number
                    FROM students s
                    WHERE s.department_id = ? 
                      AND s.semester_id = ? 
                      AND s.id != ? 
                      AND s.status = 'ACTIVE'
                    ORDER BY s.name ASC
                ");
                $stmtClassmates->execute([$deptId, $semId, $userId]);
                $classmateList = $stmtClassmates->fetchAll();

                return array_merge($classmateList, $facultyList);
            } else {
                // Faculty:
                // 1. Students enrolled in assigned courses
                $stmtStudents = $this->db->prepare("
                    SELECT DISTINCT s.id, s.name, s.email, s.profile_pic, s.roll_number,
                           CONCAT('Roll: ', s.roll_number) as subtitle,
                           'STUDENT' as role, 'Student' as category
                    FROM students s
                    JOIN courses c ON s.department_id = c.department_id AND s.semester_id = c.semester_id
                    JOIN course_assignments ca ON ca.course_id = c.id
                    WHERE ca.faculty_id = ? AND s.status = 'ACTIVE'
                    ORDER BY s.name ASC
                ");
                $stmtStudents->execute([$userId]);
                $studentList = $stmtStudents->fetchAll();

                // 2. All other faculty members in the college
                $stmtFaculty = $this->db->prepare("
                    SELECT t.id, t.name, t.email, t.profile_pic,
                           COALESCE(t.designation, 'Faculty Member') as subtitle,
                           'FACULTY' as role, 'Faculty' as category
                    FROM teachers t
                    WHERE t.id != ?
                    ORDER BY t.name ASC
                ");
                $stmtFaculty->execute([$userId]);
                $facultyList = $stmtFaculty->fetchAll();

                return array_merge($studentList, $facultyList);
            }
        } catch (\PDOException $e) {
            error_log("DB Error fetching contactable users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get conversation details, enriched with contact info relative to viewing user.
     *
     * @param int $conversationId
     * @param int|null $currentUserId
     * @param string|null $currentUserRole
     * @return array|null
     */
    public function getConversation(int $conversationId, ?int $currentUserId = null, ?string $currentUserRole = null): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                    s1.name as s1_name, s1.profile_pic as s1_pic, s1.roll_number as s1_roll,
                    t1.name as t1_name, t1.profile_pic as t1_pic, t1.designation as t1_designation,
                    s2.name as s2_name, s2.profile_pic as s2_pic, s2.roll_number as s2_roll,
                    t2.name as t2_name, t2.profile_pic as t2_pic, t2.designation as t2_designation,
                    dept.dept_name, sem.semester_number
                FROM conversations c
                LEFT JOIN students s1 ON c.user1_id = s1.id AND c.user1_type = 'STUDENT'
                LEFT JOIN teachers t1 ON c.user1_id = t1.id AND c.user1_type = 'FACULTY'
                LEFT JOIN students s2 ON c.user2_id = s2.id AND c.user2_type = 'STUDENT'
                LEFT JOIN teachers t2 ON c.user2_id = t2.id AND c.user2_type = 'FACULTY'
                LEFT JOIN departments dept ON (s1.department_id = dept.id OR s2.department_id = dept.id)
                LEFT JOIN semesters sem ON (s1.semester_id = sem.id OR s2.semester_id = sem.id)
                WHERE c.id = ?
            ");
            $stmt->execute([$conversationId]);
            $conv = $stmt->fetch();
            if (!$conv) {
                return null;
            }

            // Set backward compatible fields
            $conv['student_name'] = $conv['s1_name'] ?? $conv['s2_name'] ?? 'Student';
            $conv['student_pic'] = $conv['s1_pic'] ?? $conv['s2_pic'] ?? null;
            $conv['faculty_name'] = $conv['t2_name'] ?? $conv['t1_name'] ?? 'Faculty';
            $conv['faculty_pic'] = $conv['t2_pic'] ?? $conv['t1_pic'] ?? null;

            // Compute relative contact info if current user provided
            if ($currentUserId && $currentUserRole) {
                $isUser1 = ((int)$conv['user1_id'] === $currentUserId && $conv['user1_type'] === $currentUserRole);
                if ($isUser1) {
                    $conv['contact_name'] = ($conv['user2_type'] === 'STUDENT') ? $conv['s2_name'] : $conv['t2_name'];
                    $conv['contact_pic'] = ($conv['user2_type'] === 'STUDENT') ? $conv['s2_pic'] : $conv['t2_pic'];
                    $conv['contact_role'] = $conv['user2_type'];
                    if ($conv['user2_type'] === 'STUDENT') {
                        $conv['contact_subtitle'] = ($currentUserRole === 'STUDENT') ? 'Classmate' : 'Student';
                        if (!empty($conv['s2_roll'])) {
                            $conv['contact_subtitle'] .= ' • Roll: ' . $conv['s2_roll'];
                        }
                    } else {
                        $conv['contact_subtitle'] = !empty($conv['t2_designation']) ? $conv['t2_designation'] : 'Faculty Member';
                    }
                } else {
                    $conv['contact_name'] = ($conv['user1_type'] === 'STUDENT') ? $conv['s1_name'] : $conv['t1_name'];
                    $conv['contact_pic'] = ($conv['user1_type'] === 'STUDENT') ? $conv['s1_pic'] : $conv['t1_pic'];
                    $conv['contact_role'] = $conv['user1_type'];
                    if ($conv['user1_type'] === 'STUDENT') {
                        $conv['contact_subtitle'] = ($currentUserRole === 'STUDENT') ? 'Classmate' : 'Student';
                        if (!empty($conv['s1_roll'])) {
                            $conv['contact_subtitle'] .= ' • Roll: ' . $conv['s1_roll'];
                        }
                    } else {
                        $conv['contact_subtitle'] = !empty($conv['t1_designation']) ? $conv['t1_designation'] : 'Faculty Member';
                    }
                }
            }

            return $conv;
        } catch (\PDOException $e) {
            error_log("DB Error fetching conversation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all admin broadcasts for Admin view with optional search and filters.
     */
    public function getAdminBroadcasts(?string $search = null, ?string $targetFilter = null, ?string $priorityFilter = null): array {
        try {
            $sql = "
                SELECT ab.*, a.name as admin_name,
                    CASE 
                        WHEN ab.target_type = 'STUDENT' THEN (SELECT name FROM students WHERE id = ab.target_id)
                        WHEN ab.target_type = 'FACULTY' THEN (SELECT name FROM teachers WHERE id = ab.target_id)
                        ELSE NULL
                    END as target_name
                FROM admin_broadcasts ab
                JOIN admins a ON ab.admin_id = a.id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== null && trim($search) !== '') {
                $sql .= " AND (ab.content LIKE ? OR a.name LIKE ?)";
                $term = '%' . trim($search) . '%';
                $params[] = $term;
                $params[] = $term;
            }

            if ($targetFilter !== null && $targetFilter !== '' && $targetFilter !== 'ALL') {
                $sql .= " AND ab.target_type = ?";
                $params[] = $targetFilter;
            }

            if ($priorityFilter !== null && $priorityFilter !== '' && $priorityFilter !== 'ALL') {
                $sql .= " AND ab.priority = ?";
                $params[] = $priorityFilter;
            }

            $sql .= " ORDER BY ab.is_pinned DESC, ab.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error fetching admin broadcasts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get broadcasts relevant to a specific user (excludes unsent, supports role-wide targeting).
     */
    public function getAdminBroadcastsForUser(int $userId, string $role): array {
        try {
            $stmt = $this->db->prepare("
                SELECT ab.*, a.name as admin_name 
                FROM admin_broadcasts ab
                JOIN admins a ON ab.admin_id = a.id
                WHERE ab.is_unsent = 0
                  AND (
                      ab.target_type = 'ALL' 
                      OR (ab.target_type = 'ALL_STUDENTS' AND ? = 'STUDENT')
                      OR (ab.target_type = 'ALL_FACULTY' AND ? = 'FACULTY')
                      OR (ab.target_type = ? AND ab.target_id = ?)
                  )
                ORDER BY ab.is_pinned DESC, ab.created_at ASC
            ");
            $stmt->execute([$role, $role, $role, $userId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error fetching user broadcasts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send a new admin broadcast message.
     */
    public function sendAdminBroadcast(int $adminId, string $content, string $targetType = 'ALL', ?int $targetId = null, string $priority = 'NORMAL', bool $isPinned = false): bool {
        try {
            $validTargets = ['ALL', 'ALL_STUDENTS', 'ALL_FACULTY', 'STUDENT', 'FACULTY'];
            if (!in_array($targetType, $validTargets)) {
                $targetType = 'ALL';
            }

            $validPriorities = ['NORMAL', 'URGENT', 'ACADEMIC', 'EVENT'];
            if (!in_array($priority, $validPriorities)) {
                $priority = 'NORMAL';
            }

            $stmt = $this->db->prepare("
                INSERT INTO admin_broadcasts (admin_id, content, target_type, target_id, priority, is_pinned) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$adminId, $content, $targetType, $targetId, $priority, $isPinned ? 1 : 0]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error sending admin broadcast: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsend or restore an admin broadcast.
     */
    public function unsendAdminBroadcast(int $broadcastId, bool $unsend = true): bool {
        try {
            $stmt = $this->db->prepare("UPDATE admin_broadcasts SET is_unsent = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$unsend ? 1 : 0, $broadcastId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error unsendAdminBroadcast: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Permanently delete an admin broadcast from database.
     */
    public function deleteAdminBroadcast(int $broadcastId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM admin_broadcasts WHERE id = ?");
            $stmt->execute([$broadcastId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error deleteAdminBroadcast: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch unsend multiple broadcasts.
     */
    public function batchUnsendAdminBroadcasts(array $broadcastIds, bool $unsend = true): bool {
        try {
            if (empty($broadcastIds)) return false;
            $placeholders = implode(',', array_fill(0, count($broadcastIds), '?'));
            $params = array_merge([$unsend ? 1 : 0], array_map('intval', $broadcastIds));
            $stmt = $this->db->prepare("UPDATE admin_broadcasts SET is_unsent = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error batchUnsendAdminBroadcasts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch delete multiple broadcasts.
     */
    public function batchDeleteAdminBroadcasts(array $broadcastIds): bool {
        try {
            if (empty($broadcastIds)) return false;
            $placeholders = implode(',', array_fill(0, count($broadcastIds), '?'));
            $params = array_map('intval', $broadcastIds);
            $stmt = $this->db->prepare("DELETE FROM admin_broadcasts WHERE id IN ({$placeholders})");
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error batchDeleteAdminBroadcasts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle pinned state of an admin broadcast.
     */
    public function togglePinAdminBroadcast(int $broadcastId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE admin_broadcasts SET is_pinned = IF(is_pinned = 1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$broadcastId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error togglePinAdminBroadcast: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Edit the content of an admin broadcast.
     */
    public function editAdminBroadcast(int $broadcastId, string $newContent): bool {
        try {
            $stmt = $this->db->prepare("UPDATE admin_broadcasts SET content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([trim($newContent), $broadcastId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error editAdminBroadcast: " . $e->getMessage());
            return false;
        }
    }

    public function getAllStudents(): array {
        try {
            $stmt = $this->db->prepare("SELECT id, name, roll_number FROM students ORDER BY name ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function getAllFaculty(): array {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM teachers ORDER BY name ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}
