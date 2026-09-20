<?php
// views/admin/broadcasts.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../controllers/MessageController.php';

require_role('ADMIN');
$pageTitle = 'Broadcast Messages | Admin Portal';

require_once __DIR__ . '/../../includes/header.php';

use Controllers\MessageController;

$controller = new MessageController();
$broadcasts = $controller->getAdminBroadcasts();
$students = $controller->getAllStudents();
$faculty = $controller->getAllFaculty();
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';
$csrfToken = generate_csrf_token();

// Calculate counts for filter pills
$countAll = count($broadcasts);
$countEveryone = count(array_filter($broadcasts, fn($b) => $b['target_type'] === 'ALL'));
$countStudents = count(array_filter($broadcasts, fn($b) => in_array($b['target_type'], ['ALL_STUDENTS', 'STUDENT'])));
$countFaculty = count(array_filter($broadcasts, fn($b) => in_array($b['target_type'], ['ALL_FACULTY', 'FACULTY'])));
$countUrgent = count(array_filter($broadcasts, fn($b) => $b['priority'] === 'URGENT'));
$countPinned = count(array_filter($broadcasts, fn($b) => !empty($b['is_pinned'])));
$countUnsent = count(array_filter($broadcasts, fn($b) => !empty($b['is_unsent'])));
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="h-full flex flex-col max-w-6xl mx-auto">

        <!-- Page Header & Global Controls -->
        <div class="px-4 sm:px-6 lg:px-8 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/80 backdrop-blur-xs flex-shrink-0">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-3">
                        <i data-lucide="radio" class="w-6 h-6 text-indigo-500"></i> Admin Broadcasts
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Official announcements for students and faculty. Manage visibility, unsend, edit, or pin urgent alerts.
                    </p>
                </div>
                
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Multi-Select Toggle -->
                    <button type="button" id="btn-toggle-select" onclick="toggleMultiSelectMode()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors shadow-xs">
                        <i data-lucide="check-square" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span id="btn-toggle-select-text">Select</span>
                    </button>

                    <!-- Export Dropdown -->
                    <div class="relative" id="export-menu-container">
                        <button type="button" onclick="toggleExportMenu()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-xs">
                            <i data-lucide="download" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span>Export Log</span>
                            <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                        </button>
                        <div id="export-dropdown" class="hidden absolute right-0 mt-1 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30">
                            <a href="<?= $base ?>/controllers/process_message.php?action=export_broadcasts&format=txt" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i> Text Log (.txt)
                            </a>
                            <a href="<?= $base ?>/controllers/process_message.php?action=export_broadcasts&format=html" target="_blank" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">
                                <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-400"></i> Print / PDF Log
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar & Filter Tabs Row -->
            <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex flex-col sm:flex-row gap-2.5 sm:items-center sm:justify-between">
                <!-- Search Bar -->
                <div class="relative flex-1 max-w-md">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none"></i>
                    <input type="text" id="broadcast-search-input" placeholder="Search announcements by text, target, or date..." oninput="handleBroadcastSearch(this.value)" class="w-full pl-9 pr-8 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 placeholder:text-slate-400 transition-all">
                    <button type="button" id="broadcast-search-clear" onclick="clearBroadcastSearch()" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    </button>
                </div>

                <!-- Filter Pills -->
                <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0 text-xs font-semibold text-slate-600 dark:text-slate-300" id="filter-pills-bar">
                    <button type="button" onclick="switchFilterTab('all', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-indigo-600 text-white shadow-xs">
                        All (<?= $countAll ?>)
                    </button>
                    <button type="button" onclick="switchFilterTab('everyone', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200">
                        Everyone (<?= $countEveryone ?>)
                    </button>
                    <button type="button" onclick="switchFilterTab('students', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200">
                        Students (<?= $countStudents ?>)
                    </button>
                    <button type="button" onclick="switchFilterTab('faculty', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200">
                        Faculty (<?= $countFaculty ?>)
                    </button>
                    <?php if ($countUrgent > 0): ?>
                        <button type="button" onclick="switchFilterTab('urgent', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100">
                            Urgent (<?= $countUrgent ?>)
                        </button>
                    <?php endif; ?>
                    <?php if ($countPinned > 0): ?>
                        <button type="button" onclick="switchFilterTab('pinned', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100">
                            Pinned (<?= $countPinned ?>)
                        </button>
                    <?php endif; ?>
                    <?php if ($countUnsent > 0): ?>
                        <button type="button" onclick="switchFilterTab('unsent', this)" class="filter-pill px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200">
                            Unsent (<?= $countUnsent ?>)
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Multi-Select Floating Action Toolbar -->
        <div id="batch-action-bar" class="hidden px-4 sm:px-6 lg:px-8 py-2.5 bg-indigo-50 dark:bg-indigo-950/70 border-b border-indigo-200 dark:border-indigo-800/80 flex items-center justify-between text-xs transition-all animate-fadeIn">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-200 cursor-pointer">
                    <input type="checkbox" id="master-checkbox" onchange="toggleSelectAll(this.checked)" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600">
                    <span>Select All</span>
                </label>
                <span class="text-slate-400">|</span>
                <span id="selected-count-badge" class="font-bold text-indigo-700 dark:text-indigo-300">0 selected</span>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" onclick="openBatchActionModal('unsend')" class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/40 hover:bg-amber-200 border border-amber-300 dark:border-amber-700 transition-colors">
                    <i data-lucide="eye-off" class="w-3.5 h-3.5 inline-block mr-1"></i> Unsend Selected
                </button>
                <button type="button" onclick="openBatchActionModal('delete')" class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-xs transition-colors">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5 inline-block mr-1"></i> Delete Selected
                </button>
                <button type="button" onclick="toggleMultiSelectMode(false)" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Chat Layout -->
        <div class="flex-1 overflow-hidden flex flex-col relative bg-slate-50 dark:bg-slate-900/50">
            
            <!-- Messages Area -->
            <div id="messages-container" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5">
                <?php if (empty($broadcasts)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-slate-400 dark:text-slate-500 space-y-4 py-16">
                        <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            <i data-lucide="radio" class="w-8 h-8 opacity-50"></i>
                        </div>
                        <p class="text-sm">No broadcasts have been sent yet.</p>
                        <p class="text-xs text-slate-400">Use the form below to publish your first announcement.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $currentDate = '';
                    foreach ($broadcasts as $msg): 
                        $msgDate = date('M j, Y', strtotime($msg['created_at']));
                        $isUnsent = !empty($msg['is_unsent']);
                        $isPinned = !empty($msg['is_pinned']);
                        $priority = $msg['priority'] ?? 'NORMAL';
                        $targetType = $msg['target_type'] ?? 'ALL';

                        // Format target string
                        $targetLabel = 'Everyone';
                        $targetCategory = 'everyone';
                        if ($targetType === 'ALL_STUDENTS') {
                            $targetLabel = 'All Students';
                            $targetCategory = 'students';
                        } elseif ($targetType === 'ALL_FACULTY') {
                            $targetLabel = 'All Faculty';
                            $targetCategory = 'faculty';
                        } elseif ($targetType === 'STUDENT') {
                            $targetLabel = 'Student: ' . ($msg['target_name'] ?? 'Direct');
                            $targetCategory = 'students';
                        } elseif ($targetType === 'FACULTY') {
                            $targetLabel = 'Faculty: ' . ($msg['target_name'] ?? 'Direct');
                            $targetCategory = 'faculty';
                        }

                        // Priority Badge styling
                        $priorityBadgeClass = 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300';
                        $priorityIcon = 'megaphone';
                        if ($priority === 'URGENT') {
                            $priorityBadgeClass = 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 border border-rose-200 dark:border-rose-800';
                            $priorityIcon = 'flame';
                        } elseif ($priority === 'ACADEMIC') {
                            $priorityBadgeClass = 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800';
                            $priorityIcon = 'book-open';
                        } elseif ($priority === 'EVENT') {
                            $priorityBadgeClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
                            $priorityIcon = 'calendar';
                        }

                        // Search string for client-side search
                        $searchHaystack = strtolower($msg['content'] . ' ' . $msg['admin_name'] . ' ' . $targetLabel . ' ' . $priority . ' ' . $msgDate);

                        if ($msgDate !== $currentDate):
                            $currentDate = $msgDate;
                    ?>
                        <!-- Date Divider -->
                        <div class="flex justify-center my-4 date-divider">
                            <span class="px-3 py-0.5 bg-slate-200 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300 text-[11px] font-bold rounded-full shadow-2xs">
                                <?= $currentDate === date('M j, Y') ? 'Today' : $currentDate ?>
                            </span>
                        </div>
                    <?php endif; ?>

                        <!-- Broadcast Card Item -->
                        <div class="broadcast-card-item flex items-start gap-2 justify-end group transition-opacity" 
                             id="broadcast-card-<?= $msg['id'] ?>"
                             data-id="<?= $msg['id'] ?>"
                             data-target-cat="<?= $targetCategory ?>"
                             data-priority="<?= strtolower($priority) ?>"
                             data-pinned="<?= $isPinned ? 'true' : 'false' ?>"
                             data-unsent="<?= $isUnsent ? 'true' : 'false' ?>"
                             data-search-text="<?= htmlspecialchars($searchHaystack) ?>">
                            
                            <!-- Multi-Select Checkbox Column -->
                            <div class="batch-checkbox-col hidden pt-3">
                                <input type="checkbox" class="broadcast-select-check w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600" value="<?= $msg['id'] ?>" onchange="updateSelectedCount()">
                            </div>

                            <!-- Card Bubble Container -->
                            <div class="flex flex-col items-end max-w-[90%] sm:max-w-[75%]">
                                
                                <!-- Meta header: Admin name & Priority / Pin badges -->
                                <div class="flex items-center gap-2 mb-1 flex-wrap justify-end">
                                    <?php if ($isPinned): ?>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                            <i data-lucide="pin" class="w-3 h-3"></i> Pinned
                                        </span>
                                    <?php endif; ?>

                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $priorityBadgeClass ?>">
                                        <i data-lucide="<?= $priorityIcon ?>" class="w-3 h-3"></i> <?= htmlspecialchars($priority) ?>
                                    </span>

                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        <?= htmlspecialchars($msg['admin_name']) ?> (Admin)
                                    </span>
                                </div>
                                
                                <!-- The Main Bubble -->
                                <div class="relative group/bubble w-full">
                                    <div class="rounded-2xl rounded-tr-sm px-5 py-3.5 shadow-sm transition-all <?= $isUnsent 
                                        ? 'bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 italic' 
                                        : ($priority === 'URGENT' ? 'bg-gradient-to-br from-rose-600 to-rose-700 text-white shadow-rose-600/20 shadow-md' : 'bg-indigo-600 text-white shadow-indigo-600/20 shadow-md') ?>">
                                        
                                        <!-- Target & Unsent status header inside bubble -->
                                        <div class="mb-1.5 flex items-center justify-between gap-2 border-b border-white/10 pb-1 text-[10px] font-bold uppercase tracking-wider <?= $isUnsent ? 'text-slate-400' : 'text-indigo-100' ?>">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <i data-lucide="send" class="w-3 h-3"></i>
                                                <span>To: <?= htmlspecialchars($targetLabel) ?></span>
                                            </div>

                                            <?php if ($isUnsent): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[9px] font-extrabold not-italic">
                                                    🚫 Unsent
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Message Content -->
                                        <p class="broadcast-text text-sm whitespace-pre-wrap break-words leading-relaxed font-medium <?= $isUnsent ? 'line-through opacity-80' : '' ?>" id="broadcast-content-<?= $msg['id'] ?>"><?= htmlspecialchars($msg['content']) ?></p>

                                        <!-- Edited Flag -->
                                        <?php if (!empty($msg['updated_at']) && $msg['updated_at'] !== $msg['created_at']): ?>
                                            <div class="text-[9px] opacity-75 mt-1 text-right italic font-normal">
                                                Edited &bull; <?= date('M j, g:i A', strtotime($msg['updated_at'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Quick Action Controls (Hover Dropdown) -->
                                    <div class="absolute -left-10 top-2 opacity-0 group-hover/bubble:opacity-100 transition-opacity">
                                        <div class="relative" id="action-menu-container-<?= $msg['id'] ?>">
                                            <button type="button" onclick="toggleCardActionMenu(<?= $msg['id'] ?>, event)" class="p-1.5 bg-white dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg shadow-md border border-slate-200 dark:border-slate-700 transition-all">
                                                <i data-lucide="more-vertical" class="w-3.5 h-3.5"></i>
                                            </button>
                                            
                                            <!-- Dropdown Menu -->
                                            <div id="card-action-menu-<?= $msg['id'] ?>" class="hidden absolute left-0 mt-1 w-44 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30 card-action-menu">
                                                <!-- Edit -->
                                                <button type="button" onclick="openEditModal(<?= $msg['id'] ?>)" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                    <i data-lucide="edit-3" class="w-3 h-3 text-slate-400"></i> Edit Content
                                                </button>

                                                <!-- Pin/Unpin -->
                                                <button type="button" onclick="togglePin(<?= $msg['id'] ?>)" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                    <i data-lucide="pin" class="w-3 h-3 text-amber-500"></i> <?= $isPinned ? 'Unpin' : 'Pin to Top' ?>
                                                </button>

                                                <!-- Unsend / Restore -->
                                                <button type="button" onclick="toggleUnsend(<?= $msg['id'] ?>, <?= $isUnsent ? '0' : '1' ?>)" class="w-full text-left px-3 py-1.5 text-xs text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 flex items-center gap-2">
                                                    <i data-lucide="<?= $isUnsent ? 'eye' : 'eye-off' ?>" class="w-3 h-3"></i> <?= $isUnsent ? 'Restore Notice' : 'Unsend Notice' ?>
                                                </button>

                                                <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>

                                                <!-- Delete -->
                                                <button type="button" onclick="openDeleteModal(<?= $msg['id'] ?>)" class="w-full text-left px-3 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Delete Permanently
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timestamp & Status Footer -->
                                <span class="text-[10px] text-slate-400 mt-1 font-medium flex items-center gap-1">
                                    <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                    <i data-lucide="check-check" class="w-3 h-3 text-indigo-500"></i>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div id="no-filter-match" class="hidden text-center py-16 text-slate-400 text-xs">
                    <i data-lucide="search-x" class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2"></i>
                    <p>No broadcast announcements match the active filter or search term.</p>
                </div>
            </div>

            <!-- Upgraded Announcement Composer -->
            <div class="p-4 sm:p-5 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <form action="<?= $base ?>/controllers/process_message.php" method="POST" class="max-w-4xl mx-auto flex flex-col gap-3" id="broadcast-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send_broadcast">
                    
                    <!-- Target Selection & Priority Row -->
                    <div class="bg-slate-50 dark:bg-slate-900/60 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">SEND TO:</span>
                                
                                <div class="flex items-center gap-3 flex-wrap text-xs">
                                    <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                                        <input type="radio" name="target_type" value="ALL" class="text-indigo-600 focus:ring-indigo-500" checked onchange="toggleTargetDropdowns()">
                                        <span>Everyone</span>
                                    </label>
                                    
                                    <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                                        <input type="radio" name="target_type" value="ALL_STUDENTS" class="text-indigo-600 focus:ring-indigo-500" onchange="toggleTargetDropdowns()">
                                        <span>All Students</span>
                                    </label>
                                    
                                    <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                                        <input type="radio" name="target_type" value="ALL_FACULTY" class="text-indigo-600 focus:ring-indigo-500" onchange="toggleTargetDropdowns()">
                                        <span>All Faculty</span>
                                    </label>

                                    <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                                        <input type="radio" name="target_type" value="STUDENT" class="text-indigo-600 focus:ring-indigo-500" onchange="toggleTargetDropdowns()">
                                        <span>Specific Student</span>
                                    </label>
                                    
                                    <label class="flex items-center gap-1.5 cursor-pointer font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                                        <input type="radio" name="target_type" value="FACULTY" class="text-indigo-600 focus:ring-indigo-500" onchange="toggleTargetDropdowns()">
                                        <span>Specific Faculty</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Priority & Pin Toggle -->
                            <div class="flex items-center gap-3 ml-auto">
                                <div class="flex items-center gap-1.5">
                                    <label for="priority-select" class="text-xs font-bold text-slate-500 dark:text-slate-400">PRIORITY:</label>
                                    <select name="priority" id="priority-select" class="text-xs py-1 px-2.5 rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500 font-medium">
                                        <option value="NORMAL">General Notice</option>
                                        <option value="URGENT">🔥 Urgent Alert</option>
                                        <option value="ACADEMIC">📚 Academic / Exam</option>
                                        <option value="EVENT">📅 Event / Holiday</option>
                                    </select>
                                </div>

                                <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                                    <input type="checkbox" name="is_pinned" value="1" class="w-3.5 h-3.5 rounded text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex items-center gap-0.5"><i data-lucide="pin" class="w-3 h-3 text-amber-500"></i> Pin</span>
                                </label>
                            </div>
                        </div>

                        <!-- Dropdowns for Specific Targets -->
                        <div class="hidden pt-2 border-t border-slate-200 dark:border-slate-700" id="student-select-container">
                            <select name="student_id" id="student_id" class="w-full text-xs py-2 rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500">
                                <option value="">-- Select Student Target --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?> (Roll: <?= htmlspecialchars($student['roll_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="hidden pt-2 border-t border-slate-200 dark:border-slate-700" id="faculty-select-container">
                            <select name="faculty_id" id="faculty_id" class="w-full text-xs py-2 rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-indigo-500">
                                <option value="">-- Select Faculty Target --</option>
                                <?php foreach ($faculty as $fac): ?>
                                    <option value="<?= $fac['id'] ?>"><?= htmlspecialchars($fac['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Composer Input -->
                    <div class="flex items-end gap-3">
                        <div class="flex-1 bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-shadow flex items-end">
                            <textarea name="content" id="message-input" rows="1" class="w-full bg-transparent border-0 focus:ring-0 text-slate-900 dark:text-white px-4 py-3.5 resize-none max-h-36 text-sm placeholder:text-slate-400 leading-relaxed" placeholder="Publish an official college announcement... (Enter to send, Shift+Enter for newline)" required></textarea>
                        </div>
                        
                        <button type="submit" id="send-button" class="flex-shrink-0 w-12 h-12 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-all shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="send" class="w-5 h-5 ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</main>

<!-- Edit Broadcast Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onclick="closeEditModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-5 z-10">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3 mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-indigo-500"></i> Edit Broadcast Announcement
            </h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="edit-form" onsubmit="submitEdit(event)">
            <input type="hidden" id="edit-broadcast-id">
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Announcement Content:</label>
                <textarea id="edit-content-input" rows="4" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 p-3" required></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 z-10 text-center">
        <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
            <i data-lucide="trash-2" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete Broadcast?</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
            This will permanently remove this announcement from the college database. This action cannot be undone.
        </p>
        <input type="hidden" id="delete-broadcast-id">
        <div class="flex items-center gap-2 mt-5">
            <button type="button" onclick="closeDeleteModal()" class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors">
                Cancel
            </button>
            <button type="button" onclick="confirmDelete()" class="flex-1 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/25">
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Batch Action Confirmation Modal -->
<div id="batch-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onclick="closeBatchModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 z-10 text-center">
        <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3" id="batch-modal-icon">
            <i data-lucide="alert-circle" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white" id="batch-modal-title">Confirm Batch Action</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed" id="batch-modal-desc">
            Are you sure you want to proceed with this bulk action?
        </p>
        <div class="flex items-center gap-2 mt-5">
            <button type="button" onclick="closeBatchModal()" class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors">
                Cancel
            </button>
            <button type="button" id="batch-confirm-btn" onclick="executeBatchAction()" class="flex-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base ?>';
const CSRF_TOKEN = '<?= $csrfToken ?>';

let isMultiSelectMode = false;
let activeFilterTab = 'all';
let batchActionType = 'unsend';

// ── Search & Filter Logic ─────────────────────────────────────
function handleBroadcastSearch(query) {
    const clearBtn = document.getElementById('broadcast-search-clear');
    if (clearBtn) {
        if (query && query.trim().length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }
    applyFilters();
}

function clearBroadcastSearch() {
    const input = document.getElementById('broadcast-search-input');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('broadcast-search-clear');
    if (clearBtn) clearBtn.classList.add('hidden');
    applyFilters();
}

function switchFilterTab(tab, btn) {
    activeFilterTab = tab;
    document.querySelectorAll('.filter-pill').forEach(b => {
        b.classList.remove('bg-indigo-600', 'text-white', 'shadow-xs');
        b.classList.add('bg-slate-100', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300');
    });
    btn.classList.add('bg-indigo-600', 'text-white', 'shadow-xs');
    btn.classList.remove('bg-slate-100', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300');

    applyFilters();
}

function applyFilters() {
    const searchVal = (document.getElementById('broadcast-search-input')?.value || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.broadcast-card-item');
    let visibleCount = 0;

    cards.forEach(card => {
        const targetCat = card.getAttribute('data-target-cat');
        const priority = card.getAttribute('data-priority');
        const isPinned = card.getAttribute('data-pinned') === 'true';
        const isUnsent = card.getAttribute('data-unsent') === 'true';
        const haystack = card.getAttribute('data-search-text') || '';

        let matchesTab = false;
        if (activeFilterTab === 'all') {
            matchesTab = true;
        } else if (activeFilterTab === 'everyone') {
            matchesTab = (targetCat === 'everyone');
        } else if (activeFilterTab === 'students') {
            matchesTab = (targetCat === 'students');
        } else if (activeFilterTab === 'faculty') {
            matchesTab = (targetCat === 'faculty');
        } else if (activeFilterTab === 'urgent') {
            matchesTab = (priority === 'urgent');
        } else if (activeFilterTab === 'pinned') {
            matchesTab = isPinned;
        } else if (activeFilterTab === 'unsent') {
            matchesTab = isUnsent;
        }

        const matchesSearch = (!searchVal) || haystack.includes(searchVal);

        if (matchesTab && matchesSearch) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });

    const noMatch = document.getElementById('no-filter-match');
    if (noMatch) {
        if (visibleCount === 0) noMatch.classList.remove('hidden');
        else noMatch.classList.add('hidden');
    }
}

// ── Dropdown Menus ────────────────────────────────────────────
function toggleExportMenu() {
    const menu = document.getElementById('export-dropdown');
    if (menu) menu.classList.toggle('hidden');
}

function toggleCardActionMenu(msgId, e) {
    e.stopPropagation();
    document.querySelectorAll('.card-action-menu').forEach(m => {
        if (m.id !== 'card-action-menu-' + msgId) m.classList.add('hidden');
    });
    const menu = document.getElementById('card-action-menu-' + msgId);
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.card-action-menu').forEach(m => m.classList.add('hidden'));
    const exportMenu = document.getElementById('export-dropdown');
    if (exportMenu) exportMenu.classList.add('hidden');
});

// ── Multi-Select Mode ─────────────────────────────────────────
function toggleMultiSelectMode(force) {
    if (force !== undefined) {
        isMultiSelectMode = force;
    } else {
        isMultiSelectMode = !isMultiSelectMode;
    }

    const selectCols = document.querySelectorAll('.batch-checkbox-col');
    const toolbar = document.getElementById('batch-action-bar');
    const toggleBtnText = document.getElementById('btn-toggle-select-text');

    if (isMultiSelectMode) {
        selectCols.forEach(col => col.classList.remove('hidden'));
        toolbar.classList.remove('hidden');
        if (toggleBtnText) toggleBtnText.textContent = 'Cancel';
    } else {
        selectCols.forEach(col => col.classList.add('hidden'));
        toolbar.classList.add('hidden');
        if (toggleBtnText) toggleBtnText.textContent = 'Select';
        // Uncheck all
        document.querySelectorAll('.broadcast-select-check').forEach(chk => chk.checked = false);
        const master = document.getElementById('master-checkbox');
        if (master) master.checked = false;
        updateSelectedCount();
    }
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.broadcast-card-item:not(.hidden) .broadcast-select-check').forEach(chk => {
        chk.checked = checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.broadcast-select-check:checked');
    const badge = document.getElementById('selected-count-badge');
    if (badge) badge.textContent = checked.length + ' selected';
}

function getSelectedIds() {
    const checked = document.querySelectorAll('.broadcast-select-check:checked');
    return Array.from(checked).map(c => parseInt(c.value));
}

// ── Single Actions (AJAX) ─────────────────────────────────────
function togglePin(broadcastId) {
    const fd = new FormData();
    fd.append('action', 'toggle_pin_broadcast');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('broadcast_id', broadcastId);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function toggleUnsend(broadcastId, unsendVal) {
    const fd = new FormData();
    fd.append('action', 'unsend_broadcast');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('broadcast_id', broadcastId);
    fd.append('unsend', unsendVal);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// ── Edit Modal ────────────────────────────────────────────────
function openEditModal(broadcastId) {
    const contentEl = document.getElementById('broadcast-content-' + broadcastId);
    if (!contentEl) return;
    document.getElementById('edit-broadcast-id').value = broadcastId;
    document.getElementById('edit-content-input').value = contentEl.textContent.trim();
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-content-input').focus();
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

function submitEdit(e) {
    e.preventDefault();
    const id = document.getElementById('edit-broadcast-id').value;
    const content = document.getElementById('edit-content-input').value.trim();

    if (!content) return;

    const fd = new FormData();
    fd.append('action', 'edit_broadcast');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('broadcast_id', id);
    fd.append('content', content);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            closeEditModal();
            location.reload();
        }
    });
}

// ── Delete Modal ──────────────────────────────────────────────
function openDeleteModal(broadcastId) {
    document.getElementById('delete-broadcast-id').value = broadcastId;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}

function confirmDelete() {
    const id = document.getElementById('delete-broadcast-id').value;
    const fd = new FormData();
    fd.append('action', 'delete_broadcast');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('broadcast_id', id);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            closeDeleteModal();
            const card = document.getElementById('broadcast-card-' + id);
            if (card) card.remove();
            applyFilters();
        }
    });
}

// ── Batch Action Modal ────────────────────────────────────────
function openBatchActionModal(type) {
    const selected = getSelectedIds();
    if (selected.length === 0) {
        alert('Please select at least one broadcast announcement.');
        return;
    }

    batchActionType = type;
    const title = document.getElementById('batch-modal-title');
    const desc = document.getElementById('batch-modal-desc');
    const btn = document.getElementById('batch-confirm-btn');

    if (type === 'unsend') {
        title.textContent = 'Unsend ' + selected.length + ' Announcements?';
        desc.textContent = 'These announcements will be retracted immediately from all student and faculty feeds.';
        btn.textContent = 'Unsend Selected';
        btn.className = 'flex-1 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold transition-all shadow-md';
    } else {
        title.textContent = 'Permanently Delete ' + selected.length + ' Announcements?';
        desc.textContent = 'This action will permanently remove these announcements from the database. This cannot be undone.';
        btn.textContent = 'Delete Selected';
        btn.className = 'flex-1 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/25';
    }

    document.getElementById('batch-modal').classList.remove('hidden');
}

function closeBatchModal() {
    document.getElementById('batch-modal').classList.add('hidden');
}

function executeBatchAction() {
    const selected = getSelectedIds();
    if (selected.length === 0) return;

    const action = (batchActionType === 'unsend') ? 'batch_unsend_broadcast' : 'batch_delete_broadcast';
    const fd = new FormData();
    fd.append('action', action);
    fd.append('csrf_token', CSRF_TOKEN);
    selected.forEach(id => fd.append('broadcast_ids[]', id));

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            closeBatchModal();
            location.reload();
        }
    });
}

// ── Target Dropdowns Toggle in Composer ───────────────────────
function toggleTargetDropdowns() {
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    const studentContainer = document.getElementById('student-select-container');
    const facultyContainer = document.getElementById('faculty-select-container');
    
    if (targetType === 'STUDENT') {
        studentContainer.classList.remove('hidden');
        facultyContainer.classList.add('hidden');
    } else if (targetType === 'FACULTY') {
        studentContainer.classList.add('hidden');
        facultyContainer.classList.remove('hidden');
    } else {
        studentContainer.classList.add('hidden');
        facultyContainer.classList.add('hidden');
    }
    
    validateForm();
}

function validateForm() {
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-button');
    const studentSelect = document.getElementById('student_id');
    const facultySelect = document.getElementById('faculty_id');

    const hasContent = input.value.trim() !== '';
    const targetType = document.querySelector('input[name="target_type"]:checked').value;
    
    let hasTarget = true;
    if (targetType === 'STUDENT' && !studentSelect.value) {
        hasTarget = false;
    } else if (targetType === 'FACULTY' && !facultySelect.value) {
        hasTarget = false;
    }
    
    sendBtn.disabled = !(hasContent && hasTarget);
}

// ── DOM Initialization ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('messages-container');
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-button');
    
    // Auto-scroll to bottom
    if (container) {
        container.scrollTop = container.scrollHeight;
    }

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 144) + 'px';
        validateForm();
    });

    // Enter to submit (Shift+Enter for newline)
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendBtn.disabled) {
                document.getElementById('broadcast-form').submit();
            }
        }
    });

    const studentSelect = document.getElementById('student_id');
    const facultySelect = document.getElementById('faculty_id');
    
    studentSelect.addEventListener('change', validateForm);
    facultySelect.addEventListener('change', validateForm);
    
    window.toggleTargetDropdowns = toggleTargetDropdowns;
    toggleTargetDropdowns();
    lucide.createIcons();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
