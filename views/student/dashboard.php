<?php
// views/student/dashboard.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Student Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Fetch student details
$stmtStudent = $db->prepare("
    SELECT s.*, d.dept_name, d.dept_code, sem.semester_number, sem.academic_year
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE s.id = ?
");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();

$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Count total subjects for this student's dept + semester
$subjectCount = $db->prepare("SELECT COUNT(*) FROM courses WHERE department_id = ? AND semester_id = ?");
$subjectCount->execute([$departmentId, $semesterId]);
$totalSubjects = (int) $subjectCount->fetchColumn();

// Count total study materials available to student
$matCount = $db->prepare("
    SELECT COUNT(*) FROM study_materials sm
    JOIN courses c ON sm.course_id = c.id
    WHERE c.department_id = ? AND c.semester_id = ?
");
$matCount->execute([$departmentId, $semesterId]);
$totalMaterials = (int) $matCount->fetchColumn();

// Overall attendance percentage
$attendanceStats = $db->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late_count
    FROM attendance
    WHERE student_id = ?
");
$attendanceStats->execute([$studentId]);
$attData = $attendanceStats->fetch();
$totalRecords = (int) ($attData['total_records'] ?? 0);
$presentCount = (int) ($attData['present_count'] ?? 0);
$lateCount = (int) ($attData['late_count'] ?? 0);
$overallAttendance = $totalRecords > 0 ? round(($presentCount + $lateCount) / $totalRecords * 100) : 0;

// Today's timetable
$today = date('l'); // e.g. "Monday"
$todayClasses = $db->prepare("
    SELECT t.*, c.course_code, c.course_name, fp.name as faculty_name
    FROM timetables t
    JOIN courses c ON t.course_id = c.id
    LEFT JOIN course_assignments ca ON c.id = ca.course_id
    LEFT JOIN teachers fp ON ca.faculty_id = fp.id
    WHERE t.department_id = ? AND t.semester_id = ? AND t.day_of_week = ?
    ORDER BY t.start_time ASC
");
$todayClasses->execute([$departmentId, $semesterId, $today]);
$todaySchedule = $todayClasses->fetchAll();

// Recent notices (last 5)
$noticeStmt = $db->prepare("
    SELECT n.*, a.name as author
    FROM notices n
    JOIN admins a ON n.created_by = a.id
    WHERE n.target_role IN ('ALL', 'STUDENT')
    ORDER BY n.is_pinned DESC, n.created_at DESC
    LIMIT 5
");
$noticeStmt->execute();
$recentNotices = $noticeStmt->fetchAll();

// Recent grades (last 5)
$gradesStmt = $db->prepare("
    SELECT am.marks_obtained, am.remarks, a.title as assessment_title, a.max_marks, 
           c.course_code, c.course_name
    FROM assessment_marks am
    JOIN assessments a ON am.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE am.student_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
");
$gradesStmt->execute([$studentId]);
$recentGrades = $gradesStmt->fetchAll();

// Calculate stroke dashoffset for SVG ring (circumference = 2 * pi * 70 ≈ 439.8)
$circumference = 439.8;
$dashOffset = $circumference - ($circumference * $overallAttendance / 100);

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50/50 dark:bg-slate-900/50 relative">
    <!-- Background Decor -->
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-100/50 via-slate-50 to-white dark:from-indigo-900/20 dark:via-slate-900 dark:to-slate-950 -z-10"></div>
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-purple-400/10 dark:bg-purple-600/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
    <div class="absolute top-40 left-0 -ml-20 w-72 h-72 bg-indigo-400/10 dark:bg-indigo-600/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto space-y-8 animate-fade-in-up">
        
        <!-- Welcome Hero -->
        <div class="relative rounded-3xl overflow-hidden shadow-xl shadow-slate-200/40 dark:shadow-none border border-white/60 dark:border-slate-700/50 group bg-white/40 dark:bg-slate-800/40 backdrop-blur-xl">
            <!-- Glassy overlays -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-100/50 dark:bg-indigo-900/20 rounded-full blur-3xl -z-10"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-100/50 dark:bg-purple-900/20 rounded-full blur-3xl -z-10"></div>
            <div class="absolute right-0 bottom-0 opacity-[0.03] dark:opacity-[0.02] transition-transform duration-1000 group-hover:scale-110 pointer-events-none mb-10 mr-10">
                <i data-lucide="sparkles" class="w-64 h-64 text-indigo-900 dark:text-indigo-100"></i>
            </div>
            
            <div class="relative z-10 p-8 sm:p-10 flex flex-col md:flex-row items-center md:items-start gap-8">
                <div class="shrink-0 relative">
                    <div class="absolute inset-0 bg-indigo-500/20 dark:bg-indigo-400/20 rounded-full blur-md animate-pulse"></div>
                    <div class="h-28 w-28 rounded-full overflow-hidden border-4 border-white dark:border-slate-700 shadow-xl transform transition-transform group-hover:scale-105 relative z-10">
                        <?php
                        $avatarUrl = !empty($student['profile_pic']) 
                            ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($student['profile_pic'])
                            : "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['name']) . "&background=4f46e5&color=fff&bold=true&size=128";
                        ?>
                        <img class="w-full h-full object-cover" src="<?= $avatarUrl ?>" alt="Profile Avatar">
                    </div>
                </div>
                
                <div class="text-center md:text-left text-slate-900 dark:text-white py-1 flex-1">
                    <h1 class="text-4xl font-black tracking-tight mb-3">Hello, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
                    <p class="text-slate-600 dark:text-slate-300 font-medium flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm bg-white/60 dark:bg-slate-700/60 inline-flex px-4 py-2 rounded-full border border-white/60 dark:border-slate-600/50 shadow-sm">
                        <span class="flex items-center gap-1.5"><i data-lucide="building-2" class="w-4 h-4 text-indigo-500 dark:text-indigo-400"></i> <?= htmlspecialchars($student['dept_name'] ?? 'N/A') ?></span>
                        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-500 hidden sm:block"></span>
                        <span class="flex items-center gap-1.5"><i data-lucide="calendar-clock" class="w-4 h-4 text-indigo-500 dark:text-indigo-400"></i> Semester <?= htmlspecialchars((string)($student['semester_number'] ?? 'N/A')) ?></span>
                        <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-500 hidden sm:block"></span>
                        <span class="flex items-center gap-1.5"><i data-lucide="hash" class="w-4 h-4 text-indigo-500 dark:text-indigo-400"></i> <?= htmlspecialchars($student['roll_number'] ?? 'N/A') ?></span>
                    </p>
                    <div class="mt-6 flex gap-3 justify-center md:justify-start flex-wrap">
                        <span class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 px-4 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50 shadow-sm">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(52,211,153,0.8)]"></div> <?= htmlspecialchars($student['status'] ?? 'ACTIVE') ?>
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 text-xs font-bold text-indigo-700 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-800/50 shadow-sm">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($student['academic_year'] ?? date('Y') . '-' . (date('Y')+1)) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Subjects -->
            <a href="<?= BASE_URL ?>/views/student/my_subjects.php" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-xl shadow-slate-200/40 dark:shadow-none border border-white dark:border-slate-700/50 p-6 flex flex-col transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-indigo-200/50 dark:hover:shadow-indigo-900/20 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-transparent dark:from-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Subjects</h3>
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300 shadow-inner">
                            <i data-lucide="book-open" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight"><?= $totalSubjects ?></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-semibold">Enrolled courses</p>
                </div>
            </a>

            <!-- Study Materials -->
            <a href="<?= BASE_URL ?>/views/student/study_materials.php" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-xl shadow-slate-200/40 dark:shadow-none border border-white dark:border-slate-700/50 p-6 flex flex-col transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-200/50 dark:hover:shadow-emerald-900/20 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-transparent dark:from-emerald-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Materials</h3>
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300 shadow-inner">
                            <i data-lucide="folder-down" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight"><?= $totalMaterials ?></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-semibold">Available resources</p>
                </div>
            </a>

            <!-- Attendance -->
            <a href="<?= BASE_URL ?>/views/student/my_attendance.php" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-xl shadow-slate-200/40 dark:shadow-none border border-white dark:border-slate-700/50 p-6 flex flex-col transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-amber-200/50 dark:hover:shadow-amber-900/20 group relative overflow-hidden">
                <?php $attColor = $overallAttendance >= 75 ? 'emerald' : ($overallAttendance >= 50 ? 'amber' : 'rose'); ?>
                <div class="absolute inset-0 bg-gradient-to-br from-<?= $attColor ?>-50/50 to-transparent dark:from-<?= $attColor ?>-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Attendance</h3>
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-<?= $attColor ?>-50 text-<?= $attColor ?>-600 dark:bg-<?= $attColor ?>-900/30 dark:text-<?= $attColor ?>-400 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300 shadow-inner">
                            <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight"><?= $overallAttendance ?></h3>
                        <span class="text-2xl font-bold text-slate-400">%</span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-semibold"><?= $presentCount ?> of <?= $totalRecords ?> classes</p>
                </div>
            </a>

            <!-- Classes Today -->
            <a href="<?= BASE_URL ?>/views/student/my_timetable.php" class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-xl shadow-slate-200/40 dark:shadow-none border border-white dark:border-slate-700/50 p-6 flex flex-col transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-purple-200/50 dark:hover:shadow-purple-900/20 group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50/50 to-transparent dark:from-purple-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Today</h3>
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 group-hover:scale-110 group-hover:-rotate-3 transition-transform duration-300 shadow-inner">
                            <i data-lucide="calendar-days" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <h3 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight"><?= count($todaySchedule) ?></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-semibold"><?= $today ?>'s classes</p>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Attendance Ring -->
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/60 dark:border-slate-700/50 p-8 flex flex-col items-center justify-center text-center relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-32 h-32 bg-slate-100/50 dark:bg-slate-700/20 rounded-full blur-2xl -mr-16 -mt-16 transition-transform duration-1000 group-hover:scale-150"></div>
                <h3 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-8 relative z-10">Attendance Tracker</h3>
                
                <div class="relative w-48 h-48 flex items-center justify-center">
                    <svg class="absolute inset-0 w-48 h-48 transform -rotate-90 filter drop-shadow-md">
                        <!-- Background Circle -->
                        <circle cx="96" cy="96" r="80" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-700/50" stroke-width="14"></circle>
                        <?php 
                        $ringColor = $overallAttendance >= 75 ? 'indigo' : ($overallAttendance >= 50 ? 'amber' : 'rose'); 
                        $circumference = 2 * pi() * 80; // 502.65
                        $dashOffset = $circumference - ($circumference * $overallAttendance / 100);
                        ?>
                        <!-- Foreground Circle with Gradient effect (simulated via color) -->
                        <circle cx="96" cy="96" r="80" fill="none" stroke="currentColor" class="text-<?= $ringColor ?>-500 attendance-ring" stroke-width="14" stroke-dasharray="<?= $circumference ?>" stroke-dashoffset="<?= $dashOffset ?>" stroke-linecap="round"></circle>
                    </svg>
                    <!-- Center Content -->
                    <div class="flex flex-col items-center relative z-10">
                        <span class="text-5xl font-black text-slate-800 dark:text-white tracking-tighter"><?= $overallAttendance ?><span class="text-2xl text-slate-400">%</span></span>
                    </div>
                </div>
                
                <div class="mt-8 flex items-center gap-4 text-xs font-bold text-slate-500 dark:text-slate-400 relative z-10 bg-slate-50/80 dark:bg-slate-800/80 px-4 py-2 rounded-2xl backdrop-blur-sm border border-slate-200/50 dark:border-slate-700/50">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.6)]"></span> Prs: <?= $presentCount ?></span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_5px_rgba(245,158,11,0.6)]"></span> Lt: <?= $lateCount ?></span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_5px_rgba(244,63,94,0.6)]"></span> Abs: <?= $totalRecords - $presentCount - $lateCount ?></span>
                </div>
                
                <a href="<?= BASE_URL ?>/views/student/my_attendance.php" class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-slate-100 to-slate-50 hover:from-slate-200 hover:to-slate-100 dark:from-slate-700 dark:to-slate-700/80 dark:hover:from-slate-600 dark:hover:to-slate-600 shadow-sm text-sm font-bold text-slate-700 dark:text-slate-200 py-3 rounded-2xl transition-all hover:shadow-md relative z-10 group/btn">
                    View Details <i data-lucide="arrow-right" class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform"></i>
                </a>
            </div>

            <!-- Today's Schedule -->
            <div class="lg:col-span-2 bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/60 dark:border-slate-700/50 p-8 flex flex-col relative overflow-hidden">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </div>
                        Today's Schedule
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-400 px-3 py-1.5 rounded-lg ml-2 border border-indigo-100 dark:border-indigo-800/50"><?= $today ?></span>
                    </h3>
                    <a href="<?= BASE_URL ?>/views/student/my_timetable.php" class="text-sm font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">Full Timetable <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                </div>
                
                <?php if (empty($todaySchedule)): ?>
                <div class="flex-1 flex flex-col items-center justify-center py-12 relative z-10">
                    <div class="w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-5 shadow-inner">
                        <i data-lucide="coffee" class="w-10 h-10 text-slate-400"></i>
                    </div>
                    <h4 class="text-lg font-black text-slate-700 dark:text-slate-300 mb-1">No Classes Today</h4>
                    <p class="text-sm text-slate-500 font-medium">Enjoy your well-deserved break!</p>
                </div>
                <?php else: ?>
                <div class="space-y-4 flex-1 relative z-10 overflow-y-auto pr-2 custom-scrollbar">
                    <?php foreach ($todaySchedule as $class): 
                        $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan', 'blue'];
                        $colorIdx = crc32($class['course_code']) % count($colors);
                        $color = $colors[$colorIdx];
                    ?>
                    <div class="flex items-center gap-5 p-4 rounded-2xl bg-white/50 dark:bg-slate-700/30 hover:bg-white dark:hover:bg-slate-700/80 transition-all duration-300 border border-slate-100 dark:border-slate-700/50 hover:shadow-md hover:-translate-y-0.5 group">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-<?= $color ?>-100 to-<?= $color ?>-50 dark:from-<?= $color ?>-900/40 dark:to-<?= $color ?>-900/20 text-<?= $color ?>-600 dark:text-<?= $color ?>-400 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-inner border border-<?= $color ?>-200/50 dark:border-<?= $color ?>-700/50">
                            <span class="text-xs font-black leading-tight text-center"><?= htmlspecialchars($class['course_code']) ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-base font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-<?= $color ?>-600 dark:group-hover:text-<?= $color ?>-400 transition-colors"><?= htmlspecialchars($class['course_name']) ?></h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium flex items-center gap-3 mt-1">
                                <span class="flex items-center gap-1.5"><i data-lucide="user" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($class['faculty_name'] ?? 'TBA') ?></span>
                                <?php if ($class['room_number']): ?>
                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($class['room_number']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="text-right shrink-0 bg-slate-50 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                            <span class="text-sm font-black text-slate-700 dark:text-slate-300 block"><?= date('h:i A', strtotime($class['start_time'])) ?></span>
                            <span class="text-xs font-bold text-slate-400 block mt-0.5"><?= date('h:i A', strtotime($class['end_time'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Notices -->
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/60 dark:border-slate-700/50 p-8 flex flex-col relative overflow-hidden">
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center text-white shadow-lg shadow-rose-500/30">
                            <i data-lucide="bell-ring" class="w-5 h-5 animate-[wiggle_1s_ease-in-out_infinite]"></i>
                        </div>
                        Recent Notices
                    </h3>
                    <a href="<?= BASE_URL ?>/views/student/notices.php" class="text-sm font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">View All <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                </div>
                
                <div class="space-y-4 flex-1 flex flex-col relative z-10">
                    <?php if (empty($recentNotices)): ?>
                    <div class="flex-1 flex flex-col items-center justify-center py-12">
                        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4 shadow-inner">
                            <i data-lucide="bell-off" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-500">No recent notices</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentNotices as $notice): ?>
                        <a href="<?= BASE_URL ?>/views/student/notices.php" class="block p-4 bg-white/50 dark:bg-slate-700/30 hover:bg-white dark:hover:bg-slate-700/80 transition-all duration-300 rounded-2xl border border-slate-100 dark:border-slate-700/50 hover:shadow-md hover:-translate-y-0.5 group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl <?= $notice['is_pinned'] ? 'bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900/40 dark:to-amber-900/20 text-amber-600 border border-amber-200/50 dark:border-amber-700/50' : 'bg-gradient-to-br from-indigo-100 to-indigo-50 dark:from-indigo-900/40 dark:to-indigo-900/20 text-indigo-600 border border-indigo-200/50 dark:border-indigo-700/50' ?> flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform shadow-inner">
                                    <i data-lucide="<?= $notice['is_pinned'] ? 'pin' : 'bell' ?>" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <div class="flex items-start justify-between gap-3 mb-1">
                                        <h4 class="text-base font-bold text-slate-800 dark:text-slate-200 line-clamp-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($notice['title']) ?></h4>
                                        <span class="text-xs font-black tracking-wider text-slate-500 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg shrink-0 border border-slate-200 dark:border-slate-700"><?= date('M d', strtotime($notice['created_at'])) ?></span>
                                    </div>
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 line-clamp-1 leading-relaxed"><?= htmlspecialchars(substr($notice['content'], 0, 120)) ?>...</p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Grades -->
            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/60 dark:border-slate-700/50 p-8 flex flex-col relative overflow-hidden">
                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                            <i data-lucide="award" class="w-5 h-5"></i>
                        </div>
                        Recent Grades
                    </h3>
                    <a href="<?= BASE_URL ?>/views/student/my_grades.php" class="text-sm font-bold text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">View All <i data-lucide="chevron-right" class="w-4 h-4"></i></a>
                </div>
                
                <div class="space-y-4 flex-1 flex flex-col relative z-10">
                    <?php if (empty($recentGrades)): ?>
                    <div class="flex-1 flex flex-col items-center justify-center py-12">
                        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center mb-4 shadow-inner">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-bold text-slate-500">No grades published yet</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentGrades as $grade): 
                            $pct = $grade['max_marks'] > 0 ? round(($grade['marks_obtained'] / $grade['max_marks']) * 100) : 0;
                            $gradeColor = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
                        ?>
                        <div class="flex items-center gap-4 p-4 bg-white/50 dark:bg-slate-700/30 hover:bg-white dark:hover:bg-slate-700/80 transition-all duration-300 rounded-2xl border border-slate-100 dark:border-slate-700/50 hover:shadow-md hover:-translate-y-0.5 group">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-<?= $gradeColor ?>-400 to-<?= $gradeColor ?>-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-<?= $gradeColor ?>-500/30 group-hover:scale-110 group-hover:rotate-6 transition-transform">
                                <span class="text-sm font-black"><?= $pct ?>%</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-base font-bold text-slate-800 dark:text-slate-200 truncate group-hover:text-<?= $gradeColor ?>-600 dark:group-hover:text-<?= $gradeColor ?>-400 transition-colors"><?= htmlspecialchars($grade['assessment_title']) ?></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-black text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700"><?= htmlspecialchars($grade['course_code']) ?></span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-400"><?= $grade['marks_obtained'] ?> <span class="text-xs text-slate-400">/ <?= $grade['max_marks'] ?></span></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mb-4 sm:-mb-6 lg:-mb-8 mt-16 relative z-10">
        <?php require_once __DIR__ . '/../../includes/main_footer.php'; ?>
    </div>
</main>

<style>
.attendance-ring {
    transition: stroke-dashoffset 2s cubic-bezier(0.4, 0, 0.2, 1);
    filter: drop-shadow(0 0 6px currentColor);
}
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
    animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes wiggle {
    0%, 100% { transform: rotate(-3deg); }
    50% { transform: rotate(3deg); }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(71, 85, 105, 0.5);
}
</style>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
