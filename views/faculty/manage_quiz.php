<?php
// views/faculty/manage_quiz.php
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

$pageTitle = htmlspecialchars($quiz['title']) . ' — Manage Questions | Faculty Portal';
require_once __DIR__ . '/../../includes/header.php';

$questions = $quizCtrl->getQuizQuestions($quizId);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$editQuestionId = isset($_GET['edit_q']) ? (int)$_GET['edit_q'] : 0;
$editQuestion = null;
if ($editQuestionId > 0) {
    foreach ($questions as $q) {
        if ($q['id'] === $editQuestionId) { $editQuestion = $q; break; }
    }
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-5xl mx-auto space-y-6">
        
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

        <!-- Header -->
        <div>
            <a href="<?= $base ?>/views/faculty/quizzes.php?course_id=<?= $quiz['course_id'] ?>" class="inline-flex items-center gap-1.5 text-sm text-purple-600 dark:text-purple-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Quizzes
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="brain" class="w-6 h-6 text-purple-500"></i> <?= htmlspecialchars($quiz['title']) ?>
                    </h1>
                    <div class="flex items-center gap-3 mt-1 text-sm text-slate-500">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $quiz['status'] === 'DRAFT' ? 'amber' : ($quiz['status'] === 'PUBLISHED' ? 'emerald' : 'slate') ?>-100 text-<?= $quiz['status'] === 'DRAFT' ? 'amber' : ($quiz['status'] === 'PUBLISHED' ? 'emerald' : 'slate') ?>-700 dark:bg-<?= $quiz['status'] === 'DRAFT' ? 'amber' : ($quiz['status'] === 'PUBLISHED' ? 'emerald' : 'slate') ?>-900/30 dark:text-<?= $quiz['status'] === 'DRAFT' ? 'amber' : ($quiz['status'] === 'PUBLISHED' ? 'emerald' : 'slate') ?>-400">
                            <?= $quiz['status'] ?>
                        </span>
                        <span><?= count($questions) ?> questions</span>
                        <span>&bull;</span>
                        <span><?= $quiz['total_marks'] ?> marks</span>
                        <span>&bull;</span>
                        <span><?= $quiz['duration_minutes'] ?> min</span>
                    </div>
                </div>
                
                <button type="button" onclick="document.getElementById('add-question-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-sm hover:-translate-y-0.5 shrink-0">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Question
                </button>
            </div>
        </div>

        <!-- Questions List -->
        <?php if (empty($questions)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-500 mb-4">
                    <i data-lucide="help-circle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Questions Added</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">Add MCQ questions to build your quiz.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($questions as $idx => $q):
                    $optionLabels = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-sm font-black shrink-0">
                                <?= $idx + 1 ?>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($q['question_text']) ?></p>
                                <span class="text-xs text-slate-400 font-medium"><?= $q['marks'] ?> mark<?= $q['marks'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <a href="?quiz_id=<?= $quizId ?>&edit_q=<?= $q['id'] ?>#edit-section" class="p-1.5 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <a href="<?= $base ?>/controllers/process_quiz.php?action=delete_question&id=<?= $q['id'] ?>&quiz_id=<?= $quizId ?>" onclick="return confirm('Delete this question?')" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 ml-11">
                        <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
                            $isCorrect = $q['correct_option'] === $opt;
                        ?>
                        <div class="flex items-center gap-2 p-2.5 rounded-lg text-sm <?= $isCorrect ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-50 dark:bg-slate-700/30 border border-transparent' ?>">
                            <span class="w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center shrink-0 <?= $isCorrect ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300' ?>">
                                <?= $opt ?>
                            </span>
                            <span class="<?= $isCorrect ? 'text-emerald-700 dark:text-emerald-400 font-semibold' : 'text-slate-600 dark:text-slate-400' ?>"><?= htmlspecialchars($optionLabels[$opt]) ?></span>
                            <?php if ($isCorrect): ?>
                            <i data-lucide="check" class="w-4 h-4 text-emerald-500 ml-auto shrink-0"></i>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Add Question Modal -->
    <div id="add-question-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Add Question</h2>
                <button onclick="document.getElementById('add-question-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_quiz.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Question</label>
                    <textarea name="question_text" required rows="3" placeholder="Enter the question..." class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option A</label>
                        <input type="text" name="option_a" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option B</label>
                        <input type="text" name="option_b" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option C</label>
                        <input type="text" name="option_c" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option D</label>
                        <input type="text" name="option_d" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Correct Answer</label>
                        <select name="correct_option" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Marks</label>
                        <input type="number" name="marks" required min="1" max="100" value="1" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('add-question-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Add Question
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Question Section (inline) -->
    <?php if ($editQuestion): ?>
    <div id="edit-section" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Question</h2>
                <a href="?quiz_id=<?= $quizId ?>" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </a>
            </div>
            <form action="<?= $base ?>/controllers/process_quiz.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_question">
                <input type="hidden" name="question_id" value="<?= $editQuestion['id'] ?>">
                <input type="hidden" name="quiz_id" value="<?= $quizId ?>">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Question</label>
                    <textarea name="question_text" required rows="3" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"><?= htmlspecialchars($editQuestion['question_text']) ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option A</label>
                        <input type="text" name="option_a" required value="<?= htmlspecialchars($editQuestion['option_a']) ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option B</label>
                        <input type="text" name="option_b" required value="<?= htmlspecialchars($editQuestion['option_b']) ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option C</label>
                        <input type="text" name="option_c" required value="<?= htmlspecialchars($editQuestion['option_c']) ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Option D</label>
                        <input type="text" name="option_d" required value="<?= htmlspecialchars($editQuestion['option_d']) ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Correct Answer</label>
                        <select name="correct_option" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                            <?php foreach (['A','B','C','D'] as $o): ?>
                            <option value="<?= $o ?>" <?= $editQuestion['correct_option'] === $o ? 'selected' : '' ?>><?= $o ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Marks</label>
                        <input type="number" name="marks" required min="1" max="100" value="<?= $editQuestion['marks'] ?>" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <a href="?quiz_id=<?= $quizId ?>" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-center">
                        Cancel
                    </a>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Update Question
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
