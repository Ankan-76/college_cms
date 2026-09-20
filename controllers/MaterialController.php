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
                SELECT m.*, f.name as faculty_name 
                FROM study_materials m
                JOIN faculty_profiles f ON m.faculty_id = f.id
                WHERE m.course_id = ?
                ORDER BY m.uploaded_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB Error fetching materials: " . $e->getMessage());
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
}
