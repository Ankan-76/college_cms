<?php
// views/student/study_materials.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Study Materials | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Fetch all study materials grouped by course
$matStmt = $db->prepare("
    SELECT sm.*, c.course_code, c.course_name, t.name as faculty_name
    FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    LEFT JOIN teachers t ON sm.faculty_id = t.id
    WHERE c.department_id = ? AND c.semester_id = ?
    ORDER BY c.course_code ASC, sm.uploaded_at DESC
");
$matStmt->execute([$departmentId, $semesterId]);
$materials = $matStmt->fetchAll();

// Group by course
$groupedMaterials = [];
foreach ($materials as $mat) {
    $groupedMaterials[$mat['course_code']]['course_name'] = $mat['course_name'];
    $groupedMaterials[$mat['course_code']]['items'][] = $mat;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="folder-down" class="w-6 h-6 text-indigo-500"></i> Study Materials
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Download study resources uploaded by your faculty. <?= count($materials) ?> total files available.</p>
            </div>
        </div>

        <?php if (empty($materials)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Materials Available</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">No study materials have been uploaded for your courses yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedMaterials as $courseCode => $group): 
                $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                $colorIdx = crc32($courseCode) % count($colors);
                $color = $colors[$colorIdx];
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-r from-<?= $color ?>-50/50 to-transparent dark:from-<?= $color ?>-900/10 dark:to-transparent">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $color ?>-100 text-<?= $color ?>-800 dark:bg-<?= $color ?>-900/40 dark:text-<?= $color ?>-300 border border-<?= $color ?>-200 dark:border-<?= $color ?>-800/50">
                            <?= htmlspecialchars($courseCode) ?>
                        </span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($group['course_name']) ?></h3>
                        <span class="text-xs text-slate-400 font-medium ml-auto"><?= count($group['items']) ?> files</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach ($group['items'] as $mat): 
                        $ext = strtolower(pathinfo($mat['file_path'], PATHINFO_EXTENSION));
                        $iconMap = [
                            'pdf' => 'file-text',
                            'doc' => 'file-text',
                            'docx' => 'file-text',
                            'ppt' => 'presentation',
                            'pptx' => 'presentation',
                            'zip' => 'file-archive',
                            'rar' => 'file-archive',
                            'txt' => 'file-type',
                        ];
                        $icon = $iconMap[$ext] ?? 'file';
                        $sizeFormatted = $mat['file_size'] >= 1048576 
                            ? number_format($mat['file_size'] / 1048576, 2) . ' MB' 
                            : number_format($mat['file_size'] / 1024, 1) . ' KB';
                    ?>
                    <div class="flex items-center gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center text-slate-500 shrink-0">
                            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($mat['title']) ?></h4>
                            <p class="text-xs text-slate-500 font-medium mt-0.5 flex items-center gap-2">
                                <span><?= $sizeFormatted ?></span>
                                <span>·</span>
                                <span><?= strtoupper($ext) ?></span>
                                <span>·</span>
                                <span><?= date('M d, Y', strtotime($mat['uploaded_at'])) ?></span>
                                <?php if (!empty($mat['faculty_name'])): ?>
                                <span>·</span>
                                <span>by <?= htmlspecialchars($mat['faculty_name']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="<?= BASE_URL . '/' . htmlspecialchars($mat['file_path']) ?>" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 rounded-lg text-xs font-bold transition-colors shrink-0">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
