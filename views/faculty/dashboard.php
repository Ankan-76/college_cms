<?php
// views/faculty/dashboard.php — Enhanced Modern Faculty Dashboard
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('FACULTY');
$pageTitle = 'Faculty Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$facultyProfileId = (int)($_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'] ?? 0);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// 1. Fetch Faculty Profile Information
$stmtFaculty = $db->prepare("
    SELECT t.* 
    FROM teachers t 
    WHERE t.id = ?
");
$stmtFaculty->execute([$facultyProfileId]);
$faculty = $stmtFaculty->fetch() ?: [];

$facultyName = $faculty['name'] ?? $_SESSION['name'] ?? 'Faculty Member';
$facultyEmail = $faculty['email'] ?? $_SESSION['email'] ?? '';
$facultyDesignation = $faculty['designation'] ?? 'Lecturer';
$facultyQualification = $faculty['qualification'] ?? 'M.Tech / Ph.D';
$facultyStatus = $faculty['status'] ?? 'ACTIVE';

// Fetch primary department
$deptStmt = $db->prepare("
    SELECT d.dept_name, d.dept_code 
    FROM teacher_departments td 
    JOIN departments d ON td.department_id = d.id 
    WHERE td.teacher_id = ? 
    LIMIT 1
");
$deptStmt->execute([$facultyProfileId]);
$facultyDept = $deptStmt->fetch();

if (!$facultyDept) {
    // Fallback: check via course assignments
    $deptStmtFallback = $db->prepare("
        SELECT d.dept_name, d.dept_code 
        FROM course_assignments ca 
        JOIN courses c ON ca.course_id = c.id 
        JOIN departments d ON c.department_id = d.id 
        WHERE ca.faculty_id = ? 
        LIMIT 1
    ");
    $deptStmtFallback->execute([$facultyProfileId]);
    $facultyDept = $deptStmtFallback->fetch();
}
$deptName = $facultyDept['dept_name'] ?? 'Academic Department';
$deptCode = $facultyDept['dept_code'] ?? 'ACAD';

// 2. Fetch Assigned Courses
$coursesStmt = $db->prepare("
    SELECT c.id, c.course_code, c.course_name, c.credits, d.dept_name, d.dept_code, s.semester_number
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    WHERE ca.faculty_id = ?
    ORDER BY s.semester_number ASC, c.course_code ASC
");
$coursesStmt->execute([$facultyProfileId]);
$assignedCourses = $coursesStmt->fetchAll();
$activeCoursesCount = count($assignedCourses);

// 3. Count Enrolled Students under assigned courses
$studentsStmt = $db->prepare("
    SELECT COUNT(DISTINCT s.id)
    FROM students s
    JOIN courses c ON s.department_id = c.department_id AND s.semester_id = c.semester_id
    JOIN course_assignments ca ON c.id = ca.course_id
    WHERE ca.faculty_id = ? AND s.status = 'ACTIVE'
");
$studentsStmt->execute([$facultyProfileId]);
$enrolledStudentsCount = (int)$studentsStmt->fetchColumn();

// 4. Attendance Aggregate Metrics
$attStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT CONCAT(course_id, '_', date)) as total_sessions,
        SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
    FROM attendance
    WHERE marked_by = ? OR course_id IN (SELECT course_id FROM course_assignments WHERE faculty_id = ?)
");
$attStmt->execute([$facultyProfileId, $facultyProfileId]);
$attStats = $attStmt->fetch();
$totalAttRecords = (int)($attStats['total_records'] ?? 0);
$totalSessions = (int)($attStats['total_sessions'] ?? 0);
$presentCount = (int)($attStats['present_count'] ?? 0);
$lateCount = (int)($attStats['late_count'] ?? 0);
$attendanceRate = $totalAttRecords > 0 ? round((($presentCount + $lateCount) / $totalAttRecords) * 100) : 0;

// 5. Assignments & Pending Submissions
$assignmentsCount = (int)$db->query("SELECT COUNT(*) FROM assignments WHERE faculty_id = " . (int)$facultyProfileId)->fetchColumn();
$legacyAssessmentsCount = (int)$db->query("SELECT COUNT(*) FROM assessments WHERE faculty_id = " . (int)$facultyProfileId)->fetchColumn();
$totalAssessments = $assignmentsCount + $legacyAssessmentsCount;

$subStmt = $db->prepare("
    SELECT 
        COUNT(sub.id) as total_submissions,
        SUM(CASE WHEN sub.marks_obtained IS NULL THEN 1 ELSE 0 END) as pending_grading
    FROM assignment_submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    WHERE a.faculty_id = ?
");
$subStmt->execute([$facultyProfileId]);
$subStats = $subStmt->fetch();
$totalSubmissions = (int)($subStats['total_submissions'] ?? 0);
$pendingGrading = (int)($subStats['pending_grading'] ?? 0);

// 6. Study Materials & Storage
$matMetaStmt = $db->prepare("
    SELECT COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_bytes
    FROM study_materials 
    WHERE faculty_id = ?
");
$matMetaStmt->execute([$facultyProfileId]);
$matMeta = $matMetaStmt->fetch();
$materialsCount = (int)($matMeta['count'] ?? 0);
$materialsStorageMB = round(($matMeta['total_bytes'] ?? 0) / (1024 * 1024), 2);

// 7. Online Quizzes
$quizMetaStmt = $db->prepare("
    SELECT 
        COUNT(q.id) as total_quizzes,
        (SELECT COUNT(qa.id) FROM quiz_attempts qa JOIN quizzes q2 ON qa.quiz_id = q2.id WHERE q2.faculty_id = ?) as total_attempts
    FROM quizzes q
    WHERE q.faculty_id = ?
");
$quizMetaStmt->execute([$facultyProfileId, $facultyProfileId]);
$quizMeta = $quizMetaStmt->fetch();
$quizzesCount = (int)($quizMeta['total_quizzes'] ?? 0);
$quizAttemptsCount = (int)($quizMeta['total_attempts'] ?? 0);

// 8. Faculty Latest Leave Request
$leaveStmt = $db->prepare("
    SELECT * FROM leave_requests 
    WHERE applicant_id = ? AND applicant_type = 'FACULTY' 
    ORDER BY created_at DESC LIMIT 1
");
$leaveStmt->execute([$facultyProfileId]);
$latestLeave = $leaveStmt->fetch();

// 9. Today's Class Schedule (Timetable)
$todayDay = date('l'); // e.g. "Monday", "Wednesday"
$timetableStmt = $db->prepare("
    SELECT t.*, c.course_code, c.course_name, d.dept_code, sem.semester_number
    FROM timetables t
    JOIN courses c ON t.course_id = c.id
    JOIN departments d ON t.department_id = d.id
    JOIN semesters sem ON t.semester_id = sem.id
    WHERE (t.faculty_id = ? OR t.course_id IN (SELECT course_id FROM course_assignments WHERE faculty_id = ?))
      AND t.day_of_week = ?
    ORDER BY t.start_time ASC
");
$timetableStmt->execute([$facultyProfileId, $facultyProfileId, $todayDay]);
$todaySchedule = $timetableStmt->fetchAll();

// 10. Pending Submissions Queue (Top 4)
$pendingQueueStmt = $db->prepare("
    SELECT sub.id as submission_id, sub.submitted_at, sub.is_late, sub.file_name,
           s.name as student_name, s.roll_number,
           a.id as assignment_id, a.title as assignment_title, a.max_marks,
           c.course_code
    FROM assignment_submissions sub
    JOIN assignments a ON sub.assignment_id = a.id
    JOIN students s ON sub.student_id = s.id
    JOIN courses c ON a.course_id = c.id
    WHERE a.faculty_id = ? AND sub.marks_obtained IS NULL
    ORDER BY sub.submitted_at ASC
    LIMIT 4
");
$pendingQueueStmt->execute([$facultyProfileId]);
$pendingSubmissions = $pendingQueueStmt->fetchAll();

// 11. Recent Assignments Created (Top 4)
$recentAssignmentsStmt = $db->prepare("
    SELECT a.*, c.course_code, c.course_name,
           (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id) as sub_count,
           (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id AND sub.marks_obtained IS NOT NULL) as graded_count
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE a.faculty_id = ?
    ORDER BY a.created_at DESC
    LIMIT 4
");
$recentAssignmentsStmt->execute([$facultyProfileId]);
$recentAssignments = $recentAssignmentsStmt->fetchAll();

// 12. Recent Materials (Top 4)
$recentMaterialsStmt = $db->prepare("
    SELECT m.*, c.course_code, c.course_name
    FROM study_materials m
    JOIN courses c ON m.course_id = c.id
    WHERE m.faculty_id = ?
    ORDER BY m.uploaded_at DESC
    LIMIT 4
");
$recentMaterialsStmt->execute([$facultyProfileId]);
$recentMaterials = $recentMaterialsStmt->fetchAll();

// 13. Recent Campus Circulars & Faculty Notices (Top 4)
$noticesStmt = $db->prepare("
    SELECT n.*, a.name as author_name
    FROM notices n
    LEFT JOIN admins a ON n.created_by = a.id
    WHERE n.target_role IN ('ALL', 'FACULTY')
    ORDER BY n.is_pinned DESC, n.created_at DESC
    LIMIT 4
");
$noticesStmt->execute();
$recentNotices = $noticesStmt->fetchAll();

// 14. Chart Data Preparation
// Coursework / Assignments per course
$assessDataStmt = $db->prepare("
    SELECT c.course_code, 
           (
               (SELECT COUNT(*) FROM assignments a WHERE a.course_id = c.id AND a.faculty_id = :fid1) +
               (SELECT COUNT(*) FROM assessments ass WHERE ass.course_id = c.id AND ass.faculty_id = :fid2)
           ) as total_coursework
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    WHERE ca.faculty_id = :fid3
    GROUP BY c.id
");
$assessDataStmt->execute([
    'fid1' => $facultyProfileId,
    'fid2' => $facultyProfileId,
    'fid3' => $facultyProfileId
]);
$assessChartRows = $assessDataStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($assessChartRows)) {
    $assessChartLabels = json_encode(['No Assigned Courses']);
    $assessChartCounts = json_encode([0]);
} else {
    $assessChartLabels = json_encode(array_column($assessChartRows, 'course_code'));
    $assessChartCounts = json_encode(array_map('intval', array_column($assessChartRows, 'total_coursework')));
}

// Materials per course
$matDataStmt = $db->prepare("
    SELECT c.course_code, COUNT(m.id) as material_count
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    LEFT JOIN study_materials m ON c.id = m.course_id AND m.faculty_id = :fid1
    WHERE ca.faculty_id = :fid2
    GROUP BY c.id
");
$matDataStmt->execute([
    'fid1' => $facultyProfileId,
    'fid2' => $facultyProfileId
]);
$matChartRows = $matDataStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($matChartRows)) {
    $matChartLabels = json_encode(['No Assigned Courses']);
    $matChartCounts = json_encode([0]);
} else {
    $matChartLabels = json_encode(array_column($matChartRows, 'course_code'));
    $matChartCounts = json_encode(array_map('intval', array_column($matChartRows, 'material_count')));
}

// Time-aware greeting
$currentHour = (int)date('G');
if ($currentHour < 12) {
    $greeting = "Good Morning";
} elseif ($currentHour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ═══════════ 1. WELCOME HERO SECTION ═══════════ -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900/10 via-slate-900/5 to-purple-900/10 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-indigo-950/40 backdrop-blur-xl border border-slate-200/80 dark:border-slate-700/60 shadow-xl shadow-indigo-500/5">
            <!-- Decorative Ambient Glows -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/15 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-purple-500/15 dark:bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 p-6 sm:p-8 lg:p-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <!-- User Profile & Greeting -->
                <div class="flex items-start sm:items-center gap-4 sm:gap-5">
                    <div class="relative shrink-0">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-lg shadow-indigo-500/30 overflow-hidden ring-4 ring-white/70 dark:ring-slate-700/70 border-2 border-white/40">
                            <?php if (!empty($faculty['profile_pic'])): ?>
                                <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($faculty['profile_pic']) ?>" alt="<?= htmlspecialchars($facultyName) ?>" class="w-full h-full object-cover rounded-full">
                            <?php else: ?>
                                <?= strtoupper(substr($facultyName, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-800 shadow-sm" title="Active Duty"></span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                <?= $greeting ?>, <?= htmlspecialchars($facultyName) ?>!
                            </h1>
                            <span class="inline-block text-xl animate-bounce">👋</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 max-w-xl text-xs sm:text-sm font-medium leading-relaxed">
                            <span class="font-bold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($facultyDesignation) ?></span> &bull; 
                            <?= htmlspecialchars($deptName) ?> (<?= htmlspecialchars($deptCode) ?>) &bull; 
                            <?= htmlspecialchars($facultyQualification) ?>
                        </p>
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-500"></i>
                                <?= htmlspecialchars($facultyEmail) ?>
                            </span>
                            <span class="hidden sm:inline">&bull;</span>
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="hash" class="w-3.5 h-3.5 text-indigo-500"></i>
                                Faculty ID #<?= $facultyProfileId ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Header Actions & Live Indicator -->
                <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-3 shrink-0 w-full lg:w-auto pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-200/60 dark:border-slate-700/60">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/80 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-600/50 shadow-sm backdrop-blur-md">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <?= date('l, M j, Y') ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active Duty
                        </span>
                    </div>

                    <!-- Quick CTA buttons -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <a href="<?= $base ?>/views/faculty/take_attendance.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95 shrink-0">
                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                            <span>Mark Attendance</span>
                        </a>
                        <a href="<?= $base ?>/views/faculty/assignments.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 shadow-sm transition-all hover:scale-105 active:scale-95 shrink-0">
                            <i data-lucide="file-plus-2" class="w-4 h-4 text-indigo-500"></i>
                            <span>New Assignment</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════ 2. CONTEXTUAL ACTION BANNER ═══════════ -->
        <?php if ($pendingGrading > 0): ?>
            <div class="relative overflow-hidden p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-indigo-500/15 dark:from-amber-950/50 dark:via-orange-950/30 dark:to-indigo-950/30 border border-amber-300/80 dark:border-amber-700/60 shadow-lg shadow-amber-500/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                <?= $pendingGrading ?> <?= $pendingGrading === 1 ? 'Submission Awaiting Evaluation' : 'Submissions Awaiting Evaluation' ?>
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-200/80 text-amber-900 dark:bg-amber-800/60 dark:text-amber-200">
                                Action Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Students have submitted assignment tasks that are currently waiting for your review and grade marks.
                        </p>
                    </div>
                </div>
                <a href="<?= $base ?>/views/faculty/assignments.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-md shadow-amber-500/25 transition-all hover:scale-105 active:scale-95 shrink-0 group">
                    <span>Evaluate Submissions</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        <?php endif; ?>

        <!-- ═══════════ 3. QUICK ACTIONS HUB ═══════════ -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i> Faculty Operations Hub
                </h2>
                <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Frequent academic workflows</span>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
                
                <!-- 1. Take Attendance -->
                <a href="<?= $base ?>/views/faculty/take_attendance.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-indigo-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Attendance</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Record Roll Ledger</span>
                </a>

                <!-- 2. Assignments Portal -->
                <a href="<?= $base ?>/views/faculty/assignments.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-purple-500/10 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="file-up" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Assignments</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Tasks & Deadlines</span>
                </a>

                <!-- 3. Study Materials -->
                <a href="<?= $base ?>/views/faculty/study_materials.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-emerald-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="folder-up" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Study Notes</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Upload Syllabus Files</span>
                </a>

                <!-- 4. Online Quizzes -->
                <a href="<?= $base ?>/views/faculty/quizzes.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-pink-500/10 hover:border-pink-400 dark:hover:border-pink-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-pink-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="brain" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Online Quizzes</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">MCQ Assessments</span>
                </a>

                <!-- 5. Evaluate Submissions -->
                <a href="<?= $base ?>/views/faculty/manage_marks.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-amber-500/10 hover:border-amber-400 dark:hover:border-amber-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-amber-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Manage Marks</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Grades & Scoring</span>
                </a>

                <!-- 6. Apply Leave -->
                <a href="<?= $base ?>/views/faculty/apply_leave.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-cyan-500/10 hover:border-cyan-400 dark:hover:border-cyan-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-cyan-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="calendar-off" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Apply Leave</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Absence Request</span>
                </a>

            </div>
        </div>

        <!-- ═══════════ 4. KEY PERFORMANCE METRICS ═══════════ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 sm:gap-5">
            
            <!-- Metric 1: Assigned Courses -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Assigned Courses</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $activeCoursesCount ?></h3>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/my_subjects.php" class="hover:underline flex items-center gap-1">
                            <span>View Subjects</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 2: Enrolled Students -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-emerald-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Students</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $enrolledStudentsCount ?></h3>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/my_students.php" class="hover:underline flex items-center gap-1">
                            <span>Roster List</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 3: Attendance Presence Rate -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-teal-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Class Attendance</span>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $attendanceRate ?>%</h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $totalSessions ?> <?= $totalSessions === 1 ? 'day' : 'days' ?></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-teal-600 dark:text-teal-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/view_attendance.php" class="hover:underline flex items-center gap-1">
                            <span>Logs Matrix</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 4: Assignments & Submissions -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-purple-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Assignments</span>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $assignmentsCount ?></h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $totalSubmissions ?> subs</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-purple-600 dark:text-purple-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/assignments.php" class="hover:underline flex items-center gap-1">
                            <span><?= $pendingGrading ?> pending review</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 5: Study Materials -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-blue-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Study Materials</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="folder-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $materialsCount ?></h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $materialsStorageMB ?> MB</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-blue-600 dark:text-blue-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/study_materials.php" class="hover:underline flex items-center gap-1">
                            <span>Resource Hub</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 6: Online Quizzes -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-rose-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Online Quizzes</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="brain" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $quizzesCount ?></h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $quizAttemptsCount ?> attempts</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-rose-600 dark:text-rose-400 font-semibold">
                        <a href="<?= $base ?>/views/faculty/quizzes.php" class="hover:underline flex items-center gap-1">
                            <span>Manage Quizzes</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══════════ 5. MODERNIZED CHARTS SECTION ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="faculty-charts-container">
            
            <!-- Chart 1: Coursework per Course (Bar Chart with Canvas Linear Gradient) -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col relative chart-card" id="faculty-assess-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-500"></i> Coursework & Assignments by Subject
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Distribution of coursework deliverables across assigned courses</p>
                    </div>
                    <div class="relative">
                        <button type="button" onclick="toggleExportMenu('exportMenuFac1')" class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Export Chart Options">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuFac1" class="hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-20 py-1.5 backdrop-blur-md">
                            <button type="button" onclick="exportChartAsPDF('faculty-assess-card', 'Coursework_Distribution')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i> Save as PDF
                            </button>
                            <button type="button" onclick="exportChartAsExcel(window.facultyChartData.assessments, 'Coursework_Distribution')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5 text-emerald-500"></i> Save as Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative flex-1 min-h-[280px] w-full flex items-center justify-center">
                    <canvas id="facultyAssessChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Materials per Course (Modern Doughnut with Center Metric) -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col relative chart-card" id="faculty-mat-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="pie-chart" class="w-5 h-5 text-emerald-500"></i> Study Materials & Syllabus Distribution
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ratio of shared syllabus notes, slides, and reference files</p>
                    </div>
                    <div class="relative">
                        <button type="button" onclick="toggleExportMenu('exportMenuFac2')" class="p-2 rounded-xl text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Export Chart Options">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuFac2" class="hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-20 py-1.5 backdrop-blur-md">
                            <button type="button" onclick="exportChartAsPDF('faculty-mat-card', 'Materials_Distribution')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i> Save as PDF
                            </button>
                            <button type="button" onclick="exportChartAsExcel(window.facultyChartData.materials, 'Materials_Distribution')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5 text-emerald-500"></i> Save as Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative flex-1 min-h-[280px] w-full flex items-center justify-center pb-2">
                    <canvas id="facultyMatChart"></canvas>
                </div>
            </div>

        </div>

        <script>
            window.facultyChartData = {
                assessments: {
                    labels: <?= $assessChartLabels ?>,
                    data: <?= $assessChartCounts ?>
                },
                materials: {
                    labels: <?= $matChartLabels ?>,
                    data: <?= $matChartCounts ?>
                }
            };
        </script>

        <!-- ═══════════ 6. TIMETABLE SCHEDULE & CAMPUS NOTICES ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Today's Schedule -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="calendar-clock" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Today's Class Schedule</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= $todayDay ?>, <?= date('M j') ?></p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/faculty/timetable.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                        Full Timetable <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($todaySchedule)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 flex items-center justify-center mb-3">
                                <i data-lucide="coffee" class="w-7 h-7"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Lectures Scheduled Today</h4>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 max-w-xs px-4">
                                You have no active lecture slots assigned for <?= $todayDay ?>. Ideal time for syllabus planning or reviewing coursework.
                            </p>
                            <a href="<?= $base ?>/views/faculty/timetable.php" class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors">
                                View Full Week Matrix
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaySchedule as $slot): ?>
                            <?php 
                                $startTimeFormatted = date('g:i A', strtotime($slot['start_time']));
                                $endTimeFormatted = date('g:i A', strtotime($slot['end_time']));
                            ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:border-indigo-300 dark:hover:border-indigo-600/50 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="px-2.5 py-2 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-center shrink-0 min-w-[70px]">
                                        <span class="block text-xs font-black"><?= $startTimeFormatted ?></span>
                                        <span class="block text-[10px] opacity-75 font-medium"><?= $endTimeFormatted ?></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($slot['course_code']) ?></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                                Room <?= htmlspecialchars($slot['room_number'] ?? 'TBD') ?>
                                            </span>
                                        </div>
                                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 line-clamp-1"><?= htmlspecialchars($slot['course_name']) ?></p>
                                    </div>
                                </div>
                                <a href="<?= $base ?>/views/faculty/take_attendance.php?course_id=<?= (int)$slot['course_id'] ?>" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shrink-0 shadow-sm transition-transform hover:scale-105">
                                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                    <span>Take Attendance</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campus & Faculty Notices -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Campus Circulars & Notices</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Official administrative broadcasts</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/faculty/notices.php" class="text-xs font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 flex items-center gap-1">
                        View All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($recentNotices)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300 dark:text-slate-600 mb-2"></i>
                            <p class="text-xs font-medium text-slate-500">No active circulars at this moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentNotices as $notice): ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 mt-0.5">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <p class="text-xs font-black text-slate-900 dark:text-white line-clamp-1">
                                            <?= htmlspecialchars($notice['title']) ?>
                                        </p>
                                        <?php if (!empty($notice['is_pinned'])): ?>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300">
                                                <i data-lucide="pin" class="w-2.5 h-2.5"></i> PINNED
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">
                                        <?= strip_tags($notice['content']) ?>
                                    </p>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-400 font-medium">
                                        <span><?= date('M d, Y', strtotime($notice['created_at'])) ?></span>
                                        <span>&bull;</span>
                                        <span>By <?= htmlspecialchars($notice['author_name'] ?? 'Admin') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- ═══════════ 7. COURSEWORK DELIVERABLES & STUDY MATERIALS ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Recent Assignments & Submissions -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="award" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Coursework & Assignments</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Recently published student tasks</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/faculty/assignments.php" class="text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 flex items-center gap-1">
                        Portal <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($recentAssignments)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-400 flex items-center justify-center mb-3">
                                <i data-lucide="file-plus-2" class="w-6 h-6"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-3">No assignments created yet.</p>
                            <a href="<?= $base ?>/views/faculty/assignments.php" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-700 transition-colors shadow-sm">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Create First Assignment
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentAssignments as $assignment): ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 font-black text-xs">
                                        <?= htmlspecialchars($assignment['course_code']) ?>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 mb-0.5">
                                            <?= htmlspecialchars($assignment['title']) ?>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            Due: <?= date('M d, g:i A', strtotime($assignment['deadline'])) ?> &bull; Max: <?= (int)$assignment['max_marks'] ?> pts
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50">
                                        <?= (int)$assignment['sub_count'] ?> submitted
                                    </span>
                                    <a href="<?= $base ?>/views/faculty/view_submissions.php?assignment_id=<?= (int)$assignment['id'] ?>" class="text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 hover:underline">
                                        Review
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Study Materials -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="folder-up" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Recent Study Materials</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Syllabus notes and references</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/faculty/study_materials.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                        Browse All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($recentMaterials)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-400 flex items-center justify-center mb-3">
                                <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-3">No study materials uploaded yet.</p>
                            <a href="<?= $base ?>/views/faculty/study_materials.php" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Upload Resource
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentMaterials as $mat): ?>
                            <?php $mbSize = round(($mat['file_size'] ?? 0) / (1024 * 1024), 2); ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <i data-lucide="file-check" class="w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 mb-0.5">
                                            <?= htmlspecialchars($mat['title']) ?>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            <?= htmlspecialchars($mat['course_code']) ?> &bull; <?= $mbSize ?> MB &bull; <?= date('M d', strtotime($mat['uploaded_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?= $base ?>/uploads/materials/<?= htmlspecialchars($mat['file_path']) ?>" download class="p-2 rounded-xl text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition-colors shrink-0" title="Download Resource">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
    
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mb-4 sm:-mb-6 lg:-mb-8 mt-12">
        <?php require_once __DIR__ . '/../../includes/main_footer.php'; ?>
    </div>
</main>

<script src="<?= $base ?>/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initFacultyCharts === 'function') {
            initFacultyCharts();
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
