<?php
// views/faculty/view_attendance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'View Attendance | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

use Controllers\AttendanceController;

$controller = new AttendanceController();
$courses = $controller->getFacultyCourses($_SESSION['faculty_profile_id'] ?? $_SESSION['user_id']);

// Filters
$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$startDate = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d');
$stats = [];

if ($selectedCourseId) {
    $stats = $controller->getAttendanceStats($selectedCourseId, $startDate, $endDate);
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Attendance Report</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View detailed attendance statistics and percentages over a date range.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <a href="take_attendance.php" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Ledger
                </a>
                <button type="button" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900" onclick="exportTableToCSV('attendance_report.csv')">
                    <i data-lucide="download" class="w-4 h-4"></i> Export CSV
                </button>
                <button type="button" class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-2 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900" onclick="window.print()">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Filter Card / Context Setup -->
        <div class="bg-white dark:bg-[rgba(30,41,59,0.8)] glassmorphism rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 transform transition-all">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div class="md:col-span-2">
                    <label for="course_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assigned Course</label>
                    <select id="course_id" name="course_id" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary focus:ring-2 sm:text-sm p-2.5 outline-none transition-colors">
                        <option value="">-- Select a subject --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (Sem <?= htmlspecialchars((string)$course['semester_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="start_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary focus:ring-2 sm:text-sm p-2.5 outline-none transition-colors">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">End Date</label>
                    <div class="flex gap-2">
                        <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary focus:ring-2 sm:text-sm p-2.5 outline-none transition-colors">
                        <button type="submit" class="bg-primary hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transform transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary flex items-center justify-center">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Attendance Stats Grid -->
        <?php if ($selectedCourseId !== null): ?>
            <?php if (empty($stats)): ?>
                <!-- Empty State -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                        <i data-lucide="bar-chart-2" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No data available</h3>
                    <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">We couldn't find any student data or attendance records for the selected course.</p>
                </div>
            <?php else: ?>
                <!-- Stats Data -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 opacity-100">
                    
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="pie-chart" class="w-5 h-5 text-indigo-500"></i>
                            Attendance Summary
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="attendance-table" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Roll No</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student Name</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Classes</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Present</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Absent</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Percentage</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700/50">
                                <?php foreach ($stats as $student): 
                                    $totalClasses = (int) $student['total_classes'];
                                    $present = (int) $student['total_present'] + (int) $student['total_late']; // late is usually counted as present in some systems, adjust if needed
                                    $absent = (int) $student['total_absent'];
                                    
                                    $percentage = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 2) : 0;
                                    
                                    // Badge color based on percentage
                                    $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
                                    if ($percentage < 75 && $percentage >= 60) {
                                        $badgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
                                    } elseif ($percentage < 60) {
                                        $badgeClass = 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($student['roll_number']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($student['name']) ?></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Reg: <?= htmlspecialchars($student['registration_number']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-400 font-medium">
                                        <?= $totalClasses ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-emerald-600 dark:text-emerald-400 font-bold">
                                        <?= $present ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-rose-600 dark:text-rose-400 font-bold">
                                        <?= $absent ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeClass ?>">
                                            <?= $percentage ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

/* Print styling */
@media print {
    body * {
        visibility: hidden;
    }
    main, main * {
        visibility: visible;
    }
    main {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        background: white !important;
    }
    .shrink-0, form {
        display: none !important;
    }
}
</style>

<script>
    function downloadCSV(csv, filename) {
        let csvFile;
        let downloadLink;

        // CSV file
        csvFile = new Blob([csv], {type: "text/csv"});

        // Download link
        downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    function exportTableToCSV(filename) {
        let csv = [];
        let rows = document.querySelectorAll("#attendance-table tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (let j = 0; j < cols.length; j++) {
                // Clean up the text (remove newlines and extra spaces)
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").trim();
                // Escape double quotes
                data = data.replace(/"/g, '""');
                // Enclose in quotes
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }

        downloadCSV(csv.join("\n"), filename);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
