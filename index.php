<?php
// index.php — Landing Page + Auth Router
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? null;
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// ── Action: Login POST ──────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        redirect('/views/auth/student_login.php');
    }
    
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new \Controllers\AuthController();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $portal = $_POST['portal'] ?? 'student'; // student | faculty | admin
    
    // Validate portal value
    if (!in_array($portal, ['student', 'faculty', 'admin'])) {
        $portal = 'student';
    }
    
    if ($auth->login($email, $password, $portal)) {
        $role = strtolower($_SESSION['role_name']);
        redirect("/views/{$role}/dashboard.php");
    } else {
        redirect("/views/auth/{$portal}_login.php");
    }
    
// ── Action: Admission Inquiry POST ──────────────────────────
} elseif ($action === 'inquiry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Security token validation failed. Please try again.', 'error');
        redirect('/');
    }
    
    require_once __DIR__ . '/controllers/InquiryController.php';
    $inquiryCtrl = new \Controllers\InquiryController();
    $inquiryCtrl->submitInquiry($_POST);
    redirect('/#contact');

// ── Action: Forgot Password POST ─────────────────────────────
} elseif ($action === 'forgot-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        redirect('/views/auth/forgot-password.php');
    }
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new \Controllers\AuthController();
    if ($auth->forgotPassword($_POST['identifier'] ?? '')) {
        redirect('/views/auth/verify-otp.php');
    } else {
        redirect('/views/auth/forgot-password.php');
    }

// ── Action: Verify OTP POST ──────────────────────────────────
} elseif ($action === 'verify-otp' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        redirect('/views/auth/verify-otp.php');
    }
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new \Controllers\AuthController();
    if ($auth->verifyOTP($_POST['otp'] ?? '')) {
        redirect('/views/auth/reset-password.php');
    } else {
        redirect('/views/auth/verify-otp.php');
    }

// ── Action: Reset Password POST ──────────────────────────────
} elseif ($action === 'reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Form validation failed. Please try again.', 'error');
        redirect('/views/auth/reset-password.php');
    }
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new \Controllers\AuthController();
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        set_flash_message('Passwords do not match.', 'error');
        redirect('/views/auth/reset-password.php');
    } elseif (strlen($newPassword) < 8) {
        set_flash_message('Password must be at least 8 characters long.', 'error');
        redirect('/views/auth/reset-password.php');
    } elseif ($auth->resetPassword($newPassword)) {
        redirect('/');
    } else {
        redirect('/views/auth/reset-password.php');
    }
    
// ── Action: Logout POST ─────────────────────────────────────
} elseif ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('/');
    }
    
    require_once __DIR__ . '/controllers/AuthController.php';
    $auth = new \Controllers\AuthController();
    $auth->logout();
    redirect('/');
}

// ── Authenticated users → Dashboard ─────────────────────────
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    $role = strtolower($_SESSION['role_name']);
    redirect("/views/{$role}/dashboard.php");
}

// ── Fetch Dynamic Data from Database ────────────────────────
$db = \Config\Database::getInstance()->getConnection();

