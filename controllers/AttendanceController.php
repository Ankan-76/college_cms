<?php
declare(strict_types=1);

namespace Controllers;

// In real app, standard autoload or relative path to database config
require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;
use Exception;

class AttendanceController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fetch students mapped to a specific course for attendance listing.
     * 
     * @param int $courseId The identifier of the course.
     * @param string|null $date The date for which to fetch attendance.
     * @return array Returns an array of associative arrays containing student details and attendance status.
     */
    public function getStudentsForCourse(int $courseId, ?string $date = null): array {
        try {
            if ($date) {
                $stmt = $this->db->prepare("
                    SELECT s.id as student_id, s.name, s.roll_number, s.registration_number, a.status as attendance_status
                    FROM students s
                    LEFT JOIN attendance a ON s.id = a.student_id AND a.course_id = ? AND a.date = ?
                    WHERE s.semester_id = (SELECT semester_id FROM courses WHERE id = ?)
                      AND s.department_id = (SELECT department_id FROM courses WHERE id = ?)
                      AND s.status = 'ACTIVE'
                    ORDER BY s.roll_number ASC
                ");
                $stmt->execute([$courseId, $date, $courseId, $courseId]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT s.id as student_id, s.name, s.roll_number, s.registration_number, NULL as attendance_status
                    FROM students s
                    WHERE s.semester_id = (SELECT semester_id FROM courses WHERE id = ?)
                      AND s.department_id = (SELECT department_id FROM courses WHERE id = ?)
                      AND s.status = 'ACTIVE'
                    ORDER BY s.roll_number ASC
                ");
                $stmt->execute([$courseId, $courseId]);
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching students for course {$courseId}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retrieve a list of courses that an active faculty member is assigned to teach.
     * 
     * @param int $facultyProfileId The profile ID in the faculty_profiles table.
     * @return array List of courses.
     */
    public function getFacultyCourses(int $facultyProfileId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.id, c.course_code, c.course_name, c.department_id, d.dept_name, d.dept_code, c.semester_id, s.semester_number
                FROM courses c
                JOIN course_assignments ca ON c.id = ca.course_id
                JOIN departments d ON c.department_id = d.id
                JOIN semesters s ON c.semester_id = s.id
                WHERE ca.faculty_id = ?
                ORDER BY d.dept_name ASC, s.semester_number ASC, c.course_name ASC
            ");
            $stmt->execute([$facultyProfileId]);
            $courses = $stmt->fetchAll();

            // Fallback: If no direct course_assignments, check courses in teacher's assigned departments
            if (empty($courses)) {
                $stmtFallback = $this->db->prepare("
                    SELECT c.id, c.course_code, c.course_name, c.department_id, d.dept_name, d.dept_code, c.semester_id, s.semester_number
                    FROM courses c
                    JOIN departments d ON c.department_id = d.id
                    JOIN semesters s ON c.semester_id = s.id
                    JOIN teacher_departments td ON td.department_id = c.department_id
                    WHERE td.teacher_id = ?
                    ORDER BY d.dept_name ASC, s.semester_number ASC, c.course_name ASC
                ");
                $stmtFallback->execute([$facultyProfileId]);
                $courses = $stmtFallback->fetchAll();
            }

            return $courses;
        } catch (PDOException $e) {
            error_log("DB Error fetching assigned courses for faculty {$facultyProfileId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Process batch attendance inserts or updates via a transaction block.
     * 
     * @param int $courseId The ID of the course.
     * @param string $date The lecture date (Y-m-d).
     * @param array $attendanceData Mapping of student_id => status.
     * @param int $markedByUser ID of the user submitting the attendance.
     * @return array Status indicating success boolean and corresponding message.
     */
    public function submitBatchAttendance(int $courseId, string $date, array $attendanceData, int $markedByUser): array {
        
        // Simple manual validation
        if (empty($attendanceData)) {
            return ['success' => false, 'message' => 'No attendance data provided.'];
        }

        try {
            $this->db->beginTransaction();
            
            // Prepared statement enforcing MySQL UPSERT (Insert on duplicate key update)
            // Ensures avoiding duplicate records for the same student on the same day for a specific course
            $stmt = $this->db->prepare("
                INSERT INTO attendance (student_id, course_id, date, status, marked_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    marked_by = VALUES(marked_by)
            ");

            foreach ($attendanceData as $studentId => $status) {
                // Defensive sanitisation over predefined enumeration
                $validStatus = strtoupper(trim($status));
                if (!in_array($validStatus, ['PRESENT', 'ABSENT', 'LATE'])) {
                    continue; 
                }
                
                $stmt->execute([
                    (int) $studentId, 
                    $courseId, 
                    $date, 
                    $validStatus, 
                    $markedByUser
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Attendance successfully recorded for ' . count($attendanceData) . ' students.'
            ];
            
        } catch (Exception $e) {
            // Proper failure remediation
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Batch Attendance Error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Failed to serialize attendance. System database error occurred.'
            ];
        }
    }

    /**
     * Get attendance stats for a specific course within a date range.
     * 
     * @param int $courseId The identifier of the course.
     * @param string $startDate The start date (Y-m-d).
     * @param string $endDate The end date (Y-m-d).
     * @return array Returns an array of student attendance stats.
     */
    public function getAttendanceStats(int $courseId, string $startDate, string $endDate): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.id as student_id,
                    s.name,
                    s.roll_number,
                    s.registration_number,
                    (SELECT COUNT(DISTINCT date) FROM attendance WHERE course_id = ? AND date >= ? AND date <= ?) as total_classes,
                    SUM(CASE WHEN a.status = 'PRESENT' THEN 1 ELSE 0 END) as total_present,
                    SUM(CASE WHEN a.status = 'ABSENT' THEN 1 ELSE 0 END) as total_absent,
                    SUM(CASE WHEN a.status = 'LATE' THEN 1 ELSE 0 END) as total_late
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.course_id = ? AND a.date >= ? AND a.date <= ?
                WHERE s.semester_id = (SELECT semester_id FROM courses WHERE id = ?)
                  AND s.department_id = (SELECT department_id FROM courses WHERE id = ?)
                  AND s.status = 'ACTIVE'
                GROUP BY s.id
                ORDER BY s.roll_number ASC
            ");
            
            $stmt->execute([
                $courseId, $startDate, $endDate,
                $courseId, $startDate, $endDate,
                $courseId, $courseId
            ]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching attendance stats for course {$courseId}: " . $e->getMessage());
            return [];
        }
    }
}
