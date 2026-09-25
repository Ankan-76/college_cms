<?php
// views/student/dashboard.php — Modernized Student Academic Control Center
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Student Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = (int)$_SESSION['user_id'];
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// ══════════════════════════════════════════════════════════
// 1. STUDENT IDENTITY & ACADEMIC REGISTRATION
// ══════════════════════════════════════════════════════════
$stmtStudent = $db->prepare("
    SELECT s.*, d.dept_name, d.dept_code, sem.semester_number, sem.academic_year
    FROM students s
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN semesters sem ON s.semester_id = sem.id
    WHERE s.id = ?
");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch() ?: [];

$departmentId = (int)($student['department_id'] ?? 0);
$semesterId = (int)($student['semester_id'] ?? 0);
$studentName = $student['name'] ?? $_SESSION['name'] ?? 'Student';
$studentEmail = $student['email'] ?? $_SESSION['email'] ?? '';
$studentPhone = $student['phone'] ?? '';
$deptName = $student['dept_name'] ?? 'Academic Department';
$deptCode = $student['dept_code'] ?? 'ACAD';
$semNumber = (int)($student['semester_number'] ?? 1);
$rollNumber = $student['roll_number'] ?? 'N/A';
$regNumber = $student['registration_number'] ?? 'N/A';
$academicYear = $student['academic_year'] ?? (date('Y') . '-' . (date('Y') + 1));
$studentStatus = strtoupper($student['status'] ?? 'ACTIVE');

// ══════════════════════════════════════════════════════════
// 2. ENROLLED COURSES & TOTAL CREDITS
// ══════════════════════════════════════════════════════════
$coursesStmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM study_materials sm WHERE sm.course_id = c.id) as material_count,
           (SELECT COUNT(*) FROM assignments a WHERE a.course_id = c.id) as assignment_count,
           (SELECT COUNT(*) FROM quizzes q WHERE q.course_id = c.id AND q.status = 'PUBLISHED') as quiz_count
    FROM courses c
    WHERE c.department_id = ? AND c.semester_id = ?
    ORDER BY c.course_code ASC
");
$coursesStmt->execute([$departmentId, $semesterId]);
$enrolledCourses = $coursesStmt->fetchAll();
$totalSubjects = count($enrolledCourses);
$totalCredits = array_sum(array_column($enrolledCourses, 'credits'));

// ══════════════════════════════════════════════════════════
// 3. ATTENDANCE METRICS & SUBJECT-WISE BREAKDOWN
// ══════════════════════════════════════════════════════════
$attendanceStats = $db->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
    FROM attendance
    WHERE student_id = ?
");
$attendanceStats->execute([$studentId]);
$attData = $attendanceStats->fetch() ?: [];

$totalRecords = (int)($attData['total_records'] ?? 0);
$presentCount = (int)($attData['present_count'] ?? 0);
$lateCount = (int)($attData['late_count'] ?? 0);
$absentCount = (int)($attData['absent_count'] ?? 0);
$effectivePresent = $presentCount + $lateCount;
$overallAttendance = $totalRecords > 0 ? round(($effectivePresent / $totalRecords) * 100) : 100;

