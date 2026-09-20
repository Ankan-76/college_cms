<?php
// views/admin/leave_requests.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('ADMIN');
$pageTitle = 'Leave Requests | Admin Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/LeaveController.php';

use Controllers\LeaveController;

$controller = new LeaveController();
$statusFilter = $_GET['status'] ?? null;
$typeFilter = $_GET['type'] ?? null;
$leaves = $controller->getAllLeaves($statusFilter, $typeFilter);
$counts = $controller->getLeaveCounts();
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="calendar-off" class="w-6 h-6 text-rose-500"></i> Leave Requests
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Review and manage leave applications from students and faculty.</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-slate-700 dark:text-slate-200"><?= $counts['total'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Total Requests</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <i data-lucide="inbox" class="w-6 h-6 text-slate-500"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-amber-200 dark:border-amber-900/50 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400"><?= $counts['pending'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Pending Review</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6 text-amber-500"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-emerald-200 dark:border-emerald-900/50 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $counts['approved'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Approved</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 border border-rose-200 dark:border-rose-900/50 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-black text-rose-600 dark:text-rose-400"><?= $counts['rejected'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1">Rejected</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-6 h-6 text-rose-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <?php
                $statusTabs = [
                    ['label' => 'All', 'value' => ''],
                    ['label' => 'Pending', 'value' => 'PENDING'],
                    ['label' => 'Approved', 'value' => 'APPROVED'],
                    ['label' => 'Rejected', 'value' => 'REJECTED'],
                ];
                foreach ($statusTabs as $tab):
                    $isActive = ($statusFilter ?? '') === $tab['value'];
                    $activeClass = $isActive 
                        ? 'bg-indigo-600 text-white shadow-md' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700';
                    $params = [];
                    if ($tab['value']) $params['status'] = $tab['value'];
                    if ($typeFilter) $params['type'] = $typeFilter;
                    $href = $base . '/views/admin/leave_requests.php' . ($params ? '?' . http_build_query($params) : '');
                ?>
                    <a href="<?= $href ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $activeClass ?>">
                        <?= $tab['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Type Filter -->
            <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <?php
                $typeTabs = [
                    ['label' => 'All Roles', 'value' => ''],
                    ['label' => 'Students', 'value' => 'STUDENT'],
                    ['label' => 'Faculty', 'value' => 'FACULTY'],
                ];
                foreach ($typeTabs as $tab):
                    $isActive = ($typeFilter ?? '') === $tab['value'];
                    $activeClass = $isActive 
                        ? 'bg-purple-600 text-white shadow-md' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700';
                    $params = [];
                    if ($statusFilter) $params['status'] = $statusFilter;
                    if ($tab['value']) $params['type'] = $tab['value'];
                    $href = $base . '/views/admin/leave_requests.php' . ($params ? '?' . http_build_query($params) : '');
                ?>
                    <a href="<?= $href ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $activeClass ?>">
                        <?= $tab['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <?php if (empty($leaves)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-400 mb-4">
                    <i data-lucide="calendar-x" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No leave requests found</h3>
                <p class="text-sm text-slate-500 mt-2">There are no leave requests matching your current filters.</p>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                                <th class="text-left px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Applicant</th>
                                <th class="text-left px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                                <th class="text-left px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Period</th>
                                <th class="text-left px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reason</th>
                                <th class="text-left px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="text-center px-5 py-3 font-bold text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php foreach ($leaves as $leave): 
                                $statusColors = [
                                    'PENDING' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                    'APPROVED' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'REJECTED' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400',
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
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <!-- Applicant -->
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($leave['applicant_name'] ?? 'Unknown') ?></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $roleBadge ?>"><?= $leave['applicant_type'] ?></span>
                                            <?php if (!empty($leave['department_name'])): ?>
                                                <span class="text-xs text-slate-500"><?= htmlspecialchars($leave['department_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Type -->
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1 font-semibold <?= $typeColors[$leave['leave_type']] ?>">
                                            <i data-lucide="<?= $typeIcons[$leave['leave_type']] ?>" class="w-3.5 h-3.5"></i>
                                            <?= $leave['leave_type'] === 'OTHER' && !empty($leave['custom_subject']) ? htmlspecialchars($leave['custom_subject']) : ucfirst(strtolower($leave['leave_type'])) ?>
                                        </span>
                                    </td>
                                    <!-- Period -->
                                    <td class="px-5 py-4">
                                        <div class="text-slate-700 dark:text-slate-300 font-medium"><?= $startDate->format('M j') ?> — <?= $endDate->format('M j, Y') ?></div>
                                        <div class="text-xs text-slate-500 mt-0.5"><?= $days ?> day<?= $days > 1 ? 's' : '' ?></div>
                                    </td>
                                    <!-- Reason -->
                                    <td class="px-5 py-4 max-w-[200px]">
                                        <p class="text-slate-600 dark:text-slate-400 truncate" title="<?= htmlspecialchars($leave['reason']) ?>"><?= htmlspecialchars($leave['reason']) ?></p>
                                    </td>
                                    <!-- Status -->
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= $statusColors[$leave['status']] ?>">
                                            <?= $leave['status'] ?>
                                        </span>
                                        <?php if ($leave['status'] !== 'PENDING' && !empty($leave['reviewer_name'])): ?>
                                            <div class="text-[10px] text-slate-400 mt-1">by <?= htmlspecialchars($leave['reviewer_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <!-- Actions -->
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="<?= $base ?>/views/admin/view_leave.php?id=<?= $leave['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 dark:text-blue-400 rounded-lg transition-colors" title="View Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                            <?php if ($leave['status'] === 'PENDING'): ?>
                                                <button onclick="reviewLeave(<?= $leave['id'] ?>, 'APPROVED', '<?= htmlspecialchars($leave['applicant_name'] ?? 'User', ENT_QUOTES) ?>')" 
                                                    class="inline-flex items-center justify-center w-8 h-8 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 dark:text-emerald-400 rounded-lg transition-colors" title="Approve">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="reviewLeave(<?= $leave['id'] ?>, 'REJECTED', '<?= htmlspecialchars($leave['applicant_name'] ?? 'User', ENT_QUOTES) ?>')" 
                                                    class="inline-flex items-center justify-center w-8 h-8 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-400 rounded-lg transition-colors" title="Reject">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
const BASE_URL = '<?= $base ?>';

function reviewLeave(leaveId, status, applicantName) {
    const action = status === 'APPROVED' ? 'approve' : 'reject';
    const actionColor = status === 'APPROVED' ? '#10b981' : '#ef4444';
    
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Leave Request?`,
        html: `
            <p class="text-sm text-gray-600 mb-4">You are about to <strong>${action}</strong> the leave request from <strong>${applicantName}</strong>.</p>
            <div class="text-left">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks (optional):</label>
                <textarea id="swal-remarks" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Add any remarks or comments..."></textarea>
            </div>
        `,
        icon: status === 'APPROVED' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: `Yes, ${action}`,
        confirmButtonColor: actionColor,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
        preConfirm: () => {
            return document.getElementById('swal-remarks').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit via hidden form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = BASE_URL + '/controllers/process_leave.php';
            
            const fields = {
                'csrf_token': '<?= generate_csrf_token() ?>',
                'action': 'review',
                'leave_id': leaveId,
                'status': status,
                'remarks': result.value || ''
            };
            
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function viewDetails(leave) {
    const statusColors = {
        'PENDING': '#f59e0b',
        'APPROVED': '#10b981',
        'REJECTED': '#ef4444'
    };

    const startDate = new Date(leave.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const endDate = new Date(leave.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    
    let docsHtml = '';
    if (leave.supporting_docs) {
        try {
            const docs = JSON.parse(leave.supporting_docs);
            if (docs && docs.length > 0) {
                docsHtml = `
                    <div class="pt-2 border-t">
                        <span class="text-sm text-gray-500 block mb-2">Supporting Documents</span>
                        <div class="flex flex-col gap-2">
                            ${docs.map(doc => `
                                <a href="${BASE_URL}/uploads/leaves/${doc.file}" target="_blank" class="inline-flex items-center justify-between p-2 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition-colors text-sm text-indigo-600">
                                    <span class="truncate max-w-[300px]">${doc.name}</span>
                                    <span class="text-gray-400">View</span>
                                </a>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            console.error('Error parsing docs JSON:', e);
        }
    }

    Swal.fire({
        title: 'Leave Request Details',
        html: `
            <div class="text-left space-y-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-sm font-bold text-gray-700">Status</span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold text-white" style="background-color: ${statusColors[leave.status]}">${leave.status}</span>
                </div>
                <div class="flex justify-between"><span class="text-sm text-gray-500">Applicant</span><span class="text-sm font-semibold">${leave.applicant_name || 'Unknown'}</span></div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Type</span>
                    <span class="text-sm font-semibold">${leave.leave_type === 'OTHER' && leave.custom_subject ? leave.custom_subject : leave.leave_type}</span>
                </div>
                <div class="flex justify-between"><span class="text-sm text-gray-500">Period</span><span class="text-sm font-semibold">${startDate} — ${endDate}</span></div>
                <div class="pt-2 border-t"><span class="text-sm text-gray-500 block mb-1">Reason</span><p class="text-sm">${leave.reason}</p></div>
                ${docsHtml}
                ${leave.admin_remarks ? `<div class="pt-2 border-t"><span class="text-sm text-gray-500 block mb-1">Admin Remarks</span><p class="text-sm">${leave.admin_remarks}</p></div>` : ''}
                ${leave.reviewer_name ? `<div class="text-xs text-gray-400 pt-2">Reviewed by ${leave.reviewer_name}</div>` : ''}
            </div>
        `,
        confirmButtonText: 'Close',
        confirmButtonColor: '#4f46e5',
        width: 480,
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
