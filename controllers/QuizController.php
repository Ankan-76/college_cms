<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;

class QuizController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── QUIZ CRUD ──────────────────────────────────────

    /**
     * Get all quizzes for a specific course.
     */
    public function getQuizzesByCourse(int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT q.*,
                       (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
                       (SELECT COALESCE(SUM(qq2.marks), 0) FROM quiz_questions qq2 WHERE qq2.quiz_id = q.id) as total_marks,
                       (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as attempt_count
                FROM quizzes q
                WHERE q.course_id = ?
                ORDER BY q.created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching quizzes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single quiz by ID.
     */
    public function getQuizById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT q.*,
                       (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
                       (SELECT COALESCE(SUM(qq2.marks), 0) FROM quiz_questions qq2 WHERE qq2.quiz_id = q.id) as total_marks,
                       (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) as attempt_count
                FROM quizzes q
                WHERE q.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("DB Error fetching quiz: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new quiz.
     */
    public function createQuiz(int $courseId, int $facultyId, string $title, string $description, int $durationMinutes, ?string $startTime, ?string $endTime): array {
        if (empty($title) || $durationMinutes <= 0) {
            return ['success' => false, 'message' => 'Title and duration are required.'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO quizzes (course_id, faculty_id, title, description, duration_minutes, start_time, end_time)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $facultyId, $title, $description, $durationMinutes, $startTime, $endTime]);
            $quizId = (int)$this->db->lastInsertId();
            return ['success' => true, 'message' => 'Quiz created successfully.', 'quiz_id' => $quizId];
        } catch (PDOException $e) {
            error_log("DB Error creating quiz: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during creation.'];
        }
    }

    /**
     * Update quiz status (DRAFT, PUBLISHED, CLOSED).
     */
    public function updateQuizStatus(int $quizId, string $status, int $facultyId): array {
        $validStatuses = ['DRAFT', 'PUBLISHED', 'CLOSED'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        try {
            $stmt = $this->db->prepare("UPDATE quizzes SET status = ? WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$status, $quizId, $facultyId]);
            return $stmt->rowCount() > 0 
                ? ['success' => true, 'message' => "Quiz status changed to {$status}."]
                : ['success' => false, 'message' => 'Quiz not found or permission denied.'];
        } catch (PDOException $e) {
            error_log("DB Error updating quiz status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    /**
     * Delete a quiz (faculty ownership check).
     */
    public function deleteQuiz(int $id, int $facultyId): array {
        try {
            $check = $this->db->prepare("SELECT id FROM quizzes WHERE id = ? AND faculty_id = ?");
            $check->execute([$id, $facultyId]);
            if (!$check->fetch()) {
                return ['success' => false, 'message' => 'Quiz not found or permission denied.'];
            }

            // Delete attempts, questions, then quiz
            $this->db->prepare("DELETE FROM quiz_attempts WHERE quiz_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM quizzes WHERE id = ? AND faculty_id = ?")->execute([$id, $facultyId]);
            return ['success' => true, 'message' => 'Quiz deleted successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error deleting quiz: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during deletion.'];
        }
    }

    // ─── QUESTIONS ──────────────────────────────────────

    /**
     * Get all questions for a quiz.
     */
    public function getQuizQuestions(int $quizId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM quiz_questions 
                WHERE quiz_id = ? 
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute([$quizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching quiz questions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a question to a quiz.
     */
    public function addQuestion(int $quizId, string $questionText, string $optA, string $optB, string $optC, string $optD, string $correctOption, int $marks = 1): array {
        if (empty($questionText) || empty($optA) || empty($optB) || empty($optC) || empty($optD)) {
            return ['success' => false, 'message' => 'All question fields are required.'];
        }

        $validOptions = ['A', 'B', 'C', 'D'];
        if (!in_array(strtoupper($correctOption), $validOptions)) {
            return ['success' => false, 'message' => 'Invalid correct option.'];
        }

        try {
            // Get next sort order
            $sortStmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM quiz_questions WHERE quiz_id = ?");
            $sortStmt->execute([$quizId]);
            $nextOrder = (int)$sortStmt->fetch()['next_order'];

            $stmt = $this->db->prepare("
                INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$quizId, $questionText, $optA, $optB, $optC, $optD, strtoupper($correctOption), $marks, $nextOrder]);

            // Update quiz max_marks
            $this->recalculateQuizMarks($quizId);

            return ['success' => true, 'message' => 'Question added successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error adding question: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    /**
     * Update a question.
     */
    public function updateQuestion(int $questionId, string $questionText, string $optA, string $optB, string $optC, string $optD, string $correctOption, int $marks = 1): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE quiz_questions 
                SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, marks = ?
                WHERE id = ?
            ");
            $stmt->execute([$questionText, $optA, $optB, $optC, $optD, strtoupper($correctOption), $marks, $questionId]);

            if ($stmt->rowCount() > 0) {
                // Get quiz_id for recalculation
                $qStmt = $this->db->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
                $qStmt->execute([$questionId]);
                $q = $qStmt->fetch();
                if ($q) $this->recalculateQuizMarks($q['quiz_id']);

                return ['success' => true, 'message' => 'Question updated successfully.'];
            }
            return ['success' => false, 'message' => 'Question not found.'];
        } catch (PDOException $e) {
            error_log("DB Error updating question: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    /**
     * Delete a question.
     */
    public function deleteQuestion(int $questionId): array {
        try {
            // Get quiz_id first
            $qStmt = $this->db->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
            $qStmt->execute([$questionId]);
            $q = $qStmt->fetch();

            $stmt = $this->db->prepare("DELETE FROM quiz_questions WHERE id = ?");
            $stmt->execute([$questionId]);

            if ($stmt->rowCount() > 0 && $q) {
                $this->recalculateQuizMarks($q['quiz_id']);
                return ['success' => true, 'message' => 'Question deleted successfully.'];
            }
            return ['success' => false, 'message' => 'Question not found.'];
        } catch (PDOException $e) {
            error_log("DB Error deleting question: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }
    }

    /**
     * Recalculate total quiz marks from questions.
     */
    private function recalculateQuizMarks(int $quizId): void {
        try {
            $stmt = $this->db->prepare("UPDATE quizzes SET max_marks = (SELECT COALESCE(SUM(marks), 0) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?");
            $stmt->execute([$quizId, $quizId]);
        } catch (PDOException $e) {
            error_log("DB Error recalculating quiz marks: " . $e->getMessage());
        }
    }

    // ─── QUIZ ATTEMPTS ─────────────────────────────────

    /**
     * Submit a quiz attempt (auto-grade MCQs).
     */
    public function submitAttempt(int $quizId, int $studentId, array $answers, int $timeTakenSeconds, string $startedAt): array {
        // Check if already attempted
        try {
            $existCheck = $this->db->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
            $existCheck->execute([$quizId, $studentId]);
            if ($existCheck->fetch()) {
                return ['success' => false, 'message' => 'You have already attempted this quiz.'];
            }
        } catch (PDOException $e) {
            error_log("DB Error checking attempt: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error.'];
        }

        // Fetch questions and auto-grade
        $questions = $this->getQuizQuestions($quizId);
        $totalMarks = 0;
        $score = 0;
        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        foreach ($questions as $q) {
            $totalMarks += (int)$q['marks'];
            $qId = (string)$q['id'];
            
            if (isset($answers[$qId]) && !empty($answers[$qId])) {
                if (strtoupper($answers[$qId]) === $q['correct_option']) {
                    $score += (int)$q['marks'];
                    $correct++;
                } else {
                    $wrong++;
                }
            } else {
                $unanswered++;
            }
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO quiz_attempts (quiz_id, student_id, answers, score, total_marks, correct_count, wrong_count, unanswered_count, time_taken_seconds, started_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $quizId, $studentId, json_encode($answers),
                $score, $totalMarks, $correct, $wrong, $unanswered,
                $timeTakenSeconds, $startedAt
            ]);

            return [
                'success' => true,
                'message' => 'Quiz submitted successfully.',
                'score' => $score,
                'total_marks' => $totalMarks,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered
            ];
        } catch (PDOException $e) {
            error_log("DB Error submitting quiz attempt: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during submission.'];
        }
    }

    /**
     * Get all attempts for a quiz (faculty view).
     */
    public function getAttemptsByQuiz(int $quizId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT qa.*, s.name, s.roll_number, s.registration_number
                FROM quiz_attempts qa
                JOIN students s ON qa.student_id = s.id
                WHERE qa.quiz_id = ?
                ORDER BY qa.score DESC
            ");
            $stmt->execute([$quizId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching quiz attempts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a student's attempt for a specific quiz.
     */
    public function getStudentAttempt(int $quizId, int $studentId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
            $stmt->execute([$quizId, $studentId]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("DB Error fetching student attempt: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all quizzes available for a student (based on department/semester).
     */
    public function getStudentQuizzes(int $departmentId, int $semesterId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT q.*, c.course_code, c.course_name, fp.name as faculty_name,
                       (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
                       (SELECT COALESCE(SUM(qq2.marks), 0) FROM quiz_questions qq2 WHERE qq2.quiz_id = q.id) as total_marks
                FROM quizzes q
                JOIN courses c ON q.course_id = c.id
                LEFT JOIN teachers fp ON q.faculty_id = fp.id
                WHERE c.department_id = ? AND c.semester_id = ? AND q.status = 'PUBLISHED'
                ORDER BY q.end_time DESC, q.created_at DESC
            ");
            $stmt->execute([$departmentId, $semesterId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching student quizzes: " . $e->getMessage());
            return [];
        }
    }
}
