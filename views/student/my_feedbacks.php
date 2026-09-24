<?php
// views/student/my_feedbacks.php — Student Feedback Management
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('STUDENT');
$pageTitle = 'My Feedbacks | Student Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/FeedbackController.php';

use Controllers\FeedbackController;

$controller = new FeedbackController();
$studentId = (int)($_SESSION['user_id'] ?? 0);
$statusFilter = $_GET['status'] ?? null;
$search = trim($_GET['search'] ?? '');

$allFeedbacks = $controller->getUserFeedbacks($studentId, 'STUDENT');

// Count statuses for metrics and filter tabs
$counts = [
    'all' => count($allFeedbacks),
    'new' => 0,
    'reviewed' => 0,
    'resolved' => 0
];
$totalRating = 0;
$ratingCount = 0;

foreach ($allFeedbacks as $fb) {
    $st = strtolower($fb['status']);
    if (isset($counts[$st])) {
        $counts[$st]++;
    }
    if (!empty($fb['rating'])) {
        $totalRating += (int)$fb['rating'];
        $ratingCount++;
    }
}
$avgRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0.0;

// Filtered list
$feedbacks = $allFeedbacks;
if (!empty($statusFilter) && $statusFilter !== 'ALL') {
    $feedbacks = array_filter($feedbacks, fn($item) => $item['status'] === strtoupper($statusFilter));
}
if (!empty($search)) {
    $searchLower = strtolower($search);
    $feedbacks = array_filter($feedbacks, function($item) use ($searchLower) {
        return str_contains(strtolower($item['subject']), $searchLower)
            || str_contains(strtolower($item['message']), $searchLower)
            || str_contains(strtolower($item['category']), $searchLower);
    });
}

$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Top Header Banner -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm mb-3">
                        <i data-lucide="message-square-heart" class="w-3.5 h-3.5"></i> Student Voice & Feedback
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">My Feedback Submissions</h1>
                    <p class="text-indigo-100 text-sm mt-1 max-w-xl">
                        View responses from the college administration and manage your submitted institutional feedback.
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <a href="<?= $base ?>/feedback.php" class="inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-xl shadow-lg transition-all hover:-translate-y-0.5 text-xs sm:text-sm">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Submit New Feedback
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Sent</span>
                    <span class="text-2xl font-black text-slate-900 dark:text-white"><?= $counts['all'] ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-amber-500 uppercase tracking-wider block mb-1">Under Review</span>
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $counts['new'] ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider block mb-1">Reviewed</span>
                    <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400"><?= $counts['reviewed'] ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-500 uppercase tracking-wider block mb-1">Resolved</span>
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?= $counts['resolved'] ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white dark:bg-slate-800 p-2 sm:p-3 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-sm">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 md:pb-0">
                <?php
                $tabs = [
                    ['label' => 'All', 'value' => '', 'count' => $counts['all']],
                    ['label' => 'Under Review', 'value' => 'NEW', 'count' => $counts['new']],
                    ['label' => 'Reviewed', 'value' => 'REVIEWED', 'count' => $counts['reviewed']],
                    ['label' => 'Resolved', 'value' => 'RESOLVED', 'count' => $counts['resolved']],
                ];
                foreach ($tabs as $tab):
                    $isActive = ($statusFilter ?? '') === $tab['value'];
                    $activeClass = $isActive 
                        ? 'bg-indigo-600 text-white shadow-sm' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/60';
                ?>
                    <a href="?status=<?= urlencode($tab['value']) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap <?= $activeClass ?>">
                        <?= $tab['label'] ?> <span class="opacity-75">(<?= $tab['count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Field -->
            <form method="GET" action="" class="relative shrink-0">
                <?php if (!empty($statusFilter)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <?php endif; ?>
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search subject or keyword..."
                    class="w-full md:w-64 pl-9 pr-8 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 font-medium">
                <?php if (!empty($search)): ?>
                    <a href="?status=<?= urlencode($statusFilter ?? '') ?>" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Feedbacks List -->
        <?php if (empty($feedbacks)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-10 sm:p-12 text-center border border-slate-200 dark:border-slate-700/80 shadow-sm">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 mb-4">
                    <i data-lucide="message-square-dashed" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Feedbacks Found</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    <?= !empty($search) || !empty($statusFilter) ? 'No feedback submissions match your selected filter criteria.' : "You haven't submitted any feedback yet. Your input helps improve our campus!" ?>
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <?php if (!empty($search) || !empty($statusFilter)): ?>
                        <a href="my_feedbacks.php" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Reset Filters
                        </a>
                    <?php endif; ?>
                    <a href="<?= $base ?>/feedback.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/25 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i> Submit Feedback
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($feedbacks as $fb): 
                    // Status Badge Styling
                    $statusBadge = '';
                    if ($fb['status'] === 'NEW') {
                        $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Under Review</span>';
                    } elseif ($fb['status'] === 'REVIEWED') {
                        $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40"><i data-lucide="check" class="w-3.5 h-3.5"></i> Reviewed by Admin</span>';
                    } else {
                        $statusBadge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Resolved</span>';
                    }

                    $encodedFb = htmlspecialchars(json_encode($fb), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/80 p-5 sm:p-6 transition-all hover:shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-3">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?= $statusBadge ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300">
                                        <i data-lucide="tag" class="w-3 h-3 text-slate-400"></i> <?= htmlspecialchars($fb['category']) ?>
                                    </span>
                                </div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($fb['subject']) ?>
                                </h3>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                                <!-- Experience Stars -->
                                <?php if (!empty($fb['rating'])): ?>
                                    <div class="flex items-center gap-0.5 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 rounded-lg border border-amber-200/50 dark:border-amber-800/30 text-amber-500 text-xs font-bold">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i data-lucide="star" class="w-3.5 h-3.5 <?= $s <= $fb['rating'] ? 'fill-current' : 'text-slate-300 dark:text-slate-600' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ml-1 text-[11px] text-amber-700 dark:text-amber-300"><?= $fb['rating'] ?>/5</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Delete Action -->
                                <button type="button" onclick="confirmDeleteFeedback(<?= $fb['id'] ?>, '<?= htmlspecialchars(addslashes($fb['subject'])) ?>')" 
                                    class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors" 
                                    title="Delete Feedback Submission">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Feedback Message Body -->
                        <div class="text-sm text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800 whitespace-pre-wrap leading-relaxed">
                            <?= htmlspecialchars($fb['message']) ?>
                        </div>

                        <!-- Administrative Response (If present) -->
                        <?php if (!empty($fb['admin_notes'])): ?>
                            <div class="mt-4 p-4 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/50 space-y-1">
                                <div class="flex items-center gap-2 text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-indigo-500"></i> Response from Administration
                                </div>
                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 leading-relaxed pl-6">
                                    <?= htmlspecialchars($fb['admin_notes']) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Metadata Footer -->
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-400">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Submitted on <?= date('M d, Y • h:i A', strtotime($fb['created_at'])) ?>
                            </span>
                            <span class="font-medium">
                                Ref #<?= str_pad((string)$fb['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Hidden form for deleting feedback -->
<form id="deleteFeedbackForm" action="<?= $base ?>/controllers/process_feedback.php" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_my_feedback">
    <input type="hidden" name="id" id="deleteFeedbackId" value="">
</form>

<script>
function confirmDeleteFeedback(id, subject) {
    Swal.fire({
        title: 'Delete this feedback?',
        text: `Are you sure you want to delete "${subject}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteFeedbackId').value = id;
            document.getElementById('deleteFeedbackForm').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
