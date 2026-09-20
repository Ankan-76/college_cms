<?php
// views/admin/timetables.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Timetables | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();
$semesters = $db->query("SELECT id, semester_number, academic_year FROM semesters ORDER BY semester_number")->fetchAll();

$department_id = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: ($departments[0]['id'] ?? 0);
$semester_id = filter_input(INPUT_GET, 'semester_id', FILTER_VALIDATE_INT) ?: ($semesters[0]['id'] ?? 0);

$timetables = [];
if ($department_id && $semester_id) {
    $stmt = $db->prepare("
        SELECT t.*, c.course_name, c.course_code, f.name as faculty_name 
        FROM timetables t
        JOIN courses c ON t.course_id = c.id
        JOIN teachers f ON t.faculty_id = f.id
        WHERE t.department_id = ? AND t.semester_id = ?
        ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), t.start_time
    ");
    $stmt->execute([$department_id, $semester_id]);
    $timetables = $stmt->fetchAll();
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-days" class="w-6 h-6 text-indigo-500"></i> Class Timetables
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage and view schedules for all classes.</p>
            </div>
            <a href="add-timetable.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Add Timetable Entry
            </a>
        </div>

        <!-- Filter Form -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department</label>
                    <select name="department_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-2.5 border outline-none transition-shadow" onchange="this.form.submit()">
                        <?php foreach($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $department_id == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester</label>
                    <select name="semester_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-2.5 border outline-none transition-shadow" onchange="this.form.submit()">
                        <?php foreach($semesters as $sem): ?>
                            <option value="<?= $sem['id'] ?>" <?= $semester_id == $sem['id'] ? 'selected' : '' ?>>
                                Semester <?= htmlspecialchars((string)$sem['semester_number']) ?> (<?= htmlspecialchars($sem['academic_year']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Timetable Display -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <?php if (empty($timetables)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-x" class="w-8 h-8 text-slate-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">No timetable entries found</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">There are currently no scheduled classes for this department and semester.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto p-6">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="p-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700 w-32">Time / Day</th>
                                <?php foreach($days as $day): ?>
                                    <th class="p-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700"><?= $day ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Extract unique times for rows
                            $times = [];
                            foreach ($timetables as $t) {
                                $timeSlot = date('h:i A', strtotime($t['start_time'])) . ' - ' . date('h:i A', strtotime($t['end_time']));
                                if (!in_array($timeSlot, $times)) {
                                    $times[] = $timeSlot;
                                }
                            }
                            // Sort times (basic sorting, works if formats are consistent)
                            usort($times, function($a, $b) {
                                return strtotime(explode(' - ', $a)[0]) - strtotime(explode(' - ', $b)[0]);
                            });
                            ?>
                            
                            <?php foreach ($times as $time): ?>
                                <tr>
                                    <td class="p-3 border-b border-slate-100 dark:border-slate-700/50 align-top">
                                        <div class="text-sm font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap bg-slate-50 dark:bg-slate-900/50 py-2 px-3 rounded-lg border border-slate-200 dark:border-slate-700 text-center">
                                            <?= $time ?>
                                        </div>
                                    </td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="p-3 border-b border-slate-100 dark:border-slate-700/50 align-top text-center h-full">
                                            <?php 
                                            $found = false;
                                            foreach ($timetables as $t) {
                                                $tTime = date('h:i A', strtotime($t['start_time'])) . ' - ' . date('h:i A', strtotime($t['end_time']));
                                                if ($t['day_of_week'] === $day && $tTime === $time) {
                                                    $found = true;
                                                    ?>
                                                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-xl p-3 h-full group relative hover:shadow-md transition-shadow">
                                                        <div class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-1">
                                                            <?= htmlspecialchars($t['course_code']) ?>
                                                        </div>
                                                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-2 truncate" title="<?= htmlspecialchars($t['course_name']) ?>">
                                                            <?= htmlspecialchars($t['course_name']) ?>
                                                        </div>
                                                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-500 mt-auto">
                                                            <span class="flex items-center gap-1"><i data-lucide="user" class="w-3 h-3"></i> <?= htmlspecialchars($t['faculty_name']) ?></span>
                                                            <?php if($t['room_number']): ?>
                                                                <span class="flex items-center gap-1 font-semibold"><i data-lucide="map-pin" class="w-3 h-3"></i> <?= htmlspecialchars($t['room_number']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Actions Overlay -->
                                                        <div class="absolute inset-0 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl opacity-0 group-hover:opacity-100 flex items-center justify-center gap-3 transition-opacity">
                                                            <a href="edit-timetable.php?id=<?= $t['id'] ?>" class="p-2 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-900/70 transition-colors" title="Edit">
                                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                                            </a>
                                                            <form method="POST" action="delete-timetable.php" onsubmit="return confirm('Delete this timetable entry?');" class="inline">
                                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                                <button type="submit" class="p-2 bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400 rounded-lg hover:bg-rose-200 dark:hover:bg-rose-900/70 transition-colors" title="Delete">
                                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                            if (!$found) {
                                                echo '<div class="text-xs text-slate-300 dark:text-slate-600 font-medium py-4">--</div>';
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