// Subject-wise attendance calculation
$subAttStmt = $db->prepare("
    SELECT c.id, c.course_code, c.course_name, c.credits,
           COUNT(a.id) as total_sessions,
           SUM(CASE WHEN a.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN a.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
           SUM(CASE WHEN a.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
    FROM courses c
    LEFT JOIN attendance a ON c.id = a.course_id AND a.student_id = ?
    WHERE c.department_id = ? AND c.semester_id = ?
    GROUP BY c.id
    ORDER BY c.course_code ASC
");
$subAttStmt->execute([$studentId, $departmentId, $semesterId]);
$subjectAttendanceList = $subAttStmt->fetchAll();

// Attendance advisory calculation
$attendanceTarget = 75;
$consecutiveClassesNeeded = 0;
$canSkipClasses = 0;
if ($totalRecords > 0) {
    if ($overallAttendance < $attendanceTarget) {
        // (effectivePresent + x) / (totalRecords + x) >= 0.75 => x = ceil((0.75 * totalRecords - effectivePresent) / 0.25)
        $consecutiveClassesNeeded = (int)ceil((0.75 * $totalRecords - $effectivePresent) / 0.25);
        if ($consecutiveClassesNeeded < 1) $consecutiveClassesNeeded = 1;
    } else {
        // effectivePresent / (totalRecords + y) >= 0.75 => y = floor((effectivePresent - 0.75 * totalRecords) / 0.75)
        $canSkipClasses = (int)floor(($effectivePresent - 0.75 * $totalRecords) / 0.75);
        if ($canSkipClasses < 0) $canSkipClasses = 0;
    }
}

// ══════════════════════════════════════════════════════════
// 4. ASSIGNMENTS & TASKS
// ══════════════════════════════════════════════════════════
$assignStmt = $db->prepare("
    SELECT a.*, c.course_code, c.course_name,
           sub.id as submission_id, sub.marks_obtained, sub.submitted_at, sub.is_late, sub.feedback
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
    WHERE c.department_id = ? AND c.semester_id = ?
    ORDER BY a.deadline ASC
");
$assignStmt->execute([$studentId, $departmentId, $semesterId]);
$allAssignments = $assignStmt->fetchAll();

$totalAssignmentsCount = count($allAssignments);
$submittedAssignmentsCount = 0;
$pendingAssignmentsList = [];
$gradedAssignmentsList = [];

foreach ($allAssignments as $asg) {
    if (!empty($asg['submission_id'])) {
        $submittedAssignmentsCount++;
        if ($asg['marks_obtained'] !== null) {
            $gradedAssignmentsList[] = $asg;
        }
    } else {
        $pendingAssignmentsList[] = $asg;
    }
}
$pendingAssignmentsCount = count($pendingAssignmentsList);

// ══════════════════════════════════════════════════════════
// 5. ONLINE QUIZZES & TESTS
// ══════════════════════════════════════════════════════════
$quizStmt = $db->prepare("
    SELECT q.*, c.course_code, c.course_name,
           (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
           (SELECT COALESCE(SUM(qq2.marks), 0) FROM quiz_questions qq2 WHERE qq2.quiz_id = q.id) as total_quiz_marks,
           qa.id as attempt_id, qa.score, qa.total_marks as attempt_total_marks, qa.started_at as attempted_at
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE c.department_id = ? AND c.semester_id = ? AND q.status = 'PUBLISHED'
    ORDER BY q.end_time ASC
");
$quizStmt->execute([$studentId, $departmentId, $semesterId]);
$allQuizzes = $quizStmt->fetchAll();

$totalQuizzesCount = count($allQuizzes);
$attemptedQuizzesCount = 0;
$availableQuizzesList = [];

foreach ($allQuizzes as $qz) {
    if (!empty($qz['attempt_id'])) {
        $attemptedQuizzesCount++;
    } else {
        $availableQuizzesList[] = $qz;
    }
}
$availableQuizzesCount = count($availableQuizzesList);

// ══════════════════════════════════════════════════════════
// 6. STUDY MATERIALS & SYLLABUS FILES
// ══════════════════════════════════════════════════════════
$matStmt = $db->prepare("
    SELECT sm.*, c.course_code, c.course_name, t.name as faculty_name
    FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    LEFT JOIN teachers t ON sm.faculty_id = t.id
    WHERE c.department_id = ? AND c.semester_id = ?
    ORDER BY sm.uploaded_at DESC
    LIMIT 4
");
$matStmt->execute([$departmentId, $semesterId]);
$recentMaterials = $matStmt->fetchAll();

$matTotalStmt = $db->prepare("
    SELECT COUNT(*) FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    WHERE c.department_id = ? AND c.semester_id = ?
");
$matTotalStmt->execute([$departmentId, $semesterId]);
$totalMaterials = (int)$matTotalStmt->fetchColumn();

// ══════════════════════════════════════════════════════════
// 7. TODAY'S CLASS SCHEDULE (LIVE TIMETABLE)
// ══════════════════════════════════════════════════════════
$todayDay = date('l'); // e.g. "Monday"
$currentTimeFormatted = date('H:i:s');

$todayClasses = $db->prepare("
    SELECT t.*, c.course_code, c.course_name, fp.name as faculty_name
    FROM timetables t
    JOIN courses c ON t.course_id = c.id
    LEFT JOIN course_assignments ca ON c.id = ca.course_id
    LEFT JOIN teachers fp ON ca.faculty_id = fp.id
    WHERE t.department_id = ? AND t.semester_id = ? AND t.day_of_week = ?
    ORDER BY t.start_time ASC
");
$todayClasses->execute([$departmentId, $semesterId, $todayDay]);
$todaySchedule = $todayClasses->fetchAll();

// ══════════════════════════════════════════════════════════
// 8. RECENT GRADES & ASSESSMENT MARKS
// ══════════════════════════════════════════════════════════
$gradesStmt = $db->prepare("
    SELECT am.marks_obtained, am.remarks, a.title as assessment_title, a.max_marks, 
           c.course_code, c.course_name, a.created_at
    FROM assessment_marks am
    JOIN assessments a ON am.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE am.student_id = ?
    ORDER BY a.created_at DESC
    LIMIT 4
");
$gradesStmt->execute([$studentId]);
$recentGrades = $gradesStmt->fetchAll();

// ══════════════════════════════════════════════════════════
// 9. CAMPUS NOTICES & ADMIN BROADCASTS
// ══════════════════════════════════════════════════════════
$noticeStmt = $db->prepare("
    SELECT n.*, a.name as author_name
    FROM notices n
    LEFT JOIN admins a ON n.created_by = a.id
    WHERE n.target_role IN ('ALL', 'STUDENT')
    ORDER BY n.is_pinned DESC, n.created_at DESC
    LIMIT 4
");
$noticeStmt->execute();
$recentNotices = $noticeStmt->fetchAll();

// Admin Broadcasts for this student
$broadcastStmt = $db->prepare("
    SELECT * FROM admin_broadcasts
    WHERE (target_type = 'ALL' OR target_type = 'ALL_STUDENTS' OR (target_type = 'STUDENT' AND target_id = ?))
      AND is_unsent = 0
    ORDER BY is_pinned DESC, created_at DESC
    LIMIT 2
");
$broadcastStmt->execute([$studentId]);
$recentBroadcasts = $broadcastStmt->fetchAll();

// ══════════════════════════════════════════════════════════
// 10. LEAVES & MESSAGING
// ══════════════════════════════════════════════════════════
$leaveStmt = $db->prepare("
    SELECT * FROM leave_requests 
    WHERE applicant_id = ? AND applicant_type = 'STUDENT' 
    ORDER BY created_at DESC LIMIT 1
");
$leaveStmt->execute([$studentId]);
$latestLeave = $leaveStmt->fetch();

$msgStmt = $db->prepare("
    SELECT COUNT(*) FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    WHERE c.student_id = ? AND m.sender_type = 'FACULTY' AND m.is_read = 0
");
$msgStmt->execute([$studentId]);
$unreadFacultyMsgs = (int)$msgStmt->fetchColumn();

// ══════════════════════════════════════════════════════════
// 11. CHART DATA PREPARATION
// ══════════════════════════════════════════════════════════
$attLabels = [];
$attDataPoints = [];
$attDetails = [];

foreach ($subjectAttendanceList as $sub) {
    $attLabels[] = $sub['course_code'];
    $subTotal = (int)$sub['total_sessions'];
    $subPres = (int)$sub['present_count'] + (int)$sub['late_count'];
    $subPct = $subTotal > 0 ? round(($subPres / $subTotal) * 100) : 100;
    $attDataPoints[] = $subPct;
    $attDetails[] = [
        'code' => $sub['course_code'],
        'name' => $sub['course_name'],
        'total' => $subTotal,
        'present' => $subPres,
        'late' => (int)$sub['late_count'],
        'absent' => (int)$sub['absent_count'],
        'pct' => $subPct
    ];
}

if (empty($attLabels)) {
    $attLabels = ['General'];
    $attDataPoints = [$overallAttendance];
    $attDetails = [[
        'code' => 'GEN',
        'name' => 'General Academic Attendance',
        'total' => $totalRecords,
        'present' => $effectivePresent,
        'late' => $lateCount,
        'absent' => $absentCount,
        'pct' => $overallAttendance
    ]];
}

// Performance chart data
$perfLabels = [];
$perfDataPoints = [];

// Populate from recent grades
foreach ($recentGrades as $g) {
    $maxM = (float)($g['max_marks'] ?? 0);
    $obtM = (float)($g['marks_obtained'] ?? 0);
    $pct = $maxM > 0 ? round(($obtM / $maxM) * 100) : 0;
    $perfLabels[] = $g['course_code'] . ' (' . substr($g['assessment_title'], 0, 10) . ')';
    $perfDataPoints[] = $pct;
}

// If empty, fallback to course credit distribution or baseline
if (empty($perfLabels)) {
    if (!empty($enrolledCourses)) {
        foreach ($enrolledCourses as $ec) {
            $perfLabels[] = $ec['course_code'];
            $perfDataPoints[] = ((int)$ec['credits']) * 25; // Visual proxy for credit weightage
        }
    } else {
        $perfLabels = ['Academic Progress'];
        $perfDataPoints = [100];
    }
}

// ══════════════════════════════════════════════════════════
// 12. TIME-AWARE GREETING
// ══════════════════════════════════════════════════════════
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
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8 animate-fade-in-up">
        
        <!-- ═══════════ 1. WELCOME HERO SECTION ═══════════ -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900/10 via-slate-900/5 to-purple-900/10 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-indigo-950/40 backdrop-blur-xl border border-slate-200/80 dark:border-slate-700/60 shadow-xl shadow-indigo-500/5">
            <!-- Decorative Ambient Glows -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/15 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-purple-500/15 dark:bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 p-6 sm:p-8 lg:p-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <!-- User Profile & Greeting -->
                <div class="flex items-start sm:items-center gap-4 sm:gap-6">
                    <div class="relative shrink-0">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-indigo-600 via-indigo-700 to-purple-600 text-white flex items-center justify-center font-black text-2xl sm:text-3xl shadow-lg shadow-indigo-500/30 overflow-hidden ring-4 ring-white/70 dark:ring-slate-700/70 border-2 border-white/40">
                            <?php if (!empty($student['profile_pic'])): ?>
                                <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($student['profile_pic']) ?>" alt="<?= htmlspecialchars($studentName) ?>" class="w-full h-full object-cover rounded-full">
                            <?php else: ?>
                                <?= strtoupper(substr($studentName, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-800 shadow-sm" title="Active Account"></span>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                <?= $greeting ?>, <?= htmlspecialchars($studentName) ?>!
                            </h1>
                            <span class="inline-block text-xl animate-bounce">👋</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 max-w-xl text-xs sm:text-sm font-medium leading-relaxed flex flex-wrap items-center gap-2">
                            <span class="font-bold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($deptName) ?> (<?= htmlspecialchars($deptCode) ?>)</span>
                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-500"></i> Semester <?= $semNumber ?></span>
                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="award" class="w-3.5 h-3.5 text-indigo-500"></i> <?= $totalCredits ?> Total Credits</span>
                        </p>
                        
                        <!-- Badges Ribbon -->
                        <div class="flex flex-wrap items-center gap-2.5 mt-2.5 text-xs text-slate-500 dark:text-slate-400 font-medium">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/70 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                                <i data-lucide="hash" class="w-3 h-3 text-indigo-500"></i>
                                Roll: <strong class="text-slate-800 dark:text-slate-200"><?= htmlspecialchars($rollNumber) ?></strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/70 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
                                <i data-lucide="id-card" class="w-3 h-3 text-indigo-500"></i>
                                Reg: <strong class="text-slate-800 dark:text-slate-200"><?= htmlspecialchars($regNumber) ?></strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60 shadow-sm">
                                <i data-lucide="calendar" class="w-3 h-3 text-indigo-500"></i>
                                <?= htmlspecialchars($academicYear) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Header Actions & Live Indicator -->
                <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-3 shrink-0 w-full lg:w-auto pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-200/60 dark:border-slate-700/60">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/80 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-600/50 shadow-sm backdrop-blur-md">
                            <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <?= date('l, M j, Y') ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <?= $studentStatus ?>
                        </span>
                    </div>

                    <!-- Quick CTA buttons -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <a href="<?= $base ?>/views/student/my_timetable.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95 shrink-0">
                            <i data-lucide="clock" class="w-4 h-4"></i>
                            <span>My Timetable</span>
                        </a>
                        <a href="<?= $base ?>/views/student/assignments.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 shadow-sm transition-all hover:scale-105 active:scale-95 shrink-0">
                            <i data-lucide="file-up" class="w-4 h-4 text-indigo-500"></i>
                            <span>Assignments</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════ 2. CONTEXTUAL ADVISORY & DEADLINE BANNERS ═══════════ -->
        <?php if (!empty($recentBroadcasts)): ?>
            <?php foreach ($recentBroadcasts as $bcast): ?>
                <div class="relative overflow-hidden p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-indigo-500/15 via-purple-500/10 to-indigo-500/15 dark:from-indigo-950/50 dark:via-purple-950/30 dark:to-indigo-950/30 border border-indigo-300/80 dark:border-indigo-700/60 shadow-lg shadow-indigo-500/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/30">
                            <i data-lucide="megaphone" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                    Official Campus Announcement
                                </h4>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200">
                                    <?= htmlspecialchars($bcast['priority'] ?? 'BROADCAST') ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-300">
                                <?= htmlspecialchars($bcast['content']) ?>
                            </p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500 shrink-0 font-medium">
                        <?= date('M d, g:i A', strtotime($bcast['created_at'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Attendance Health Alert -->
        <?php if ($totalRecords > 0 && $overallAttendance < 75): ?>
            <div class="relative overflow-hidden p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-amber-500/15 via-rose-500/10 to-amber-500/15 dark:from-amber-950/50 dark:via-rose-950/30 dark:to-amber-950/30 border border-amber-300/80 dark:border-amber-700/60 shadow-lg shadow-amber-500/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-rose-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                Attendance Advisory: <?= $overallAttendance ?>% (Threshold: 75%)
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-200/80 text-amber-900 dark:bg-amber-800/60 dark:text-amber-200">
                                Attention Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Your current attendance is below the mandatory 75% examination eligibility criteria. You need to attend <strong><?= $consecutiveClassesNeeded ?> more consecutive <?= $consecutiveClassesNeeded === 1 ? 'class' : 'classes' ?></strong> without absence to restore safe standing.
                        </p>
                    </div>
                </div>
                <a href="<?= $base ?>/views/student/my_attendance.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-md shadow-amber-500/25 transition-all hover:scale-105 active:scale-95 shrink-0 group">
                    <span>Attendance Ledger</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        <?php elseif ($totalRecords > 0 && $overallAttendance >= 75): ?>
            <div class="relative overflow-hidden p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-emerald-500/10 dark:from-emerald-950/40 dark:via-teal-950/20 dark:to-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                            Attendance in Good Standing: <strong class="text-emerald-600 dark:text-emerald-400"><?= $overallAttendance ?>%</strong> &bull; You meet the semester examination criteria.
                            <?php if ($canSkipClasses > 0): ?>
                                <span class="hidden md:inline text-xs font-normal text-slate-500 dark:text-slate-400">(Safe margin: up to <?= $canSkipClasses ?> <?= $canSkipClasses === 1 ? 'class' : 'classes' ?>)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="<?= $base ?>/views/student/my_attendance.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center gap-1 shrink-0">
                    Detailed Report <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        <?php endif; ?>

        <!-- ═══════════ 3. QUICK OPERATIONS HUB ═══════════ -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i> Student Operations Hub
                </h2>
                <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Frequent academic services</span>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
                
                <!-- 1. Timetable -->
                <a href="<?= $base ?>/views/student/my_timetable.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-indigo-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="calendar-days" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Class Schedule</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Weekly Timetable</span>
                </a>

                <!-- 2. Assignments -->
                <a href="<?= $base ?>/views/student/assignments.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-purple-500/10 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="file-up" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Assignments</span>
                    <span class="text-[10px] text-slate-400 mt-0.5"><?= $pendingAssignmentsCount ?> Pending</span>
                </a>

                <!-- 3. Study Materials -->
                <a href="<?= $base ?>/views/student/study_materials.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-emerald-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="folder-down" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Study Notes</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Syllabus Files</span>
                </a>

                <!-- 4. Online Quizzes -->
                <a href="<?= $base ?>/views/student/quizzes.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-pink-500/10 hover:border-pink-400 dark:hover:border-pink-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-pink-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="brain" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-pink-600 dark:group-hover:text-pink-400 transition-colors">Online Quizzes</span>
                    <span class="text-[10px] text-slate-400 mt-0.5"><?= $availableQuizzesCount ?> Available</span>
                </a>

                <!-- 5. Academic Grades -->
                <a href="<?= $base ?>/views/student/my_grades.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-amber-500/10 hover:border-amber-400 dark:hover:border-amber-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-amber-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Marks & Grades</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Score Ledger</span>
                </a>

                <!-- 6. Apply Leave -->
                <a href="<?= $base ?>/views/student/apply_leave.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-cyan-500/10 hover:border-cyan-400 dark:hover:border-cyan-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-cyan-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="calendar-off" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Apply Leave</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Absence Request</span>
                </a>

            </div>
        </div>

        <!-- ═══════════ 4. KEY ACADEMIC METRICS (6 KPI CARDS) ═══════════ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 sm:gap-5">
            
            <!-- Metric 1: Enrolled Courses -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Enrolled Courses</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $totalSubjects ?></h3>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-indigo-600 dark:text-indigo-400 font-semibold">
                        <a href="<?= $base ?>/views/student/my_subjects.php" class="hover:underline flex items-center gap-1">
                            <span><?= $totalCredits ?> Credits enrolled</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 2: Attendance Rate -->
            <?php
            $attColor = $overallAttendance >= 75 ? 'emerald' : ($overallAttendance >= 50 ? 'amber' : 'rose');
            ?>
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-<?= $attColor ?>-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Attendance</span>
                    <div class="w-10 h-10 rounded-xl bg-<?= $attColor ?>-50 dark:bg-<?= $attColor ?>-950/50 text-<?= $attColor ?>-600 dark:text-<?= $attColor ?>-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $overallAttendance ?>%</h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $effectivePresent ?>/<?= $totalRecords ?></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-<?= $attColor ?>-600 dark:text-<?= $attColor ?>-400 font-semibold">
                        <a href="<?= $base ?>/views/student/my_attendance.php" class="hover:underline flex items-center gap-1">
                            <span><?= $overallAttendance >= 75 ? 'Meets 75% rule' : 'Needs attention' ?></span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 3: Pending Assignments -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-purple-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Assignments</span>
                    <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $pendingAssignmentsCount ?></h3>
                        <span class="text-[11px] font-bold text-slate-500">pending</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-purple-600 dark:text-purple-400 font-semibold">
                        <a href="<?= $base ?>/views/student/assignments.php" class="hover:underline flex items-center gap-1">
                            <span><?= $submittedAssignmentsCount ?> submitted</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 4: Quizzes & Tests -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-rose-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Online Quizzes</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="brain" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $availableQuizzesCount ?></h3>
                        <span class="text-[11px] font-bold text-slate-500">to attempt</span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-rose-600 dark:text-rose-400 font-semibold">
                        <a href="<?= $base ?>/views/student/quizzes.php" class="hover:underline flex items-center gap-1">
                            <span><?= $attemptedQuizzesCount ?> completed</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 5: Study Materials -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-teal-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Study Materials</span>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="folder-check" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= $totalMaterials ?></h3>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-teal-600 dark:text-teal-400 font-semibold">
                        <a href="<?= $base ?>/views/student/study_materials.php" class="hover:underline flex items-center gap-1">
                            <span>Download Files</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Metric 6: Today's Classes / Leaves -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-blue-500/5 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Today's Classes</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white"><?= count($todaySchedule) ?></h3>
                        <span class="text-[11px] font-bold text-slate-500"><?= $todayDay ?></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-blue-600 dark:text-blue-400 font-semibold">
                        <a href="<?= $base ?>/views/student/my_timetable.php" class="hover:underline flex items-center gap-1">
                            <span>View Schedule</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- ═══════════ 5. VISUAL ANALYTICS & CHARTS SECTION ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="student-charts-container">
            
            <!-- Chart 1: Subject-wise Attendance Breakdown -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col relative chart-card" id="student-att-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 text-emerald-500"></i> Subject Attendance Breakdown
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Course-by-course attendance rates vs 75% exam criterion</p>
                    </div>
                    <div class="relative">
                        <button type="button" onclick="toggleExportMenu('exportMenuStu1')" class="p-2 rounded-xl text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Export Chart Options">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuStu1" class="hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-20 py-1.5 backdrop-blur-md">
                            <button type="button" onclick="exportChartAsPDF('student-att-card', 'Subject_Attendance_Report')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i> Save as PDF
                            </button>
                            <button type="button" onclick="exportChartAsExcel(window.studentChartData.attendance, 'Subject_Attendance_Report')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5 text-emerald-500"></i> Save as Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative flex-1 min-h-[280px] w-full flex items-center justify-center">
                    <canvas id="studentAttendanceChart"></canvas>
                </div>
            </div>

            <!-- Chart 2: Academic Performance / Grade Distribution -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col relative chart-card" id="student-perf-card">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="line-chart" class="w-5 h-5 text-purple-500"></i> Academic Scores & Coursework Performance
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Scores across assessments, internal tests, and courses</p>
                    </div>
                    <div class="relative">
                        <button type="button" onclick="toggleExportMenu('exportMenuStu2')" class="p-2 rounded-xl text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Export Chart Options">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuStu2" class="hidden absolute right-0 mt-2 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 z-20 py-1.5 backdrop-blur-md">
                            <button type="button" onclick="exportChartAsPDF('student-perf-card', 'Academic_Performance_Report')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-rose-500"></i> Save as PDF
                            </button>
                            <button type="button" onclick="exportChartAsExcel(window.studentChartData.performance, 'Academic_Performance_Report')" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5 text-emerald-500"></i> Save as Excel
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative flex-1 min-h-[280px] w-full flex items-center justify-center">
                    <canvas id="studentPerformanceChart"></canvas>
                </div>
            </div>

        </div>

        <script>
            window.studentChartData = {
                attendance: {
                    labels: <?= json_encode($attLabels) ?>,
                    data: <?= json_encode($attDataPoints) ?>,
                    details: <?= json_encode($attDetails) ?>
                },
                performance: {
                    labels: <?= json_encode($perfLabels) ?>,
                    data: <?= json_encode($perfDataPoints) ?>
                }
            };
        </script>

        <!-- ═══════════ 6. TIMETABLE SCHEDULE & CAMPUS NOTICES ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Today's Schedule Tracker -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="calendar-clock" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Today's Lectures</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?= $todayDay ?>, <?= date('M j') ?></p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/student/my_timetable.php" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                        Full Timetable <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($todaySchedule)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 flex items-center justify-center mb-3">
                                <i data-lucide="coffee" class="w-7 h-7"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Classes Scheduled Today</h4>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 max-w-xs px-4">
                                You have no lectures on <?= $todayDay ?>. Ideal time to review study materials or catch up on assignments.
                            </p>
                            <a href="<?= $base ?>/views/student/my_timetable.php" class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors">
                                View Full Week Matrix
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todaySchedule as $slot): ?>
                            <?php 
                                $startTs = strtotime($slot['start_time']);
                                $endTs = strtotime($slot['end_time']);
                                $nowTs = strtotime($currentTimeFormatted);
                                
                                $isOngoing = ($nowTs >= $startTs && $nowTs <= $endTs);
                                $isUpcoming = ($nowTs < $startTs);
                                
                                $startTimeFormatted = date('g:i A', $startTs);
                                $endTimeFormatted = date('g:i A', $endTs);
                            ?>
                            <div class="p-3.5 rounded-2xl <?= $isOngoing ? 'bg-indigo-50/80 dark:bg-indigo-950/40 border-2 border-indigo-400 dark:border-indigo-600' : 'bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50' ?> hover:border-indigo-300 dark:hover:border-indigo-600/50 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="px-2.5 py-2 rounded-xl <?= $isOngoing ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' ?> text-center shrink-0 min-w-[70px]">
                                        <span class="block text-xs font-black"><?= $startTimeFormatted ?></span>
                                        <span class="block text-[10px] opacity-75 font-medium"><?= $endTimeFormatted ?></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($slot['course_code']) ?></span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-md font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                                Room <?= htmlspecialchars($slot['room_number'] ?? 'TBD') ?>
                                            </span>
                                            <?php if ($isOngoing): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-black bg-emerald-500 text-white animate-pulse">
                                                    NOW
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300 line-clamp-1"><?= htmlspecialchars($slot['course_name']) ?></p>
                                        <p class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                            <i data-lucide="user" class="w-3 h-3 text-slate-400"></i>
                                            <?= htmlspecialchars($slot['faculty_name'] ?? 'Faculty Instructor') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center justify-end">
                                    <a href="<?= $base ?>/views/student/study_materials.php" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors">
                                        <i data-lucide="book-open" class="w-3.5 h-3.5 text-indigo-500"></i> Notes
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campus Circulars & Official Notices -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Campus Circulars & Notices</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Official college notifications</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/student/notices.php" class="text-xs font-bold text-rose-600 hover:text-rose-700 dark:text-rose-400 flex items-center gap-1">
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
                            <a href="<?= $base ?>/views/student/notices.php" class="block p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all group">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg <?= !empty($notice['is_pinned']) ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-600' : 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400' ?> flex items-center justify-center shrink-0 mt-0.5">
                                        <i data-lucide="<?= !empty($notice['is_pinned']) ? 'pin' : 'message-square' ?>" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <p class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                <?= htmlspecialchars($notice['title']) ?>
                                            </p>
                                            <?php if (!empty($notice['is_pinned'])): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200">
                                                    PINNED
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">
                                            <?= strip_tags($notice['content']) ?>
                                        </p>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-medium">
                                            <span><?= date('M d, Y', strtotime($notice['created_at'])) ?></span>
                                            <span>&bull;</span>
                                            <span>By <?= htmlspecialchars($notice['author_name'] ?? 'College Admin') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- ═══════════ 7. TASKS & PERFORMANCE (ASSIGNMENTS / QUIZZES / GRADES) ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Pending Deliverables (Assignments & Available Quizzes) -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="check-square" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Upcoming Tasks & Quizzes</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Deadlines requiring your submission</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/student/assignments.php" class="text-xs font-bold text-purple-600 hover:text-purple-700 dark:text-purple-400 flex items-center gap-1">
                        Portal <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($pendingAssignmentsList) && empty($availableQuizzesList)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 flex items-center justify-center mb-3">
                                <i data-lucide="party-popper" class="w-6 h-6"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">All Caught Up!</h4>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                You have no pending assignments or quizzes due right now. Excellent work!
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Show pending assignments -->
                        <?php foreach (array_slice($pendingAssignmentsList, 0, 3) as $assignment): ?>
                            <?php 
                                $isOverdue = strtotime($assignment['deadline']) < time();
                            ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 font-black text-xs">
                                        <?= htmlspecialchars($assignment['course_code']) ?>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 mb-0.5">
                                            <?= htmlspecialchars($assignment['title']) ?>
                                        </h4>
                                        <p class="text-[11px] <?= $isOverdue ? 'text-rose-500 font-bold' : 'text-slate-500 dark:text-slate-400' ?>">
                                            <i data-lucide="clock" class="w-3 h-3 inline mr-0.5"></i>
                                            <?= $isOverdue ? 'Deadline Passed: ' : 'Due: ' ?><?= date('M d, g:i A', strtotime($assignment['deadline'])) ?> &bull; <?= (int)$assignment['max_marks'] ?> pts
                                        </p>
                                    </div>
                                </div>
                                <a href="<?= $base ?>/views/student/assignments.php" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shrink-0 shadow-sm transition-transform hover:scale-105">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                    <span>Submit</span>
                                </a>
                            </div>
                        <?php endforeach; ?>

                        <!-- Show available quizzes -->
                        <?php foreach (array_slice($availableQuizzesList, 0, 2) as $quiz): ?>
                            <div class="p-3.5 rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/40 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 font-black text-xs">
                                        <?= htmlspecialchars($quiz['course_code']) ?>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-rose-500 text-white uppercase">Quiz</span>
                                            <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1">
                                                <?= htmlspecialchars($quiz['title']) ?>
                                            </h4>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                            <?= (int)$quiz['question_count'] ?> Qs &bull; <?= (int)$quiz['duration_minutes'] ?> mins &bull; Closes <?= date('M d', strtotime($quiz['end_time'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <a href="<?= $base ?>/views/student/take_quiz.php?quiz_id=<?= (int)$quiz['id'] ?>" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shrink-0 shadow-sm transition-transform hover:scale-105">
                                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                    <span>Start Quiz</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Academic Grades & Feedback -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="award" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Recent Grades & Feedback</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Published examination and coursework marks</p>
                        </div>
                    </div>
                    <a href="<?= $base ?>/views/student/my_grades.php" class="text-xs font-bold text-amber-600 hover:text-amber-700 dark:text-amber-400 flex items-center gap-1">
                        View All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div class="space-y-3 flex-1">
                    <?php if (empty($recentGrades) && empty($gradedAssignmentsList)): ?>
                        <div class="text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-3">
                                <i data-lucide="inbox" class="w-6 h-6 text-slate-400"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">No Graded Results Published Yet</p>
                            <p class="text-xs text-slate-400">Your exam and assignment marks will appear here once graded by faculty.</p>
                        </div>
                    <?php else: ?>
                        <!-- Show assessment marks -->
                        <?php foreach ($recentGrades as $grade): ?>
                            <?php 
                                $maxM = (float)($grade['max_marks'] ?? 0);
                                $obtM = (float)($grade['marks_obtained'] ?? 0);
                                $pct = $maxM > 0 ? round(($obtM / $maxM) * 100) : 0;
                                $gColor = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
                            ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-<?= $gColor ?>-500 to-<?= $gColor ?>-600 text-white flex flex-col items-center justify-center shrink-0 shadow-md shadow-<?= $gColor ?>-500/20">
                                        <span class="text-xs font-black"><?= $pct ?>%</span>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 mb-0.5">
                                            <?= htmlspecialchars($grade['assessment_title']) ?>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            <?= htmlspecialchars($grade['course_code']) ?> &bull; <strong><?= $obtM ?> / <?= $maxM ?></strong> marks
                                            <?php if (!empty($grade['remarks'])): ?>
                                                &bull; <em>"<?= htmlspecialchars($grade['remarks']) ?>"</em>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-<?= $gColor ?>-50 dark:bg-<?= $gColor ?>-950/60 text-<?= $gColor ?>-700 dark:text-<?= $gColor ?>-300 border border-<?= $gColor ?>-200 dark:border-<?= $gColor ?>-800/50 shrink-0">
                                    <?= $pct >= 75 ? 'Distinction' : ($pct >= 50 ? 'Passed' : 'Needs Work') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <!-- Show graded assignments if assessment marks are few -->
                        <?php foreach (array_slice($gradedAssignmentsList, 0, 2) as $ga): ?>
                            <?php 
                                $maxM = (float)($ga['max_marks'] ?? 0);
                                $obtM = (float)($ga['marks_obtained'] ?? 0);
                                $pct = $maxM > 0 ? round(($obtM / $maxM) * 100) : 0;
                                $gColor = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
                            ?>
                            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex flex-col items-center justify-center shrink-0 font-black text-xs">
                                        <span><?= $obtM ?></span>
                                        <span class="text-[9px] opacity-75">/<?= $maxM ?></span>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-1 mb-0.5">
                                            <?= htmlspecialchars($ga['title']) ?>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            <?= htmlspecialchars($ga['course_code']) ?> &bull; Assignment Graded
                                            <?php if (!empty($ga['feedback'])): ?>
                                                &bull; <em>"<?= htmlspecialchars($ga['feedback']) ?>"</em>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50 shrink-0">
                                    Graded
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- ═══════════ 8. RECENT STUDY MATERIALS DOWNLOAD HUB ═══════════ -->
        <div class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                        <i data-lucide="folder-down" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Recently Uploaded Study Materials</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Lecture slides, syllabus modules, and reference books shared by faculty</p>
                    </div>
                </div>
                <a href="<?= $base ?>/views/student/study_materials.php" class="text-xs font-bold text-teal-600 hover:text-teal-700 dark:text-teal-400 flex items-center gap-1">
                    Browse All <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php if (empty($recentMaterials)): ?>
                    <div class="col-span-full text-center py-10 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700/50">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-teal-500 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="folder" class="w-6 h-6"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-1">No Materials Uploaded Yet</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Your faculty members will publish lecture slides and reference files here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentMaterials as $mat): ?>
                        <?php 
                            $ext = strtolower(pathinfo($mat['file_path'], PATHINFO_EXTENSION));
                            $mbSize = round(($mat['file_size'] ?? 0) / (1024 * 1024), 2);
                            $badgeColor = in_array($ext, ['pdf']) ? 'rose' : (in_array($ext, ['doc', 'docx']) ? 'blue' : (in_array($ext, ['ppt', 'pptx']) ? 'amber' : 'emerald'));
                        ?>
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/60 dark:border-slate-700/50 hover:bg-slate-100/70 dark:hover:bg-slate-800/80 transition-all flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-<?= $badgeColor ?>-100 text-<?= $badgeColor ?>-700 dark:bg-<?= $badgeColor ?>-900/40 dark:text-<?= $badgeColor ?>-300">
                                        <?= strtoupper($ext ?: 'FILE') ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-bold"><?= $mbSize ?> MB</span>
                                </div>
                                <h4 class="text-xs font-black text-slate-900 dark:text-white line-clamp-2 mb-1 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                                    <?= htmlspecialchars($mat['title']) ?>
                                </h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                                    <?= htmlspecialchars($mat['course_code']) ?> &bull; <?= htmlspecialchars($mat['faculty_name'] ?? 'Faculty') ?>
                                </p>
                            </div>
                            <?php 
                                $matFileUrl = (strpos($mat['file_path'], 'uploads/') === 0) 
                                    ? ($base . '/' . $mat['file_path']) 
                                    : ($base . '/uploads/materials/' . $mat['file_path']);
                            ?>
                            <div class="mt-4 pt-3 border-t border-slate-200/60 dark:border-slate-700/50 flex items-center justify-between">
                                <span class="text-[10px] text-slate-400"><?= date('M d', strtotime($mat['uploaded_at'])) ?></span>
                                <div class="flex items-center gap-2">
                                    <a href="<?= $matFileUrl ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-teal-600 hover:text-teal-700 dark:text-teal-400" title="View study material without downloading">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                    </a>
                                    <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                    <a href="<?= $matFileUrl ?>" download class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" title="Download to device">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
    
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mb-4 sm:-mb-6 lg:-mb-8 mt-12">
        <?php require_once __DIR__ . '/../../includes/main_footer.php'; ?>
    </div>
</main>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

<script src="<?= $base ?>/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initStudentCharts === 'function') {
            initStudentCharts();
        }
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
