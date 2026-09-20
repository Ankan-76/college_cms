<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;

class AssignmentController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all assignments for a specific course.
     */
    public function getAssignmentsByCourse(int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id) as submission_count,
                       (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id AND sub.marks_obtained IS NOT NULL) as graded_count
                FROM assignments a
                WHERE a.course_id = ?
                ORDER BY a.deadline DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching assignments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single assignment by ID.
     */
    public function getAssignmentById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("DB Error fetching assignment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new assignment.
     */
    public function createAssignment(int $courseId, int $facultyId, string $title, string $description, int $maxMarks, string $deadline, ?array $fileData = null): array {
        if (empty($title) || $maxMarks <= 0 || empty($deadline)) {
            return ['success' => false, 'message' => 'Title, max marks, and deadline are required.'];
        }

        $refFilePath = null;

        // Handle optional reference file upload
        if ($fileData && !empty($fileData['name']) && $fileData['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/assignments/references/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'png'];
            if (!in_array($ext, $allowedExts)) {
                return ['success' => false, 'message' => 'Reference file type not allowed.'];
            }

            $newFileName = uniqid('ref_') . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $newFileName;
            $refFilePath = 'uploads/assignments/references/' . $newFileName;

            if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
                return ['success' => false, 'message' => 'Failed to upload reference file.'];
            }
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO assignments (course_id, faculty_id, title, description, max_marks, deadline, reference_file)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $facultyId, $title, $description, $maxMarks, $deadline, $refFilePath]);
            return ['success' => true, 'message' => 'Assignment created successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error creating assignment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during creation.'];
        }
    }

    /**
     * Delete an assignment (faculty ownership check).
     */
    public function deleteAssignment(int $id, int $facultyId): array {
        try {
            // Get assignment details for file cleanup
            $stmt = $this->db->prepare("SELECT reference_file FROM assignments WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$id, $facultyId]);
            $assignment = $stmt->fetch();

            if (!$assignment) {
                return ['success' => false, 'message' => 'Assignment not found or permission denied.'];
            }

            // Delete submission files
            $subStmt = $this->db->prepare("SELECT file_path FROM assignment_submissions WHERE assignment_id = ?");
            $subStmt->execute([$id]);
            $submissions = $subStmt->fetchAll();
            foreach ($submissions as $sub) {
                $path = __DIR__ . '/../' . $sub['file_path'];
                if (file_exists($path)) unlink($path);
            }

            // Delete submissions from DB
            $this->db->prepare("DELETE FROM assignment_submissions WHERE assignment_id = ?")->execute([$id]);

            // Delete reference file
            if ($assignment['reference_file']) {
                $refPath = __DIR__ . '/../' . $assignment['reference_file'];
                if (file_exists($refPath)) unlink($refPath);
            }

            // Delete assignment
            $this->db->prepare("DELETE FROM assignments WHERE id = ? AND faculty_id = ?")->execute([$id, $facultyId]);
            return ['success' => true, 'message' => 'Assignment deleted successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error deleting assignment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during deletion.'];
        }
    }

    /**
     * Get all submissions for an assignment (faculty view).
     */
    public function getSubmissionsForAssignment(int $assignmentId, int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id as student_id, s.name, s.roll_number, s.registration_number,
                       sub.id as submission_id, sub.file_path, sub.file_size, sub.file_name,
                       sub.is_late, sub.marks_obtained, sub.feedback, sub.submitted_at, sub.graded_at
                FROM students s
                LEFT JOIN assignment_submissions sub ON s.id = sub.student_id AND sub.assignment_id = ?
                WHERE s.semester_id = (SELECT semester_id FROM courses WHERE id = ?)
                  AND s.department_id = (SELECT department_id FROM courses WHERE id = ?)
                  AND s.status = 'ACTIVE'
                ORDER BY s.roll_number ASC
            ");
            $stmt->execute([$assignmentId, $courseId, $courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching submissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Student submits an assignment file.
     */
    public function submitAssignment(int $assignmentId, int $studentId, array $fileData): array {
        // Validate file
        if (empty($fileData['name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Invalid file upload.'];
        }

        // Verify assignment exists and check deadline
        $assignment = $this->getAssignmentById($assignmentId);
        if (!$assignment) {
            return ['success' => false, 'message' => 'Assignment not found.'];
        }

        $isLate = (strtotime($assignment['deadline']) < time()) ? 1 : 0;
        if ($isLate && !$assignment['allow_late']) {
            return ['success' => false, 'message' => 'The deadline has passed and late submissions are not allowed.'];
        }

        // Check for existing submission
        try {
            $existCheck = $this->db->prepare("SELECT id, file_path FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $existCheck->execute([$assignmentId, $studentId]);
            $existing = $existCheck->fetch();
        } catch (PDOException $e) {
            error_log("DB Error checking submission: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }

        // Upload file
        $uploadDir = __DIR__ . '/../uploads/assignments/submissions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'png', 'xlsx', 'xls'];
        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'message' => 'File type not allowed. Accepted: PDF, Word, PPT, ZIP, images, Excel.'];
        }

        if ($fileData['size'] > 25 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds 25MB limit.'];
        }

        $newFileName = uniqid('sub_') . '_' . time() . '.' . $ext;
        $destination = $uploadDir . $newFileName;
        $dbFilePath = 'uploads/assignments/submissions/' . $newFileName;

        if (!move_uploaded_file($fileData['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }

        try {
            if ($existing) {
                // Delete old file
                $oldPath = __DIR__ . '/../' . $existing['file_path'];
                if (file_exists($oldPath)) unlink($oldPath);

                // Update existing submission
                $stmt = $this->db->prepare("
                    UPDATE assignment_submissions 
                    SET file_path = ?, file_size = ?, file_name = ?, is_late = ?, 
                        marks_obtained = NULL, feedback = NULL, graded_at = NULL, graded_by = NULL,
                        submitted_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$dbFilePath, $fileData['size'], $fileData['name'], $isLate, $existing['id']]);
            } else {
                // New submission
                $stmt = $this->db->prepare("
                    INSERT INTO assignment_submissions (assignment_id, student_id, file_path, file_size, file_name, is_late)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$assignmentId, $studentId, $dbFilePath, $fileData['size'], $fileData['name'], $isLate]);
            }
            return ['success' => true, 'message' => 'Assignment submitted successfully.' . ($isLate ? ' (Marked as late submission)' : '')];
        } catch (PDOException $e) {
            error_log("DB Error submitting assignment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during submission.'];
        }
    }

    /**
     * Faculty grades a submission.
     */
    public function gradeSubmission(int $submissionId, float $marks, string $feedback, int $gradedBy): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE assignment_submissions 
                SET marks_obtained = ?, feedback = ?, graded_at = NOW(), graded_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$marks, $feedback, $gradedBy, $submissionId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Submission graded successfully.'];
            }
            return ['success' => false, 'message' => 'Submission not found.'];
        } catch (PDOException $e) {
            error_log("DB Error grading submission: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during grading.'];
        }
    }

    /**
     * Get all assignments for a student's enrolled courses.
     */
    public function getStudentAssignments(int $departmentId, int $semesterId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, c.course_code, c.course_name, fp.name as faculty_name
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                LEFT JOIN faculty_profiles fp ON a.faculty_id = fp.id
                WHERE c.department_id = ? AND c.semester_id = ?
                ORDER BY a.deadline DESC
            ");
            $stmt->execute([$departmentId, $semesterId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching student assignments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a student's submission for a specific assignment.
     */
    public function getStudentSubmission(int $assignmentId, int $studentId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignmentId, $studentId]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("DB Error fetching student submission: " . $e->getMessage());
            return null;
        }
    }
}
