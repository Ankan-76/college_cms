<?php
// views/admin/view-feedback.php — Admin Feedback Management
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('feedbacks');
$pageTitle = 'Feedback Submissions | Admin Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/FeedbackController.php';

use Controllers\FeedbackController;

$controller = new FeedbackController();

// Filter parameters
$roleFilter = $_GET['role'] ?? 'ALL';
$statusFilter = $_GET['status'] ?? 'ALL';
$categoryFilter = $_GET['category'] ?? 'ALL';
$ratingFilter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : null;
$search = trim($_GET['search'] ?? '');

$feedbacks = $controller->getAllFeedbacks($roleFilter, $statusFilter, $categoryFilter, $search, $ratingFilter);
$stats = $controller->getFeedbackStats();
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="message-square-heart" class="w-6 h-6 text-indigo-500"></i> Feedback & Suggestions
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Review and act upon feedback submitted by students, faculty, and campus visitors.
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="<?= $base ?>/feedback.php" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all shadow-sm">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Open Feedback Form
                </a>
                <button onclick="exportTableToCSV()" class="inline-flex items-center gap-1.5 bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-semibold transition-all shadow-sm hover:-translate-y-0.5">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white"><?= $stats['total'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Total Received</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-700/60 flex items-center justify-center">
                        <i data-lucide="inbox" class="w-5 h-5 text-slate-600 dark:text-slate-300"></i>
                    </div>
                </div>
            </div>

            <!-- New / Pending -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-amber-200 dark:border-amber-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400"><?= $stats['new'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">New / Unread</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-5 h-5 text-amber-500"></i>
                    </div>
                </div>
            </div>

            <!-- Reviewed -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-indigo-200 dark:border-indigo-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400"><?= $stats['reviewed'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Reviewed</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                        <i data-lucide="eye" class="w-5 h-5 text-indigo-500"></i>
                    </div>
                </div>
            </div>

            <!-- Resolved -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-emerald-200 dark:border-emerald-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $stats['resolved'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Resolved</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                </div>
            </div>

            <!-- Average Rating -->
            <div class="col-span-2 lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl p-5 border border-purple-200 dark:border-purple-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                            <?= $stats['avg_rating'] ?>
                            <span class="text-xs text-slate-400 font-semibold">/ 5.0</span>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Avg. Rating</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center">
                        <i data-lucide="star" class="w-5 h-5 text-purple-500 fill-purple-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/80 p-5">
            <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                
                <!-- Search Input -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Search Keywords
                    </label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, subject..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        User Role
                    </label>
                    <select name="role" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                        <option value="ALL" <?= $roleFilter === 'ALL' ? 'selected' : '' ?>>All Roles</option>
                        <option value="STUDENT" <?= $roleFilter === 'STUDENT' ? 'selected' : '' ?>>Student (<?= $stats['student'] ?>)</option>
                        <option value="FACULTY" <?= $roleFilter === 'FACULTY' ? 'selected' : '' ?>>Faculty (<?= $stats['faculty'] ?>)</option>
                        <option value="GUEST" <?= $roleFilter === 'GUEST' ? 'selected' : '' ?>>Visitor / Guest (<?= $stats['guest'] ?>)</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                        <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>All Status</option>
                        <option value="NEW" <?= $statusFilter === 'NEW' ? 'selected' : '' ?>>New / Unread (<?= $stats['new'] ?>)</option>
                        <option value="REVIEWED" <?= $statusFilter === 'REVIEWED' ? 'selected' : '' ?>>Reviewed (<?= $stats['reviewed'] ?>)</option>
                        <option value="RESOLVED" <?= $statusFilter === 'RESOLVED' ? 'selected' : '' ?>>Resolved (<?= $stats['resolved'] ?>)</option>
                    </select>
                </div>

                <!-- Rating Filter -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Rating
                    </label>
                    <select name="rating" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                        <option value="" <?= $ratingFilter === null ? 'selected' : '' ?>>All Ratings</option>
                        <option value="5" <?= $ratingFilter === 5 ? 'selected' : '' ?>>5 Stars ★★★★★</option>
                        <option value="4" <?= $ratingFilter === 4 ? 'selected' : '' ?>>4 Stars ★★★★☆</option>
                        <option value="3" <?= $ratingFilter === 3 ? 'selected' : '' ?>>3 Stars ★★★☆☆</option>
                        <option value="2" <?= $ratingFilter === 2 ? 'selected' : '' ?>>2 Stars ★★☆☆☆</option>
                        <option value="1" <?= $ratingFilter === 1 ? 'selected' : '' ?>>1 Star ★☆☆☆☆</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
                    </button>
                    <a href="view-feedback.php" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 text-xs font-semibold transition-colors" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Feedbacks List Table Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-sm overflow-hidden">
            
            <?php if (empty($feedbacks)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-500 mb-4">
                        <i data-lucide="inbox" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">No Feedbacks Found</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">
                        There are no feedback submissions matching your current filter criteria.
                    </p>
                    <a href="view-feedback.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset All Filters
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300" id="feedbackTable">
                        <thead class="text-[11px] uppercase tracking-wider bg-slate-50/80 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 font-bold">Submitter</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Category & Rating</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Subject & Summary</th>
                                <th scope="col" class="px-5 py-3.5 font-bold text-center">Status</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Submitted At</th>
                                <th scope="col" class="px-5 py-3.5 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <?php foreach ($feedbacks as $fb): ?>
                                <?php
                                    // Submitter styling & role badge
                                    $roleBadge = '';
                                    if ($fb['user_role'] === 'STUDENT') {
                                        $roleBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">Student</span>';
                                    } elseif ($fb['user_role'] === 'FACULTY') {
                                        $roleBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">Faculty</span>';
                                    } else {
                                        $roleBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Visitor</span>';
                                    }

                                    // Status Badge
                                    $statusBadge = '';
                                    if ($fb['status'] === 'NEW') {
                                        $statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> NEW</span>';
                                    } elseif ($fb['status'] === 'REVIEWED') {
                                        $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">REVIEWED</span>';
                                    } else {
                                        $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">RESOLVED</span>';
                                    }

                                    $encodedFb = htmlspecialchars(json_encode($fb), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors group">
                                    
                                    <!-- Submitter Column -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 text-indigo-600 dark:text-indigo-400 font-black flex items-center justify-center flex-shrink-0 text-xs border border-indigo-200/50 dark:border-indigo-800/30">
                                                <?= strtoupper(substr($fb['name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                    <?= htmlspecialchars($fb['name'] ?? 'Anonymous') ?>
                                                    <?= $roleBadge ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                    <?= htmlspecialchars($fb['email'] ?? 'No email on record') ?>
                                                    <?php if (!empty($fb['phone'])): ?>
                                                        • <?= htmlspecialchars($fb['phone']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Category & Rating -->
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">
                                            <?= htmlspecialchars($fb['category']) ?>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <?php if ($fb['rating']): ?>
                                                <div class="flex items-center text-amber-400">
                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                        <i data-lucide="star" class="w-3 h-3 <?= $s <= $fb['rating'] ? 'fill-current' : 'text-slate-200 dark:text-slate-700' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="text-[10px] font-bold text-slate-400 ml-1"><?= $fb['rating'] ?>/5</span>
                                            <?php else: ?>
                                                <span class="text-[10px] text-slate-400 italic">No rating</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Subject & Message -->
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white max-w-xs truncate" title="<?= htmlspecialchars($fb['subject']) ?>">
                                            <?= htmlspecialchars($fb['subject']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 max-w-xs truncate mt-0.5">
                                            <?= htmlspecialchars($fb['message']) ?>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-4 whitespace-nowrap text-center">
                                        <?= $statusBadge ?>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-5 py-4 whitespace-nowrap text-[11px] text-slate-500 dark:text-slate-400">
                                        <div><?= date('M d, Y', strtotime($fb['created_at'])) ?></div>
                                        <div class="text-[10px] text-slate-400"><?= date('h:i A', strtotime($fb['created_at'])) ?></div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- View Modal -->
                                            <button onclick="openFeedbackModal(<?= $encodedFb ?>)"
                                                class="p-1.5 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors" title="View Full Details">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>

                                            <!-- Delete -->
                                            <button onclick="confirmDeleteFeedback(<?= $fb['id'] ?>)"
                                                class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors" title="Delete Feedback">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Hidden form for deletion -->
<form id="deleteFeedbackForm" action="<?= $base ?>/controllers/process_feedback.php" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteFeedbackId" value="">
</form>

<!-- Feedback Details Modal -->
<div id="feedbackModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-xl w-full border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden transform transition-all">
        
        <!-- Modal Top Bar -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i data-lucide="message-square-quote" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white" id="modalSubject">Feedback Details</h3>
                    <p class="text-xs text-slate-400" id="modalDate">Submitted on ...</p>
                </div>
            </div>
            <button onclick="closeFeedbackModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Content Body -->
        <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
            
            <!-- Submitter Info Box -->
            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-700/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block mb-0.5">Submitter</span>
                    <strong class="text-slate-900 dark:text-white text-sm" id="modalName">...</strong>
                    <span id="modalRoleBadge" class="ml-1.5"></span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Email</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" id="modalEmail">...</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Phone</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300" id="modalPhone">N/A</span>
                </div>
            </div>

            <!-- Category & Rating Row -->
            <div class="flex items-center justify-between gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block mb-0.5">Category</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200" id="modalCategory">...</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 block mb-0.5">Experience Rating</span>
                    <div id="modalRatingStars" class="flex items-center gap-1"></div>
                </div>
            </div>

            <!-- Message Full Text -->
            <div>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block mb-2">Message Body</span>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700/60 text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap" id="modalMessage">
                    ...
                </div>
            </div>

            <!-- Network Metadata -->
            <div class="text-[11px] text-slate-400 font-mono flex items-center justify-between border-t border-slate-200 dark:border-slate-700 pt-3">
                <span id="modalIp">IP: ...</span>
                <span id="modalUserAgent" class="truncate max-w-[260px]">UA: ...</span>
            </div>

            <!-- Status Update Form -->
            <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                <form action="<?= $base ?>/controllers/process_feedback.php" method="POST" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="modalFeedbackId" value="">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Update Status
                            </label>
                            <select name="status" id="modalStatusSelect"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                                <option value="NEW">NEW / PENDING</option>
                                <option value="REVIEWED">REVIEWED</option>
                                <option value="RESOLVED">RESOLVED</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Admin Internal Notes / Resolution Log
                        </label>
                        <textarea name="admin_notes" id="modalAdminNotes" rows="2"
                            class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs placeholder-slate-400 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Add internal notes on actions taken or status updates..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="closeFeedbackModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Close
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-indigo-500/20 transition-all">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Save Status & Notes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
// Modal Handlers
function openFeedbackModal(fb) {
    document.getElementById('modalFeedbackId').value = fb.id;
    document.getElementById('modalSubject').textContent = fb.subject;
    document.getElementById('modalDate').textContent = 'Submitted on ' + new Date(fb.created_at).toLocaleString();
    document.getElementById('modalName').textContent = fb.name || 'Anonymous';
    document.getElementById('modalEmail').textContent = fb.email || 'N/A';
    document.getElementById('modalPhone').textContent = fb.phone || 'N/A';
    document.getElementById('modalCategory').textContent = fb.category;
    document.getElementById('modalMessage').textContent = fb.message;
    document.getElementById('modalStatusSelect').value = fb.status;
    document.getElementById('modalAdminNotes').value = fb.admin_notes || '';
    document.getElementById('modalIp').textContent = 'IP: ' + (fb.ip_address || 'Unknown');
    document.getElementById('modalUserAgent').textContent = 'Browser: ' + (fb.user_agent || 'Unknown');

    // Role badge
    const roleBadgeContainer = document.getElementById('modalRoleBadge');
    if (fb.user_role === 'STUDENT') {
        roleBadgeContainer.innerHTML = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Student</span>';
    } else if (fb.user_role === 'FACULTY') {
        roleBadgeContainer.innerHTML = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Faculty</span>';
    } else {
        roleBadgeContainer.innerHTML = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">Visitor</span>';
    }

    // Stars
    const starsContainer = document.getElementById('modalRatingStars');
    starsContainer.innerHTML = '';
    const rating = parseInt(fb.rating) || 0;
    if (rating > 0) {
        for (let i = 1; i <= 5; i++) {
            const isFilled = i <= rating;
            starsContainer.innerHTML += `<i data-lucide="star" class="w-4 h-4 ${isFilled ? 'text-amber-400 fill-amber-400' : 'text-slate-300 dark:text-slate-600'}"></i>`;
        }
        starsContainer.innerHTML += `<span class="ml-1 font-bold text-slate-600 dark:text-slate-300">(${rating}/5)</span>`;
    } else {
        starsContainer.innerHTML = '<span class="text-slate-400 italic">No rating given</span>';
    }

    document.getElementById('feedbackModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
}

// Delete confirmation
function confirmDeleteFeedback(id) {
    Swal.fire({
        title: 'Delete this feedback?',
        text: 'This action cannot be undone and will permanently remove this feedback record.',
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

// Export to CSV
function exportTableToCSV() {
    const rows = <?= json_encode($feedbacks) ?>;
    if (!rows || rows.length === 0) {
        Swal.fire('Notice', 'No feedback records to export.', 'info');
        return;
    }

    const headers = ['ID', 'User Role', 'Name', 'Email', 'Phone', 'Category', 'Subject', 'Rating', 'Status', 'Message', 'Admin Notes', 'Date'];
    const csvRows = [headers.join(',')];

    rows.forEach(r => {
        const clean = (val) => `"${String(val || '').replace(/"/g, '""')}"`;
        csvRows.push([
            r.id,
            clean(r.user_role),
            clean(r.name),
            clean(r.email),
            clean(r.phone),
            clean(r.category),
            clean(r.subject),
            r.rating || '',
            clean(r.status),
            clean(r.message),
            clean(r.admin_notes),
            clean(r.created_at)
        ].join(','));
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `feedbacks_export_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Close modal on Escape key or backdrop click
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeFeedbackModal();
});
document.getElementById('feedbackModal').addEventListener('click', (e) => {
    if (e.target.id === 'feedbackModal') closeFeedbackModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
