<?php
// views/admin/view_leave.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../controllers/LeaveController.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('leave_requests');
$pageTitle = 'Leave Request Details | Admin Portal';

use Controllers\LeaveController;

$leaveId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$leaveId) {
    set_flash_message('Invalid leave request ID.', 'error');
    redirect('/views/admin/leave_requests.php');
}

$controller = new LeaveController();
$leave = $controller->getLeaveById($leaveId);

if (!$leave) {
    set_flash_message('Leave request not found.', 'error');
    redirect('/views/admin/leave_requests.php');
}

require_once __DIR__ . '/../../includes/header.php';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$statusColors = [
    'PENDING' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50',
    'APPROVED' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50',
    'REJECTED' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50',
];

$typeColors = [
    'SICK' => 'text-rose-600 dark:text-rose-400',
    'CASUAL' => 'text-amber-600 dark:text-amber-400',
    'OTHER' => 'text-indigo-600 dark:text-indigo-400',
];
$typeIcons = ['SICK' => 'thermometer', 'CASUAL' => 'sun', 'OTHER' => 'file-text'];
$roleBadge = $leave['applicant_type'] === 'STUDENT' 
    ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' 
    : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';

$startDate = new DateTime($leave['start_date']);
$endDate = new DateTime($leave['end_date']);
$days = $startDate->diff($endDate)->days + 1;
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-5xl mx-auto space-y-8">
        
        <!-- Header Section -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-4">
                <a href="<?= $base ?>/views/admin/leave_requests.php" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 shadow-sm border border-slate-200 dark:border-slate-700 transition-all hover:shadow-md hover:-translate-x-0.5">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                        Leave Request Details
                    </h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">
                        Application Ref: <span class="text-slate-700 dark:text-slate-300 font-bold">#<?= str_pad($leave['id'], 5, '0', STR_PAD_LEFT) ?></span> • Submitted on <?= date('F j, Y', strtotime($leave['created_at'])) ?>
                    </p>
                </div>
            </div>
            <div class="hidden sm:block">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold <?= $statusColors[$leave['status']] ?> shadow-sm">
                    <?php if ($leave['status'] === 'PENDING'): ?>
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    <?php elseif ($leave['status'] === 'APPROVED'): ?>
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    <?php else: ?>
                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                    <?php endif; ?>
                    <?= $leave['status'] ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <!-- Left Column: Details -->
            <div class="xl:col-span-2 space-y-6">
                
                <!-- Main Info Card -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden relative">
                    <div class="absolute top-0 left-0 w-2 h-full <?= strpos($typeColors[$leave['leave_type']], 'text-rose') !== false ? 'bg-rose-500' : (strpos($typeColors[$leave['leave_type']], 'text-amber') !== false ? 'bg-amber-500' : 'bg-indigo-500') ?>"></div>
                    
                    <div class="p-8 sm:p-10 pl-10 sm:pl-12">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-10">
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 block">Leave Type</label>
                                <span class="inline-flex items-center gap-2 text-2xl font-black <?= $typeColors[$leave['leave_type']] ?>">
                                    <i data-lucide="<?= $typeIcons[$leave['leave_type']] ?>" class="w-7 h-7"></i>
                                    <?= $leave['leave_type'] === 'OTHER' && !empty($leave['custom_subject']) ? htmlspecialchars($leave['custom_subject']) : ucfirst(strtolower($leave['leave_type'])) . ' Leave' ?>
                                </span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-100 dark:border-slate-700 flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">From</div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200"><?= $startDate->format('M j, Y') ?></div>
                                </div>
                                <div class="flex flex-col items-center justify-center text-slate-300 dark:text-slate-600">
                                    <i data-lucide="arrow-right" class="w-4 h-4 mb-1"></i>
                                    <span class="text-[10px] font-bold text-slate-400 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full border border-slate-100 dark:border-slate-700 shadow-sm"><?= $days ?>d</span>
                                </div>
                                <div class="text-center">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">To</div>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200"><?= $endDate->format('M j, Y') ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="relative">
                            <i data-lucide="quote" class="absolute -top-3 -left-3 w-10 h-10 text-slate-100 dark:text-slate-700/50 transform -scale-x-100"></i>
                            <div class="relative z-10">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Reason for Leave</h3>
                                <p class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed text-base font-medium"><?= htmlspecialchars($leave['reason']) ?></p>
                            </div>
                        </div>

                        <!-- Supporting Documents -->
                        <?php 
                        $docs = !empty($leave['supporting_docs']) ? json_decode($leave['supporting_docs'], true) : [];
                        if (!empty($docs)): 
                        ?>
                            <div class="mt-10 pt-8 border-t border-slate-100 dark:border-slate-700/50">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Supporting Documents</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($docs as $doc): ?>
                                        <a href="<?= $base ?>/uploads/leaves/<?= htmlspecialchars($doc['file']) ?>" target="_blank" 
                                           class="group relative overflow-hidden flex items-center gap-4 p-4 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-lg transition-all duration-300">
                                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            <div class="relative z-10 w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                                                <i data-lucide="file-text" class="w-6 h-6"></i>
                                            </div>
                                            <div class="relative z-10 min-w-0 flex-1">
                                                <div class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">
                                                    <?= htmlspecialchars($doc['name']) ?>
                                                </div>
                                                <div class="text-[11px] text-slate-400 font-semibold mt-1 uppercase tracking-wider flex items-center gap-1">
                                                    <span>View Document</span> <i data-lucide="external-link" class="w-3 h-3"></i>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Right Column: Sidebar -->
            <div class="space-y-6">
                
                <!-- Applicant Profile -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="h-20 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800"></div>
                    <div class="p-6 px-8 text-center -mt-12 relative">
                        <div class="w-24 h-24 mx-auto rounded-2xl bg-white dark:bg-slate-800 p-2 shadow-lg mb-4">
                            <div class="w-full h-full rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-500">
                                <i data-lucide="user" class="w-10 h-10"></i>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($leave['applicant_name'] ?? 'Unknown') ?></h2>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold mt-2 <?= $roleBadge ?> shadow-sm">
                            <?= $leave['applicant_type'] ?>
                        </span>
                        
                        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-700/50 space-y-4 text-left">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Address</span>
                                <a href="mailto:<?= htmlspecialchars($leave['applicant_email'] ?? '') ?>" class="text-sm font-semibold text-slate-700 dark:text-slate-300 hover:text-indigo-600 transition-colors flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i> <?= htmlspecialchars($leave['applicant_email'] ?? 'Not provided') ?>
                                </a>
                            </div>
                            <?php if (!empty($leave['department_name'])): ?>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Department</span>
                                <div class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                    <i data-lucide="building" class="w-4 h-4 text-slate-400"></i> <?= htmlspecialchars($leave['department_name']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Admin Action Card -->
                <?php if ($leave['status'] === 'PENDING'): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-lg shadow-indigo-500/5 border border-indigo-100 dark:border-indigo-500/20 overflow-hidden">
                        <div class="p-6 sm:p-8 bg-gradient-to-b from-indigo-50/50 to-white dark:from-slate-800/80 dark:to-slate-800">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-5 flex items-center gap-2">
                                <i data-lucide="gavel" class="w-5 h-5 text-indigo-500"></i> Official Review
                            </h3>
                            <form action="<?= $base ?>/controllers/process_leave.php" method="POST" class="space-y-5">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Remarks (Optional)</label>
                                    <textarea name="remarks" rows="3" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-200 dark:border-slate-600 bg-transparent text-slate-900 dark:text-slate-100 focus:ring-0 focus:border-indigo-500 transition-colors text-sm resize-none placeholder:text-slate-400" placeholder="Add official comments..."></textarea>
                                </div>
                                
                                <div class="flex flex-col gap-3 pt-2">
                                    <button type="submit" name="status" value="APPROVED" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:-translate-y-0.5 transition-all">
                                        <i data-lucide="check-circle" class="w-5 h-5"></i> Approve Request
                                    </button>
                                    <button type="submit" name="status" value="REJECTED" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-slate-600 border-2 border-rose-100 dark:border-rose-900/50 rounded-2xl text-sm font-bold transition-all">
                                        <i data-lucide="x-circle" class="w-5 h-5"></i> Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 dark:bg-slate-800/80 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-700">
                        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-6 flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-indigo-500"></i> Resolution Details
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700/50">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Reviewed By</span>
                                <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($leave['reviewer_name'] ?? 'Unknown Admin') ?></span>
                            </div>
                            <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700/50">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Date</span>
                                <span class="font-bold text-slate-900 dark:text-white"><?= date('M j, Y', strtotime($leave['updated_at'])) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($leave['admin_remarks'])): ?>
                            <div class="mt-6 bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Official Remarks</span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 font-medium">"<?= htmlspecialchars($leave['admin_remarks']) ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