// 1. Live Public Notices (target_role = 'ALL')
$publicNotices = [];
try {
    $stmt = $db->query("
        SELECT n.*, a.name as author 
        FROM notices n 
        LEFT JOIN admins a ON n.created_by = a.id 
        WHERE n.target_role = 'ALL' 
        ORDER BY n.is_pinned DESC, n.created_at DESC 
        LIMIT 6
    ");
    $publicNotices = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    error_log("Landing page notices query failed: " . $e->getMessage());
}

// 2. Departments with Courses Count
$departmentsList = [];
try {
    $stmt = $db->query("
        SELECT d.*, COUNT(c.id) as course_count 
        FROM departments d 
        LEFT JOIN courses c ON c.department_id = d.id 
        GROUP BY d.id 
        ORDER BY d.dept_name ASC
    ");
    $departmentsList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    error_log("Landing page departments query failed: " . $e->getMessage());
}

// ── Public Landing Page ─────────────────────────────────────
$pageTitle = 'Greenfield College — Excellence in Education & Academic Management';
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en" class="light scroll-smooth overflow-x-hidden max-w-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Welcome to Greenfield College's premier College Management System. Explore programs, campus life, admissions, and access student, faculty, and admin portals.">
    
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <style>
        html, body {
            max-width: 100vw;
            overflow-x: hidden !important;
            position: relative;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(79, 70, 229, 0.15); }
            50% { box-shadow: 0 0 40px rgba(79, 70, 229, 0.35); }
        }
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .animate-gradient { 
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite; 
        }
        .animate-pulse-glow { animation: pulseGlow 3s ease-in-out infinite; }
        
        .marquee-track {
            display: flex;
            width: max-content;
            animation: marquee 30s linear infinite;
        }
        .marquee-track:hover {
            animation-play-state: paused;
        }
        
        .portal-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .portal-card:hover {
            transform: translateY(-8px) scale(1.02);
        }
        
        .glassmorphism-landing {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glassmorphism-landing {
            background: rgba(15, 23, 42, 0.85);
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased transition-colors duration-300 overflow-x-hidden max-w-full relative">
    
    <!-- ═══════════ SCROLL PROGRESS BAR ═══════════ -->
    <div id="scroll-progress-bar" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 z-[100] transition-all duration-75 max-w-full" style="width: 0%;"></div>

    <!-- ═══════════ NAVBAR ═══════════ -->
    <nav class="fixed top-0 w-full max-w-full z-50 glassmorphism-landing border-b border-slate-200/60 dark:border-slate-800/60 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <a href="<?= $base ?>/" class="flex items-center gap-2.5 group shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/25 group-hover:shadow-indigo-500/40 transition-shadow">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">Greenfield</span>
                        <span class="text-xs block -mt-1 text-slate-500 dark:text-slate-400 font-medium tracking-wide">COLLEGE</span>
                    </div>
                </a>
                
                <!-- Desktop Navigation Links (Responsive spacing and display) -->
                <div class="hidden xl:flex items-center gap-5 2xl:gap-7">
                    <a href="#about" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">About</a>
                    <a href="#notices" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors flex items-center gap-1.5">
                        Notices
                        <span class="inline-flex w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </a>
                    <a href="#programs" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Programs</a>
                    <a href="#placements" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Placements</a>
                    <a href="#campus-life" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Campus Life</a>
                    <a href="#faq" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">FAQ</a>
                    <a href="<?= $base ?>/feedback.php" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Feedback</a>
                    <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Contact</a>
                </div>
                
                <!-- Action Controls -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <button id="theme-toggle" type="button" aria-label="Toggle Dark Mode" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2 sm:p-2.5 rounded-full transition-colors">
                        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
                        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
                    </button>
                    
                    <button type="button" onclick="openApplyModal()" class="hidden sm:inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3 sm:px-3.5 py-2 rounded-lg text-xs font-bold transition-all shadow-md shadow-emerald-500/20 hover:-translate-y-0.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Apply Now
                    </button>
                    
                    <a href="<?= $base ?>/views/auth/login.php" class="hidden md:inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 sm:px-4 py-2 rounded-lg text-xs font-bold transition-all shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
                        <i data-lucide="log-in" class="w-3.5 h-3.5"></i> Sign In
                    </a>

                    <!-- Hamburger Menu Button for < xl screens -->
                    <button id="mobile-menu-btn" type="button" class="xl:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-label="Open Navigation Menu">
                        <i id="menu-icon-open" data-lucide="menu" class="w-6 h-6"></i>
                        <i id="menu-icon-close" data-lucide="x" class="w-6 h-6 hidden"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════ MOBILE DRAWER MENU ═══════════ -->
        <div id="mobile-menu" class="hidden xl:hidden border-t border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl px-4 pt-3 pb-6 space-y-2 max-w-full overflow-hidden">
            <a href="#about" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="info" class="w-4 h-4 text-indigo-500"></i> About Greenfield
            </a>
            <a href="#notices" class="mobile-nav-link flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <span class="flex items-center gap-3"><i data-lucide="bell" class="w-4 h-4 text-emerald-500"></i> Announcements</span>
                <span class="text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 px-2 py-0.5 rounded-full">Live</span>
            </a>
            <a href="#programs" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="book-open" class="w-4 h-4 text-purple-500"></i> Programs & Departments
            </a>
            <a href="#placements" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="briefcase" class="w-4 h-4 text-amber-500"></i> Placements & Recruiters
            </a>
            <a href="#campus-life" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="image" class="w-4 h-4 text-sky-500"></i> Campus Gallery
            </a>
            <a href="#faq" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="help-circle" class="w-4 h-4 text-rose-500"></i> Frequently Asked Questions
            </a>
            <a href="<?= $base ?>/feedback.php" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="message-square" class="w-4 h-4 text-teal-500"></i> Institutional Feedback
            </a>
            <a href="#contact" class="mobile-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                <i data-lucide="phone" class="w-4 h-4 text-slate-500"></i> Contact Us
            </a>

            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-2">
                <button type="button" onclick="openApplyModal()" class="w-full inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20">
                    <i data-lucide="sparkles" class="w-4 h-4"></i> Inquire for Admission
                </button>
                <a href="<?= $base ?>/views/auth/login.php" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-indigo-500/25">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Sign In to Portals
                </a>
            </div>
        </div>
    </nav>

    <!-- ═══════════ HERO SECTION ═══════════ -->
    <section class="relative min-h-[92vh] flex items-center justify-center overflow-hidden pt-20 pb-16 w-full max-w-full">
        <!-- Ambient Glowing Background -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden max-w-full">
            <div class="absolute top-20 left-10 w-80 h-80 bg-indigo-400 rounded-full mix-blend-multiply filter blur-[90px] opacity-25 dark:opacity-15 animate-float"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-[110px] opacity-20 dark:opacity-10 animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-emerald-300 rounded-full mix-blend-multiply filter blur-[130px] opacity-15 dark:opacity-5 animate-float" style="animation-delay: 4s;"></div>
        </div>
        
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 text-center w-full">
            <div>
                <span class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-4 py-1.5 rounded-full text-xs font-bold tracking-wider uppercase border border-indigo-100 dark:border-indigo-800/60 mb-8 shadow-sm">
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    Admissions Open • Academic Session 2026-2027
                </span>
            </div>
            
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-slate-900 dark:text-white leading-[1.1] mb-6">
                Transforming Ambition into<br>
                <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-emerald-500 bg-clip-text text-transparent animate-gradient">Global Excellence</span>
            </h1>
            
            <p class="text-base sm:text-xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed font-medium">
                Welcome to Greenfield College's connected academic ecosystem. Streamlined course management, real-time attendance, and integrated portals for students, faculty, and administration.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center w-full">
                <button type="button" onclick="openApplyModal()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3.5 rounded-xl text-base font-bold transition-all shadow-xl shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-1">
                    <i data-lucide="sparkles" class="w-5 h-5"></i> Inquire for Admission
                </button>
                <a href="<?= $base ?>/views/auth/login.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-xl text-base font-bold transition-all shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-1">
                    Sign In to Portal <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
                <a href="#notices" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-7 py-3.5 rounded-xl text-base font-bold border border-slate-200 dark:border-slate-700 transition-all hover:shadow-lg hover:-translate-y-1">
                    <i data-lucide="bell" class="w-5 h-5 text-indigo-500"></i> View Notices
                </a>
            </div>
            
            <!-- Animated Stats Bar -->
            <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto w-full">
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-md rounded-2xl p-5 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                    <div class="text-3xl sm:text-4xl font-black text-indigo-600 dark:text-indigo-400 counter-stat" data-target="5200">5,000+</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Students Enrolled</div>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-md rounded-2xl p-5 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                    <div class="text-3xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 counter-stat" data-target="210">200+</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Faculty Experts</div>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-md rounded-2xl p-5 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                    <div class="text-3xl sm:text-4xl font-black text-purple-600 dark:text-purple-400 counter-stat" data-target="48">50+</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Academic Programs</div>
                </div>
                <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-md rounded-2xl p-5 border border-slate-200/60 dark:border-slate-700/60 shadow-sm hover:shadow-md transition-shadow">
                    <div class="text-3xl sm:text-4xl font-black text-rose-600 dark:text-rose-400">A+</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">NAAC Accredited</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ LIVE NOTICES BULLETIN BOARD ═══════════ -->
    <section id="notices" class="py-20 px-4 sm:px-6 bg-white dark:bg-slate-900 border-y border-slate-200/60 dark:border-slate-800/60 relative w-full max-w-full overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-2 block flex items-center gap-1.5">
                        <i data-lucide="radio" class="w-4 h-4 text-emerald-500 animate-pulse"></i> Public Circulars
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white">Notice & Announcement Board</h2>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-1 font-medium">Real-time official updates, schedules, and alerts for campus members and visitors.</p>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Updated continuously by Administration
                </div>
            </div>

            <?php if (!empty($publicNotices)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($publicNotices as $notice): 
                        $isRecent = (time() - strtotime($notice['created_at'])) < (7 * 24 * 60 * 60);
                        $formattedDate = date('M d, Y', strtotime($notice['created_at']));
                    ?>
                    <div class="bg-slate-50 dark:bg-slate-800/80 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($notice['is_pinned'])): ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-extrabold uppercase bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 px-2.5 py-0.5 rounded-full">
                                            <i data-lucide="pin" class="w-3 h-3"></i> Pinned
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($isRecent): ?>
                                        <span class="inline-flex items-center text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 px-2 py-0.5 rounded-full">
                                            New
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-medium flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> <?= $formattedDate ?>
                                </span>
                            </div>

                            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                <?= htmlspecialchars($notice['title']) ?>
                            </h3>
                            
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 line-clamp-3 leading-relaxed mb-4">
                                <?= htmlspecialchars($notice['content']) ?>
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                By <?= htmlspecialchars($notice['author'] ?? 'Admin') ?>
                            </span>
                            <button type="button" 
                                onclick='previewNotice(<?= json_encode([
                                    "title" => $notice["title"],
                                    "content" => $notice["content"],
                                    "date" => $formattedDate,
                                    "author" => $notice["author"] ?? "Administration",
                                    "attachment" => $notice["attachment_url"] ?? null
                                ]) ?>)'
                                class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                Read Notice <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                    <i data-lucide="bell-off" class="w-10 h-10 text-slate-400 mx-auto mb-3"></i>
                    <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">No public circulars published right now</h3>
                    <p class="text-xs text-slate-500 mt-1">Check back soon or sign in to your role dashboard for department-specific notices.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ═══════════ PLACEMENT HIGHLIGHTS & RECRUITERS MARQUEE ═══════════ -->
    <section id="placements" class="py-20 px-4 sm:px-6 bg-slate-50 dark:bg-slate-950 w-full max-w-full overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-14">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2 block">Career Outcomes</span>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Placement Milestones & Industry Partners</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Leading multinational technology firms and consulting organizations recruit top talent from our graduating cohorts.</p>
            </div>

            <!-- Key Placement Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-14">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                    <div class="text-3xl sm:text-4xl font-black text-emerald-600 dark:text-emerald-400 mb-1">₹45.0 LPA</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Highest International CTC</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                    <div class="text-3xl sm:text-4xl font-black text-indigo-600 dark:text-indigo-400 mb-1">₹8.6 LPA</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Average Package</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                    <div class="text-3xl sm:text-4xl font-black text-purple-600 dark:text-purple-400 mb-1">98.2%</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Placement Record</div>
                </div>
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm text-center">
                    <div class="text-3xl sm:text-4xl font-black text-rose-600 dark:text-rose-400 mb-1">350+</div>
                    <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Corporate Recruiters</div>
                </div>
            </div>

            <!-- Marquee of Top Recruiters -->
            <div class="relative w-full overflow-hidden py-4">
                <div class="absolute left-0 top-0 bottom-0 w-20 bg-gradient-to-r from-slate-50 dark:from-slate-950 to-transparent z-10 pointer-events-none"></div>
                <div class="absolute right-0 top-0 bottom-0 w-20 bg-gradient-to-l from-slate-50 dark:from-slate-950 to-transparent z-10 pointer-events-none"></div>

                <div class="marquee-track flex items-center gap-6">
                    <?php 
                    $recruiters = [
                        [
                            'name' => 'Google',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>'
                        ],
                        [
                            'name' => 'Microsoft',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><rect x="1" y="1" width="10" height="10" fill="#F25022"/><rect x="13" y="1" width="10" height="10" fill="#7FBA00"/><rect x="1" y="13" width="10" height="10" fill="#00A4EF"/><rect x="13" y="13" width="10" height="10" fill="#FFB900"/></svg>'
                        ],
                        [
                            'name' => 'Amazon',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"><path d="M13.9 14.1c-1.3 0-2.3-.4-2.8-.9l.7-1.1c.4.4 1.2.7 2.1.7 1.2 0 1.9-.6 1.9-1.5v-.3c-.5.4-1.3.6-2.1.6-1.9 0-3.3-1-3.3-2.7 0-1.8 1.4-2.8 3.5-2.8 1 0 1.7.2 2.1.4V6c0-1.4-.9-2.1-2.4-2.1-1 0-1.9.3-2.6.8l-.6-1.1c.9-.7 2.2-1 3.6-1 2.3 0 3.7 1.1 3.7 3.3v6c0 .8.1 1.6.3 2.1h-1.6c-.1-.3-.2-.7-.2-.9zm.2-4.2c1 0 1.6-.3 1.9-.7v-1.7c-.4-.2-1-.3-1.7-.3-1.3 0-2.1.6-2.1 1.6 0 .9.7 1.1 1.9 1.1z" fill="#FF9900"/><path d="M21.7 18.2C19.3 20 15.7 21 12 21c-5.2 0-9.8-2-11.7-5.1-.2-.3 0-.7.3-.8.3-.1.6 0 .8.3 1.7 2.8 6 4.5 10.6 4.5 3.3 0 6.6-.9 8.8-2.5.4-.3.9.1.9.4 0 .1-.1.2-.2.3z" fill="#FF9900"/><path d="M22.5 17.1c-.2-.3-1.6-.8-3.3-.4-.3.1-.4-.2-.1-.4 1.3-1.1 3.4-.8 3.6-.5.3.3.1 2.4-1.1 3.6-.3.2-.5.1-.3-.2.8-1.2 1.1-2.1 1.2-2.1z" fill="#FF9900"/></svg>'
                        ],
                        [
                            'name' => 'Deloitte',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><text x="0" y="17" font-family="Inter, system-ui, sans-serif" font-weight="900" font-size="14" fill="#111827" class="dark:fill-white">D</text><circle cx="16" cy="15" r="3" fill="#86BC25"/></svg>'
                        ],
                        [
                            'name' => 'TCS',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#0076CE"/><text x="12" y="16" font-family="Inter, system-ui, sans-serif" font-weight="900" font-size="8.5" fill="#ffffff" text-anchor="middle" letter-spacing="0.5">TCS</text></svg>'
                        ],
                        [
                            'name' => 'Infosys',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><rect width="24" height="24" rx="6" fill="#007CC3"/><text x="12" y="16" font-family="Inter, system-ui, sans-serif" font-weight="900" font-size="11" fill="#ffffff" text-anchor="middle">i</text></svg>'
                        ],
                        [
                            'name' => 'IBM',
                            'svg' => '<svg class="w-6 h-4 shrink-0" viewBox="0 0 40 20" fill="#1F70C1"><path d="M2 3h8v2H2zM2 7h8v2H2zM2 11h8v2H2zM2 15h8v2H2z"/><path d="M14 3h7c1.7 0 3 .7 3 2.2 0 .9-.5 1.5-1.2 1.8.9.3 1.5 1 1.5 2 0 1.6-1.3 2.3-3 2.3h-7.3v-2h7c.8 0 1.3-.2 1.3-.8s-.5-.7-1.3-.7h-7V7h7c.8 0 1.3-.2 1.3-.8s-.5-.7-1.3-.7h-7V3z"/><path d="M28 3h3l3 7 3-7h3v14h-2.5V8.5L34.8 15h-1.6L30.5 8.5V17H28V3z"/></svg>'
                        ],
                        [
                            'name' => 'Oracle',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="6" stroke="#F80000" stroke-width="3"/></svg>'
                        ],
                        [
                            'name' => 'Accenture',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"><path d="M10 5l8 7-8 7" stroke="#A100FF" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                        ],
                        [
                            'name' => 'Wipro',
                            'svg' => '<svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="4.5" r="2" fill="#E03A3E"/><circle cx="17.5" cy="7.5" r="2" fill="#F6821F"/><circle cx="19.5" cy="13.5" r="2" fill="#00965E"/><circle cx="16" cy="18.5" r="2" fill="#00A3E0"/><circle cx="8" cy="18.5" r="2" fill="#005A9C"/><circle cx="4.5" cy="13.5" r="2" fill="#782F8F"/><circle cx="6.5" cy="7.5" r="2" fill="#E03A3E"/><circle cx="12" cy="12" r="2.8" fill="#1D252D"/></svg>'
                        ]
                    ];
                    // Duplicate for smooth seamless loop
                    $loopList = array_merge($recruiters, $recruiters);
                    foreach ($loopList as $r):
                    ?>
                    <div class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all shrink-0">
                        <div class="w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-700/50 flex items-center justify-center p-1.5 shadow-inner">
                            <?= $r['svg'] ?>
                        </div>
                        <span class="font-bold text-sm text-slate-800 dark:text-slate-200 whitespace-nowrap"><?= $r['name'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ ABOUT SECTION ═══════════ -->
    <section id="about" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 relative w-full max-w-full overflow-hidden transition-colors duration-300">
        <!-- Ambient decorative background glow for modern depth -->
        <div class="absolute top-1/4 -right-48 w-96 h-96 bg-indigo-500/[0.04] dark:bg-indigo-500/[0.07] rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-10 -left-48 w-96 h-96 bg-purple-500/[0.04] dark:bg-purple-500/[0.07] rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="max-w-6xl mx-auto relative z-10">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-2 block">Institutional Heritage</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">About Greenfield College</h2>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">A pioneering sanctuary for technical mastery, creative innovation, and ethical leadership.</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                        Greenfield College, established in 1993, is an autonomous higher education institution dedicated to multidisciplinary excellence. Spread across 120 acres of high-speed Wi-Fi enabled green campus, our facilities nurture cutting-edge research and comprehensive student development.
                    </p>
                    <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                        Recognized with NAAC A+ accreditation and active academic partnerships with leading global institutions, we equip students with real-world technical prowess, critical thinking, and leadership acumen.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="award" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">NAAC A+ Accredited</h4>
                                <p class="text-xs text-slate-500">Highest quality tier</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="globe" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Global Ties</h4>
                                <p class="text-xs text-slate-500">30+ partner institutions</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="microscope" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Applied Research</h4>
                                <p class="text-xs text-slate-500">500+ research papers</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="briefcase" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Career Acceleration</h4>
                                <p class="text-xs text-slate-500">Industry-led internships</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative group">
                    <!-- Ambient outer glow behind card (reactive to theme) -->
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-emerald-500/10 dark:from-indigo-500/20 dark:via-purple-500/20 dark:to-emerald-500/20 rounded-[2rem] blur-xl opacity-75 group-hover:opacity-100 transition duration-500 pointer-events-none"></div>

                    <!-- Modern Vision Card (adaptive light & dark theme) -->
                    <div class="relative bg-gradient-to-br from-indigo-50/70 via-white to-purple-50/50 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950 rounded-3xl p-8 sm:p-10 border border-indigo-100/90 dark:border-slate-800/80 shadow-2xl shadow-indigo-500/10 dark:shadow-slate-950/50 overflow-hidden transition-colors duration-300">
                        <!-- Top subtle glass border highlight -->
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-300/50 dark:via-indigo-400/40 to-transparent"></div>
                        
                        <!-- Ambient inner aurora blur orbs -->
                        <div class="absolute -top-16 -right-16 w-56 h-56 bg-indigo-300/25 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-16 -left-16 w-56 h-56 bg-emerald-300/20 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
                        
                        <!-- Glowing Badge -->
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-500/15 border border-indigo-200/80 dark:border-indigo-400/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-6 backdrop-blur-md shadow-sm dark:shadow-none">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                            <span class="tracking-wide uppercase text-[11px] font-bold">Guiding North Star</span>
                        </div>
                        
                        <h3 class="text-2xl sm:text-3xl font-black tracking-tight mb-3 text-slate-900 dark:text-white">Institutional Vision</h3>
                        <p class="text-slate-600 dark:text-slate-300 leading-relaxed font-normal text-sm sm:text-base">
                            To stand as a beacon of academic eminence and ethical discovery, empowering curious minds to solve grand societal challenges, engineer future technologies, and foster sustainable global progress.
                        </p>
                        
                        <!-- High-contrast glass stat counters -->
                        <div class="mt-8 pt-6 border-t border-slate-200/80 dark:border-slate-800 grid grid-cols-3 gap-3 sm:gap-4 text-center">
                            <div class="bg-white/80 hover:bg-white dark:bg-white/[0.04] dark:hover:bg-white/[0.08] transition-all rounded-2xl p-3 sm:p-4 border border-indigo-100/80 dark:border-white/[0.06] shadow-sm dark:shadow-none">
                                <div class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-200 dark:via-white dark:to-purple-200 bg-clip-text text-transparent">30+</div>
                                <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold mt-1 uppercase tracking-wider">Glorious Years</div>
                            </div>
                            <div class="bg-white/80 hover:bg-white dark:bg-white/[0.04] dark:hover:bg-white/[0.08] transition-all rounded-2xl p-3 sm:p-4 border border-indigo-100/80 dark:border-white/[0.06] shadow-sm dark:shadow-none">
                                <div class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-200 dark:via-white dark:to-teal-200 bg-clip-text text-transparent">120</div>
                                <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold mt-1 uppercase tracking-wider">Acre Campus</div>
                            </div>
                            <div class="bg-white/80 hover:bg-white dark:bg-white/[0.04] dark:hover:bg-white/[0.08] transition-all rounded-2xl p-3 sm:p-4 border border-indigo-100/80 dark:border-white/[0.06] shadow-sm dark:shadow-none">
                                <div class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-purple-600 to-indigo-600 dark:from-purple-200 dark:via-white dark:to-indigo-200 bg-clip-text text-transparent">50K+</div>
                                <div class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 font-semibold mt-1 uppercase tracking-wider">Proud Alumni</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ DYNAMIC PROGRAMS & DEPARTMENT EXPLORER ═══════════ -->
    <section id="programs" class="py-24 px-4 sm:px-6 bg-slate-50 dark:bg-slate-950 w-full max-w-full overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2 block">Academics</span>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Academic Streams & Departments</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Explore industry-aligned degree curricula configured dynamically within our institution.</p>
            </div>

            <!-- Program Cards Display -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                if (!empty($departmentsList)):
                    $deptColors = ['indigo', 'emerald', 'purple', 'amber', 'rose', 'sky'];
                    $icons = ['cpu', 'zap', 'monitor', 'briefcase', 'database', 'layers'];
                    $idx = 0;
                    foreach ($departmentsList as $dept):
                        $c = $deptColors[$idx % count($deptColors)];
                        $icon = $icons[$idx % count($icons)];
                        $idx++;
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-<?= $c ?>-50 dark:bg-<?= $c ?>-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i data-lucide="<?= $icon ?>" class="w-6 h-6 text-<?= $c ?>-600 dark:text-<?= $c ?>-400"></i>
                            </div>
                            <span class="text-xs font-black uppercase px-2.5 py-1 rounded-md bg-<?= $c ?>-50 text-<?= $c ?>-700 dark:bg-<?= $c ?>-900/40 dark:text-<?= $c ?>-300 border border-<?= $c ?>-200/50 dark:border-<?= $c ?>-800/50">
                                <?= htmlspecialchars($dept['dept_code']) ?>
                            </span>
                        </div>
                        
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-primary transition-colors">
                            <?= htmlspecialchars($dept['dept_name']) ?>
                        </h3>
                        
                        <div class="space-y-1.5 mb-6 text-xs text-slate-500 dark:text-slate-400 font-medium">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i> 
                                <?= htmlspecialchars((string)($dept['total_semesters'] ?? 8)) ?> Academic Semesters
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="book-check" class="w-3.5 h-3.5 text-slate-400"></i> 
                                <?= htmlspecialchars((string)($dept['course_count'] ?? 0)) ?> Courses Configured
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
                        <button type="button" 
                            onclick="openApplyModal('<?= htmlspecialchars($dept['dept_name']) ?>', <?= (int)$dept['id'] ?>)" 
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition-colors">
                            Inquire for Dept <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </button>
                        <span class="text-[11px] font-semibold text-slate-400">Full Time</span>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <div class="col-span-full text-center py-12 text-slate-500">
                        No departments found in the system.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ STUDENT & ALUMNI TESTIMONIALS ═══════════ -->
    <section id="testimonials" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 border-t border-slate-200/60 dark:border-slate-800/60 w-full max-w-full overflow-hidden">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-14">
                <span class="text-xs font-bold uppercase tracking-widest text-purple-600 dark:text-purple-400 mb-2 block">Voices of Greenfield</span>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Student & Alumni Experiences</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Discover how Greenfield College creates pathways from learning to real-world corporate leadership.</p>
            </div>

            <!-- Testimonials Slider Wrapper -->
            <div class="relative bg-slate-50 dark:bg-slate-800/60 rounded-3xl p-6 sm:p-12 border border-slate-200 dark:border-slate-700/80 shadow-lg">
                <div class="testimonial-slide active">
                    <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=250&q=80" alt="Priya Sharma" class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover shadow-md border-2 border-indigo-500/30 shrink-0">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-2">
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                            </div>
                            <p class="text-base sm:text-lg text-slate-700 dark:text-slate-200 font-medium italic mb-4 leading-relaxed">
                                "The modern computing labs and faculty mentorship at Greenfield allowed me to crack coding hackathons and land a dream software engineering role at Google. The portal made managing attendance and course notes effortless!"
                            </p>
                            <div>
                                <h4 class="font-extrabold text-base text-slate-900 dark:text-white">Priya Sharma</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">B.Tech Computer Science (Batch of 2024) • Software Engineer, Google</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-slide hidden">
                    <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=250&q=80" alt="Rohan Verma" class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover shadow-md border-2 border-emerald-500/30 shrink-0">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-2">
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                            </div>
                            <p class="text-base sm:text-lg text-slate-700 dark:text-slate-200 font-medium italic mb-4 leading-relaxed">
                                "The management curriculum combined analytical case studies with live boardroom simulations. When Deloitte conducted interviews on campus, the preparation I received here set me apart from everyone else."
                            </p>
                            <div>
                                <h4 class="font-extrabold text-base text-slate-900 dark:text-white">Rohan Verma</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">BBA Graduate (Batch of 2023) • Strategy Analyst, Deloitte</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-slide hidden">
                    <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=250&q=80" alt="Ananya Sen" class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover shadow-md border-2 border-purple-500/30 shrink-0">
                        <div>
                            <div class="flex items-center gap-1 text-amber-400 mb-2">
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                                <i data-lucide="star" class="w-4 h-4 fill-current"></i>
                            </div>
                            <p class="text-base sm:text-lg text-slate-700 dark:text-slate-200 font-medium italic mb-4 leading-relaxed">
                                "Greenfield is not just an academic institution—it is a launchpad. From organizing our cultural fest to working in high-voltage engineering labs, every semester taught me resilience and leadership."
                            </p>
                            <div>
                                <h4 class="font-extrabold text-base text-slate-900 dark:text-white">Ananya Sen</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Electrical Engineering (Batch of 2025) • Student Council Lead</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carousel Controls -->
                <div class="flex items-center justify-end gap-3 mt-6 sm:mt-2">
                    <button type="button" id="prev-test" class="p-2.5 rounded-full bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors shadow-sm" aria-label="Previous Testimonial">
                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                    </button>
                    <button type="button" id="next-test" class="p-2.5 rounded-full bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors shadow-sm" aria-label="Next Testimonial">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ CAMPUS LIFE & INFRASTRUCTURE GALLERY ═══════════ -->
    <section id="campus-life" class="py-24 px-4 sm:px-6 bg-slate-50 dark:bg-slate-950 w-full max-w-full overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-sky-600 dark:text-sky-400 mb-2 block">Environment</span>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Campus Life & World-Class Facilities</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Experience an inspiring, technology-integrated environment engineered for holistic growth.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $galleryItems = [
                    [
                        'title' => 'High-Performance Computing Lab',
                        'desc' => 'Equipped with dedicated GPU workstations, enterprise cloud racks, and Gigabit fiber lines.',
                        'img' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Central Multi-storey Library',
                        'desc' => 'Over 120,000 physical volumes, quiet study pods, and 24/7 access to IEEE/Springer digital journals.',
                        'img' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Olympic-Standard Sports Arena',
                        'desc' => 'Floodlit basketball courts, indoor badminton arena, FIFA-grade turf, and Olympic swimming complex.',
                        'img' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Smart Interactive Classrooms',
                        'desc' => 'Acoustically tuned lecture amphitheatres featuring automated session recording and smart displays.',
                        'img' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => '120-Acre Eco-Friendly Campus',
                        'desc' => 'Zero-carbon footprint, solar-powered facilities, shaded green walkways, and open amphitheaters.',
                        'img' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Student Innovation & Robotics Hub',
                        'desc' => 'Makerspace equipped with 3D printers, IoT testbeds, and drone testing cages for student founders.',
                        'img' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=800&q=80',
                    ],
                ];
                foreach ($galleryItems as $item):
                ?>
                <div class="group relative rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 bg-slate-900 cursor-pointer"
                     onclick="openGalleryModal('<?= htmlspecialchars($item['title']) ?>', '<?= htmlspecialchars($item['desc']) ?>', '<?= $item['img'] ?>')">
                    <img src="<?= $item['img'] ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent flex flex-col justify-end p-6">
                        <span class="text-xs font-bold uppercase text-indigo-400 mb-1 flex items-center gap-1">
                            <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i> Tap to View
                        </span>
                        <h3 class="text-lg font-bold text-white mb-1"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="text-xs text-slate-300 line-clamp-2"><?= htmlspecialchars($item['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ INTERACTIVE FAQ ACCORDION ═══════════ -->
    <section id="faq" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 border-t border-slate-200/60 dark:border-slate-800/60 w-full max-w-full overflow-hidden">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-2 block">Got Questions?</span>
                <h2 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Frequently Asked Questions</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto font-medium">Find instant answers regarding admissions, portal access, campus rules, and academic policies.</p>
            </div>

            <div class="space-y-4">
                <?php
                $faqs = [
                    [
                        'q' => 'How can I apply for admission at Greenfield College for 2026-27?',
                        'a' => 'You can click the "Inquire for Admission" button right on this page to submit an initial inquiry form. Our admissions counselor will contact you within 24 business hours to guide you through document verification, entrance criteria, and seat allocation.'
                    ],
                    [
                        'q' => 'Are merit scholarships and financial aid available?',
                        'a' => 'Yes, Greenfield College provides merit-based tuition waivers up to 100% for students scoring in the top 5th percentile of national entrance examinations, as well as sports and economically backward category grants.'
                    ],
                    [
                        'q' => 'How do registered students and faculty sign in to their portals?',
                        'a' => 'Click the "Sign In" button in the navigation bar to access the dedicated Portal Gateway, choose your designated portal (Student, Faculty, or Admin), and enter your registered credentials.'
                    ],
                    [
                        'q' => 'What residential hostel and cafeteria facilities are on campus?',
                        'a' => 'We offer fully furnished separate air-conditioned and regular hostels for male and female students with 24/7 security, high-speed Wi-Fi, laundry amenities, and hygienic multi-cuisine dining facilities.'
                    ],
                    [
                        'q' => 'How does the automated attendance and feedback system work?',
                        'a' => 'Faculty members mark attendance per lecture period directly through the Faculty Portal. Students can track their attendance percentage in real-time, and both students and visitors can submit institutional feedback via our dedicated Feedback portal.'
                    ]
                ];
                $fIdx = 0;
                foreach ($faqs as $faq):
                    $fIdx++;
                ?>
                <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden transition-colors">
                    <button type="button" class="faq-toggle w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-slate-100/60 dark:hover:bg-slate-800 transition-colors">
                        <span class="font-bold text-sm sm:text-base text-slate-900 dark:text-white"><?= htmlspecialchars($faq['q']) ?></span>
                        <i data-lucide="chevron-down" class="faq-icon w-5 h-5 text-slate-400 transition-transform duration-300 shrink-0"></i>
                    </button>
                    <div class="faq-content hidden px-6 py-4 text-sm text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 leading-relaxed font-normal">
                        <?= htmlspecialchars($faq['a']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTACT SECTION ═══════════ -->
    <section id="contact" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 w-full max-w-full overflow-hidden">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-rose-600 dark:text-rose-400 mb-2 block">Reach Us</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Contact & Admissions Information</h2>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto font-medium">Have inquiries regarding courses, campus visits, or institutional partnerships? Reach out to us directly.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="map-pin" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Campus Address</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">123 Academic Lane, Knowledge City, 560001</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="phone" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Admissions Phone</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">+91 80 1234 5678</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="mail" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">General Email</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">info@greenfield.edu</p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="clock" class="w-6 h-6 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Office Hours</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Mon - Sat: 9:00 AM - 5:00 PM</p>
                </div>
            </div>

            <!-- Feedback CTA Banner -->
            <div class="mt-12 bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-indigo-500/10 rounded-2xl p-8 border border-indigo-200/50 dark:border-indigo-800/40 flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Have Suggestions or Opinions?</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">We welcome constructive feedback from students, faculty, alumni, and campus visitors anytime.</p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <button type="button" onclick="openApplyModal()" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 hover:-translate-y-0.5 transition-all">
                        <i data-lucide="sparkles" class="w-4 h-4"></i> Apply Online
                    </button>
                    <a href="<?= $base ?>/feedback.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all">
                        <i data-lucide="message-square-heart" class="w-4 h-4"></i> Give Feedback
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ "APPLY NOW" ADMISSION INQUIRY MODAL ═══════════ -->
    <div id="applyModal" class="fixed inset-0 z-[110] hidden overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-2xl transition-all">
            <button type="button" onclick="closeApplyModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="mb-6">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1 block">Admissions 2026-27</span>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Inquire for Admission</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Fill out your academic background and our admissions desk will connect with you.</p>
            </div>

            <form action="<?= $base ?>/?action=inquiry" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" required placeholder="John Doe" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Email Address *</label>
                        <input type="email" name="email" required placeholder="john@example.com" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Phone Number *</label>
                        <input type="tel" name="phone" required placeholder="+91 9876543210" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Interested Department</label>
                        <select name="department_id" id="modal_dept_select" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                            <option value="">Select Department...</option>
                            <?php foreach ($departmentsList as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['dept_name']) ?> (<?= htmlspecialchars($d['dept_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Prior Qualification</label>
                        <input type="text" name="previous_qualification" placeholder="e.g. 12th Standard / Diploma" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Inquiry Message / Questions (Optional)</label>
                    <textarea name="message" rows="3" placeholder="Tell us about your questions regarding fee, scholarships, or entrance eligibility..." class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 dark:text-white outline-none"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/25 transition-all">
                        Submit Admission Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════ NOTICE PREVIEW MODAL ═══════════ -->
    <div id="noticeModal" class="fixed inset-0 z-[110] hidden overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-2xl transition-all">
            <button type="button" onclick="closeNoticeModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                    <i data-lucide="bell" class="w-3.5 h-3.5"></i> Official Circular
                </span>
                <span id="notice-modal-date" class="text-xs text-slate-400 font-medium"></span>
            </div>

            <h3 id="notice-modal-title" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mb-4 pr-8"></h3>
            
            <div id="notice-modal-content" class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed max-h-72 overflow-y-auto pr-2 mb-6 whitespace-pre-line"></div>

            <div id="notice-modal-attachment" class="hidden mb-6 p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <i data-lucide="paperclip" class="w-4 h-4 text-indigo-500"></i> Download Attached Document
                </div>
                <a id="notice-modal-attachment-link" href="#" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                    View File <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <span id="notice-modal-author" class="text-xs text-slate-500 font-medium"></span>
                <button type="button" onclick="closeNoticeModal()" class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════ CAMPUS GALLERY LIGHTBOX MODAL ═══════════ -->
    <div id="galleryModal" class="fixed inset-0 z-[110] hidden overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full overflow-hidden border border-slate-200 dark:border-slate-800 shadow-2xl">
            <button type="button" onclick="closeGalleryModal()" class="absolute top-4 right-4 z-10 text-white bg-slate-950/60 hover:bg-slate-950 p-2 rounded-full transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <img id="gallery-modal-img" src="" alt="Campus Facility" class="w-full max-h-[60vh] object-cover">
            <div class="p-6">
                <h3 id="gallery-modal-title" class="text-xl font-bold text-slate-900 dark:text-white mb-2"></h3>
                <p id="gallery-modal-desc" class="text-sm text-slate-600 dark:text-slate-400"></p>
            </div>
        </div>
    </div>

    <!-- ═══════════ FLOATING QUICK-ACTION / SUPPORT WIDGET ═══════════ -->
    <div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-3">
        <!-- Floating Support Concierge Card -->
        <div id="floating-actions" class="hidden flex-col w-[calc(100vw-3rem)] sm:w-84 max-w-xs sm:max-w-sm bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-2xl p-5 mb-1 transition-all duration-300">
            <!-- Concierge Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                        <i data-lucide="headset" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Campus Support Desk</h4>
                        <div class="flex items-center gap-1.5 text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online • Admissions Active
                        </div>
                    </div>
                </div>
                <button type="button" id="close-support-card" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Action Options -->
            <div class="space-y-2 pt-1">
                <a href="https://api.whatsapp.com/send?phone=918012345678&text=Hi%2C%20I%20would%20like%20to%20inquire%20about%20admissions%20at%20Greenfield%20College." target="_blank" 
                   class="flex items-center gap-3 p-3 rounded-2xl bg-emerald-50/70 hover:bg-emerald-100/80 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/40 border border-emerald-100 dark:border-emerald-800/40 transition-all group">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-xs text-slate-900 dark:text-white">WhatsApp Admissions</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Direct chat with counselors</div>
                    </div>
                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 transition-colors"></i>
                </a>

                <button type="button" onclick="openApplyModal()" 
                        class="w-full flex items-center gap-3 p-3 rounded-2xl bg-purple-50/70 hover:bg-purple-100/80 dark:bg-purple-950/30 dark:hover:bg-purple-900/40 border border-purple-100 dark:border-purple-800/40 text-left transition-all group">
                    <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-xs text-slate-900 dark:text-white">Apply for Admission</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Session 2026-27 inquiries</div>
                    </div>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-purple-600 transition-colors"></i>
                </button>

                <a href="tel:+918012345678" 
                   class="flex items-center gap-3 p-3 rounded-2xl bg-sky-50/70 hover:bg-sky-100/80 dark:bg-sky-950/30 dark:hover:bg-sky-900/40 border border-sky-100 dark:border-sky-800/40 transition-all group">
                    <div class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i data-lucide="phone-call" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-xs text-slate-900 dark:text-white">+91 80 1234 5678</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Admissions helpline desk</div>
                    </div>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-sky-600 transition-colors"></i>
                </a>

                <a href="<?= $base ?>/feedback.php" 
                   class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/60 dark:hover:bg-slate-800 border border-slate-200/80 dark:border-slate-700/60 transition-all group">
                    <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                        <i data-lucide="message-square-heart" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-xs text-slate-900 dark:text-white">Give Feedback</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Share suggestions or opinions</div>
                    </div>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition-colors"></i>
                </a>
            </div>

            <!-- Quick FAQ Link -->
            <div class="pt-2 text-center border-t border-slate-100 dark:border-slate-800">
                <a href="#faq" class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                    Have questions? Check our FAQ <i data-lucide="help-circle" class="w-3 h-3"></i>
                </a>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Back to top button -->
            <button id="back-to-top" type="button" aria-label="Back to Top" class="hidden w-12 h-12 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-full shadow-xl border border-slate-200 dark:border-slate-700 items-center justify-center hover:bg-slate-50 dark:hover:bg-slate-700 hover:-translate-y-1 transition-all">
                <i data-lucide="arrow-up" class="w-5 h-5"></i>
            </button>

            <!-- Main Support Launcher -->
            <button id="support-widget-btn" type="button" aria-label="Campus Support Desk" title="Campus Support Desk"
                    class="relative w-14 h-14 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 text-white rounded-full shadow-2xl hover:shadow-indigo-500/50 flex items-center justify-center hover:scale-110 active:scale-95 transition-all">
                <span class="absolute top-0 right-0 flex h-3.5 w-3.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900"></span>
                </span>
                <i data-lucide="headset" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <!-- ═══════════ FOOTER ═══════════ -->
    <?php require_once __DIR__ . '/includes/main_footer.php'; ?>

    <!-- ═══════════ SCRIPTS ═══════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            
            // ── 1. Reading Progress Bar & Back to Top ─────────────
            const progressBar = document.getElementById('scroll-progress-bar');
            const backToTopBtn = document.getElementById('back-to-top');

            window.addEventListener('scroll', () => {
                const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
                const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const scrolled = (height > 0) ? (winScroll / height) * 100 : 0;
                if (progressBar) progressBar.style.width = scrolled + '%';

                if (backToTopBtn) {
                    if (winScroll > 400) {
                        backToTopBtn.classList.remove('hidden');
                        backToTopBtn.classList.add('flex');
                    } else {
                        backToTopBtn.classList.add('hidden');
                        backToTopBtn.classList.remove('flex');
                    }
                }
            });

            if (backToTopBtn) {
                backToTopBtn.addEventListener('click', () => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            // ── 2. Mobile Menu Toggle ──────────────────────────────
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const menuOpenIcon = document.getElementById('menu-icon-open');
            const menuCloseIcon = document.getElementById('menu-icon-close');

            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', () => {
                    const isExpanded = !mobileMenu.classList.contains('hidden');
                    if (isExpanded) {
                        mobileMenu.classList.add('hidden');
                        menuOpenIcon.classList.remove('hidden');
                        menuCloseIcon.classList.add('hidden');
                    } else {
                        mobileMenu.classList.remove('hidden');
                        menuOpenIcon.classList.add('hidden');
                        menuCloseIcon.classList.remove('hidden');
                    }
                });

                document.querySelectorAll('.mobile-nav-link').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileMenu.classList.add('hidden');
                        menuOpenIcon.classList.remove('hidden');
                        menuCloseIcon.classList.add('hidden');
                    });
                });
            }

            // ── 3. Theme Toggle ────────────────────────────────────
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

            // ── 4. Stats Number Counter Animation ─────────────────
            const counterObserver = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const targetNum = parseInt(target.getAttribute('data-target') || '0', 10);
                        if (targetNum > 0) {
                            let current = 0;
                            const step = Math.max(1, Math.floor(targetNum / 50));
                            const timer = setInterval(() => {
                                current += step;
                                if (current >= targetNum) {
                                    target.textContent = targetNum.toLocaleString() + '+';
                                    clearInterval(timer);
                                } else {
                                    target.textContent = current.toLocaleString() + '+';
                                }
                            }, 25);
                        }
                        obs.unobserve(target);
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('.counter-stat').forEach(el => counterObserver.observe(el));

            // ── 5. Testimonials Carousel ───────────────────────────
            const slides = document.querySelectorAll('.testimonial-slide');
            let currentSlide = 0;

            const showSlide = (idx) => {
                slides.forEach((s, i) => {
                    if (i === idx) {
                        s.classList.remove('hidden');
                    } else {
                        s.classList.add('hidden');
                    }
                });
            };

            const prevBtn = document.getElementById('prev-test');
            const nextBtn = document.getElementById('next-test');

            if (prevBtn && nextBtn && slides.length > 0) {
                prevBtn.addEventListener('click', () => {
                    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                    showSlide(currentSlide);
                });
                nextBtn.addEventListener('click', () => {
                    currentSlide = (currentSlide + 1) % slides.length;
                    showSlide(currentSlide);
                });
            }

            // ── 6. FAQ Accordion ───────────────────────────────────
            document.querySelectorAll('.faq-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const content = btn.nextElementSibling;
                    const icon = btn.querySelector('.faq-icon');
                    const isHidden = content.classList.contains('hidden');

                    // Close all other FAQs
                    document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
                    document.querySelectorAll('.faq-icon').forEach(ic => ic.classList.remove('rotate-180'));

                    if (isHidden) {
                        content.classList.remove('hidden');
                        if (icon) icon.classList.add('rotate-180');
                    }
                });
            });

            // ── 7. Floating Support Concierge Launcher ────────────
            const supportWidgetBtn = document.getElementById('support-widget-btn');
            const floatingActions = document.getElementById('floating-actions');
            const closeSupportCard = document.getElementById('close-support-card');

            const openSupportCard = () => {
                if (!floatingActions) return;
                floatingActions.classList.remove('hidden');
                floatingActions.classList.add('flex');
            };

            const closeSupportMenu = () => {
                if (!floatingActions) return;
                floatingActions.classList.add('hidden');
                floatingActions.classList.remove('flex');
            };

            if (supportWidgetBtn) {
                supportWidgetBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (!floatingActions) return;
                    if (floatingActions.classList.contains('hidden')) {
                        openSupportCard();
                    } else {
                        closeSupportMenu();
                    }
                });
            }

            if (closeSupportCard) {
                closeSupportCard.addEventListener('click', (e) => {
                    e.stopPropagation();
                    closeSupportMenu();
                });
            }

            // Close support popover when clicking anywhere outside
            document.addEventListener('click', (e) => {
                if (floatingActions && !floatingActions.classList.contains('hidden')) {
                    if (!floatingActions.contains(e.target) && !supportWidgetBtn.contains(e.target)) {
                        closeSupportMenu();
                    }
                }
            });

            // ── 8. Flash Messages Alert ────────────────────────────
            <?php if (isset($_SESSION['flash_message'])): ?>
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false,
                    timer: 4000, timerProgressBar: true
                });
                Toast.fire({
                    icon: <?= json_encode($_SESSION['flash_message']['type']) ?>,
                    title: <?= json_encode($_SESSION['flash_message']['message']) ?>
                });
            <?php endif; unset($_SESSION['flash_message']); ?>
        });

        // ── Modal Handlers (Global Scope) ──────────────────────────
        function openApplyModal(deptName = '', deptId = '') {
            const modal = document.getElementById('applyModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                if (deptId) {
                    const select = document.getElementById('modal_dept_select');
                    if (select) select.value = deptId;
                }
            }
        }

        function closeApplyModal() {
            const modal = document.getElementById('applyModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        if (window.location.hash === '#applyModal') {
            openApplyModal();
        }

        function previewNotice(notice) {
            const modal = document.getElementById('noticeModal');
            if (!modal) return;

            document.getElementById('notice-modal-title').textContent = notice.title || 'Official Notice';
            document.getElementById('notice-modal-content').textContent = notice.content || '';
            document.getElementById('notice-modal-date').textContent = notice.date || '';
            document.getElementById('notice-modal-author').textContent = 'Published by ' + (notice.author || 'Administration');

            const attachBox = document.getElementById('notice-modal-attachment');
            const attachLink = document.getElementById('notice-modal-attachment-link');

            if (notice.attachment) {
                attachBox.classList.remove('hidden');
                attachLink.href = '<?= $base ?>/uploads/notices/' + encodeURIComponent(notice.attachment);
            } else {
                attachBox.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function closeNoticeModal() {
            const modal = document.getElementById('noticeModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function openGalleryModal(title, desc, imgUrl) {
            const modal = document.getElementById('galleryModal');
            if (!modal) return;
            document.getElementById('gallery-modal-img').src = imgUrl;
            document.getElementById('gallery-modal-title').textContent = title;
            document.getElementById('gallery-modal-desc').textContent = desc;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeGalleryModal() {
            const modal = document.getElementById('galleryModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        // Close modals on backdrop click or ESC key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeApplyModal();
                closeNoticeModal();
                closeGalleryModal();
            }
        });

        document.querySelectorAll('#applyModal, #noticeModal, #galleryModal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeApplyModal();
                    closeNoticeModal();
                    closeGalleryModal();
                }
            });
        });
    </script>
</body>
</html>
