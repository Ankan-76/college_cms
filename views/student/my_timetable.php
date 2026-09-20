<?php
// views/student/my_timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'My Timetable | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

// Fetch timetable
$ttStmt = $db->prepare("
    SELECT t.*, c.course_code, c.course_name, tp.name as faculty_name
    FROM timetables t
    JOIN courses c ON t.course_id = c.id
    LEFT JOIN teachers tp ON t.faculty_id = tp.id
    WHERE t.department_id = ? AND t.semester_id = ?
    ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time ASC
");
$ttStmt->execute([$departmentId, $semesterId]);
$timetableRows = $ttStmt->fetchAll();

// Organize by day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$timetable = [];
foreach ($days as $day) {
    $timetable[$day] = [];
}
foreach ($timetableRows as $row) {
    $timetable[$row['day_of_week']][] = $row;
}

// Collect unique time slots
$timeSlots = [];
foreach ($timetableRows as $row) {
    $slot = $row['start_time'] . '-' . $row['end_time'];
    if (!in_array($slot, $timeSlots)) {
        $timeSlots[] = $slot;
    }
}
sort($timeSlots);

$today = date('l');

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-days" class="w-6 h-6 text-indigo-500"></i> My Timetable
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Your weekly class schedule for the current semester.</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>
            </div>
        </div>

        <?php if (empty($timetableRows)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-500 mb-4">
                <i data-lucide="calendar-clock" class="w-8 h-8"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Timetable Not Published</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">The academic timetable for your semester has not been published yet. Please check back later.</p>
        </div>
        <?php else: ?>

        <!-- Timetable Grid -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider sticky left-0 bg-slate-50 dark:bg-slate-800/50 z-10">Day</th>
                            <?php foreach ($timeSlots as $slot): 
                                $parts = explode('-', $slot);
                                $startFormatted = date('h:i A', strtotime($parts[0]));
                                $endFormatted = date('h:i A', strtotime($parts[1]));
                            ?>
                            <th class="px-4 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">
                                <?= $startFormatted ?><br><span class="text-slate-400 font-normal"><?= $endFormatted ?></span>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach ($days as $day): 
                            $isToday = ($day === $today);
                        ?>
                        <tr class="<?= $isToday ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900 dark:text-white sticky left-0 <?= $isToday ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : 'bg-white dark:bg-slate-800' ?> z-10">
                                <div class="flex items-center gap-2">
                                    <?= $day ?>
                                    <?php if ($isToday): ?>
                                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php foreach ($timeSlots as $slot): 
                                $parts = explode('-', $slot);
                                $slotStart = $parts[0];
                                $slotEnd = $parts[1];
                                
                                // Find matching class
                                $matchedClass = null;
                                foreach ($timetable[$day] as $cls) {
                                    if ($cls['start_time'] === $slotStart && $cls['end_time'] === $slotEnd) {
                                        $matchedClass = $cls;
                                        break;
                                    }
                                }
                            ?>
                            <td class="px-2 py-3">
                                <?php if ($matchedClass): 
                                    $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                                    $colorIdx = crc32($matchedClass['course_code']) % count($colors);
                                    $color = $colors[$colorIdx];
                                ?>
                                <div class="bg-<?= $color ?>-50 dark:bg-<?= $color ?>-900/20 text-<?= $color ?>-700 dark:text-<?= $color ?>-300 text-xs p-2.5 rounded-lg text-center font-medium border border-<?= $color ?>-100 dark:border-<?= $color ?>-800/50 min-w-[100px]">
                                    <div class="font-bold"><?= htmlspecialchars($matchedClass['course_code']) ?></div>
                                    <?php if ($matchedClass['room_number']): ?>
                                    <div class="text-<?= $color ?>-500 dark:text-<?= $color ?>-400 mt-0.5"><?= htmlspecialchars($matchedClass['room_number']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($matchedClass['faculty_name']): ?>
                                    <div class="text-<?= $color ?>-400 dark:text-<?= $color ?>-500 mt-0.5 text-[10px] truncate max-w-[100px]"><?= htmlspecialchars($matchedClass['faculty_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="bg-slate-50 dark:bg-slate-700/30 text-slate-400 text-xs p-2.5 rounded-lg text-center font-medium border border-slate-100 dark:border-slate-700/50 min-w-[100px]">
                                    Free
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Today's Classes List (mobile-friendly) -->
        <div class="lg:hidden space-y-4">
            <h3 class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Today — <?= $today ?></h3>
            <?php if (empty($timetable[$today])): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 text-center border border-slate-200 dark:border-slate-700">
                <i data-lucide="coffee" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                <p class="text-sm text-slate-500 font-medium">No classes today</p>
            </div>
            <?php else: ?>
                <?php foreach ($timetable[$today] as $cls): 
                    $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                    $colorIdx = crc32($cls['course_code']) % count($colors);
                    $color = $colors[$colorIdx];
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-<?= $color ?>-50 dark:bg-<?= $color ?>-900/20 text-<?= $color ?>-600 dark:text-<?= $color ?>-400 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-black"><?= htmlspecialchars($cls['course_code']) ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($cls['course_name']) ?></h4>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            <?= date('h:i A', strtotime($cls['start_time'])) ?> — <?= date('h:i A', strtotime($cls['end_time'])) ?>
                            <?php if ($cls['room_number']): ?> · <?= htmlspecialchars($cls['room_number']) ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
