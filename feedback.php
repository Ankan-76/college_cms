<?php
// feedback.php — Universal Feedback Submission Portal
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Submit Feedback | Greenfield College';
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['role_name']);
$userRole = $isLoggedIn ? strtoupper($_SESSION['role_name']) : 'GUEST';
$userName = $_SESSION['name'] ?? '';
$userEmail = '';
$userPhone = '';

if ($isLoggedIn && !empty($_SESSION['user_table']) && !empty($_SESSION['user_id'])) {
    try {
        $db = \Config\Database::getInstance()->getConnection();
        $allowedTables = ['students', 'teachers', 'admins'];
        if (in_array($_SESSION['user_table'], $allowedTables)) {
            $stmt = $db->prepare("SELECT email, phone FROM {$_SESSION['user_table']} WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userInfo) {
                $userEmail = $userInfo['email'] ?? '';
                $userPhone = $userInfo['phone'] ?? '';
            }
        }
    } catch (Exception $e) {
        // Fallback gracefully
    }
}

// If logged in, render portal layout with sidebar and header
if ($isLoggedIn):
    require_once __DIR__ . '/includes/header.php';
?>
<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm mb-3">
                        <i data-lucide="message-square-heart" class="w-3.5 h-3.5"></i> Continuous Improvement
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Institutional Feedback</h1>
                    <p class="text-indigo-100 text-sm mt-1 max-w-xl">
                        Your voice matters. Share your thoughts, report issues, or propose suggestions to help us elevate Greenfield College.
                    </p>
                </div>
                <div class="sm:text-right flex-shrink-0">
                    <span class="inline-block px-3 py-1.5 rounded-xl bg-white/15 backdrop-blur-md text-xs font-semibold border border-white/20">
                        Posting as: <strong class="text-white"><?= htmlspecialchars($userRole) ?></strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- Feedback Form Card -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/80 p-6 sm:p-8">
            <form action="<?= $base ?>/controllers/process_feedback.php" method="POST" class="space-y-6" id="feedbackForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="submit">

                <!-- User Meta Information (Auto-filled) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Your Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            <input type="text" name="name" value="<?= htmlspecialchars($userName) ?>" required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                placeholder="Your full name">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Email Address <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            <input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                placeholder="name@greenfield.edu">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            <input type="text" name="phone" value="<?= htmlspecialchars($userPhone) ?>"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                placeholder="+91 98765 43210">
                        </div>
                    </div>
                </div>

                <!-- Category & Experience Rating -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
                    <div class="md:col-span-6">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Feedback Category <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            <select name="category" required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                                <option value="General">General Feedback</option>
                                <option value="Academic Curriculum">Academic Curriculum & Syllabus</option>
                                <option value="Teaching & Faculty">Teaching & Faculty Support</option>
                                <option value="Campus & Infrastructure">Campus & Infrastructure</option>
                                <option value="Library & Resources">Library & Digital Resources</option>
                                <option value="Laboratory & Equipments">Laboratory & Equipments</option>
                                <option value="Administration & Office">Administration & Examination</option>
                                <option value="CMS & Digital Portal">College CMS / Web Portal</option>
                                <option value="Hostel & Canteen">Hostel & Canteen Services</option>
                                <option value="Extracurricular & Sports">Extracurricular & Sports</option>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Overall Rating / Experience
                        </label>
                        <div class="flex items-center gap-2 pt-1">
                            <div class="flex items-center gap-1" id="starContainer">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" data-rating="<?= $i ?>" class="star-btn p-1 text-slate-300 dark:text-slate-600 hover:text-amber-400 transition-colors focus:outline-none">
                                    <i data-lucide="star" class="w-6 h-6 fill-current"></i>
                                </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" value="5">
                            <span id="ratingLabel" class="text-xs font-bold text-amber-600 dark:text-amber-400 ml-2">Excellent (5/5)</span>
                        </div>
                    </div>
                </div>

                <!-- Subject Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Subject / Brief Topic <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <i data-lucide="bookmark" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                        <input type="text" name="subject" required maxlength="200"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                            placeholder="e.g. Suggestion regarding library extended study hours">
                    </div>
                </div>

                <!-- Detailed Feedback Message -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Your Feedback / Message <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <textarea name="message" rows="5" required minlength="10"
                            class="w-full p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium placeholder-slate-400"
                            placeholder="Please provide detailed feedback, constructive criticism, or specific suggestions..."></textarea>
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i> Minimum 10 characters. Submissions are reviewed directly by college administration.
                    </p>
                </div>

                <!-- Submit Button and Notices -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i> Confidential & secure institutional transmission.
                    </div>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-7 py-3 rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all text-sm">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    initRatingSelector();
});

