<?php
// views/admin/edit-profile.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('ADMIN');
$pageTitle = 'Edit Profile | College Management System';

use Config\Database;
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Invalid CSRF token.', 'error');
        redirect('/views/admin/edit-profile.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_details') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name)) {
            set_flash_message('Name cannot be empty.', 'error');
        } else {
            $stmt = $db->prepare("UPDATE admins SET name = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$name, $phone, $userId])) {
                $_SESSION['name'] = $name;
                set_flash_message('Profile details updated successfully.', 'success');
            } else {
                set_flash_message('Failed to update details.', 'error');
            }
        }
        redirect('/views/admin/edit-profile.php');
    } elseif ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            set_flash_message('All password fields are required.', 'error');
        } elseif ($newPassword !== $confirmPassword) {
            set_flash_message('New password and confirm password do not match.', 'error');
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();

            if (password_verify($currentPassword, $hash)) {
                $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
                $updateStmt = $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                if ($updateStmt->execute([$newHash, $userId])) {
                    set_flash_message('Password updated successfully.', 'success');
                } else {
                    set_flash_message('Failed to update password.', 'error');
                }
            } else {
                set_flash_message('Incorrect current password.', 'error');
            }
        }
        redirect('/views/admin/edit-profile.php');
    } elseif ($action === 'update_picture') {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowedTypes)) {
                set_flash_message('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'error');
            } elseif ($file['size'] > $maxSize) {
                set_flash_message('File is too large. Maximum size is 2MB.', 'error');
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../../uploads/profiles/';
                
                // Ensure directory exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destination = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Get old picture to delete
                    $stmt = $db->prepare("SELECT profile_pic FROM admins WHERE id = ?");
                    $stmt->execute([$userId]);
                    $oldPic = $stmt->fetchColumn();

                    // Update DB
                    $updateStmt = $db->prepare("UPDATE admins SET profile_pic = ? WHERE id = ?");
                    if ($updateStmt->execute([$filename, $userId])) {
                        $_SESSION['profile_pic'] = $filename;
                        
                        // Delete old picture
                        if ($oldPic && file_exists($uploadDir . $oldPic)) {
                            unlink($uploadDir . $oldPic);
                        }

                        set_flash_message('Profile picture updated successfully.', 'success');
                    } else {
                        set_flash_message('Failed to update database.', 'error');
                    }
                } else {
                    set_flash_message('Failed to move uploaded file.', 'error');
                }
            }
        } else {
            set_flash_message('Please select a valid image file.', 'error');
        }
        redirect('/views/admin/edit-profile.php');
    }
}

// Fetch user details for display
$stmt = $db->prepare("SELECT name, email, phone, status, created_at, profile_pic FROM admins WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i data-lucide="settings" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i> Edit Profile
            </h1>
            <a href="<?= BASE_URL ?>/views/admin/view-profile.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Profile
            </a>
        </div>

        <?php 
        $flash = get_flash_message();
        if ($flash): 
            $bgClass = $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800';
            $icon = $flash['type'] === 'success' ? 'check-circle' : 'alert-circle';
        ?>
            <div class="p-4 rounded-xl border flex items-start gap-3 <?= $bgClass ?>">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Left Column: Profile Picture -->
            <div class="md:col-span-1 space-y-6">
                <!-- Profile Picture Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 text-center">
                    <div class="relative inline-block mb-4">
                        <?php if ($user['profile_pic']): ?>
                            <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover border-4 border-indigo-100 dark:border-indigo-900 shadow-sm mx-auto">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=4f46e5&color=fff&bold=true&size=128" alt="Profile Avatar" class="w-32 h-32 rounded-full border-4 border-indigo-100 dark:border-indigo-900 shadow-sm mx-auto">
                        <?php endif; ?>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="text-indigo-600 dark:text-indigo-400 font-medium text-sm mb-4">ADMIN</p>
                    
                    <form action="<?= BASE_URL ?>/views/admin/edit-profile.php" method="POST" enctype="multipart/form-data" class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="update_picture">
                        <div class="flex items-center justify-center w-full">
                            <label for="profile_pic" class="flex flex-col items-center justify-center w-full h-10 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 dark:border-slate-600 transition-colors">
                                <div class="flex items-center justify-center gap-2">
                                    <i data-lucide="upload" class="w-4 h-4 text-slate-500 dark:text-slate-400"></i>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Select Image</p>
                                </div>
                                <input id="profile_pic" name="profile_pic" type="file" class="hidden" accept="image/jpeg, image/png, image/gif" onchange="this.form.submit()" />
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">JPG, PNG, GIF (Max 2MB). Auto-uploads.</p>
                    </form>
                </div>
            </div>

            <!-- Right Column: Edit Forms -->
            <div class="md:col-span-2 space-y-6">
                
                <!-- Personal Details Form -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i data-lucide="user" class="w-5 h-5 text-indigo-500"></i> Personal Details
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Update your personal information.</p>
                    </div>
                    <form action="<?= BASE_URL ?>/views/admin/edit-profile.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="update_details">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name <span class="text-rose-500">*</span></label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-900 dark:text-slate-100">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-900 dark:text-slate-100">
                            </div>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-900 shadow-sm gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i> Save Details
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Form -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <i data-lucide="lock" class="w-5 h-5 text-indigo-500"></i> Change Password
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ensure your account is using a secure password.</p>
                    </div>
                    <form action="<?= BASE_URL ?>/views/admin/edit-profile.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div>
                            <div class="flex items-center justify-between max-w-md mb-1">
                                <label for="current_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Current Password <span class="text-rose-500">*</span></label>
                                <a href="<?= BASE_URL ?>/views/auth/forgot-password.php" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 hover:underline transition-colors">Forgot Password?</a>
                            </div>
                            <input type="password" id="current_password" name="current_password" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-900 dark:text-slate-100 max-w-md">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password <span class="text-rose-500">*</span></label>
                            <input type="password" id="new_password" name="new_password" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-900 dark:text-slate-100 max-w-md">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm New Password <span class="text-rose-500">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors text-slate-900 dark:text-slate-100 max-w-md">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 dark:focus:ring-offset-slate-900 shadow-sm gap-2">
                                <i data-lucide="key" class="w-4 h-4"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
