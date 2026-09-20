<?php
// views/faculty/my_students.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'My Students | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

use Controllers\AttendanceController;

$controller = new AttendanceController();
$courses = $controller->getFacultyCourses($_SESSION['faculty_profile_id']);

// Filters
$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$students = [];

if ($selectedCourseId) {
    $students = $controller->getStudentsForCourse($selectedCourseId);
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="users" class="w-6 h-6 text-indigo-500"></i> Class Roster
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View the students enrolled in your assigned courses.</p>
            </div>
        </div>

        <!-- Filter Card / Context Setup -->
        <div class="bg-white dark:bg-[rgba(30,41,59,0.8)] glassmorphism rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 transform transition-all">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div class="md:col-span-3">
                    <label for="course_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Select Assigned Course</label>
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
                    <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transform transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary flex items-center justify-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i> View Students
                    </button>
                </div>
            </form>
        </div>

        <!-- Students Grid Results -->
        <?php if ($selectedCourseId !== null): ?>
            <?php if (empty($students)): ?>
                <!-- Empty State -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                        <i data-lucide="users-x" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No students enrolled</h3>
                    <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">There are currently no students mapped to this course and semester combination.</p>
                </div>
            <?php else: ?>
                <!-- Roster Data -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 opacity-100">
                    
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-5 h-5 text-indigo-500"></i>
                            Enrolled Students <span class="text-sm font-medium text-slate-500 bg-slate-200 dark:bg-slate-700 px-2.5 py-0.5 rounded-full ml-2"><?= count($students) ?> total</span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-white dark:bg-slate-800/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Roll No</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student Details</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Registration Number</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700/50">
                                <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors duration-150 group">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($student['roll_number']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full border-2 border-slate-200 dark:border-slate-600 shadow-sm" src="https://ui-avatars.com/api/?name=<?= urlencode($student['name']) ?>&background=random&color=fff&bold=true" alt="Student Avatar">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($student['name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-medium">
                                        <?= htmlspecialchars($student['registration_number']) ?>
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
/* Smooth fade-in animation for empty state */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
