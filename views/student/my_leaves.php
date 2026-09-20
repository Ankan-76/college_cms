<?php
// views/student/my_leaves.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('STUDENT');
$pageTitle = 'My Leaves | Student Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/LeaveController.php';

use Controllers\LeaveController;

$controller = new LeaveController();
$statusFilter = $_GET['status'] ?? null;
$leaves = $controller->getMyLeaves((int) $_SESSION['user_id'], 'STUDENT', $statusFilter);
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// Count by status for tabs
$allLeaves = $controller->getMyLeaves((int) $_SESSION['user_id'], 'STUDENT');
$counts = ['all' => count($allLeaves), 'pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($allLeaves as $l) {
    $counts[strtolower($l['status'])]++;
}
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-check" class="w-6 h-6 text-indigo-500"></i> My Leave Requests
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track and manage all your submitted leave applications.</p>
            </div>
            <a href="<?= $base ?>/views/student/apply_leave.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all hover:-translate-y-0.5">
                <i data-lucide="plus" class="w-4 h-4"></i> Apply for Leave
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="text-2xl font-black text-slate-700 dark:text-slate-200"><?= $counts['all'] ?></div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Total</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-amber-200 dark:border-amber-900/50 shadow-sm">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $counts['pending'] ?></div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Pending</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-emerald-200 dark:border-emerald-900/50 shadow-sm">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $counts['approved'] ?></div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Approved</div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-rose-200 dark:border-rose-900/50 shadow-sm">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400"><?= $counts['rejected'] ?></div>
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Rejected</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm w-fit">
            <?php
            $tabs = [
                ['label' => 'All', 'value' => '', 'count' => $counts['all']],
                ['label' => 'Pending', 'value' => 'PENDING', 'count' => $counts['pending']],
                ['label' => 'Approved', 'value' => 'APPROVED', 'count' => $counts['approved']],
                ['label' => 'Rejected', 'value' => 'REJECTED', 'count' => $counts['rejected']],
            ];
            foreach ($tabs as $tab):
                $isActive = ($statusFilter ?? '') === $tab['value'];
                $activeClass = $isActive 
                    ? 'bg-indigo-600 text-white shadow-md' 
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700';
            ?>
                <a href="<?= $base ?>/views/student/my_leaves.php<?= $tab['value'] ? '?status=' . $tab['value'] : '' ?>" 
                   class="px-4 py-2 rounded-lg text-xs font-bold transition-all <?= $activeClass ?>">
                    <?= $tab['label'] ?> <span class="opacity-70">(<?= $tab['count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Leave Requests List -->
        <?php if (empty($leaves)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-400 mb-4">
                    <i data-lucide="calendar-x" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No leave requests found</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">You haven't submitted any leave applications yet.</p>
                <a href="<?= $base ?>/views/student/apply_leave.php" class="inline-flex items-center gap-2 mt-4 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Apply Now
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($leaves as $leave): 
                    $statusColors = [
                        'PENDING' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800/50',
                        'APPROVED' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50',
                        'REJECTED' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border-rose-200 dark:border-rose-800/50',
                    ];
                    $typeColors = [
                        'SICK' => 'text-rose-600 dark:text-rose-400',
                        'CASUAL' => 'text-amber-600 dark:text-amber-400',
                        'OTHER' => 'text-indigo-600 dark:text-indigo-400',
                    ];
                    $typeIcons = ['SICK' => 'thermometer', 'CASUAL' => 'sun', 'OTHER' => 'file-text'];
                    $startDate = new DateTime($leave['start_date']);
                    $endDate = new DateTime($leave['end_date']);
                    $days = $startDate->diff($endDate)->days + 1;
                ?>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all duration-300 group">
                        <div class="p-5 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $statusColors[$leave['status']] ?>">
                                        <?= $leave['status'] ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold <?= $typeColors[$leave['leave_type']] ?>">
                                        <i data-lucide="<?= $typeIcons[$leave['leave_type']] ?>" class="w-4 h-4"></i>
                                        <?= $leave['leave_type'] === 'OTHER' && !empty($leave['custom_subject']) ? htmlspecialchars($leave['custom_subject']) : ucfirst(strtolower($leave['leave_type'])) . ' Leave' ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                        Applied: <?= date('M j, Y', strtotime($leave['created_at'])) ?>
                                    </span>
                                    <form action="<?= $base ?>/controllers/process_leave.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this leave request? This will undo the application and remove any attached files.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                        <button type="submit" class="text-rose-500 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300 p-1 rounded-md hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors" title="Delete Leave Request">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 mb-3">
                                <i data-lucide="calendar-days" class="w-4 h-4 text-indigo-500"></i>
                                <span class="font-semibold"><?= $startDate->format('M j, Y') ?></span>
                                <i data-lucide="arrow-right" class="w-3 h-3 text-slate-400"></i>
                                <span class="font-semibold"><?= $endDate->format('M j, Y') ?></span>
                                <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-full font-bold"><?= $days ?> day<?= $days > 1 ? 's' : '' ?></span>
                            </div>

                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= htmlspecialchars($leave['reason']) ?></p>

                            <?php 
                            $docs = !empty($leave['supporting_docs']) ? json_decode($leave['supporting_docs'], true) : [];
                            if (!empty($docs)): 
                            ?>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php foreach ($docs as $doc): ?>
                                        <a href="<?= $base ?>/uploads/leaves/<?= htmlspecialchars($doc['file']) ?>" target="_blank" 
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-xs font-medium text-slate-700 dark:text-slate-200 transition-colors border border-slate-200 dark:border-slate-600">
                                            <i data-lucide="paperclip" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span class="truncate max-w-[150px]"><?= htmlspecialchars($doc['name']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($leave['status'] !== 'PENDING' && !empty($leave['admin_remarks'])): ?>
                                <div class="mt-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 border border-slate-100 dark:border-slate-600">
                                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400 mb-1">
                                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i> Admin Remarks
                                        <?php if (!empty($leave['reviewer_name'])): ?>
                                            — <?= htmlspecialchars($leave['reviewer_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-slate-700 dark:text-slate-300"><?= htmlspecialchars($leave['admin_remarks']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
