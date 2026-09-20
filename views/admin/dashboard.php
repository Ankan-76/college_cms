<?php
// views/admin/dashboard.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Admin Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

// Robust analytic mapping from raw MySQL counts
$metrics = [
    'students' => $db->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'faculty' => $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'courses' => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'departments' => $db->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
    'notices' => $db->query("SELECT COUNT(*) FROM notices")->fetchColumn()
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
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Welcome Hero Section -->
        <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-slate-800/60 backdrop-blur-md border border-slate-200 dark:border-slate-700/50 shadow-sm">
            <!-- Decorative shapes (adjusted for new theme) -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-purple-500/10 dark:bg-purple-500/20 rounded-full blur-2xl"></div>
            
            <div class="relative z-10 p-8 sm:p-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white mb-2">Welcome Back, Admin! 👋</h1>
                    <p class="text-slate-600 dark:text-slate-300 max-w-xl text-sm sm:text-base">Here is your daily snapshot of the institution's activity. Manage students, oversee faculty, and broadcast important notices all from one place.</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                <i data-lucide="zap" class="w-5 h-5 text-amber-500"></i> Quick Actions
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <a href="<?= BASE_URL ?>/views/admin/add-student.php" class="flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 group">
                    <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/40 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-plus" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Add Student</span>
                </a>
                <a href="<?= BASE_URL ?>/views/admin/add-faculty.php" class="flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 group">
                    <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/40 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-check" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Add Faculty</span>
                </a>
                <a href="<?= BASE_URL ?>/views/admin/add-notice.php" class="flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 group">
                    <div class="w-12 h-12 bg-rose-50 dark:bg-rose-900/40 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i data-lucide="bell-ring" class="w-6 h-6 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">New Notice</span>
                </a>
                <a href="<?= BASE_URL ?>/views/admin/add-subject.php" class="flex flex-col items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 group">
                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/40 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i data-lucide="book-open" class="w-6 h-6 text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Add Subject</span>
                </a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="w-5 h-5 text-indigo-500"></i> System Metrics
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php 
                $cards = [
                    ['title' => 'Total Students', 'value' => $metrics['students'], 'icon' => 'users', 'color' => 'text-indigo-600 dark:text-indigo-400', 'bg' => 'bg-indigo-100 dark:bg-indigo-900/40', 'border' => 'border-indigo-100 dark:border-indigo-800'],
                    ['title' => 'Active Faculty', 'value' => $metrics['faculty'], 'icon' => 'briefcase', 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'border' => 'border-emerald-100 dark:border-emerald-800'],
                    ['title' => 'Ongoing Courses', 'value' => $metrics['courses'], 'icon' => 'book-open', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-100 dark:bg-amber-900/40', 'border' => 'border-amber-100 dark:border-amber-800'],
                    ['title' => 'Broadcast Notices', 'value' => $metrics['notices'], 'icon' => 'megaphone', 'color' => 'text-rose-600 dark:text-rose-400', 'bg' => 'bg-rose-100 dark:bg-rose-900/40', 'border' => 'border-rose-100 dark:border-rose-800']
                ];
                foreach ($cards as $card): 
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border <?= $card['border'] ?> p-6 flex flex-col relative overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
                         <i data-lucide="<?= $card['icon'] ?>" class="w-24 h-24 <?= $card['color'] ?>"></i>
                    </div>
                    <div class="relative z-10 flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 <?= $card['bg'] ?> <?= $card['color'] ?> shadow-sm">
                            <i data-lucide="<?= $card['icon'] ?>" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider"><?= $card['title'] ?></h3>
                    </div>
                    <div class="relative z-10">
                        <span class="text-4xl font-black text-slate-900 dark:text-white tracking-tight"><?= number_format((int)$card['value']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Charts Layout Infrastructure -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="charts-container">
            <!-- Line Graph -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col relative chart-card" id="enrollment-card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-5 h-5 text-indigo-500"></i> Enrollment By Semester
                    </h3>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenu1')" class="text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenu1" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-md shadow-lg border border-slate-200 dark:border-slate-700 z-10">
                            <button onclick="exportChartAsPDF('enrollment-card', 'Enrollment_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">PDF</button>
                            <button onclick="exportChartAsExcel(window.chartData.enrollment, 'Enrollment_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Excel</button>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 min-h-[250px] w-full">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
            <!-- Radial Context -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col relative chart-card" id="department-card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-5 h-5 text-emerald-500"></i> Students per Department
                    </h3>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenu2')" class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenu2" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-md shadow-lg border border-slate-200 dark:border-slate-700 z-10">
                            <button onclick="exportChartAsPDF('department-card', 'Department_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">PDF</button>
                            <button onclick="exportChartAsExcel(window.chartData.departments, 'Department_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Excel</button>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 min-h-[250px] w-full flex justify-center pb-2">
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
                    data: <?= $deptChartCounts ?>
                }
            };
        </script>
        
        <!-- Recent Data Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-6">
            <!-- Recent Students -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-indigo-500"></i> Recent Admissions
                    </h3>
                    <a href="<?= BASE_URL ?>/views/admin/students.php" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline">View All</a>
                </div>
                <div class="p-0">
                    <?php if (empty($recentStudents)): ?>
                        <div class="p-6 text-center text-slate-500 dark:text-slate-400">
                            No students registered yet.
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php foreach($recentStudents as $student): ?>
                            <li class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($student['name']) ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Roll: <?= htmlspecialchars($student['roll_number']) ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <?= htmlspecialchars($student['dept_code']) ?>
                                    </span>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sem <?= $student['semester_number'] ?></p>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Notices -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="bell" class="w-5 h-5 text-rose-500"></i> Latest Broadcasts
                    </h3>
                    <a href="<?= BASE_URL ?>/views/admin/notices.php" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline">View All</a>
                </div>
                <div class="p-0">
                    <?php if (empty($recentNotices)): ?>
                        <div class="p-6 text-center text-slate-500 dark:text-slate-400">
                            No notices published yet.
                        </div>
                    <?php else: ?>
                        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php foreach($recentNotices as $notice): ?>
                            <li class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors flex gap-4">
                                <div class="shrink-0 mt-1">
                                    <?php if($notice['is_pinned']): ?>
                                        <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400" title="Pinned">
                                            <i data-lucide="pin" class="w-4 h-4 fill-current"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate" title="<?= htmlspecialchars($notice['title']) ?>">
                                        <?= htmlspecialchars($notice['title']) ?>
                                    </p>
                                    <div class="flex items-center gap-2 mt-1 text-xs">
                                        <span class="text-slate-500 dark:text-slate-400">
                                            <?= date('M d, Y', strtotime($notice['created_at'])) ?>
                                        </span>
                                        <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">
                                            <?= htmlspecialchars($notice['target_role']) ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mb-4 sm:-mb-6 lg:-mb-8 mt-12">
        <?php require_once __DIR__ . '/../../includes/main_footer.php'; ?>
    </div>
</main>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Trigger generic analytic rendering strictly when DOM propagates natively 
        if(typeof initCharts === 'function') initCharts();
        
        // Re-initialize Lucide icons for any dynamically added content
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
