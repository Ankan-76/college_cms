<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';

use Config\Database;
use PDO;
use PDOException;
use Exception;

class MaterialController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all study materials for a specific course.
     */
    public function getMaterialsByCourse(int $courseId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, f.name as faculty_name,
                       c.course_code, c.course_name, c.department_id,
                       d.dept_name, d.dept_code,
                       c.semester_id, s.semester_number
                FROM study_materials m
                JOIN teachers f ON m.faculty_id = f.id
                JOIN courses c ON m.course_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN semesters s ON c.semester_id = s.id
                WHERE m.course_id = ?
                ORDER BY m.uploaded_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error fetching materials: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get study materials with optional filtering by department, semester, and course.
     */
    public function getFilteredMaterials(array $allowedCourseIds, int $departmentId = 0, int $semesterId = 0, int $courseId = 0, int $facultyId = 0): array {
        try {
            $conditions = [];
            $params = [];

            if ($courseId > 0) {
                $conditions[] = "m.course_id = ?";
                $params[] = $courseId;
            } elseif (!empty($allowedCourseIds)) {
                $inPlaceholders = implode(',', array_fill(0, count($allowedCourseIds), '?'));
                if ($facultyId > 0) {
                    $conditions[] = "(m.course_id IN ($inPlaceholders) OR m.faculty_id = ?)";
                    $params = array_merge($params, array_map('intval', $allowedCourseIds), [$facultyId]);
                } else {
                    $conditions[] = "m.course_id IN ($inPlaceholders)";
                    $params = array_merge($params, array_map('intval', $allowedCourseIds));
                }
            } elseif ($facultyId > 0) {
                $conditions[] = "m.faculty_id = ?";
                $params[] = $facultyId;
            } else {
                return [];
            }

            if ($departmentId > 0) {
                $conditions[] = "c.department_id = ?";
                $params[] = $departmentId;
            }

            if ($semesterId > 0) {
                $conditions[] = "c.semester_id = ?";
                $params[] = $semesterId;
            }

            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

            $sql = "
                SELECT m.*, f.name as faculty_name, 
                       c.course_code, c.course_name, c.department_id, 
                       d.dept_name, d.dept_code, 
                       c.semester_id, s.semester_number
                FROM study_materials m
                JOIN teachers f ON m.faculty_id = f.id
                JOIN courses c ON m.course_id = c.id
                JOIN departments d ON c.department_id = d.id
                JOIN semesters s ON c.semester_id = s.id
                $whereClause
                ORDER BY m.uploaded_at DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error fetching filtered materials: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload a new material.
     */
    public function uploadMaterial(int $courseId, int $facultyId, string $title, array $fileData): array {
        // Validation
        if (empty($title) || empty($fileData['name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Invalid file or title provided.'];
        }

        // Configuration
        $uploadDir = __DIR__ . '/../uploads/materials/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // File info
        $fileSize = $fileData['size'];
        $fileExt = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
        if (!in_array($fileExt, $allowedExts)) {
            return ['success' => false, 'message' => 'File type not allowed.'];
        }

        // Secure file name
        $newFileName = uniqid('mat_') . '_' . time() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        $dbFilePath = 'uploads/materials/' . $newFileName;

        if (move_uploaded_file($fileData['tmp_name'], $destination)) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO study_materials (course_id, faculty_id, title, file_path, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$courseId, $facultyId, $title, $dbFilePath, $fileSize]);
                return ['success' => true, 'message' => 'Material uploaded successfully.'];
            } catch (PDOException $e) {
                // Remove file if DB insert fails
                unlink($destination);
                error_log("DB Error uploading material: " . $e->getMessage());
                return ['success' => false, 'message' => 'Database error during upload.'];
            }
        }

        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }

    /**
     * Delete a material.
     */
    public function deleteMaterial(int $materialId, int $facultyId): array {
        try {
            // First check if it belongs to this faculty (or if user has rights)
            $stmt = $this->db->prepare("SELECT file_path FROM study_materials WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$materialId, $facultyId]);
            $material = $stmt->fetch();

            if (!$material) {
                return ['success' => false, 'message' => 'Material not found or you do not have permission to delete it.'];
            }

            // Delete from DB
            $delStmt = $this->db->prepare("DELETE FROM study_materials WHERE id = ? AND faculty_id = ?");
            $delStmt->execute([$materialId, $facultyId]);

            // Delete file
            $absolutePath = __DIR__ . '/../' . $material['file_path'];
            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }

            return ['success' => true, 'message' => 'Material deleted successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error deleting material: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error during deletion.'];
        }
    }

    /**
     * Update an existing study material.
     */
    public function updateMaterial(int $materialId, int $facultyId, int $courseId, string $title, ?array $newFile = null): array {
        if ($materialId <= 0 || empty($title) || $courseId <= 0) {
            return ['success' => false, 'message' => 'Please provide valid course and title.'];
        }

        try {
            // Check ownership
            $stmt = $this->db->prepare("SELECT file_path, file_size FROM study_materials WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$materialId, $facultyId]);
            $existing = $stmt->fetch();

            if (!$existing) {
                return ['success' => false, 'message' => 'Study material not found or you do not have permission to edit it.'];
            }

            $filePath = $existing['file_path'];
            $fileSize = (int)$existing['file_size'];

            // Handle optional new file replacement
            if ($newFile && !empty($newFile['name']) && $newFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/materials/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExt = strtolower(pathinfo($newFile['name'], PATHINFO_EXTENSION));
                $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
                if (!in_array($fileExt, $allowedExts)) {
                    return ['success' => false, 'message' => 'File type not allowed. Allowed formats: PDF, DOC, DOCX, PPT, PPTX, TXT, ZIP, RAR.'];
                }

                $newFileName = uniqid('mat_') . '_' . time() . '.' . $fileExt;
                $destination = $uploadDir . $newFileName;
                $newDbFilePath = 'uploads/materials/' . $newFileName;

                if (move_uploaded_file($newFile['tmp_name'], $destination)) {
                    // Delete old file if exists
                    $oldAbsolutePath = __DIR__ . '/../' . $existing['file_path'];
                    if (file_exists($oldAbsolutePath)) {
                        unlink($oldAbsolutePath);
                    }
                    $filePath = $newDbFilePath;
                    $fileSize = (int)$newFile['size'];
                } else {
                    return ['success' => false, 'message' => 'Failed to upload replacement file.'];
                }
            }

            // Update database record
            $updateStmt = $this->db->prepare("
                UPDATE study_materials 
                SET course_id = ?, title = ?, file_path = ?, file_size = ?
                WHERE id = ? AND faculty_id = ?
            ");
            $updateStmt->execute([$courseId, $title, $filePath, $fileSize, $materialId, $facultyId]);

            return ['success' => true, 'message' => 'Study material updated successfully.'];
        } catch (PDOException $e) {
            error_log("DB Error updating material: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error while updating material.'];
        }
    }
}
