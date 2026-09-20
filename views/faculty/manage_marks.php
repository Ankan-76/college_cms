<?php
// views/faculty/manage_marks.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Assignments | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php'; 
require_once __DIR__ . '/../../controllers/AssessmentController.php'; 

use Controllers\AttendanceController;
use Controllers\AssessmentController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$attendanceCtrl = new AttendanceController();
$courses = $attendanceCtrl->getFacultyCourses($facultyId);

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses) > 0 ? $courses[0]['id'] : 0);

$assessmentCtrl = new AssessmentController();
$assessments = [];
if ($selectedCourseId > 0) {
    $assessments = $assessmentCtrl->getAssessmentsByCourse($selectedCourseId);
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        
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

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Assignments</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Record and manage student marks for internal exams, assignments, and projects.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('add-assessment-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i> Create Assessment
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-[rgba(30,41,59,0.8)] glassmorphism rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Select Course</label>
                    <select name="course_id" onchange="this.form.submit()" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary focus:ring-primary p-2.5 outline-none">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Assessments List -->
        <?php if ($selectedCourseId > 0 && empty($assessments)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 transition-transform hover:scale-110 duration-300">
                    <i data-lucide="clipboard-edit" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Assessments Created</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">You have not created any assessments for this course. Click "Create Assessment" to get started.</p>
            </div>
        <?php elseif ($selectedCourseId > 0): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <ul class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($assessments as $assessment): ?>
                    <li class="p-4 sm:px-6 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hidden sm:block">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($assessment['title']) ?></h4>
                                <p class="text-sm text-slate-500 flex items-center gap-2">
                                    <span>Max Marks: <span class="font-semibold text-slate-700 dark:text-slate-300"><?= $assessment['max_marks'] ?></span></span>
                                    <span>&bull;</span>
                                    <span>Created <?= date('M d, Y', strtotime($assessment['created_at'])) ?></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="text-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                View / Edit Marks
                            </button>
                            <a href="../../controllers/process_assessment.php?action=delete&id=<?= $assessment['id'] ?>&course_id=<?= $selectedCourseId ?>" onclick="return confirm('Are you sure you want to delete this assessment? All associated student marks will also be deleted.')" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>

    <!-- Add Assessment Modal -->
    <div id="add-assessment-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Create New Assessment</h2>
                <button onclick="document.getElementById('add-assessment-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../../controllers/process_assessment.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Course</label>
                    <select name="course_id" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assessment Title</label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Term Exam" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Maximum Marks</label>
                    <input type="number" name="max_marks" required min="1" max="1000" value="100" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('add-assessment-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Save Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
