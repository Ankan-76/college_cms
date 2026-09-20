<?php
// views/admin/subjects.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Manage Subjects | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$departments = $db->query("SELECT id, dept_code FROM departments ORDER BY dept_code")->fetchAll();
$semesters = $db->query("SELECT id, semester_number FROM semesters ORDER BY semester_number")->fetchAll();

$search = $_GET['search'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$semester_id = $_GET['semester_id'] ?? '';

$query = "
    SELECT 
        c.id, c.course_code, c.course_name, c.credits,
        d.dept_code, s.semester_number
    FROM courses c
    JOIN departments d ON c.department_id = d.id
    JOIN semesters s ON c.semester_id = s.id
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (c.course_name LIKE ? OR c.course_code LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}
if ($department_id) {
    $query .= " AND c.department_id = ?";
    $params[] = $department_id;
}
if ($semester_id) {
    $query .= " AND c.semester_id = ?";
    $params[] = $semester_id;
}

$query .= " ORDER BY d.dept_code, s.semester_number, c.course_code";

$stmt = $db->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="book-open" class="w-6 h-6 text-indigo-500"></i> Subject Management
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage curriculum subjects and credit allocations.</p>
            </div>
            <a href="add-subject.php" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Subject
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-6 mb-6">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Search Subjects</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Subject Name or Code..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors outline-none">
                    </div>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Department</label>
                    <select name="department_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors appearance-none outline-none">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $department_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-48">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Semester</label>
                    <select name="semester_id" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 dark:text-white transition-colors appearance-none outline-none">
                        <option value="">All Semesters</option>
                        <?php foreach($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $semester_id == $s['id'] ? 'selected' : '' ?>>Semester <?= htmlspecialchars((string)$s['semester_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg text-sm font-semibold transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm outline-none">
                    Apply
                </button>
                
                <a href="subjects.php" class="w-full md:w-auto px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-lg text-sm transition-colors shadow-sm flex items-center justify-center outline-none" title="Reset Filters">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </a>
            </form>
        </div>

        <div class="mb-2">
            <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Found <?= count($courses) ?> Subjects</h2>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Semester</th>
                            <th class="px-6 py-4 text-center text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Credits</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach($courses as $course): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($course['course_name']) ?></div>
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($course['course_code']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800 px-2.5 py-1 rounded-md text-xs font-bold tracking-wider">
                                    <?= htmlspecialchars($course['dept_code']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-700 dark:text-slate-300">
                                Semester <?= htmlspecialchars((string)$course['semester_number']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-slate-700 dark:text-slate-300">
                                <?= htmlspecialchars((string)$course['credits']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="view-subject.php?id=<?= $course['id'] ?>" class="inline-block text-sky-500 hover:text-sky-700 hover:bg-sky-50 dark:hover:bg-sky-900/50 p-1.5 rounded transition-colors outline-none" title="View Details"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="edit-subject.php?id=<?= $course['id'] ?>" class="inline-block text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                    <form action="delete-subject.php" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this subject? Assignments and attendance related to this subject will also be deleted.');">
                                        <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/50 p-1.5 rounded transition-colors outline-none"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($courses)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                                    <i data-lucide="book-open" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No subjects configured.</p>
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
