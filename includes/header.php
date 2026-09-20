<?php
// includes/header.php

// Ensure session is started for auth handlers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'College Management System') ?></title>
    
    <!-- OpenGraph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'College Management System') ?>">
    <meta property="og:description" content="Next generation college management and administrative portal.">
    <meta property="og:type" content="website">

    <!-- PWA Setup -->
    <link rel="manifest" href="<?= defined('BASE_URL') ? BASE_URL : '/college_cms' ?>/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="https://ui-avatars.com/api/?name=GFC&background=4f46e5&color=fff&size=192">

    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <!-- Tailwind CSS (CDN for demo, CLI recommended for production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5', // indigo-600
                        secondary: '#10b981', // emerald-500
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Theme Initialization: Zero-flicker inline script -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Custom Styles (Scrollbar & Micro-interactions) -->
    <style>
        /* Custom Scrollbar for modern feel */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Glassmorphism Classes */
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .dark .glassmorphism {
            background: rgba(15, 23, 42, 0.7);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors duration-200 ease-in-out antialiased flex flex-col h-screen overflow-hidden">
    
    <!-- Navbar / Top Header -->
    <header class="sticky top-0 z-40 glassmorphism border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Mobile menu button -->
                <div class="flex items-center lg:hidden">
                    <button type="button" id="mobile-menu-btn" class="text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary rounded-md p-1 transition-colors" aria-expanded="false" aria-label="Open sidebar">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <span class="ml-4 font-semibold text-xl tracking-tight text-primary">CMS</span>
                </div>
                
                <!-- Desktop Logo -->
                <div class="hidden lg:flex lg:items-center">
                    <span class="font-bold text-2xl tracking-tight text-primary flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="w-8 h-8 text-indigo-600 dark:text-indigo-500"></i>
                        GreenField College
                    </span>
                </div>
                
                <!-- Right Header Actions -->
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Switch -->
                    <button id="theme-toggle" type="button" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary rounded-full p-2 transition-colors duration-200" aria-label="Toggle Dark Mode">
                        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
                        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
                    </button>

                    <?php
                    // Notification bell and message badge for students and faculty
                    $notifRole = strtoupper($_SESSION['role_name'] ?? '');
                    if (in_array($notifRole, ['STUDENT', 'FACULTY'])):
                        $notifCount = 0;
                        $msgUnreadCount = 0;
                        try {
                            require_once __DIR__ . '/../config/database.php';
                            $notifDb = \Config\Database::getInstance()->getConnection();
                            $notifStmt = $notifDb->prepare("SELECT COUNT(*) FROM notices WHERE target_role IN ('ALL', ?) AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                            $notifStmt->execute([$notifRole]);
                            $notifCount = (int) $notifStmt->fetchColumn();

                            // Unread message count
                            $otherMsgType = $notifRole === 'STUDENT' ? 'FACULTY' : 'STUDENT';
                            $msgIdCol = $notifRole === 'STUDENT' ? 'student_id' : 'faculty_id';
                            $msgStmt = $notifDb->prepare("
                                SELECT COUNT(*) FROM messages m
                                JOIN conversations c ON m.conversation_id = c.id
                                WHERE c.{$msgIdCol} = ? AND m.sender_type = ? AND m.is_read = 0
                            ");
                            $msgStmt->execute([$_SESSION['user_id'], $otherMsgType]);
                            $msgUnreadCount = (int) $msgStmt->fetchColumn();
                        } catch (Exception $e) { /* silent */ }
                        
                        $notifUrl = (defined('BASE_URL') ? BASE_URL : '/college_cms') . '/views/' . strtolower($notifRole === 'FACULTY' ? 'faculty' : 'student') . '/notices.php';
                        $msgUrl = (defined('BASE_URL') ? BASE_URL : '/college_cms') . '/views/' . strtolower($notifRole === 'FACULTY' ? 'faculty' : 'student') . '/messages.php';
                    ?>
                    <a href="<?= $msgUrl ?>" class="relative text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2 rounded-full transition-colors duration-200" aria-label="Messages">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                        <?php if ($msgUnreadCount > 0): ?>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-indigo-500 rounded-full shadow-sm"><?= $msgUnreadCount > 9 ? '9+' : $msgUnreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= $notifUrl ?>" class="relative text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2 rounded-full transition-colors duration-200" aria-label="Notifications">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <?php if ($notifCount > 0): ?>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-rose-500 rounded-full shadow-sm"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Profile Avatar -->
                    <div class="relative">
                        <?php 
                        $roleName = strtolower($_SESSION['role_name'] ?? '');
                        if (in_array($roleName, ['admin', 'faculty', 'student'])) {
                            $profileUrl = (defined('BASE_URL') ? BASE_URL : '/college_cms') . "/views/{$roleName}/view-profile.php";
                        } else {
                            $profileUrl = '#';
                        }
                        
                        $userName = $_SESSION['name'] ?? 'User';
                        
                        // Determine profile picture
                        if (!empty($_SESSION['profile_pic'])) {
                            $picUrl = (defined('BASE_URL') ? BASE_URL : '/college_cms') . '/uploads/profiles/' . htmlspecialchars($_SESSION['profile_pic']);
                        } else {
                            $picUrl = "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=4f46e5&color=fff&bold=true";
                        }
                        ?>
                        <div class="flex items-center gap-4">
                            <a href="<?= $profileUrl ?>" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-primary rounded-full transition-transform hover:scale-105 p-1 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="User profile">
                                <span class="hidden md:block text-sm font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($userName) ?></span>
                                <img class="h-9 w-9 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700 shadow-sm" src="<?= $picUrl ?>" alt="Profile Avatar">
                            </a>
                            
                            <?php if (($_SESSION['role_name'] ?? 'GUEST') !== 'GUEST'): ?>
                            <form action="<?= htmlspecialchars(defined('BASE_URL') ? BASE_URL : '/college_cms') ?>/index.php?action=logout" method="POST" class="m-0">
                                <?= (function_exists('csrf_field') ? csrf_field() : '') ?>
                                <button type="submit" class="flex items-center justify-center gap-2 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-400 rounded-lg text-sm font-medium transition-colors outline-none focus:ring-2 focus:ring-rose-500" aria-label="Log Out">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    <span class="hidden md:inline">Log Out</span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Application Flex Container -->
    <div class="flex flex-1 overflow-hidden relative">
        <!-- Sidebar component -->
        <?php 
        require_once 'sidebar.php'; 
        ?>
