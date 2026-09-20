<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

use Config\Database;
use PDO;

class LeaveController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Submit a new leave request for the logged-in user.
     *
     * @param int    $applicantId   The user's ID from their role table
     * @param string $applicantType STUDENT or FACULTY
     * @param string $leaveType     SICK, CASUAL, or OTHER
     * @param string $startDate     Y-m-d
     * @param string $endDate       Y-m-d
     * @param string $reason        Free-text reason
     * @param string $customSubject  Custom subject when leave type is OTHER
     * @param array  $supportingDocs Array of uploaded file paths
     * @return bool
     */
    public function applyLeave(int $applicantId, string $applicantType, string $leaveType, string $startDate, string $endDate, string $reason, string $customSubject = '', array $supportingDocs = []): bool {
        try {
            // Validate leave type
            if (!in_array($leaveType, ['SICK', 'CASUAL', 'OTHER'])) {
                set_flash_message('Invalid leave type selected.', 'error');
                return false;
            }

            // Validate applicant type
            if (!in_array($applicantType, ['STUDENT', 'FACULTY'])) {
                set_flash_message('Invalid applicant type.', 'error');
                return false;
            }

            // Validate custom subject for OTHER type
            if ($leaveType === 'OTHER' && strlen(trim($customSubject)) < 3) {
                set_flash_message('Please specify the subject/reason type for your leave.', 'error');
                return false;
            }

            // Validate dates
            $start = strtotime($startDate);
            $end = strtotime($endDate);
            if (!$start || !$end) {
                set_flash_message('Invalid date format provided.', 'error');
                return false;
            }
            if ($end < $start) {
                set_flash_message('End date cannot be before start date.', 'error');
                return false;
            }

            // Validate reason
            if (strlen(trim($reason)) < 10) {
                set_flash_message('Please provide a detailed reason (at least 10 characters).', 'error');
                return false;
            }

            $docsJson = !empty($supportingDocs) ? json_encode($supportingDocs) : null;
            $subjectValue = $leaveType === 'OTHER' ? trim($customSubject) : null;

            $stmt = $this->db->prepare("
                INSERT INTO leave_requests (applicant_id, applicant_type, leave_type, custom_subject, start_date, end_date, reason, supporting_docs)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$applicantId, $applicantType, $leaveType, $subjectValue, $startDate, $endDate, trim($reason), $docsJson]);

            set_flash_message('Leave application submitted successfully. You will be notified once it is reviewed.', 'success');
            return true;
        } catch (\PDOException $e) {
            error_log("DB Error applying leave: " . $e->getMessage());
            set_flash_message('Failed to submit leave request. Please try again.', 'error');
            return false;
        }
    }

    /**
     * Get all leave requests for a specific user.
     *
     * @param int    $userId The applicant's ID
     * @param string $type   STUDENT or FACULTY
     * @param string|null $statusFilter Optional status filter
     * @return array
     */
    public function getMyLeaves(int $userId, string $type, ?string $statusFilter = null): array {
        try {
            $sql = "
                SELECT lr.*, a.name as reviewer_name
                FROM leave_requests lr
                LEFT JOIN admins a ON lr.reviewed_by = a.id
                WHERE lr.applicant_id = ? AND lr.applicant_type = ?
            ";
            $params = [$userId, $type];

            if ($statusFilter && in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED'])) {
                $sql .= " AND lr.status = ?";
                $params[] = $statusFilter;
            }

            $sql .= " ORDER BY lr.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error fetching leaves: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Admin: Get all leave requests with applicant name resolved.
     *
     * @param string|null $statusFilter Optional status filter
     * @param string|null $typeFilter   Optional applicant type filter
     * @return array
     */
    public function getAllLeaves(?string $statusFilter = null, ?string $typeFilter = null): array {
        try {
            // We need to union-join to resolve names from students and teachers tables
            $sql = "
                SELECT lr.*,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN s.name 
                        WHEN lr.applicant_type = 'FACULTY' THEN t.name 
                    END as applicant_name,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN s.email 
                        WHEN lr.applicant_type = 'FACULTY' THEN t.email 
                    END as applicant_email,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN d.dept_name 
                        WHEN lr.applicant_type = 'FACULTY' THEN NULL
                    END as department_name,
                    a.name as reviewer_name
                FROM leave_requests lr
                LEFT JOIN students s ON lr.applicant_type = 'STUDENT' AND lr.applicant_id = s.id
                LEFT JOIN teachers t ON lr.applicant_type = 'FACULTY' AND lr.applicant_id = t.id
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN admins a ON lr.reviewed_by = a.id
                WHERE 1=1
            ";
            $params = [];

            if ($statusFilter && in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED'])) {
                $sql .= " AND lr.status = ?";
                $params[] = $statusFilter;
            }

            if ($typeFilter && in_array($typeFilter, ['STUDENT', 'FACULTY'])) {
                $sql .= " AND lr.applicant_type = ?";
                $params[] = $typeFilter;
            }

            $sql .= " ORDER BY FIELD(lr.status, 'PENDING', 'APPROVED', 'REJECTED'), lr.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("DB Error fetching all leaves: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get counts by status for dashboard stats.
     *
     * @return array ['pending' => int, 'approved' => int, 'rejected' => int, 'total' => int]
     */
    public function getLeaveCounts(): array {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'APPROVED' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) as rejected
                FROM leave_requests
            ");
            return $stmt->fetch() ?: ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        } catch (\PDOException $e) {
            error_log("DB Error fetching leave counts: " . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }

    /**
     * Get a single leave request by ID with full details.
     *
     * @param int $id The leave request ID
     * @return array|null
     */
    public function getLeaveById(int $id): ?array {
        try {
            $sql = "
                SELECT lr.*,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN s.name 
                        WHEN lr.applicant_type = 'FACULTY' THEN t.name 
                    END as applicant_name,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN s.email 
                        WHEN lr.applicant_type = 'FACULTY' THEN t.email 
                    END as applicant_email,
                    CASE 
                        WHEN lr.applicant_type = 'STUDENT' THEN d.dept_name 
                        WHEN lr.applicant_type = 'FACULTY' THEN NULL
                    END as department_name,
                    a.name as reviewer_name
                FROM leave_requests lr
                LEFT JOIN students s ON lr.applicant_type = 'STUDENT' AND lr.applicant_id = s.id
                LEFT JOIN teachers t ON lr.applicant_type = 'FACULTY' AND lr.applicant_id = t.id
                LEFT JOIN departments d ON s.department_id = d.id
                LEFT JOIN admins a ON lr.reviewed_by = a.id
                WHERE lr.id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("DB Error fetching leave by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a leave request if it belongs to the user.
     *
     * @param int $leaveId
     * @param int $userId
     * @param string $userType
     * @return bool
     */
    public function deleteLeave(int $leaveId, int $userId, string $userType): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM leave_requests WHERE id = ? AND applicant_id = ? AND applicant_type = ?");
            $stmt->execute([$leaveId, $userId, $userType]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("DB Error deleting leave: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a specific leave request to check ownership and retrieve supporting docs.
     */
    public function getLeaveForUser(int $leaveId, int $userId, string $userType): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM leave_requests WHERE id = ? AND applicant_id = ? AND applicant_type = ?");
            $stmt->execute([$leaveId, $userId, $userType]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("DB Error fetching user leave: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Admin: Approve or reject a leave request.
     *
     * @param int    $leaveId  The leave request ID
     * @param string $status   APPROVED or REJECTED
     * @param string $remarks  Admin's comments
     * @param int    $adminId  The admin's ID
     * @return bool
     */
    public function reviewLeave(int $leaveId, string $status, string $remarks, int $adminId): bool {
        try {
            if (!in_array($status, ['APPROVED', 'REJECTED'])) {
                set_flash_message('Invalid review status.', 'error');
                return false;
            }

            // Check leave exists and is still pending
            $check = $this->db->prepare("SELECT id, status FROM leave_requests WHERE id = ?");
            $check->execute([$leaveId]);
            $leave = $check->fetch();

            if (!$leave) {
                set_flash_message('Leave request not found.', 'error');
                return false;
            }

            if ($leave['status'] !== 'PENDING') {
                set_flash_message('This leave request has already been reviewed.', 'error');
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE leave_requests 
                SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, trim($remarks), $adminId, $leaveId]);

            $action = $status === 'APPROVED' ? 'approved' : 'rejected';
            set_flash_message("Leave request has been {$action} successfully.", 'success');
            return true;
        } catch (\PDOException $e) {
            error_log("DB Error reviewing leave: " . $e->getMessage());
            set_flash_message('Failed to process leave request. Please try again.', 'error');
            return false;
        }
    }
}
