<?php
// views/faculty/quizzes.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Online Quizzes | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';
require_once __DIR__ . '/../../controllers/QuizController.php';

use Controllers\AttendanceController;
use Controllers\QuizController;

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

// Fallback to database if empty
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

// If a specific course_id was passed, infer department and semester if not given
if ($selectedCourseId > 0) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $selectedCourseId) {
            if ($selectedDepartmentId === 0) $selectedDepartmentId = (int)$c['department_id'];
            if ($selectedSemesterId === 0) $selectedSemesterId = (int)$c['semester_id'];
            break;
        }
    }
}

// Fetch quizzes (by default all faculty quizzes in ACTIVE > UPCOMING > DRAFT > CLOSED order)
$quizCtrl = new QuizController();
$quizzes = $quizCtrl->getFilteredFacultyQuizzes($facultyId, $selectedDepartmentId, $selectedSemesterId, $selectedCourseId);

// Summary counts
$totalQuizzes = count($quizzes);
$activeCount = count(array_filter($quizzes, fn($q) => $q['display_state'] === 'ACTIVE'));
$upcomingCount = count(array_filter($quizzes, fn($q) => $q['display_state'] === 'UPCOMING'));
$draftCount = count(array_filter($quizzes, fn($q) => $q['display_state'] === 'DRAFT'));
$closedCount = count(array_filter($quizzes, fn($q) => $q['display_state'] === 'CLOSED'));

