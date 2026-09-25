<?php
// views/student/notices.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('STUDENT');
$pageTitle = 'Notices | Student Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/NoticeController.php';

use Controllers\NoticeController;

$controller = new NoticeController();
$notices = $controller->getNoticesForRole($_SESSION['role_name']);
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="bell" class="w-6 h-6 text-indigo-500"></i> Notice Board
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Stay updated with the latest announcements and institutional notices.</p>
            </div>
        </div>

        <?php if (empty($notices)): ?>
            <!-- Empty State -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-10 text-center border border-slate-200 dark:border-slate-700 shadow-sm animate-fade-in">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4 transition-transform hover:scale-110 duration-300">
                    <i data-lucide="bell-off" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">No notices found</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">There are currently no announcements or notices available.</p>
            </div>
        <?php else: ?>
            <!-- Notices List -->
            <div class="space-y-4">
                <?php foreach ($notices as $notice): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border <?= $notice['is_pinned'] ? 'border-amber-200 dark:border-amber-900/50 ring-1 ring-amber-100 dark:ring-amber-900/30' : 'border-slate-200 dark:border-slate-700' ?> overflow-hidden transform transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 relative group">
                        
                        <?php if ($notice['is_pinned']): ?>
                            <div class="absolute top-0 right-0 w-0 h-0 border-t-[3rem] border-r-[3rem] border-t-amber-100 dark:border-t-amber-900/40 border-r-transparent"></div>
                            <i data-lucide="pin" class="absolute top-2 right-2 w-4 h-4 text-amber-500 fill-amber-500/20"></i>
                        <?php endif; ?>

                        <div class="p-5 sm:p-6">
                            <div class="flex items-center gap-3 mb-3">
                                <?php
                                $roleClass = 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
                                if ($notice['target_role'] === 'ALL') $roleClass = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800/50';
                                else if ($notice['target_role'] === 'STUDENT') $roleClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $roleClass ?>">
                                    <?= htmlspecialchars($notice['target_role']) ?>
                                </span>
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    <?= date('M j, Y g:i A', strtotime($notice['created_at'])) ?>
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 pr-8">
                                <?= htmlspecialchars($notice['title']) ?>
                            </h3>
                            
                            <div class="prose prose-sm dark:prose-invert prose-slate max-w-none text-slate-600 dark:text-slate-300">
                                <?= nl2br(htmlspecialchars($notice['content'])) ?>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <i data-lucide="user" class="w-4 h-4"></i> Published by <?= htmlspecialchars($notice['author']) ?>
                                </span>
                                
                                <?php if (!empty($notice['attachment_url'])): ?>
                                    <a href="<?= htmlspecialchars($notice['attachment_url']) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold transition-colors">
                                        <i data-lucide="paperclip" class="w-4 h-4"></i> Attachment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
