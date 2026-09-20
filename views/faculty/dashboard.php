<?php
// views/faculty/dashboard.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('FACULTY');
$pageTitle = 'Faculty Dashboard | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$facultyProfileId = $_SESSION['faculty_profile_id'] ?? 0;

// Simple UI rendering counts
$activeCoursesCount = $db->query("SELECT COUNT(*) FROM course_assignments WHERE faculty_id = " . (int)$facultyProfileId)->fetchColumn();
$materialsCount = $db->query("SELECT COUNT(*) FROM study_materials WHERE faculty_id = " . (int)$facultyProfileId)->fetchColumn();
$assessmentsCount = $db->query("SELECT COUNT(*) FROM assessments WHERE faculty_id = " . (int)$facultyProfileId)->fetchColumn();

// Fetch Recent Assessments
$recentAssessments = $db->query("
    SELECT a.*, c.course_code 
    FROM assessments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.faculty_id = " . (int)$facultyProfileId . " 
    ORDER BY a.created_at DESC LIMIT 3
")->fetchAll();

// Fetch Recent Materials
$recentMaterials = $db->query("
    SELECT m.*, c.course_code 
    FROM study_materials m 
    JOIN courses c ON m.course_id = c.id 
    WHERE m.faculty_id = " . (int)$facultyProfileId . " 
    ORDER BY m.uploaded_at DESC LIMIT 3
")->fetchAll();

// Fetch Chart Data: Assessments per course
$assessData = $db->query("
    SELECT c.course_code, COUNT(a.id) as assessment_count
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    LEFT JOIN assessments a ON c.id = a.course_id AND a.faculty_id = " . (int)$facultyProfileId . "
    WHERE ca.faculty_id = " . (int)$facultyProfileId . "
    GROUP BY c.id
")->fetchAll(PDO::FETCH_ASSOC);
$assessChartLabels = json_encode(array_column($assessData, 'course_code'));
$assessChartCounts = json_encode(array_column($assessData, 'assessment_count'));

// Fetch Chart Data: Materials per course
$matData = $db->query("
    SELECT c.course_code, COUNT(m.id) as material_count
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    LEFT JOIN study_materials m ON c.id = m.course_id AND m.faculty_id = " . (int)$facultyProfileId . "
    WHERE ca.faculty_id = " . (int)$facultyProfileId . "
    GROUP BY c.id
")->fetchAll(PDO::FETCH_ASSOC);
$matChartLabels = json_encode(array_column($matData, 'course_code'));
$matChartCounts = json_encode(array_column($matData, 'material_count'));

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-primary dark:bg-indigo-900 p-8 rounded-2xl shadow-sm text-white overflow-hidden relative">
            <div class="absolute right-0 top-0 opacity-10 pointer-events-none -mt-10 -mr-10">
                <i data-lucide="book" class="w-64 h-64"></i>
            </div>
            
            <div class="relative z-10">
                <h1 class="text-3xl font-black tracking-tight mb-2">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?></h1>
                <p class="text-indigo-100 font-medium">Manage your curriculum, track attendance, and record assignments efficiently.</p>
            </div>
            
            <a href="<?= BASE_URL ?>/views/faculty/take_attendance.php" class="relative z-10 inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-slate-50 px-5 py-2.5 rounded-xl font-bold transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 focus:outline-none shrink-0 whitespace-nowrap">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i> Execute Ledger
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Metric Widget 1 -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col transition-transform hover:-translate-y-1 group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assigned Classes</h3>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex-1 flex items-end">
                    <h3 class="text-4xl font-black text-slate-900 dark:text-white"><?= (int)$activeCoursesCount ?></h3>
                </div>
            </div>

            <!-- Metric Widget 2 -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col transition-transform hover:-translate-y-1 group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Materials</h3>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="folder-up" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex-1 flex items-end">
                    <h3 class="text-4xl font-black text-slate-900 dark:text-white"><?= (int)$materialsCount ?></h3>
                </div>
            </div>

            <!-- Metric Widget 3 -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col transition-transform hover:-translate-y-1 group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Assignments Setup</h3>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 group-hover:scale-110 transition-transform">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="flex-1 flex items-end">
                    <h3 class="text-4xl font-black text-slate-900 dark:text-white"><?= (int)$assessmentsCount ?></h3>
                </div>
            </div>
        </div>

        <!-- Charts Layout Infrastructure -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" id="faculty-charts-container">
            <!-- Assessments Bar Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col relative chart-card" id="faculty-assess-card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-5 h-5 text-indigo-500"></i> Assessments per Course
                    </h3>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenuFac1')" class="text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuFac1" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-md shadow-lg border border-slate-200 dark:border-slate-700 z-10">
                            <button onclick="exportChartAsPDF('faculty-assess-card', 'Assessments_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">PDF</button>
                            <button onclick="exportChartAsExcel(window.facultyChartData.assessments, 'Assessments_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Excel</button>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 min-h-[250px] w-full">
                    <canvas id="facultyAssessChart"></canvas>
                </div>
            </div>
            <!-- Materials Doughnut -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col relative chart-card" id="faculty-mat-card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-5 h-5 text-emerald-500"></i> Materials per Course
                    </h3>
                    <div class="relative">
                        <button onclick="toggleExportMenu('exportMenuFac2')" class="text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                            <i data-lucide="download" class="w-5 h-5"></i>
                        </button>
                        <div id="exportMenuFac2" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-slate-800 rounded-md shadow-lg border border-slate-200 dark:border-slate-700 z-10">
                            <button onclick="exportChartAsPDF('faculty-mat-card', 'Materials_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">PDF</button>
                            <button onclick="exportChartAsExcel(window.facultyChartData.materials, 'Materials_Stats')" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700">Excel</button>
                        </div>
                    </div>
                </div>
                <div class="relative flex-1 min-h-[250px] w-full flex justify-center pb-2">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Assessments -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4 text-indigo-500"></i> Recent Assignments
                    </h3>
                    <a href="manage_marks.php" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">View All</a>
                </div>
                <div class="space-y-4 flex-1">
                    <?php if (empty($recentAssessments)): ?>
                        <div class="text-center py-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col justify-center">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-3"></i>
                            <p class="text-sm font-medium text-slate-500">No assignments created yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentAssessments as $assessment): ?>
                        <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold"><?= htmlspecialchars($assessment['course_code']) ?></span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mb-0.5 line-clamp-1"><?= htmlspecialchars($assessment['title']) ?></p>
                                <p class="text-xs font-medium text-slate-500">Max Marks: <?= $assessment['max_marks'] ?> &bull; Created <?= date('M d', strtotime($assessment['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Materials -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="folder-up" class="w-4 h-4 text-emerald-500"></i> Recent Materials
                    </h3>
                    <a href="study_materials.php" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">View All</a>
                </div>
                <div class="space-y-4 flex-1">
                    <?php if (empty($recentMaterials)): ?>
                        <div class="text-center py-6 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-700/50 h-full flex flex-col justify-center">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-3"></i>
                            <p class="text-sm font-medium text-slate-500">No study materials uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentMaterials as $mat): ?>
                        <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold"><?= htmlspecialchars($mat['course_code']) ?></span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mb-0.5 line-clamp-1"><?= htmlspecialchars($mat['title']) ?></p>
                                <p class="text-xs font-medium text-slate-500"><?= number_format($mat['file_size']/1024/1024, 2) ?> MB &bull; <?= date('M d', strtotime($mat['uploaded_at'])) ?></p>
                            </div>
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
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof initFacultyCharts === 'function') initFacultyCharts();
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
