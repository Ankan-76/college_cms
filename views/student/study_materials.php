<?php
// views/student/study_materials.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Study Materials | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['student_profile_id'] ?? $_SESSION['user_id'] ?? 0;

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Fetch all courses for student's department and semester for the subject filter
$coursesStmt = $db->prepare("
    SELECT id, course_code, course_name 
    FROM courses 
    WHERE department_id = ? AND semester_id = ? 
    ORDER BY course_code ASC
");
$coursesStmt->execute([$departmentId, $semesterId]);
$courses = $coursesStmt->fetchAll();

// Subject filter: default to 0 (shows all study materials)
$selectedCourseId = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : 0;
$selectedCourse = null;
if ($selectedCourseId > 0) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $selectedCourseId) {
            $selectedCourse = $c;
            break;
        }
    }
    if (!$selectedCourse) {
        $selectedCourseId = 0;
    }
}

// Fetch total count of all study materials in student's semester
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    WHERE c.department_id = ? AND c.semester_id = ?
");
$countStmt->execute([$departmentId, $semesterId]);
$totalMaterialsCount = (int)$countStmt->fetchColumn();

// Fetch study materials (optionally filtered by selected subject)
$query = "
    SELECT sm.*, c.id as course_id, c.course_code, c.course_name, t.name as faculty_name
    FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    LEFT JOIN teachers t ON sm.faculty_id = t.id
    WHERE c.department_id = ? AND c.semester_id = ?
";
$params = [$departmentId, $semesterId];

if ($selectedCourseId > 0) {
    $query .= " AND c.id = ?";
    $params[] = $selectedCourseId;
}

$query .= " ORDER BY c.course_code ASC, sm.uploaded_at DESC";

