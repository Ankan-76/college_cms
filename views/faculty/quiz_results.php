<?php
// views/faculty/quiz_results.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');

require_once __DIR__ . '/../../controllers/QuizController.php';
use Controllers\QuizController;

$quizCtrl = new QuizController();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$quiz = $quizCtrl->getQuizById($quizId);

if (!$quiz) {
    $_SESSION['flash_error'] = 'Quiz not found.';
    header('Location: quizzes.php');
    exit;
}

$pageTitle = htmlspecialchars($quiz['title']) . ' — Results | Faculty Portal';
require_once __DIR__ . '/../../includes/header.php';

$attempts = $quizCtrl->getAttemptsByQuiz($quizId);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// Statistics
$totalAttempts = count($attempts);
$avgScore = 0;
$highestScore = 0;
$lowestScore = $quiz['total_marks'];
$passCount = 0;

if ($totalAttempts > 0) {
    $scoreSum = 0;
    foreach ($attempts as $a) {
        $scoreSum += (float)$a['score'];
        if ((float)$a['score'] > $highestScore) $highestScore = (float)$a['score'];
        if ((float)$a['score'] < $lowestScore) $lowestScore = (float)$a['score'];
        $pct = $quiz['total_marks'] > 0 ? ($a['score'] / $quiz['total_marks'] * 100) : 0;
        if ($pct >= 40) $passCount++;
    }
    $avgScore = round($scoreSum / $totalAttempts, 1);
}
$passRate = $totalAttempts > 0 ? round($passCount / $totalAttempts * 100, 1) : 0;
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <a href="<?= $base ?>/views/faculty/quizzes.php?course_id=<?= $quiz['course_id'] ?>" class="inline-flex items-center gap-1.5 text-sm text-purple-600 dark:text-purple-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Quizzes
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-6 h-6 text-purple-500"></i> <?= htmlspecialchars($quiz['title']) ?> — Results
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                <?= $quiz['question_count'] ?> questions &bull; <?= $quiz['total_marks'] ?> marks &bull; <?= $quiz['duration_minutes'] ?> min
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Attempts</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?= $totalAttempts ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Average</p>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400"><?= $avgScore ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Highest</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $highestScore ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-rose-500 uppercase tracking-wider mb-1">Lowest</p>
                <p class="text-2xl font-black text-rose-600 dark:text-rose-400"><?= $totalAttempts > 0 ? $lowestScore : '—' ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Pass Rate</p>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $passRate ?>%</p>
            </div>
        </div>

        <!-- Results Table -->
        <?php if (empty($attempts)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-500 mb-4">
                    <i data-lucide="users" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Attempts Yet</h3>
                <p class="text-sm text-slate-500 mt-2">No students have taken this quiz yet.</p>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Student</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Score</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Percentage</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Correct</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Wrong</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Unanswered</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Time Taken</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <?php foreach ($attempts as $rank => $att):
                                $pct = $att['total_marks'] > 0 ? round(($att['score'] / $att['total_marks']) * 100, 1) : 0;
                                $pctColor = $pct >= 75 ? 'emerald' : ($pct >= 40 ? 'amber' : 'rose');
                                $mins = floor($att['time_taken_seconds'] / 60);
                                $secs = $att['time_taken_seconds'] % 60;
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-5 py-3.5 text-sm font-bold text-slate-400"><?= $rank + 1 ?></td>
                                <td class="px-5 py-3.5">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($att['name']) ?></span>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($att['roll_number']) ?></p>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white"><?= $att['score'] ?></span>
                                    <span class="text-xs text-slate-400">/ <?= $att['total_marks'] ?></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $pctColor ?>-100 text-<?= $pctColor ?>-800 dark:bg-<?= $pctColor ?>-900/30 dark:text-<?= $pctColor ?>-400">
                                        <?= $pct ?>%
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center text-sm font-semibold text-emerald-600 dark:text-emerald-400"><?= $att['correct_count'] ?></td>
                                <td class="px-5 py-3.5 text-center text-sm font-semibold text-rose-600 dark:text-rose-400"><?= $att['wrong_count'] ?></td>
                                <td class="px-5 py-3.5 text-center text-sm font-semibold text-slate-400"><?= $att['unanswered_count'] ?></td>
                                <td class="px-5 py-3.5 text-center text-xs text-slate-500 font-medium"><?= $mins ?>m <?= $secs ?>s</td>
                                <td class="px-5 py-3.5 text-right text-xs text-slate-500 font-medium"><?= date('M d, h:i A', strtotime($att['submitted_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