$isFiltered = ($selectedDepartmentId > 0 || $selectedSemesterId > 0 || $selectedCourseId > 0);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Page Header & Action -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="brain" class="w-6 h-6 text-purple-500"></i> Online Quizzes
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Manage online MCQ quizzes with automated evaluation, custom duration, and timed releases.
                </p>
            </div>
            
            <button type="button" onclick="openCreateQuizModal()" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-purple-500/20 hover:-translate-y-0.5 shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i> New Quiz
            </button>
        </div>

        <!-- Status Summary Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Quizzes</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white mt-0.5"><?= $totalQuizzes ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <i data-lucide="brain" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active / Live</p>
                    </div>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5"><?= $activeCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="play" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Upcoming</p>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5"><?= $upcomingCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Drafts</p>
                    <p class="text-2xl font-black text-slate-700 dark:text-slate-300 mt-0.5"><?= $draftCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 flex items-center justify-center">
                    <i data-lucide="file-edit" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-3.5 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Closed</p>
                    <p class="text-2xl font-black text-slate-600 dark:text-slate-400 mt-0.5"><?= $closedCount ?></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <!-- 3-Tier Filter Bar: Department -> Semester -> Subject -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between gap-4 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Filter Quizzes</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Choose department, semester, and subject to filter</p>
                    </div>
                </div>
                <?php if ($isFiltered): ?>
                    <a href="quizzes.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <form method="GET" id="filter-form" action="quizzes.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-end">
                <!-- 1. Department -->
                <div class="lg:col-span-4">
                    <label for="filter-department" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Department
                    </label>
                    <select name="department_id" id="filter-department" onchange="onFilterDepartmentChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $selectedDepartmentId === (int)$dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Semester -->
                <div class="lg:col-span-3">
                    <label for="filter-semester" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Semester
                    </label>
                    <select name="semester_id" id="filter-semester" onchange="onFilterSemesterChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Semesters --</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= $sem['id'] ?>" <?= $selectedSemesterId === (int)$sem['id'] ? 'selected' : '' ?>>
                                Semester <?= htmlspecialchars((string)$sem['semester_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Subject / Course -->
                <div class="lg:col-span-3">
                    <label for="filter-course" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Subject / Course
                    </label>
                    <select name="course_id" id="filter-course" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Subjects --</option>
                        <?php foreach ($courses as $c): 
                            $deptMatches = ($selectedDepartmentId === 0 || (int)$c['department_id'] === $selectedDepartmentId);
                            $semMatches = ($selectedSemesterId === 0 || (int)$c['semester_id'] === $selectedSemesterId);
                            if (!$deptMatches || !$semMatches) continue;
                        ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?> (Sem <?= htmlspecialchars((string)$c['semester_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 4. Action Buttons: Filter & Reset -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" id="btn-apply-filter" class="flex-1 inline-flex items-center justify-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white px-3.5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-purple-500/20 hover:-translate-y-0.5">
                        <i data-lucide="filter" class="w-4 h-4"></i> Filter
                    </button>
                    <a href="quizzes.php" id="btn-reset-filter" class="inline-flex items-center justify-center gap-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-3.5 py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm" title="Reset all filters to default">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Quizzes List (Ordered: Active > Upcoming > Draft > Closed) -->
        <?php if (empty($quizzes)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-900/30 text-purple-500 mb-4 shadow-inner">
                    <i data-lucide="brain" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">No Quizzes Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">
                    <?= $isFiltered ? 'No quizzes match your selected filter criteria. Try clearing the filters.' : 'You have not created any quizzes yet. Click "New Quiz" to build your first MCQ test.' ?>
                </p>
                <div class="mt-5 flex items-center justify-center gap-3">
                    <?php if ($isFiltered): ?>
                        <a href="quizzes.php" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition-colors">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset Filters
                        </a>
                    <?php endif; ?>
                    <button type="button" onclick="openCreateQuizModal()" class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Create Quiz
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($quizzes as $quiz):
                    $state = $quiz['display_state'];
                    $now = time();
                    $hasStartTime = !empty($quiz['start_time']) && $quiz['start_time'] !== '0000-00-00 00:00:00';
                    $hasEndTime = !empty($quiz['end_time']) && $quiz['end_time'] !== '0000-00-00 00:00:00';
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-all">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1 min-w-0">
                            <!-- Icon / State Avatar -->
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-2xl hidden sm:flex items-center justify-center shrink-0">
                                <i data-lucide="brain" class="w-6 h-6"></i>
                            </div>

                            <div class="flex-1 min-w-0 space-y-1.5">
                                <!-- Top Badges: Course + State -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Course & Dept Pill -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60 shrink-0">
                                        <?= htmlspecialchars($quiz['course_code']) ?> &bull; <?= htmlspecialchars($quiz['dept_code']) ?> Sem <?= htmlspecialchars((string)$quiz['semester_number']) ?>
                                    </span>

                                    <!-- Display State Pill -->
                                    <?php if ($state === 'ACTIVE'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Active / Live
                                        </span>
                                    <?php elseif ($state === 'UPCOMING'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                            <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i> Upcoming
                                        </span>
                                    <?php elseif ($state === 'DRAFT'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                            <i data-lucide="file-edit" class="w-3 h-3 text-slate-500"></i> Draft
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                            <i data-lucide="lock" class="w-3 h-3"></i> Closed
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Title & Subject Name -->
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white truncate">
                                        <?= htmlspecialchars($quiz['title']) ?>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Subject: <strong class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($quiz['course_name']) ?></strong>
                                        <?php if (!empty($quiz['description'])): ?>
                                            &bull; <?= htmlspecialchars($quiz['description']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <!-- Metadata Strip -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400 pt-0.5">
                                    <span class="flex items-center gap-1"><i data-lucide="help-circle" class="w-3.5 h-3.5 text-slate-400"></i> <?= $quiz['question_count'] ?> questions</span>
                                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i> <?= $quiz['duration_minutes'] ?> min</span>
                                    <span class="flex items-center gap-1"><i data-lucide="star" class="w-3.5 h-3.5 text-slate-400"></i> <?= $quiz['total_marks'] ?> marks</span>
                                    <span class="flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i> <?= $quiz['attempt_count'] ?> attempts</span>
                                    <?php if ($hasStartTime || $hasEndTime): ?>
                                        <span class="flex items-center gap-1 text-slate-600 dark:text-slate-300 font-medium">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-purple-500"></i>
                                            <?= $hasStartTime ? date('M d, h:i A', strtotime($quiz['start_time'])) : 'Open' ?> — <?= $hasEndTime ? date('M d, h:i A', strtotime($quiz['end_time'])) : 'Open' ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions Strip -->
                        <div class="flex items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-700/60 justify-end shrink-0">
                            <!-- Status Toggle (Publish / Close / Reopen) -->
                            <?php if ($quiz['status'] === 'DRAFT'): ?>
                                <form method="POST" action="<?= $base ?>/controllers/process_quiz.php" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                    <input type="hidden" name="status" value="PUBLISHED">
                                    <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                                    <button type="submit" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-400 px-3 py-1.5 rounded-lg font-bold transition-colors" onclick="return confirm('Publish this quiz? Students will be able to take it during the scheduled window.')">
                                        Publish
                                    </button>
                                </form>
                            <?php elseif ($quiz['status'] === 'PUBLISHED'): ?>
                                <form method="POST" action="<?= $base ?>/controllers/process_quiz.php" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                    <input type="hidden" name="status" value="CLOSED">
                                    <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                                    <button type="submit" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-400 px-3 py-1.5 rounded-lg font-bold transition-colors" onclick="return confirm('Close this quiz? Students will no longer be able to take new attempts, but attempted results will remain visible.')">
                                        Close
                                    </button>
                                </form>
                            <?php elseif ($quiz['status'] === 'CLOSED'): ?>
                                <form method="POST" action="<?= $base ?>/controllers/process_quiz.php" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                    <input type="hidden" name="status" value="PUBLISHED">
                                    <input type="hidden" name="course_id" value="<?= $quiz['course_id'] ?>">
                                    <button type="submit" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-400 px-3 py-1.5 rounded-lg font-bold transition-colors" onclick="return confirm('Reopen this quiz for students?')">
                                        Reopen
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <!-- Manage Questions -->
                            <a href="<?= $base ?>/views/faculty/manage_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded-lg font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Manage Questions
                            </a>
                            
                            <!-- Results (if attempts exist) -->
                            <?php if ($quiz['attempt_count'] > 0): ?>
                                <a href="<?= $base ?>/views/faculty/quiz_results.php?quiz_id=<?= $quiz['id'] ?>" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 px-3 py-1.5 rounded-lg font-bold transition-colors">
                                    Results
                                </a>
                            <?php endif; ?>
                            
                            <!-- Delete -->
                            <a href="<?= $base ?>/controllers/process_quiz.php?action=delete_quiz&id=<?= $quiz['id'] ?>&course_id=<?= $quiz['course_id'] ?>" onclick="return confirm('Delete this quiz and all its question and attempt records permanently?')" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete Quiz">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Create Quiz Modal with Department & Semester Chaining -->
    <div id="create-quiz-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <i data-lucide="brain" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Create New Quiz</h2>
                </div>
                <button type="button" onclick="closeCreateQuizModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form action="<?= $base ?>/controllers/process_quiz.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_quiz">
                
                <!-- Department & Semester Chaining -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="modal-dept-select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Department
                        </label>
                        <select id="modal-dept-select" onchange="filterModalCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none">
                            <option value="">-- All Departments --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $selectedDepartmentId === (int)$dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="modal-sem-select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Semester
                        </label>
                        <select id="modal-sem-select" onchange="filterModalCourses()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none">
                            <option value="">-- All Semesters --</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>" <?= $selectedSemesterId === (int)$sem['id'] ? 'selected' : '' ?>>
                                    Semester <?= htmlspecialchars((string)$sem['semester_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Subject / Course (Required) -->
                <div>
                    <label for="modal-course-select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Subject / Course <span class="text-rose-500">*</span>
                    </label>
                    <select name="course_id" id="modal-course-select" required class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none">
                        <option value="">-- Select Subject / Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id'] ?>" data-dept="<?= $c['department_id'] ?>" data-sem="<?= $c['semester_id'] ?>" <?= $selectedCourseId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?> (Sem <?= htmlspecialchars((string)$c['semester_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quiz Title -->
                <div>
                    <label for="modal-title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Quiz Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="modal-title" required placeholder="e.g. Unit 1 MCQ Assessment" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none">
                </div>

                <!-- Description -->
                <div>
                    <label for="modal-description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Description (Optional)
                    </label>
                    <textarea name="description" id="modal-description" rows="2" placeholder="Instructions for students taking this quiz..." class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none"></textarea>
                </div>

                <!-- Duration -->
                <div>
                    <label for="modal-duration" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Duration (Minutes) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="duration_minutes" id="modal-duration" required min="5" max="300" value="30" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-sm outline-none pr-12">
                        <span class="absolute right-3.5 top-2.5 text-xs text-slate-400 font-semibold">min</span>
                    </div>
                </div>

                <!-- Schedule Window -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="modal-start-time" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Available From (Optional)
                        </label>
                        <input type="datetime-local" name="start_time" id="modal-start-time" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-xs outline-none">
                    </div>
                    <div>
                        <label for="modal-end-time" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Available Until (Optional)
                        </label>
                        <input type="datetime-local" name="end_time" id="modal-end-time" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500 p-2.5 text-xs outline-none">
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 flex items-center gap-3 border-t border-slate-100 dark:border-slate-700/60">
                    <button type="button" onclick="closeCreateQuizModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl font-bold transition-all shadow-md shadow-purple-500/25 hover:-translate-y-0.5 text-sm">
                        Create & Add Questions
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const facultyCourses = <?= json_encode(array_values($courses)) ?>;
    const allDepartments = <?= json_encode(array_values($departments)) ?>;
    const allSemesters = <?= json_encode(array_values($semesters)) ?>;

    // --- Filter Bar Cascading ---
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
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
            if (parseInt(c.id) === preselectedCourseId) opt.selected = true;
            selectEl.appendChild(opt);
        });
    }

    // --- Create Quiz Modal Chaining ---
    function openCreateQuizModal() {
        const filterDept = document.getElementById('filter-department')?.value || '';
        const filterSem = document.getElementById('filter-semester')?.value || '';
        const filterCourse = document.getElementById('filter-course')?.value || '';

        const modalDept = document.getElementById('modal-dept-select');
        const modalSem = document.getElementById('modal-sem-select');

        if (modalDept && filterDept) modalDept.value = filterDept;
        if (modalSem && filterSem) modalSem.value = filterSem;

        filterModalCourses(filterCourse ? parseInt(filterCourse) : 0);

        document.getElementById('create-quiz-modal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeCreateQuizModal() {
        document.getElementById('create-quiz-modal').classList.add('hidden');
    }

    function filterModalCourses(preselectedCourseId = 0) {
        const deptId = parseInt(document.getElementById('modal-dept-select')?.value) || 0;
        const semId = parseInt(document.getElementById('modal-sem-select')?.value) || 0;
        const courseSelect = document.getElementById('modal-course-select');
        if (!courseSelect) return;

        let filtered = facultyCourses;
        if (deptId > 0) {
            filtered = filtered.filter(c => parseInt(c.department_id) === deptId);
        }
        if (semId > 0) {
            filtered = filtered.filter(c => parseInt(c.semester_id) === semId);
        }

        courseSelect.innerHTML = '<option value="">-- Select Subject / Course --</option>';
        if (filtered.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No courses found for selected criteria';
            opt.disabled = true;
            courseSelect.appendChild(opt);
            return;
        }

        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
            if (preselectedCourseId && parseInt(c.id) === preselectedCourseId) {
                opt.selected = true;
            }
            courseSelect.appendChild(opt);
        });

        // Auto-select if only 1 course matches
        if (filtered.length === 1 && !preselectedCourseId) {
            courseSelect.value = filtered[0].id;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
