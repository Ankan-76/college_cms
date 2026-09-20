<?php
// views/student/my_grades.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('STUDENT');
$pageTitle = 'My Grades | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$studentId = $_SESSION['user_id'];

// Fetch all assessment marks for this student, grouped by course
$gradesStmt = $db->prepare("
    SELECT am.marks_obtained, am.remarks, 
           a.id as assessment_id, a.title as assessment_title, a.max_marks, a.created_at,
           c.course_code, c.course_name, c.credits
    FROM assessment_marks am
    JOIN assessments a ON am.assessment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE am.student_id = ?
    ORDER BY c.course_code ASC, a.created_at DESC
");
$gradesStmt->execute([$studentId]);
$allGrades = $gradesStmt->fetchAll();

// Group by course
$groupedGrades = [];
$totalMarks = 0;
$totalMaxMarks = 0;
foreach ($allGrades as $grade) {
    $groupedGrades[$grade['course_code']]['course_name'] = $grade['course_name'];
    $groupedGrades[$grade['course_code']]['credits'] = $grade['credits'];
    $groupedGrades[$grade['course_code']]['items'][] = $grade;
    $totalMarks += (float) $grade['marks_obtained'];
    $totalMaxMarks += (int) $grade['max_marks'];
}

$overallPct = $totalMaxMarks > 0 ? round($totalMarks / $totalMaxMarks * 100, 1) : 0;

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="award" class="w-6 h-6 text-indigo-500"></i> My Grades
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View your assessment scores and academic performance.</p>
            </div>
        </div>

        <?php if (!empty($allGrades)): ?>
        <!-- Overall Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Assessments</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white"><?= count($allGrades) ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Total Marks</p>
                <p class="text-3xl font-black text-slate-900 dark:text-white"><?= $totalMarks ?> <span class="text-base text-slate-400">/ <?= $totalMaxMarks ?></span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 text-center">
                <?php $overallGradeColor = $overallPct >= 75 ? 'emerald' : ($overallPct >= 50 ? 'amber' : 'rose'); ?>
                <p class="text-xs font-bold text-<?= $overallGradeColor ?>-500 uppercase tracking-wider mb-2">Overall</p>
                <p class="text-3xl font-black text-<?= $overallGradeColor ?>-600 dark:text-<?= $overallGradeColor ?>-400"><?= $overallPct ?>%</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($allGrades)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-500 mb-4">
                    <i data-lucide="file-question" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No Grades Yet</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">Your assessment grades will appear here once they are published by faculty.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedGrades as $courseCode => $group): 
                $colors = ['indigo', 'emerald', 'amber', 'purple', 'rose', 'cyan'];
                $colorIdx = crc32($courseCode) % count($colors);
                $color = $colors[$colorIdx];
                
                // Course-level summary
                $courseTotal = 0;
                $courseMax = 0;
                foreach ($group['items'] as $g) {
                    $courseTotal += (float) $g['marks_obtained'];
                    $courseMax += (int) $g['max_marks'];
                }
                $coursePct = $courseMax > 0 ? round($courseTotal / $courseMax * 100, 1) : 0;
                $coursePctColor = $coursePct >= 75 ? 'emerald' : ($coursePct >= 50 ? 'amber' : 'rose');
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/50 bg-gradient-to-r from-<?= $color ?>-50/50 to-transparent dark:from-<?= $color ?>-900/10 dark:to-transparent flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-<?= $color ?>-100 text-<?= $color ?>-800 dark:bg-<?= $color ?>-900/40 dark:text-<?= $color ?>-300 border border-<?= $color ?>-200 dark:border-<?= $color ?>-800/50">
                            <?= htmlspecialchars($courseCode) ?>
                        </span>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($group['course_name']) ?></h3>
                    </div>
                    <span class="text-sm font-black text-<?= $coursePctColor ?>-600 dark:text-<?= $coursePctColor ?>-400"><?= $coursePct ?>%</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700/50">
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Assessment</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Marks</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Percentage</th>
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Remarks</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                            <?php foreach ($group['items'] as $grade): 
                                $pct = $grade['max_marks'] > 0 ? round(($grade['marks_obtained'] / $grade['max_marks']) * 100, 1) : 0;
                                $pctColor = $pct >= 75 ? 'emerald' : ($pct >= 50 ? 'amber' : 'rose');
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-5 py-3.5">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($grade['assessment_title']) ?></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white"><?= $grade['marks_obtained'] ?></span>
                                    <span class="text-xs text-slate-400"> / <?= $grade['max_marks'] ?></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-<?= $pctColor ?>-100 text-<?= $pctColor ?>-800 dark:bg-<?= $pctColor ?>-900/30 dark:text-<?= $pctColor ?>-400">
                                        <?= $pct ?>%
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($grade['remarks'] ?? '—') ?></span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="text-xs text-slate-500 font-medium"><?= date('M d, Y', strtotime($grade['created_at'])) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
