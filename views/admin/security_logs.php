<?php
// views/admin/security_logs.php
$pageTitle = 'Network Surveillance Matrix';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_role('ADMIN');
require_permission('security_logs');
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';
use Config\Database;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT sl.*, 
        COALESCE(a.name, t.name, s.name) as user_name,
        CASE 
            WHEN sl.user_table = 'admins' THEN 'ADMIN'
            WHEN sl.user_table = 'teachers' THEN 'FACULTY'
            WHEN sl.user_table = 'students' THEN 'STUDENT'
            ELSE 'UNKNOWN'
        END as role_name 
    FROM security_logs sl 
    LEFT JOIN admins a ON sl.user_table = 'admins' AND sl.user_id = a.id
    LEFT JOIN teachers t ON sl.user_table = 'teachers' AND sl.user_id = t.id
    LEFT JOIN students s ON sl.user_table = 'students' AND sl.user_id = s.id
    ORDER BY sl.created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();

// Telemetry Metric Counters
$totalCount = count($logs);
$adminCount = 0;
$facultyCount = 0;
$studentCount = 0;
$blockedCount = 0;
$grantedCount = 0;

foreach ($logs as $l) {
    if ($l['role_name'] === 'ADMIN') $adminCount++;
    elseif ($l['role_name'] === 'FACULTY') $facultyCount++;
    elseif ($l['role_name'] === 'STUDENT') $studentCount++;
    
    if ($l['status'] === 'BLOCKED') $blockedCount++;
    else $grantedCount++;
}
?>

