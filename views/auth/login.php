<?php
// views/auth/login.php — Role Selection & Unified Portal Gateway
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

// Redirect already authenticated users straight to their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    $role = strtolower($_SESSION['role_name']);
    redirect("/views/{$role}/dashboard.php");
}

$pageTitle = 'Choose Your Portal | Greenfield College';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Choose your designated portal to sign in to Greenfield College's Management System. Dedicated access for students, faculty, and administrators.">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { primary: '#4f46e5', secondary: '#10b981' },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(1.5deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
        
        .portal-choice-card {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .portal-choice-card:hover {
            transform: translateY(-8px) scale(1.02);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glass-panel {
            background: rgba(15, 23, 42, 0.85);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans min-h-screen flex flex-col justify-between relative overflow-x-hidden transition-colors duration-300">

    <!-- Ambient Glowing Background -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-20 left-10 w-96 h-96 bg-indigo-500/20 dark:bg-indigo-600/10 rounded-full filter blur-[100px] animate-float"></div>
        <div class="absolute top-1/2 -right-20 w-[500px] h-[500px] bg-purple-500/15 dark:bg-purple-600/10 rounded-full filter blur-[120px] animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute -bottom-20 left-1/3 w-96 h-96 bg-emerald-500/15 dark:bg-emerald-600/10 rounded-full filter blur-[100px] animate-float" style="animation-delay: 4s;"></div>
    </div>

    <!-- ═══════════ HEADER BAR ═══════════ -->
    <header class="relative z-20 w-full glass-panel border-b border-slate-200/60 dark:border-slate-800/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="<?= $base ?>/" class="flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Home
            </a>

            <a href="<?= $base ?>/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-500/25">
                    <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                </div>
                <div class="hidden sm:block text-left">
                    <span class="font-black text-base tracking-tight text-slate-900 dark:text-white">Greenfield</span>
                    <span class="text-[10px] block -mt-1 text-slate-500 dark:text-slate-400 font-bold tracking-widest">COLLEGE</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <button id="theme-toggle" type="button" aria-label="Toggle Dark Mode" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2.5 rounded-full transition-colors">
                    <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
                    <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- ═══════════ MAIN CONTENT ═══════════ -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-4 sm:px-6 py-12 sm:py-16">
        <div class="max-w-5xl w-full mx-auto">
            <!-- Header Titles -->
            <div class="text-center mb-12 animate-fade-in-up">
                <span class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border border-indigo-100 dark:border-indigo-800/60 mb-4 shadow-sm">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-500"></i> Secure Institutional Authentication
                </span>
                <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">
                    Choose Your Portal
                </h1>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto font-medium">
                    Please select your designated institutional role to proceed to your dedicated sign-in gateway.
                </p>
            </div>

            <!-- Portal Choice Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                
                <!-- 1. Student Portal Card -->
                <a href="<?= $base ?>/views/auth/student_login.php" 
                   class="portal-choice-card group block bg-white dark:bg-slate-800/90 rounded-3xl p-8 border-2 border-slate-200/90 dark:border-slate-700/80 shadow-lg hover:shadow-2xl hover:border-indigo-400 dark:hover:border-indigo-500 text-center relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/5 to-transparent dark:from-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-xl shadow-indigo-500/25 group-hover:scale-110 group-hover:shadow-indigo-500/40 transition-all duration-300">
                            <i data-lucide="graduation-cap" class="w-10 h-10"></i>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Student Portal</h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mb-6 leading-relaxed">
                            Access your enrolled subjects, real-time attendance tracking, exam timetables, study materials, and grades.
                        </p>
                        
                        <div class="flex flex-wrap justify-center gap-1.5 mb-6">
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50">Subjects</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50">Attendance</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50">Grades</span>
                        </div>
                    </div>

                    <div class="relative z-10 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <span class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-indigo-600 group-hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-500/20 transition-all">
                            Sign In as Student <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </div>
                </a>

                <!-- 2. Faculty Portal Card -->
                <a href="<?= $base ?>/views/auth/faculty_login.php" 
                   class="portal-choice-card group block bg-white dark:bg-slate-800/90 rounded-3xl p-8 border-2 border-slate-200/90 dark:border-slate-700/80 shadow-lg hover:shadow-2xl hover:border-emerald-400 dark:hover:border-emerald-500 text-center relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute inset-0 bg-gradient-to-b from-emerald-500/5 to-transparent dark:from-emerald-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center text-white shadow-xl shadow-emerald-500/25 group-hover:scale-110 group-hover:shadow-emerald-500/40 transition-all duration-300">
                            <i data-lucide="briefcase" class="w-10 h-10"></i>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Faculty Portal</h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mb-6 leading-relaxed">
                            Record student attendance per lecture, manage subject curriculum, upload assignments, and enter student marks.
                        </p>
                        
                        <div class="flex flex-wrap justify-center gap-1.5 mb-6">
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50">Mark Attendance</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50">Assignments</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50">Marks</span>
                        </div>
                    </div>

                    <div class="relative z-10 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <span class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-emerald-600 group-hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition-all">
                            Sign In as Faculty <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </div>
                </a>

                <!-- 3. Admin Portal Card -->
                <a href="<?= $base ?>/views/auth/admin_login.php" 
                   class="portal-choice-card group block bg-white dark:bg-slate-800/90 rounded-3xl p-8 border-2 border-slate-200/90 dark:border-slate-700/80 shadow-lg hover:shadow-2xl hover:border-rose-400 dark:hover:border-rose-500 text-center relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute inset-0 bg-gradient-to-b from-rose-500/5 to-transparent dark:from-rose-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center text-white shadow-xl shadow-rose-500/25 group-hover:scale-110 group-hover:shadow-rose-500/40 transition-all duration-300">
                            <i data-lucide="shield-check" class="w-10 h-10"></i>
                        </div>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Admin Portal</h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mb-6 leading-relaxed">
                            Master administrative control over students, teachers, departments, timetables, notices, logs, and system RBAC.
                        </p>
                        
                        <div class="flex flex-wrap justify-center gap-1.5 mb-6">
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-100 dark:border-rose-800/50">User Governance</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-100 dark:border-rose-800/50">Departments</span>
                            <span class="text-[10px] font-bold uppercase px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-100 dark:border-rose-800/50">Notices</span>
                        </div>
                    </div>

                    <div class="relative z-10 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <span class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-rose-600 group-hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-500/20 transition-all">
                            Sign In as Admin <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </div>
                </a>

            </div>

            <!-- Bottom Support Help -->
            <div class="mt-12 text-center text-xs text-slate-500 dark:text-slate-400 font-medium">
                Forgot password or need help accessing your credentials? 
                <a href="<?= $base ?>/views/auth/forgot-password.php" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                    Reset Password
                </a>
                or contact the campus IT desk at 
                <a href="mailto:info@greenfield.edu" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">info@greenfield.edu</a>.
            </div>
        </div>
    </main>

    <!-- ═══════════ FOOTER ═══════════ -->
    <footer class="relative z-20 py-6 text-center text-xs text-slate-400 dark:text-slate-500 border-t border-slate-200/60 dark:border-slate-800/60">
        &copy; <?= date('Y') ?> Greenfield College. All rights reserved. Secure Education Management System.
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Theme Toggle
            const themeToggleBtn = document.getElementById('theme-toggle');
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            
            const updateIcons = () => {
                if (document.documentElement.classList.contains('dark')) {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                } else {
                    lightIcon.classList.add('hidden');
                    darkIcon.classList.remove('hidden');
                }
            };
            updateIcons();
            
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    document.documentElement.classList.toggle('dark');
                    localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                    updateIcons();
                });
            }
        });
    </script>
</body>
</html>
