<?php
// views/student/take_quiz.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('STUDENT');

require_once __DIR__ . '/../../controllers/QuizController.php';
use Controllers\QuizController;

$quizCtrl = new QuizController();
$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$studentId = $_SESSION['user_id'];

$quiz = $quizCtrl->getQuizById($quizId);
if (!$quiz || $quiz['status'] !== 'PUBLISHED') {
    $_SESSION['flash_error'] = 'Quiz not available.';
    header('Location: quizzes.php');
    exit;
}

// Check already attempted
$existingAttempt = $quizCtrl->getStudentAttempt($quizId, $studentId);
if ($existingAttempt) {
    $_SESSION['flash_error'] = 'You have already attempted this quiz.';
    header('Location: quiz_result.php?quiz_id=' . $quizId);
    exit;
}

// Check time window
$now = time();
if ($quiz['start_time'] && strtotime($quiz['start_time']) > $now) {
    $_SESSION['flash_error'] = 'This quiz has not started yet.';
    header('Location: quizzes.php');
    exit;
}
if ($quiz['end_time'] && strtotime($quiz['end_time']) < $now) {
    $_SESSION['flash_error'] = 'This quiz has expired.';
    header('Location: quizzes.php');
    exit;
}

$questions = $quizCtrl->getQuizQuestions($quizId);
if (empty($questions)) {
    $_SESSION['flash_error'] = 'This quiz has no questions.';
    header('Location: quizzes.php');
    exit;
}

// Shuffle if enabled
if ($quiz['shuffle_questions']) {
    shuffle($questions);
}

