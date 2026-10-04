<?php
// views/faculty/view_attendance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'View Attendance | Faculty Portal';

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
$startDate = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d');

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

$stats = [];
$reportTotalStudents = 0;
$classAvgPct = 0;
$safeCount = 0;
$warningCount = 0;
$criticalCount = 0;
$atRiskCount = 0;
$studentChartLabels = [];
$studentChartData = [];
$studentChartDetails = [];

if ($selectedCourseId) {
    $stats = $controller->getAttendanceStats($selectedCourseId, $startDate, $endDate);
    $reportTotalStudents = count($stats);

    if (!empty($stats)) {
        $sumPct = 0;
        foreach ($stats as $st) {
            $tClasses = (int) $st['total_classes'];
            $stPresentOnTime = (int) $st['total_present'];
            $stLate = (int) $st['total_late'];
            $stAbsent = (int) $st['total_absent'];
            $stEffectivePresent = $stPresentOnTime + ($stLate * 0.5); // Option B: 1 late = 0.5 presence
            $stPct = $tClasses > 0 ? round(($stEffectivePresent / $tClasses) * 100, 1) : 0;
            $sumPct += $stPct;

            if ($stPct >= 75) {
                $safeCount++;
            } elseif ($stPct >= 60) {
                $warningCount++;
            } else {
                $criticalCount++;
            }

            $roll = !empty($st['roll_number']) ? $st['roll_number'] : '';
            $name = !empty($st['name']) ? $st['name'] : 'Student';
            $studentChartLabels[] = $roll ? $roll : (strlen($name) > 12 ? substr($name, 0, 12) . '..' : $name);
            $studentChartData[] = $stPct;
            $studentChartDetails[] = [
                'name' => $name,
                'roll' => $roll,
                'present' => $stPresentOnTime,
                'late' => $stLate,
                'effective_present' => $stEffectivePresent,
                'absent' => $stAbsent,
                'total' => $tClasses,
                'pct' => $stPct
            ];
        }
        $classAvgPct = $reportTotalStudents > 0 ? round($sumPct / $reportTotalStudents, 1) : 0;
        $atRiskCount = $warningCount + $criticalCount;
    }
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header & Actions -->
        <div class="screen-only flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Attendance Report</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View detailed attendance statistics and percentages over a date range.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="take_attendance.php<?= $selectedCourseId ? '?course_id=' . $selectedCourseId . ($selectedDepartmentId ? '&department_id=' . $selectedDepartmentId : '') . ($selectedSemesterId ? '&semester_id=' . $selectedSemesterId : '') : '' ?>" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Ledger
                </a>
                <button type="button" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900" onclick="exportTableToCSV('attendance_report.csv')">
                    <i data-lucide="download" class="w-4 h-4"></i> Export CSV
                </button>
                <?php if ($selectedCourseId !== null && !empty($stats)): ?>
                <button type="button" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900" onclick="exportChartAsPDF('faculty-attendance-visual-section', 'Faculty_Attendance_Analytics')">
                    <i data-lucide="file-text" class="w-4 h-4 text-rose-500"></i> Export Charts
                </button>
                <?php endif; ?>
                <button type="button" class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-2 border border-indigo-200 dark:border-indigo-800 rounded-xl text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900" onclick="window.print()">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Filter Card: Step 1: Department -> Step 2: Semester -> Step 3: Subject -> Date Range -->
        <div class="filter-card-container bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 transform transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 mb-4 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Report Scope Setup</h2>
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
                        <i data-lucide="calendar-range" class="w-3 h-3"></i> <?= date('M d', strtotime($startDate)) ?> &ndash; <?= date('M d, Y', strtotime($endDate)) ?>
                    </span>
                </div>
            </div>

            <form method="GET" action="view_attendance.php" id="view-attendance-filter-form" class="space-y-4">
                <!-- Academic Scope: Department -> Semester -> Subject -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                    <!-- Step 1: Department -->
                    <div class="lg:col-span-4">
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
                    <div class="lg:col-span-3">
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
                    <div class="lg:col-span-5">
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
                </div>

                <!-- Date Range & Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end pt-1">
                    <!-- Start Date -->
                    <div class="lg:col-span-4">
                        <label for="start_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                                Start Date (From)
                            </span>
                        </label>
                        <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                    </div>

                    <!-- End Date -->
                    <div class="lg:col-span-4">
                        <label for="end_date" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-indigo-500"></i>
                                End Date (To)
                            </span>
                        </label>
                        <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 p-2.5 text-sm outline-none transition-colors">
                    </div>

                    <!-- Action Buttons: Generate & Reset -->
                    <div class="lg:col-span-4 flex items-center gap-2">
                        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-3 rounded-xl text-sm transition-all hover:shadow hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center justify-center gap-1.5 shrink-0">
                            <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                            <span>Generate Report</span>
                        </button>
                        <a href="view_attendance.php" title="Reset Filters" class="p-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl transition-colors shrink-0 flex items-center justify-center">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Attendance Stats Grid -->
        <?php if ($selectedCourseId !== null): ?>
            <?php if (empty($stats)): ?>
                <!-- Empty State -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                        <i data-lucide="bar-chart-2" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No data available</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">We couldn't find any student data or attendance records for the selected course in this date range.</p>
                </div>
            <?php else: ?>
                <!-- Summary KPI Metric Cards (screen-only) -->
                <div class="screen-only grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-slate-300 dark:hover:border-slate-600">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 flex items-center justify-center mx-auto mb-2.5">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Enrolled Students</p>
                        <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?= $reportTotalStudents ?></p>
                        <p class="text-[11px] text-slate-400 mt-1">Class Roster Size</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-700">
                        <?php 
                            $avgColor = $classAvgPct >= 75 ? 'emerald' : ($classAvgPct >= 60 ? 'amber' : 'rose');
                        ?>
                        <div class="w-9 h-9 rounded-xl bg-<?= $avgColor ?>-50 dark:bg-<?= $avgColor ?>-950/50 text-<?= $avgColor ?>-600 dark:text-<?= $avgColor ?>-400 flex items-center justify-center mx-auto mb-2.5">
                            <i data-lucide="percent" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[10px] sm:text-xs font-bold text-<?= $avgColor ?>-500 uppercase tracking-wider mb-1">Class Average</p>
                        <p class="text-2xl sm:text-3xl font-black text-<?= $avgColor ?>-600 dark:text-<?= $avgColor ?>-400"><?= $classAvgPct ?>%</p>
                        <p class="text-[11px] font-semibold text-<?= $avgColor ?>-600 dark:text-<?= $avgColor ?>-400 mt-1">
                            <?= $classAvgPct >= 75 ? 'Optimal Standing' : ($classAvgPct >= 60 ? 'Caution Range' : 'Critical Deficit') ?>
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-emerald-300 dark:hover:border-emerald-800">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2.5">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[10px] sm:text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Exam Eligible (&ge;75%)</p>
                        <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $safeCount ?></p>
                        <p class="text-[11px] text-slate-400 mt-1"><?= $reportTotalStudents > 0 ? round(($safeCount / $reportTotalStudents) * 100) : 0 ?>% of Roster</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-rose-300 dark:hover:border-rose-800">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-2.5">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                        <p class="text-[10px] sm:text-xs font-bold text-rose-500 uppercase tracking-wider mb-1">At-Risk (&lt;75%)</p>
                        <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400"><?= $atRiskCount ?></p>
                        <p class="text-[11px] text-slate-400 mt-1"><?= $warningCount ?> Warning &bull; <?= $criticalCount ?> Critical</p>
                    </div>
                </div>

                <!-- ═══════════ VISUAL ANALYTICS & ATTENDANCE GRAPHS ═══════════ -->
                <div class="screen-only grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6" id="faculty-attendance-visual-section">
                    
                    <!-- Graph 1: Student-wise Comparison vs 75% Benchmark (Bar Chart) -->
                    <div class="lg:col-span-7 xl:col-span-8 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 sm:p-6 flex flex-col relative" id="faculty-student-att-card">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="bar-chart-2" class="w-5 h-5 text-indigo-500"></i> Student Attendance Comparison
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Student-by-student attendance rate relative to the 75% exam eligibility criterion
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-700/60 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-600">
                                    Eligible: <span class="text-emerald-600 dark:text-emerald-400 font-extrabold"><?= $safeCount ?></span> / <?= $reportTotalStudents ?>
                                </span>
                            </div>
                        </div>

                        <!-- Visual Color Key Legend -->
                        <div class="flex flex-wrap items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-0.5 rounded-full border border-emerald-200/60 dark:border-emerald-800/40">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> &ge;75% Safe
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 rounded-full border border-amber-200/60 dark:border-amber-800/40">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> 60-74% Warning
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 px-2.5 py-0.5 rounded-full border border-rose-200/60 dark:border-rose-800/40">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> &lt;60% Critical
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/60 px-2.5 py-0.5 rounded-full border border-slate-200 dark:border-slate-600">
                                <span class="w-3 border-t-2 border-dashed border-amber-500"></span> 75% Criterion Line
                            </span>
                        </div>

                        <!-- Student Bar Chart Canvas Container -->
                        <div class="relative w-full h-[320px]">
                            <canvas id="facultyStudentAttendanceChart"></canvas>
                        </div>
                    </div>

                    <!-- Graph 2: Class Eligibility Tiers (Doughnut Chart) -->
                    <div class="lg:col-span-5 xl:col-span-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 sm:p-6 flex flex-col relative" id="faculty-tier-att-card">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="pie-chart" class="w-5 h-5 text-emerald-500"></i> Class Eligibility Tiers
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Statutory exam eligibility distribution
                                </p>
                            </div>
                        </div>

                        <!-- Doughnut Canvas Container -->
                        <div class="relative w-full h-[260px]">
                            <canvas id="facultyAttendanceTierChart"></canvas>
                        </div>

                        <!-- Summary Breakdown Pills -->
                        <div class="grid grid-cols-3 gap-2 pt-4 mt-2 border-t border-slate-100 dark:border-slate-700/60 text-center">
                            <div class="p-2 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30">
                                <span class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Safe (&ge;75%)</span>
                                <span class="text-base font-black text-slate-900 dark:text-white"><?= $safeCount ?></span>
                                <span class="block text-[10px] text-slate-400 font-medium"><?= $reportTotalStudents > 0 ? round(($safeCount / $reportTotalStudents) * 100) : 0 ?>%</span>
                            </div>
                            <div class="p-2 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30">
                                <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase">Warning (60-74%)</span>
                                <span class="text-base font-black text-slate-900 dark:text-white"><?= $warningCount ?></span>
                                <span class="block text-[10px] text-slate-400 font-medium"><?= $reportTotalStudents > 0 ? round(($warningCount / $reportTotalStudents) * 100) : 0 ?>%</span>
                            </div>
                            <div class="p-2 rounded-xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30">
                                <span class="block text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase">Critical (&lt;60%)</span>
                                <span class="text-base font-black text-slate-900 dark:text-white"><?= $criticalCount ?></span>
                                <span class="block text-[10px] text-slate-400 font-medium"><?= $reportTotalStudents > 0 ? round(($criticalCount / $reportTotalStudents) * 100) : 0 ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Data Card -->
                <div class="stats-card-container bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 opacity-100">
                    
                    <!-- Screen-Only Summary Header -->
                    <div class="screen-header-bar px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                                <i data-lucide="pie-chart" class="w-5 h-5 text-indigo-500"></i>
                                Attendance Summary
                            </h2>
                            <?php if ($selectedCourse): ?>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <?= htmlspecialchars($selectedCourse['course_code']) ?> &bull; <?= htmlspecialchars($selectedCourse['course_name']) ?> (Sem <?= htmlspecialchars((string)$selectedCourse['semester_number']) ?>)
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-700 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <?= date('M d, Y', strtotime($startDate)) ?> &ndash; <?= date('M d, Y', strtotime($endDate)) ?>
                        </div>
                    </div>

                    <!-- Print-Only Official Institution Header -->
                    <div class="print-header-banner hidden p-4 pb-3 mb-3 border-b-2 border-black">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h1 class="text-2xl font-black text-black tracking-tight uppercase">GreenField College</h1>
                                </div>
                                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-widest mt-0.5">Faculty Academic Ledger &bull; Official Course Attendance Report</h2>
                                <?php if ($selectedCourse): ?>
                                    <div class="mt-2 text-xs text-black space-y-0.5">
                                        <p><strong>Course:</strong> <?= htmlspecialchars($selectedCourse['course_code'] . ' - ' . $selectedCourse['course_name']) ?></p>
                                        <p><strong>Department:</strong> <?= htmlspecialchars($selectedCourse['dept_name'] ?? 'General') ?> &nbsp;|&nbsp; <strong>Semester:</strong> Semester <?= htmlspecialchars((string)$selectedCourse['semester_number']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-right text-xs text-black space-y-1">
                                <div class="border border-black px-3 py-2 rounded-lg bg-slate-50 text-left">
                                    <p><strong>Period:</strong> <?= date('M d, Y', strtotime($startDate)) ?> &ndash; <?= date('M d, Y', strtotime($endDate)) ?></p>
                                    <p><strong>Generated:</strong> <?= date('M d, Y H:i') ?></p>
                                    <p><strong>Students Enrolled:</strong> <?= count($stats) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="attendance-table" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Roll No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student Name</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Classes</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Present</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Late (0.5x)</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Absent</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider col-percentage">Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700/50">
                                <?php foreach ($stats as $student): 
                                    $totalClasses = (int) $student['total_classes'];
                                    $present = (int) $student['total_present'];
                                    $late = (int) $student['total_late'];
                                    $absent = (int) $student['total_absent'];
                                    $effectivePresent = $present + ($late * 0.5); // Option B: 1 late counts as 0.5 presence
                                    
                                    $percentage = $totalClasses > 0 ? round(($effectivePresent / $totalClasses) * 100, 2) : 0;
                                    
                                    // Badge color and semantic print status class based on percentage
                                    $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
                                    $statusClass = 'badge-safe';
                                    if ($percentage < 75 && $percentage >= 60) {
                                        $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
                                        $statusClass = 'badge-warning';
                                    } elseif ($percentage < 60) {
                                        $badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400';
                                        $statusClass = 'badge-danger';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($student['roll_number']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="student-name text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($student['name']) ?></div>
                                        <div class="student-reg-no text-xs text-slate-500 dark:text-slate-400 font-medium">Reg: <?= htmlspecialchars($student['registration_number']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-400 font-medium">
                                        <?= $totalClasses ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-emerald-600 dark:text-emerald-400 font-bold">
                                        <?= $present ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-amber-600 dark:text-amber-400 font-bold">
                                        <?= $late ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-rose-600 dark:text-rose-400 font-bold">
                                        <?= $absent ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center col-percentage">
                                        <span class="percentage-badge inline-block px-3 py-1 rounded-full text-xs font-bold <?= $badgeClass ?> <?= $statusClass ?>">
                                            <?= (float)$percentage ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Print-Only Verification & Signature Block -->
                    <div class="print-footer-signature hidden p-6 pt-8 mt-4 border-t border-slate-300">
                        <div class="grid grid-cols-3 gap-8 text-center text-xs text-slate-900">
                            <div>
                                <div class="border-b border-black pb-1 mb-1.5 h-12"></div>
                                <p class="font-bold uppercase tracking-wider text-[11px] text-black">Faculty In-Charge</p>
                                <p class="text-[10px] text-slate-600">(Signature & Date)</p>
                            </div>
                            <div>
                                <div class="border-b border-black pb-1 mb-1.5 h-12"></div>
                                <p class="font-bold uppercase tracking-wider text-[11px] text-black">Head of Department (HOD)</p>
                                <p class="text-[10px] text-slate-600">(Verification Stamp)</p>
                            </div>
                            <div>
                                <div class="border-b border-black pb-1 mb-1.5 h-12"></div>
                                <p class="font-bold uppercase tracking-wider text-[11px] text-black">Academic Controller</p>
                                <p class="text-[10px] text-slate-600">(Official Seal)</p>
                            </div>
                        </div>
                        <div class="mt-6 text-center text-[10px] text-slate-500 uppercase tracking-widest border-t border-slate-200 pt-2">
                            GreenField College &bull; Official Academic Attendance Record &bull; Confidential
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Initial Prompt / Guide State -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-5 shadow-inner">
                    <i data-lucide="bar-chart-2" class="w-10 h-10"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">Select Scope & Subject</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
                    Filter by Department and Semester above, choose your assigned subject, and click <strong>Generate Report</strong> to inspect cumulative attendance analytics.
                </p>
            </div>
        <?php endif; ?>

    </div>
</main>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

/* Executive Black & White Print Styling (Guarantees beautiful, high-contrast B&W report even when in dark mode) */
@media print {
    /* Standard document paper margins */
    @page {
        size: auto;
        margin: 12mm 10mm 12mm 10mm;
    }

    /* Force pure white page background and eliminate any dark mode background leakage */
    html, 
    body, 
    main, 
    .max-w-7xl, 
    .stats-card-container, 
    #attendance-table, 
    #attendance-table tbody, 
    #attendance-table tr, 
    #attendance-table td {
        background-color: #ffffff !important;
        background: #ffffff !important;
        color: #000000 !important;
    }

    html, body {
        height: auto !important;
        min-height: 100% !important;
        overflow: visible !important;
        font-size: 11px !important;
        line-height: 1.4 !important;
    }

    /* Exact color fidelity for borders, text, and print elements */
    *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
        text-shadow: none !important;
    }

    /* Completely hide web navigation, screen headers, filters, and action buttons */
    header, 
    aside, 
    nav,
    footer,
    #sidebar, 
    .sidebar, 
    #mobile-menu-btn,
    .screen-only,
    .screen-header-bar,
    .filter-card-container,
    #view-attendance-filter-form,
    .shrink-0,
    button,
    form {
        display: none !important;
    }

    /* In-flow normal document rendering */
    main {
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .max-w-7xl {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
    }

    /* Remove scroll clipping */
    .overflow-hidden,
    .overflow-x-auto,
    .overflow-y-auto {
        overflow: visible !important;
        width: 100% !important;
    }

    /* Show official institutional print letterhead & signature footer */
    .print-header-banner {
        display: block !important;
    }

    .print-footer-signature {
        display: block !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .stats-card-container {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    /* Table Layout & Executive Black-and-White Styling */
    #attendance-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
        border: 2px solid #000000 !important;
    }

    #attendance-table thead {
        display: table-header-group !important;
    }

    /* High-contrast solid black table header with crisp white text */
    #attendance-table thead tr,
    #attendance-table thead th {
        background-color: #000000 !important;
        background: #000000 !important;
        color: #ffffff !important;
        border: 1px solid #000000 !important;
        font-weight: 800 !important;
        font-size: 10.5px !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        padding: 8px 6px !important;
    }

    #attendance-table tbody tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* Alternating subtle zebra striping for easy reading */
    #attendance-table tbody tr:nth-child(even) td {
        background-color: #f8fafc !important;
        background: #f8fafc !important;
    }
    #attendance-table tbody tr:nth-child(odd) td {
        background-color: #ffffff !important;
        background: #ffffff !important;
    }

    #attendance-table th,
    #attendance-table td {
        white-space: normal !important;
        word-break: break-word !important;
        padding: 7px 8px !important;
        border: 1px solid #cbd5e1 !important;
    }

    /* Ensure text colors in all table cells are crisp black / dark slate */
    #attendance-table td,
    #attendance-table td div,
    #attendance-table td span:not(.percentage-badge) {
        color: #000000 !important;
    }

    #attendance-table td .student-name {
        font-weight: 700 !important;
        color: #000000 !important;
        font-size: 11.5px !important;
    }

    #attendance-table td .student-reg-no {
        color: #475569 !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }

    /* Column Widths (Sum = 100%) */
    #attendance-table th:nth-child(1),
    #attendance-table td:nth-child(1) {
        width: 15% !important;
        font-weight: 700 !important;
    } /* Roll No */

    #attendance-table th:nth-child(2),
    #attendance-table td:nth-child(2) {
        width: 33% !important;
    } /* Student Name & Reg */

    #attendance-table th:nth-child(3),
    #attendance-table td:nth-child(3) {
        width: 13% !important;
        text-align: center !important;
        font-weight: 600 !important;
    } /* Total Classes */

    #attendance-table th:nth-child(4),
    #attendance-table td:nth-child(4) {
        width: 12% !important;
        text-align: center !important;
        font-weight: 700 !important;
        color: #000000 !important;
    } /* Present */

    #attendance-table th:nth-child(5),
    #attendance-table td:nth-child(5) {
        width: 12% !important;
        text-align: center !important;
        font-weight: 700 !important;
        color: #000000 !important;
    } /* Absent */

    #attendance-table th:nth-child(6),
    #attendance-table td:nth-child(6),
    .col-percentage {
        width: 15% !important;
        text-align: center !important;
        display: table-cell !important;
        visibility: visible !important;
        opacity: 1 !important;
    } /* Percentage */

    /* Attractive Black & White Percentage Badges */
    .percentage-badge {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        padding: 3px 10px !important;
        border-radius: 9999px !important;
        font-size: 10.5px !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
        text-align: center !important;
        white-space: nowrap !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Safe (>=75%): Crisp light gray pill with solid black border & black text */
    .percentage-badge.badge-safe {
        background-color: #f1f5f9 !important;
        background: #f1f5f9 !important;
        color: #000000 !important;
        border: 1.5px solid #000000 !important;
    }

    /* Warning (60-74%): White pill with dashed dark border & black text */
    .percentage-badge.badge-warning {
        background-color: #ffffff !important;
        background: #ffffff !important;
        color: #000000 !important;
        border: 1.5px dashed #475569 !important;
    }

    /* Danger (<60%): Inverted solid black pill with white text for instant emphasis */
    .percentage-badge.badge-danger {
        background-color: #000000 !important;
        background: #000000 !important;
        color: #ffffff !important;
        border: 1.5px solid #000000 !important;
    }
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

    function downloadCSV(csv, filename) {
        let csvFile;
        let downloadLink;

        // CSV file
        csvFile = new Blob([csv], {type: "text/csv"});

        // Download link
        downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    function exportTableToCSV(filename) {
        let csv = [];
        let rows = document.querySelectorAll("#attendance-table tr");
        if (rows.length === 0) {
            alert('No attendance data available to export.');
            return;
        }
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (let j = 0; j < cols.length; j++) {
                // Clean up the text (remove newlines and extra spaces)
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                // Escape double quotes
                data = data.replace(/"/g, '""');
                // Enclose in quotes
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }

        downloadCSV(csv.join("\n"), filename);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php if ($selectedCourseId !== null && !empty($stats)): ?>
<script src="<?= BASE_URL ?>/assets/js/charts.js?v=<?= filemtime(__DIR__ . '/../../assets/js/charts.js') ?>"></script>
<script>
    window.facultyAttendanceReportData = <?= json_encode([
        'students' => [
            'labels' => $studentChartLabels,
            'data' => $studentChartData,
            'details' => $studentChartDetails
        ],
        'tiers' => [
            'safe' => $safeCount,
            'warning' => $warningCount,
            'critical' => $criticalCount
        ],
        'classAvg' => $classAvgPct
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    (function() {
        function launchFacultyAttendanceCharts() {
            if (typeof Chart === 'undefined') {
                setTimeout(launchFacultyAttendanceCharts, 60);
                return;
            }

            if (typeof initFacultyAttendanceReportCharts === 'function') {
                initFacultyAttendanceReportCharts();
            } else if (typeof window.initFacultyAttendanceReportCharts === 'function') {
                window.initFacultyAttendanceReportCharts();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', launchFacultyAttendanceCharts);
        } else {
            launchFacultyAttendanceCharts();
        }
        window.addEventListener('load', launchFacultyAttendanceCharts);
    })();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
