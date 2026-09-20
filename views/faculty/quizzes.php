<?php
// views/faculty/quizzes.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Online Quizzes | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';
require_once __DIR__ . '/../../controllers/QuizController.php';

use Controllers\AttendanceController;
use Controllers\QuizController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$attendanceCtrl = new AttendanceController();
$courses = $attendanceCtrl->getFacultyCourses($facultyId);

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses) > 0 ? $courses[0]['id'] : 0);

$quizCtrl = new QuizController();
$quizzes = [];
if ($selectedCourseId > 0) {
    $quizzes = $quizCtrl->getQuizzesByCourse($selectedCourseId);
}
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
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
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="brain" class="w-6 h-6 text-purple-500"></i> Online Quizzes
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Create MCQ-based quizzes with automated grading and timed assessments.</p>
            </div>
            
            <button type="button" onclick="document.getElementById('create-quiz-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-sm hover:-translate-y-0.5 shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i> New Quiz
            </button>
        </div>

        <!-- Course Filter -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1 max-w-sm">
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

        <!-- Quizzes List -->
        <?php if (empty($quizzes) && $selectedCourseId > 0): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-500 mb-4">
                    <i data-lucide="brain" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Quizzes Created</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">Create your first quiz for this course.</p>
            </div>
        <?php elseif ($selectedCourseId > 0): ?>
            <div class="space-y-4">
                <?php foreach ($quizzes as $quiz):
                    $statusColors = ['DRAFT' => 'amber', 'PUBLISHED' => 'emerald', 'CLOSED' => 'slate'];
                    $sColor = $statusColors[$quiz['status']] ?? 'slate';
                    $now = time();
                    $isActive = $quiz['status'] === 'PUBLISHED' && 
                                (!$quiz['start_time'] || strtotime($quiz['start_time']) <= $now) && 
                                (!$quiz['end_time'] || strtotime($quiz['end_time']) >= $now);
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-md transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl hidden sm:block">
                                <i data-lucide="brain" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($quiz['title']) ?></h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $sColor ?>-100 text-<?= $sColor ?>-700 dark:bg-<?= $sColor ?>-900/30 dark:text-<?= $sColor ?>-400">
                                        <?= $quiz['status'] ?>
                                    </span>
                                    <?php if ($isActive): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="flex items-center gap-1"><i data-lucide="help-circle" class="w-3 h-3"></i> <?= $quiz['question_count'] ?> questions</span>
                                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?= $quiz['duration_minutes'] ?> min</span>
                                    <span class="flex items-center gap-1"><i data-lucide="star" class="w-3 h-3"></i> <?= $quiz['total_marks'] ?> marks</span>
                                    <span class="flex items-center gap-1"><i data-lucide="users" class="w-3 h-3"></i> <?= $quiz['attempt_count'] ?> attempts</span>
                                    <?php if ($quiz['start_time']): ?>
                                    <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?= date('M d, h:i A', strtotime($quiz['start_time'])) ?> — <?= $quiz['end_time'] ? date('M d, h:i A', strtotime($quiz['end_time'])) : 'Open' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Status toggle -->
                            <?php if ($quiz['status'] === 'DRAFT'): ?>
                            <form method="POST" action="<?= $base ?>/controllers/process_quiz.php" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                <input type="hidden" name="status" value="PUBLISHED">
                                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                                <button type="submit" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-400 px-3 py-1.5 rounded-lg font-bold transition-colors" onclick="return confirm('Publish this quiz? Students will be able to take it.')">
                                    Publish
                                </button>
                            </form>
                            <?php elseif ($quiz['status'] === 'PUBLISHED'): ?>
                            <form method="POST" action="<?= $base ?>/controllers/process_quiz.php" class="inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
                                <input type="hidden" name="status" value="CLOSED">
                                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                                <button type="submit" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 dark:text-rose-400 px-3 py-1.5 rounded-lg font-bold transition-colors">
                                    Close
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <a href="<?= $base ?>/views/faculty/manage_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 px-3 py-1.5 rounded-lg font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Manage Questions
                            </a>
                            
                            <?php if ($quiz['attempt_count'] > 0): ?>
                            <a href="<?= $base ?>/views/faculty/quiz_results.php?quiz_id=<?= $quiz['id'] ?>" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 px-3 py-1.5 rounded-lg font-bold transition-colors">
                                Results
                            </a>
                            <?php endif; ?>
                            
                            <a href="<?= $base ?>/controllers/process_quiz.php?action=delete_quiz&id=<?= $quiz['id'] ?>&course_id=<?= $selectedCourseId ?>" onclick="return confirm('Delete this quiz and all data?')" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Create Quiz Modal -->
    <div id="create-quiz-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Create New Quiz</h2>
                <button onclick="document.getElementById('create-quiz-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_quiz.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="create_quiz">
                
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
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Quiz Title</label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Term MCQ Test" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description (optional)</label>
                    <textarea name="description" rows="2" placeholder="Quiz instructions..." class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" required min="5" max="240" value="30" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Available From (optional)</label>
                        <input type="datetime-local" name="start_time" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Available Until (optional)</label>
                        <input type="datetime-local" name="end_time" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('create-quiz-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Create & Add Questions
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
