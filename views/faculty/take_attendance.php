<?php
// views/faculty/take_attendance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Take Attendance | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';

use Controllers\AttendanceController;

$controller = new AttendanceController();
$courses = $controller->getFacultyCourses($_SESSION['faculty_profile_id']);

// Filters
$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$selectedDate = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d');
$students = [];

if ($selectedCourseId) {
    $students = $controller->getStudentsForCourse($selectedCourseId, $selectedDate);
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Smart Attendance Ledger</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage, track, and record daily student attendance.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <a href="view_attendance.php" class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-2 border border-indigo-200 dark:border-indigo-800 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i data-lucide="eye" class="w-4 h-4"></i> View Attendance
                </a>
                <button type="button" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <i data-lucide="download" class="w-4 h-4"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-lg flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-lg flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Filter Card / Context Setup -->
        <div class="bg-white dark:bg-[rgba(30,41,59,0.8)] glassmorphism rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 transform transition-all">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
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
                    <label for="date" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Lecture Date</label>
                    <input type="date" id="date" name="date" value="<?= $selectedDate ?>" required max="<?= date('Y-m-d') ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary focus:ring-2 sm:text-sm p-2.5 outline-none transition-colors">
                </div>
                
                <div>
                    <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transform transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary flex items-center justify-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i> Load Roster
                    </button>
                </div>
            </form>
        </div>

        <!-- Attendance Grid Results -->
        <?php if ($selectedCourseId !== null): ?>
            <?php if (empty($students)): ?>
                <!-- Empty State -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                        <i data-lucide="users" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No students enrolled</h3>
                    <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">There are currently no students mapped to this course and semester combination.</p>
                </div>
            <?php else: ?>
                <!-- Roster Data -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all duration-300 opacity-100">
                    
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-indigo-500"></i>
                            Mark Attendance - <span class="text-indigo-600 dark:text-indigo-400"><?= date('M d, Y', strtotime($selectedDate)) ?></span>
                        </h2>
                        <div class="flex gap-3 bg-white dark:bg-slate-700 p-1.5 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm">
                            <button type="button" onclick="markBulk('PRESENT')" class="text-sm text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 font-medium px-3 py-1.5 rounded-md transition-all outline-none flex items-center gap-1">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i> All Present
                            </button>
                            <div class="w-px bg-slate-200 dark:bg-slate-600"></div>
                            <button type="button" onclick="markBulk('ABSENT')" class="text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/40 font-medium px-3 py-1.5 rounded-md transition-all outline-none flex items-center gap-1">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> All Absent
                            </button>
                        </div>
                    </div>

                    <form id="attendance-form" action="../../controllers/process_attendance.php" method="POST">
                        <input type="hidden" name="csrf_token" value="dummy_csrf_token_for_demo">
                        <input type="hidden" name="course_id" value="<?= htmlspecialchars((string)$selectedCourseId) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-white dark:bg-slate-800/50">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Roll No</th>
                                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Student Profile</th>
                                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-48">Status Action</th>
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
                                                <div class="flex-shrink-0 h-9 w-9">
                                                    <img class="h-9 w-9 rounded-full border border-slate-200 dark:border-slate-600 shadow-sm" src="https://ui-avatars.com/api/?name=<?= urlencode($student['name']) ?>&background=random&color=fff&bold=true" alt="Student">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($student['name']) ?></div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Reg: <?= htmlspecialchars($student['registration_number']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex justify-center items-center gap-6">
                                                <!-- Present Radio Option -->
                                                <label class="flex flex-col items-center cursor-pointer group/present hover:-translate-y-0.5 transition-transform">
                                                    <input type="radio" name="attendance[<?= $student['student_id'] ?>]" value="PRESENT" class="peer status-radio sr-only" required <?= (isset($student['attendance_status']) && $student['attendance_status'] === 'PRESENT') ? 'checked' : '' ?>>
                                                    <div class="w-9 h-9 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-600 dark:peer-checked:bg-emerald-500/20 dark:peer-checked:border-emerald-400 group-hover/present:border-emerald-400">
                                                        <i data-lucide="check" class="w-4 h-4 opacity-0 transition-all peer-checked:opacity-100 peer-checked:scale-110 rounded-indicator"></i>
                                                    </div>
                                                </label>
                                                
                                                <!-- Absent Radio Option -->
                                                <label class="flex flex-col items-center cursor-pointer group/absent hover:-translate-y-0.5 transition-transform">
                                                    <input type="radio" name="attendance[<?= $student['student_id'] ?>]" value="ABSENT" class="peer status-radio sr-only" required <?= (isset($student['attendance_status']) && $student['attendance_status'] === 'ABSENT') ? 'checked' : '' ?>>
                                                    <div class="w-9 h-9 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-600 dark:peer-checked:bg-rose-500/20 dark:peer-checked:border-rose-400 group-hover/absent:border-rose-400">
                                                        <i data-lucide="x" class="w-4 h-4 opacity-0 transition-all peer-checked:opacity-100 peer-checked:scale-110 rounded-indicator"></i>
                                                    </div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                            <button type="submit" id="save-btn" class="bg-primary hover:bg-indigo-700 text-white font-semibold py-2.5 px-8 rounded-lg transform transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-slate-900 flex items-center gap-2">
                                <i data-lucide="save" class="w-5 h-5"></i> Submit Attendance Ledger
                            </button>
                        </div>
                    </form>
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

<script>
    // UX Interactivity and mock-AJAX submission for demo
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('attendance-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Form will submit natively now
                
                // UX: set uploading state to submit button
                const btn = document.getElementById('save-btn');
                btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Processing...';
                // Note: Disable button can prevent form submission in some browsers if it's the submit button, 
                // but since it's on submit event, usually it's fine. 
                // For safety we'll just add a class for pointer events.
                btn.classList.add('pointer-events-none', 'opacity-80');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
        
        // Custom visual logic for active states (ensuring check/x scale properly)
        const radios = document.querySelectorAll('.status-radio');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const groupName = this.getAttribute('name');
                const groupRadios = document.querySelectorAll(`input[name="${groupName}"]`);
                groupRadios.forEach(r => {
                    // Navigate from input nested inside div.
                    const container = r.nextElementSibling;
                    const icon = container.querySelector('.rounded-indicator');
                    if (r.checked) {
                        icon.classList.remove('opacity-0');
                        icon.classList.add('opacity-100', 'scale-110');
                    } else {
                        icon.classList.add('opacity-0');
                        icon.classList.remove('opacity-100', 'scale-110');
                    }
                });
            });
            // Sync initial state if pre-checked
            if (radio.checked) {
                // Use a small timeout to let Lucide icons render first if needed
                setTimeout(() => {
                    radio.dispatchEvent(new Event('change'));
                }, 10);
            }
        });
    });

    // Helper for marking complete batch
    function markBulk(status) {
        let count = 0;
        const radios = document.querySelectorAll(`input[value="${status}"]`);
        radios.forEach(radio => {
            if (!radio.checked) count++;
            radio.checked = true;
            radio.dispatchEvent(new Event('change')); // trigger style sync
        });
        
        if(count > 0 && typeof window.showToast === 'function') {
            window.showToast(`Batch updated! Marked ${count} as ${status.charAt(0).toUpperCase() + status.slice(1).toLowerCase()}.`, 'info');
        }
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
