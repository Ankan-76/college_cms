<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

use Config\Database;
use PDO;
use Exception;

class InquiryController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Submit an admission inquiry
     *
     * @param array $data
     * @return bool
     */
    public function submitInquiry(array $data): bool {
        try {
            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $departmentId = !empty($data['department_id']) ? (int)$data['department_id'] : null;
            $qualification = trim($data['previous_qualification'] ?? '');
            $message = trim($data['message'] ?? '');

            // Validation
            if (empty($fullName)) {
                set_flash_message('Please enter your full name.', 'error');
                return false;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_flash_message('Please provide a valid email address.', 'error');
                return false;
            }

            if (empty($phone)) {
                set_flash_message('Please provide your contact phone number.', 'error');
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO admission_inquiries 
                (full_name, email, phone, department_id, previous_qualification, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $success = $stmt->execute([
                $fullName,
                $email,
                $phone,
                $departmentId,
                $qualification ?: null,
                $message ?: null
            ]);

            if ($success) {
                set_flash_message('Thank you! Your admission inquiry has been received. Our counselor will contact you soon.', 'success');
                return true;
            }

            set_flash_message('Failed to submit inquiry. Please try again.', 'error');
            return false;

        } catch (Exception $e) {
            error_log('Inquiry Submission Error: ' . $e->getMessage());
            set_flash_message('A system error occurred. Please try again later.', 'error');
            return false;
        }
    }

    /**
     * Retrieve all admission inquiries with optional status, department, and search filters.
     *
     * @param string|null $statusFilter
     * @param int|null $departmentId
     * @param string|null $search
     * @return array
     */
    public function getAllInquiries(?string $statusFilter = null, ?int $departmentId = null, ?string $search = null): array {
        try {
            $sql = "
                SELECT 
                    ai.*,
                    d.dept_name,
                    d.dept_code
                FROM admission_inquiries ai
                LEFT JOIN departments d ON ai.department_id = d.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($statusFilter) && strtoupper($statusFilter) !== 'ALL') {
                $sql .= " AND LOWER(ai.status) = ?";
                $params[] = strtolower($statusFilter);
            }

            if (!empty($departmentId) && $departmentId > 0) {
                $sql .= " AND ai.department_id = ?";
                $params[] = $departmentId;
            }

            if (!empty($search)) {
                $sql .= " AND (ai.full_name LIKE ? OR ai.email LIKE ? OR ai.phone LIKE ? OR ai.previous_qualification LIKE ? OR ai.message LIKE ?)";
                $term = "%{$search}%";
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= " ORDER BY ai.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error retrieving admission inquiries: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get aggregate metric counts for admission inquiries.
     *
     * @return array
     */
    public function getInquiryStats(): array {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN LOWER(status) = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
                    SUM(CASE WHEN LOWER(status) = 'admitted' THEN 1 ELSE 0 END) as admitted_count,
                    SUM(CASE WHEN LOWER(status) = 'rejected' THEN 1 ELSE 0 END) as rejected_count
                FROM admission_inquiries
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($row['total'] ?? 0),
                'pending' => (int)($row['pending_count'] ?? 0),
                'contacted' => (int)($row['contacted_count'] ?? 0),
                'admitted' => (int)($row['admitted_count'] ?? 0),
                'rejected' => (int)($row['rejected_count'] ?? 0),
            ];
        } catch (Exception $e) {
            error_log('Error fetching inquiry stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'contacted' => 0,
                'admitted' => 0,
                'rejected' => 0,
            ];
        }
    }

    /**
     * Retrieve a single admission inquiry by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getInquiryById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ai.*,
                    d.dept_name,
                    d.dept_code
                FROM admission_inquiries ai
                LEFT JOIN departments d ON ai.department_id = d.id
                WHERE ai.id = ?
            ");
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Exception $e) {
            error_log('Error fetching inquiry by id: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update an inquiry's status and counselor notes.
     *
     * @param int $id
     * @param string $status
     * @param string|null $adminNotes
     * @return bool
     */
    public function updateStatus(int $id, string $status, ?string $adminNotes = null): bool {
        try {
            $allowedStatus = ['pending', 'contacted', 'admitted', 'rejected'];
            $normalizedStatus = strtolower(trim($status));

            if (!in_array($normalizedStatus, $allowedStatus, true)) {
                set_flash_message('Invalid inquiry status selected.', 'error');
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE admission_inquiries 
                SET status = ?, admin_notes = ? 
                WHERE id = ?
            ");
            $success = $stmt->execute([$normalizedStatus, $adminNotes, $id]);

            if ($success) {
                set_flash_message('Inquiry record updated successfully.', 'success');
                return true;
            }

            set_flash_message('Failed to update inquiry record.', 'error');
            return false;
        } catch (Exception $e) {
            error_log('Error updating inquiry status: ' . $e->getMessage());
            set_flash_message('Database error occurred while updating inquiry.', 'error');
            return false;
        }
    }

    /**
     * Delete an inquiry record.
     *
     * @param int $id
     * @return bool
     */
    public function deleteInquiry(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM admission_inquiries WHERE id = ?");
            $success = $stmt->execute([$id]);

            if ($success) {
                set_flash_message('Admission inquiry deleted successfully.', 'success');
                return true;
            }

            set_flash_message('Failed to delete inquiry.', 'error');
            return false;
        } catch (Exception $e) {
            error_log('Error deleting admission inquiry: ' . $e->getMessage());
            set_flash_message('Database error occurred while deleting inquiry.', 'error');
            return false;
        }
    }
}

