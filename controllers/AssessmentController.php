<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;

class AssessmentController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all assessments for a specific course.
     */
    public function getAssessmentsByCourse(int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM assessments 
                WHERE course_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching assessments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new assessment.
     */
    public function createAssessment(int $courseId, int $facultyId, string $title, int $maxMarks): array {
        if (empty($title) || $maxMarks <= 0) {
            return ['success' => false, 'message' => 'Invalid title or max marks.'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO assessments (course_id, faculty_id, title, max_marks)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $facultyId, $title, $maxMarks]);
            return ['success' => true, 'message' => 'Assessment created successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error creating assessment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during creation.'];
        }
    }

    /**
     * Delete an assessment.
     */
    public function deleteAssessment(int $assessmentId, int $facultyId): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM assessments WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$assessmentId, $facultyId]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Assessment deleted successfully.'];
            } else {
                return ['success' => false, 'message' => 'Assessment not found or permission denied.'];
            }
        } catch (PDOException $e) {
            error_log("DB Error deleting assessment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during deletion.'];
        }
    }
}
