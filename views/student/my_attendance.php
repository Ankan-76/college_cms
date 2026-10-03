<?php
// views/student/my_attendance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'My Attendance | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Get all courses for the student's dept + semester with attendance stats
$courseStmt = $db->prepare("
    SELECT c.id, c.course_code, c.course_name, c.credits,
        (SELECT COUNT(*) FROM attendance WHERE course_id = c.id AND student_id = ?) as total_classes,
        (SELECT SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) FROM attendance WHERE course_id = c.id AND student_id = ?) as present_count,
        (SELECT SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) FROM attendance WHERE course_id = c.id AND student_id = ?) as late_count,
        (SELECT SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) FROM attendance WHERE course_id = c.id AND student_id = ?) as absent_count
    FROM courses c
    WHERE c.department_id = ? AND c.semester_id = ?
    ORDER BY c.course_code ASC
");
$courseStmt->execute([$studentId, $studentId, $studentId, $studentId, $departmentId, $semesterId]);
$courseAttendance = $courseStmt->fetchAll();

// Overall stats
$overallTotal = 0;
$overallPresent = 0;
$overallLate = 0;
$overallAbsent = 0;
$eligibleSubjectsCount = 0;
$criticalSubjectsCount = 0;

$subChartLabels = [];
$subChartData = [];
$subChartDetails = [];

foreach ($courseAttendance as $ca) {
    $cTotal = (int)$ca['total_classes'];
    $cPres = (int)$ca['present_count'];
    $cLate = (int)$ca['late_count'];
    $cAbs = (int)$ca['absent_count'];
    $cPct = $cTotal > 0 ? round(($cPres + $cLate) / $cTotal * 100) : 100;

    $overallTotal += $cTotal;
    $overallPresent += $cPres;
    $overallLate += $cLate;
    $overallAbsent += $cAbs;

    if ($cPct >= 75) {
        $eligibleSubjectsCount++;
    } else {
        $criticalSubjectsCount++;
    }

    $subChartLabels[] = $ca['course_code'];
    $subChartData[] = $cPct;
    $subChartDetails[] = [
        'code' => $ca['course_code'],
        'name' => $ca['course_name'],
        'credits' => $ca['credits'],
        'total' => $cTotal,
        'present' => $cPres,
        'late' => $cLate,
        'absent' => $cAbs,
        'pct' => $cPct
    ];
}

$effectivePresent = $overallPresent + $overallLate;
$overallPct = $overallTotal > 0 ? round(($effectivePresent / $overallTotal) * 100) : 100;

// Smart Attendance Advisory Calculation
$attendanceTarget = 75;
$consecutiveNeeded = 0;
$canSkipClasses = 0;

