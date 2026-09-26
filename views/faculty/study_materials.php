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

// Build distinct departments and semesters from faculty's courses
$departments = [];
$semesters = [];
foreach ($courses as $c) {
    $deptId = (int)($c['department_id'] ?? 0);
    if ($deptId > 0 && !isset($departments[$deptId])) {
        $departments[$deptId] = [
            'id' => $deptId,
            'dept_name' => $c['dept_name'] ?? 'Department ' . $deptId,
            'dept_code' => $c['dept_code'] ?? 'DEPT'
        ];
    }
    $semId = (int)($c['semester_id'] ?? 0);
    if ($semId > 0 && !isset($semesters[$semId])) {
        $semesters[$semId] = [
            'id' => $semId,
            'semester_number' => (int)($c['semester_number'] ?? 1)
        ];
    }
}

// Fallback to database if no courses or departments found
if (empty($departments)) {
    try {
        $db = \Config\Database::getInstance()->getConnection();
        $allDeptsStmt = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name ASC");
        while ($d = $allDeptsStmt->fetch(PDO::FETCH_ASSOC)) {
            $departments[(int)$d['id']] = $d;
        }
    } catch (Exception $e) {}
}

if (empty($semesters)) {
    try {
        $db = \Config\Database::getInstance()->getConnection();
        $allSemsStmt = $db->query("SELECT id, semester_number FROM semesters ORDER BY semester_number ASC");
        while ($s = $allSemsStmt->fetch(PDO::FETCH_ASSOC)) {
            $semesters[(int)$s['id']] = $s;
        }
    } catch (Exception $e) {}
}

uasort($departments, fn($a, $b) => strcmp($a['dept_name'], $b['dept_name']));
uasort($semesters, fn($a, $b) => $a['semester_number'] <=> $b['semester_number']);

// Filter selections from query parameters
$selectedDepartmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : 0;
$selectedSemesterId = isset($_GET['semester_id']) && $_GET['semester_id'] !== '' ? (int)$_GET['semester_id'] : 0;
$selectedCourseId = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : 0;

// If a specific course_id was passed, infer its department and semester if not explicitly given
if ($selectedCourseId > 0) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $selectedCourseId) {
            if ($selectedDepartmentId === 0) {
                $selectedDepartmentId = (int)$c['department_id'];
            }
            if ($selectedSemesterId === 0) {
                $selectedSemesterId = (int)$c['semester_id'];
            }
            break;
        }
    }
}

// Find selected course metadata if a specific subject was chosen
$selectedCourse = null;
if ($selectedCourseId > 0) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $selectedCourseId) {
            $deptMatches = ($selectedDepartmentId === 0 || (int)$c['department_id'] === $selectedDepartmentId);
            $semMatches = ($selectedSemesterId === 0 || (int)$c['semester_id'] === $selectedSemesterId);
            if ($deptMatches && $semMatches) {
                $selectedCourse = $c;
            } else {
                $selectedCourseId = 0;
            }
            break;
        }
    }
}

