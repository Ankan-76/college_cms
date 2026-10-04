<?php
// views/faculty/take_attendance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Take Attendance | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

use Controllers\AttendanceController;

$controller = new AttendanceController();
$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$courses = $controller->getFacultyCourses($facultyId);

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

// Filters
$selectedDepartmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : 0;
$selectedSemesterId = isset($_GET['semester_id']) && $_GET['semester_id'] !== '' ? (int)$_GET['semester_id'] : 0;
$selectedCourseId = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null;
$selectedDate = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d');

// If a specific course_id was passed, infer department and semester if not given
if ($selectedCourseId) {
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

// Find selected course metadata if a specific course was chosen
$selectedCourse = null;
if ($selectedCourseId) {
    foreach ($courses as $c) {
        if ((int)$c['id'] === $selectedCourseId) {
            $selectedCourse = $c;
            break;
        }
    }
}

$students = [];
if ($selectedCourseId) {
    $students = $controller->getStudentsForCourse($selectedCourseId, $selectedDate);
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Smart Attendance Ledger</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage, track, and record daily student attendance.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <a href="view_attendance.php<?= $selectedCourseId ? '?course_id=' . $selectedCourseId . ($selectedDepartmentId ? '&department_id=' . $selectedDepartmentId : '') . ($selectedSemesterId ? '&semester_id=' . $selectedSemesterId : '') : '' ?>" class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-2 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i data-lucide="eye" class="w-4 h-4"></i> View Attendance
                </a>
            </div>
        </div>

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

        <!-- Filter Card: Step 1: Department -> Step 2: Semester -> Step 3: Subject -> Step 4: Lecture Date -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 transform transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Attendance Roster Setup</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Filter department and semester before selecting subject</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($selectedDepartmentId > 0 && isset($departments[$selectedDepartmentId])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                        <i data-lucide="building-2" class="w-3 h-3"></i> <?= htmlspecialchars($departments[$selectedDepartmentId]['dept_code'] ?? $departments[$selectedDepartmentId]['dept_name']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($selectedSemesterId > 0): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                        <i data-lucide="calendar" class="w-3 h-3"></i> Sem <?= htmlspecialchars((string)($semesters[$selectedSemesterId]['semester_number'] ?? $selectedSemesterId)) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($selectedCourse): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                        <i data-lucide="book-open" class="w-3 h-3"></i> <?= htmlspecialchars($selectedCourse['course_code']) ?>
                    </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60">
                        <i data-lucide="calendar-check" class="w-3 h-3"></i> <?= date('M d, Y', strtotime($selectedDate)) ?>
                    </span>
                </div>
            </div>

            <form method="GET" action="take_attendance.php" id="attendance-filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                <!-- Step 1: Department -->
                <div class="lg:col-span-3">
                    <label for="filter-department" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">1</span>
                            Department
                        </span>
                    </label>
                    <select id="filter-department" name="department_id" onchange="onFilterDepartmentChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $selectedDepartmentId === (int)$dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Semester -->
                <div class="lg:col-span-2">
                    <label for="filter-semester" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">2</span>
                            Semester
                        </span>
                    </label>
                    <select id="filter-semester" name="semester_id" onchange="onFilterSemesterChange()" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- All Semesters --</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= $sem['id'] ?>" <?= $selectedSemesterId === (int)$sem['id'] ? 'selected' : '' ?>>
                                Semester <?= htmlspecialchars((string)$sem['semester_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 3: Subject -->
                <div class="lg:col-span-3">
                    <label for="course_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-extrabold">3</span>
                            Subject
                        </span>
                    </label>
                    <select id="course_id" name="course_id" required class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                        <option value="">-- Select a subject --</option>
                        <?php foreach ($courses as $course): ?>
                            <?php 
                                $deptMatch = ($selectedDepartmentId === 0 || (int)$course['department_id'] === $selectedDepartmentId);
                                $semMatch = ($selectedSemesterId === 0 || (int)$course['semester_id'] === $selectedSemesterId);
                                if ($deptMatch && $semMatch):
                            ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === (int)$course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (Sem <?= htmlspecialchars((string)$course['semester_number']) ?>)
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 4: Lecture Date -->
                <div class="lg:col-span-2">
                    <label for="date" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                            Lecture Date
                        </span>
                    </label>
                    <input type="date" id="date" name="date" value="<?= $selectedDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                </div>

                <!-- Action Buttons: Load Roster & Reset -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-3 rounded-xl text-sm transition-all hover:shadow hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center justify-center gap-1.5 shrink-0">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Load Roster</span>
                    </button>
                    <a href="take_attendance.php" title="Reset Filters" class="p-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl transition-colors shrink-0 flex items-center justify-center">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Attendance Grid Results -->
        <?php if ($selectedCourseId !== null): ?>
            <?php if (empty($students)): ?>
                <!-- Empty State -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                        <i data-lucide="users" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No students enrolled</h3>
                    <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">There are currently no students mapped to this course and semester combination.</p>
                </div>
            <?php else: ?>
                <!-- Roster Data -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 opacity-100">
                    
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-indigo-500"></i>
                            Mark Attendance - <span class="text-indigo-600 dark:text-indigo-400"><?= date('M d, Y', strtotime($selectedDate)) ?></span>
                        </h2>
                        <div class="flex flex-wrap items-center gap-2 bg-white dark:bg-slate-700 p-1.5 rounded-xl border border-slate-200 dark:border-slate-600 shadow-sm">
                            <button type="button" onclick="markBulk('PRESENT')" class="text-xs sm:text-sm text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 font-semibold px-2.5 py-1.5 rounded-lg transition-all outline-none flex items-center gap-1.5" title="Mark all students Present (1.0 credit)">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i> All Present
                            </button>
                            <div class="w-px h-4 bg-slate-200 dark:bg-slate-600"></div>
                            <button type="button" onclick="markBulk('LATE')" class="text-xs sm:text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/40 font-semibold px-2.5 py-1.5 rounded-lg transition-all outline-none flex items-center gap-1.5" title="Mark all students Late (0.5 credit)">
                                <i data-lucide="clock" class="w-4 h-4"></i> All Late
                            </button>
                            <div class="w-px h-4 bg-slate-200 dark:bg-slate-600"></div>
                            <button type="button" onclick="markBulk('ABSENT')" class="text-xs sm:text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/40 font-semibold px-2.5 py-1.5 rounded-lg transition-all outline-none flex items-center gap-1.5" title="Mark all students Absent (0.0 credit)">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> All Absent
                            </button>
                            <div class="w-px h-4 bg-slate-200 dark:bg-slate-600"></div>
                            <button type="button" onclick="resetAttendance()" class="text-xs sm:text-sm text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-600/50 font-medium px-2.5 py-1.5 rounded-lg transition-all outline-none flex items-center gap-1.5" title="Reset all attendance selections">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset
                            </button>
                        </div>
                    </div>

                    <form id="attendance-form" action="../../controllers/process_attendance.php" method="POST">
                        <input type="hidden" name="csrf_token" value="dummy_csrf_token_for_demo">
                        <input type="hidden" name="course_id" value="<?= htmlspecialchars((string)$selectedCourseId) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                        <input type="hidden" name="department_id" value="<?= htmlspecialchars((string)$selectedDepartmentId) ?>">
                        <input type="hidden" name="semester_id" value="<?= htmlspecialchars((string)$selectedSemesterId) ?>">
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-white dark:bg-slate-800/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Roll No</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student Profile</th>
                                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-64">
                                            <span class="block">Attendance Status</span>
                                            <span class="inline-flex items-center gap-2.5 text-[10px] font-semibold text-slate-400 lowercase tracking-normal mt-0.5">
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase">P: 1.0</span> &bull; 
                                                <span class="text-amber-600 dark:text-amber-400 font-bold uppercase">L: 0.5</span> &bull; 
                                                <span class="text-rose-600 dark:text-rose-400 font-bold uppercase">A: 0.0</span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700/50">
                                    <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors duration-150 group">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-slate-300">
                                            <?= htmlspecialchars($student['roll_number']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-9 w-9">
                                                    <img class="h-9 w-9 rounded-full border border-slate-200 dark:border-slate-600 shadow-sm" src="https://ui-avatars.com/api/?name=<?= urlencode($student['name']) ?>&background=random&color=fff&bold=true" alt="Student">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($student['name']) ?></div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Reg: <?= htmlspecialchars($student['registration_number']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex justify-center items-center gap-5">
                                                <!-- Present Radio Option -->
                                                <label class="flex flex-col items-center cursor-pointer group/present hover:-translate-y-0.5 transition-transform" title="Mark Present (1.0 Credit)">
                                                    <input type="radio" name="attendance[<?= $student['student_id'] ?>]" value="PRESENT" class="peer status-radio sr-only" required <?= (isset($student['attendance_status']) && $student['attendance_status'] === 'PRESENT') ? 'checked' : '' ?>>
                                                    <div class="w-9 h-9 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-600 dark:peer-checked:bg-emerald-500/20 dark:peer-checked:border-emerald-400 group-hover/present:border-emerald-400">
                                                        <i data-lucide="check" class="w-4 h-4 opacity-0 transition-all peer-checked:opacity-100 peer-checked:scale-110 rounded-indicator"></i>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-slate-400 group-hover/present:text-emerald-600 dark:group-hover/present:text-emerald-400 mt-1 uppercase">P</span>
                                                </label>
                                                
                                                <!-- Late Radio Option -->
                                                <label class="flex flex-col items-center cursor-pointer group/late hover:-translate-y-0.5 transition-transform" title="Mark Late (0.5 Credit)">
                                                    <input type="radio" name="attendance[<?= $student['student_id'] ?>]" value="LATE" class="peer status-radio sr-only" required <?= (isset($student['attendance_status']) && $student['attendance_status'] === 'LATE') ? 'checked' : '' ?>>
                                                    <div class="w-9 h-9 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-600 dark:peer-checked:bg-amber-500/20 dark:peer-checked:border-amber-400 group-hover/late:border-amber-400">
                                                        <i data-lucide="clock" class="w-4 h-4 opacity-0 transition-all peer-checked:opacity-100 peer-checked:scale-110 rounded-indicator"></i>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-slate-400 group-hover/late:text-amber-600 dark:group-hover/late:text-amber-400 mt-1 uppercase">L</span>
                                                </label>
                                                
                                                <!-- Absent Radio Option -->
                                                <label class="flex flex-col items-center cursor-pointer group/absent hover:-translate-y-0.5 transition-transform" title="Mark Absent (0.0 Credit)">
                                                    <input type="radio" name="attendance[<?= $student['student_id'] ?>]" value="ABSENT" class="peer status-radio sr-only" required <?= (isset($student['attendance_status']) && $student['attendance_status'] === 'ABSENT') ? 'checked' : '' ?>>
                                                    <div class="w-9 h-9 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-600 dark:peer-checked:bg-rose-500/20 dark:peer-checked:border-rose-400 group-hover/absent:border-rose-400">
                                                        <i data-lucide="x" class="w-4 h-4 opacity-0 transition-all peer-checked:opacity-100 peer-checked:scale-110 rounded-indicator"></i>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-slate-400 group-hover/absent:text-rose-600 dark:group-hover/absent:text-rose-400 mt-1 uppercase">A</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                            <button type="submit" id="save-btn" class="bg-primary hover:bg-indigo-700 text-white font-semibold py-2.5 px-8 rounded-lg transform transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900 flex items-center gap-2">
                                <i data-lucide="save" class="w-5 h-5"></i> Submit Attendance Ledger
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Clean Prompt State when no course selected yet -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200/80 dark:border-slate-700 shadow-sm animate-fade-in">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 mb-4 shadow-inner">
                    <i data-lucide="clipboard-check" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Select a Subject to Mark Attendance</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    Filter by Department and Semester above, choose your assigned subject, and click <strong>Load Roster</strong> to start marking daily attendance.
                </p>
            </div>
        <?php endif; ?>

    </div>
</main>

<style>
/* Smooth fade-in animation for empty state */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<script>
    const facultyCourses = <?= json_encode($courses) ?>;
    const allDepartments = <?= json_encode(array_values($departments)) ?>;
    const allSemesters = <?= json_encode(array_values($semesters)) ?>;

    function onFilterDepartmentChange() {
        const deptSelect = document.getElementById('filter-department');
        const semSelect = document.getElementById('filter-semester');
        const courseSelect = document.getElementById('course_id');
        if (!deptSelect || !semSelect || !courseSelect) return;

        const deptId = parseInt(deptSelect.value) || 0;
        const currentCourseId = parseInt(courseSelect.value) || 0;

        let filtered = facultyCourses;
        if (deptId > 0) {
            filtered = filtered.filter(c => parseInt(c.department_id) === deptId);
        }

        updateSemesterSelect(semSelect, filtered, parseInt(semSelect.value) || 0);
        updateCourseSelect(courseSelect, filtered, parseInt(semSelect.value) || 0, currentCourseId);
    }

    function onFilterSemesterChange() {
        const deptSelect = document.getElementById('filter-department');
        const semSelect = document.getElementById('filter-semester');
        const courseSelect = document.getElementById('course_id');
        if (!deptSelect || !semSelect || !courseSelect) return;

        const deptId = parseInt(deptSelect.value) || 0;
        const semId = parseInt(semSelect.value) || 0;
        const currentCourseId = parseInt(courseSelect.value) || 0;

        let filtered = facultyCourses;
        if (deptId > 0) {
            filtered = filtered.filter(c => parseInt(c.department_id) === deptId);
        }

        updateCourseSelect(courseSelect, filtered, semId, currentCourseId);
    }

    function updateSemesterSelect(selectEl, coursesList, preselectedSemId = 0) {
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

        selectEl.innerHTML = '<option value="">-- Select a subject --</option>';
        if (filtered.length === 0) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'No courses assigned in this scope';
            opt.disabled = true;
            selectEl.appendChild(opt);
            return;
        }

        let isSelectedValid = false;
        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.course_code} - ${c.course_name} (Sem ${c.semester_number})`;
            if (parseInt(c.id) === preselectedCourseId) {
                opt.selected = true;
                isSelectedValid = true;
            }
            selectEl.appendChild(opt);
        });

        if (!isSelectedValid && preselectedCourseId > 0) {
            selectEl.value = '';
        }
    }

    // UX Interactivity and mock-AJAX submission for demo
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('attendance-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Form will submit natively now
                
                // UX: set uploading state to submit button
                const btn = document.getElementById('save-btn');
                btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Processing...';
                // Note: Disable button can prevent form submission in some browsers if it's the submit button, 
                // but since it's on submit event, usually it's fine. 
                // For safety we'll just add a class for pointer events.
                btn.classList.add('pointer-events-none', 'opacity-80');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
        
        // Custom visual logic for active states (ensuring check/x scale properly)
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const groupName = this.getAttribute('name');
                const groupRadios = document.querySelectorAll(`input[name="${groupName}"]`);
                groupRadios.forEach(r => {
                    // Navigate from input nested inside div.
                    const container = r.nextElementSibling;
                    const icon = container.querySelector('.rounded-indicator');
                    if (r.checked) {
                        icon.classList.remove('opacity-0');
                        icon.classList.add('opacity-100', 'scale-110');
                    } else {
                        icon.classList.add('opacity-0');
                        icon.classList.remove('opacity-100', 'scale-110');
                    }
                });
            });
            // Sync initial state if pre-checked
            if (radio.checked) {
                // Use a small timeout to let Lucide icons render first if needed
                setTimeout(() => {
                    radio.dispatchEvent(new Event('change'));
                }, 10);
            }
        });
    });

    // Helper for marking complete batch
    function markBulk(status) {
        let count = 0;
        const radios = document.querySelectorAll(`input[value="${status}"]`);
        radios.forEach(radio => {
            if (!radio.checked) count++;
            radio.checked = true;
            radio.dispatchEvent(new Event('change')); // trigger style sync
        });
        
        if (typeof window.showToast === 'function') {
            const statusLabel = status === 'LATE' ? 'Late (0.5 credit)' : (status === 'PRESENT' ? 'Present (1.0 credit)' : 'Absent');
            window.showToast(`Batch updated! Marked ${radios.length} students as ${statusLabel}.`, 'info');
        }
    }

    // Helper to reset/clear all attendance selections
    function resetAttendance() {
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            radio.checked = false;
            const container = radio.nextElementSibling;
            if (container) {
                const icon = container.querySelector('.rounded-indicator');
                if (icon) {
                    icon.classList.add('opacity-0');
                    icon.classList.remove('opacity-100', 'scale-110');
                }
            }
        });
        
        if (typeof window.showToast === 'function') {
            window.showToast('Attendance selections cleared. You can mark individually or use quick actions.', 'info');
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
