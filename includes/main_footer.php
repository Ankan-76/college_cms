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
    $quick_links = [
        ['label' => 'Dashboard', 'url' => '/views/admin/dashboard.php'],
        ['label' => 'Students', 'url' => '/views/admin/students.php'],
        ['label' => 'Faculty', 'url' => '/views/admin/faculty.php'],
        ['label' => 'Notices', 'url' => '/views/admin/notices.php'],
        ['label' => 'Feedbacks', 'url' => '/views/admin/view-feedback.php'],
    ];
    $more_links = [
        ['label' => 'Departments', 'url' => '/views/admin/departments.php'],
        ['label' => 'Subjects', 'url' => '/views/admin/subjects.php'],
        ['label' => 'Timetables', 'url' => '/views/admin/timetables.php'],
    ];
} elseif ($role === 'FACULTY') {
    $quick_links = [
        ['label' => 'Dashboard', 'url' => '/views/faculty/dashboard.php'],
        ['label' => 'My Students', 'url' => '/views/faculty/my_students.php'],
        ['label' => 'My Subjects', 'url' => '/views/faculty/my_subjects.php'],
        ['label' => 'Attendance', 'url' => '/views/faculty/take_attendance.php'],
        ['label' => 'Feedback', 'url' => '/feedback.php'],
    ];
    $more_links = [
        ['label' => 'Marks', 'url' => '/views/faculty/manage_marks.php'],
        ['label' => 'Timetable', 'url' => '/views/faculty/timetable.php'],
        ['label' => 'Notices', 'url' => '/views/faculty/notices.php'],
    ];
} elseif ($role === 'STUDENT') {
    $quick_links = [
        ['label' => 'Dashboard', 'url' => '/views/student/dashboard.php'],
        ['label' => 'My Subjects', 'url' => '/views/student/my_subjects.php'],
        ['label' => 'My Timetable', 'url' => '/views/student/my_timetable.php'],
        ['label' => 'My Attendance', 'url' => '/views/student/my_attendance.php'],
        ['label' => 'Feedback', 'url' => '/feedback.php'],
    ];
    $more_links = [
        ['label' => 'Study Materials', 'url' => '/views/student/study_materials.php'],
        ['label' => 'My Grades', 'url' => '/views/student/my_grades.php'],
        ['label' => 'Notices', 'url' => '/views/student/notices.php'],
        ['label' => 'My Profile', 'url' => '/views/student/view-profile.php'],
    ];
} else {
    // Landing page / Guest
    $quick_links = [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About', 'url' => '/#about'],
        ['label' => 'Portals', 'url' => '/views/auth/login.php'],
        ['label' => 'Feedback', 'url' => '/feedback.php'],
        ['label' => 'Contact', 'url' => '/#contact'],
    ];
    $more_links = [
        ['label' => 'Student Portal', 'url' => '/views/auth/student_login.php'],
        ['label' => 'Faculty Portal', 'url' => '/views/auth/faculty_login.php'],
        ['label' => 'Admin Portal', 'url' => '/views/auth/admin_login.php'],
        ['label' => 'Submit Feedback', 'url' => '/feedback.php'],
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
                <ul class="space-y-4">
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
            <div class="md:col-span-3 lg:col-span-2">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-6 border-b border-slate-200 dark:border-slate-800 pb-2 inline-block">More</h3>
                <ul class="space-y-4">
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
