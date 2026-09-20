<?php
// views/student/quizzes.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Online Quizzes | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

require_once __DIR__ . '/../../controllers/QuizController.php';
use Controllers\QuizController;

$quizCtrl = new QuizController();
$quizzes = $quizCtrl->getStudentQuizzes($departmentId, $semesterId);

// Check attempts for each quiz
$attempts = [];
foreach ($quizzes as $q) {
    $attempts[$q['id']] = $quizCtrl->getStudentAttempt($q['id'], $studentId);
}

// Group by course
$grouped = [];
foreach ($quizzes as $q) {
    $grouped[$q['course_code']]['course_name'] = $q['course_name'];
    $grouped[$q['course_code']]['items'][] = $q;
}

require_once __DIR__ . '/../../includes/header.php';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-6">
        
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
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="brain" class="w-6 h-6 text-purple-500"></i> Online Quizzes
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Take quizzes and view your results. <?= count($quizzes) ?> quizzes available.</p>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-500 mb-4">
                    <i data-lucide="brain" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Quizzes Available</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">No quizzes have been published for your courses yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped as $courseCode => $group):
                $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                $colorIdx = crc32($courseCode) % count($colors);
                $color = $colors[$colorIdx];
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-r from-<?= $color ?>-50/50 to-transparent dark:from-<?= $color ?>-900/10 dark:to-transparent">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $color ?>-100 text-<?= $color ?>-800 dark:bg-<?= $color ?>-900/40 dark:text-<?= $color ?>-300 border border-<?= $color ?>-200 dark:border-<?= $color ?>-800/50">
                            <?= htmlspecialchars($courseCode) ?>
                        </span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($group['course_name']) ?></h3>
                    </div>
                </div>
                
                <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach ($group['items'] as $quiz):
                        $attempt = $attempts[$quiz['id']] ?? null;
                        $now = time();
                        $isBeforeStart = $quiz['start_time'] && strtotime($quiz['start_time']) > $now;
                        $isAfterEnd = $quiz['end_time'] && strtotime($quiz['end_time']) < $now;
                        
                        if ($attempt) {
                            $status = 'Attempted';
                            $statusColor = 'emerald';
                        } elseif ($isBeforeStart) {
                            $status = 'Upcoming';
                            $statusColor = 'amber';
                        } elseif ($isAfterEnd) {
                            $status = 'Expired';
                            $statusColor = 'slate';
                        } else {
                            $status = 'Available';
                            $statusColor = 'indigo';
                        }
                        
                        $canTake = !$attempt && !$isBeforeStart && !$isAfterEnd;
                    ?>
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($quiz['title']) ?></h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-700 dark:bg-<?= $statusColor ?>-900/30 dark:text-<?= $statusColor ?>-400 shrink-0">
                                        <?= $status ?>
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="flex items-center gap-1"><i data-lucide="help-circle" class="w-3 h-3"></i> <?= $quiz['question_count'] ?> questions</span>
                                    <span class="flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?= $quiz['duration_minutes'] ?> min</span>
                                    <span class="flex items-center gap-1"><i data-lucide="star" class="w-3 h-3"></i> <?= $quiz['total_marks'] ?> marks</span>
                                    <?php if ($quiz['start_time'] || $quiz['end_time']): ?>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="calendar" class="w-3 h-3"></i>
                                        <?= $quiz['start_time'] ? date('M d, h:i A', strtotime($quiz['start_time'])) : 'Open' ?> — <?= $quiz['end_time'] ? date('M d, h:i A', strtotime($quiz['end_time'])) : 'Open' ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Attempt result -->
                                <?php if ($attempt): ?>
                                <div class="mt-2 p-2.5 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                                    <?php $pct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100, 1) : 0; ?>
                                    <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">
                                        Score: <?= $attempt['score'] ?> / <?= $attempt['total_marks'] ?> (<?= $pct ?>%)
                                        &bull; <?= $attempt['correct_count'] ?> correct, <?= $attempt['wrong_count'] ?> wrong
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex items-center gap-2 shrink-0">
                                <?php if ($canTake): ?>
                                <a href="<?= $base ?>/views/student/take_quiz.php?quiz_id=<?= $quiz['id'] ?>" onclick="return confirm('Start this quiz? You will have <?= $quiz['duration_minutes'] ?> minutes to complete it. The timer cannot be paused.')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-bold transition-colors shadow-sm hover:-translate-y-0.5">
                                    <i data-lucide="play" class="w-4 h-4"></i> Start Quiz
                                </a>
                                <?php elseif ($attempt): ?>
                                <a href="<?= $base ?>/views/student/quiz_result.php?quiz_id=<?= $quiz['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 rounded-lg text-xs font-bold transition-colors">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Results
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
