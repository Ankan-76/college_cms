<?php
// views/faculty/timetable.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'My Timetable | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">My Timetable</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View your weekly class schedule and teaching assignments.</p>
            </div>
            
            <div class="flex gap-2 shrink-0">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all hover:-translate-y-0.5 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Schedule
                </button>
            </div>
        </div>

        <!-- Timetable UI -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden relative">
            
            <div class="absolute inset-0 bg-white/60 dark:bg-slate-800/60 backdrop-blur-[2px] z-10 flex flex-col items-center justify-center">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-xl text-center border border-slate-200 dark:border-slate-700 max-w-sm mx-4 transform transition-all hover:scale-105">
                    <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/30 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-clock" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Timetable Sync Pending</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">The academic timetable matrix is currently being updated by the administration. Your schedule will appear here once published.</p>
                </div>
            </div>

            <div class="overflow-x-auto opacity-40 blur-[1px] pointer-events-none">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase">Day</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">09:00 - 10:00</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">10:00 - 11:00</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">11:00 - 12:00</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">12:00 - 01:00</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase">02:00 - 03:00</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <!-- Monday -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900 dark:text-white">Monday</td>
                            <td class="px-2 py-3"><div class="bg-indigo-50 text-indigo-700 text-xs p-2 rounded-lg text-center font-medium border border-indigo-100">CS-301<br>Room 102</div></td>
                            <td class="px-2 py-3"><div class="bg-slate-50 text-slate-400 text-xs p-2 rounded-lg text-center font-medium border border-slate-100">Free</div></td>
                            <td class="px-2 py-3"><div class="bg-emerald-50 text-emerald-700 text-xs p-2 rounded-lg text-center font-medium border border-emerald-100">IT-204<br>Lab 1</div></td>
                            <td class="px-2 py-3 bg-slate-50/50"><div class="text-slate-400 text-xs p-2 text-center font-medium">LUNCH</div></td>
                            <td class="px-2 py-3"><div class="bg-slate-50 text-slate-400 text-xs p-2 rounded-lg text-center font-medium border border-slate-100">Free</div></td>
                        </tr>
                        <!-- Tuesday -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900 dark:text-white">Tuesday</td>
                            <td class="px-2 py-3"><div class="bg-slate-50 text-slate-400 text-xs p-2 rounded-lg text-center font-medium border border-slate-100">Free</div></td>
                            <td class="px-2 py-3"><div class="bg-amber-50 text-amber-700 text-xs p-2 rounded-lg text-center font-medium border border-amber-100">CS-405<br>Room 205</div></td>
                            <td class="px-2 py-3"><div class="bg-amber-50 text-amber-700 text-xs p-2 rounded-lg text-center font-medium border border-amber-100">CS-405<br>Room 205</div></td>
                            <td class="px-2 py-3 bg-slate-50/50"><div class="text-slate-400 text-xs p-2 text-center font-medium">LUNCH</div></td>
                            <td class="px-2 py-3"><div class="bg-indigo-50 text-indigo-700 text-xs p-2 rounded-lg text-center font-medium border border-indigo-100">CS-301<br>Room 102</div></td>
                        </tr>
                        <!-- Wednesday -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-900 dark:text-white">Wednesday</td>
                            <td class="px-2 py-3"><div class="bg-emerald-50 text-emerald-700 text-xs p-2 rounded-lg text-center font-medium border border-emerald-100">IT-204<br>Lab 1</div></td>
                            <td class="px-2 py-3"><div class="bg-emerald-50 text-emerald-700 text-xs p-2 rounded-lg text-center font-medium border border-emerald-100">IT-204<br>Lab 1</div></td>
                            <td class="px-2 py-3"><div class="bg-slate-50 text-slate-400 text-xs p-2 rounded-lg text-center font-medium border border-slate-100">Free</div></td>
                            <td class="px-2 py-3 bg-slate-50/50"><div class="text-slate-400 text-xs p-2 text-center font-medium">LUNCH</div></td>
                            <td class="px-2 py-3"><div class="bg-slate-50 text-slate-400 text-xs p-2 rounded-lg text-center font-medium border border-slate-100">Free</div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
