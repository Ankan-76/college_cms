<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;
use Exception;

class AssessmentController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all assessments for a specific course with aggregated marks statistics.
     */
    public function getAssessmentsByCourse(int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    a.id, 
                    a.course_id, 
                    a.faculty_id, 
                    a.title, 
                    a.max_marks, 
                    a.pass_marks,
                    a.created_at,
                    COUNT(am.id) AS graded_count,
                    ROUND(AVG(am.marks_obtained), 1) AS avg_marks,
                    MAX(am.marks_obtained) AS max_obtained,
                    MIN(am.marks_obtained) AS min_obtained
                FROM assessments a
                LEFT JOIN assessment_marks am ON am.assessment_id = a.id AND am.marks_obtained IS NOT NULL
                WHERE a.course_id = ? 
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching assessments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve a specific assessment by ID with course details and optional faculty authorization.
     */
    public function getAssessmentById(int $assessmentId, ?int $facultyId = null): ?array {
        try {
            $sql = "
                SELECT a.*, c.course_code, c.course_name, c.department_id, c.semester_id, d.dept_name, s.semester_number
                FROM assessments a
                JOIN courses c ON a.course_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN semesters s ON c.semester_id = s.id
                WHERE a.id = ?
            ";
            $params = [$assessmentId];
            if ($facultyId !== null) {
                $sql .= " AND a.faculty_id = ?";
                $params[] = $facultyId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $assessment = $stmt->fetch();
            return $assessment ?: null;
        } catch (PDOException $e) {
            error_log("DB Error fetching assessment {$assessmentId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new assessment.
     */
    public function createAssessment(int $courseId, int $facultyId, string $title, int $maxMarks, int $passMarks = 40): array {
        $title = trim($title);
        if (empty($title) || $maxMarks <= 0 || $courseId <= 0) {
            return ['success' => false, 'message' => 'Please provide a valid assessment title and positive maximum marks.'];
        }

        if ($passMarks <= 0) {
            $passMarks = (int)round($maxMarks * 0.4);
        }
        if ($passMarks > $maxMarks) {
            return ['success' => false, 'message' => 'Pass marks cannot exceed maximum marks.'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO assessments (course_id, faculty_id, title, max_marks, pass_marks)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $facultyId, $title, $maxMarks, $passMarks]);
            $newId = (int)$this->db->lastInsertId();
            return ['success' => true, 'message' => 'Assessment created successfully.', 'id' => $newId];
        } catch (PDOException $e) {
            error_log("DB Error creating assessment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during creation.'];
        }
    }

    /**
     * Update an assessment's title, maximum marks, and pass marks.
     */
    public function updateAssessment(int $assessmentId, int $facultyId, string $title, int $maxMarks, int $passMarks = 40): array {
        $title = trim($title);
        if (empty($title) || $maxMarks <= 0) {
            return ['success' => false, 'message' => 'Invalid title or maximum marks.'];
        }

        if ($passMarks <= 0) {
            $passMarks = (int)round($maxMarks * 0.4);
        }
        if ($passMarks > $maxMarks) {
            return ['success' => false, 'message' => 'Pass marks cannot exceed maximum marks.'];
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE assessments 
                SET title = ?, max_marks = ?, pass_marks = ? 
                WHERE id = ? AND faculty_id = ?
            ");
            $stmt->execute([$title, $maxMarks, $passMarks, $assessmentId, $facultyId]);
            
            if ($stmt->rowCount() >= 0) {
                return ['success' => true, 'message' => 'Assessment updated successfully.'];
            }
            return ['success' => false, 'message' => 'Assessment not found or permission denied.'];
        } catch (PDOException $e) {
            error_log("DB Error updating assessment {$assessmentId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during update.'];
        }
    }

    /**
     * Delete an assessment and associated student marks (cascading).
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

    /**
     * Fetch enrolled active students for a course along with their current marks for the assessment.
     */
    public function getStudentsWithMarks(int $assessmentId, int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.id AS student_id, 
                    s.name, 
                    s.roll_number, 
                    s.registration_number, 
                    s.email,
                    am.id AS mark_id, 
                    am.marks_obtained, 
                    am.remarks
                FROM students s
                JOIN courses c ON c.id = ?
                LEFT JOIN assessment_marks am ON am.student_id = s.id AND am.assessment_id = ?
                WHERE s.semester_id = c.semester_id
                  AND s.department_id = c.department_id
                  AND s.status = 'ACTIVE'
                ORDER BY s.roll_number ASC
            ");
            $stmt->execute([$courseId, $assessmentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching student marks for assessment {$assessmentId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Atomically batch save / update student marks for an assessment.
     */
    public function saveBatchMarks(int $assessmentId, int $facultyId, array $marksData, array $remarksData): array {
        $assessment = $this->getAssessmentById($assessmentId, $facultyId);
        if (!$assessment) {
            return ['success' => false, 'message' => 'Assessment not found or permission denied.'];
        }

        $maxMarks = (float)$assessment['max_marks'];

        try {
            $this->db->beginTransaction();

            $stmtUpsert = $this->db->prepare("
                INSERT INTO assessment_marks (assessment_id, student_id, marks_obtained, remarks)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    marks_obtained = VALUES(marks_obtained),
                    remarks = VALUES(remarks)
            ");

            $savedCount = 0;
            foreach ($marksData as $studentId => $rawMark) {
                $studentId = (int)$studentId;
                if ($studentId <= 0) continue;

                $remark = isset($remarksData[$studentId]) ? trim((string)$remarksData[$studentId]) : null;
                if ($remark === '') $remark = null;

                // Handle blank/unmarked
                if ($rawMark === '' || $rawMark === null) {
                    $stmtUpsert->execute([$assessmentId, $studentId, null, $remark]);
                    continue;
                }

                $mark = (float)$rawMark;
                // Clamp mark bounds
                if ($mark < 0) $mark = 0.0;
                if ($mark > $maxMarks) $mark = $maxMarks;

                $stmtUpsert->execute([$assessmentId, $studentId, $mark, $remark]);
                $savedCount++;
            }

            $this->db->commit();
            return [
                'success' => true,
                'message' => "Marks successfully saved for {$savedCount} student(s)."
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("DB Error batch saving marks: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save marks due to a database error.'];
        }
    }

    /**
     * Get aggregate analytics for an assessment.
     */
    public function getAssessmentAnalytics(int $assessmentId, int $courseId): array {
        try {
            // 1. Total enrolled count
            $stmtEnrolled = $this->db->prepare("
                SELECT COUNT(s.id) 
                FROM students s
                JOIN courses c ON c.id = ?
                WHERE s.semester_id = c.semester_id
                  AND s.department_id = c.department_id
                  AND s.status = 'ACTIVE'
            ");
            $stmtEnrolled->execute([$courseId]);
            $totalEnrolled = (int)$stmtEnrolled->fetchColumn();

            // 2. Graded metrics
            $stmtMetrics = $this->db->prepare("
                SELECT 
                    COUNT(am.id) AS graded_count,
                    ROUND(AVG(am.marks_obtained), 1) AS avg_score,
                    MAX(am.marks_obtained) AS highest_score,
                    MIN(am.marks_obtained) AS lowest_score,
                    SUM(CASE WHEN am.marks_obtained >= a.pass_marks THEN 1 ELSE 0 END) AS pass_count
                FROM assessments a
                JOIN assessment_marks am ON am.assessment_id = a.id AND am.marks_obtained IS NOT NULL
                WHERE a.id = ?
                GROUP BY a.id
            ");
            $stmtMetrics->execute([$assessmentId]);
            $metrics = $stmtMetrics->fetch() ?: [
                'graded_count' => 0,
                'avg_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'pass_count' => 0
            ];

            $gradedCount = (int)($metrics['graded_count'] ?? 0);
            $passCount = (int)($metrics['pass_count'] ?? 0);
            $passRate = $gradedCount > 0 ? round(($passCount / $gradedCount) * 100, 1) : 0;

            return [
                'total_enrolled' => $totalEnrolled,
                'graded_count' => $gradedCount,
                'ungraded_count' => max(0, $totalEnrolled - $gradedCount),
                'avg_score' => (float)($metrics['avg_score'] ?? 0),
                'highest_score' => (float)($metrics['highest_score'] ?? 0),
                'lowest_score' => (float)($metrics['lowest_score'] ?? 0),
                'pass_count' => $passCount,
                'pass_rate' => $passRate
            ];
        } catch (PDOException $e) {
            error_log("DB Error getting analytics for assessment {$assessmentId}: " . $e->getMessage());
            return [
                'total_enrolled' => 0,
                'graded_count' => 0,
                'ungraded_count' => 0,
                'avg_score' => 0,
                'highest_score' => 0,
                'lowest_score' => 0,
                'pass_count' => 0,
                'pass_rate' => 0
            ];
        }
    }
}
