<?php
// views/faculty/manage_marks.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('FACULTY');
$pageTitle = 'Internal Marks & Assessments | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/AttendanceController.php'; 
require_once __DIR__ . '/../../controllers/AssessmentController.php'; 

use Controllers\AttendanceController;
use Controllers\AssessmentController;

$facultyId = (int)($_SESSION['faculty_profile_id'] ?? $_SESSION['user_id']);
$attendanceCtrl = new AttendanceController();
$courses = $attendanceCtrl->getFacultyCourses($facultyId);

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses) > 0 ? (int)$courses[0]['id'] : 0);
$selectedAssessmentId = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;

$assessmentCtrl = new AssessmentController();
$assessments = [];
if ($selectedCourseId > 0) {
    $assessments = $assessmentCtrl->getAssessmentsByCourse($selectedCourseId);
}

// Check if an assessment is selected for grading
$activeAssessment = null;
$studentsWithMarks = [];
$analytics = [];
if ($selectedAssessmentId > 0) {
    $activeAssessment = $assessmentCtrl->getAssessmentById($selectedAssessmentId, $facultyId);
    if ($activeAssessment) {
        $studentsWithMarks = $assessmentCtrl->getStudentsWithMarks($selectedAssessmentId, $selectedCourseId);
        $analytics = $assessmentCtrl->getAssessmentAnalytics($selectedAssessmentId, $selectedCourseId);
    }
}

