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
foreach ($courseAttendance as $ca) {
    $overallTotal += (int)$ca['total_classes'];
    $overallPresent += (int)$ca['present_count'];
    $overallLate += (int)$ca['late_count'];
    $overallAbsent += (int)$ca['absent_count'];
}
$overallPct = $overallTotal > 0 ? round(($overallPresent + $overallLate) / $overallTotal * 100) : 0;

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>/views/student/dashboard.php" class="p-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="bar-chart-big" class="w-6 h-6 text-indigo-500"></i> My Attendance
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Subject-wise attendance breakdown for current semester.</p>
                </div>
            </div>
        </div>

        <!-- Overall Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center">
                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 sm:mb-2">Total Classes</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"><?= $overallTotal ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center">
                <p class="text-[10px] sm:text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1 sm:mb-2">Present</p>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $overallPresent ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center">
                <p class="text-[10px] sm:text-xs font-bold text-rose-500 uppercase tracking-wider mb-1 sm:mb-2">Absent</p>
                <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400"><?= $overallAbsent ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 text-center">
                <?php $overallColor = $overallPct >= 75 ? 'emerald' : ($overallPct >= 50 ? 'amber' : 'rose'); ?>
                <p class="text-[10px] sm:text-xs font-bold text-<?= $overallColor ?>-500 uppercase tracking-wider mb-1 sm:mb-2">Overall</p>
                <p class="text-2xl sm:text-3xl font-black text-<?= $overallColor ?>-600 dark:text-<?= $overallColor ?>-400"><?= $overallPct ?>%</p>
            </div>
        </div>

        <!-- Subject-wise Attendance -->
        <?php if (empty($courseAttendance)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 sm:p-12 text-center">
            <div class="inline-flex justify-center items-center w-16 h-16 bg-slate-100 dark:bg-slate-700/50 text-slate-400 rounded-full mb-4">
                <i data-lucide="clipboard-list" class="w-8 h-8"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-2">No Attendance Records</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium max-w-sm mx-auto">Attendance data will appear here once your classes begin.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <?php foreach ($courseAttendance as $course): 
                $total = (int) $course['total_classes'];
                $present = (int) $course['present_count'];
                $late = (int) $course['late_count'];
                $absent = (int) $course['absent_count'];
                $pct = $total > 0 ? round(($present + $late) / $total * 100) : 0;
                $color = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 sm:p-6 hover:shadow-md transition-all">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50 mb-1.5">
                            <?= htmlspecialchars($course['course_code']) ?>
                        </span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($course['course_name']) ?></h3>
                        <p class="text-xs text-slate-500 mt-0.5"><?= $course['credits'] ?> Credits</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xl sm:text-2xl font-black text-<?= $color ?>-600 dark:text-<?= $color ?>-400"><?= $pct ?>%</span>
                    </div>
                </div>
                
                <!-- Progress bar -->
                <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-3 mb-4 overflow-hidden">
                    <div class="bg-<?= $color ?>-500 h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?= $pct ?>%"></div>
                </div>
                
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span> Present: <?= $present ?></span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span> Late: <?= $late ?></span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span> Absent: <?= $absent ?></span>
                    <span class="text-slate-400 dark:text-slate-500 font-semibold">Total: <?= $total ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
