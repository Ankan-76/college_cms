<?php
// includes/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role_name'] ?? 'GUEST';
$links = [];

if ($role === 'ADMIN') {
    $links = [
        // Main
        ['url' => '/views/admin/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        
        // Users
        ['is_header' => true, 'label' => 'USERS'],
        ['url' => '/views/admin/students.php', 'icon' => 'graduation-cap', 'label' => 'Students'],
        ['url' => '/views/admin/faculty.php', 'icon' => 'users', 'label' => 'Faculty'],
        
        // Academics
        ['is_header' => true, 'label' => 'ACADEMICS'],
        ['url' => '/views/admin/departments.php', 'icon' => 'building-2', 'label' => 'Departments'],
        ['url' => '/views/admin/semesters.php', 'icon' => 'calendar-days', 'label' => 'Semesters'],
        ['url' => '/views/admin/subjects.php', 'icon' => 'book-open', 'label' => 'Subject'],
        ['url' => '/views/admin/subject_assignments.php', 'icon' => 'clipboard-list', 'label' => 'Assignments'],
        ['url' => '/views/admin/timetables.php', 'icon' => 'calendar', 'label' => 'Timetables'],
        
        // Communication
        ['is_header' => true, 'label' => 'COMMUNICATION'],
        ['url' => '/views/admin/broadcasts.php', 'icon' => 'radio', 'label' => 'Broadcasts'],
        ['url' => '/views/admin/notices.php', 'icon' => 'bell', 'label' => 'Notices'],
        ['url' => '/views/admin/leave_requests.php', 'icon' => 'calendar-off', 'label' => 'Leave Requests'],
        ['url' => '/views/admin/view-feedback.php', 'icon' => 'message-square-heart', 'label' => 'Feedbacks'],
        
        // Account
        ['is_header' => true, 'label' => 'ACCOUNT'],
        ['url' => '/views/admin/view-profile.php', 'icon' => 'user-circle', 'label' => 'My Profile'],
        
        // Systems Matrix
        ['is_header' => true, 'label' => 'SYSTEMS MATRIX'],
        ['url' => '/views/admin/security_logs.php', 'icon' => 'fingerprint', 'label' => 'Security Logs', 'class' => 'text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-900/10 hover:bg-rose-100 dark:hover:bg-rose-900/30'],
    ];
} elseif ($role === 'FACULTY') {
    $links = [
        // Main
        ['url' => '/views/faculty/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        
        // Academics
        ['is_header' => true, 'label' => 'ACADEMICS'],
        ['url' => '/views/faculty/my_subjects.php', 'icon' => 'book-open', 'label' => 'My Subjects'],
        ['url' => '/views/faculty/my_students.php', 'icon' => 'users', 'label' => 'My Students'],
        ['url' => '/views/faculty/timetable.php', 'icon' => 'calendar-days', 'label' => 'My Timetable'],
        ['url' => '/views/faculty/study_materials.php', 'icon' => 'folder-up', 'label' => 'Study Materials'],
        ['url' => '/views/faculty/assignments.php', 'icon' => 'file-up', 'label' => 'Assignments Portal'],
        ['url' => '/views/faculty/quizzes.php', 'icon' => 'brain', 'label' => 'Online Quizzes'],
        
        // Operations
        ['is_header' => true, 'label' => 'OPERATIONS'],
        ['url' => '/views/faculty/take_attendance.php', 'icon' => 'clipboard-check', 'label' => 'Take Attendance'],
        ['url' => '/views/faculty/view_attendance.php', 'icon' => 'pie-chart', 'label' => 'View Attendance'],
        ['url' => '/views/faculty/manage_marks.php', 'icon' => 'award', 'label' => 'Assignments'],
        
        // Communication
        ['is_header' => true, 'label' => 'COMMUNICATION'],
        ['url' => '/views/faculty/notices.php', 'icon' => 'bell', 'label' => 'Notices'],
        ['url' => '/views/faculty/apply_leave.php', 'icon' => 'calendar-off', 'label' => 'Apply Leave'],
        ['url' => '/views/faculty/my_leaves.php', 'icon' => 'calendar-check', 'label' => 'My Leaves'],
        ['url' => '/views/faculty/messages.php', 'icon' => 'message-circle', 'label' => 'Messages'],
        ['url' => '/feedback.php', 'icon' => 'message-square-heart', 'label' => 'Feedback'],
        
        // Account
        ['is_header' => true, 'label' => 'ACCOUNT'],
        ['url' => '/views/faculty/view-profile.php', 'icon' => 'user-circle', 'label' => 'My Profile'],
    ];
} elseif ($role === 'STUDENT') {
    $links = [
        // Main
        ['url' => '/views/student/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
        
        // Academics
        ['is_header' => true, 'label' => 'ACADEMICS'],
        ['url' => '/views/student/my_subjects.php', 'icon' => 'book-open', 'label' => 'My Subjects'],
        ['url' => '/views/student/my_timetable.php', 'icon' => 'calendar-days', 'label' => 'My Timetable'],
        ['url' => '/views/student/study_materials.php', 'icon' => 'folder-down', 'label' => 'Study Materials'],
        ['url' => '/views/student/assignments.php', 'icon' => 'file-up', 'label' => 'Assignments'],
        ['url' => '/views/student/quizzes.php', 'icon' => 'brain', 'label' => 'Online Quizzes'],
        
        // Performance
        ['is_header' => true, 'label' => 'PERFORMANCE'],
        ['url' => '/views/student/my_attendance.php', 'icon' => 'bar-chart-3', 'label' => 'My Attendance'],
        ['url' => '/views/student/my_grades.php', 'icon' => 'award', 'label' => 'My Grades'],
        
        // Communication
        ['is_header' => true, 'label' => 'COMMUNICATION'],
        ['url' => '/views/student/notices.php', 'icon' => 'bell', 'label' => 'Notices'],
        ['url' => '/views/student/apply_leave.php', 'icon' => 'calendar-off', 'label' => 'Apply Leave'],
        ['url' => '/views/student/my_leaves.php', 'icon' => 'calendar-check', 'label' => 'My Leaves'],
        ['url' => '/views/student/messages.php', 'icon' => 'message-circle', 'label' => 'Messages'],
        ['url' => '/feedback.php', 'icon' => 'message-square-heart', 'label' => 'Feedback'],
        
        // Account
        ['is_header' => true, 'label' => 'ACCOUNT'],
        ['url' => '/views/student/view-profile.php', 'icon' => 'user-circle', 'label' => 'My Profile'],
    ];
}

$current_uri = $_SERVER['REQUEST_URI'];
$base_url = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>
<aside id="sidebar" class="bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 w-64 flex-shrink-0 absolute lg:relative z-30 h-full transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out font-sans flex flex-col justify-between">
    <div class="py-6 px-4 flex-1 overflow-y-auto">
        <!-- Sidebar Navigation -->
        <nav class="space-y-1 mt-2">
            <?php foreach ($links as $link): ?>
                <?php if (!empty($link['is_header'])): ?>
                    <div class="px-3 pt-5 pb-2 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                        <?= htmlspecialchars($link['label']) ?>
                    </div>
                <?php else: ?>
                    <?php 
                    // Determine active status accurately
                    $isActive = strpos($current_uri, $link['url']) !== false;
                    $defaultActiveClasses = $isActive 
                        ? 'bg-indigo-50 text-primary dark:bg-indigo-900/30 dark:text-indigo-400 font-semibold shadow-[inset_4px_0_0_0_#4f46e5]' 
                        : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-slate-200';
                    $customClass = $link['class'] ?? $defaultActiveClasses;
                    ?>
                    <a href="<?= htmlspecialchars($base_url . $link['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?= $customClass ?>">
                        <i data-lucide="<?= $link['icon'] ?>" class="w-5 h-5 <?= $isActive ? '' : 'opacity-70' ?>"></i>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
    
    <!-- Footer/Copyright -->
    <div class="p-4 mt-auto border-t border-slate-200 dark:border-slate-800">
        <p class="text-xs text-center text-slate-500 dark:text-slate-400">
            &copy; <?= date('Y') ?> GreenField College. All rights reserved.
        </p>
    </div>
</aside>

<!-- Mobile Drawer Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 dark:bg-slate-900/80 backdrop-blur-sm z-20 hidden lg:hidden transition-opacity cursor-pointer"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const overlay = document.getElementById('sidebar-overlay');
        const sidebar = document.getElementById('sidebar');
        const mobileBtn = document.getElementById('mobile-menu-btn');
        
        if (mobileBtn && sidebar && overlay) {
            mobileBtn.addEventListener('click', () => {
                const isExpanded = sidebar.classList.contains('-translate-x-full');
                if (isExpanded) {
                    overlay.classList.remove('hidden');
                } else {
                    overlay.classList.add('hidden');
                }
            });
            
            overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                mobileBtn.setAttribute('aria-expanded', 'false');
            });
        }
    });
</script>
