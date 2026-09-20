<?php
// views/admin/edit-notice.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';

require_role('ADMIN');
$pageTitle = 'Edit Notice | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: notices.php');
    exit;
}

$error = '';
$success = '';

$stmt = $db->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->execute([$id]);
$notice = $stmt->fetch();

if (!$notice) {
    header('Location: notices.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target_role = $_POST['target_role'] ?? 'ALL';
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $attachment_url = $notice['attachment_url']; // keep existing

    if (empty($title) || empty($content)) {
        $error = 'Title and Content are required.';
    } else {
        try {
            $stmt = $db->prepare("UPDATE notices SET title = ?, content = ?, target_role = ?, attachment_url = ?, is_pinned = ? WHERE id = ?");
            $stmt->execute([$title, $content, $target_role, $attachment_url, $is_pinned, $id]);
            header('Location: notices.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Error updating notice.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="edit" class="w-6 h-6 text-indigo-500"></i> Edit Notice
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update announcement details.</p>
            </div>
            <a href="notices.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Notices
            </a>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden p-6 sm:p-8">
            <?php if ($error): ?>
                <div class="mb-6 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-xl text-sm border border-rose-200 dark:border-rose-800 flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 p-4 rounded-xl text-sm border border-emerald-200 dark:border-emerald-800 flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Notice Title *</label>
                        <input type="text" name="title" required
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"
                            value="<?= htmlspecialchars($notice['title']) ?>">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Target Audience</label>
                            <select name="target_role" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow">
                                <option value="ALL" <?= $notice['target_role'] == 'ALL' ? 'selected' : '' ?>>All Roles</option>
                                <option value="FACULTY" <?= $notice['target_role'] == 'FACULTY' ? 'selected' : '' ?>>Faculty Only</option>
                                <option value="STUDENT" <?= $notice['target_role'] == 'STUDENT' ? 'selected' : '' ?>>Students Only</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-8">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative flex items-center">
                                    <input type="checkbox" name="is_pinned" class="peer sr-only" <?= $notice['is_pinned'] ? 'checked' : '' ?>>
                                    <div class="h-6 w-11 rounded-full bg-slate-200 dark:bg-slate-700 peer-checked:bg-primary transition-colors"></div>
                                    <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Pin to top</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Content *</label>
                        <textarea name="content" required rows="6"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary shadow-sm sm:text-sm px-4 py-3 border outline-none transition-shadow"><?= htmlspecialchars($notice['content']) ?></textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 bg-primary hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900 hover:-translate-y-0.5">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
