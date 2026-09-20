<?php
// views/student/view-profile.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('STUDENT');
$pageTitle = 'My Profile | Student Portal';

use Config\Database;
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT s.*, d.dept_name, d.dept_code, sem.semester_number, sem.academic_year
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN semesters sem ON s.semester_id = sem.id
    WHERE s.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(\PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2 tracking-tight">
                <i data-lucide="user-circle" class="w-7 h-7 text-indigo-600 dark:text-indigo-400"></i> My Profile
            </h1>
            <a href="<?= BASE_URL ?>/views/student/edit-profile.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-indigo-600/20 hover:-translate-y-0.5 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Profile
            </a>
        </div>

        <?php 
        $flash = get_flash_message();
        if ($flash): 
            $bgClass = $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800';
            $icon = $flash['type'] === 'success' ? 'check-circle' : 'alert-circle';
        ?>
            <div class="p-4 rounded-xl border flex items-start gap-3 <?= $bgClass ?> animate-in fade-in slide-in-from-top-2 duration-300">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p class="text-sm font-semibold"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$user): ?>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-12 text-center border border-slate-200 dark:border-slate-700 shadow-sm">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-700/50 text-slate-400 mb-5">
                <i data-lucide="user-x" class="w-10 h-10"></i>
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Profile Not Found</h3>
            <p class="text-sm text-slate-500 font-medium">Unable to load your profile information. Please contact administration.</p>
        </div>
        <?php else: ?>

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
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden group">
            <!-- Dynamic Banner -->
            <div class="h-40 bg-gradient-to-r from-blue-900/60 via-indigo-900/50 to-blue-950/60 animate-banner relative overflow-hidden backdrop-blur-2xl">
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl transform group-hover:scale-110 transition-transform duration-1000"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl transform group-hover:-translate-y-8 transition-transform duration-1000"></div>
            </div>
            
            <div class="px-6 sm:px-10 pb-10">
                <!-- Avatar & Status -->
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6 -mt-16 mb-8 relative z-10">
                    <?php
                    $picUrl = !empty($user['profile_pic'])
                        ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($user['profile_pic'])
                        : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=4f46e5&color=fff&bold=true&size=256";
                    ?>
                    <div class="relative group/avatar">
                        <img class="w-32 h-32 rounded-full object-cover border-4 border-white dark:border-slate-800 shadow-xl bg-white dark:bg-slate-800" src="<?= $picUrl ?>" alt="Profile Photo">
                        <a href="<?= BASE_URL ?>/views/student/edit-profile.php" class="absolute inset-0 bg-slate-900/60 rounded-full opacity-0 group-hover/avatar:opacity-100 transition-opacity flex flex-col items-center justify-center text-white backdrop-blur-sm m-1">
                            <i data-lucide="camera" class="w-6 h-6 mb-1"></i>
                            <span class="text-xs font-bold">Change</span>
                        </a>
                    </div>
                    
                    <div class="text-center sm:text-left flex-1 pt-2 sm:pt-0">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?= htmlspecialchars($user['name']) ?></h2>
                        <p class="text-sm text-indigo-600 dark:text-indigo-400 font-bold mt-1 flex items-center justify-center sm:justify-start gap-1.5">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i> STUDENT
                        </p>
                    </div>

                    <div class="shrink-0 flex items-center gap-3 bg-slate-50 dark:bg-slate-900/50 p-2 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm">
                        <div class="flex flex-col text-center px-4 py-1">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Semester</span>
                            <span class="text-lg font-black text-slate-900 dark:text-white leading-none"><?= htmlspecialchars((string) $user['semester_number']) ?></span>
                        </div>
                        <div class="w-px h-8 bg-slate-200 dark:bg-slate-700"></div>
                        <div class="flex flex-col items-center justify-center px-4 py-1">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Status</span>
                            <?php $statusColor = $user['status'] === 'ACTIVE' ? 'emerald' : 'rose'; ?>
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-<?= $statusColor ?>-100 dark:bg-<?= $statusColor ?>-900/30 px-2 py-0.5 text-[10px] font-black text-<?= $statusColor ?>-700 dark:text-<?= $statusColor ?>-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-<?= $statusColor ?>-500 <?= $user['status'] === 'ACTIVE' ? 'animate-pulse' : '' ?>"></span>
                                <?= htmlspecialchars($user['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Contact Group -->
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                            <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                                <i data-lucide="mail" class="w-5 h-5 text-indigo-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Email Address</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                            <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                                <i data-lucide="phone" class="w-5 h-5 text-emerald-500"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Phone Number</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Identifiers -->
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="hash" class="w-5 h-5 text-amber-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Roll Number</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['roll_number']) ?></p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="id-card" class="w-5 h-5 text-purple-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Registration Number</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['registration_number']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Program Info -->
                    <div class="md:col-span-2 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="building-2" class="w-5 h-5 text-rose-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Department / Program</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['dept_name']) ?> <span class="text-slate-400 font-medium">(<?= htmlspecialchars($user['dept_code']) ?>)</span></p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="calendar-days" class="w-5 h-5 text-cyan-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Academic Year</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($user['academic_year']) ?></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/50 flex items-center gap-4 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800">
                        <div class="w-10 h-10 rounded-full bg-white dark:bg-slate-700 flex items-center justify-center shadow-sm shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-slate-500 dark:text-slate-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Enrolled Since</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
