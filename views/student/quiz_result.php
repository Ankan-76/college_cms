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

$canViewResult = $quizCtrl->canViewResults($quiz);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$hasEndTime = !empty($quiz['end_time']) && $quiz['end_time'] !== '0000-00-00 00:00:00';
$mins = floor((int)$attempt['time_taken_seconds'] / 60);
$secs = (int)$attempt['time_taken_seconds'] % 60;

$pageTitle = htmlspecialchars($quiz['title']) . ($canViewResult ? ' — Result' : ' — Submission Confirmed') . ' | Student Portal';
require_once __DIR__ . '/../../includes/header.php';

if ($canViewResult) {
    $questions = $quizCtrl->getQuizQuestions($quizId);
    $answers = json_decode($attempt['answers'], true) ?? [];
    $pct = $attempt['total_marks'] > 0 ? round(($attempt['score'] / $attempt['total_marks']) * 100, 1) : 0;
    $pctColor = $pct >= 75 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose');
    $showResults = $quiz['show_results'];
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Flash Notifications -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 flex items-center gap-3 shadow-xs">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 flex items-center gap-3 shadow-xs">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (!$canViewResult): ?>
        <!-- ══════════════════════════════════════════════════════════
             STATE 1: RESULTS WITHHELD UNTIL QUIZ CLOSES / DEADLINE
             ══════════════════════════════════════════════════════════ -->

        <!-- Navigation & Header -->
        <div>
            <a href="<?= $base ?>/views/student/quizzes.php" class="inline-flex items-center gap-1.5 text-sm text-purple-600 dark:text-purple-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Quizzes
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-500"></i> Submission Confirmed
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        <?= htmlspecialchars($quiz['title']) ?> &bull; <?= htmlspecialchars($quiz['course_name'] ?? $quiz['course_code'] ?? 'Course') ?>
                    </p>
                </div>
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/60 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Results Pending Release</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Submission Status Container -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 dark:border-slate-700 space-y-6">
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 pb-6 border-b border-slate-100 dark:border-slate-700/60">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/25">
                    <i data-lucide="shield-check" class="w-7 h-7"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Your Responses Have Been Recorded</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Your exam submission has been securely stored in the system. To ensure examination integrity and prevent answer sharing while other students are still completing their tests, detailed scores and answer reviews are not released immediately.
                    </p>
                </div>
            </div>

            <!-- Release Policy Notice Box -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-indigo-500/10 dark:from-amber-950/40 dark:via-slate-800/40 dark:to-indigo-950/40 border border-amber-200/80 dark:border-amber-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Release Condition</p>
                        <p class="text-sm text-slate-700 dark:text-slate-300 font-medium">
                            <?php if ($hasEndTime): ?>
                                Results will be published automatically after <strong class="text-slate-900 dark:text-white"><?= date('D, M j, Y \a\t g:i A', strtotime($quiz['end_time'])) ?></strong> or once closed by faculty.
                            <?php else: ?>
                                Results will unlock as soon as the faculty closes this quiz session.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php if ($hasEndTime): ?>
                <div class="shrink-0 bg-white/90 dark:bg-slate-800/90 px-4 py-2.5 rounded-xl border border-amber-200/80 dark:border-amber-800/80 text-center sm:text-right shadow-xs">
                    <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 block">Exam Deadline</span>
                    <span class="text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-400 flex items-center gap-1 justify-center sm:justify-end">
                        <i data-lucide="calendar-clock" class="w-4 h-4"></i> <?= date('M d, g:i A', strtotime($quiz['end_time'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Safe Submission Overview (No scores or answer leaks) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60">
                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-500"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Submitted On</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                        <?= !empty($attempt['started_at']) ? date('M d, Y, h:i A', strtotime($attempt['started_at'])) : date('M d, Y') ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60">
                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
                        <i data-lucide="timer" class="w-4 h-4 text-purple-500"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Time Taken</span>
                    </div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                        <?= $mins ?>m <?= $secs ?>s <span class="text-xs font-normal text-slate-400">/ <?= (int)$quiz['duration_minutes'] ?>m max</span>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200/80 dark:border-slate-700/60">
                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400 mb-1">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Status</span>
                    </div>
                    <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                        Received & Secured
                    </p>
                </div>
            </div>

            <!-- Helpful Guidance Box -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/30 border border-slate-200/70 dark:border-slate-700/50 text-xs sm:text-sm text-slate-600 dark:text-slate-400 space-y-1.5">
                <p class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i> What happens next?
                </p>
                <p>• Your answers have been recorded and locked against further edits.</p>
                <p>• Once the quiz deadline passes or your instructor closes the exam, return to this page to view your total score, correct/incorrect questions, and percentage.</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <a href="<?= $base ?>/views/student/quizzes.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold transition-colors shadow-sm">
                    <i data-lucide="list" class="w-4 h-4"></i> Return to Quizzes
                </a>
                <a href="<?= $base ?>/views/student/dashboard.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Student Dashboard
                </a>
            </div>

        </div>

        <?php else: ?>
        <!-- ══════════════════════════════════════════════════════════
             STATE 2: RESULTS RELEASED (QUIZ CLOSED OR DEADLINE PASSED)
             ══════════════════════════════════════════════════════════ -->

        <!-- Header -->
        <div>
            <a href="<?= $base ?>/views/student/quizzes.php" class="inline-flex items-center gap-1.5 text-sm text-purple-600 dark:text-purple-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Quizzes
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="trophy" class="w-6 h-6 text-amber-500"></i> Quiz Result
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                <span><?= htmlspecialchars($quiz['title']) ?></span>
                <?php if ($quiz['status'] === 'CLOSED'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        <i data-lucide="lock" class="w-3 h-3"></i> Quiz Closed by Faculty
                    </span>
                <?php elseif ($hasEndTime && strtotime($quiz['end_time']) <= time()): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <i data-lucide="calendar-check" class="w-3 h-3"></i> Deadline Concluded
                    </span>
                <?php endif; ?>
            </p>
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

        <?php endif; ?>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