function initRatingSelector() {
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('ratingInput');
    const ratingLabel = document.getElementById('ratingLabel');
    const labels = {
        1: 'Poor (1/5)',
        2: 'Fair (2/5)',
        3: 'Good (3/5)',
        4: 'Very Good (4/5)',
        5: 'Excellent (5/5)'
    };

    function updateStars(val) {
        starButtons.forEach(btn => {
            const starVal = parseInt(btn.getAttribute('data-rating'));
            if (starVal <= val) {
                btn.classList.remove('text-slate-300', 'dark:text-slate-600');
                btn.classList.add('text-amber-400');
            } else {
                btn.classList.remove('text-amber-400');
                btn.classList.add('text-slate-300', 'dark:text-slate-600');
            }
        });
        if (labels[val]) {
            ratingLabel.textContent = labels[val];
        }
    }

    starButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const val = parseInt(btn.getAttribute('data-rating'));
            ratingInput.value = val;
            updateStars(val);
        });

        btn.addEventListener('mouseenter', () => {
            const val = parseInt(btn.getAttribute('data-rating'));
            updateStars(val);
        });
    });

    const container = document.getElementById('starContainer');
    if (container) {
        container.addEventListener('mouseleave', () => {
            updateStars(parseInt(ratingInput.value || 5));
        });
    }

    // Set initial
    updateStars(5);
}
</script>

<?php 
    require_once __DIR__ . '/includes/footer.php';
    exit;
endif;

