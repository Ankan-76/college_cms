<?php
// views/faculty/messages.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';

require_role('FACULTY');
$pageTitle = 'Messages | Faculty Portal';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controllers/MessageController.php';

use Controllers\MessageController;

$controller = new MessageController();
$userId = (int) $_SESSION['user_id'];
$conversations = $controller->getConversations($userId, 'FACULTY');
$contacts = $controller->getContactableUsers($userId, 'FACULTY');
$base = defined('BASE_URL') ? BASE_URL : '/college_cms';

// Active conversation
$rawConvId = $_GET['conv'] ?? '';
$isBroadcast = ($rawConvId === 'broadcast');
$activeConvId = $isBroadcast ? 'broadcast' : (int) $rawConvId;

$activeMessages = [];
$activeConversation = null;
$pinnedMessages = [];

if ($isBroadcast) {
    $activeMessages = $controller->getAdminBroadcastsForUser($userId, 'FACULTY');
} elseif ($activeConvId) {
    $activeMessages = $controller->getMessages((int)$activeConvId, $userId, 'FACULTY');
    $activeConversation = $controller->getConversation((int)$activeConvId, $userId, 'FACULTY');
    $pinnedMessages = $controller->getPinnedMessages((int)$activeConvId, $userId, 'FACULTY');
    // Re-fetch conversations to update read counts
    $conversations = $controller->getConversations($userId, 'FACULTY');
}

$csrfToken = generate_csrf_token();
?>

