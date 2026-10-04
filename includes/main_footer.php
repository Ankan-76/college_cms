<?php
// includes/main_footer.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role_name'] ?? 'GUEST';
$base_url = defined('BASE_URL') ? BASE_URL : '/college_cms';

$quick_links = [];
$more_links = [];

if ($role === 'ADMIN') {
    require_once __DIR__ . '/permission_middleware.php';

    $admin_pages = [
        ['label' => 'Dashboard', 'url' => '/views/admin/dashboard.php', 'permission' => 'dashboard'],
        ['label' => 'Students', 'url' => '/views/admin/students.php', 'permission' => 'students'],
        ['label' => 'Faculty', 'url' => '/views/admin/faculty.php', 'permission' => 'faculty'],
        ['label' => 'Departments', 'url' => '/views/admin/departments.php', 'permission' => 'departments'],
        ['label' => 'Semesters', 'url' => '/views/admin/semesters.php', 'permission' => 'semesters'],
        ['label' => 'Subjects', 'url' => '/views/admin/subjects.php', 'permission' => 'subjects'],
        ['label' => 'Subject Allocation', 'url' => '/views/admin/subject_assignments.php', 'permission' => 'subject_assignments'],
    ];

    $admin_more = [
        ['label' => 'Timetables', 'url' => '/views/admin/timetables.php', 'permission' => 'timetables'],
        ['label' => 'Notices', 'url' => '/views/admin/notices.php', 'permission' => 'notices'],
        ['label' => 'Broadcasts', 'url' => '/views/admin/broadcasts.php', 'permission' => 'broadcasts'],
        ['label' => 'Leave Requests', 'url' => '/views/admin/leave_requests.php', 'permission' => 'leave_requests'],
        ['label' => 'Feedbacks', 'url' => '/views/admin/view-feedback.php', 'permission' => 'feedbacks'],
        ['label' => 'Admission Inquiries', 'url' => '/views/admin/admission-inquiries.php', 'permission' => 'admission_inquiries'],
        ['label' => 'Manage Admins', 'url' => '/views/admin/manage-admins.php', 'permission' => 'manage_admins'],
    ];

    $quick_links = array_values(array_filter($admin_pages, function($l) {
        return empty($l['permission']) || (function_exists('has_permission') && has_permission($l['permission']));
    }));
    $more_links = array_values(array_filter($admin_more, function($l) {
        return empty($l['permission']) || (function_exists('has_permission') && has_permission($l['permission']));
    }));
} elseif ($role === 'FACULTY') {
    $quick_links = [
        ['label' => 'Dashboard', 'url' => '/views/faculty/dashboard.php'],
        ['label' => 'My Subjects', 'url' => '/views/faculty/my_subjects.php'],
        ['label' => 'My Students', 'url' => '/views/faculty/my_students.php'],
        ['label' => 'My Timetable', 'url' => '/views/faculty/timetable.php'],
        ['label' => 'Study Materials', 'url' => '/views/faculty/study_materials.php'],
        ['label' => 'Assignments Portal', 'url' => '/views/faculty/assignments.php'],
        ['label' => 'Online Quizzes', 'url' => '/views/faculty/quizzes.php'],
    ];
    $more_links = [
        ['label' => 'Take Attendance', 'url' => '/views/faculty/take_attendance.php'],
        ['label' => 'Internal Marks', 'url' => '/views/faculty/manage_marks.php'],
        ['label' => 'Notices', 'url' => '/views/faculty/notices.php'],
        ['label' => 'Messages', 'url' => '/views/faculty/messages.php'],
        ['label' => 'Apply Leave', 'url' => '/views/faculty/apply_leave.php'],
        ['label' => 'My Profile', 'url' => '/views/faculty/view-profile.php'],
        ['label' => 'Give Feedback', 'url' => '/feedback.php'],
    ];
} elseif ($role === 'STUDENT') {
    $quick_links = [
        ['label' => 'Dashboard', 'url' => '/views/student/dashboard.php'],
        ['label' => 'My Subjects', 'url' => '/views/student/my_subjects.php'],
        ['label' => 'My Timetable', 'url' => '/views/student/my_timetable.php'],
        ['label' => 'Study Materials', 'url' => '/views/student/study_materials.php'],
        ['label' => 'Assignments', 'url' => '/views/student/assignments.php'],
        ['label' => 'Online Quizzes', 'url' => '/views/student/quizzes.php'],
    ];
    $more_links = [
        ['label' => 'My Attendance', 'url' => '/views/student/my_attendance.php'],
        ['label' => 'My Grades', 'url' => '/views/student/my_grades.php'],
        ['label' => 'Notices', 'url' => '/views/student/notices.php'],
        ['label' => 'Messages', 'url' => '/views/student/messages.php'],
        ['label' => 'Apply Leave', 'url' => '/views/student/apply_leave.php'],
        ['label' => 'My Profile', 'url' => '/views/student/view-profile.php'],
        ['label' => 'Give Feedback', 'url' => '/feedback.php'],
    ];
} else {
    // Landing page / Guest
    $quick_links = [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About', 'url' => '/#about'],
        ['label' => 'Departments', 'url' => '/#departments'],
        ['label' => 'Notices', 'url' => '/#notices'],
        ['label' => 'Admissions', 'url' => '/#admissions'],
        ['label' => 'Contact', 'url' => '/#contact'],
    ];
    $more_links = [
        ['label' => 'Student Portal', 'url' => '/views/auth/student_login.php'],
        ['label' => 'Faculty Portal', 'url' => '/views/auth/faculty_login.php'],
        ['label' => 'Admin Portal', 'url' => '/views/auth/admin_login.php'],
        ['label' => 'Feedback', 'url' => '/feedback.php'],
    ];
}
?>
<!-- ═══════════ MAIN FOOTER ═══════════ -->
<footer class="bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-950 text-slate-600 dark:text-slate-300 border-t border-slate-200 dark:border-slate-800 w-full max-w-full mt-auto relative overflow-hidden">
    <!-- Decorative Glow -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-16 mb-8">
            <!-- Left Column: Branding & Description -->
            <div class="md:col-span-6 lg:col-span-5">
                <h2 class="text-3xl font-extrabold tracking-tight mb-6 text-slate-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="graduation-cap" class="w-8 h-8 text-indigo-600 dark:text-indigo-500"></i>
                    GreenField <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-500">College</span>
                </h2>
                <p class="text-sm leading-relaxed max-w-md text-slate-500 dark:text-slate-400 font-medium">
                    Next generation college management and administrative portal. Crafting modern, responsive, and high-performance digital experiences for academia.
                </p>
            </div>

            <!-- Middle Column: Quick Links -->
            <div class="md:col-span-3 lg:col-span-3 lg:col-start-7">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-6 border-b border-slate-200 dark:border-slate-800 pb-2 inline-block">Pages</h3>
                <ul class="space-y-3">
                    <?php foreach ($quick_links as $link): ?>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . $link['url']) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 hover:translate-x-1 inline-flex items-center gap-2 group">
                                <i data-lucide="chevron-right" class="w-3 h-3 opacity-0 -ml-5 group-hover:opacity-100 group-hover:ml-0 transition-all duration-300"></i>
                                <?= htmlspecialchars($link['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Right Column: More Links -->
            <div class="md:col-span-3 lg:col-span-3">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-6 border-b border-slate-200 dark:border-slate-800 pb-2 inline-block">More</h3>
                <ul class="space-y-3">
                    <?php foreach ($more_links as $link): ?>
                        <li>
                            <a href="<?= htmlspecialchars($base_url . $link['url']) ?>" class="text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-300 hover:translate-x-1 inline-flex items-center gap-2 group">
                                <i data-lucide="chevron-right" class="w-3 h-3 opacity-0 -ml-5 group-hover:opacity-100 group-hover:ml-0 transition-all duration-300"></i>
                                <?= htmlspecialchars($link['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="pt-6 border-t border-slate-200 dark:border-slate-800/60 flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">
                &copy; <?= date('Y') ?> GreenField College. All rights reserved.
            </p>
            <div class="flex items-center gap-6">
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium flex items-center gap-1.5">
                    Developed by 
                    <a href="https://ankan-76.github.io/portfolio/" target="_blank" rel="noopener noreferrer" class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 hover:from-indigo-500 hover:to-purple-500 dark:hover:from-indigo-300 dark:hover:to-purple-300 font-bold tracking-wide transition-all duration-300">Ankan Biswas</a>
                </p>
            </div>
        </div>
    </div>
</footer>
