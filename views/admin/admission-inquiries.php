<?php
// views/admin/admission-inquiries.php — Admission Inquiries Management
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('admission_inquiries');
$pageTitle = 'Admission Inquiries | Admin Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/InquiryController.php';
require_once __DIR__ . '/../../config/database.php';

use Controllers\InquiryController;
use Config\Database;

$controller = new InquiryController();
$db = Database::getInstance()->getConnection();

// Fetch department list for filtering
$departments = $db->query("SELECT id, dept_name, dept_code FROM departments ORDER BY dept_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filters
$statusFilter = $_GET['status'] ?? 'ALL';
$deptFilter = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : null;
$search = trim($_GET['search'] ?? '');

$inquiries = $controller->getAllInquiries($statusFilter, $deptFilter, $search);
$stats = $controller->getInquiryStats();
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-6 h-6 text-emerald-500"></i> Admission Inquiries
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Manage prospective student inquiries, track counseling stages, and follow up with applicants.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="exportInquiriesCSV()" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-semibold transition-all shadow-sm hover:-translate-y-0.5 shadow-emerald-500/20">
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
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Total Inquiries</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-700/60 flex items-center justify-center">
                        <i data-lucide="inbox" class="w-5 h-5 text-slate-600 dark:text-slate-300"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Review -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-amber-200 dark:border-amber-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400"><?= $stats['pending'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Pending Review</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <i data-lucide="clock" class="w-5 h-5 text-amber-500"></i>
                    </div>
                </div>
            </div>

            <!-- Contacted / In Progress -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-indigo-200 dark:border-indigo-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400"><?= $stats['contacted'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Contacted</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                        <i data-lucide="phone-call" class="w-5 h-5 text-indigo-500"></i>
                    </div>
                </div>
            </div>

            <!-- Admitted -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-emerald-200 dark:border-emerald-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400"><?= $stats['admitted'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Admitted</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                </div>
            </div>

            <!-- Rejected / Dropped -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-rose-200 dark:border-rose-900/40 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400"><?= $stats['rejected'] ?></div>
                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Rejected / Dropped</div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center">
                        <i data-lucide="x-circle" class="w-5 h-5 text-rose-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700/80 shadow-sm">
            <form method="GET" action="admission-inquiries.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                
                <!-- Search -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Search Applicant
                    </label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                            placeholder="Name, email, phone, questions..." 
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all outline-none font-medium">
                    </div>
                </div>

                <!-- Department Filter -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Target Department
                    </label>
                    <select name="department_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $deptFilter === (int)$dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['dept_name']) ?> (<?= htmlspecialchars($dept['dept_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all font-medium">
                        <option value="ALL" <?= $statusFilter === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= strtolower($statusFilter) === 'pending' ? 'selected' : '' ?>>Pending (<?= $stats['pending'] ?>)</option>
                        <option value="contacted" <?= strtolower($statusFilter) === 'contacted' ? 'selected' : '' ?>>Contacted (<?= $stats['contacted'] ?>)</option>
                        <option value="admitted" <?= strtolower($statusFilter) === 'admitted' ? 'selected' : '' ?>>Admitted (<?= $stats['admitted'] ?>)</option>
                        <option value="rejected" <?= strtolower($statusFilter) === 'rejected' ? 'selected' : '' ?>>Rejected (<?= $stats['rejected'] ?>)</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
                    </button>
                    <a href="admission-inquiries.php" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 text-xs font-semibold transition-colors" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Inquiries List Table Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/80 shadow-sm overflow-hidden">
            <?php if (empty($inquiries)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-500 mb-4">
                        <i data-lucide="user-plus" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white mb-1">No Inquiries Found</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-4">
                        There are no admission inquiries matching your selected filters or search keywords.
                    </p>
                    <a href="admission-inquiries.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset All Filters
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300" id="inquiryTable">
                        <thead class="text-[11px] uppercase tracking-wider bg-slate-50/80 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 font-bold">Applicant Details</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Target Department</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Prior Qualification</th>
                                <th scope="col" class="px-5 py-3.5 font-bold text-center">Status</th>
                                <th scope="col" class="px-5 py-3.5 font-bold">Submitted Date</th>
                                <th scope="col" class="px-5 py-3.5 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <?php foreach ($inquiries as $inq): ?>
                                <?php
                                    $statusLower = strtolower($inq['status'] ?? 'pending');
                                    $statusBadge = '';
                                    if ($statusLower === 'pending') {
                                        $statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> PENDING</span>';
                                    } elseif ($statusLower === 'contacted') {
                                        $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">CONTACTED</span>';
                                    } elseif ($statusLower === 'admitted') {
                                        $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">ADMITTED</span>';
                                    } else {
                                        $statusBadge = '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40">REJECTED</span>';
                                    }

                                    $cleanDigitsPhone = preg_replace('/\D/', '', $inq['phone'] ?? '');
                                    $encodedInq = htmlspecialchars(json_encode($inq), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors group">
                                    <!-- Applicant Info -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-black text-sm shrink-0 border border-emerald-200 dark:border-emerald-800/50">
                                                <?= strtoupper(substr($inq['full_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-1.5">
                                                    <?= htmlspecialchars($inq['full_name']) ?>
                                                </div>
                                                <div class="flex items-center gap-3 text-[11px] text-slate-400 mt-0.5">
                                                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-1" title="Send Email">
                                                        <i data-lucide="mail" class="w-3 h-3"></i> <?= htmlspecialchars($inq['email']) ?>
                                                    </a>
                                                    <span>•</span>
                                                    <a href="tel:<?= htmlspecialchars($inq['phone']) ?>" class="hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-1" title="Call Phone">
                                                        <i data-lucide="phone" class="w-3 h-3"></i> <?= htmlspecialchars($inq['phone']) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Department -->
                                    <td class="px-5 py-4">
                                        <?php if (!empty($inq['dept_name'])): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300">
                                                <i data-lucide="building" class="w-3 h-3 text-slate-400"></i>
                                                <?= htmlspecialchars($inq['dept_name']) ?> (<?= htmlspecialchars($inq['dept_code']) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic">Not specified</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Prior Qualification -->
                                    <td class="px-5 py-4">
                                        <?php if (!empty($inq['previous_qualification'])): ?>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($inq['previous_qualification']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-5 py-4 text-center">
                                        <?= $statusBadge ?>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-5 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-[11px]">
                                        <div class="font-medium text-slate-700 dark:text-slate-300">
                                            <?= date('M d, Y', strtotime($inq['created_at'])) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            <?= date('h:i A', strtotime($inq['created_at'])) ?>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Review / Notes Button -->
                                            <button onclick='openInquiryModal(<?= $encodedInq ?>)' 
                                                class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-colors inline-flex items-center gap-1 border border-emerald-200 dark:border-emerald-800/40"
                                                title="View details & update counselor status">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Review
                                            </button>

                                            <!-- Direct Contact Popover / Quick Links -->
                                            <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" 
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-700 transition-colors"
                                                title="Send Email">
                                                <i data-lucide="mail" class="w-4 h-4"></i>
                                            </a>

                                            <?php if (!empty($cleanDigitsPhone)): ?>
                                                <a href="https://wa.me/<?= $cleanDigitsPhone ?>" target="_blank"
                                                    class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-700 transition-colors"
                                                    title="Message via WhatsApp">
                                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Delete Button -->
                                            <button onclick="confirmDeleteInquiry(<?= (int)$inq['id'] ?>)" 
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-700 transition-colors" 
                                                title="Delete Inquiry">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Bar -->
                <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 dark:text-slate-400 gap-2">
                    <div>
                        Showing <span class="font-bold text-slate-700 dark:text-slate-300"><?= count($inquiries) ?></span> inquiries
                        <?php if ($statusFilter !== 'ALL' || !empty($search) || $deptFilter): ?>
                            (filtered from total <?= $stats['total'] ?>)
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending: <?= $stats['pending'] ?>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Contacted: <?= $stats['contacted'] ?>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Admitted: <?= $stats['admitted'] ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- ═══════════ INQUIRY DETAIL & REVIEW MODAL ═══════════ -->
<div id="inquiryModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden transition-all my-8">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span id="modalStatusBadge"></span>
                    <span class="text-xs text-slate-400" id="modalSubmittedDate"></span>
                </div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white" id="modalApplicantName">Applicant Details</h3>
            </div>
            <button type="button" onclick="closeInquiryModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 space-y-6">
            
            <!-- Candidate Contact & Academic Matrix -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 text-xs">
                <div>
                    <span class="text-slate-400 font-semibold uppercase tracking-wider block mb-1">Email Address</span>
                    <a id="modalEmailLink" href="" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1.5">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                        <span id="modalEmail"></span>
                    </a>
                </div>
                <div>
                    <span class="text-slate-400 font-semibold uppercase tracking-wider block mb-1">Phone Number</span>
                    <a id="modalPhoneLink" href="" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1.5">
                        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                        <span id="modalPhone"></span>
                    </a>
                </div>
                <div>
                    <span class="text-slate-400 font-semibold uppercase tracking-wider block mb-1">Target Department</span>
                    <span id="modalDepartment" class="font-bold text-slate-800 dark:text-white"></span>
                </div>
                <div>
                    <span class="text-slate-400 font-semibold uppercase tracking-wider block mb-1">Prior Qualification</span>
                    <span id="modalQualification" class="font-bold text-slate-800 dark:text-white"></span>
                </div>
            </div>

            <!-- Inquiry Message -->
            <div>
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider block mb-2">
                    Questions / Message Submitted:
                </span>
                <div class="p-4 rounded-2xl bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed min-h-[80px]" id="modalMessage">
                </div>
            </div>

            <!-- Fast Contact Actions Bar -->
            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <span class="text-xs text-slate-400 font-semibold mr-1">Direct Contact:</span>
                <a id="actionEmailBtn" href="" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> Compose Email
                </a>
                <a id="actionCallBtn" href="" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 transition-colors">
                    <i data-lucide="phone-call" class="w-3.5 h-3.5"></i> Direct Call
                </a>
                <a id="actionWhatsAppBtn" href="" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-green-50 dark:bg-green-950/50 text-green-600 dark:text-green-400 hover:bg-green-100 transition-colors">
                    <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> WhatsApp Chat
                </a>
            </div>

            <!-- Form: Update Status and Notes -->
            <form action="<?= $base ?>/controllers/process_inquiry.php" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="modalInquiryId" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Inquiry Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" id="modalStatusSelect" required class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                            <option value="pending">Pending Review</option>
                            <option value="contacted">Contacted / In Progress</option>
                            <option value="admitted">Admitted (Seat Confirmed)</option>
                            <option value="rejected">Rejected / Dropped</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <span class="text-xs text-slate-400">
                            Updating status helps the admissions team keep track of seat distribution and contact history.
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                        Counselor Notes / Follow-up History (Internal)
                    </label>
                    <textarea name="admin_notes" id="modalAdminNotes" rows="3" 
                        placeholder="Log counseling notes, discussion details, entrance exam rank, eligibility verification..." 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeInquiryModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/25 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- ═══════════ HIDDEN FORM FOR DELETION ═══════════ -->
<form id="deleteInquiryForm" action="<?= $base ?>/controllers/process_inquiry.php" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteInquiryId" value="">
</form>

<script>
// Modal Handlers
function openInquiryModal(inq) {
    document.getElementById('modalInquiryId').value = inq.id;
    document.getElementById('modalApplicantName').textContent = inq.full_name;
    document.getElementById('modalSubmittedDate').textContent = 'Submitted on ' + new Date(inq.created_at).toLocaleString();
    
    document.getElementById('modalEmail').textContent = inq.email;
    document.getElementById('modalEmailLink').href = 'mailto:' + inq.email;
    document.getElementById('actionEmailBtn').href = 'mailto:' + inq.email;

    document.getElementById('modalPhone').textContent = inq.phone;
    document.getElementById('modalPhoneLink').href = 'tel:' + inq.phone;
    document.getElementById('actionCallBtn').href = 'tel:' + inq.phone;

    const cleanDigits = (inq.phone || '').replace(/\D/g, '');
    document.getElementById('actionWhatsAppBtn').href = 'https://wa.me/' + cleanDigits;

    const deptText = inq.dept_name ? (inq.dept_name + ' (' + inq.dept_code + ')') : 'Not specified';
    document.getElementById('modalDepartment').textContent = deptText;
    document.getElementById('modalQualification').textContent = inq.previous_qualification || 'Not provided';
    document.getElementById('modalMessage').textContent = inq.message || 'No additional questions or comments provided.';

    document.getElementById('modalStatusSelect').value = (inq.status || 'pending').toLowerCase();
    document.getElementById('modalAdminNotes').value = inq.admin_notes || '';

    // Status badge
    const badgeEl = document.getElementById('modalStatusBadge');
    const status = (inq.status || 'pending').toLowerCase();
    if (status === 'pending') {
        badgeEl.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">PENDING REVIEW</span>';
    } else if (status === 'contacted') {
        badgeEl.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">CONTACTED</span>';
    } else if (status === 'admitted') {
        badgeEl.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">ADMITTED</span>';
    } else {
        badgeEl.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400">REJECTED</span>';
    }

    document.getElementById('inquiryModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeInquiryModal() {
    document.getElementById('inquiryModal').classList.add('hidden');
}

// Delete confirmation with SweetAlert2
function confirmDeleteInquiry(id) {
    Swal.fire({
        title: 'Delete this admission inquiry?',
        text: 'This action cannot be undone and will permanently remove this inquiry record.',
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
            document.getElementById('deleteInquiryId').value = id;
            document.getElementById('deleteInquiryForm').submit();
        }
    });
}

// Export to CSV
function exportInquiriesCSV() {
    const rows = <?= json_encode($inquiries) ?>;
    if (!rows || rows.length === 0) {
        Swal.fire('Notice', 'No inquiry records to export.', 'info');
        return;
    }

    const headers = ['ID', 'Applicant Name', 'Email', 'Phone', 'Department', 'Department Code', 'Prior Qualification', 'Status', 'Inquiry Message', 'Counselor Notes', 'Submitted Date'];
    const csvRows = [headers.join(',')];

    rows.forEach(r => {
        const clean = (val) => `"${String(val || '').replace(/"/g, '""')}"`;
        csvRows.push([
            r.id,
            clean(r.full_name),
            clean(r.email),
            clean(r.phone),
            clean(r.dept_name || ''),
            clean(r.dept_code || ''),
            clean(r.previous_qualification || ''),
            clean(r.status || 'pending'),
            clean(r.message || ''),
            clean(r.admin_notes || ''),
            clean(r.created_at)
        ].join(','));
    });

    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `admission_inquiries_export_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

// Keyboard and Backdrop handlers
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeInquiryModal();
});
document.getElementById('inquiryModal').addEventListener('click', (e) => {
    if (e.target.id === 'inquiryModal') closeInquiryModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