<!-- Main Content Area Wrapper -->
<main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="h-full flex flex-col">

        <!-- Page Header -->
        <div class="px-4 sm:px-6 lg:px-8 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/50 flex-shrink-0">
            <div class="flex items-center justify-between max-w-full">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-5 h-5 text-emerald-500"></i> Messages
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Direct messaging with colleagues and your assigned course students.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openStarredDrawer()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700/50 hover:bg-amber-100 transition-all shadow-sm">
                        <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400 text-amber-500"></i>
                        <span class="hidden sm:inline">Saved Messages</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Layout -->
        <div class="flex-1 flex overflow-hidden">
            
            <!-- Conversations Sidebar -->
            <div id="conv-sidebar" class="w-80 flex-shrink-0 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex flex-col <?= $activeConvId ? 'hidden md:flex' : 'flex' ?>">
                
                <!-- Sidebar Header: Search Bar, New Conversation & Filter Tabs -->
                <div class="p-3 border-b border-slate-200 dark:border-slate-700 space-y-2.5">
                    <!-- Sidebar Chat Search Bar (Above New Conversation) -->
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none"></i>
                        <input type="text" id="sidebar-search-input" placeholder="Search conversations..." oninput="handleSidebarSearch(this.value)" class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:bg-white dark:focus:bg-slate-700 placeholder:text-slate-400 transition-all">
                        <button type="button" id="sidebar-search-clear" onclick="clearSidebarSearch()" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <button onclick="document.getElementById('new-conv-modal').classList.remove('hidden')" 
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i> New Conversation
                    </button>

                    <!-- Filter Tabs: All, Unread, Students, Faculty -->
                    <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-700/60 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300" id="conv-tabs">
                        <button type="button" onclick="filterConversations('all', this)" class="conv-tab-btn flex-1 py-1.5 text-center rounded-lg transition-all bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-sm">
                            All
                        </button>
                        <button type="button" onclick="filterConversations('unread', this)" class="conv-tab-btn flex-1 py-1.5 text-center rounded-lg transition-all hover:text-slate-900 dark:hover:text-white relative">
                            Unread
                            <span id="tab-unread-badge" class="hidden ml-1 px-1.5 py-0.2 rounded-full text-[9px] bg-rose-500 text-white font-bold"></span>
                        </button>
                        <button type="button" onclick="filterConversations('student', this)" class="conv-tab-btn flex-1 py-1.5 text-center rounded-lg transition-all hover:text-slate-900 dark:hover:text-white">
                            Students
                        </button>
                        <button type="button" onclick="filterConversations('faculty', this)" class="conv-tab-btn flex-1 py-1.5 text-center rounded-lg transition-all hover:text-slate-900 dark:hover:text-white">
                            Faculty
                        </button>
                    </div>
                </div>

                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto" id="conv-list">
                    
                    <!-- Pinned Admin Broadcasts -->
                    <a href="<?= $base ?>/views/faculty/messages.php?conv=broadcast" 
                       data-category="broadcast"
                       data-unread="false"
                       data-search-text="college admin official announcements broadcast"
                       class="conv-item flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= $isBroadcast ? 'bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-l-emerald-500' : '' ?>">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white flex-shrink-0 shadow-sm border-2 border-slate-200 dark:border-slate-600">
                            <i data-lucide="radio" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 dark:text-white truncate">College Admin</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 uppercase">Broadcast</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5 font-medium italic">Official announcements</p>
                        </div>
                    </a>

                    <?php if (empty($conversations)): ?>
                        <div class="p-6 text-center" id="empty-conv-notice">
                            <i data-lucide="message-square-off" class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-3"></i>
                            <p class="text-sm text-slate-500 dark:text-slate-400">No conversations yet</p>
                            <p class="text-xs text-slate-400 mt-1">Start one by clicking the button above</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): 
                            $isActive = $activeConvId === (int) $conv['id'];
                            $convCat = strtolower($conv['contact_role'] ?? 'student');
                            $isPeerFaculty = ($convCat === 'faculty');
                            $avatarColor = $isPeerFaculty ? '6366f1' : '10b981';
                            $contactPic = !empty($conv['contact_pic']) 
                                ? $base . '/uploads/profiles/' . htmlspecialchars($conv['contact_pic'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($conv['contact_name']) . '&background=' . $avatarColor . '&color=fff&bold=true';
                            $unreadCount = (int) ($conv['unread_count'] ?? 0);
                        ?>
                            <a href="<?= $base ?>/views/faculty/messages.php?conv=<?= $conv['id'] ?>" 
                               data-category="<?= $convCat ?>"
                               data-unread="<?= $unreadCount > 0 ? 'true' : 'false' ?>"
                               data-search-text="<?= htmlspecialchars(strtolower($conv['contact_name'] . ' ' . ($conv['last_message'] ?? '') . ' ' . ($conv['contact_roll'] ?? ''))) ?>"
                               class="conv-item flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors <?= $isActive ? 'bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-l-emerald-500' : '' ?>">
                                <img src="<?= $contactPic ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600 flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($conv['contact_name']) ?></span>
                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider <?= $isPeerFaculty ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' ?>">
                                                <?= $isPeerFaculty ? 'Faculty' : 'Student' ?>
                                            </span>
                                        </div>
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-emerald-600 rounded-full flex-shrink-0 animate-pulse"><?= $unreadCount ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= htmlspecialchars($conv['last_message'] ?? 'No messages yet') ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div id="no-filter-match" class="hidden p-6 text-center text-xs text-slate-400">
                        No conversations found in this filter.
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col min-w-0 <?= !$activeConvId ? 'hidden md:flex' : 'flex' ?>">
                <?php if (!$activeConvId): ?>
                    <!-- No conversation selected -->
                    <div class="flex-1 flex items-center justify-center p-8">
                        <div class="text-center">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                                <i data-lucide="message-circle" class="w-10 h-10 text-emerald-400 dark:text-emerald-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Select a conversation</h3>
                            <p class="text-sm text-slate-500 mt-1">Choose a student conversation from the sidebar or reach out to a student.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Chat Header -->
                    <div class="relative px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex items-center justify-between flex-shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <a href="<?= $base ?>/views/faculty/messages.php" class="md:hidden text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 p-1 rounded-lg">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <?php if ($isBroadcast): ?>
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white flex-shrink-0 shadow-sm">
                                    <i data-lucide="radio" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">College Admin</h3>
                                    <p class="text-[11px] text-rose-500 font-bold uppercase tracking-wider">Broadcast Channel</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                $chatContactName = $activeConversation['contact_name'] ?? 'Contact';
                                $chatContactRole = $activeConversation['contact_role'] ?? 'STUDENT';
                                $chatContactSubtitle = $activeConversation['contact_subtitle'] ?? ($chatContactRole === 'FACULTY' ? 'Faculty Member' : 'Student');
                                $avatarColor = ($chatContactRole === 'FACULTY') ? '6366f1' : '10b981';
                                $chatContactPic = !empty($activeConversation['contact_pic'])
                                    ? $base . '/uploads/profiles/' . htmlspecialchars($activeConversation['contact_pic'])
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($chatContactName) . '&background=' . $avatarColor . '&color=fff&bold=true';
                                ?>
                                <img src="<?= $chatContactPic ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-600 flex-shrink-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($chatContactName) ?></h3>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider <?= $chatContactRole === 'FACULTY' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' ?>">
                                            <?= $chatContactRole === 'FACULTY' ? 'Faculty' : 'Student' ?>
                                        </span>
                                    </div>
                                    <p class="text-[11px] <?= $chatContactRole === 'FACULTY' ? 'text-indigo-600 dark:text-indigo-400' : 'text-emerald-600 dark:text-emerald-400' ?> font-medium truncate"><?= htmlspecialchars($chatContactSubtitle) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Header Controls -->
                        <div class="flex items-center gap-1">
                            <?php if (!$isBroadcast): ?>
                                <!-- In-chat Search Toggle -->
                                <button type="button" onclick="toggleSearch()" title="Search in chat" class="p-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                    <i data-lucide="search" class="w-4 h-4"></i>
                                </button>

                                <!-- Three Dot Options Menu -->
                                <div class="relative" id="chat-menu-container">
                                    <button type="button" onclick="toggleChatMenu()" title="More options" class="p-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                        <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                    </button>
                                    <div id="chat-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1.5 z-30">
                                        <button type="button" onclick="startMultiSelect()" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                            <i data-lucide="check-square" class="w-4 h-4 text-slate-400"></i> Select Messages
                                        </button>
                                        <button type="button" onclick="openStarredDrawer()" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                            <i data-lucide="star" class="w-4 h-4 text-amber-500"></i> Starred Messages
                                        </button>
                                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                                        <a href="<?= $base ?>/controllers/process_message.php?action=export_transcript&conv=<?= $activeConvId ?>&format=txt" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                            <i data-lucide="download" class="w-4 h-4 text-slate-400"></i> Export Text (.txt)
                                        </a>
                                        <a href="<?= $base ?>/controllers/process_message.php?action=export_transcript&conv=<?= $activeConvId ?>&format=html" target="_blank" class="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                            <i data-lucide="printer" class="w-4 h-4 text-slate-400"></i> Print / PDF Transcript
                                        </a>
                                        <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>
                                        <button type="button" onclick="openClearChatModal()" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i> Clear Chat (For Me)
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- In-Chat Search Bar (Expandable) -->
                    <div id="search-bar" class="hidden px-4 py-2 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2 transition-all">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        <input type="text" id="search-input" placeholder="Search in this conversation..." oninput="handleSearch(this.value)" class="flex-1 bg-transparent border-0 text-xs text-slate-900 dark:text-white focus:ring-0 placeholder:text-slate-400">
                        <span id="search-count" class="text-[11px] text-slate-400 font-medium"></span>
                        <button type="button" onclick="closeSearch()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <!-- Pinned Messages Banner -->
                    <div id="pinned-banner" class="<?= empty($pinnedMessages) ? 'hidden' : '' ?> px-4 py-2 bg-amber-50/90 dark:bg-amber-900/30 border-b border-amber-200/80 dark:border-amber-800/50 flex items-center justify-between text-xs text-amber-900 dark:text-amber-200 transition-all">
                        <div class="flex items-center gap-2 min-w-0 cursor-pointer" onclick="jumpToPinnedMessage()">
                            <i data-lucide="pin" class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0"></i>
                            <span class="font-bold text-[11px] uppercase tracking-wider text-amber-700 dark:text-amber-300">Pinned:</span>
                            <span id="pinned-preview" class="truncate font-medium"><?= !empty($pinnedMessages) ? htmlspecialchars($pinnedMessages[0]['content']) : '' ?></span>
                        </div>
                        <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold cursor-pointer" onclick="jumpToPinnedMessage()">View</span>
                    </div>

                    <!-- Messages Container -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                        <?php if (empty($activeMessages)): ?>
                            <div class="text-center py-10" id="no-messages-placeholder">
                                <i data-lucide="message-square" class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2"></i>
                                <p class="text-sm text-slate-400">No messages yet. Send a note or answer student doubts!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activeMessages as $msg): 
                                $isMine = !$isBroadcast && ($msg['sender_type'] === 'FACULTY' && (int) $msg['sender_id'] === $userId);
                                $isUnsent = !empty($msg['is_unsent']);
                                $isStarred = !empty($msg['is_starred']);
                                $isPinned = !empty($msg['is_pinned']);
                            ?>
                                <div class="message-wrapper flex items-start gap-2 <?= $isMine ? 'justify-end' : 'justify-start' ?>" id="msg-wrapper-<?= $msg['id'] ?>" data-msg-id="<?= $msg['id'] ?>" data-mine="<?= $isMine ? 'true' : 'false' ?>">
                                    <!-- Multi-Select Checkbox -->
                                    <div class="select-checkbox-col hidden pt-2.5">
                                        <input type="checkbox" class="msg-select-check w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-600" value="<?= $msg['id'] ?>" onchange="updateSelectedCount()">
                                    </div>

                                    <!-- Message Bubble Container -->
                                    <div class="relative group max-w-[78%] sm:max-w-[70%]">
                                        <div class="message-bubble relative rounded-2xl px-4 py-2.5 shadow-sm transition-all <?= $isMine 
                                            ? ($isUnsent ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 italic rounded-br-md border border-slate-300 dark:border-slate-600' : 'bg-emerald-600 text-white rounded-br-md')
                                            : ($isUnsent ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 italic rounded-bl-md border border-slate-200 dark:border-slate-700' : 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-bl-md border border-slate-200 dark:border-slate-600') ?>" id="msg-bubble-<?= $msg['id'] ?>">

                                            <!-- Pinned indicator -->
                                            <?php if ($isPinned && !$isUnsent): ?>
                                                <div class="flex items-center gap-1 text-[10px] font-bold text-amber-400 mb-1">
                                                    <i data-lucide="pin" class="w-3 h-3"></i> Pinned
                                                </div>
                                            <?php endif; ?>

                                            <!-- Admin Broadcast Header -->
                                            <?php if ($isBroadcast): ?>
                                                <div class="flex items-center justify-between mb-1">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-[10px] font-bold text-indigo-400">
                                                            <?= htmlspecialchars($msg['admin_name'] ?? 'Admin') ?>
                                                        </span>
                                                        <?php if (!empty($msg['priority']) && $msg['priority'] !== 'NORMAL'): ?>
                                                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[8px] font-bold uppercase tracking-wider <?= $msg['priority'] === 'URGENT' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' : ($msg['priority'] === 'ACADEMIC' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300') ?>">
                                                                <?= htmlspecialchars($msg['priority']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (($msg['target_type'] ?? '') !== 'ALL'): ?>
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[8px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 uppercase tracking-wider">
                                                            <i data-lucide="lock" class="w-2 h-2"></i> Targeted
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Quoted Message Card (if replying) -->
                                            <?php if (!empty($msg['reply_to_content'])): ?>
                                                <div class="mb-2 p-2 rounded-lg text-xs cursor-pointer border-l-4 <?= $isMine ? 'bg-emerald-700/60 border-emerald-300 text-emerald-100' : 'bg-slate-100 dark:bg-slate-800/80 border-emerald-500 text-slate-700 dark:text-slate-300' ?>" onclick="scrollToMessage(<?= (int)$msg['reply_to_id'] ?>)">
                                                    <div class="font-bold text-[10px] opacity-90"><?= htmlspecialchars($msg['reply_to_sender_name'] ?? 'Replying') ?></div>
                                                    <div class="truncate italic"><?= htmlspecialchars($msg['reply_to_content']) ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Content Text -->
                                            <p class="msg-content-text text-sm whitespace-pre-wrap break-words leading-relaxed"><?= htmlspecialchars($msg['content']) ?></p>

                                            <!-- Timestamp & Status -->
                                            <div class="flex items-center justify-end gap-1 text-[10px] mt-1 <?= $isMine ? ($isUnsent ? 'text-slate-400' : 'text-emerald-100') : 'text-slate-400' ?>">
                                                <?php if ($isStarred && !$isUnsent): ?>
                                                    <i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400 mr-0.5"></i>
                                                <?php endif; ?>
                                                <span><?= date('g:i A', strtotime($msg['created_at'])) ?></span>
                                                <?php if ($isMine && !$isUnsent && !empty($msg['is_read'])): ?>
                                                    <i data-lucide="check-check" class="w-3 h-3 inline-block"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Emoji Reaction Badges -->
                                        <div class="reaction-badges flex flex-wrap gap-1 mt-1 <?= $isMine ? 'justify-end' : 'justify-start' ?>" id="rx-badges-<?= $msg['id'] ?>">
                                            <?php if (!empty($msg['reactions'])): ?>
                                                <?php foreach ($msg['reactions'] as $emoji => $count): 
                                                    $userReacted = in_array($emoji, $msg['user_reactions'] ?? []);
                                                ?>
                                                    <button type="button" onclick="toggleReaction(<?= $msg['id'] ?>, '<?= $emoji ?>')" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[11px] font-medium border transition-all <?= $userReacted ? 'bg-emerald-100 border-emerald-300 dark:bg-emerald-900/50 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100' ?>">
                                                        <span><?= $emoji ?></span>
                                                        <span class="text-[10px] font-bold"><?= $count ?></span>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Hover Action Bar (only for non-broadcast, non-unsent) -->
                                        <?php if (!$isBroadcast && !$isUnsent): ?>
                                            <div class="msg-action-bar hidden group-hover:flex items-center gap-0.5 absolute -top-3 <?= $isMine ? 'right-2' : 'left-2' ?> bg-white dark:bg-slate-800 rounded-lg shadow-md border border-slate-200 dark:border-slate-700 p-0.5 z-20">
                                                <!-- Reactions Trigger -->
                                                <div class="relative group/rx">
                                                    <button type="button" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                                        <i data-lucide="smile" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <div class="hidden group-hover/rx:flex items-center gap-1 absolute bottom-full mb-1 left-0 bg-white dark:bg-slate-800 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 p-1 z-30">
                                                        <?php foreach (['👍', '❤️', '💡', '❓', '✅', '👏'] as $em): ?>
                                                            <button type="button" onclick="toggleReaction(<?= $msg['id'] ?>, '<?= $em ?>')" class="hover:scale-125 transition-transform px-1 text-sm"><?= $em ?></button>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Reply Button -->
                                                <button type="button" onclick="setReply(<?= $msg['id'] ?>, '<?= htmlspecialchars(addslashes($msg['sender_name'] ?? 'User')) ?>', '<?= htmlspecialchars(addslashes(mb_substr($msg['content'], 0, 70))) ?>')" title="Reply" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                                    <i data-lucide="reply" class="w-3.5 h-3.5"></i>
                                                </button>

                                                <!-- Star Button -->
                                                <button type="button" onclick="toggleStar(<?= $msg['id'] ?>)" title="Bookmark" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded <?= $isStarred ? 'text-amber-500 fill-amber-500' : 'text-slate-600 dark:text-slate-300' ?>" id="star-btn-<?= $msg['id'] ?>">
                                                    <i data-lucide="star" class="w-3.5 h-3.5"></i>
                                                </button>

                                                <!-- Dropdown Menu for Delete / Unsend / Pin -->
                                                <div class="relative" id="msg-more-<?= $msg['id'] ?>">
                                                    <button type="button" onclick="toggleMsgMore(<?= $msg['id'] ?>)" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                                        <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <div id="msg-more-menu-<?= $msg['id'] ?>" class="hidden absolute right-0 bottom-full mb-1 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30">
                                                        <button type="button" onclick="togglePin(<?= $msg['id'] ?>)" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                            <i data-lucide="pin" class="w-3 h-3 text-amber-500"></i> <?= $isPinned ? 'Unpin' : 'Pin to chat' ?>
                                                        </button>
                                                        <button type="button" onclick="deleteForMe([<?= $msg['id'] ?>])" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                            <i data-lucide="trash" class="w-3 h-3 text-slate-400"></i> Delete for me
                                                        </button>
                                                        <?php if ($isMine): ?>
                                                            <button type="button" onclick="unsendMessages([<?= $msg['id'] ?>])" class="w-full text-left px-3 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                                                                <i data-lucide="undo-2" class="w-3 h-3"></i> Unsend
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Floating Multi-Select Toolbar -->
                    <div id="multi-select-toolbar" class="hidden px-4 py-3 bg-slate-900 text-white flex items-center justify-between shadow-2xl z-20 border-t border-slate-700">
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="cancelMultiSelect()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                            <span id="selected-count-label" class="text-xs font-bold tracking-wide">0 messages selected</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="batchDeleteForMe()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold border border-slate-600 transition-all">
                                <i data-lucide="trash" class="w-3.5 h-3.5"></i> Delete for me
                            </button>
                            <button type="button" id="batch-unsend-btn" onclick="batchUnsend()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                                <i data-lucide="undo-2" class="w-3.5 h-3.5"></i> Unsend
                            </button>
                        </div>
                    </div>

                    <!-- Reply Preview Bar (when replying to a message) -->
                    <div id="reply-preview-bar" class="hidden px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 border-t border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-xs text-emerald-900 dark:text-emerald-200">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="reply" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
                            <span class="font-bold text-[11px]" id="reply-sender-name">Replying to:</span>
                            <span class="truncate italic text-slate-600 dark:text-slate-300" id="reply-content-preview"></span>
                        </div>
                        <button type="button" onclick="cancelReply()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Message Composer -->
                    <?php if ($isBroadcast): ?>
                        <div class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-3 flex-shrink-0 text-center">
                            <p class="text-xs text-slate-400 flex items-center justify-center gap-2">
                                <i data-lucide="info" class="w-4 h-4"></i> This is a read-only broadcast channel
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-3 flex-shrink-0" id="msg-composer-wrapper">
                            <form id="msg-form" class="flex items-end gap-2">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="conversation_id" value="<?= $activeConvId ?>">
                                <input type="hidden" name="action" value="send">
                                <input type="hidden" name="reply_to_id" id="reply-to-id-input" value="">

                                <textarea id="msg-input" name="content" rows="1" 
                                    placeholder="Type your reply or advice..." 
                                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm resize-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all placeholder:text-slate-400 max-h-32"
                                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
                                <button type="button" onclick="sendMessage()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-lg shadow-emerald-500/25 transition-all hover:scale-105">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Clear Chat Confirmation Modal -->
<div id="clear-chat-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeClearChatModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-5 z-10">
        <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white text-center">Clear Chat History?</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-1.5 leading-relaxed">
            This will clear messages in this conversation <strong>for your account only</strong>. The student will still keep their conversation history.
        </p>
        <div class="flex items-center gap-2 mt-5">
            <button type="button" onclick="closeClearChatModal()" class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition-colors">
                Cancel
            </button>
            <button type="button" onclick="confirmClearChat()" class="flex-1 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/25">
                Clear Chat
            </button>
        </div>
    </div>
</div>

<!-- Starred Messages Slide-Over Drawer -->
<div id="starred-drawer" class="hidden fixed inset-0 z-50 flex justify-end">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-xs" onclick="closeStarredDrawer()"></div>
    <div class="relative w-full max-w-md bg-white dark:bg-slate-800 h-full shadow-2xl border-l border-slate-200 dark:border-slate-700 flex flex-col z-10">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-800/80">
            <div class="flex items-center gap-2">
                <i data-lucide="star" class="w-5 h-5 fill-amber-400 text-amber-500"></i>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Starred Messages</h3>
            </div>
            <button onclick="closeStarredDrawer()" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="starred-list-container">
            <div class="text-center py-10 text-slate-400 text-xs">Loading saved messages...</div>
        </div>
    </div>
</div>

<!-- New Conversation Modal (With Students & Faculty Tabs) -->
<div id="new-conv-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('new-conv-modal').classList.add('hidden')"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md max-h-[82vh] flex flex-col overflow-hidden z-10">
        <!-- Modal Header -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-emerald-500"></i> New Conversation
            </h3>
            <button onclick="document.getElementById('new-conv-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Contact Search & Filter Tabs -->
        <div class="p-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 space-y-2">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                <input type="text" id="contact-search-input" placeholder="Search students (by name, roll) or faculty..." oninput="filterModalContacts(this.value)" class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 placeholder:text-slate-400">
            </div>
            
            <?php 
            $studentsCount = count(array_filter($contacts, fn($c) => ($c['category'] ?? '') === 'Student'));
            $facultyCount = count(array_filter($contacts, fn($c) => ($c['category'] ?? '') === 'Faculty'));
            ?>
            <div class="flex items-center gap-1.5 text-xs font-semibold" id="modal-filter-pills">
                <button type="button" onclick="switchModalTab('all', this)" class="modal-tab-btn px-2.5 py-1 rounded-lg bg-emerald-600 text-white shadow-xs">
                    All (<?= count($contacts) ?>)
                </button>
                <button type="button" onclick="switchModalTab('Student', this)" class="modal-tab-btn px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300">
                    Students (<?= $studentsCount ?>)
                </button>
                <button type="button" onclick="switchModalTab('Faculty', this)" class="modal-tab-btn px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-300">
                    Faculty (<?= $facultyCount ?>)
                </button>
            </div>
        </div>

        <!-- Contacts List -->
        <div class="p-4 overflow-y-auto flex-1 space-y-2" id="modal-contacts-list">
            <?php if (empty($contacts)): ?>
                <p class="text-sm text-slate-500 text-center py-6">No eligible contacts found.</p>
            <?php else: ?>
                <?php foreach ($contacts as $contact): 
                    $isPeerFaculty = ($contact['category'] ?? '') === 'Faculty';
                    $avatarColor = $isPeerFaculty ? '6366f1' : '10b981';
                    $contactPic = !empty($contact['profile_pic']) 
                        ? $base . '/uploads/profiles/' . htmlspecialchars($contact['profile_pic'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($contact['name']) . '&background=' . $avatarColor . '&color=fff&bold=true';
                ?>
                    <form action="<?= $base ?>/controllers/process_message.php" method="POST" class="modal-contact-item" data-category="<?= htmlspecialchars($contact['category'] ?? '') ?>" data-search-text="<?= htmlspecialchars(strtolower($contact['name'] . ' ' . ($contact['roll_number'] ?? '') . ' ' . ($contact['email'] ?? '') . ' ' . ($contact['subtitle'] ?? ''))) ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="start_conversation">
                        <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                        <input type="hidden" name="contact_type" value="<?= $contact['role'] ?>">

                        <button type="submit" class="w-full flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600 transition-all text-left group">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="<?= $contactPic ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-600 flex-shrink-0">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"><?= htmlspecialchars($contact['name']) ?></p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($contact['subtitle'] ?? $contact['email']) ?></p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $isPeerFaculty ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' ?>">
                                <?= htmlspecialchars($contact['category'] ?? 'Contact') ?>
                            </span>
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
            <div id="modal-no-match" class="hidden text-center py-6 text-xs text-slate-400">
                No matching contacts found.
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= $base ?>';
const ACTIVE_CONV = '<?= $activeConvId ?>';
const CSRF_TOKEN = '<?= $csrfToken ?>';
const CURRENT_USER_ID = <?= $userId ?>;
const CURRENT_USER_ROLE = 'FACULTY';

let isMultiSelectMode = false;
let activeModalCategory = 'all';
let currentConvTab = 'all';

// ── Tab Filtering (Sidebar) ───────────────────────────────────
function filterConversations(tab, btn) {
    currentConvTab = tab;
    document.querySelectorAll('.conv-tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'dark:bg-slate-800', 'text-emerald-600', 'dark:text-emerald-400', 'shadow-sm');
        b.classList.add('hover:text-slate-900', 'dark:hover:text-white');
    });
    btn.classList.add('bg-white', 'dark:bg-slate-800', 'text-emerald-600', 'dark:text-emerald-400', 'shadow-sm');
    btn.classList.remove('hover:text-slate-900', 'dark:hover:text-white');

    applySidebarFilters();
}

// ── Sidebar Chat Search ───────────────────────────────────────
function handleSidebarSearch(query) {
    const clearBtn = document.getElementById('sidebar-search-clear');
    if (clearBtn) {
        if (query && query.trim().length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }
    applySidebarFilters();
}

function clearSidebarSearch() {
    const input = document.getElementById('sidebar-search-input');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('sidebar-search-clear');
    if (clearBtn) clearBtn.classList.add('hidden');
    applySidebarFilters();
}

function applySidebarFilters() {
    const searchVal = (document.getElementById('sidebar-search-input')?.value || '').trim().toLowerCase();
    const items = document.querySelectorAll('#conv-list .conv-item');
    let visibleCount = 0;

    items.forEach(item => {
        const cat = item.getAttribute('data-category');
        const isUnread = item.getAttribute('data-unread') === 'true';
        const searchText = item.getAttribute('data-search-text') || '';

        let matchesTab = false;
        if (currentConvTab === 'all') {
            matchesTab = true;
        } else if (currentConvTab === 'unread') {
            matchesTab = isUnread;
        } else if (currentConvTab === 'student') {
            matchesTab = (cat === 'student');
        } else if (currentConvTab === 'faculty') {
            matchesTab = (cat === 'faculty');
        }

        const matchesSearch = (!searchVal) || searchText.includes(searchVal);

        if (matchesTab && matchesSearch) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    const noMatch = document.getElementById('no-filter-match');
    if (noMatch) {
        if (visibleCount === 0) {
            noMatch.textContent = searchVal ? 'No conversations found matching your search.' : 'No conversations found in this filter.';
            noMatch.classList.remove('hidden');
        } else {
            noMatch.classList.add('hidden');
        }
    }
}

// ── Modal Tab Filtering & Search ──────────────────────────────
function switchModalTab(category, btn) {
    activeModalCategory = category;
    document.querySelectorAll('.modal-tab-btn').forEach(b => {
        b.classList.remove('bg-emerald-600', 'text-white', 'shadow-xs');
        b.classList.add('bg-slate-200', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300');
    });
    btn.classList.add('bg-emerald-600', 'text-white', 'shadow-xs');
    btn.classList.remove('bg-slate-200', 'dark:bg-slate-700', 'text-slate-700', 'dark:text-slate-300');

    const searchVal = document.getElementById('contact-search-input').value;
    filterModalContacts(searchVal);
}

function filterModalContacts(searchVal) {
    searchVal = (searchVal || '').trim().toLowerCase();
    const items = document.querySelectorAll('#modal-contacts-list .modal-contact-item');
    let visible = 0;

    items.forEach(item => {
        const cat = item.getAttribute('data-category');
        const text = item.getAttribute('data-search-text') || '';

        const matchesCat = (activeModalCategory === 'all') || (cat === activeModalCategory);
        const matchesSearch = (!searchVal) || text.includes(searchVal);

        if (matchesCat && matchesSearch) {
            item.classList.remove('hidden');
            visible++;
        } else {
            item.classList.add('hidden');
        }
    });

    const noMatch = document.getElementById('modal-no-match');
    if (noMatch) {
        if (visible === 0) noMatch.classList.remove('hidden');
        else noMatch.classList.add('hidden');
    }
}

// ── In-Chat Search ────────────────────────────────────────────
function toggleSearch() {
    const bar = document.getElementById('search-bar');
    if (bar.classList.contains('hidden')) {
        bar.classList.remove('hidden');
        document.getElementById('search-input').focus();
    } else {
        closeSearch();
    }
}

function closeSearch() {
    const bar = document.getElementById('search-bar');
    if (bar) bar.classList.add('hidden');
    const input = document.getElementById('search-input');
    if (input) input.value = '';
    handleSearch('');
}

function handleSearch(term) {
    term = term.trim().toLowerCase();
    const wrappers = document.querySelectorAll('.message-wrapper');
    let matches = 0;

    wrappers.forEach(w => {
        const textEl = w.querySelector('.msg-content-text');
        if (!textEl) return;
        const text = textEl.textContent.toLowerCase();

        if (!term) {
            w.classList.remove('opacity-30', 'bg-yellow-50', 'dark:bg-yellow-950/30');
            return;
        }

        if (text.includes(term)) {
            w.classList.remove('opacity-30');
            w.classList.add('bg-yellow-50/50', 'dark:bg-yellow-950/20');
            matches++;
        } else {
            w.classList.add('opacity-30');
            w.classList.remove('bg-yellow-50/50', 'dark:bg-yellow-950/20');
        }
    });

    const countEl = document.getElementById('search-count');
    if (countEl) {
        countEl.textContent = term ? `${matches} match${matches === 1 ? '' : 'es'}` : '';
    }
}

// ── Header Dropdown Menu ──────────────────────────────────────
function toggleChatMenu() {
    const menu = document.getElementById('chat-dropdown-menu');
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', (e) => {
    const container = document.getElementById('chat-menu-container');
    if (container && !container.contains(e.target)) {
        const menu = document.getElementById('chat-dropdown-menu');
        if (menu) menu.classList.add('hidden');
    }
});

// ── Quoting & Reply ───────────────────────────────────────────
function setReply(msgId, senderName, snippet) {
    document.getElementById('reply-to-id-input').value = msgId;
    document.getElementById('reply-sender-name').textContent = 'Replying to ' + senderName;
    document.getElementById('reply-content-preview').textContent = '"' + snippet + '"';
    document.getElementById('reply-preview-bar').classList.remove('hidden');
    document.getElementById('msg-input').focus();
}

function cancelReply() {
    document.getElementById('reply-to-id-input').value = '';
    document.getElementById('reply-preview-bar').classList.add('hidden');
}

function scrollToMessage(msgId) {
    const el = document.getElementById('msg-wrapper-' + msgId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('ring-2', 'ring-emerald-400');
        setTimeout(() => el.classList.remove('ring-2', 'ring-emerald-400'), 2000);
    }
}

// ── Emoji Reactions ───────────────────────────────────────────
function toggleReaction(msgId, reaction) {
    const fd = new FormData();
    fd.append('action', 'toggle_reaction');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('message_id', msgId);
    fd.append('reaction', reaction);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            refreshMessages();
        }
    });
}

// ── Star / Bookmark ───────────────────────────────────────────
function toggleStar(msgId) {
    const fd = new FormData();
    fd.append('action', 'toggle_star');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('message_id', msgId);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            refreshMessages();
        }
    });
}

function openStarredDrawer() {
    const drawer = document.getElementById('starred-drawer');
    drawer.classList.remove('hidden');
    const container = document.getElementById('starred-list-container');
    container.innerHTML = '<div class="text-center py-10 text-slate-400 text-xs">Loading saved messages...</div>';

    fetch(BASE_URL + '/controllers/process_message.php?action=get_starred', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success && data.starred) {
            if (data.starred.length === 0) {
                container.innerHTML = '<div class="text-center py-12 text-slate-400 text-xs"><i data-lucide="star-off" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>No saved messages yet. Click the star icon on any message to save it here.</div>';
                lucide.createIcons();
                return;
            }
            let html = '';
            data.starred.forEach(s => {
                const date = new Date(s.message_created_at).toLocaleDateString([], {month: 'short', day: 'numeric'});
                html += `
                    <div class="p-3 bg-slate-50 dark:bg-slate-700/60 rounded-xl border border-slate-200 dark:border-slate-600 text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white">${escapeHtml(s.sender_name || s.sender_type)}</span>
                            <span class="text-[10px] text-slate-400">${date}</span>
                        </div>
                        <p class="text-slate-600 dark:text-slate-300 whitespace-pre-wrap">${escapeHtml(s.content)}</p>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-200/50 dark:border-slate-600/50">
                            <a href="${BASE_URL}/views/faculty/messages.php?conv=${s.conversation_id}#msg-wrapper-${s.message_id}" onclick="closeStarredDrawer()" class="text-emerald-600 dark:text-emerald-400 font-semibold hover:underline flex items-center gap-1">
                                Jump to chat &rarr;
                            </a>
                            <button type="button" onclick="toggleStar(${s.message_id}); openStarredDrawer();" class="text-slate-400 hover:text-rose-500 text-[11px]">
                                Remove
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            lucide.createIcons();
        }
    });
}

function closeStarredDrawer() {
    document.getElementById('starred-drawer').classList.add('hidden');
}

// ── Pinning ───────────────────────────────────────────────────
function togglePin(msgId) {
    const fd = new FormData();
    fd.append('action', 'toggle_pin');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('message_id', msgId);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            refreshMessages();
        }
    });
}

function jumpToPinnedMessage() {
    const pinnedEl = document.querySelector('[id^="msg-bubble-"] [data-lucide="pin"]');
    if (pinnedEl) {
        const wrapper = pinnedEl.closest('.message-wrapper');
        if (wrapper) {
            wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            wrapper.classList.add('ring-2', 'ring-amber-400');
            setTimeout(() => wrapper.classList.remove('ring-2', 'ring-amber-400'), 2000);
        }
    }
}

// ── Message Sub-menu toggle ───────────────────────────────────
function toggleMsgMore(msgId) {
    const menu = document.getElementById('msg-more-menu-' + msgId);
    if (menu) menu.classList.toggle('hidden');
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('[id^="msg-more-"]')) {
        document.querySelectorAll('[id^="msg-more-menu-"]').forEach(m => m.classList.add('hidden'));
    }
});

// ── Multi-Select Batch Actions ────────────────────────────────
function startMultiSelect() {
    isMultiSelectMode = true;
    document.getElementById('chat-dropdown-menu').classList.add('hidden');
    document.querySelectorAll('.select-checkbox-col').forEach(c => c.classList.remove('hidden'));
    document.getElementById('multi-select-toolbar').classList.remove('hidden');
    updateSelectedCount();
}

function cancelMultiSelect() {
    isMultiSelectMode = false;
    document.querySelectorAll('.select-checkbox-col').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.msg-select-check').forEach(cb => cb.checked = false);
    document.getElementById('multi-select-toolbar').classList.add('hidden');
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.msg-select-check:checked');
    const count = checked.length;
    document.getElementById('selected-count-label').textContent = `${count} message${count === 1 ? '' : 's'} selected`;

    const unsendBtn = document.getElementById('batch-unsend-btn');
    if (!unsendBtn) return;

    if (count === 0) {
        unsendBtn.disabled = true;
        return;
    }

    let allMine = true;
    checked.forEach(cb => {
        const wrapper = cb.closest('.message-wrapper');
        if (wrapper && wrapper.getAttribute('data-mine') !== 'true') {
            allMine = false;
        }
    });

    unsendBtn.disabled = !allMine;
    unsendBtn.title = allMine ? 'Unsend selected messages for everyone' : 'Only your own messages can be unsent';
}

function batchDeleteForMe() {
    const checked = Array.from(document.querySelectorAll('.msg-select-check:checked')).map(c => parseInt(c.value));
    if (checked.length === 0) return;

    if (!confirm(`Delete ${checked.length} message(s) for yourself?`)) return;
    deleteForMe(checked);
}

function batchUnsend() {
    const checked = Array.from(document.querySelectorAll('.msg-select-check:checked')).map(c => parseInt(c.value));
    if (checked.length === 0) return;

    if (!confirm(`Unsend ${checked.length} message(s) for everyone?`)) return;
    unsendMessages(checked);
}

function deleteForMe(messageIds) {
    const fd = new FormData();
    fd.append('action', 'delete_for_me');
    fd.append('csrf_token', CSRF_TOKEN);
    messageIds.forEach(id => fd.append('message_ids[]', id));

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            cancelMultiSelect();
            refreshMessages();
        }
    });
}

function unsendMessages(messageIds) {
    const fd = new FormData();
    fd.append('action', 'unsend');
    fd.append('csrf_token', CSRF_TOKEN);
    messageIds.forEach(id => fd.append('message_ids[]', id));

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            cancelMultiSelect();
            refreshMessages();
        }
    });
}

// ── Clear Chat (For Me) ───────────────────────────────────────
function openClearChatModal() {
    document.getElementById('chat-dropdown-menu').classList.add('hidden');
    document.getElementById('clear-chat-modal').classList.remove('hidden');
}

function closeClearChatModal() {
    document.getElementById('clear-chat-modal').classList.add('hidden');
}

function confirmClearChat() {
    const fd = new FormData();
    fd.append('action', 'clear_chat');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('conversation_id', ACTIVE_CONV);

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        closeClearChatModal();
        if (data.success) {
            window.location.reload();
        }
    });
}

// ── Send Message ──────────────────────────────────────────────
function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;
}

function sendMessage() {
    const input = document.getElementById('msg-input');
    const content = input.value.trim();
    if (!content) return;

    const form = document.getElementById('msg-form');
    const formData = new FormData(form);

    input.value = '';
    input.style.height = 'auto';
    cancelReply();

    fetch(BASE_URL + '/controllers/process_message.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            refreshMessages(true);
        }
    }).catch(() => {});
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// ── Fetch & Reconcile Messages ────────────────────────────────
function refreshMessages(forceScroll = false) {
    if (!ACTIVE_CONV || ACTIVE_CONV === 'broadcast') return;

    fetch(BASE_URL + '/controllers/process_message.php?action=fetch&conv=' + ACTIVE_CONV, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderMessages(data.messages || [], data.pinned || [], forceScroll);
        }
    }).catch(() => {});
}

function renderMessages(messages, pinned, forceScroll) {
    const container = document.getElementById('messages-container');
    if (!container) return;

    // Update pinned banner
    const banner = document.getElementById('pinned-banner');
    const preview = document.getElementById('pinned-preview');
    if (banner && preview) {
        if (pinned && pinned.length > 0) {
            preview.textContent = pinned[0].content;
            banner.classList.remove('hidden');
        } else {
            banner.classList.add('hidden');
        }
    }

    if (messages.length === 0) {
        container.innerHTML = `
            <div class="text-center py-10" id="no-messages-placeholder">
                <i data-lucide="message-square" class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2"></i>
                <p class="text-sm text-slate-400">No messages yet. Send a note or answer student doubts!</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    const wasAtBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 100);

    let html = '';
    messages.forEach(msg => {
        const isMine = msg.sender_type === 'FACULTY' && parseInt(msg.sender_id) === CURRENT_USER_ID;
        const isUnsent = !!msg.is_unsent;
        const isStarred = !!msg.is_starred;
        const isPinned = !!msg.is_pinned;
        const time = new Date(msg.created_at).toLocaleTimeString([], {hour: 'numeric', minute: '2-digit'});

        let reactionsHtml = '';
        if (msg.reactions && Object.keys(msg.reactions).length > 0) {
            for (const [emoji, cnt] of Object.entries(msg.reactions)) {
                const userReacted = (msg.user_reactions || []).includes(emoji);
                reactionsHtml += `
                    <button type="button" onclick="toggleReaction(${msg.id}, '${emoji}')" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[11px] font-medium border transition-all ${userReacted ? 'bg-emerald-100 border-emerald-300 dark:bg-emerald-900/50 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100'}">
                        <span>${emoji}</span>
                        <span class="text-[10px] font-bold">${cnt}</span>
                    </button>
                `;
            }
        }

        let quoteHtml = '';
        if (msg.reply_to_content) {
            quoteHtml = `
                <div class="mb-2 p-2 rounded-lg text-xs cursor-pointer border-l-4 ${isMine ? 'bg-emerald-700/60 border-emerald-300 text-emerald-100' : 'bg-slate-100 dark:bg-slate-800/80 border-emerald-500 text-slate-700 dark:text-slate-300'}" onclick="scrollToMessage(${msg.reply_to_id})">
                    <div class="font-bold text-[10px] opacity-90">${escapeHtml(msg.reply_to_sender_name || 'Replying')}</div>
                    <div class="truncate italic">${escapeHtml(msg.reply_to_content)}</div>
                </div>
            `;
        }

        html += `
            <div class="message-wrapper flex items-start gap-2 ${isMine ? 'justify-end' : 'justify-start'}" id="msg-wrapper-${msg.id}" data-msg-id="${msg.id}" data-mine="${isMine ? 'true' : 'false'}">
                <div class="select-checkbox-col ${isMultiSelectMode ? '' : 'hidden'} pt-2.5">
                    <input type="checkbox" class="msg-select-check w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-600" value="${msg.id}" onchange="updateSelectedCount()">
                </div>
                <div class="relative group max-w-[78%] sm:max-w-[70%]">
                    <div class="message-bubble relative rounded-2xl px-4 py-2.5 shadow-sm transition-all ${isMine 
                        ? (isUnsent ? 'bg-slate-200 dark:bg-slate-700 text-slate-500 italic rounded-br-md border border-slate-300 dark:border-slate-600' : 'bg-emerald-600 text-white rounded-br-md')
                        : (isUnsent ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 italic rounded-bl-md border border-slate-200 dark:border-slate-700' : 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-bl-md border border-slate-200 dark:border-slate-600')}" id="msg-bubble-${msg.id}">
                        ${isPinned && !isUnsent ? '<div class="flex items-center gap-1 text-[10px] font-bold text-amber-400 mb-1"><i data-lucide="pin" class="w-3 h-3"></i> Pinned</div>' : ''}
                        ${quoteHtml}
                        <p class="msg-content-text text-sm whitespace-pre-wrap break-words leading-relaxed">${escapeHtml(msg.content)}</p>
                        <div class="flex items-center justify-end gap-1 text-[10px] mt-1 ${isMine ? (isUnsent ? 'text-slate-400' : 'text-emerald-100') : 'text-slate-400'}">
                            ${isStarred && !isUnsent ? '<i data-lucide="star" class="w-3 h-3 fill-amber-400 text-amber-400 mr-0.5"></i>' : ''}
                            <span>${time}</span>
                            ${isMine && !isUnsent && msg.is_read ? '<i data-lucide="check-check" class="w-3 h-3 inline-block"></i>' : ''}
                        </div>
                    </div>
                    <div class="reaction-badges flex flex-wrap gap-1 mt-1 ${isMine ? 'justify-end' : 'justify-start'}" id="rx-badges-${msg.id}">
                        ${reactionsHtml}
                    </div>
                    ${!isUnsent ? `
                        <div class="msg-action-bar hidden group-hover:flex items-center gap-0.5 absolute -top-3 ${isMine ? 'right-2' : 'left-2'} bg-white dark:bg-slate-800 rounded-lg shadow-md border border-slate-200 dark:border-slate-700 p-0.5 z-20">
                            <div class="relative group/rx">
                                <button type="button" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                    <i data-lucide="smile" class="w-3.5 h-3.5"></i>
                                </button>
                                <div class="hidden group-hover/rx:flex items-center gap-1 absolute bottom-full mb-1 left-0 bg-white dark:bg-slate-800 rounded-full shadow-lg border border-slate-200 dark:border-slate-700 p-1 z-30">
                                    <button type="button" onclick="toggleReaction(${msg.id}, '👍')" class="hover:scale-125 transition-transform px-1 text-sm">👍</button>
                                    <button type="button" onclick="toggleReaction(${msg.id}, '❤️')" class="hover:scale-125 transition-transform px-1 text-sm">❤️</button>
                                    <button type="button" onclick="toggleReaction(${msg.id}, '💡')" class="hover:scale-125 transition-transform px-1 text-sm">💡</button>
                                    <button type="button" onclick="toggleReaction(${msg.id}, '❓')" class="hover:scale-125 transition-transform px-1 text-sm">❓</button>
                                    <button type="button" onclick="toggleReaction(${msg.id}, '✅')" class="hover:scale-125 transition-transform px-1 text-sm">✅</button>
                                    <button type="button" onclick="toggleReaction(${msg.id}, '👏')" class="hover:scale-125 transition-transform px-1 text-sm">👏</button>
                                </div>
                            </div>
                            <button type="button" onclick="setReply(${msg.id}, '${escapeHtml(msg.sender_name || 'User')}', '${escapeHtml((msg.content || '').substring(0, 70))}')" title="Reply" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                <i data-lucide="reply" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="toggleStar(${msg.id})" title="Bookmark" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded ${isStarred ? 'text-amber-500 fill-amber-500' : 'text-slate-600 dark:text-slate-300'}">
                                <i data-lucide="star" class="w-3.5 h-3.5"></i>
                            </button>
                            <div class="relative" id="msg-more-${msg.id}">
                                <button type="button" onclick="toggleMsgMore(${msg.id})" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-slate-600 dark:text-slate-300">
                                    <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                </button>
                                <div id="msg-more-menu-${msg.id}" class="hidden absolute right-0 bottom-full mb-1 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1 z-30">
                                    <button type="button" onclick="togglePin(${msg.id})" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <i data-lucide="pin" class="w-3 h-3 text-amber-500"></i> ${isPinned ? 'Unpin' : 'Pin to chat'}
                                    </button>
                                    <button type="button" onclick="deleteForMe([${msg.id}])" class="w-full text-left px-3 py-1.5 text-xs text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                        <i data-lucide="trash" class="w-3 h-3 text-slate-400"></i> Delete for me
                                    </button>
                                    ${isMine ? `
                                        <button type="button" onclick="unsendMessages([${msg.id}])" class="w-full text-left px-3 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                                            <i data-lucide="undo-2" class="w-3 h-3"></i> Unsend
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    lucide.createIcons();

    if (forceScroll || wasAtBottom) {
        scrollToBottom();
    }
}

// ── Initialization & Polling ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    scrollToBottom();

    // Auto-resize textarea
    const input = document.getElementById('msg-input');
    if (input) {
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 128) + 'px';
        });
    }

    // Count unread conversations for tab badge
    const unreadItems = document.querySelectorAll('#conv-list .conv-item[data-unread="true"]');
    const badge = document.getElementById('tab-unread-badge');
    if (badge && unreadItems.length > 0) {
        badge.textContent = unreadItems.length;
        badge.classList.remove('hidden');
    }

    // Poll every 4 seconds
    if (ACTIVE_CONV && ACTIVE_CONV !== 'broadcast') {
        setInterval(() => {
            if (!isMultiSelectMode) {
                refreshMessages(false);
            }
        }, 4000);
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
