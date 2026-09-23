<?php
// views/admin/view-profile.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('ADMIN');
$pageTitle = 'View Profile | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Fetch user details for display
$stmt = $db->prepare("SELECT name, role, email, phone, status, created_at, profile_pic FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-3xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i data-lucide="user" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i> My Profile
            </h1>
            <a href="<?= BASE_URL ?>/views/admin/edit-profile.php" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Profile
            </a>
        </div>

        <?php 
        $flash = get_flash_message();
        if ($flash): 
            $bgClass = $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800';
            $icon = $flash['type'] === 'success' ? 'check-circle' : 'alert-circle';
        ?>
            <div class="p-4 rounded-xl border flex items-start gap-3 <?= $bgClass ?>">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Profile Card -->
        <style>
            @keyframes bannerGradient {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            .animate-banner {
                background-size: 200% 200%;
                animation: bannerGradient 8s ease infinite;
            }
        </style>
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden group">
            <!-- Banner / Header area -->
            <div class="h-40 bg-gradient-to-r from-blue-900/60 via-indigo-900/50 to-blue-950/60 animate-banner relative overflow-hidden backdrop-blur-2xl">
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl transform group-hover:scale-110 transition-transform duration-1000"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl transform group-hover:-translate-y-8 transition-transform duration-1000"></div>
            </div>
            
            <div class="px-6 sm:px-8 pb-8">
                <!-- Avatar & Basic Info -->
                <div class="flex flex-col sm:flex-row items-center sm:items-end -mt-16 sm:-mt-20 gap-6 mb-8">
                    <div class="relative group/avatar">
                        <?php if ($user['profile_pic']): ?>
                            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="w-32 h-32 sm:w-40 sm:h-40 rounded-full object-cover border-4 border-white dark:border-slate-800 shadow-lg bg-white dark:bg-slate-800">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=4f46e5&color=fff&bold=true&size=160" alt="Profile Avatar" class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border-4 border-white dark:border-slate-800 shadow-lg bg-white dark:bg-slate-800">
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/views/admin/edit-profile.php" class="absolute inset-0 bg-slate-900/60 rounded-full opacity-0 group-hover/avatar:opacity-100 transition-opacity flex flex-col items-center justify-center text-white backdrop-blur-sm m-1">
                            <i data-lucide="camera" class="w-6 h-6 mb-1"></i>
                            <span class="text-xs font-bold">Change</span>
                        </a>
                    </div>
                    
                    <div class="flex-1 text-center sm:text-left mb-2">
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($user['name']) ?></h2>
                        <p class="text-indigo-600 dark:text-indigo-400 font-medium tracking-wide mt-1"><?= htmlspecialchars($user['role'] ?? 'ADMINISTRATOR') ?></p>
                    </div>
                </div>

                <!-- Detailed Information Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t border-slate-100 dark:border-slate-700/50">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4">Contact Information</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-400 dark:text-slate-300">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Email Address</p>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-400 dark:text-slate-300">
                                    <i data-lucide="phone" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Phone Number</p>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-slate-400 italic">Not provided</span>' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4">Account Status</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 dark:text-emerald-400">
                                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Current Status</p>
                                    <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <?= htmlspecialchars($user['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-3">
                                <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-slate-400 dark:text-slate-300">
                                    <i data-lucide="calendar" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Member Since</p>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
