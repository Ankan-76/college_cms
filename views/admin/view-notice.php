<?php
// views/admin/view-notice.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_permission('notices');
$pageTitle = 'View Notice | College Management System';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: notices.php');
    exit;
}

use Config\Database;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT n.*, a.name as author
    FROM notices n
    JOIN admins a ON n.created_by = a.id
    WHERE n.id = ?
");
$stmt->execute([$id]);
$notice = $stmt->fetch();

if (!$notice) {
    header('Location: notices.php');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="bell" class="w-6 h-6 text-indigo-500"></i> Notice Board
            </h1>
            <a href="notices.php" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center gap-1 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Notices
            </a>
        </div>
        
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden relative">
            <?php if ($notice['is_pinned']): ?>
                <div class="absolute top-0 right-0 p-6 z-10">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400 shadow-sm" title="Pinned Notice">
                        <i data-lucide="pin" class="w-5 h-5 fill-current"></i>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="p-8 sm:p-12 border-b border-slate-200 dark:border-slate-700">
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold 
                        <?= $notice['target_role'] === 'ALL' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' : '' ?>
                        <?= $notice['target_role'] === 'STUDENT' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400' : '' ?>
                        <?= $notice['target_role'] === 'FACULTY' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : '' ?>
                    ">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i> Target: <?= htmlspecialchars($notice['target_role']) ?>
                    </span>
                    <span class="text-xs font-medium text-slate-500 flex items-center gap-1">
                        <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i> <?= date('F j, Y, g:i a', strtotime($notice['created_at'])) ?>
                    </span>
                </div>
                
                <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white mb-6 pr-12"><?= htmlspecialchars($notice['title']) ?></h2>
                
                <div class="prose prose-slate dark:prose-invert max-w-none prose-p:leading-relaxed prose-a:text-indigo-600 dark:prose-a:text-indigo-400">
                    <!-- Note: nl2br is used assuming content was saved as plain text -->
                    <?= nl2br(htmlspecialchars($notice['content'])) ?>
                </div>
                
                <?php if ($notice['attachment_url']): ?>
                    <div class="mt-8 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700 inline-block">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-2">
                            <i data-lucide="paperclip" class="w-4 h-4"></i> Attachment
                        </h4>
                        <a href="<?= BASE_URL . '/uploads/notices/' . htmlspecialchars($notice['attachment_url']) ?>" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline flex items-center gap-2">
                            <?= htmlspecialchars($notice['attachment_url']) ?>
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 flex items-center justify-between border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 shadow-sm border border-slate-300 dark:border-slate-600">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Posted by <?= htmlspecialchars($notice['author']) ?></span>
                </div>
                
                <a href="edit-notice.php?id=<?= $notice['id'] ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold flex items-center gap-2 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i data-lucide="edit-2" class="w-4 h-4"></i> Edit Notice
                </a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
