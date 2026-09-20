<?php
// views/admin/edit-timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Edit Timetable | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: timetables.php');
    exit;
}

$error = '';
$success = '';

// Fetch the existing timetable entry
$stmt = $db->prepare("SELECT * FROM timetables WHERE id = ?");
$stmt->execute([$id]);
$timetable = $stmt->fetch();

if (!$timetable) {
    header('Location: timetables.php');
    exit;
}

$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name")->fetchAll();
$semesters = $db->query("SELECT id, semester_number, academic_year FROM semesters ORDER BY semester_number")->fetchAll();
$courses = $db->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name")->fetchAll();
$faculties = $db->query("SELECT id, name, designation FROM teachers WHERE status = 'ACTIVE' ORDER BY name")->fetchAll();
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
    $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $faculty_id = filter_input(INPUT_POST, 'faculty_id', FILTER_VALIDATE_INT);
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $room_number = trim($_POST['room_number'] ?? '');

    if (!$department_id || !$semester_id || !$course_id || !$faculty_id || !$day_of_week || !$start_time || !$end_time) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $error = 'End time must be after start time.';
    } else {
        // Check for faculty scheduling conflict
        $conflictStmt = $db->prepare("
            SELECT id FROM timetables 
            WHERE faculty_id = ? AND day_of_week = ? AND id != ?
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ");
        $conflictStmt->execute([$faculty_id, $day_of_week, $id, $start_time, $start_time, $end_time, $end_time]);
        
        if ($conflictStmt->fetch()) {
            $error = 'The selected faculty already has a class scheduled during this time slot.';
        } else {
            // Check for class (dept+sem) scheduling conflict
            $classConflictStmt = $db->prepare("
                SELECT id FROM timetables 
                WHERE department_id = ? AND semester_id = ? AND day_of_week = ? AND id != ?
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            ");
            $classConflictStmt->execute([$department_id, $semester_id, $day_of_week, $id, $start_time, $start_time, $end_time, $end_time]);
            
            if ($classConflictStmt->fetch()) {
                $error = 'This class (Department + Semester) already has a schedule during this time slot.';
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE timetables 
                        SET department_id = ?, semester_id = ?, course_id = ?, faculty_id = ?, day_of_week = ?, start_time = ?, end_time = ?, room_number = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$department_id, $semester_id, $course_id, $faculty_id, $day_of_week, $start_time, $end_time, $room_number, $id]);
                    header('Location: timetables.php?department_id=' . $department_id . '&semester_id=' . $semester_id);
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database error: Unable to update timetable entry.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit" class="w-6 h-6 text-emerald-500"></i> Edit Timetable
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update an existing class schedule.</p>
            </div>
            <a href="timetables.php?department_id=<?= $timetable['department_id'] ?>&semester_id=<?= $timetable['semester_id'] ?>" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Timetables
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-sm border border-rose-200 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Class Details -->
                    <div class="space-y-6 md:col-span-2 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">Class Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Department *</label>
                                <select name="department_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                    <option value="">Select Department</option>
                                    <?php 
                                    $selected_dept = $_POST['department_id'] ?? $timetable['department_id'];
                                    foreach($departments as $dept): 
                                    ?>
                                        <option value="<?= $dept['id'] ?>" <?= $selected_dept == $dept['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Semester *</label>
                                <select name="semester_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                    <option value="">Select Semester</option>
                                    <?php 
                                    $selected_sem = $_POST['semester_id'] ?? $timetable['semester_id'];
                                    foreach($semesters as $sem): 
                                    ?>
                                        <option value="<?= $sem['id'] ?>" <?= $selected_sem == $sem['id'] ? 'selected' : '' ?>>
                                            Semester <?= htmlspecialchars((string)$sem['semester_number']) ?> (<?= htmlspecialchars($sem['academic_year']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Details -->
                    <div class="space-y-6 md:col-span-2 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">Academic Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Subject (Course) *</label>
                                <select name="course_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                    <option value="">Select Subject</option>
                                    <?php 
                                    $selected_course = $_POST['course_id'] ?? $timetable['course_id'];
                                    foreach($courses as $course): 
                                    ?>
                                        <option value="<?= $course['id'] ?>" <?= $selected_course == $course['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Faculty *</label>
                                <select name="faculty_id" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                    <option value="">Select Faculty</option>
                                    <?php 
                                    $selected_faculty = $_POST['faculty_id'] ?? $timetable['faculty_id'];
                                    foreach($faculties as $faculty): 
                                    ?>
                                        <option value="<?= $faculty['id'] ?>" <?= $selected_faculty == $faculty['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($faculty['name']) ?> - <?= htmlspecialchars($faculty['designation']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Details -->
                    <div class="space-y-6 md:col-span-2 bg-slate-50 dark:bg-slate-900/50 p-5 rounded-xl border border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">Schedule Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Day of Week *</label>
                                <select name="day_of_week" required class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                    <option value="">Select Day</option>
                                    <?php 
                                    $selected_day = $_POST['day_of_week'] ?? $timetable['day_of_week'];
                                    foreach($days as $day): 
                                    ?>
                                        <option value="<?= $day ?>" <?= $selected_day == $day ? 'selected' : '' ?>>
                                            <?= $day ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Start Time *</label>
                                <input type="time" name="start_time" required
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                    value="<?= htmlspecialchars($_POST['start_time'] ?? $timetable['start_time']) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">End Time *</label>
                                <input type="time" name="end_time" required
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                    value="<?= htmlspecialchars($_POST['end_time'] ?? $timetable['end_time']) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Room Number</label>
                                <input type="text" name="room_number" placeholder="e.g. 101-A"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                                    value="<?= htmlspecialchars($_POST['room_number'] ?? $timetable['room_number']) ?>">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="mt-8 flex justify-end border-t border-slate-200 dark:border-slate-700 pt-6">
                    <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
