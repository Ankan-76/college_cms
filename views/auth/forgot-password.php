<?php
// views/auth/forgot-password.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

$pageTitle = 'Forgot Password | Greenfield University';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { primary: '#4f46e5' }, fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark'); }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen flex items-center justify-center p-4 font-sans relative overflow-hidden transition-colors">
    
    <!-- Ambient -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 dark:opacity-15 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 dark:opacity-15 animate-blob" style="animation-delay: 2s;"></div>
    </div>

    <div class="max-w-md w-full bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden animate-fade-in border border-slate-200/50 dark:border-slate-700/50 z-10 relative">
        <div class="p-8 sm:p-10">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-indigo-100 dark:border-indigo-800 shadow-sm transition-transform hover:scale-105 duration-300">
                    <i data-lucide="key-round" class="w-8 h-8"></i>
                </div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Reset Password</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-medium">Enter your registered email or roll number to receive an OTP.</p>
            </div>
            
            <form action="<?= htmlspecialchars(BASE_URL) ?>/index.php?action=forgot-password" method="POST" class="space-y-6">
                <?= csrf_field() ?>
                
                <div>
                    <label for="identifier" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Email / Roll Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="user" class="w-5 h-5 text-slate-400"></i>
                        </div>
                        <input type="text" name="identifier" id="identifier" required class="block w-full pl-12 pr-4 rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 sm:text-sm py-3 outline-none transition-all placeholder-slate-400 dark:placeholder-slate-500" placeholder="user@example.com or 101234">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold flex items-center justify-center gap-2 py-3 px-4 rounded-xl transform transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 shadow-md">
                    Send OTP <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
        <div class="px-8 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700/50 text-center flex items-center justify-between">
            <a href="<?= htmlspecialchars(BASE_URL) ?>/views/auth/student_login.php" class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3 h-3"></i> Back to Login
            </a>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">&copy; <?= date('Y') ?> Greenfield University</span>
        </div>
    </div>

    <!-- Theme Toggle -->
    <button id="theme-toggle" type="button" class="fixed bottom-6 right-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-3 rounded-full shadow-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-indigo-500 z-50">
        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
    </button>

    <style>
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            const themeToggleBtn = document.getElementById('theme-toggle');
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            const updateIcons = () => {
                if (document.documentElement.classList.contains('dark')) { darkIcon.classList.add('hidden'); lightIcon.classList.remove('hidden'); }
                else { lightIcon.classList.add('hidden'); darkIcon.classList.remove('hidden'); }
            };
            updateIcons();
            themeToggleBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                if (document.documentElement.classList.contains('dark')) { localStorage.theme = 'dark'; }
                else { localStorage.theme = 'light'; }
                updateIcons();
            });
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true }).fire({
                    icon: <?= json_encode($_SESSION['flash_message']['type']) ?>,
                    title: <?= json_encode($_SESSION['flash_message']['message']) ?>
                });
            <?php endif; unset($_SESSION['flash_message']); ?>
        });
    </script>
</body>
</html>
