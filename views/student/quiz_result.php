<?php
// views/student/quiz_result.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('STUDENT');

require_once __DIR__ . '/../../controllers/QuizController.php';
use Controllers\QuizController;

$quizCtrl = new QuizController();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$studentId = $_SESSION['user_id'];

$quiz = $quizCtrl->getQuizById($quizId);
$attempt = $quizCtrl->getStudentAttempt($quizId, $studentId);

if (!$quiz || !$attempt) {
    $_SESSION['flash_error'] = 'Result not found.';
    header('Location: quizzes.php');
    exit;
}

$pageTitle = htmlspecialchars($quiz['title']) . ' — Result | Student Portal';
require_once __DIR__ . '/../../includes/header.php';

$questions = $quizCtrl->getQuizQuestions($quizId);
$answers = json_decode($attempt['answers'], true) ?? [];
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$pct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100, 1) : 0;
$pctColor = $pct >= 75 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose');
$mins = floor($attempt['time_taken_seconds'] / 60);
$secs = $attempt['time_taken_seconds'] % 60;
$showResults = $quiz['show_results'];
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header -->
        <div>
            <a href="<?= $base ?>/views/student/quizzes.php" class="inline-flex items-center gap-1.5 text-sm text-purple-600 dark:text-purple-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Quizzes
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="trophy" class="w-6 h-6 text-amber-500"></i> Quiz Result
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= htmlspecialchars($quiz['title']) ?></p>
        </div>

        <!-- Score Card -->
        <div class="bg-gradient-to-br from-<?= $pctColor ?>-500 to-<?= $pctColor ?>-700 rounded-2xl p-8 text-white shadow-xl shadow-<?= $pctColor ?>-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full blur-3xl -translate-y-10 translate-x-10"></div>
            <div class="relative z-10 text-center">
                <p class="text-sm font-medium text-white/80 uppercase tracking-wider mb-2">Your Score</p>
                <p class="text-6xl font-black mb-1"><?= $attempt['score'] ?> <span class="text-2xl text-white/70">/ <?= $attempt['total_marks'] ?></span></p>
                <p class="text-3xl font-bold text-white/90 mb-4"><?= $pct ?>%</p>
                
                <div class="flex justify-center gap-6 text-sm">
                    <div class="text-center">
                        <p class="text-2xl font-black"><?= $attempt['correct_count'] ?></p>
                        <p class="text-white/70 text-xs font-medium">Correct</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black"><?= $attempt['wrong_count'] ?></p>
                        <p class="text-white/70 text-xs font-medium">Wrong</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black"><?= $attempt['unanswered_count'] ?></p>
                        <p class="text-white/70 text-xs font-medium">Unanswered</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-black"><?= $mins ?>m <?= $secs ?>s</p>
                        <p class="text-white/70 text-xs font-medium">Time Taken</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($showResults): ?>
        <!-- Question-by-Question Review -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="list-checks" class="w-5 h-5 text-indigo-500"></i> Detailed Review
            </h2>
            
            <?php foreach ($questions as $idx => $q):
                $qId = (string)$q['id'];
                $selected = $answers[$qId] ?? null;
                $isCorrect = $selected && strtoupper($selected) === $q['correct_option'];
                $isWrong = $selected && !$isCorrect;
                $isSkipped = !$selected;
                
                $borderColor = $isCorrect ? 'emerald' : ($isWrong ? 'rose' : 'amber');
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border-l-4 border-<?= $borderColor ?>-500 border-r border-t border-b border-r-slate-200 border-t-slate-200 border-b-slate-200 dark:border-r-slate-700 dark:border-t-slate-700 dark:border-b-slate-700 p-5">
                <div class="flex items-start gap-3 mb-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-black shrink-0 <?= $isCorrect ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($isWrong ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400') ?>">
                        <?= $idx + 1 ?>
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($q['question_text']) ?></p>
                        <div class="flex items-center gap-2 mt-1">
                            <?php if ($isCorrect): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Correct</span>
                            <?php elseif ($isWrong): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400"><i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Wrong</span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400"><i data-lucide="minus-circle" class="w-3.5 h-3.5"></i> Skipped</span>
                            <?php endif; ?>
                            <span class="text-xs text-slate-400">&bull; <?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 ml-11">
                    <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $opt => $text):
                        $isThisCorrect = $q['correct_option'] === $opt;
                        $isThisSelected = $selected && strtoupper($selected) === $opt;
                        
                        if ($isThisCorrect) {
                            $optClass = 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700';
                            $labelClass = 'bg-emerald-500 text-white';
                            $textClass = 'text-emerald-700 dark:text-emerald-400 font-semibold';
                        } elseif ($isThisSelected && !$isThisCorrect) {
                            $optClass = 'bg-rose-50 dark:bg-rose-900/20 border border-rose-300 dark:border-rose-700';
                            $labelClass = 'bg-rose-500 text-white';
                            $textClass = 'text-rose-700 dark:text-rose-400 line-through';
                        } else {
                            $optClass = 'bg-slate-50 dark:bg-slate-700/30 border border-transparent';
                            $labelClass = 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300';
                            $textClass = 'text-slate-600 dark:text-slate-400';
                        }
                    ?>
                    <div class="flex items-center gap-2 p-2.5 rounded-lg text-sm <?= $optClass ?>">
                        <span class="w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center shrink-0 <?= $labelClass ?>"><?= $opt ?></span>
                        <span class="<?= $textClass ?>"><?= htmlspecialchars($text) ?></span>
                        <?php if ($isThisCorrect): ?>
                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 ml-auto shrink-0"></i>
                        <?php elseif ($isThisSelected && !$isThisCorrect): ?>
                        <i data-lucide="x" class="w-4 h-4 text-rose-500 ml-auto shrink-0"></i>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-8 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
            <i data-lucide="eye-off" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Detailed Results Hidden</h3>
            <p class="text-sm text-slate-500 mt-1">The faculty has disabled detailed results for this quiz.</p>
        </div>
        <?php endif; ?>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