$pageTitle = htmlspecialchars($quiz['title']) . ' | Quiz';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
$totalQuestions = count($questions);
$durationSeconds = (int)$quiz['duration_minutes'] * 60;
$startedAt = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { primary: '#4f46e5' },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        .quiz-option { transition: all 0.2s ease; }
        .quiz-option:hover { transform: translateY(-1px); }
        .quiz-option.selected { border-color: #4f46e5; background: rgba(79, 70, 229, 0.08); }
        .dark .quiz-option.selected { background: rgba(79, 70, 229, 0.2); }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-sans antialiased min-h-screen">

    <!-- Top Bar -->
    <header class="sticky top-0 z-50 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($quiz['title']) ?></h1>
                <p class="text-xs text-slate-500"><?= $totalQuestions ?> questions &bull; <?= $quiz['total_marks'] ?> marks</p>
            </div>
            
            <!-- Timer -->
            <div id="timer-display" class="flex items-center gap-2 px-4 py-2 bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl">
                <i data-lucide="timer" class="w-5 h-5 text-rose-500"></i>
                <span id="timer-text" class="text-lg font-black text-rose-600 dark:text-rose-400 tabular-nums"><?= sprintf('%02d:%02d', floor($durationSeconds / 60), $durationSeconds % 60) ?></span>
            </div>
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 flex gap-6">
        
        <!-- Question Navigator (sidebar) -->
        <div class="hidden lg:block w-48 shrink-0">
            <div class="sticky top-24 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Questions</p>
                <div class="grid grid-cols-5 gap-1.5" id="question-nav">
                    <?php for ($i = 0; $i < $totalQuestions; $i++): ?>
                    <button type="button" onclick="goToQuestion(<?= $i ?>)" id="nav-btn-<?= $i ?>" class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                        <?= $i + 1 ?>
                    </button>
                    <?php endfor; ?>
                </div>
                
                <div class="mt-4 space-y-1.5 text-xs text-slate-500">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-indigo-500"></span> Current</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-emerald-500"></span> Answered</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-slate-200 dark:bg-slate-700"></span> Unanswered</div>
                </div>
                
                <!-- Progress -->
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Progress</span>
                        <span id="progress-text">0 / <?= $totalQuestions ?></span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div id="progress-bar" class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Area -->
        <div class="flex-1">
            <form id="quiz-form" action="<?= $base ?>/controllers/process_quiz.php" method="POST">
                <input type="hidden" name="action" value="submit_attempt">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                <input type="hidden" name="started_at" value="<?= $startedAt ?>">
                <input type="hidden" name="time_taken" id="time-taken-input" value="0">

                <?php foreach ($questions as $idx => $q): ?>
                <div class="question-slide <?= $idx > 0 ? 'hidden' : '' ?>" id="question-<?= $idx ?>">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
                        <!-- Question header -->
                        <div class="flex items-center gap-3 mb-6">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white text-sm font-black shadow-lg shadow-purple-500/20">
                                <?= $idx + 1 ?>
                            </span>
                            <div>
                                <span class="text-xs text-slate-400 font-medium">Question <?= $idx + 1 ?> of <?= $totalQuestions ?></span>
                                <span class="text-xs text-slate-400 ml-2">&bull; <?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        
                        <!-- Question text -->
                        <p class="text-lg font-semibold text-slate-900 dark:text-white mb-6 leading-relaxed"><?= htmlspecialchars($q['question_text']) ?></p>
                        
                        <!-- Options -->
                        <div class="space-y-3">
                            <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $opt => $text): ?>
                            <label class="quiz-option block cursor-pointer rounded-xl border-2 border-slate-200 dark:border-slate-700 p-4 hover:border-indigo-300 dark:hover:border-indigo-700">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $opt ?>" class="sr-only peer" onchange="markAnswered(<?= $idx ?>)">
                                    <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-sm font-bold flex items-center justify-center peer-checked:bg-indigo-500 peer-checked:text-white transition-colors shrink-0">
                                        <?= $opt ?>
                                    </span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300 font-medium"><?= htmlspecialchars($text) ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Navigation buttons -->
                    <div class="flex justify-between items-center mt-4">
                        <?php if ($idx > 0): ?>
                        <button type="button" onclick="goToQuestion(<?= $idx - 1 ?>)" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i> Previous
                        </button>
                        <?php else: ?>
                        <div></div>
                        <?php endif; ?>
                        
                        <?php if ($idx < $totalQuestions - 1): ?>
                        <button type="button" onclick="goToQuestion(<?= $idx + 1 ?>)" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors text-sm shadow-sm">
                            Next <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                        <?php else: ?>
                        <button type="button" onclick="confirmSubmit()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition-colors text-sm shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5">
                            <i data-lucide="check-circle" class="w-4 h-4"></i> Submit Quiz
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </form>
            
            <!-- Mobile question nav -->
            <div class="lg:hidden mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Jump to Question</p>
                <div class="flex flex-wrap gap-1.5" id="question-nav-mobile">
                    <?php for ($i = 0; $i < $totalQuestions; $i++): ?>
                    <button type="button" onclick="goToQuestion(<?= $i ?>)" id="nav-btn-mobile-<?= $i ?>" class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-indigo-100 transition-colors">
                        <?= $i + 1 ?>
                    </button>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize icons
        document.addEventListener('DOMContentLoaded', () => { if (typeof lucide !== 'undefined') lucide.createIcons(); });

        const totalQuestions = <?= $totalQuestions ?>;
        const durationSeconds = <?= $durationSeconds ?>;
        let currentQuestion = 0;
        let answeredSet = new Set();
        let timeRemaining = durationSeconds;
        let elapsedSeconds = 0;

        // ── Timer ──────────────────────────────────────
        const timerText = document.getElementById('timer-text');
        const timerDisplay = document.getElementById('timer-display');

        const timerInterval = setInterval(() => {
            timeRemaining--;
            elapsedSeconds++;
            
            const mins = Math.floor(timeRemaining / 60);
            const secs = timeRemaining % 60;
            timerText.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            
            // Warning colors
            if (timeRemaining <= 60) {
                timerDisplay.className = 'flex items-center gap-2 px-4 py-2 bg-rose-100 dark:bg-rose-900/50 border border-rose-300 dark:border-rose-700 rounded-xl animate-pulse';
            } else if (timeRemaining <= 300) {
                timerDisplay.className = 'flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl';
            }
            
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                autoSubmit();
            }
        }, 1000);

        // ── Navigation ─────────────────────────────────
        function goToQuestion(idx) {
            document.querySelectorAll('.question-slide').forEach(el => el.classList.add('hidden'));
            document.getElementById(`question-${idx}`).classList.remove('hidden');
            currentQuestion = idx;
            updateNavHighlight();
        }

        function updateNavHighlight() {
            for (let i = 0; i < totalQuestions; i++) {
                const btn = document.getElementById(`nav-btn-${i}`);
                const btnMobile = document.getElementById(`nav-btn-mobile-${i}`);
                let classes;
                
                if (i === currentQuestion) {
                    classes = 'w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center bg-indigo-500 text-white shadow-sm';
                } else if (answeredSet.has(i)) {
                    classes = 'w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center bg-emerald-500 text-white';
                } else {
                    classes = 'w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors';
                }
                
                if (btn) btn.className = classes;
                if (btnMobile) btnMobile.className = classes;
            }
        }

        function markAnswered(idx) {
            answeredSet.add(idx);
            updateNavHighlight();
            updateProgress();
            
            // Visual feedback on options
            const container = document.getElementById(`question-${idx}`);
            container.querySelectorAll('.quiz-option').forEach(opt => {
                const radio = opt.querySelector('input[type="radio"]');
                if (radio.checked) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });

            // Save to sessionStorage
            saveAnswers();
        }

        function updateProgress() {
            const count = answeredSet.size;
            document.getElementById('progress-text').textContent = `${count} / ${totalQuestions}`;
            document.getElementById('progress-bar').style.width = `${(count / totalQuestions) * 100}%`;
        }

        // ── Save/Restore ───────────────────────────────
        function saveAnswers() {
            const formData = new FormData(document.getElementById('quiz-form'));
            const answers = {};
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('answers[')) answers[key] = value;
            }
            sessionStorage.setItem('quiz_<?= $quizId ?>_answers', JSON.stringify(answers));
        }

        function restoreAnswers() {
            const saved = sessionStorage.getItem('quiz_<?= $quizId ?>_answers');
            if (!saved) return;
            const answers = JSON.parse(saved);
            for (const [key, value] of Object.entries(answers)) {
                const radio = document.querySelector(`input[name="${key}"][value="${value}"]`);
                if (radio) {
                    radio.checked = true;
                    // Find question index
                    const slide = radio.closest('.question-slide');
                    if (slide) {
                        const idx = parseInt(slide.id.replace('question-', ''));
                        answeredSet.add(idx);
                        // Visual
                        slide.querySelectorAll('.quiz-option').forEach(opt => {
                            const r = opt.querySelector('input[type="radio"]');
                            if (r && r.checked) opt.classList.add('selected');
                        });
                    }
                }
            }
            updateNavHighlight();
            updateProgress();
        }

        // ── Submit ─────────────────────────────────────
        function confirmSubmit() {
            const unanswered = totalQuestions - answeredSet.size;
            let msg = 'Are you sure you want to submit?';
            if (unanswered > 0) {
                msg = `You have ${unanswered} unanswered question${unanswered > 1 ? 's' : ''}. Submit anyway?`;
            }
            if (confirm(msg)) {
                submitQuiz();
            }
        }

        function autoSubmit() {
            alert('Time is up! Your quiz will be submitted automatically.');
            submitQuiz();
        }

        function submitQuiz() {
            clearInterval(timerInterval);
            document.getElementById('time-taken-input').value = elapsedSeconds;
            sessionStorage.removeItem('quiz_<?= $quizId ?>_answers');
            document.getElementById('quiz-form').submit();
        }

        // Prevent accidental navigation
        window.addEventListener('beforeunload', (e) => {
            e.preventDefault();
            e.returnValue = '';
        });

        // Initialize
        restoreAnswers();
        updateNavHighlight();
    </script>
</body>
</html>
