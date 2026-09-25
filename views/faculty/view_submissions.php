<?php
// views/faculty/view_submissions.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');

require_once __DIR__ . '/../../controllers/AssignmentController.php';

use Controllers\AssignmentController;

$assignmentCtrl = new AssignmentController();
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$assignment = $assignmentCtrl->getAssignmentById($assignmentId);

if (!$assignment) {
    $_SESSION['flash_error'] = 'Assignment not found.';
    header('Location: assignments.php');
    exit;
}

$pageTitle = htmlspecialchars($assignment['title']) . ' — Submissions | Faculty Portal';
require_once __DIR__ . '/../../includes/header.php';

$submissions = $assignmentCtrl->getSubmissionsForAssignment($assignmentId, $assignment['course_id']);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$totalStudents = count($submissions);
$submittedCount = 0;
$gradedCount = 0;
$lateCount = 0;
foreach ($submissions as $s) {
    if ($s['submission_id']) {
        $submittedCount++;
        if ($s['marks_obtained'] !== null) $gradedCount++;
        if ($s['is_late']) $lateCount++;
    }
}
$isPast = strtotime($assignment['deadline']) < time();
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

        <!-- Breadcrumb & Header -->
        <div>
            <a href="<?= $base ?>/views/faculty/assignments.php?course_id=<?= $assignment['course_id'] ?>" class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Assignments
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"><?= htmlspecialchars($assignment['title']) ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Deadline: <span class="font-semibold text-<?= $isPast ? 'rose' : 'emerald' ?>-600 dark:text-<?= $isPast ? 'rose' : 'emerald' ?>-400"><?= date('M d, Y h:i A', strtotime($assignment['deadline'])) ?></span>
                &bull; Max Marks: <span class="font-semibold"><?= $assignment['max_marks'] ?></span>
            </p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Students</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?= $totalStudents ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Submitted</p>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400"><?= $submittedCount ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Graded</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $gradedCount ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Late</p>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $lateCount ?></p>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Student</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">File</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Submitted At</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Marks</th>
                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Feedback</th>
                            <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach ($submissions as $sub): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors" id="row-<?= $sub['student_id'] ?>">
                            <td class="px-5 py-3.5">
                                <div>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($sub['name']) ?></span>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($sub['roll_number']) ?></p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if (!$sub['submission_id']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Not Submitted</span>
                                <?php elseif ($sub['marks_obtained'] !== null): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Graded</span>
                                <?php elseif ($sub['is_late']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Late</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">Submitted</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if ($sub['submission_id'] && $sub['file_path'] !== 'offline'): ?>
                                    <a href="<?= $base . '/' . htmlspecialchars($sub['file_path']) ?>" target="_blank" download class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i> <?= htmlspecialchars($sub['file_name']) ?>
                                    </a>
                                <?php elseif ($sub['submission_id'] && $sub['file_path'] === 'offline'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                                        <i data-lucide="clipboard-pen" class="w-3.5 h-3.5"></i> Offline Grade
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-center text-xs text-slate-500">
                                <?= $sub['submitted_at'] ? date('M d, h:i A', strtotime($sub['submitted_at'])) : '—' ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <?php if ($sub['marks_obtained'] !== null): ?>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white"><?= $sub['marks_obtained'] ?></span>
                                    <span class="text-xs text-slate-400">/ <?= $assignment['max_marks'] ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 max-w-[200px]">
                                <span class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2"><?= htmlspecialchars($sub['feedback'] ?? '—') ?></span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button" 
                                    class="grade-btn text-xs px-3 py-1.5 rounded-lg font-bold transition-all shadow-sm inline-flex items-center gap-1.5 <?= $sub['marks_obtained'] !== null ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : ($sub['submission_id'] ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300') ?>"
                                    data-submission-id="<?= (int)($sub['submission_id'] ?? 0) ?>"
                                    data-student-id="<?= (int)$sub['student_id'] ?>"
                                    data-student-name="<?= htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-student-roll="<?= htmlspecialchars($sub['roll_number'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-marks="<?= $sub['marks_obtained'] !== null ? htmlspecialchars($sub['marks_obtained']) : '' ?>"
                                    data-feedback="<?= htmlspecialchars($sub['feedback'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-has-file="<?= !empty($sub['submission_id']) && $sub['file_path'] !== 'offline' ? '1' : '0' ?>"
                                    title="<?= $sub['marks_obtained'] !== null ? 'Modify existing grade' : 'Assign grade & feedback' ?>">
                                    <i data-lucide="<?= $sub['marks_obtained'] !== null ? 'edit-2' : 'award' ?>" class="w-3.5 h-3.5"></i>
                                    <span><?= $sub['marks_obtained'] !== null ? 'Edit Grade' : 'Grade' ?></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Grade Modal -->
    <div id="grade-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="award" class="w-5 h-5 text-indigo-500"></i> Grade Submission
                </h2>
                <button type="button" onclick="closeGradeModal()" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_assignment.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="grade">
                <input type="hidden" name="assignment_id" value="<?= $assignmentId ?>">
                <input type="hidden" name="submission_id" id="grade-submission-id">
                <input type="hidden" name="student_id" id="grade-student-id">
                
                <div class="p-3.5 bg-slate-50 dark:bg-slate-700/50 rounded-lg space-y-1 border border-slate-100 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Student</span>
                        <span id="grade-modal-note" class="text-xs"></span>
                    </div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white" id="grade-student-name"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" id="grade-student-roll"></p>
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Marks Obtained</label>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Max Marks: <strong class="text-indigo-600 dark:text-indigo-400"><?= $assignment['max_marks'] ?></strong></span>
                    </div>
                    <input type="number" name="marks" id="grade-marks" required min="0" max="<?= $assignment['max_marks'] ?>" step="0.5" placeholder="e.g. 85" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Instructor Feedback</label>
                    <textarea name="feedback" id="grade-feedback" rows="3" placeholder="Add comments, guidance, or remarks for the student..." class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"></textarea>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeGradeModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Save Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function openGradeModal(btn) {
        const submissionId = btn.getAttribute('data-submission-id') || 0;
        const studentId = btn.getAttribute('data-student-id') || 0;
        const studentName = btn.getAttribute('data-student-name') || '';
        const studentRoll = btn.getAttribute('data-student-roll') || '';
        const marks = btn.getAttribute('data-marks') || '';
        const feedback = btn.getAttribute('data-feedback') || '';
        const hasFile = btn.getAttribute('data-has-file') === '1';

        document.getElementById('grade-submission-id').value = submissionId;
        document.getElementById('grade-student-id').value = studentId;
        document.getElementById('grade-student-name').textContent = studentName;
        document.getElementById('grade-student-roll').textContent = studentRoll ? 'Roll No: ' + studentRoll : '';
        document.getElementById('grade-marks').value = marks;
        document.getElementById('grade-feedback').value = feedback;

        const noteEl = document.getElementById('grade-modal-note');
        if (noteEl) {
            if (hasFile) {
                noteEl.innerHTML = '<span class="text-indigo-600 dark:text-indigo-400 font-medium">✓ Online Submission</span>';
            } else {
                noteEl.innerHTML = '<span class="text-amber-600 dark:text-amber-400 font-medium">Offline / Direct Grade</span>';
            }
        }

        document.getElementById('grade-modal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeGradeModal() {
        document.getElementById('grade-modal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.grade-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                openGradeModal(this);
            });
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
