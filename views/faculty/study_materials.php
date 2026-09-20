<?php
// views/faculty/study_materials.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Study Materials | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php'; // For courses
require_once __DIR__ . '/../../controllers/MaterialController.php';

use Controllers\AttendanceController;
use Controllers\MaterialController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$attendanceCtrl = new AttendanceController();
$courses = $attendanceCtrl->getFacultyCourses($facultyId);

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses) > 0 ? $courses[0]['id'] : 0);

$materialCtrl = new MaterialController();
$materials = [];
if ($selectedCourseId > 0) {
    $materials = $materialCtrl->getMaterialsByCourse($selectedCourseId);
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-lg flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-lg flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Study Materials</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Upload and manage course resources, lecture notes, and syllabus copies.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-sm">
                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload Material
                </button>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1 max-w-sm">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Select Course</label>
                    <select name="course_id" onchange="this.form.submit()" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary p-2.5 outline-none">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Material Grid -->
        <?php if (empty($materials) && $selectedCourseId > 0): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 mx-auto mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Materials Uploaded</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">You haven't uploaded any resources for this course yet.</p>
            </div>
        <?php elseif ($selectedCourseId > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($materials as $mat): 
                    $ext = strtolower(pathinfo($mat['file_path'], PATHINFO_EXTENSION));
                    $icon = 'file-text';
                    $colorClass = 'bg-slate-50 text-slate-500 dark:bg-slate-900/30';
                    
                    if (in_array($ext, ['pdf'])) {
                        $colorClass = 'bg-red-50 text-red-500 dark:bg-red-900/30';
                    } elseif (in_array($ext, ['doc', 'docx'])) {
                        $colorClass = 'bg-blue-50 text-blue-500 dark:bg-blue-900/30';
                    } elseif (in_array($ext, ['ppt', 'pptx'])) {
                        $colorClass = 'bg-orange-50 text-orange-500 dark:bg-orange-900/30';
                        $icon = 'presentation';
                    } elseif (in_array($ext, ['zip', 'rar'])) {
                        $colorClass = 'bg-purple-50 text-purple-500 dark:bg-purple-900/30';
                        $icon = 'archive';
                    }
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-all group relative">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 rounded-lg <?= $colorClass ?>">
                            <i data-lucide="<?= $icon ?>" class="w-6 h-6"></i>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= htmlspecialchars('/college_cms/' . $mat['file_path']) ?>" download class="text-slate-400 hover:text-indigo-500 transition-colors" title="Download">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </a>
                            <?php if ($mat['faculty_id'] == $facultyId): ?>
                            <a href="../../controllers/process_material.php?action=delete&id=<?= $mat['id'] ?>" onclick="return confirm('Are you sure you want to delete this material?')" class="text-slate-400 hover:text-rose-500 transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 line-clamp-1" title="<?= htmlspecialchars($mat['title']) ?>">
                        <?= htmlspecialchars($mat['title']) ?>
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 truncate">
                        Uploaded by <?= htmlspecialchars($mat['faculty_name']) ?>
                    </p>
                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?= date('M d, Y', strtotime($mat['uploaded_at'])) ?></span>
                        <span class="font-medium bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                            <?= number_format($mat['file_size'] / 1024 / 1024, 2) ?> MB
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Upload Material</h2>
                <button onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../../controllers/process_material.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="upload">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Course</label>
                    <select name="course_id" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Title / Description</label>
                    <input type="text" name="title" required placeholder="e.g. Chapter 1 Notes" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">File</label>
                    <input type="file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <p class="mt-1 text-xs text-slate-500">Max size 10MB. Allowed: PDF, Word, PPT, ZIP.</p>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

