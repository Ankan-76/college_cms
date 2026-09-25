<?php
// views/student/assignments.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'Assignments | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['student_profile_id'] ?? $_SESSION['user_id'] ?? 0;

// Get student's department and semester
$stmtStudent = $db->prepare("SELECT department_id, semester_id FROM students WHERE id = ?");
$stmtStudent->execute([$studentId]);
$student = $stmtStudent->fetch();
$departmentId = $student['department_id'] ?? 0;
$semesterId = $student['semester_id'] ?? 0;

require_once __DIR__ . '/../../controllers/AssignmentController.php';
use Controllers\AssignmentController;

$assignmentCtrl = new AssignmentController();
$assignments = $assignmentCtrl->getStudentAssignments($departmentId, $semesterId);

// Get submissions for this student
$submissions = [];
$submittedCount = 0;
$pendingCount = 0;
$gradedCount = 0;
foreach ($assignments as $a) {
    $sub = $assignmentCtrl->getStudentSubmission($a['id'], $studentId);
    $submissions[$a['id']] = $sub;
    if ($sub) {
        $submittedCount++;
        if ($sub['marks_obtained'] !== null) $gradedCount++;
    } else {
        $pendingCount++;
    }
}

// Group by course
$grouped = [];
foreach ($assignments as $a) {
    $grouped[$a['course_code']]['course_name'] = $a['course_name'];
    $grouped[$a['course_code']]['items'][] = $a;
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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="file-up" class="w-6 h-6 text-indigo-500"></i> My Course Assignments
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View posted coursework, download questions, submit your files, and view your sent submissions.</p>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white"><?= count($assignments) ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Sent / Submitted</p>
                <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400"><?= $submittedCount ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Pending</p>
                <p class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $pendingCount ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 text-center">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Graded</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $gradedCount ?></p>
            </div>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4">
                    <i data-lucide="file-check" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Assignments</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">No assignments have been posted for your courses yet.</p>
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
                        <span class="text-xs text-slate-400 font-medium ml-auto"><?= count($group['items']) ?> assignments</span>
                    </div>
                </div>
                
                <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <?php foreach ($group['items'] as $asgn):
                        $sub = $submissions[$asgn['id']] ?? null;
                        $isPast = strtotime($asgn['deadline']) < time();
                        
                        // Determine status
                        if ($sub && $sub['marks_obtained'] !== null) {
                            $status = 'Graded';
                            $statusColor = 'emerald';
                        } elseif ($sub && $sub['is_late']) {
                            $status = 'Submitted (Late)';
                            $statusColor = 'amber';
                        } elseif ($sub) {
                            $status = 'Submitted';
                            $statusColor = 'indigo';
                        } elseif ($isPast) {
                            $status = 'Overdue';
                            $statusColor = 'rose';
                        } else {
                            $status = 'Pending';
                            $statusColor = 'amber';
                        }
                    ?>
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($asgn['title']) ?></h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-700 dark:bg-<?= $statusColor ?>-900/30 dark:text-<?= $statusColor ?>-400 shrink-0">
                                        <?= $status ?>
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        Deadline: <span class="font-semibold text-<?= $isPast ? 'rose' : 'slate' ?>-600 dark:text-<?= $isPast ? 'rose' : 'slate' ?>-400"><?= date('M d, Y h:i A', strtotime($asgn['deadline'])) ?></span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="star" class="w-3 h-3"></i> <?= $asgn['max_marks'] ?> marks
                                    </span>
                                    <?php if (!empty($asgn['faculty_name'])): ?>
                                    <span>by <?= htmlspecialchars($asgn['faculty_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($asgn['description'])): ?>
                                <p class="text-xs text-slate-400 mt-1 line-clamp-1"><?= htmlspecialchars($asgn['description']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Sent Submission & Feedback -->
                                <?php if ($sub): ?>
                                <div class="mt-3.5 p-3.5 rounded-xl border <?= $sub['marks_obtained'] !== null ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700' ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                                            <div class="p-2.5 rounded-lg shrink-0 <?= $sub['marks_obtained'] !== null ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400' ?>">
                                                <i data-lucide="file-check-2" class="w-5 h-5"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                                        Your Sent Submission: <?= htmlspecialchars($sub['file_name']) ?>
                                                    </span>
                                                    <?php if (!empty($sub['file_size'])): ?>
                                                    <span class="text-[11px] text-slate-500 font-medium">
                                                        (<?= round($sub['file_size'] / 1024, 1) ?> KB)
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                    Sent on <span class="font-medium text-slate-700 dark:text-slate-300"><?= date('M d, Y \a\t h:i A', strtotime($sub['submitted_at'])) ?></span>
                                                    <?php if ($sub['is_late']): ?>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 ml-1">Late Submission</span>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <?php if (!empty($sub['file_path']) && $sub['file_path'] !== 'offline'): ?>
                                        <div class="flex items-center gap-2 pt-2.5 sm:pt-0 border-t sm:border-t-0 border-slate-200/80 dark:border-slate-700 justify-end sm:justify-start w-full sm:w-auto shrink-0">
                                            <a href="<?= $base . '/' . htmlspecialchars($sub['file_path']) ?>" target="_blank" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 rounded-lg text-xs font-semibold transition-colors shadow-sm" title="Preview sent file in new browser tab">
                                                <i data-lucide="eye" class="w-3.5 h-3.5 text-indigo-500"></i> View File
                                            </a>
                                            <a href="<?= $base . '/' . htmlspecialchars($sub['file_path']) ?>" download class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-sm" title="Download your submitted file">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($sub['marks_obtained'] !== null): ?>
                                    <div class="mt-3 pt-3 border-t border-emerald-200/80 dark:border-emerald-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                                    Score Awarded: <?= $sub['marks_obtained'] ?> / <?= $asgn['max_marks'] ?>
                                                    (<?= $asgn['max_marks'] > 0 ? round(($sub['marks_obtained'] / $asgn['max_marks']) * 100, 1) : 0 ?>%)
                                                </span>
                                            </div>
                                            <?php if (!empty($sub['feedback'])): ?>
                                            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                                                <strong>Feedback from Faculty:</strong> <?= htmlspecialchars($sub['feedback']) ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 shrink-0">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Graded
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-700/60 justify-end sm:justify-start w-full sm:w-auto shrink-0">
                                <?php if ($asgn['reference_file']): ?>
                                <a href="<?= $base . '/' . htmlspecialchars($asgn['reference_file']) ?>" target="_blank" download class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1 px-3 py-2 sm:py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300 rounded-lg text-xs font-bold transition-colors">
                                    <i data-lucide="download" class="w-3 h-3"></i> Ref File
                                </a>
                                <?php endif; ?>

                                <?php if ($sub && !empty($sub['file_path']) && $sub['file_path'] !== 'offline'): ?>
                                <a href="<?= $base . '/' . htmlspecialchars($sub['file_path']) ?>" target="_blank" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 sm:py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:hover:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold transition-colors shadow-sm" title="View your submitted assignment file">
                                    <i data-lucide="file-check" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">View Sent Assignment</span><span class="sm:hidden">Sent Work</span>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!$sub || ($sub && $sub['marks_obtained'] === null)): ?>
                                <button type="button" onclick="openSubmitModal(<?= $asgn['id'] ?>, '<?= htmlspecialchars(addslashes($asgn['title'])) ?>')" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 sm:py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i> <?= $sub ? 'Re-submit' : 'Submit' ?>
                                </button>
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

    <!-- Submit Modal -->
    <div id="submit-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 border border-slate-200 dark:border-slate-700">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Submit Assignment</h2>
                <button onclick="document.getElementById('submit-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_assignment.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="submit">
                <input type="hidden" name="assignment_id" id="submit-assignment-id">
                
                <p class="text-sm text-slate-600 dark:text-slate-400">Submitting: <span id="submit-assignment-title" class="font-bold text-slate-900 dark:text-white"></span></p>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Upload File</label>
                    <input type="file" name="submission_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.png,.xlsx,.xls" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <p class="mt-1 text-xs text-slate-500">Max 25MB. Allowed: PDF, Word, PPT, Excel, ZIP, images.</p>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('submit-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function openSubmitModal(assignmentId, title) {
        document.getElementById('submit-assignment-id').value = assignmentId;
        document.getElementById('submit-assignment-title').textContent = title;
        document.getElementById('submit-modal').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
