<?php
declare(strict_types=1);

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

use Config\Database;
use PDO;

class AuthController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Portal-based login authentication.
     * Queries the appropriate table based on which portal the user is logging in from.
     *
     * @param string $portal One of: 'student', 'faculty', 'admin'
     */
    public function login(string $email, string $password, string $portal = 'student'): bool {
        
        // Map portal to table and role
        $tableMap = [
            'student' => ['table' => 'students', 'role' => 'STUDENT'],
            'faculty' => ['table' => 'teachers', 'role' => 'FACULTY'],
            'admin'   => ['table' => 'admins',   'role' => 'ADMIN'],
        ];
        
        if (!isset($tableMap[$portal])) {
            set_flash_message('Invalid login portal.', 'error');
            return false;
        }
        
        $tableName = $tableMap[$portal]['table'];
        $roleName = $tableMap[$portal]['role'];
        
        $stmt = $this->db->prepare("
            SELECT id, name, profile_pic, password_hash, status 
            FROM {$tableName}
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $browser_vector = substr($_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN', 0, 255);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'ACTIVE') {
                $this->logActivity($user['id'], $tableName, $email, 'BLOCKED', $ip_address, $browser_vector);
                set_flash_message('Your account is currently inactive. Please contact system administration.', 'error');
                return false;
            }

            $this->logActivity($user['id'], $tableName, $email, 'GRANTED', $ip_address, $browser_vector);

            // Establish session context
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['role_name'] = $roleName;
            $_SESSION['user_table'] = $tableName;
            
            // Map profile IDs (now the same as the user's id in the role-specific table)
            if ($roleName === 'FACULTY') {
                $_SESSION['faculty_profile_id'] = $user['id'];
            } else if ($roleName === 'STUDENT') {
                $_SESSION['student_profile_id'] = $user['id'];
            }

            set_flash_message('Welcome securely back to your dashboard, ' . htmlspecialchars($user['name']) . '!');
            return true;
        }

        $this->logActivity($user ? $user['id'] : null, $tableName, $email, 'BLOCKED', $ip_address, $browser_vector);
        set_flash_message('The email or password you entered is incorrect.', 'error');
        return false;
    }

    /**
     * Resets the entire session ecosystem safely.
     */
    public function logout(): void {
        // Wipes all data
        session_unset();
        session_destroy();
        
        // Initialize a new shell session purely for delivering the Toast message
        session_start();
        set_flash_message('You have been logged out securely.', 'info');
    }

    public function forgotPassword(string $identifier): bool {
        $user = null;
        $table = '';
        $role = '';
        
        // Search students
        $stmt = $this->db->prepare("SELECT id, email, name FROM students WHERE email = ? OR roll_number = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        if ($user) {
            $table = 'students';
            $role = 'STUDENT';
        } else {
            // Search teachers
            $stmt = $this->db->prepare("SELECT id, email, name FROM teachers WHERE email = ?");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();
            if ($user) {
                $table = 'teachers';
                $role = 'FACULTY';
            } else {
                // Search admins
                $stmt = $this->db->prepare("SELECT id, email, username as name FROM admins WHERE email = ? OR username = ?");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch();
                if ($user) {
                    $table = 'admins';
                    $role = 'ADMIN';
                }
            }
        }
        
        if (!$user) {
            set_flash_message('No account found with that email or ID.', 'error');
            return false;
        }
        
        $otp = (string)random_int(1000, 9999);
        $_SESSION['password_reset'] = [
            'otp' => $otp,
            'email' => $user['email'],
            'user_id' => $user['id'],
            'table' => $table,
            'role' => $role,
            'expires_at' => time() + 600 // 10 minutes
        ];
        
        // Include PHPMailer
        require_once __DIR__ . '/../includes/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../includes/PHPMailer/src/SMTP.php';
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ankanbiswas762@gmail.com'; 
            $mail->Password   = 'hsmfzwxufbkpuosn'; 
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('ankanbiswas762@gmail.com', 'College CMS Support');
            $mail->addAddress($user['email'], $user['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Verification Code - Greenfield University';
            
            $emailTemplate = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
                    .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
                    .header { background: linear-gradient(135deg, #4f46e5 0%, #1832a5ff 100%); padding: 35px 20px; text-align: center; color: #ffffff; }
                    .header h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; }
                    .header p { margin: 10px 0 0 0; font-size: 15px; opacity: 0.9; }
                    .body { padding: 45px 40px; color: #334155; line-height: 1.7; text-align: center; }
                    .greeting { font-size: 20px; font-weight: 600; margin-bottom: 25px; color: #0f172a; text-align: left; }
                    .message { font-size: 16px; margin-bottom: 35px; text-align: left; color: #475569; }
                    .otp-wrapper { text-align: center; margin: 35px 0; padding: 0 10px; }
                    .otp-container { background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px; padding: 25px 15px; display: inline-block; width: 100%; max-width: 280px; box-sizing: border-box; }
                    .otp-text { font-size: 13px; color: #64748b; margin-bottom: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
                    .otp-number { font-size: 42px; font-weight: 800; color: #4f46e5; letter-spacing: 8px; margin: 0; font-family: monospace; text-shadow: 2px 2px 0px rgba(79,70,229,0.1); }
                    .warning { font-size: 14px; color: #94a3b8; text-align: left; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 25px; }
                    .footer { background-color: #f8fafc; padding: 25px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='header'>
                        <h1>Greenfield University</h1>
                        <p>Secure Account Management</p>
                    </div>
                    <div class='body'>
                        <div class='greeting'>Hello {$user['name']},</div>
                        <div class='message'>We received a request to reset the password for your college portal account. Please use the verification code below to securely complete the process.</div>
                        
                        <div class='otp-wrapper'>
                            <div class='otp-container'>
                                <div class='otp-text'>Your Verification Code</div>
                                <div class='otp-number'>{$otp}</div>
                            </div>
                        </div>
                        
                        <div class='message'>This code is securely generated and will expire in <b>10 minutes</b>. For your security, please do not share this code with anyone.</div>
                        
                        <div class='warning'>If you did not request a password reset, you can safely ignore this email. Your account remains secure.</div>
                    </div>
                    <div class='footer'>
                        &copy; " . date('Y') . " Greenfield University. All rights reserved.<br>
                        This is an automated message, please do not reply.
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->Body    = $emailTemplate;
            $mail->AltBody = "Hello {$user['name']},\n\nWe received a request to reset your password. Your verification code is: {$otp}\n\nThis code is valid for 10 minutes. If you did not request this, please ignore this email.\n\nGreenfield University Support";

            $mail->send();
            
            set_flash_message('An OTP has been sent to your registered email address.', 'success');
            return true;
        } catch (\Exception $e) {
            set_flash_message('Failed to send OTP email. Please try again.', 'error');
            return false;
        }
    }

    public function verifyOTP(string $otp): bool {
        if (!isset($_SESSION['password_reset'])) {
            set_flash_message('Session expired. Please request a new OTP.', 'error');
            return false;
        }
        
        $resetData = $_SESSION['password_reset'];
        
        if (time() > $resetData['expires_at']) {
            unset($_SESSION['password_reset']);
            set_flash_message('OTP has expired. Please request a new one.', 'error');
            return false;
        }
        
        if ($otp !== $resetData['otp']) {
            set_flash_message('Invalid OTP. Please try again.', 'error');
            return false;
        }
        
        $_SESSION['password_reset']['verified'] = true;
        return true;
    }

    public function resetPassword(string $newPassword): bool {
        if (!isset($_SESSION['password_reset']) || empty($_SESSION['password_reset']['verified'])) {
            set_flash_message('Unauthorized access. Please verify OTP first.', 'error');
            return false;
        }
        
        $resetData = $_SESSION['password_reset'];
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE {$resetData['table']} SET password_hash = ? WHERE id = ?");
        if ($stmt->execute([$hash, $resetData['user_id']])) {
            unset($_SESSION['password_reset']);
            set_flash_message('Password updated successfully. Please log in.', 'success');
            return true;
        }
        
        set_flash_message('Failed to update password. Please try again.', 'error');
        return false;
    }

    /**
     * Logs the login activity with portal awareness.
     */
    private function logActivity(?int $userId, string $userTable, string $email, string $status, string $ip, string $vector): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO security_logs (user_id, user_table, email_attempt, status, ip_address, browser_vector) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $userTable, $email, $status, $ip, $vector]);
        } catch (\Exception $e) {
            // Silently fail logging if issue occurs to avoid breaking login loop
        }
    }
}