$facultyCourseIds = array_map(fn($c) => (int)$c['id'], $courses);
$materialCtrl = new MaterialController();
$materials = $materialCtrl->getFilteredMaterials($facultyCourseIds, $selectedDepartmentId, $selectedSemesterId, $selectedCourseId, (int)$facultyId);
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
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <i data-lucide="book-open" class="w-6 h-6 text-indigo-500"></i> Study Materials
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Upload, organize, and manage course resources, lecture notes, and syllabus copies.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="openUploadModal()" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow hover:-translate-y-0.5">
                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload Material
                </button>
            </div>
        </div>

        <!-- Filter Bar: Choose Department -> Choose Semester -> Select Subject Name -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Filter Study Materials</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Choose department and semester before selecting subject</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                        <i data-lucide="building-2" class="w-3 h-3"></i> <?= htmlspecialchars($selectedDepartmentId > 0 && isset($departments[$selectedDepartmentId]) ? ($departments[$selectedDepartmentId]['dept_code'] ?? $departments[$selectedDepartmentId]['dept_name']) : 'All Departments') ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        <i data-lucide="calendar" class="w-3 h-3"></i> <?= $selectedSemesterId > 0 ? 'Sem ' . htmlspecialchars((string)($semesters[$selectedSemesterId]['semester_number'] ?? $selectedSemesterId)) : 'All Semesters' ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-violet-50 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800/60">
                        <i data-lucide="book-open" class="w-3 h-3"></i> <?= $selectedCourse ? htmlspecialchars($selectedCourse['course_code']) : 'All Subjects' ?>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                        <i data-lucide="files" class="w-3 h-3"></i> <?= count($materials) ?> <?= count($materials) === 1 ? 'Resource' : 'Resources' ?>
                    </span>
                </div>
            </div>

            <form method="GET" id="filter-form" action="study_materials.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <!-- Step 1: Choose Department -->
                <div class="lg:col-span-4">
                    <label for="filter-department" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">1</span>
                            Choose Department
                        </span>
                    </label>
                    <select name="department_id" id="filter-department" onchange="onFilterDepartmentChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $selectedDepartmentId === (int)$dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Choose Semester -->
                <div class="lg:col-span-3">
                    <label for="filter-semester" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">2</span>
                            Choose Semester
                        </span>
                    </label>
                    <select name="semester_id" id="filter-semester" onchange="onFilterSemesterChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Semesters --</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= $sem['id'] ?>" <?= $selectedSemesterId === (int)$sem['id'] ? 'selected' : '' ?>>
                                Semester <?= htmlspecialchars((string)$sem['semester_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 3: Select Subject Name -->
                <div class="lg:col-span-4">
                    <label for="filter-course" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">3</span>
                            Select Subject Name
                        </span>
                    </label>
                    <select name="course_id" id="filter-course" onchange="onFilterCourseChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="" <?= $selectedCourseId === 0 ? 'selected' : '' ?>>-- All Subjects --</option>
                        <?php foreach ($courses as $c): ?>
                            <?php 
                                $visible = true;
                                if ($selectedDepartmentId > 0 && (int)$c['department_id'] !== $selectedDepartmentId) $visible = false;
                                if ($selectedSemesterId > 0 && (int)$c['semester_id'] !== $selectedSemesterId) $visible = false;
                            ?>
                            <?php if ($visible): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?> (Sem <?= htmlspecialchars((string)$c['semester_number']) ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="lg:col-span-1 flex gap-2">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white p-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow focus:ring-2 focus:ring-indigo-500" title="Apply Filter">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span class="lg:hidden text-xs">Filter</span>
                    </button>
                    <?php if ($selectedCourseId > 0 || $selectedDepartmentId > 0 || $selectedSemesterId > 0): ?>
                    <a href="study_materials.php" class="inline-flex items-center justify-center p-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Material Grid -->
        <?php if (empty($materials)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700/60 flex items-center justify-center text-slate-400 mx-auto mb-4">
                    <i data-lucide="folder-open" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">No Materials Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                    <?php if ($selectedCourse): ?>
                        No resources or lecture notes have been published for <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($selectedCourse['course_name']) ?></strong>.
                    <?php else: ?>
                        No study materials found matching the selected filters.
                    <?php endif; ?>
                </p>
                <div class="mt-6">
                    <button type="button" onclick="openUploadModal()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow hover:-translate-y-0.5">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload Material
                    </button>
                </div>
            </div>
        <?php else: ?>
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-all group relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="p-3 rounded-lg <?= $colorClass ?>">
                                <i data-lucide="<?= $icon ?>" class="w-6 h-6"></i>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" 
                                    onclick="viewMaterial('<?= htmlspecialchars('/college_cms/' . $mat['file_path'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($mat['title'])) ?>', '<?= strtolower($ext) ?>')" 
                                    class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" 
                                    title="View Material Online">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <?php if ($mat['faculty_id'] == $facultyId): ?>
                                <button type="button" 
                                    onclick="openEditMaterialModal(<?= $mat['id'] ?>, <?= $mat['course_id'] ?>, '<?= htmlspecialchars(addslashes($mat['title'])) ?>', '<?= htmlspecialchars(addslashes(basename($mat['file_path']))) ?>')" 
                                    class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" 
                                    title="Edit Study Material">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <a href="../../controllers/process_material.php?action=delete&id=<?= $mat['id'] ?>" onclick="return confirm('Are you sure you want to delete this material?')" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Course Code, Sem, and Dept Badges -->
                        <div class="flex flex-wrap items-center gap-1.5 mb-2.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                <?= htmlspecialchars($mat['course_code'] ?? 'Course') ?>
                            </span>
                            <?php if (!empty($mat['semester_number'])): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                Sem <?= htmlspecialchars((string)$mat['semester_number']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($mat['dept_code'])): ?>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                <?= htmlspecialchars($mat['dept_code']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-1 line-clamp-1" title="<?= htmlspecialchars($mat['title']) ?>">
                            <?= htmlspecialchars($mat['title']) ?>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2 truncate" title="<?= htmlspecialchars($mat['course_name'] ?? '') ?>">
                            <?= htmlspecialchars($mat['course_name'] ?? '') ?>
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mb-3 truncate">
                            By <?= htmlspecialchars($mat['faculty_name']) ?>
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?= date('M d, Y', strtotime($mat['uploaded_at'])) ?></span>
                            <span class="font-medium bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded text-[11px]">
                                <?= number_format($mat['file_size'] / 1024 / 1024, 2) ?> MB
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <button type="button" 
                                onclick="viewMaterial('<?= htmlspecialchars('/college_cms/' . $mat['file_path'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($mat['title'])) ?>', '<?= strtolower($ext) ?>')" 
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Material
                            </button>
                            <?php if ($mat['faculty_id'] == $facultyId): ?>
                            <button type="button" 
                                onclick="openEditMaterialModal(<?= $mat['id'] ?>, <?= $mat['course_id'] ?>, '<?= htmlspecialchars(addslashes($mat['title'])) ?>', '<?= htmlspecialchars(addslashes(basename($mat['file_path']))) ?>')" 
                                class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-semibold">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit Material
                            </button>
                            <?php else: ?>
                            <a href="<?= htmlspecialchars('/college_cms/' . $mat['file_path']) ?>" download class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                            </a>
                            <?php endif; ?>
                        </div>
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
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department</label>
                        <select id="upload-department-id" onchange="filterUploadCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-xs outline-none">
                            <option value="">-- All Departments --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester</label>
                        <select id="upload-semester-id" onchange="filterUploadCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-xs outline-none">
                            <option value="">-- All Semesters --</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>">Semester <?= htmlspecialchars((string)$sem['semester_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Subject Name <span class="text-rose-500">*</span>
                    </label>
                    <select name="course_id" id="upload-course-id" required class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === (int)$course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (Sem <?= htmlspecialchars((string)$course['semester_number']) ?>)
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

    <!-- Edit Material Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full border border-slate-200 dark:border-slate-700 overflow-hidden animate-in fade-in duration-200">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/70">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Edit Study Material</h2>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../../controllers/process_material.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="material_id" id="edit-material-id" value="">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department</label>
                        <select id="edit-department-id" onchange="filterEditCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 text-xs outline-none">
                            <option value="">-- All Departments --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester</label>
                        <select id="edit-semester-id" onchange="filterEditCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 text-xs outline-none">
                            <option value="">-- All Semesters --</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>">Semester <?= htmlspecialchars((string)$sem['semester_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Subject Name <span class="text-rose-500">*</span>
                    </label>
                    <select name="course_id" id="edit-course-id" required class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 text-sm outline-none">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>">
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (Sem <?= htmlspecialchars((string)$course['semester_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Title / Topic Name</label>
                    <input type="text" name="title" id="edit-title" required placeholder="e.g. Chapter 1 Notes" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 p-2.5 text-sm outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                        Replace File <span class="text-xs font-normal text-slate-400">(optional)</span>
                    </label>
                    <div id="edit-current-file-box" class="text-xs text-slate-500 dark:text-slate-400 mb-2 truncate bg-slate-100 dark:bg-slate-700/50 px-2.5 py-1.5 rounded-lg flex items-center gap-1.5">
                        <i data-lucide="file" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <span class="truncate">Current: <strong id="edit-current-filename" class="font-semibold text-slate-700 dark:text-slate-200"></strong></span>
                    </div>
                    <input type="file" name="material_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-400">
                    <p class="mt-1 text-[11px] text-slate-400">Leave blank to keep existing file. Allowed: PDF, Word, PPT, ZIP (Max 10MB).</p>
                </div>

                <div class="pt-3 flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2.5 rounded-xl text-xs font-bold transition-colors shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
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
    const facultyCourses = <?= json_encode(array_values($courses)) ?>;
    const allDepartments = <?= json_encode(array_values($departments)) ?>;
    const allSemesters = <?= json_encode(array_values($semesters)) ?>;

    // --- Main Filter Cascading ---
    function onFilterDepartmentChange() {
        const deptSelect = document.getElementById('filter-department');
        const semSelect = document.getElementById('filter-semester');
        const courseSelect = document.getElementById('filter-course');

        const selectedDeptId = parseInt(deptSelect.value) || 0;
        const currentSemId = parseInt(semSelect.value) || 0;

        let matchingCourses = facultyCourses;
        if (selectedDeptId > 0) {
            matchingCourses = matchingCourses.filter(c => parseInt(c.department_id) === selectedDeptId);
        }

        updateSemesterSelect(semSelect, matchingCourses, currentSemId);
        const newSemId = parseInt(semSelect.value) || 0;
        updateCourseSelect(courseSelect, matchingCourses, newSemId);

        document.getElementById('filter-form').submit();
    }

    function onFilterSemesterChange() {
        const deptSelect = document.getElementById('filter-department');
        const semSelect = document.getElementById('filter-semester');
        const courseSelect = document.getElementById('filter-course');

        const selectedDeptId = parseInt(deptSelect.value) || 0;
        const selectedSemId = parseInt(semSelect.value) || 0;

        let matchingCourses = facultyCourses;
        if (selectedDeptId > 0) {
            matchingCourses = matchingCourses.filter(c => parseInt(c.department_id) === selectedDeptId);
        }

        updateCourseSelect(courseSelect, matchingCourses, selectedSemId);

        document.getElementById('filter-form').submit();
    }

    function onFilterCourseChange() {
        document.getElementById('filter-form').submit();
    }

    function updateSemesterSelect(selectEl, coursesList, preselectedSemId) {
        if (!selectEl) return;
        const availableSemIds = new Set(coursesList.map(c => parseInt(c.semester_id)));
        
        selectEl.innerHTML = '<option value="">-- All Semesters --</option>';
        allSemesters.forEach(s => {
            const semId = parseInt(s.id);
            if (coursesList.length === 0 || availableSemIds.has(semId)) {
                const opt = document.createElement('option');
                opt.value = semId;
                opt.textContent = 'Semester ' + s.semester_number;
                if (semId === preselectedSemId) opt.selected = true;
                selectEl.appendChild(opt);
            }
        });
    }

    function updateCourseSelect(selectEl, coursesList, filterSemId, preselectedCourseId = 0) {
        if (!selectEl) return;
        let filtered = coursesList;
        if (filterSemId > 0) {
            filtered = filtered.filter(c => parseInt(c.semester_id) === filterSemId);
        }

        selectEl.innerHTML = '<option value="">-- All Subjects --</option>';
        if (filtered.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No subjects available in this scope';
            opt.disabled = true;
            selectEl.appendChild(opt);
            return;
        }

        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
            if (parseInt(c.id) === preselectedCourseId) {
                opt.selected = true;
            }
            selectEl.appendChild(opt);
        });
    }

    // --- Upload Modal Helpers ---
    function openUploadModal() {
        const currentDeptId = document.getElementById('filter-department')?.value || '';
        const currentSemId = document.getElementById('filter-semester')?.value || '';
        const currentCourseId = document.getElementById('filter-course')?.value || '';

        const modalDept = document.getElementById('upload-department-id');
        if (modalDept && currentDeptId) {
            modalDept.value = currentDeptId;
        }
        filterUploadCourses(currentSemId, currentCourseId);

        document.getElementById('upload-modal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function filterUploadCourses(keepSemId = null, keepCourseId = null) {
        const deptSelect = document.getElementById('upload-department-id');
        const semSelect = document.getElementById('upload-semester-id');
        const courseSelect = document.getElementById('upload-course-id');
        if (!deptSelect || !semSelect || !courseSelect) return;

        const deptId = parseInt(deptSelect.value) || 0;
        const targetSemId = keepSemId !== null ? parseInt(keepSemId) : (parseInt(semSelect.value) || 0);
        const targetCourseId = keepCourseId !== null ? parseInt(keepCourseId) : (parseInt(courseSelect.value) || 0);

        let matching = facultyCourses;
        if (deptId > 0) {
            matching = matching.filter(c => parseInt(c.department_id) === deptId);
        }

        updateSemesterSelect(semSelect, matching, targetSemId);
        const activeSemId = parseInt(semSelect.value) || 0;

        let finalCourses = matching;
        if (activeSemId > 0) {
            finalCourses = finalCourses.filter(c => parseInt(c.semester_id) === activeSemId);
        }

        courseSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        if (finalCourses.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No courses assigned in this scope';
            opt.disabled = true;
            courseSelect.appendChild(opt);
        } else {
            finalCourses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
                if (parseInt(c.id) === targetCourseId || finalCourses.length === 1) {
                    opt.selected = true;
                }
                courseSelect.appendChild(opt);
            });
        }
    }

    // --- Edit Modal Helpers ---
    function openEditMaterialModal(id, courseId, title, currentFilename) {
        document.getElementById('edit-material-id').value = id;
        document.getElementById('edit-title').value = title;
        const currentFileEl = document.getElementById('edit-current-filename');
        if (currentFileEl) {
            currentFileEl.textContent = currentFilename || 'attached file';
        }

        const course = facultyCourses.find(c => parseInt(c.id) === parseInt(courseId));
        const deptSelect = document.getElementById('edit-department-id');
        const semSelect = document.getElementById('edit-semester-id');
        const courseSelect = document.getElementById('edit-course-id');

        if (deptSelect && course) {
            deptSelect.value = course.department_id;
            filterEditCourses(course.semester_id, course.id);
        } else if (courseSelect) {
            courseSelect.value = courseId;
        }

        document.getElementById('edit-modal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function filterEditCourses(keepSemId = null, keepCourseId = null) {
        const deptSelect = document.getElementById('edit-department-id');
        const semSelect = document.getElementById('edit-semester-id');
        const courseSelect = document.getElementById('edit-course-id');
        if (!deptSelect || !semSelect || !courseSelect) return;

        const deptId = parseInt(deptSelect.value) || 0;
        const targetSemId = keepSemId !== null ? parseInt(keepSemId) : (parseInt(semSelect.value) || 0);
        const targetCourseId = keepCourseId !== null ? parseInt(keepCourseId) : (parseInt(courseSelect.value) || 0);

        let matching = facultyCourses;
        if (deptId > 0) {
            matching = matching.filter(c => parseInt(c.department_id) === deptId);
        }

        updateSemesterSelect(semSelect, matching, targetSemId);
        const activeSemId = parseInt(semSelect.value) || 0;

        let finalCourses = matching;
        if (activeSemId > 0) {
            finalCourses = finalCourses.filter(c => parseInt(c.semester_id) === activeSemId);
        }

        courseSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        if (finalCourses.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No courses assigned in this scope';
            opt.disabled = true;
            courseSelect.appendChild(opt);
        } else {
            finalCourses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
                if (parseInt(c.id) === targetCourseId) {
                    opt.selected = true;
                }
                courseSelect.appendChild(opt);
            });
        }
    }

    function closeEditModal() {
        const editModal = document.getElementById('edit-modal');
        if (editModal) editModal.classList.add('hidden');
    }

    // --- Viewer Modal ---
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
            closeEditModal();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

