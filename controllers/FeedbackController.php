<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

use Config\Database;
use PDO;
use Exception;

class FeedbackController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Submit feedback from any user (guest or authenticated)
     *
     * @param array $data
     * @return bool
     */
    public function submitFeedback(array $data): bool {
        try {
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = !empty($data['phone']) ? trim($data['phone']) : null;
            $userRole = strtoupper(trim($data['user_role'] ?? 'GUEST'));
            $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;
            $category = trim($data['category'] ?? 'General');
            $subject = trim($data['subject'] ?? '');
            $rating = !empty($data['rating']) ? (int)$data['rating'] : null;
            $message = trim($data['message'] ?? '');

            // Validations
            if (empty($name)) {
                set_flash_message('Please provide your name.', 'error');
                return false;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash_message('Please provide a valid email address.', 'error');
                return false;
            }

            if (empty($subject)) {
                set_flash_message('Please provide a subject for your feedback.', 'error');
                return false;
            }

            if (empty($message) || strlen($message) < 10) {
                set_flash_message('Please enter a feedback message of at least 10 characters.', 'error');
                return false;
            }

            if ($rating !== null && ($rating < 1 || $rating > 5)) {
                $rating = null;
            }

            $allowedRoles = ['STUDENT', 'FACULTY', 'ADMIN', 'GUEST'];
            if (!in_array($userRole, $allowedRoles)) {
                $userRole = 'GUEST';
            }

            // Capture IP and User Agent
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipParts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipAddress = trim($ipParts[0]);
            }
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

            $stmt = $this->db->prepare("
                INSERT INTO feedbacks (user_id, user_role, name, email, phone, category, subject, rating, message, status, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'NEW', ?, ?)
            ");

            $result = $stmt->execute([
                $userId,
                $userRole,
                $name,
                $email,
                $phone,
                $category,
                $subject,
                $rating,
                $message,
                $ipAddress,
                $userAgent
            ]);

            if ($result) {
                set_flash_message('Thank you! Your feedback has been submitted successfully.', 'success');
                return true;
            }

            set_flash_message('Failed to submit feedback. Please try again.', 'error');
            return false;
        } catch (Exception $e) {
            error_log('Feedback submission error: ' . $e->getMessage());
            set_flash_message('An error occurred while submitting your feedback. Please try again.', 'error');
            return false;
        }
    }

    /**
     * Retrieve all feedbacks with optional filters
     *
     * @param string|null $roleFilter
     * @param string|null $statusFilter
     * @param string|null $categoryFilter
     * @param string|null $search
     * @param int|null $ratingFilter
     * @return array
     */
    public function getAllFeedbacks(
        ?string $roleFilter = null,
        ?string $statusFilter = null,
        ?string $categoryFilter = null,
        ?string $search = null,
        ?int $ratingFilter = null
    ): array {
        try {
            $sql = "SELECT * FROM feedbacks WHERE 1=1";
            $params = [];

            if (!empty($roleFilter) && $roleFilter !== 'ALL') {
                $sql .= " AND user_role = ?";
                $params[] = strtoupper($roleFilter);
            }

            if (!empty($statusFilter) && $statusFilter !== 'ALL') {
                $sql .= " AND status = ?";
                $params[] = strtoupper($statusFilter);
            }

            if (!empty($categoryFilter) && $categoryFilter !== 'ALL') {
                $sql .= " AND category = ?";
                $params[] = $categoryFilter;
            }

            if (!empty($ratingFilter) && $ratingFilter > 0) {
                $sql .= " AND rating = ?";
                $params[] = $ratingFilter;
            }

            if (!empty($search)) {
                $sql .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error retrieving feedbacks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get aggregate statistics for feedback dashboard
     *
     * @return array
     */
    public function getFeedbackStats(): array {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'NEW' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'REVIEWED' THEN 1 ELSE 0 END) as reviewed_count,
                    SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN user_role = 'STUDENT' THEN 1 ELSE 0 END) as student_count,
                    SUM(CASE WHEN user_role = 'FACULTY' THEN 1 ELSE 0 END) as faculty_count,
                    SUM(CASE WHEN user_role = 'GUEST' THEN 1 ELSE 0 END) as guest_count,
                    AVG(CASE WHEN rating IS NOT NULL THEN rating ELSE NULL END) as avg_rating
                FROM feedbacks
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($row['total'] ?? 0),
                'new' => (int)($row['new_count'] ?? 0),
                'reviewed' => (int)($row['reviewed_count'] ?? 0),
                'resolved' => (int)($row['resolved_count'] ?? 0),
                'student' => (int)($row['student_count'] ?? 0),
                'faculty' => (int)($row['faculty_count'] ?? 0),
                'guest' => (int)($row['guest_count'] ?? 0),
                'avg_rating' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0.0,
            ];
        } catch (Exception $e) {
            error_log('Error fetching feedback stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'new' => 0,
                'reviewed' => 0,
                'resolved' => 0,
                'student' => 0,
                'faculty' => 0,
                'guest' => 0,
                'avg_rating' => 0.0
            ];
        }
    }

    /**
     * Get feedback by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getFeedbackById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM feedbacks WHERE id = ?");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            error_log('Error fetching feedback by id: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update feedback status and optional administrative notes
     *
     * @param int $id
     * @param string $status
     * @param string|null $adminNotes
     * @return bool
     */
    public function updateStatus(int $id, string $status, ?string $adminNotes = null): bool {
        try {
            $allowedStatus = ['NEW', 'REVIEWED', 'RESOLVED'];
            if (!in_array(strtoupper($status), $allowedStatus)) {
                set_flash_message('Invalid status selected.', 'error');
                return false;
            }

            if ($adminNotes !== null) {
                $stmt = $this->db->prepare("UPDATE feedbacks SET status = ?, admin_notes = ? WHERE id = ?");
                $res = $stmt->execute([strtoupper($status), trim($adminNotes), $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE feedbacks SET status = ? WHERE id = ?");
                $res = $stmt->execute([strtoupper($status), $id]);
            }

            if ($res) {
                set_flash_message('Feedback status updated successfully.', 'success');
                return true;
            }

            set_flash_message('Failed to update feedback status.', 'error');
            return false;
        } catch (Exception $e) {
            error_log('Error updating feedback status: ' . $e->getMessage());
            set_flash_message('An error occurred while updating feedback status.', 'error');
            return false;
        }
    }

    /**
     * Delete a feedback record
     *
     * @param int $id
     * @return bool
     */
    public function deleteFeedback(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM feedbacks WHERE id = ?");
            $res = $stmt->execute([$id]);

            if ($res) {
                set_flash_message('Feedback deleted successfully.', 'success');
                return true;
            }

            set_flash_message('Failed to delete feedback.', 'error');
            return false;
        } catch (Exception $e) {
            error_log('Error deleting feedback: ' . $e->getMessage());
            set_flash_message('An error occurred while deleting feedback.', 'error');
            return false;
        }
    }
}
