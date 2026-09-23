<?php
// views/admin/subject_assignments.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('subject_assignments');
$pageTitle = 'Course Assignments | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$query = "
    SELECT 
        ca.id,
        c.course_code, c.course_name, c.credits,
        t.name AS faculty_name,
        d.dept_code, s.semester_number
    FROM course_assignments ca
    JOIN courses c ON ca.course_id = c.id
    JOIN teachers t ON ca.faculty_id = t.id
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    ORDER BY d.dept_code, s.semester_number, c.course_code
";
$assignments = $db->query($query)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-6 h-6 text-indigo-500"></i> Course Assignments
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage which faculty members teach which courses.</p>
            </div>
            <a href="assign-subject.php" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New Assignment
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Department/Sem</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Faculty Assigned</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($assignments as $assignment): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($assignment['course_name']) ?></div>
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($assignment['course_code']) ?> (<?= htmlspecialchars((string)$assignment['credits']) ?> Credits)</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 px-2.5 py-1 rounded-md text-xs font-bold tracking-wider inline-block mb-1">
                                    <?= htmlspecialchars($assignment['dept_code']) ?>
                                </span>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Semester <?= htmlspecialchars((string)$assignment['semester_number']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i data-lucide="user" class="w-4 h-4 text-slate-400 mr-2"></i>
                                    <span class="text-sm font-medium text-slate-900 dark:text-slate-200"><?= htmlspecialchars($assignment['faculty_name']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="view-assignment.php?id=<?= $assignment['id'] ?>" class="inline-block text-sky-500 hover:text-sky-700 hover:bg-sky-50 dark:hover:bg-sky-900/50 p-1.5 rounded transition-colors outline-none" title="View Details"><i data-lucide="eye" class="w-4 h-4"></i> View</a>
                                    <a href="edit-assignment.php?id=<?= $assignment['id'] ?>" class="inline-block text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/50 p-1.5 rounded transition-colors outline-none" title="Edit Assignment"><i data-lucide="edit" class="w-4 h-4"></i> Edit</a>
                                    <form action="delete-assignment.php" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Are you sure you want to revoke this assignment?');">
                                        <input type="hidden" name="id" value="<?= $assignment['id'] ?>">
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="trash-2" class="w-4 h-4"></i> Revoke</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($assignments)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                    <i data-lucide="clipboard-list" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No course assignments found.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