// ═════════════════════════════════════════════════════════════════
// GUEST / PUBLIC VIEW (Matches Landing Page Design System)
// ═════════════════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Share your feedback, reviews, and suggestions with Greenfield College management.">

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
        .glassmorphism-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glassmorphism-nav {
            background: rgba(15, 23, 42, 0.85);
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased min-h-screen flex flex-col transition-colors duration-300">

    <!-- ═══════════ NAVBAR ═══════════ -->
    <nav class="fixed top-0 w-full z-50 glassmorphism-nav border-b border-slate-200/60 dark:border-slate-800/60">
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
                    <a href="<?= $base ?>/#about" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">About</a>
                    <a href="<?= $base ?>/#programs" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Programs</a>
                    <a href="<?= $base ?>/#portals" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Portals</a>
                    <a href="<?= $base ?>/feedback.php" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 transition-colors">Feedback</a>
                    <a href="<?= $base ?>/#contact" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 transition-colors">Contact</a>
                </div>

                <div class="flex items-center gap-3">
                    <button id="theme-toggle" type="button" class="text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 p-2.5 rounded-full transition-colors">
                        <i id="theme-toggle-dark-icon" data-lucide="moon" class="hidden w-5 h-5"></i>
                        <i id="theme-toggle-light-icon" data-lucide="sun" class="hidden w-5 h-5 text-yellow-500"></i>
                    </button>
                    <a href="<?= $base ?>/#portals" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
                        <i data-lucide="log-in" class="w-4 h-4"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="flex-grow pt-28 pb-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Ambient Background glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-3xl mx-auto space-y-8 relative z-10">
            
            <!-- Page Header -->
            <div class="text-center space-y-3">
                <span class="inline-flex items-center gap-2 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                    <i data-lucide="message-square-heart" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i> We Value Your Opinion
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-slate-900 dark:text-white">
                    Submit Your Feedback
                </h1>
                <p class="text-slate-600 dark:text-slate-400 text-base max-w-xl mx-auto">
                    Whether you are a student, faculty member, prospective applicant, parent, or campus visitor, your suggestions help us continually raise institutional standards.
                </p>
            </div>

            <!-- Feedback Submission Card -->
            <div class="bg-white dark:bg-slate-900/90 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl p-6 sm:p-10 backdrop-blur-sm">
                <form action="<?= $base ?>/controllers/process_feedback.php" method="POST" class="space-y-6" id="feedbackForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="submit">

                    <!-- Personal Info Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Full Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                                <input type="text" name="name" required
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                    placeholder="Enter your full name">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                                <input type="email" name="email" required
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                    placeholder="your.email@example.com">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Phone Number <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                                <input type="text" name="phone"
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                    placeholder="+91 98765 43210">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Your Affiliation / Role
                            </label>
                            <div class="relative">
                                <i data-lucide="users" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                                <select name="role_affinity"
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                                    <option value="Visitor">Visitor / Public</option>
                                    <option value="Student">Student</option>
                                    <option value="Faculty">Faculty / Staff</option>
                                    <option value="Parent">Parent / Guardian</option>
                                    <option value="Alumni">Alumni</option>
                                    <option value="Prospective Student">Prospective Student</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Category & Rating -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Feedback Category <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                                <select name="category" required
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium">
                                    <option value="General">General Feedback</option>
                                    <option value="Academic Curriculum">Academic Curriculum & Courses</option>
                                    <option value="Teaching & Faculty">Faculty & Teaching Quality</option>
                                    <option value="Campus & Infrastructure">Campus Facilities & Infrastructure</option>
                                    <option value="Library & Resources">Library & Academic Resources</option>
                                    <option value="Administration & Support">Administrative & Student Support</option>
                                    <option value="CMS & Digital Portal">Website & College CMS</option>
                                    <option value="Admissions & Inquiry">Admissions & Inquiry Process</option>
                                    <option value="Hostel & Canteen">Hostel & Food Quality</option>
                                    <option value="Suggestion">New Idea / Suggestion</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Your Experience Rating
                            </label>
                            <div class="flex items-center gap-2 pt-1.5">
                                <div class="flex items-center gap-1" id="starContainer">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" data-rating="<?= $i ?>" class="star-btn p-1 text-slate-300 dark:text-slate-600 hover:text-amber-400 transition-colors focus:outline-none">
                                        <i data-lucide="star" class="w-6 h-6 fill-current"></i>
                                    </button>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" value="5">
                                <span id="ratingLabel" class="text-xs font-bold text-amber-600 dark:text-amber-400 ml-2">Excellent (5/5)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Subject / Topic <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i data-lucide="bookmark" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            <input type="text" name="subject" required maxlength="200"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium"
                                placeholder="Summary of your feedback or inquiry">
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Detailed Message <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <textarea name="message" rows="5" required minlength="10"
                                class="w-full p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium placeholder-slate-400"
                                placeholder="Describe your experience, suggestion, or feedback with relevant details..."></textarea>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i> Minimum 10 characters.
                        </p>
                    </div>

                    <!-- Submit & Privacy -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4 text-emerald-500"></i> Your submission is private & handled securely.
                        </div>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3.5 rounded-xl shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all text-sm">
                            <i data-lucide="send" class="w-4 h-4"></i> Submit Feedback
                        </button>
                    </div>
                </form>
            </div>

            <!-- Return Home / Portal Info -->
            <div class="text-center pt-2">
                <a href="<?= $base ?>/" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Return to Greenfield College Homepage
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <?php require_once __DIR__ . '/includes/main_footer.php'; ?>

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

            // Star Rating Logic
            const starButtons = document.querySelectorAll('.star-btn');
            const ratingInput = document.getElementById('ratingInput');
            const ratingLabel = document.getElementById('ratingLabel');
            const labels = {
                1: 'Poor (1/5)',
                2: 'Fair (2/5)',
                3: 'Good (3/5)',
                4: 'Very Good (4/5)',
                5: 'Excellent (5/5)'
            };

            function updateStars(val) {
                starButtons.forEach(btn => {
                    const starVal = parseInt(btn.getAttribute('data-rating'));
                    if (starVal <= val) {
                        btn.classList.remove('text-slate-300', 'dark:text-slate-600');
                        btn.classList.add('text-amber-400');
                    } else {
                        btn.classList.remove('text-amber-400');
                        btn.classList.add('text-slate-300', 'dark:text-slate-600');
                    }
                });
                if (labels[val] && ratingLabel) {
                    ratingLabel.textContent = labels[val];
                }
            }

            starButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = parseInt(btn.getAttribute('data-rating'));
                    ratingInput.value = val;
                    updateStars(val);
                });

                btn.addEventListener('mouseenter', () => {
                    const val = parseInt(btn.getAttribute('data-rating'));
                    updateStars(val);
                });
            });

            const container = document.getElementById('starContainer');
            if (container) {
                container.addEventListener('mouseleave', () => {
                    updateStars(parseInt(ratingInput.value || 5));
                });
            }

            updateStars(5);

            // Flash notification
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
    </script>
</body>
</html>