$matStmt = $db->prepare($query);
$matStmt->execute($params);
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
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Download study resources uploaded by your faculty.
                    <?php if ($selectedCourseId > 0 && $selectedCourse): ?>
                        Showing <?= count($materials) ?> <?= count($materials) === 1 ? 'file' : 'files' ?> for <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($selectedCourse['course_code']) ?></strong> (<?= $totalMaterialsCount ?> total).
                    <?php else: ?>
                        <?= $totalMaterialsCount ?> total <?= $totalMaterialsCount === 1 ? 'file' : 'files' ?> available.
                    <?php endif; ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i> <?= $selectedCourse ? htmlspecialchars($selectedCourse['course_code']) : 'All Subjects' ?>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                    <i data-lucide="files" class="w-3.5 h-3.5"></i> <?= count($materials) ?> <?= count($materials) === 1 ? 'File' : 'Files' ?>
                </span>
            </div>
        </div>

        <!-- Filter Bar: Select Subject -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 transition-all">
            <form method="GET" action="study_materials.php" id="filter-form" class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="flex-1 max-w-lg">
                    <label for="filter-course" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="filter" class="w-3.5 h-3.5 text-indigo-500"></i>
                            Select Subject
                        </span>
                    </label>
                    <select name="course_id" id="filter-course" onchange="document.getElementById('filter-form').submit()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="" <?= $selectedCourseId === 0 ? 'selected' : '' ?>>-- All Subjects --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow active:scale-95">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Filter</span>
                    </button>
                    <a href="study_materials.php" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-semibold transition-all shadow-sm active:scale-95" title="Reset filter to all subjects">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>

        <?php if (empty($materials)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700/60 text-slate-400 mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Materials Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">
                    <?php if ($selectedCourse): ?>
                        No study materials have been uploaded for <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($selectedCourse['course_name']) ?></strong> yet.
                    <?php else: ?>
                        No study materials have been uploaded for your courses yet.
                    <?php endif; ?>
                </p>
                <?php if ($selectedCourseId > 0): ?>
                    <div class="mt-4">
                        <a href="study_materials.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 hover:bg-indigo-100 transition-colors">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Show All Subjects
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($groupedMaterials as $courseCode => $group): 
                $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                $colorIdx = crc32($courseCode) % count($colors);
                $color = $colors[$colorIdx];
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-r from-<?= $color ?>-50/50 to-transparent dark:from-<?= $color ?>-900/10 dark:to-transparent">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $color ?>-100 text-<?= $color ?>-800 dark:bg-<?= $color ?>-900/40 dark:text-<?= $color ?>-300 border border-<?= $color ?>-200 dark:border-<?= $color ?>-800/50 shrink-0">
                                <?= htmlspecialchars($courseCode) ?>
                            </span>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($group['course_name']) ?></h3>
                        </div>
                        <span class="text-xs text-slate-400 font-medium shrink-0"><?= count($group['items']) ?> <?= count($group['items']) === 1 ? 'file' : 'files' ?></span>
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
                    <div class="p-3.5 sm:p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                        <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center text-slate-500 dark:text-slate-400 shrink-0 mt-0.5 sm:mt-0">
                                <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate" title="<?= htmlspecialchars($mat['title']) ?>">
                                    <?= htmlspecialchars($mat['title']) ?>
                                </h4>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300"><?= $sizeFormatted ?></span>
                                    <span>·</span>
                                    <span class="font-semibold uppercase text-indigo-600 dark:text-indigo-400"><?= strtoupper($ext) ?></span>
                                    <span>·</span>
                                    <span><?= date('M d, Y', strtotime($mat['uploaded_at'])) ?></span>
                                    <?php if (!empty($mat['faculty_name'])): ?>
                                    <span>·</span>
                                    <span class="truncate max-w-[140px] sm:max-w-none">by <?= htmlspecialchars($mat['faculty_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-700/60 justify-end sm:justify-start shrink-0 w-full sm:w-auto">
                            <button type="button" 
                                onclick="viewMaterial('<?= htmlspecialchars(BASE_URL . '/' . $mat['file_path'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($mat['title'])) ?>', '<?= strtolower($ext) ?>')" 
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 sm:py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl sm:rounded-lg text-xs font-bold transition-all shadow-sm hover:shadow active:scale-95" 
                                title="View study material online without downloading">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> <span>View</span>
                            </button>
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($mat['file_path']) ?>" target="_blank" class="px-2.5 py-2 sm:py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300 rounded-xl sm:rounded-lg text-xs font-bold transition-colors inline-flex items-center justify-center shrink-0" title="Open full screen in a new tab">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($mat['file_path']) ?>" download class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 sm:py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 rounded-xl sm:rounded-lg text-xs font-bold transition-colors active:scale-95" title="Download to device">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> <span>Download</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- Material Viewer Modal -->
    <div id="material-viewer-modal" onclick="if(event.target === this) closeViewer()" class="fixed inset-0 bg-slate-900/75 backdrop-blur-md z-50 hidden flex flex-col justify-center items-center p-0 sm:p-4 md:p-6 transition-all duration-200 cursor-pointer">
        <div class="bg-white dark:bg-slate-800 rounded-none sm:rounded-2xl shadow-2xl border-0 sm:border border-slate-200 dark:border-slate-700 w-full max-w-5xl h-full sm:h-[92vh] flex flex-col overflow-hidden animate-in fade-in duration-200 cursor-default">
            <!-- Viewer Header -->
            <div class="flex items-center justify-between px-3 sm:px-5 py-3 sm:py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/90 shrink-0 gap-2">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-1">
                    <div class="p-1.5 sm:p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <i data-lucide="book-open" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="viewer-title" class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">Document Viewer</h3>
                        <p id="viewer-subtitle" class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 truncate">Viewing material online</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                    <a id="viewer-new-tab" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg text-xs font-semibold transition-colors shadow-sm" title="Open full screen in a new tab">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Open in New Tab</span>
                        <span class="sm:hidden">Open</span>
                    </a>
                    <a id="viewer-download" href="#" download class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:hover:bg-indigo-900/60 dark:text-indigo-300 rounded-lg text-xs font-semibold transition-colors" title="Download this file">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Download</span>
                    </a>
                    <button type="button" onclick="closeViewer()" class="p-1.5 sm:p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors ml-0.5" title="Close Viewer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Viewer Body -->
            <div class="flex-1 bg-slate-100 dark:bg-slate-900 relative overflow-hidden flex items-center justify-center">
                <!-- Loading Indicator -->
                <div id="viewer-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-100/90 dark:bg-slate-900/90 z-10 transition-opacity">
                    <div class="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-3 text-xs font-semibold text-slate-500 dark:text-slate-400">Loading document...</p>
                </div>

                <!-- Iframe Container for PDF, text, images -->
                <iframe id="viewer-frame" class="w-full h-full border-0 hidden" src="" onload="onViewerFrameLoaded()"></iframe>

                <!-- Fallback container for office/archive files (DOC, PPT, ZIP) -->
                <div id="viewer-fallback" class="hidden p-8 text-center max-w-md mx-auto">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="file-question" class="w-8 h-8"></i>
                    </div>
                    <h4 id="fallback-filename" class="text-base font-bold text-slate-900 dark:text-white mb-2"></h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">
                        This file format cannot be displayed directly inside the embedded viewer. You can open it in a new browser tab or download it to view on your device.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <a id="fallback-new-tab" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                            <i data-lucide="external-link" class="w-4 h-4"></i> View in New Tab
                        </a>
                        <a id="fallback-download" href="#" download class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i> Download File
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function viewMaterial(fileUrl, title, ext) {
        const modal = document.getElementById('material-viewer-modal');
        const titleEl = document.getElementById('viewer-title');
        const subtitleEl = document.getElementById('viewer-subtitle');
        const frame = document.getElementById('viewer-frame');
        const fallback = document.getElementById('viewer-fallback');
        const loading = document.getElementById('viewer-loading');
        const newTabBtn = document.getElementById('viewer-new-tab');
        const downloadBtn = document.getElementById('viewer-download');

        titleEl.textContent = title || 'Study Material';
        subtitleEl.textContent = 'Format: ' + (ext || 'Document').toUpperCase();
        newTabBtn.href = fileUrl;
        downloadBtn.href = fileUrl;

        const previewableExts = ['pdf', 'txt', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
        const canPreview = previewableExts.includes((ext || '').toLowerCase());

        if (canPreview) {
            fallback.classList.add('hidden');
            frame.classList.remove('hidden');
            loading.classList.remove('hidden');
            frame.src = fileUrl;
        } else {
            frame.classList.add('hidden');
            frame.src = '';
            loading.classList.add('hidden');
            fallback.classList.remove('hidden');
            document.getElementById('fallback-filename').textContent = title + ' (.' + ext + ')';
            document.getElementById('fallback-new-tab').href = fileUrl;
            document.getElementById('fallback-download').href = fileUrl;
        }

        modal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function onViewerFrameLoaded() {
        const loading = document.getElementById('viewer-loading');
        if (loading) loading.classList.add('hidden');
    }

    function closeViewer() {
        const modal = document.getElementById('material-viewer-modal');
        const frame = document.getElementById('viewer-frame');
        if (frame) frame.src = '';
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeViewer();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
