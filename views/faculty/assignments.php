<?php
// views/faculty/assignments.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Assignments Portal | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php';
require_once __DIR__ . '/../../controllers/AssignmentController.php';

use Controllers\AttendanceController;
use Controllers\AssignmentController;

$facultyId = $_SESSION['faculty_profile_id'] ?? $_SESSION['user_id'];
$attendanceCtrl = new AttendanceController();
$courses = $attendanceCtrl->getFacultyCourses($facultyId);

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses) > 0 ? $courses[0]['id'] : 0);

$assignmentCtrl = new AssignmentController();
$assignments = [];
if ($selectedCourseId > 0) {
    $assignments = $assignmentCtrl->getAssignmentsByCourse($selectedCourseId);
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
                    <i data-lucide="file-up" class="w-6 h-6 text-indigo-500"></i> Assignments Portal
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Create assignments with deadlines, review student submissions, and provide grades & feedback.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('create-assignment-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-sm hover:-translate-y-0.5">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Assignment
                </button>
            </div>
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

        <!-- Assignments Grid -->
        <?php if (empty($assignments) && $selectedCourseId > 0): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4">
                    <i data-lucide="file-plus-2" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Assignments Created</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">Create your first assignment for this course using the "New Assignment" button above.</p>
            </div>
        <?php elseif ($selectedCourseId > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($assignments as $asgn):
                    $isPast = strtotime($asgn['deadline']) < time();
                    $deadlineColor = $isPast ? 'rose' : 'emerald';
                    $statusLabel = $asgn['status'] === 'CLOSED' ? 'Closed' : ($isPast ? 'Overdue' : 'Active');
                    $statusColor = $asgn['status'] === 'CLOSED' ? 'slate' : ($isPast ? 'rose' : 'emerald');
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg transition-all group relative flex flex-col">
                    <!-- Status badge -->
                    <div class="flex items-start justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-700 dark:bg-<?= $statusColor ?>-900/30 dark:text-<?= $statusColor ?>-400">
                            <?= $statusLabel ?>
                        </span>
                        <div class="flex gap-1">
                            <a href="<?= $base ?>/views/faculty/view_submissions.php?assignment_id=<?= $asgn['id'] ?>" class="p-1.5 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="View Submissions & Grades">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <button type="button" 
                                onclick='openEditModal(<?= htmlspecialchars(json_encode([
                                    'id' => (int)$asgn['id'],
                                    'title' => $asgn['title'],
                                    'description' => $asgn['description'] ?? '',
                                    'max_marks' => (int)$asgn['max_marks'],
                                    'deadline' => date('Y-m-d\TH:i', strtotime($asgn['deadline'])),
                                    'allow_late' => (int)($asgn['allow_late'] ?? 1),
                                    'status' => $asgn['status'] ?? 'ACTIVE',
                                    'reference_file' => $asgn['reference_file'] ? basename($asgn['reference_file']) : ''
                                ]), ENT_QUOTES, 'UTF-8') ?>)' 
                                class="p-1.5 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" 
                                title="Edit Assignment (Extend date, marks, etc.)">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <a href="<?= $base ?>/controllers/process_assignment.php?action=delete&id=<?= $asgn['id'] ?>&course_id=<?= $selectedCourseId ?>" onclick="return confirm('Delete this assignment and all submissions?')" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1 line-clamp-2" title="<?= htmlspecialchars($asgn['title']) ?>">
                        <?= htmlspecialchars($asgn['title']) ?>
                    </h3>
                    
                    <?php if (!empty($asgn['description'])): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 line-clamp-2"><?= htmlspecialchars($asgn['description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="mt-auto pt-4 space-y-2">
                        <!-- Deadline -->
                        <div class="flex items-center gap-2 text-xs text-<?= $deadlineColor ?>-600 dark:text-<?= $deadlineColor ?>-400 font-medium">
                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                            Deadline: <?= date('M d, Y h:i A', strtotime($asgn['deadline'])) ?>
                        </div>
                        
                        <!-- Stats -->
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-700">
                            <span class="flex items-center gap-1">
                                <i data-lucide="users" class="w-3 h-3"></i>
                                <?= $asgn['submission_count'] ?> submissions
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="check-circle" class="w-3 h-3"></i>
                                <?= $asgn['graded_count'] ?> graded
                            </span>
                            <span class="font-semibold bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">
                                <?= $asgn['max_marks'] ?> marks
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                        <?php if ($asgn['reference_file']): ?>
                        <a href="<?= $base . '/' . htmlspecialchars($asgn['reference_file']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                            <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> Reference File
                        </a>
                        <?php else: ?>
                        <span class="text-xs text-slate-400">No attachment</span>
                        <?php endif; ?>

                        <a href="<?= $base ?>/views/faculty/view_submissions.php?assignment_id=<?= $asgn['id'] ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Grades & Submissions <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Create Assignment Modal -->
    <div id="create-assignment-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Create New Assignment</h2>
                <button onclick="document.getElementById('create-assignment-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_assignment.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
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
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assignment Title</label>
                    <input type="text" name="title" required placeholder="e.g. Lab Report — Week 5" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description (optional)</label>
                    <textarea name="description" rows="3" placeholder="Instructions, requirements, etc." class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Max Marks</label>
                        <input type="number" name="max_marks" required min="1" max="1000" value="100" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Deadline</label>
                        <input type="datetime-local" name="deadline" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Reference File (optional)</label>
                    <input type="file" name="reference_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.png" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <p class="mt-1 text-xs text-slate-500">Attach question paper, rubric, or guidelines.</p>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('create-assignment-modal').classList.add('hidden')" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="edit-assignment-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex justify-center items-center">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full mx-4 border border-slate-200 dark:border-slate-700 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="pencil" class="w-5 h-5 text-amber-500"></i> Edit Assignment
                </h2>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-500">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="<?= $base ?>/controllers/process_assignment.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="assignment_id" id="edit-assignment-id">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Assignment Title</label>
                    <input type="text" name="title" id="edit-title" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Description (optional)</label>
                    <textarea name="description" id="edit-description" rows="3" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Max Marks</label>
                        <input type="number" name="max_marks" id="edit-max-marks" required min="1" max="1000" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Deadline (Extend Date & Time)</label>
                        <input type="datetime-local" name="deadline" id="edit-deadline" required class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Status</label>
                        <select name="status" id="edit-status" class="block w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-primary p-2.5">
                            <option value="ACTIVE">ACTIVE (Open for submissions)</option>
                            <option value="CLOSED">CLOSED (Locked)</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allow_late" id="edit-allow-late" value="1" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Allow Late Submissions</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Replace Reference File (optional)</label>
                    <input type="file" name="reference_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.png" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <p id="edit-current-file-text" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2.5 rounded-lg font-medium transition-colors shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function openEditModal(data) {
        document.getElementById('edit-assignment-id').value = data.id;
        document.getElementById('edit-title').value = data.title || '';
        document.getElementById('edit-description').value = data.description || '';
        document.getElementById('edit-max-marks').value = data.max_marks || 100;
        document.getElementById('edit-deadline').value = data.deadline || '';
        document.getElementById('edit-status').value = data.status || 'ACTIVE';
        document.getElementById('edit-allow-late').checked = (parseInt(data.allow_late) === 1);
        
        const fileText = document.getElementById('edit-current-file-text');
        if (data.reference_file) {
            fileText.textContent = 'Current file: ' + data.reference_file + ' (uploading a new file will replace it)';
        } else {
            fileText.textContent = 'No reference file currently attached.';
        }
        
        document.getElementById('edit-assignment-modal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeEditModal() {
        document.getElementById('edit-assignment-modal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
