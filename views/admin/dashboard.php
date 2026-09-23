<?php
// views/admin/dashboard.php — Enhanced Modern Admin Dashboard
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('dashboard');
$pageTitle = 'Admin Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

// System Operational Metrics
$metrics = [
    'students' => (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'faculty' => (int)$db->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'departments' => (int)$db->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'courses' => (int)$db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'notices' => (int)$db->query("SELECT COUNT(*) FROM notices")->fetchColumn(),
    'inquiries_total' => (int)$db->query("SELECT COUNT(*) FROM admission_inquiries")->fetchColumn(),
    'inquiries_pending' => (int)$db->query("SELECT COUNT(*) FROM admission_inquiries WHERE LOWER(status) = 'pending'")->fetchColumn(),
    'feedbacks_total' => (int)$db->query("SELECT COUNT(*) FROM feedbacks")->fetchColumn(),
    'feedbacks_new' => (int)$db->query("SELECT COUNT(*) FROM feedbacks WHERE status = 'NEW'")->fetchColumn(),
];

// Fetch Recent Students
$recentStudentsQuery = $db->query("
    SELECT s.name, s.roll_number, d.dept_code, sem.semester_number 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN semesters sem ON s.semester_id = sem.id
    ORDER BY s.id DESC LIMIT 5
");
$recentStudents = $recentStudentsQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Notices
$recentNoticesQuery = $db->query("
    SELECT title, target_role, created_at, is_pinned 
    FROM notices 
    ORDER BY is_pinned DESC, created_at DESC LIMIT 5
");
$recentNotices = $recentNoticesQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Admission Inquiries
$recentInquiriesQuery = $db->query("
    SELECT ai.id, ai.full_name, ai.email, ai.phone, ai.status, ai.created_at, d.dept_code, d.dept_name
    FROM admission_inquiries ai
    LEFT JOIN departments d ON ai.department_id = d.id
    ORDER BY ai.id DESC LIMIT 5
");
$recentInquiries = $recentInquiriesQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Feedbacks
$recentFeedbacksQuery = $db->query("
    SELECT id, name, user_role, subject, rating, status, created_at
    FROM feedbacks
    ORDER BY id DESC LIMIT 5
");
$recentFeedbacks = $recentFeedbacksQuery->fetchAll(PDO::FETCH_ASSOC);

// Fetch Chart Data: Departments Distribution
$deptData = $db->query("
    SELECT d.dept_name as department_name, COUNT(s.id) as student_count 
    FROM departments d 
    LEFT JOIN students s ON d.id = s.department_id 
    GROUP BY d.id
")->fetchAll(PDO::FETCH_ASSOC);
$deptChartLabels = json_encode(array_column($deptData, 'department_name'));
$deptChartCounts = json_encode(array_column($deptData, 'student_count'));

// Fetch Chart Data: Enrollment by Semester
$enrollData = $db->query("
    SELECT sem.semester_number, COUNT(s.id) as student_count
    FROM semesters sem
    LEFT JOIN students s ON sem.id = s.semester_id
    GROUP BY sem.id
    ORDER BY sem.semester_number ASC
")->fetchAll(PDO::FETCH_ASSOC);
$enrollChartLabels = json_encode(array_map(function($sem) { return 'Sem ' . $sem; }, array_column($enrollData, 'semester_number')));
$enrollChartCounts = json_encode(array_column($enrollData, 'student_count'));

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">
        
        <!-- ═══════════ WELCOME HERO SECTION ═══════════ -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900/10 via-slate-900/5 to-purple-900/10 dark:from-slate-800/90 dark:via-slate-800/60 dark:to-indigo-950/40 backdrop-blur-xl border border-slate-200/80 dark:border-slate-700/60 shadow-xl shadow-indigo-500/5">
            <!-- Decorative Ambient Glows -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/15 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-purple-500/15 dark:bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 p-6 sm:p-8 lg:p-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <!-- User Info & Intro -->
                <div class="flex items-start sm:items-center gap-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-black text-xl sm:text-2xl shadow-lg shadow-indigo-500/30 shrink-0 border-2 border-white/20">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                                Welcome Back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>!
                            </h1>
                            <span class="inline-block text-xl animate-bounce">👋</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 max-w-xl text-xs sm:text-sm font-medium leading-relaxed">
                            Campus overview and real-time operational hub. Monitor enrollments, academic faculties, inquiries, and announcements.
                        </p>
                    </div>
                </div>

                <!-- Status Badges & Date -->
                <div class="flex flex-wrap md:flex-col items-start md:items-end gap-2.5 shrink-0 w-full md:w-auto pt-2 md:pt-0 border-t md:border-t-0 border-slate-200/60 dark:border-slate-700/60">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/80 dark:bg-slate-700/60 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-600/50 shadow-sm backdrop-blur-md">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <?= date('l, M j, Y') ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/60">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($_SESSION['admin_role'] ?? 'SUPER ADMIN') ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($metrics['inquiries_pending'] > 0): ?>
            <!-- ═══════════ ADMISSION INQUIRIES ACTION ALERT ═══════════ -->
            <div class="relative overflow-hidden p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-emerald-500/15 via-teal-500/10 to-indigo-500/15 dark:from-emerald-950/50 dark:via-teal-950/30 dark:to-indigo-950/30 border border-emerald-300/80 dark:border-emerald-700/60 shadow-lg shadow-emerald-500/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-sm">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/30">
                        <i data-lucide="user-plus" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <h4 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">
                                <?= $metrics['inquiries_pending'] ?> New Admission <?= $metrics['inquiries_pending'] === 1 ? 'Inquiry' : 'Inquiries' ?>
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-200/80 text-emerald-900 dark:bg-emerald-800/60 dark:text-emerald-200">
                                Action Required
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Prospective student candidates have submitted inquiry applications awaiting counseling review.
                        </p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/views/admin/admission-inquiries.php?status=pending" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-500/25 transition-all hover:scale-105 active:scale-95 shrink-0 group">
                    <span>Review Inquiries</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        <?php endif; ?>

        <!-- ═══════════ QUICK ACTIONS HUB ═══════════ -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i> Quick Actions
                </h2>
                <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Frequent administrative shortcuts</span>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3.5 sm:gap-4">
                
                <!-- 1. Add Student -->
                <a href="<?= BASE_URL ?>/views/admin/add-student.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-indigo-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Add Student</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Enroll new record</span>
                </a>

                <!-- 2. Add Faculty -->
                <a href="<?= BASE_URL ?>/views/admin/add-faculty.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-emerald-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Add Faculty</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Assign professors</span>
                </a>

                <!-- 3. Add Subject -->
                <a href="<?= BASE_URL ?>/views/admin/add-subject.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-amber-500/10 hover:border-amber-400 dark:hover:border-amber-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-amber-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Add Subject</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Curriculum setup</span>
                </a>

                <!-- 4. New Notice -->
                <a href="<?= BASE_URL ?>/views/admin/add-notice.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-rose-500/10 hover:border-rose-400 dark:hover:border-rose-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-rose-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">New Notice</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Broadcast circular</span>
                </a>

                <!-- 5. Admission Inquiries -->
                <a href="<?= BASE_URL ?>/views/admin/admission-inquiries.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-teal-500/10 hover:border-teal-400 dark:hover:border-teal-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <?php if ($metrics['inquiries_pending'] > 0): ?>
                        <span class="absolute top-3 right-3 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                    <?php endif; ?>
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-teal-500 to-emerald-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-teal-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="inbox" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors flex items-center gap-1">
                        Inquiries
                        <?php if ($metrics['inquiries_pending'] > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"><?= $metrics['inquiries_pending'] ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Counseling desk</span>
                </a>

                <!-- 6. Feedback Inbox -->
                <a href="<?= BASE_URL ?>/views/admin/view-feedback.php" class="relative group p-4 sm:p-5 bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-purple-500/10 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <?php if ($metrics['feedbacks_new'] > 0): ?>
                        <span class="absolute top-3 right-3 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                        </span>
                    <?php endif; ?>
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-500 to-indigo-600 text-white flex items-center justify-center mb-2.5 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                        <i data-lucide="message-square-heart" class="w-5 h-5"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors flex items-center gap-1">
                        Feedbacks
                        <?php if ($metrics['feedbacks_new'] > 0): ?>
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300"><?= $metrics['feedbacks_new'] ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Campus voice</span>
                </a>

            </div>
        </div>

        <!-- ═══════════ SYSTEM METRICS (6 KPI CARDS) ═══════════ -->
        <div>
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-indigo-500"></i> Core Statistics
                </h2>
                <span class="text-[11px] text-slate-400 font-semibold hidden sm:inline">Live database synchronized</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3.5 sm:gap-4">
                
                <!-- 1. Total Students -->
                <a href="<?= BASE_URL ?>/views/admin/students.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/20">
                                <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['students']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Students</span>
                    </div>
                </a>

                <!-- 2. Teaching Faculty -->
                <a href="<?= BASE_URL ?>/views/admin/faculty.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-emerald-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['faculty']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Faculty</span>
                    </div>
                </a>

                <!-- 3. Departments -->
                <a href="<?= BASE_URL ?>/views/admin/departments.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-sky-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-blue-600 text-white flex items-center justify-center shadow-md shadow-sky-500/20">
                                <i data-lucide="building-2" class="w-5 h-5"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-sky-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['departments']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Departments</span>
                    </div>
                </a>

                <!-- 4. Subjects / Courses -->
                <a href="<?= BASE_URL ?>/views/admin/subjects.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-amber-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                                <i data-lucide="book-open" class="w-5 h-5"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-amber-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['courses']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Subjects</span>
                    </div>
                </a>

                <!-- 5. Admission Inquiries -->
                <a href="<?= BASE_URL ?>/views/admin/admission-inquiries.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-teal-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-500 to-emerald-600 text-white flex items-center justify-center shadow-md shadow-teal-500/20">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                            </div>
                            <?php if ($metrics['inquiries_pending'] > 0): ?>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    <?= $metrics['inquiries_pending'] ?> New
                                </span>
                            <?php else: ?>
                                <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-teal-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                            <?php endif; ?>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['inquiries_total']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Inquiries</span>
                    </div>
                </a>

                <!-- 6. Broadcast Notices -->
                <a href="<?= BASE_URL ?>/views/admin/notices.php" class="bg-white/90 dark:bg-slate-800/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl hover:shadow-rose-500/10 hover:-translate-y-1 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-pink-600 text-white flex items-center justify-center shadow-md shadow-rose-500/20">
                                <i data-lucide="megaphone" class="w-5 h-5"></i>
                            </div>
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-rose-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"></i>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= number_format($metrics['notices']) ?>
                        </div>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-wider flex items-center gap-1">
                        <span>Notices</span>
                    </div>
                </a>

            </div>
        </div>

        <!-- ═══════════ ANALYTICS CHARTS SECTION ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="charts-container">
            
            <!-- 1. Line Graph: Enrollment by Semester -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-5 sm:p-6 flex flex-col relative chart-card backdrop-blur-sm" id="enrollment-card">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">Enrollment By Semester</h3>
                            <p class="text-[11px] text-slate-400">Distribution across active academic terms</p>
                        </div>
                    </div>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenu1')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors" title="Export Chart">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </button>
                        <div id="exportMenu1" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 z-10 overflow-hidden">
                            <button onclick="exportChartAsPDF('enrollment-card', 'Enrollment_Stats')" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
                            </button>
                            <button onclick="exportChartAsExcel(window.chartData.enrollment, 'Enrollment_Stats')" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="relative h-48 sm:h-52 w-full">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>

            <!-- 2. Students per Department -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-5 sm:p-6 flex flex-col relative chart-card backdrop-blur-sm" id="department-card">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="pie-chart" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">Students per Department</h3>
                            <p class="text-[11px] text-slate-400">Headcount allocation by discipline</p>
                        </div>
                    </div>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenu2')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors" title="Export Chart">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </button>
                        <div id="exportMenu2" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 z-10 overflow-hidden">
                            <button onclick="exportChartAsPDF('department-card', 'Department_Stats')" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> PDF
                            </button>
                            <button onclick="exportChartAsExcel(window.chartData.departments, 'Department_Stats')" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i data-lucide="sheet" class="w-3.5 h-3.5"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="relative h-48 sm:h-52 w-full flex items-center justify-center">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>

        </div>
        
        <script>
            // Injecting PHP Data for charts.js
            window.chartData = {
                enrollment: {
                    labels: <?= $enrollChartLabels ?>,
                    data: <?= $enrollChartCounts ?>
                },
                departments: {
                    labels: <?= $deptChartLabels ?>,
                    data: <?= $deptChartCounts ?>,
                    total: <?= (int)$metrics['students'] ?>
                }
            };
        </script>
        
        <!-- ═══════════ ACTIVITY & COMMUNICATIONS HUB (2x2 GRID) ═══════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-6">
            
            <!-- 1. Recent Enrolled Students -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden flex flex-col backdrop-blur-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Recent Enrolled Students</h3>
                            <p class="text-[10px] text-slate-400">Newly registered campus attendees</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/views/admin/students.php" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline flex items-center gap-1 group">
                        <span>Directory</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>
                <div class="p-2 flex-1">
                    <?php if (empty($recentStudents)): ?>
                        <div class="p-10 text-center text-xs text-slate-400">
                            <i data-lucide="users" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                            No students registered yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-1">
                            <?php foreach($recentStudents as $student): ?>
                            <div class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-2xl transition-all duration-200 flex items-center justify-between group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-md shadow-indigo-500/20">
                                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($student['name']) ?></p>
                                        <p class="text-[11px] text-slate-400 truncate">Roll: <?= htmlspecialchars($student['roll_number']) ?></p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/40">
                                        <?= htmlspecialchars($student['dept_code']) ?>
                                    </span>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Sem <?= $student['semester_number'] ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Recent Admission Inquiries -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden flex flex-col backdrop-blur-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Admission Inquiries</h3>
                            <p class="text-[10px] text-slate-400">Prospective candidate counseling</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/views/admin/admission-inquiries.php" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1 group">
                        <span>Admission Desk</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>
                <div class="p-2 flex-1">
                    <?php if (empty($recentInquiries)): ?>
                        <div class="p-10 text-center text-xs text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                            No admission inquiries received yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-1">
                            <?php foreach($recentInquiries as $inq): ?>
                                <?php
                                    $st = strtolower($inq['status'] ?? 'pending');
                                    $pillClass = match($st) {
                                        'contacted' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/40',
                                        'admitted' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/40',
                                        'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border-rose-200 dark:border-rose-800/40',
                                        default => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800/40'
                                    };
                                ?>
                                <div class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-2xl transition-all duration-200 flex items-center justify-between group">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-md shadow-emerald-500/20">
                                            <?= strtoupper(substr($inq['full_name'], 0, 1)) ?>
                                        </div>
                                        <div class="min-w-0 truncate">
                                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($inq['full_name']) ?></p>
                                            <p class="text-[11px] text-slate-400 truncate flex items-center gap-1.5 mt-0.5">
                                                <span><?= htmlspecialchars($inq['dept_code'] ?? 'General') ?></span>
                                                <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                                <span><?= date('M d, Y', strtotime($inq['created_at'])) ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0 ml-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border <?= $pillClass ?>">
                                            <?= htmlspecialchars($inq['status']) ?>
                                        </span>
                                        <a href="<?= BASE_URL ?>/views/admin/admission-inquiries.php" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Manage Inquiry">
                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Latest Broadcasts -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden flex flex-col backdrop-blur-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <i data-lucide="megaphone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Latest Broadcasts</h3>
                            <p class="text-[10px] text-slate-400">Campus circulars & notices</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/views/admin/notices.php" class="text-xs text-rose-600 dark:text-rose-400 font-bold hover:underline flex items-center gap-1 group">
                        <span>All Notices</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>
                <div class="p-2 flex-1">
                    <?php if (empty($recentNotices)): ?>
                        <div class="p-10 text-center text-xs text-slate-400">
                            <i data-lucide="bell-off" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                            No notices published yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-1">
                            <?php foreach($recentNotices as $notice): ?>
                            <div class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-2xl transition-all duration-200 flex gap-3.5 items-center">
                                <div class="shrink-0">
                                    <?php if($notice['is_pinned']): ?>
                                        <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center border border-rose-200/60 dark:border-rose-800/40" title="Pinned Notice">
                                            <i data-lucide="pin" class="w-4 h-4 fill-current"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-700/70 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate" title="<?= htmlspecialchars($notice['title']) ?>">
                                        <?= htmlspecialchars($notice['title']) ?>
                                    </p>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px]">
                                        <span class="text-slate-400">
                                            <?= date('M d, Y', strtotime($notice['created_at'])) ?>
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-md text-[9px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                            <?= htmlspecialchars($notice['target_role']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. Recent Feedback & Suggestions -->
            <div class="bg-white/90 dark:bg-slate-800/90 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden flex flex-col backdrop-blur-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="message-square-heart" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Recent Feedback</h3>
                            <p class="text-[10px] text-slate-400">Institutional reviews & ideas</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/views/admin/view-feedback.php" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline flex items-center gap-1 group">
                        <span>Feedback Inbox</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>
                <div class="p-2 flex-1">
                    <?php if (empty($recentFeedbacks)): ?>
                        <div class="p-10 text-center text-xs text-slate-400">
                            <i data-lucide="message-square" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                            No feedback submissions received yet.
                        </div>
                    <?php else: ?>
                        <div class="space-y-1">
                            <?php foreach($recentFeedbacks as $fb): ?>
                            <div class="p-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 rounded-2xl transition-all duration-200 flex items-center justify-between group">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-md shadow-purple-500/20">
                                        <?= strtoupper(substr($fb['name'], 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate" title="<?= htmlspecialchars($fb['subject']) ?>">
                                            <?= htmlspecialchars($fb['subject']) ?>
                                        </p>
                                        <p class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5 truncate">
                                            <span class="truncate"><?= htmlspecialchars($fb['name']) ?></span>
                                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                            <span class="text-purple-600 dark:text-purple-400 font-bold"><?= htmlspecialchars($fb['user_role']) ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <?php if (!empty($fb['rating'])): ?>
                                        <div class="flex items-center justify-end text-amber-400 text-xs">
                                            <?php for($i=1; $i<=$fb['rating']; $i++): ?>★<?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider mt-1 <?= $fb['status'] === 'NEW' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-300' ?>">
                                        <?= htmlspecialchars($fb['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
    </div>

    <!-- Main Footer Container -->
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mb-4 sm:-mb-6 lg:-mb-8 mt-12">
        <?php require_once __DIR__ . '/../../includes/main_footer.php'; ?>
    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Trigger analytic rendering strictly when DOM propagates natively 
        if(typeof initCharts === 'function') initCharts();
        
        // Re-initialize Lucide icons for all dynamically added icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
