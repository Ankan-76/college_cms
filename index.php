<?php
// index.php — Landing Page + Auth Router
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';

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

// ── Public Landing Page ─────────────────────────────────────
$pageTitle = 'Greenfield College — College Management System';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Welcome to Greenfield College's College Management System. Access student, faculty, and admin portals for academic management.">
    
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
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(79, 70, 229, 0.15); }
            50% { box-shadow: 0 0 40px rgba(79, 70, 229, 0.3); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .animate-slide-in-left { animation: slideInLeft 0.6s ease-out forwards; }
        .animate-gradient { 
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite; 
        }
        .animate-pulse-glow { animation: pulse-glow 3s ease-in-out infinite; }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .stagger { opacity: 0; }
        
        .portal-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .portal-card:hover {
            transform: translateY(-8px) scale(1.02);
        }
        
        .glassmorphism-landing {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glassmorphism-landing {
            background: rgba(15, 23, 42, 0.8);
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased transition-colors duration-300">
    
    <!-- ═══════════ NAVBAR ═══════════ -->
    <nav class="fixed top-0 w-full z-50 glassmorphism-landing border-b border-slate-200/50 dark:border-slate-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?= $base ?>/" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/25 group-hover:shadow-indigo-500/40 transition-shadow">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">Greenfield</span>
                        <span class="text-xs block -mt-1 text-slate-500 dark:text-slate-400 font-medium tracking-wide">COLLEGE</span>
                    </div>
                </a>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#about" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">About</a>
                    <a href="#programs" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Programs</a>
                    <a href="#portals" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Portals</a>
                    <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Contact</a>
                </div>
                
                <div class="flex items-center gap-3">
                    <button id="theme-toggle" type="button" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2.5 rounded-full transition-colors">
                        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
                        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
                    </button>
                    <a href="#portals" class="hidden sm:inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══════════ HERO SECTION ═══════════ -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
        <!-- Ambient Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-[80px] opacity-20 dark:opacity-10 animate-float"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-400 rounded-full mix-blend-multiply filter blur-[100px] opacity-15 dark:opacity-10 animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-emerald-300 rounded-full mix-blend-multiply filter blur-[120px] opacity-10 dark:opacity-5 animate-float" style="animation-delay: 4s;"></div>
        </div>
        
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 text-center">
            <div class="stagger animate-fade-in-up">
                <span class="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-4 py-1.5 rounded-full text-xs font-bold tracking-wider uppercase border border-indigo-100 dark:border-indigo-800/50 mb-8">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                    Academic Session 2026-2027
                </span>
            </div>
            
            <h1 class="stagger animate-fade-in-up delay-100 text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-slate-900 dark:text-white leading-[1.1] mb-6">
                Welcome to<br>
                <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent animate-gradient">Greenfield College</span>
            </h1>
            
            <p class="stagger animate-fade-in-up delay-200 text-lg sm:text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed font-medium">
                Empowering minds through excellence in education. Access your personalized academic portal to manage courses, attendance, and institutional resources.
            </p>
            
            <div class="stagger animate-fade-in-up delay-300 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#portals" class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-xl text-base font-bold transition-all shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-1">
                    Access Your Portal <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
                <a href="#about" class="inline-flex items-center justify-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-8 py-3.5 rounded-xl text-base font-bold border border-slate-200 dark:border-slate-700 transition-all hover:shadow-lg hover:-translate-y-1">
                    <i data-lucide="info" class="w-5 h-5"></i> Learn More
                </a>
            </div>
            
            <!-- Stats Bar -->
            <div class="stagger animate-fade-in-up delay-400 mt-16 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto">
                <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-slate-200/50 dark:border-slate-700/50">
                    <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400">5K+</div>
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Students</div>
                </div>
                <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-slate-200/50 dark:border-slate-700/50">
                    <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">200+</div>
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Faculty</div>
                </div>
                <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-slate-200/50 dark:border-slate-700/50">
                    <div class="text-3xl font-black text-purple-600 dark:text-purple-400">50+</div>
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">Programs</div>
                </div>
                <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-slate-200/50 dark:border-slate-700/50">
                    <div class="text-3xl font-black text-rose-600 dark:text-rose-400">A+</div>
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mt-1">NAAC Grade</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ ABOUT SECTION ═══════════ -->
    <section id="about" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 relative">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mb-2 block">About Us</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">About the College</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">A legacy of academic excellence spanning over three decades.</p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                        Greenfield College, established in 1993, is a premier institution committed to fostering innovation, research, and holistic development. Our campus spans 120 acres of green, technology-enabled infrastructure designed for next-generation learning.
                    </p>
                    <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                        With NAAC A+ accreditation and partnerships with global institutions, we provide students with world-class education and industry-ready skills. Our alumni network spans over 50,000 professionals across the globe.
                    </p>
                    
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="award" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">NAAC A+ Accredited</h4>
                                <p class="text-xs text-slate-500">Highest quality standard</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="globe" class="w-5 h-5 text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Global Partnerships</h4>
                                <p class="text-xs text-slate-500">30+ partner universities</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="microscope" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Research Excellence</h4>
                                <p class="text-xs text-slate-500">500+ annual publications</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                                <i data-lucide="briefcase" class="w-5 h-5 text-rose-600 dark:text-rose-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white">98% Placement Rate</h4>
                                <p class="text-xs text-slate-500">Top industry recruiters</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="bg-gradient-to-br from-indigo-500 via-purple-500 to-indigo-600 rounded-3xl p-8 text-white shadow-2xl shadow-indigo-500/20 animate-pulse-glow">
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                        <i data-lucide="building-2" class="w-12 h-12 mb-6 opacity-80"></i>
                        <h3 class="text-2xl font-black mb-3">Our Vision</h3>
                        <p class="text-indigo-100 leading-relaxed font-medium">
                            To be a globally recognized center of academic excellence, fostering innovation, ethical leadership, and inclusive growth — shaping future leaders who transform communities and industries.
                        </p>
                        <div class="mt-6 pt-6 border-t border-white/20 grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-black">30+</div>
                                <div class="text-xs text-indigo-200 font-medium">Years</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black">120</div>
                                <div class="text-xs text-indigo-200 font-medium">Acre Campus</div>
                            </div>
                            <div>
                                <div class="text-2xl font-black">50K+</div>
                                <div class="text-xs text-indigo-200 font-medium">Alumni</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ PROGRAMS SECTION ═══════════ -->
    <section id="programs" class="py-24 px-4 sm:px-6 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2 block">Academics</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Programs Offered</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Explore our diverse range of undergraduate, postgraduate, and doctoral programs.</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $programs = [
                    ['icon' => 'cpu', 'name' => 'Computer Science & Engineering', 'degrees' => 'B.Tech, M.Tech, Ph.D.', 'color' => 'indigo'],
                    ['icon' => 'zap', 'name' => 'Electrical Engineering', 'degrees' => 'B.Tech, M.Tech', 'color' => 'amber'],
                    ['icon' => 'cog', 'name' => 'Mechanical Engineering', 'degrees' => 'B.Tech, M.Tech', 'color' => 'slate'],
                    ['icon' => 'flask-conical', 'name' => 'Chemical Engineering', 'degrees' => 'B.Tech', 'color' => 'emerald'],
                    ['icon' => 'calculator', 'name' => 'Mathematics & Statistics', 'degrees' => 'B.Sc, M.Sc, Ph.D.', 'color' => 'purple'],
                    ['icon' => 'landmark', 'name' => 'Business Administration', 'degrees' => 'BBA, MBA', 'color' => 'rose'],
                ];
                foreach ($programs as $prog): 
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl bg-<?= $prog['color'] ?>-50 dark:bg-<?= $prog['color'] ?>-900/30 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="<?= $prog['icon'] ?>" class="w-6 h-6 text-<?= $prog['color'] ?>-600 dark:text-<?= $prog['color'] ?>-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2"><?= $prog['name'] ?></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium"><?= $prog['degrees'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ PORTALS SECTION ═══════════ -->
    <section id="portals" class="py-24 px-4 sm:px-6 bg-white dark:bg-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-100 dark:bg-indigo-900/20 rounded-full filter blur-[100px] opacity-50"></div>
            <div class="absolute top-0 right-0 w-80 h-80 bg-emerald-100 dark:bg-emerald-900/20 rounded-full filter blur-[100px] opacity-40"></div>
        </div>
        
        <div class="max-w-5xl mx-auto relative z-10">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-purple-600 dark:text-purple-400 mb-2 block">Access</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Choose Your Portal</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto font-medium">Sign in to your designated portal to access your personalized dashboard.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Student Portal Card -->
                <a href="<?= $base ?>/views/auth/student_login.php" class="portal-card group block bg-white dark:bg-slate-800 rounded-3xl p-8 border-2 border-slate-200 dark:border-slate-700 shadow-lg hover:shadow-2xl hover:border-indigo-300 dark:hover:border-indigo-700 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-indigo-50/80 to-transparent dark:from-indigo-900/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center shadow-xl shadow-indigo-500/25 group-hover:shadow-indigo-500/40 group-hover:scale-110 transition-all">
                            <i data-lucide="graduation-cap" class="w-10 h-10 text-white"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Student Portal</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">Access your courses, attendance records, and academic information.</p>
                        <span class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm group-hover:gap-3 transition-all">
                            Sign In <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </span>
                    </div>
                </a>
                
                <!-- Faculty Portal Card -->
                <a href="<?= $base ?>/views/auth/faculty_login.php" class="portal-card group block bg-white dark:bg-slate-800 rounded-3xl p-8 border-2 border-slate-200 dark:border-slate-700 shadow-lg hover:shadow-2xl hover:border-emerald-300 dark:hover:border-emerald-700 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-emerald-50/80 to-transparent dark:from-emerald-900/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-xl shadow-emerald-500/25 group-hover:shadow-emerald-500/40 group-hover:scale-110 transition-all">
                            <i data-lucide="briefcase" class="w-10 h-10 text-white"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Faculty Portal</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">Manage your subjects, track student attendance, and view schedules.</p>
                        <span class="inline-flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-bold text-sm group-hover:gap-3 transition-all">
                            Sign In <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </span>
                    </div>
                </a>
                
                <!-- Admin Portal Card -->
                <a href="<?= $base ?>/views/auth/admin_login.php" class="portal-card group block bg-white dark:bg-slate-800 rounded-3xl p-8 border-2 border-slate-200 dark:border-slate-700 shadow-lg hover:shadow-2xl hover:border-rose-300 dark:hover:border-rose-700 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-rose-50/80 to-transparent dark:from-rose-900/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 flex items-center justify-center shadow-xl shadow-rose-500/25 group-hover:shadow-rose-500/40 group-hover:scale-110 transition-all">
                            <i data-lucide="shield" class="w-10 h-10 text-white"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white mb-2">Admin Portal</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">Full administrative control over users, departments, and system settings.</p>
                        <span class="inline-flex items-center gap-2 text-rose-600 dark:text-rose-400 font-bold text-sm group-hover:gap-3 transition-all">
                            Sign In <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════ CONTACT SECTION ═══════════ -->
    <section id="contact" class="py-24 px-4 sm:px-6 bg-slate-50 dark:bg-slate-950">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-rose-600 dark:text-rose-400 mb-2 block">Reach Us</span>
                <h2 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900 dark:text-white mb-4">Contact Information</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="map-pin" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Address</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">123 Academic Lane, Knowledge City, 560001</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="phone" class="w-6 h-6 text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Phone</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">+91 80 1234 5678</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="mail" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Email</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">info@greenfield.edu</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 text-center hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center mb-4">
                        <i data-lucide="clock" class="w-6 h-6 text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <h4 class="font-bold text-slate-900 dark:text-white mb-1">Office Hours</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Mon - Sat: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ FOOTER ═══════════ -->
    <?php require_once __DIR__ . '/includes/main_footer.php'; ?>

    <!-- ═══════════ SCRIPTS ═══════════ -->
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
            
            themeToggleBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                updateIcons();
            });
            
            // Intersection Observer for stagger animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.stagger').forEach(el => observer.observe(el));
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    const target = document.querySelector(a.getAttribute('href'));
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // Flash messages from session
            <?php if (isset($_SESSION['flash_message'])): ?>
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false,
                    timer: 3500, timerProgressBar: true
                });
                Toast.fire({
                    icon: <?= json_encode($_SESSION['flash_message']['type']) ?>,
                    title: <?= json_encode($_SESSION['flash_message']['message']) ?>
                });
            <?php endif; unset($_SESSION['flash_message']); ?>
        });
    </script>
</body>
</html>