<!-- Main Content -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                    <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                        <i data-lucide="fingerprint" class="w-6 h-6"></i>
                    </div>
                    Network Surveillance Matrix
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Monitoring all inbound Admin, Faculty, and Student authentication streams. (Showing last 100 events)
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live Feed Active
                </span>
            </div>
        </div>

        <!-- Telemetry KPI Stream Counters -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- Total Events -->
            <div class="bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm backdrop-blur-sm">
                <div class="flex items-center justify-between text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">
                    <span>Total Streams</span>
                    <i data-lucide="activity" class="w-4 h-4 text-indigo-500"></i>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white"><?= $totalCount ?></div>
            </div>

            <!-- Admin Streams -->
            <div class="bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-blue-200/80 dark:border-blue-800/50 shadow-sm backdrop-blur-sm">
                <div class="flex items-center justify-between text-xs text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider mb-1">
                    <span>Admin</span>
                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-500"></i>
                </div>
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400"><?= $adminCount ?></div>
            </div>

            <!-- Faculty Streams -->
            <div class="bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-amber-200/80 dark:border-amber-800/50 shadow-sm backdrop-blur-sm">
                <div class="flex items-center justify-between text-xs text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider mb-1">
                    <span>Faculty</span>
                    <i data-lucide="briefcase" class="w-4 h-4 text-amber-500"></i>
                </div>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400"><?= $facultyCount ?></div>
            </div>

            <!-- Student Streams -->
            <div class="bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-purple-200/80 dark:border-purple-800/50 shadow-sm backdrop-blur-sm">
                <div class="flex items-center justify-between text-xs text-purple-600 dark:text-purple-400 font-bold uppercase tracking-wider mb-1">
                    <span>Student</span>
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-purple-500"></i>
                </div>
                <div class="text-2xl font-black text-purple-600 dark:text-purple-400"><?= $studentCount ?></div>
            </div>

            <!-- Blocked Streams -->
            <div class="bg-white dark:bg-slate-800/80 p-4 rounded-2xl border border-rose-200/80 dark:border-rose-800/50 shadow-sm backdrop-blur-sm col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between text-xs text-rose-600 dark:text-rose-400 font-bold uppercase tracking-wider mb-1">
                    <span>Blocked</span>
                    <i data-lucide="shield-alert" class="w-4 h-4 text-rose-500"></i>
                </div>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400"><?= $blockedCount ?></div>
            </div>
        </div>

        <!-- Filter Tabs & Real-Time Search Bar -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <!-- Filter Tabs -->
            <div class="inline-flex p-1 rounded-2xl bg-slate-200/70 dark:bg-slate-800 border border-slate-300/70 dark:border-slate-700 text-xs font-bold overflow-x-auto shadow-inner">
                <button type="button" class="filter-tab px-4 py-2 rounded-xl transition-all font-bold bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm flex items-center gap-1.5" data-filter="all">
                    <span>All</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-indigo-50 dark:bg-slate-800 text-[11px]"><?= $totalCount ?></span>
                </button>
                <button type="button" class="filter-tab px-4 py-2 rounded-xl transition-all font-bold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-1.5" data-filter="admin">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-500"></i>
                    <span>Admin</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px]"><?= $adminCount ?></span>
                </button>
                <button type="button" class="filter-tab px-4 py-2 rounded-xl transition-all font-bold text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 flex items-center gap-1.5" data-filter="faculty">
                    <i data-lucide="briefcase" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span>Faculty</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px]"><?= $facultyCount ?></span>
                </button>
                <button type="button" class="filter-tab px-4 py-2 rounded-xl transition-all font-bold text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 flex items-center gap-1.5" data-filter="student">
                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-purple-500"></i>
                    <span>Student</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px]"><?= $studentCount ?></span>
                </button>
                <button type="button" class="filter-tab px-4 py-2 rounded-xl transition-all font-bold text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 flex items-center gap-1.5" data-filter="blocked">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-rose-500"></i>
                    <span>Blocked</span>
                    <span class="px-1.5 py-0.2 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px]"><?= $blockedCount ?></span>
                </button>
            </div>

            <!-- Search Box -->
            <div class="relative w-full md:w-80">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" id="logSearch" placeholder="Filter by name, email, IP, browser..." class="w-full pl-10 pr-4 py-2.5 rounded-2xl text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all shadow-sm">
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/60 overflow-hidden shadow-lg backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] table-fixed text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/60">
                        <tr>
                            <th scope="col" class="w-28 px-3.5 py-3.5 font-bold tracking-wider whitespace-nowrap">Login Time</th>
                            <th scope="col" class="w-24 px-3 py-3.5 font-bold tracking-wider whitespace-nowrap">Role</th>
                            <th scope="col" class="w-56 px-3.5 py-3.5 font-bold tracking-wider">Entity / Attempt</th>
                            <th scope="col" class="w-24 px-3 py-3.5 font-bold tracking-wider whitespace-nowrap">Status</th>
                            <th scope="col" class="w-24 px-3 py-3.5 font-bold tracking-wider whitespace-nowrap">IP Address</th>
                            <th scope="col" class="px-4 py-3.5 font-bold tracking-wider">Node / Browser Vector</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody" class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $logDate = date('M d, y', strtotime($log['created_at']));
                            $logTime = date('H:i:s', strtotime($log['created_at']));
                            $roleName = $log['role_name'];
                            $hasUser = !empty($log['user_id']);
                            
                            // Visual Role Archetypes
                            switch ($roleName) {
                                case 'ADMIN':
                                    $roleIcon = 'shield-check';
                                    $roleLabel = 'ADMIN';
                                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
                                    $iconBoxClass = 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-800';
                                    $nameColorClass = 'text-blue-700 dark:text-blue-300';
                                    break;
                                case 'FACULTY':
                                    $roleIcon = 'briefcase';
                                    $roleLabel = 'FACULTY';
                                    $badgeClass = 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
                                    $iconBoxClass = 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/40 dark:text-amber-400 dark:border-amber-800';
                                    $nameColorClass = 'text-amber-700 dark:text-amber-300';
                                    break;
                                case 'STUDENT':
                                    $roleIcon = 'graduation-cap';
                                    $roleLabel = 'STUDENT';
                                    $badgeClass = 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800';
                                    $iconBoxClass = 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-900/40 dark:text-purple-400 dark:border-purple-800';
                                    $nameColorClass = 'text-purple-700 dark:text-purple-300';
                                    break;
                                default:
                                    $roleIcon = 'user-x';
                                    $roleLabel = 'UNKNOWN';
                                    $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
                                    $iconBoxClass = 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700';
                                    $nameColorClass = 'text-slate-600 dark:text-slate-400';
                                    break;
                            }
                            
                            $entityName = $hasUser ? strtoupper($log['user_name']) : strtoupper($log['email_attempt']);
                            $searchString = strtolower($entityName . ' ' . $log['email_attempt'] . ' ' . $log['ip_address'] . ' ' . $log['browser_vector'] . ' ' . $roleName . ' ' . $log['status']);
                            ?>
                            <tr class="log-row hover:bg-slate-50/80 dark:hover:bg-slate-700/20 transition-colors group" 
                                data-role="<?= strtolower($roleName) ?>" 
                                data-status="<?= strtolower($log['status']) ?>"
                                data-search="<?= htmlspecialchars($searchString) ?>">
                                
                                <!-- Login Time -->
                                <td class="px-3.5 py-3.5 whitespace-nowrap text-xs">
                                    <div class="font-medium text-slate-800 dark:text-slate-200"><?= $logDate ?></div>
                                    <div class="text-[11px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                                        <?= $logTime ?>
                                    </div>
                                </td>

                                <!-- Role Column -->
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-black border shadow-sm <?= $badgeClass ?>">
                                        <i data-lucide="<?= $roleIcon ?>" class="w-3.5 h-3.5"></i>
                                        <?= $roleLabel ?>
                                    </span>
                                </td>

                                <!-- Entity / Attempt -->
                                <td class="px-3.5 py-3.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="p-2 rounded-xl border flex items-center justify-center shrink-0 shadow-sm <?= $iconBoxClass ?>">
                                            <i data-lucide="<?= $hasUser ? $roleIcon : 'user-x' ?>" class="w-4 h-4"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold tracking-wide text-xs truncate <?= $hasUser ? $nameColorClass : 'text-slate-800 dark:text-slate-200' ?>" title="<?= htmlspecialchars($entityName) ?>">
                                                <?= htmlspecialchars($entityName) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-mono mt-0.5 truncate flex items-center gap-1.5" title="<?= htmlspecialchars($hasUser ? ('ID: #' . $log['user_id'] . (!empty($log['email_attempt']) ? ' • ' . $log['email_attempt'] : '')) : 'Unregistered / Failed Auth') ?>">
                                                <?php if ($hasUser): ?>
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">ID: #<?= htmlspecialchars($log['user_id']) ?></span>
                                                    <?php if (!empty($log['email_attempt'])): ?>
                                                        <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                                        <span class="truncate"><?= htmlspecialchars($log['email_attempt']) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-rose-600 dark:text-rose-400 font-sans font-bold flex items-center gap-1 truncate">
                                                        <i data-lucide="alert-circle" class="w-3 h-3 shrink-0"></i> Failed Auth
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-3 py-3.5 whitespace-nowrap">
                                    <?php if ($log['status'] === 'GRANTED'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/60 shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            GRANTED
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black tracking-wider bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/60 shadow-sm">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            BLOCKED
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- IP Address -->
                                <td class="px-3 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600 dark:text-slate-300">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-900/70 border border-slate-200/80 dark:border-slate-700/60 text-[11px]">
                                        <i data-lucide="globe" class="w-3 h-3 text-slate-400"></i>
                                        <?= htmlspecialchars($log['ip_address']) ?>
                                    </span>
                                </td>

                                <!-- Node / Browser Vector -->
                                <td class="px-4 py-3.5 text-xs">
                                    <?php
                                    $bv = $log['browser_vector'] ?? 'UNKNOWN';
                                    if ($bv !== 'UNKNOWN' && !empty($bv)):
                                        // Quick OS & Browser detector for scannability
                                        $os = 'Unknown OS';
                                        if (stripos($bv, 'Windows NT 10.0') !== false) $os = 'Windows 10/11';
                                        elseif (stripos($bv, 'Windows NT 6.3') !== false) $os = 'Windows 8.1';
                                        elseif (stripos($bv, 'Windows NT 6.1') !== false) $os = 'Windows 7';
                                        elseif (stripos($bv, 'Windows') !== false) $os = 'Windows';
                                        elseif (stripos($bv, 'Macintosh') !== false || stripos($bv, 'Mac OS X') !== false) $os = 'macOS';
                                        elseif (stripos($bv, 'Android') !== false) $os = 'Android';
                                        elseif (stripos($bv, 'iPhone') !== false || stripos($bv, 'iPad') !== false) $os = 'iOS';
                                        elseif (stripos($bv, 'Linux') !== false) $os = 'Linux';

                                        $browser = 'Browser';
                                        if (stripos($bv, 'Edg/') !== false) $browser = 'Edge';
                                        elseif (stripos($bv, 'Chrome/') !== false) $browser = 'Chrome';
                                        elseif (stripos($bv, 'Firefox/') !== false) $browser = 'Firefox';
                                        elseif (stripos($bv, 'Safari/') !== false) $browser = 'Safari';
                                    ?>
                                        <div class="mb-1 flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                                <i data-lucide="laptop" class="w-3 h-3 text-slate-400"></i>
                                                <?= htmlspecialchars($os) ?> &bull; <?= htmlspecialchars($browser) ?>
                                            </span>
                                        </div>
                                        <div class="font-mono text-[10.5px] text-slate-600 dark:text-slate-400 [overflow-wrap:anywhere] break-words leading-relaxed select-all" title="Full User Agent String">
                                            <?= htmlspecialchars($bv) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                            UNKNOWN
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Empty Filter State -->
                        <tr id="noResultsRow" class="hidden">
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i data-lucide="filter-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                                <p class="text-sm font-semibold">No authentication events match your filter criteria.</p>
                            </td>
                        </tr>

                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                    <i data-lucide="activity" class="w-8 h-8 mx-auto mb-3 opacity-50"></i>
                                    No authentication streams detected yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer Status Bar -->
            <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200/80 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span id="eventsCounter">Showing <?= $totalCount ?> of <?= $totalCount ?> events</span>
                <span class="text-slate-400">Logs automatically truncated to the most recent 100 entries</span>
            </div>
        </div>
        
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const tabs = document.querySelectorAll('.filter-tab');
    const searchInput = document.getElementById('logSearch');
    const rows = document.querySelectorAll('.log-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const counter = document.getElementById('eventsCounter');
    const totalCount = rows.length;

    let currentFilter = 'all';
    let searchQuery = '';

    function applyFilters() {
        let visibleCount = 0;

        rows.forEach(row => {
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');
            const searchData = row.getAttribute('data-search') || '';

            let matchesTab = true;
            if (currentFilter === 'admin') matchesTab = (role === 'admin');
            else if (currentFilter === 'faculty') matchesTab = (role === 'faculty');
            else if (currentFilter === 'student') matchesTab = (role === 'student');
            else if (currentFilter === 'blocked') matchesTab = (status === 'blocked');

            let matchesSearch = true;
            if (searchQuery) {
                matchesSearch = searchData.includes(searchQuery);
            }

            if (matchesTab && matchesSearch) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        if (noResultsRow) {
            if (visibleCount === 0 && totalCount > 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        if (counter) {
            counter.textContent = `Showing ${visibleCount} of ${totalCount} events`;
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'dark:text-white', 'shadow-sm');
                t.classList.add('text-slate-600', 'dark:text-slate-400');
            });
            tab.classList.add('bg-white', 'dark:bg-slate-700', 'text-indigo-600', 'dark:text-white', 'shadow-sm');
            tab.classList.remove('text-slate-600', 'dark:text-slate-400');

            currentFilter = tab.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            applyFilters();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