if ($overallTotal > 0) {
    if ($overallPct < $attendanceTarget) {
        // (effectivePresent + x) / (overallTotal + x) >= 0.75 => x = ceil((0.75 * total - effPres) / 0.25)
        $consecutiveNeeded = (int)ceil((0.75 * $overallTotal - $effectivePresent) / 0.25);
        if ($consecutiveNeeded < 1) $consecutiveNeeded = 1;
    } else {
        // effectivePresent / (overallTotal + y) >= 0.75 => y = floor((effectivePresent - 0.75 * total) / 0.75)
        $canSkipClasses = (int)floor(($effectivePresent - 0.75 * $overallTotal) / 0.75);
        if ($canSkipClasses < 0) $canSkipClasses = 0;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Page Header & Action Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>/views/student/dashboard.php" class="p-2.5 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-all" title="Back to Dashboard">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                        </span>
                        My Attendance
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Visual analytics, 75% exam criterion tracking, and course compliance breakdown.
                    </p>
                </div>
            </div>

            <!-- Export and Print Actions -->
            <div class="flex items-center gap-2.5 self-start sm:self-center">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-all">
                    <i data-lucide="printer" class="w-4 h-4 text-slate-500"></i> Print
                </button>
                
                <div class="relative">
                    <button type="button" onclick="toggleExportMenu('exportMenuAttPage')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-500/20 transition-all">
                        <i data-lucide="download" class="w-4 h-4"></i> Export <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                    </button>
                    <div id="exportMenuAttPage" class="hidden absolute right-0 mt-2 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-30 py-1.5 backdrop-blur-md">
                        <button type="button" onclick="exportChartAsPDF('attendance-visual-section', 'Attendance_Analytics_Report')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i> Save Charts as PDF
                        </button>
                        <button type="button" onclick="exportChartAsExcel(window.myAttendanceData.subjects, 'Course_Attendance_Details')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                            <i data-lucide="sheet" class="w-3.5 h-3.5 text-emerald-500"></i> Save Data as Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Advisory Alert Banner -->
        <?php if ($overallTotal > 0): ?>
            <?php if ($overallPct >= 75): ?>
                <div class="rounded-2xl bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 p-4 sm:p-5 flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-300">Exam Eligibility Confirmed</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                Safe Standing
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                            Your overall attendance is <strong class="text-emerald-600 dark:text-emerald-400 font-bold"><?= $overallPct ?>%</strong>, which comfortably exceeds the university minimum requirement of 75%.
                            <?php if ($canSkipClasses > 0): ?>
                                You can safely miss up to <strong class="text-slate-900 dark:text-white font-bold"><?= $canSkipClasses ?> session<?= $canSkipClasses === 1 ? '' : 's' ?></strong> while maintaining your exam eligibility.
                            <?php else: ?>
                                Attend upcoming classes to protect your eligibility threshold.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-2xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-transparent border border-amber-500/30 p-4 sm:p-5 flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300">Attendance Shortage Warning</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                Action Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                            Your overall attendance is <strong class="text-rose-600 dark:text-rose-400 font-bold"><?= $overallPct ?>%</strong>, which is currently below the required 75% exam criterion.
                            You need to attend the next <strong class="text-slate-900 dark:text-white font-bold"><?= $consecutiveNeeded ?> consecutive session<?= $consecutiveNeeded === 1 ? '' : 's' ?></strong> without absence to restore your standing.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Overall KPI Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-slate-300 dark:hover:border-slate-600">
                <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700/60 text-slate-500 dark:text-slate-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Sessions</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?= $overallTotal ?></p>
                <p class="text-[11px] text-slate-400 mt-1"><?= count($courseAttendance) ?> Enrolled Subjects</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-emerald-300 dark:hover:border-emerald-800">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="user-check" class="w-4 h-4"></i>
                </div>
                <p class="text-[10px] sm:text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Attended</p>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $effectivePresent ?></p>
                <p class="text-[11px] text-slate-400 mt-1"><?= $overallPresent ?> Pres • <?= $overallLate ?> Late</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-rose-300 dark:hover:border-rose-800">
                <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="user-x" class="w-4 h-4"></i>
                </div>
                <p class="text-[10px] sm:text-xs font-bold text-rose-500 uppercase tracking-wider mb-1">Absences</p>
                <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400"><?= $overallAbsent ?></p>
                <p class="text-[11px] text-slate-400 mt-1"><?= $overallTotal > 0 ? round(($overallAbsent / $overallTotal) * 100) : 0 ?>% Miss Rate</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center transition-all hover:border-indigo-300 dark:hover:border-indigo-800">
                <?php $overallColor = $overallPct >= 75 ? 'emerald' : ($overallPct >= 50 ? 'amber' : 'rose'); ?>
                <div class="w-8 h-8 rounded-lg bg-<?= $overallColor ?>-50 dark:bg-<?= $overallColor ?>-950/50 text-<?= $overallColor ?>-600 dark:text-<?= $overallColor ?>-400 flex items-center justify-center mx-auto mb-2">
                    <i data-lucide="percent" class="w-4 h-4"></i>
                </div>
                <p class="text-[10px] sm:text-xs font-bold text-<?= $overallColor ?>-500 uppercase tracking-wider mb-1">Overall Rate</p>
                <p class="text-2xl sm:text-3xl font-black text-<?= $overallColor ?>-600 dark:text-<?= $overallColor ?>-400"><?= $overallPct ?>%</p>
                <p class="text-[11px] font-semibold text-<?= $overallColor ?>-600 dark:text-<?= $overallColor ?>-400 mt-1">
                    <?= $overallPct >= 75 ? 'Eligible' : 'Warning' ?>
                </p>
            </div>
        </div>

        <!-- ═══════════ VISUAL ANALYTICS & ATTENDANCE GRAPHS ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" id="attendance-visual-section">
            
            <!-- Graph 1: Subject-wise Comparison vs 75% Benchmark (Bar Chart) -->
            <div class="lg:col-span-7 xl:col-span-8 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-5 sm:p-6 flex flex-col relative" id="subject-att-card">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="bar-chart-2" class="w-5 h-5 text-indigo-500"></i> Subject-wise Attendance Rates
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Course comparison relative to the 75% exam eligibility threshold
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            Safe: <span class="text-emerald-600 dark:text-emerald-400 font-extrabold"><?= $eligibleSubjectsCount ?></span> / <?= count($courseAttendance) ?>
                        </span>
                    </div>
                </div>

                <!-- Visual Color Key Legend -->
                <div class="flex flex-wrap items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-0.5 rounded-full border border-emerald-200/60 dark:border-emerald-800/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> &ge;75% Safe
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 rounded-full border border-amber-200/60 dark:border-amber-800/40">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> 50-74% Alert
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 px-2.5 py-0.5 rounded-full border border-rose-200/60 dark:border-rose-800/40">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> &lt;50% Critical
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/60 px-2.5 py-0.5 rounded-full border border-slate-200 dark:border-slate-600">
                        <span class="w-3 border-t-2 border-dashed border-amber-500"></span> 75% Target Line
                    </span>
                </div>

                <!-- Subject Bar Chart Canvas Container -->
                <div class="relative w-full h-[320px]">
                    <canvas id="myAttendanceSubjectChart"></canvas>
                </div>
            </div>

            <!-- Graph 2: Session Status Distribution (Doughnut Chart) -->
            <div class="lg:col-span-5 xl:col-span-4 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-5 sm:p-6 flex flex-col relative" id="status-att-card">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="pie-chart" class="w-5 h-5 text-emerald-500"></i> Status Breakdown
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Proportion of present, late, and absent records
                        </p>
                    </div>
                </div>

                <!-- Doughnut Canvas Container -->
                <div class="relative w-full h-[260px]">
                    <canvas id="myAttendanceStatusChart"></canvas>
                </div>

                <!-- Summary Breakdown Pills -->
                <div class="grid grid-cols-3 gap-2 pt-4 mt-2 border-t border-slate-100 dark:border-slate-700/60 text-center">
                    <div class="p-2 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30">
                        <span class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Present</span>
                        <span class="text-base font-black text-slate-900 dark:text-white"><?= $overallPresent ?></span>
                        <span class="block text-[10px] text-slate-400 font-medium"><?= $overallTotal > 0 ? round(($overallPresent / $overallTotal) * 100) : 0 ?>%</span>
                    </div>
                    <div class="p-2 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/30">
                        <span class="block text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase">Late</span>
                        <span class="text-base font-black text-slate-900 dark:text-white"><?= $overallLate ?></span>
                        <span class="block text-[10px] text-slate-400 font-medium"><?= $overallTotal > 0 ? round(($overallLate / $overallTotal) * 100) : 0 ?>%</span>
                    </div>
                    <div class="p-2 rounded-xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-900/30">
                        <span class="block text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase">Absent</span>
                        <span class="text-base font-black text-slate-900 dark:text-white"><?= $overallAbsent ?></span>
                        <span class="block text-[10px] text-slate-400 font-medium"><?= $overallTotal > 0 ? round(($overallAbsent / $overallTotal) * 100) : 0 ?>%</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══════════ DETAILED SUBJECT-WISE ACCORDION / CARDS ═══════════ -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="layers" class="w-5 h-5 text-indigo-500"></i> Course Attendance Details
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Comprehensive lecture records per assigned subject.</p>
                </div>
            </div>

            <?php if (empty($courseAttendance)): ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 sm:p-12 text-center">
                    <div class="inline-flex justify-center items-center w-16 h-16 bg-slate-100 dark:bg-slate-700/50 text-slate-400 rounded-full mb-4">
                        <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-2">No Attendance Records</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium max-w-sm mx-auto">Attendance data will appear here once your faculty conducts lectures.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <?php foreach ($courseAttendance as $course): 
                        $total = (int) $course['total_classes'];
                        $present = (int) $course['present_count'];
                        $late = (int) $course['late_count'];
                        $absent = (int) $course['absent_count'];
                        $effectiveCoursePres = $present + $late;
                        $pct = $total > 0 ? round(($effectiveCoursePres) / $total * 100) : 100;
                        $color = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
                        $isSafe = $pct >= 75;
                    ?>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 sm:p-6 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                                        <?= htmlspecialchars($course['course_code']) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-<?= $color ?>-50 dark:bg-<?= $color ?>-950/40 text-<?= $color ?>-600 dark:text-<?= $color ?>-400 border border-<?= $color ?>-200/60 dark:border-<?= $color ?>-800/40">
                                        <i data-lucide="<?= $isSafe ? 'check-circle' : 'alert-triangle' ?>" class="w-3 h-3"></i>
                                        <?= $isSafe ? 'Eligible' : 'Low Attendance' ?>
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate" title="<?= htmlspecialchars($course['course_name']) ?>">
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= $course['credits'] ?> Academic Credits</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-2xl sm:text-3xl font-black text-<?= $color ?>-600 dark:text-<?= $color ?>-400"><?= $pct ?>%</span>
                            </div>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-3 mb-4 overflow-hidden relative">
                            <!-- 75% Benchmark Indicator line inside progress bar -->
                            <div class="absolute top-0 bottom-0 left-[75%] w-0.5 bg-amber-400/80 z-10" title="75% Benchmark"></div>
                            <div class="bg-<?= $color ?>-500 h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?= min($pct, 100) ?>%"></div>
                        </div>
                        
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 pt-1">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span> Present: <strong class="text-slate-700 dark:text-slate-200"><?= $present ?></strong></span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span> Late: <strong class="text-slate-700 dark:text-slate-200"><?= $late ?></strong></span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span> Absent: <strong class="text-slate-700 dark:text-slate-200"><?= $absent ?></strong></span>
                            <span class="text-slate-400 dark:text-slate-500 font-bold">Total: <?= $total ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Inject Chart Data & Call Script -->
<script>
    window.myAttendanceData = {
        subjects: {
            labels: <?= json_encode($subChartLabels) ?>,
            data: <?= json_encode($subChartData) ?>,
            details: <?= json_encode($subChartDetails) ?>
        },
        status: {
            labels: ['Present', 'Late', 'Absent'],
            data: [<?= $overallPresent ?>, <?= $overallLate ?>, <?= $overallAbsent ?>],
            overallPct: <?= $overallPct ?>,
            overallTotal: <?= $overallTotal ?>,
            overallPresent: <?= $overallPresent ?>,
            overallLate: <?= $overallLate ?>,
            overallAbsent: <?= $overallAbsent ?>
        }
    };
</script>

<script src="<?= BASE_URL ?>/assets/js/charts.js?v=<?= filemtime(__DIR__ . '/../../assets/js/charts.js') ?>"></script>
<script>
    (function() {
        function launchAttendanceCharts() {
            if (typeof Chart === 'undefined') {
                console.warn('Waiting for Chart.js...');
                setTimeout(launchAttendanceCharts, 60);
                return;
            }

            if (typeof initMyAttendanceCharts === 'function') {
                initMyAttendanceCharts();
            } else {
                // Self-contained fallback renderer ensuring charts render even if external script is cached
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#94a3b8' : '#64748b';
                const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';
                Chart.defaults.color = textColor;
                Chart.defaults.font.family = 'Inter, sans-serif';

                const subCtx = document.getElementById('myAttendanceSubjectChart');
                if (subCtx) {
                    const existing = Chart.getChart(subCtx);
                    if (existing) existing.destroy();

                    const labels = window.myAttendanceData?.subjects?.labels?.length ? window.myAttendanceData.subjects.labels : ['No Courses'];
                    const data = window.myAttendanceData?.subjects?.data?.length ? window.myAttendanceData.subjects.data : [0];
                    const details = window.myAttendanceData?.subjects?.details || [];
                    const ctx2d = subCtx.getContext('2d');

                    const backgroundColors = data.map(val => {
                        const grad = ctx2d.createLinearGradient(0, 0, 0, 260);
                        if (val >= 75) {
                            grad.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
                            grad.addColorStop(1, 'rgba(16, 185, 129, 0.25)');
                        } else if (val >= 50) {
                            grad.addColorStop(0, 'rgba(245, 158, 11, 0.95)');
                            grad.addColorStop(1, 'rgba(245, 158, 11, 0.25)');
                        } else {
                            grad.addColorStop(0, 'rgba(244, 63, 94, 0.95)');
                            grad.addColorStop(1, 'rgba(244, 63, 94, 0.25)');
                        }
                        return grad;
                    });
                    const borderColors = data.map(val => val >= 75 ? '#10b981' : (val >= 50 ? '#f59e0b' : '#f43f5e'));

                    new Chart(subCtx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Attendance Rate',
                                data: data,
                                backgroundColor: backgroundColors,
                                borderColor: borderColors,
                                borderWidth: { top: 2, right: 0, bottom: 0, left: 0 },
                                borderRadius: 8,
                                borderSkipped: false,
                                maxBarThickness: 46
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: isDark ? '#0f172a' : '#ffffff',
                                    titleColor: isDark ? '#f8fafc' : '#0f172a',
                                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                                    borderColor: isDark ? '#334155' : '#e2e8f0',
                                    borderWidth: 1,
                                    padding: 12,
                                    cornerRadius: 10,
                                    callbacks: {
                                        title: ctx => {
                                            const item = details[ctx[0]?.dataIndex];
                                            return item ? `${item.name} (${item.code})` : ctx[0]?.label;
                                        },
                                        label: ctx => {
                                            const item = details[ctx.dataIndex];
                                            const lines = [` Attendance Rate: ${ctx.parsed.y}%`];
                                            if (item) {
                                                lines.push(` Attended: ${item.present}/${item.total} sessions`);
                                                if (item.late > 0) lines.push(` Late: ${item.late}`);
                                                if (item.absent > 0) lines.push(` Absent: ${item.absent}`);
                                                lines.push(` Status: ${ctx.parsed.y >= 75 ? 'Eligible' : 'Low (<75%)'}`);
                                            }
                                            return lines;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: { color: gridColor, drawBorder: false },
                                    ticks: { stepSize: 25, callback: v => v + '%', font: { size: 11, weight: '500' }, color: textColor },
                                    border: { display: false }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11, weight: '600' }, color: textColor },
                                    border: { display: false }
                                }
                            }
                        },
                        plugins: [{
                            id: 'inlineThresholdLine',
                            afterDraw(chart) {
                                const { ctx, chartArea, scales: { y } } = chart;
                                if (!chartArea || !y) return;
                                const yVal = y.getPixelForValue(75);
                                if (yVal < chartArea.top || yVal > chartArea.bottom) return;
                                ctx.save();
                                ctx.beginPath();
                                ctx.lineWidth = 1.5;
                                ctx.setLineDash([5, 4]);
                                ctx.strokeStyle = '#f59e0b';
                                ctx.moveTo(chartArea.left, yVal);
                                ctx.lineTo(chartArea.right, yVal);
                                ctx.stroke();
                                ctx.setLineDash([]);
                                ctx.font = '700 10px Inter, sans-serif';
                                ctx.fillStyle = '#f59e0b';
                                ctx.textAlign = 'right';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText('75% Target', chartArea.right - 4, yVal - 3);
                                ctx.restore();
                            }
                        }]
                    });
                }

                const statusCtx = document.getElementById('myAttendanceStatusChart');
                if (statusCtx) {
                    const existingStatus = Chart.getChart(statusCtx);
                    if (existingStatus) existingStatus.destroy();

                    const s = window.myAttendanceData?.status || {};
                    const total = s.overallTotal || 0;
                    const hasData = total > 0;
                    const chartData = hasData ? [s.overallPresent || 0, s.overallLate || 0, s.overallAbsent || 0] : [1];
                    const chartLabels = hasData ? ['Present', 'Late', 'Absent'] : ['No Sessions'];
                    const chartColors = hasData ? ['#10b981', '#f59e0b', '#f43f5e'] : [isDark ? '#334155' : '#cbd5e1'];

                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                data: chartData,
                                backgroundColor: chartColors,
                                borderWidth: isDark ? 2 : 1,
                                borderColor: isDark ? '#1e293b' : '#ffffff',
                                hoverOffset: hasData ? 6 : 0,
                                borderRadius: 4,
                                spacing: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 14,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        font: { size: 11, weight: '600', family: 'Inter, sans-serif' },
                                        color: textColor
                                    }
                                }
                            }
                        },
                        plugins: [{
                            id: 'inlineCenterText',
                            afterDraw(chart) {
                                if (chart.config.type !== 'doughnut') return;
                                const { ctx, chartArea } = chart;
                                if (!chartArea) return;
                                let centerX = (chartArea.left + chartArea.right) / 2;
                                let centerY = (chartArea.top + chartArea.bottom) / 2;
                                const meta = chart.getDatasetMeta(0);
                                if (meta && meta.data && meta.data.length > 0 && typeof meta.data[0].x === 'number') {
                                    centerX = meta.data[0].x;
                                    centerY = meta.data[0].y;
                                }
                                ctx.save();
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.font = '800 24px Inter, sans-serif';
                                ctx.fillStyle = isDark ? '#f8fafc' : '#0f172a';
                                ctx.fillText(`${s.overallPct || 0}%`, centerX, centerY - 8);
                                ctx.font = '700 9px Inter, sans-serif';
                                ctx.fillStyle = isDark ? '#94a3b8' : '#64748b';
                                ctx.fillText('ATTENDANCE', centerX, centerY + 10);
                                if (hasData) {
                                    const isEligible = (s.overallPct || 0) >= 75;
                                    ctx.font = '700 8.5px Inter, sans-serif';
                                    ctx.fillStyle = isEligible ? '#10b981' : ((s.overallPct || 0) >= 50 ? '#f59e0b' : '#f43f5e');
                                    ctx.fillText(isEligible ? 'ELIGIBLE' : 'DEFICIT', centerX, centerY + 22);
                                }
                                ctx.restore();
                            }
                        }]
                    });
                }
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // Trigger on DOM ready, or immediately if already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', launchAttendanceCharts);
        } else {
            launchAttendanceCharts();
        }
        window.addEventListener('load', launchAttendanceCharts);

        // Theme toggle observer
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(() => {
                setTimeout(launchAttendanceCharts, 50);
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        }
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