// Find current selected course details
$currentCourse = null;
foreach ($courses as $c) {
    if ((int)$c['id'] === $selectedCourseId) {
        $currentCourse = $c;
        break;
    }
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200 relative">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm animate-fade-in">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 text-emerald-500"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></p>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm animate-fade-in">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-500"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash_error']) ?></p>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if ($activeAssessment): ?>
        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODE B: INTERACTIVE GRADING LEDGER -->
        <!-- ══════════════════════════════════════════════════════════ -->

        <!-- Breadcrumb & Top Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                    <a href="manage_marks.php?course_id=<?= $selectedCourseId ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Assessments
                    </a>
                    <span>/</span>
                    <span class="text-slate-700 dark:text-slate-300"><?= htmlspecialchars($activeAssessment['course_code']) ?></span>
                    <span>/</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold"><?= htmlspecialchars($activeAssessment['title']) ?></span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <i data-lucide="award" class="w-6 h-6"></i>
                        </span>
                        <?= htmlspecialchars($activeAssessment['title']) ?>
                    </h1>
                    <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-full text-xs font-bold border border-indigo-200 dark:border-indigo-800">
                        Max: <?= (float)$activeAssessment['max_marks'] ?> Marks
                    </span>
                    <span class="px-3 py-1 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 rounded-full text-xs font-bold border border-amber-200 dark:border-amber-800">
                        Pass: <?= (float)($activeAssessment['pass_marks'] ?? 40) ?> Marks
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        Created <?= date('M d, Y', strtotime($activeAssessment['created_at'])) ?>
                    </span>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="submit" form="marks-ledger-form" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md shadow-indigo-500/20 hover:-translate-y-0.5 transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Marks
                </button>
                <a href="../../controllers/process_assessment.php?action=export_csv&id=<?= $activeAssessment['id'] ?>&course_id=<?= $selectedCourseId ?>" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    <i data-lucide="download" class="w-4 h-4 text-emerald-500"></i> Export CSV
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4 text-indigo-500"></i> Print
                </button>
                <a href="manage_marks.php?course_id=<?= $selectedCourseId ?>" class="inline-flex items-center gap-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-3.5 py-2 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    <i data-lucide="x" class="w-4 h-4"></i> Close
                </a>
            </div>
        </div>

        <!-- KPI Performance Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm backdrop-blur-sm">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Total Enrolled</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1"><?= $analytics['total_enrolled'] ?></p>
                <p class="text-xs text-slate-400 mt-1">Class Roster</p>
            </div>
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm backdrop-blur-sm">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Graded</p>
                <p class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1" id="stat-graded-count"><?= $analytics['graded_count'] ?></p>
                <p class="text-xs text-slate-400 mt-1"><?= $analytics['ungraded_count'] ?> pending</p>
            </div>
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm backdrop-blur-sm">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Class Average</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1" id="stat-avg-score"><?= $analytics['avg_score'] ?></p>
                <p class="text-xs text-slate-400 mt-1">out of <?= (float)$activeAssessment['max_marks'] ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm backdrop-blur-sm">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400">Top Score</p>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1"><?= $analytics['highest_score'] ?></p>
                <p class="text-xs text-slate-400 mt-1">High Mark</p>
            </div>
            <div class="col-span-2 sm:col-span-1 bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm backdrop-blur-sm">
                <?php $passColor = $analytics['pass_rate'] >= 75 ? 'emerald' : ($analytics['pass_rate'] >= 40 ? 'amber' : 'rose'); ?>
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-<?= $passColor ?>-500">Pass Rate (≥<?= (float)($activeAssessment['pass_marks'] ?? 40) ?>)</p>
                <p class="text-2xl sm:text-3xl font-black text-<?= $passColor ?>-600 dark:text-<?= $passColor ?>-400 mt-1"><?= $analytics['pass_rate'] ?>%</p>
                <p class="text-xs text-slate-400 mt-1"><?= $analytics['pass_count'] ?> Passed</p>
            </div>
        </div>

        <!-- Filter & Quick Assist Toolbar -->
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="student-search-input" placeholder="Search student by name or roll number..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400 mr-1 hidden sm:inline">Quick Fill:</span>
                <button type="button" onclick="quickFillPassing(<?= (float)($activeAssessment['pass_marks'] ?? round((float)$activeAssessment['max_marks'] * 0.4, 1)) ?>)" class="text-xs bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 px-3 py-2 rounded-lg font-medium transition-colors">
                    Pass Mark (<?= (float)($activeAssessment['pass_marks'] ?? round((float)$activeAssessment['max_marks'] * 0.4, 1)) ?>)
                </button>
                <button type="button" onclick="quickFillFull(<?= (float)$activeAssessment['max_marks'] ?>)" class="text-xs bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 px-3 py-2 rounded-lg font-medium transition-colors">
                    Full Marks (<?= (float)$activeAssessment['max_marks'] ?>)
                </button>
                <button type="button" onclick="quickClearAll()" class="text-xs bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 px-3 py-2 rounded-lg font-medium transition-colors">
                    Clear All
                </button>
            </div>
        </div>

        <!-- Student Marks Ledger Form -->
        <form id="marks-ledger-form" action="../../controllers/process_assessment.php" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_marks">
            <input type="hidden" name="assessment_id" value="<?= $activeAssessment['id'] ?>">
            <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/70 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="py-3.5 px-4 sm:px-6 w-16 text-center">#</th>
                                <th class="py-3.5 px-4 sm:px-6">Student Details</th>
                                <th class="py-3.5 px-4 sm:px-6 w-48 text-center">Score (Max: <?= (float)$activeAssessment['max_marks'] ?>)</th>
                                <th class="py-3.5 px-4 sm:px-6 w-32 text-center">Performance</th>
                                <th class="py-3.5 px-4 sm:px-6 min-w-[220px]">Remarks / Evaluation Feedback</th>
                                <th class="py-3.5 px-4 sm:px-6 w-24 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60" id="students-table-body">
                            <?php if (empty($studentsWithMarks)): ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                        <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                                        <p class="font-semibold text-slate-700 dark:text-slate-300">No active students enrolled in this course semester.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $idx = 1; foreach ($studentsWithMarks as $student): ?>
                                    <?php 
                                        $currentMark = $student['marks_obtained'] !== null ? (float)$student['marks_obtained'] : '';
                                        $maxM = (float)$activeAssessment['max_marks'];
                                        $passM = (float)($activeAssessment['pass_marks'] ?? round($maxM * 0.4));
                                        $pct = ($currentMark !== '' && $maxM > 0) ? round(($currentMark / $maxM) * 100, 1) : null;
                                        $isPass = ($currentMark !== '' && $currentMark >= $passM);
                                    ?>
                                    <tr class="student-row hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors" data-name="<?= htmlspecialchars(strtolower($student['name'])) ?>" data-roll="<?= htmlspecialchars(strtolower($student['roll_number'])) ?>">
                                        <!-- Index -->
                                        <td class="py-4 px-4 sm:px-6 text-center text-xs font-bold text-slate-400">
                                            <?= $idx++ ?>
                                        </td>

                                        <!-- Student Profile Info -->
                                        <td class="py-4 px-4 sm:px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-200/50 dark:border-indigo-800/50">
                                                    <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
                                                        <?= htmlspecialchars($student['name']) ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex flex-wrap items-center gap-1.5 font-mono">
                                                        <span>Roll: <?= htmlspecialchars($student['roll_number']) ?></span>
                                                        <span>&bull;</span>
                                                        <span>Reg: <?= htmlspecialchars($student['registration_number']) ?></span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Mark Input -->
                                        <td class="py-4 px-4 sm:px-6 text-center">
                                            <div class="relative max-w-[140px] mx-auto">
                                                <input 
                                                    type="number" 
                                                    name="marks[<?= $student['student_id'] ?>]" 
                                                    id="mark-input-<?= $student['student_id'] ?>"
                                                    value="<?= $currentMark !== '' ? $currentMark : '' ?>" 
                                                    step="0.5" 
                                                    min="0" 
                                                    max="<?= $maxM ?>" 
                                                    placeholder="—"
                                                    oninput="updateRowScore(this, <?= $maxM ?>, <?= $passM ?>)"
                                                    class="mark-input w-full text-center font-bold text-base py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                                >
                                            </div>
                                        </td>

                                        <!-- Performance / Result Badge -->
                                        <td class="py-4 px-4 sm:px-6 text-center">
                                            <div id="badge-container-<?= $student['student_id'] ?>">
                                                <?php if ($pct !== null): ?>
                                                    <?php 
                                                        $badgeClass = $pct >= 75 
                                                            ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' 
                                                            : ($isPass 
                                                                ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800' 
                                                                : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800');
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border <?= $badgeClass ?>">
                                                        <?= $pct ?>% (<?= $isPass ? 'PASS' : 'FAIL' ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-slate-400 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                                        Ungraded
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Remarks Input -->
                                        <td class="py-4 px-4 sm:px-6">
                                            <input 
                                                type="text" 
                                                name="remarks[<?= $student['student_id'] ?>]" 
                                                value="<?= htmlspecialchars($student['remarks'] ?? '') ?>" 
                                                placeholder="e.g. Good concept clarity, Needs revision..." 
                                                maxlength="255"
                                                class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-200 placeholder:text-slate-400 focus:bg-white dark:focus:bg-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none"
                                            >
                                        </td>

                                        <!-- Quick Row Actions -->
                                        <td class="py-4 px-4 sm:px-6 text-center">
                                            <button 
                                                type="button" 
                                                onclick="clearSingleStudent(<?= $student['student_id'] ?>)" 
                                                title="Clear score" 
                                                class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors"
                                            >
                                                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- In-Flow Table Card Action Footer -->
                <div class="px-4 sm:px-6 py-4 bg-slate-50/90 dark:bg-slate-900/60 border-t border-slate-200/80 dark:border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Assessment: <span class="text-indigo-600 dark:text-indigo-400 font-bold"><?= htmlspecialchars($activeAssessment['title']) ?></span>
                        </span>
                        <span class="text-slate-400 text-xs hidden sm:inline">&bull; Click "Save All Marks" to commit changes</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <a href="manage_marks.php?course_id=<?= $selectedCourseId ?>" class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold px-6 py-2.5 rounded-xl text-xs sm:text-sm shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all">
                            <i data-lucide="save" class="w-4 h-4"></i> Save All Marks
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php else: ?>
        <!-- ══════════════════════════════════════════════════════════ -->
        <!-- MODE A: COURSE ASSESSMENTS OVERVIEW LIST -->
        <!-- ══════════════════════════════════════════════════════════ -->

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                    <span class="p-2 bg-gradient-to-tr from-amber-500 to-orange-600 text-white rounded-xl shadow-md shadow-amber-500/20">
                        <i data-lucide="award" class="w-6 h-6"></i>
                    </span>
                    Internal Marks & Assessments
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Record, evaluate, and publish student scores for internal examinations, class tests, and coursework.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-indigo-500/20 hover:-translate-y-0.5">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Create Assessment
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white dark:bg-slate-800/90 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-5 backdrop-blur-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Select Assigned Subject</label>
                    <select name="course_id" onchange="this.form.submit()" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 p-3 text-sm outline-none transition-colors">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === (int)$course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?> (<?= htmlspecialchars($course['dept_code'] ?? 'Dept') ?> &bull; Sem <?= htmlspecialchars((string)($course['semester_number'] ?? '')) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <?php if ($currentCourse): ?>
                        <div class="p-3 bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/60 rounded-xl text-xs text-indigo-700 dark:text-indigo-300">
                            <span class="font-bold">Active Subject:</span> <?= htmlspecialchars($currentCourse['course_code']) ?> &bull; <?= count($assessments) ?> Assessment(s)
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Assessments List -->
        <?php if ($selectedCourseId > 0 && empty($assessments)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4 shadow-inner">
                    <i data-lucide="clipboard-list" class="w-10 h-10"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">No Assessments Created Yet</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">You have not created any assessments for this subject. Set up a Mid-Term, Class Test, or Lab Exam to record student marks.</p>
                <div class="mt-6">
                    <button type="button" onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-md">
                        <i data-lucide="plus" class="w-4 h-4"></i> Create First Assessment
                    </button>
                </div>
            </div>
        <?php elseif ($selectedCourseId > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($assessments as $assessment): ?>
                    <?php 
                        $graded = (int)($assessment['graded_count'] ?? 0);
                        $maxM = (float)$assessment['max_marks'];
                        $avg = $assessment['avg_marks'] !== null ? (float)$assessment['avg_marks'] : null;
                    ?>
                    <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-xl transition-all duration-300 p-5 flex flex-col justify-between group backdrop-blur-sm">
                        <div>
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0 group-hover:scale-105 transition-transform">
                                        <i data-lucide="file-text" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                            <?= htmlspecialchars($assessment['title']) ?>
                                        </h3>
                                        <p class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                            <?= date('M d, Y', strtotime($assessment['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-1">
                                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold rounded-lg text-xs border border-indigo-200/70 dark:border-indigo-800/70">
                                        Max: <?= $maxM ?>
                                    </span>
                                    <span class="px-2 py-0.5 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-semibold rounded-md text-[10px] border border-amber-200/70 dark:border-amber-800/70">
                                        Pass: <?= (float)($assessment['pass_marks'] ?? 40) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Metrics / Stats -->
                            <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3.5 my-4 border border-slate-100 dark:border-slate-700/50 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Grading Status:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">
                                        <?= $graded ?> Student(s) Graded
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">Class Average:</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">
                                        <?= $avg !== null ? "{$avg} / {$maxM}" : "Not evaluated yet" ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-700/70 flex items-center justify-between gap-2">
                            <a href="manage_marks.php?course_id=<?= $selectedCourseId ?>&assessment_id=<?= $assessment['id'] ?>" class="flex-1 inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Enter / Edit Marks
                            </a>

                            <a href="../../controllers/process_assessment.php?action=export_csv&id=<?= $assessment['id'] ?>&course_id=<?= $selectedCourseId ?>" title="Download CSV" class="p-2 text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl transition-colors">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </a>

                            <button type="button" onclick="openEditModal(<?= $assessment['id'] ?>, '<?= htmlspecialchars(addslashes($assessment['title'])) ?>', <?= $assessment['max_marks'] ?>, <?= $assessment['pass_marks'] ?? 40 ?>)" title="Edit Assessment Details" class="p-2 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-colors">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                            </button>

                            <button type="button" onclick="openDeleteModal(<?= $assessment['id'] ?>, '<?= htmlspecialchars(addslashes($assessment['title'])) ?>', <?= $graded ?>)" title="Delete Assessment" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- MODALS SECTION -->
    <!-- ══════════════════════════════════════════════════════════ -->

    <!-- 1. Add Assessment Modal -->
    <div id="add-assessment-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex justify-center items-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 dark:border-slate-700 overflow-hidden animate-scale-up">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-500"></i> Create Assessment
                </h2>
                <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../../controllers/process_assessment.php" method="POST" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Course</label>
                    <select name="course_id" required class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm">
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $selectedCourseId === (int)$course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Assessment Title</label>
                    <input type="text" name="title" required placeholder="e.g. Mid-Term Examination 2026" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Maximum Marks</label>
                        <input type="number" id="create-max-marks" name="max_marks" required min="1" max="1000" value="50" oninput="autoSuggestPassMarks(this.value, 'create-pass-marks')" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Pass Marks</label>
                        <input type="number" id="create-pass-marks" name="pass_marks" required min="1" max="1000" value="20" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeCreateModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl font-bold text-sm transition-all shadow-md">
                        Save Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Edit Assessment Modal -->
    <div id="edit-assessment-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex justify-center items-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 dark:border-slate-700 overflow-hidden animate-scale-up">
            <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="settings" class="w-5 h-5 text-indigo-500"></i> Edit Assessment
                </h2>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="../../controllers/process_assessment.php" method="POST" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="assessment_id" id="edit-assessment-id" value="">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Assessment Title</label>
                    <input type="text" name="title" id="edit-assessment-title" required class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Maximum Marks</label>
                        <input type="number" name="max_marks" id="edit-assessment-max" required min="1" max="1000" oninput="autoSuggestPassMarks(this.value, 'edit-assessment-pass')" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Pass Marks</label>
                        <input type="number" name="pass_marks" id="edit-assessment-pass" required min="1" max="1000" class="block w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 p-3 text-sm outline-none">
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl font-bold text-sm transition-all shadow-md">
                        Update Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Delete Confirmation Modal -->
    <div id="delete-assessment-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex justify-center items-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 dark:border-slate-700 overflow-hidden animate-scale-up">
            <div class="p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto">
                    <i data-lucide="alert-triangle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Assessment?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Are you sure you want to delete <strong class="text-slate-800 dark:text-slate-200" id="delete-assessment-name"></strong>?
                </p>
                <div id="delete-marks-warning" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 p-3 rounded-xl text-xs text-rose-700 dark:text-rose-400 text-left">
                    <i data-lucide="info" class="w-4 h-4 inline mr-1 text-rose-500"></i>
                    <strong>Caution:</strong> All associated student marks will also be permanently deleted.
                </div>
                <form action="../../controllers/process_assessment.php" method="POST" class="pt-2 flex gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="assessment_id" id="delete-assessment-id" value="">
                    <input type="hidden" name="course_id" value="<?= $selectedCourseId ?>">

                    <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white py-2.5 rounded-xl font-bold text-sm transition-all shadow-md">
                        Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    // ══════════════════════════════════════════════════════════
    // MODAL HANDLERS
    // ══════════════════════════════════════════════════════════
    function openCreateModal() {
        document.getElementById('add-assessment-modal').classList.remove('hidden');
    }
    function closeCreateModal() {
        document.getElementById('add-assessment-modal').classList.add('hidden');
    }

    function openEditModal(id, title, maxMarks, passMarks) {
        document.getElementById('edit-assessment-id').value = id;
        document.getElementById('edit-assessment-title').value = title;
        document.getElementById('edit-assessment-max').value = maxMarks;
        document.getElementById('edit-assessment-pass').value = passMarks || Math.round(maxMarks * 0.4);
        document.getElementById('edit-assessment-modal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('edit-assessment-modal').classList.add('hidden');
    }

    function autoSuggestPassMarks(maxVal, passInputId) {
        const max = parseFloat(maxVal);
        const passInput = document.getElementById(passInputId);
        if (!isNaN(max) && max > 0 && passInput && (!passInput.value || parseFloat(passInput.value) > max)) {
            passInput.value = Math.round(max * 0.4);
        }
    }

    function openDeleteModal(id, title, gradedCount) {
        document.getElementById('delete-assessment-id').value = id;
        document.getElementById('delete-assessment-name').textContent = title;
        const warning = document.getElementById('delete-marks-warning');
        if (gradedCount > 0) {
            warning.innerHTML = `<i data-lucide="info" class="w-4 h-4 inline mr-1 text-rose-500"></i><strong>Caution:</strong> This assessment already has <strong>${gradedCount}</strong> student score(s) recorded which will be permanently deleted.`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        document.getElementById('delete-assessment-modal').classList.remove('hidden');
    }
    function closeDeleteModal() {
        document.getElementById('delete-assessment-modal').classList.add('hidden');
    }

    // Close modals on Escape key or backdrop click
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreateModal();
            closeEditModal();
            closeDeleteModal();
        }
    });

    ['add-assessment-modal', 'edit-assessment-modal', 'delete-assessment-modal'].forEach(modalId => {
        const el = document.getElementById(modalId);
        if (el) {
            el.addEventListener('click', (e) => {
                if (e.target === el) el.classList.add('hidden');
            });
        }
    });

    // ══════════════════════════════════════════════════════════
    // INTERACTIVE GRADING LEDGER LOGIC
    // ══════════════════════════════════════════════════════════
    function updateRowScore(input, maxMarks, passMarks) {
        let val = parseFloat(input.value);
        const studentId = input.id.replace('mark-input-', '');
        const badgeContainer = document.getElementById('badge-container-' + studentId);

        if (isNaN(val) || input.value === '') {
            badgeContainer.innerHTML = '<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-slate-400 bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">Ungraded</span>';
            recalculateSummary(maxMarks);
            return;
        }

        if (val < 0) {
            val = 0;
            input.value = 0;
        }
        if (val > maxMarks) {
            val = maxMarks;
            input.value = maxMarks;
        }

        const pct = maxMarks > 0 ? ((val / maxMarks) * 100).toFixed(1) : 0;
        const isPass = (val >= (passMarks || maxMarks * 0.4));
        let badgeClass = 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800';
        if (pct >= 75) {
            badgeClass = 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800';
        } else if (isPass) {
            badgeClass = 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800';
        }

        const statusText = isPass ? 'PASS' : 'FAIL';
        badgeContainer.innerHTML = `<span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border ${badgeClass}">${pct}% (${statusText})</span>`;
        recalculateSummary(maxMarks);
    }

    function clearSingleStudent(studentId) {
        const input = document.getElementById('mark-input-' + studentId);
        if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input'));
        }
    }

    function quickFillPassing(passMark) {
        if (!confirm(`Fill passing marks (${passMark}) for all currently ungraded students?`)) return;
        document.querySelectorAll('.mark-input').forEach(input => {
            if (input.value === '') {
                input.value = passMark;
                input.dispatchEvent(new Event('input'));
            }
        });
    }

    function quickFillFull(fullMark) {
        if (!confirm(`Fill full marks (${fullMark}) for all currently ungraded students?`)) return;
        document.querySelectorAll('.mark-input').forEach(input => {
            if (input.value === '') {
                input.value = fullMark;
                input.dispatchEvent(new Event('input'));
            }
        });
    }

    function quickClearAll() {
        if (!confirm('Are you sure you want to clear all scores entered on this page?')) return;
        document.querySelectorAll('.mark-input').forEach(input => {
            input.value = '';
            input.dispatchEvent(new Event('input'));
        });
    }

    function recalculateSummary(maxMarks) {
        const inputs = document.querySelectorAll('.mark-input');
        let graded = 0;
        let total = 0;
        inputs.forEach(inp => {
            const val = parseFloat(inp.value);
            if (!isNaN(val) && inp.value !== '') {
                graded++;
                total += val;
            }
        });

        const statGraded = document.getElementById('stat-graded-count');
        const statAvg = document.getElementById('stat-avg-score');

        if (statGraded) statGraded.textContent = graded;
        if (statAvg) statAvg.textContent = graded > 0 ? (total / graded).toFixed(1) : '0';
    }

    // Live Student Search Filter
    const searchInput = document.getElementById('student-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.student-row').forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const roll = row.getAttribute('data-roll') || '';
                if (name.includes(q) || roll.includes(q)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Initialize Lucide Icons
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
