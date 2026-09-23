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
?>

<!-- Main Content -->
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Network Surveillance Matrix</h1>
            <p class="text-slate-500 dark:text-slate-400">Monitoring all inbound Admin and Student authentication streams. (Showing last 100 events)</p>
        </div>

        <!-- Table Container -->
        <div class="bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 overflow-hidden shadow-xl backdrop-blur-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Login Time</th>
                            <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Entity</th>
                            <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 font-semibold tracking-wider">IP Address</th>
                            <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Node / Browser Vector</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $date = date('M d, y • H:i:s', strtotime($log['created_at']));
                            
                            // Determine Entity
                            if ($log['user_id']) {
                                $entityName = strtoupper($log['user_name']);
                                $entityId = $log['user_id'];
                                if ($log['role_name'] === 'ADMIN') {
                                    $icon = 'shield-check';
                                    $colorClass = 'text-blue-600 dark:text-blue-400';
                                } else {
                                    $icon = 'users'; // multiple users icon as in screenshot
                                    $colorClass = 'text-emerald-600 dark:text-emerald-400';
                                }
                            } else {
                                $entityName = strtoupper($log['email_attempt']);
                                $entityId = 'N/A';
                                $icon = 'user-x';
                                $colorClass = 'text-slate-500 dark:text-slate-400';
                            }
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-600 dark:text-slate-300">
                                    <?= $date ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                            <i data-lucide="<?= $icon ?>" class="w-4 h-4 <?= $colorClass ?>"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold tracking-wide <?= $colorClass ?>"><?= htmlspecialchars($entityName) ?></div>
                                            <div class="text-xs text-slate-500 font-mono mt-0.5">ID: <?= htmlspecialchars($entityId) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($log['status'] === 'GRANTED'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/50">
                                            GRANTED
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/50">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            BLOCKED
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 max-w-sm truncate" title="<?= htmlspecialchars($log['browser_vector']) ?>">
                                    <?= htmlspecialchars($log['browser_vector']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <i data-lucide="activity" class="w-8 h-8 mx-auto mb-3 opacity-50"></i>
                                    No authentication streams detected yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
