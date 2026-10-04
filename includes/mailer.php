<?php
// includes/mailer.php
// Enterprise Mailer Utility for Greenfield College CMS

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Stores the last error message from mail sending attempt.
 */
$GLOBALS['last_mail_error'] = '';

/**
 * Retrieves the last mail dispatch error message.
 *
 * @return string
 */
function get_last_mail_error(): string {
    return $GLOBALS['last_mail_error'] ?? '';
}

/**
 * Instantiates and configures a PHPMailer client with institutional SMTP settings.
 *
 * @return PHPMailer
 * @throws Exception
 */
function get_smtp_mailer(): PHPMailer {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ankanbiswas762@gmail.com';
    $mail->Password   = 'hsmfzwxufbkpuosn';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Default sender
    $mail->setFrom('ankanbiswas762@gmail.com', 'Greenfield College CMS');

    return $mail;
}

/**
 * Builds a modern, high-contrast, professional HTML email template for account provisioning.
 *
 * @param array $params
 * @return string
 */
function build_credentials_email_html(array $params): string {
    $name         = htmlspecialchars($params['name'] ?? 'User');
    $email        = htmlspecialchars($params['email'] ?? '');
    $password     = htmlspecialchars($params['password'] ?? '');
    $roleTitle    = htmlspecialchars($params['role_title'] ?? 'Authorized User');
    $portalType   = strtolower($params['portal_type'] ?? 'student');
    $extraDetails = $params['extra_details'] ?? [];
    $year         = date('Y');

    // Role-specific badges and theme highlights
    switch ($portalType) {
        case 'admin':
            $portalLabel  = 'Admin Control Matrix';
            $badgeBg      = '#eff6ff';
            $badgeColor   = '#1d4ed8';
            $badgeBorder  = '#bfdbfe';
            $headerGrad   = 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%)';
            $themeColor   = '#4f46e5';
            break;
        case 'faculty':
            $portalLabel  = 'Faculty Academic Portal';
            $badgeBg      = '#fffbeb';
            $badgeColor   = '#b45309';
            $badgeBorder  = '#fde68a';
            $headerGrad   = 'linear-gradient(135deg, #0f172a 0%, #1c1917 60%, #78350f 100%)';
            $themeColor   = '#d97706';
            break;
        case 'student':
        default:
            $portalLabel  = 'Student Learning Portal';
            $badgeBg      = '#faf5ff';
            $badgeColor   = '#7e22ce';
            $badgeBorder  = '#e9d5ff';
            $headerGrad   = 'linear-gradient(135deg, #0f172a 0%, #2e1065 60%, #581c87 100%)';
            $themeColor   = '#7c3aed';
            break;
    }

    // Build extra details rows if provided
    $extraRowsHtml = '';
    if (!empty($extraDetails) && is_array($extraDetails)) {
        foreach ($extraDetails as $label => $value) {
            if (!empty($value)) {
                $extraRowsHtml .= "
                <tr>
                    <td style=\"padding: 9px 0; font-size: 13px; color: #64748b; font-weight: 600; width: 38%;\">" . htmlspecialchars($label) . ":</td>
                    <td style=\"padding: 9px 0; font-size: 13px; color: #0f172a; font-weight: 700;\">" . htmlspecialchars($value) . "</td>
                </tr>";
            }
        }
    }

    return "
    <!DOCTYPE html>
    <html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>Your Account Credentials — Greenfield College</title>
        <!--[if mso]>
        <style type=\"text/css\">
        body, table, td, a { font-family: Arial, Helvetica, sans-serif !important; }
        </style>
        <![endif]-->
    </head>
    <body style=\"margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; line-height: 1.6; color: #334155;\">
        
        <!-- Outer Wrapper -->
        <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"background-color: #f1f5f9; padding: 30px 15px;\">
            <tr>
                <td align=\"center\">
                    
                    <!-- Main Card Container -->
                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;\">
                        
                        <!-- Header Banner -->
                        <tr>
                            <td style=\"background: {$headerGrad}; padding: 40px 35px; text-align: center; color: #ffffff;\">
                                <!-- University Logo / Crest Badge -->
                                <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\" style=\"margin-bottom: 16px;\">
                                    <tr>
                                        <td align=\"center\" style=\"width: 56px; height: 56px; background-color: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 16px; font-size: 26px; line-height: 56px; text-align: center;\">
                                            &#127891;
                                        </td>
                                    </tr>
                                </table>
                                
                                <div style=\"font-size: 12px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #cbd5e1; margin-bottom: 6px;\">
                                    Greenfield College
                                </div>
                                <h1 style=\"margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; color: #ffffff; line-height: 1.3;\">
                                    Official Portal Account Created
                                </h1>
                                <p style=\"margin: 8px 0 0 0; font-size: 14px; color: #94a3b8; font-weight: 400;\">
                                    Your secure authentication credentials are ready
                                </p>
                            </td>
                        </tr>

                        <!-- Body Content -->
                        <tr>
                            <td style=\"padding: 35px 35px 25px 35px;\">
                                
                                <!-- Salutation -->
                                <h2 style=\"margin: 0 0 14px 0; font-size: 19px; font-weight: 700; color: #0f172a;\">
                                    Dear {$name},
                                </h2>
                                <p style=\"margin: 0 0 24px 0; font-size: 14.5px; line-height: 1.65; color: #475569;\">
                                    An official account has been provisioned for you in the <strong>Greenfield College Management System</strong>. You now have authorized access to your departmental tools, academic records, and personalized portal stream.
                                </p>

                                <!-- Credentials Hero Card -->
                                <div style=\"background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 14px; padding: 22px; margin-bottom: 26px;\">
                                    
                                    <!-- Card Header Bar -->
                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;\">
                                        <tr>
                                            <td style=\"font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #475569;\">
                                                &#128274; Sign-In Credentials
                                            </td>
                                            <td align=\"right\">
                                                <span style=\"background-color: {$badgeBg}; color: {$badgeColor}; border: 1px solid {$badgeBorder}; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;\">
                                                    {$roleTitle}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Credentials Data Table -->
                                    <table role=\"presentation\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
                                        <tr>
                                            <td style=\"padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 600; width: 38%;\">Target Portal:</td>
                                            <td style=\"padding: 7px 0; font-size: 13.5px; color: #0f172a; font-weight: 700;\">{$portalLabel}</td>
                                        </tr>
                                        <tr>
                                            <td style=\"padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 600;\">Login Email:</td>
                                            <td style=\"padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 800; font-family: 'Consolas', 'Courier New', monospace;\">{$email}</td>
                                        </tr>
                                        {$extraRowsHtml}
                                    </table>

                                    <!-- High-Contrast Password Display Block -->
                                    <div style=\"background: #0f172a; border-radius: 10px; padding: 14px 18px; margin-top: 16px; border-left: 4px solid {$themeColor};\">
                                        <div style=\"font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; margin-bottom: 4px;\">
                                            Assigned Temporary Password
                                        </div>
                                        <div style=\"font-family: 'Consolas', 'Courier New', monospace; font-size: 20px; font-weight: 800; color: #38bdf8; letter-spacing: 1.5px; word-break: break-all;\">
                                            {$password}
                                        </div>
                                        <div style=\"font-size: 11px; color: #64748b; margin-top: 5px;\">
                                            &#8226; Copy and use this exact password during your initial sign-in.
                                        </div>
                                    </div>

                                </div>

                                <!-- Sign-In Instructions Notice -->
                                <div style=\"background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 18px 22px; margin: 26px 0; text-align: center;\">
                                    <div style=\"font-size: 13.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px;\">
                                        &#128273; Portal Access Instructions
                                    </div>
                                    <div style=\"font-size: 13px; color: #475569; line-height: 1.6;\">
                                        Please open your college management portal, choose the <strong style=\"color: {$themeColor}; font-weight: 800;\">{$portalLabel}</strong>, and sign in using your registered email and temporary password shown above.
                                    </div>
                                </div>

                                <!-- Security Advisory Card -->
                                <div style=\"background-color: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;\">
                                    <div style=\"font-size: 12.5px; font-weight: 700; color: #92400e; margin-bottom: 4px; display: flex; align-items: center;\">
                                        &#9888; Security Advisory
                                    </div>
                                    <ul style=\"margin: 4px 0 0 16px; padding: 0; font-size: 12px; color: #78350f; line-height: 1.55;\">
                                        <li>Please update this temporary password immediately after your first sign in.</li>
                                        <li>Never share your credentials or two-factor security codes with anyone.</li>
                                        <li>College IT personnel will never request your password via email or phone.</li>
                                    </ul>
                                </div>

                                <p style=\"margin: 0; font-size: 13.5px; color: #64748b; line-height: 1.6;\">
                                    If you experience any difficulties accessing your account, please reach out to the Greenfield College IT Administration or Campus Helpdesk.
                                </p>
                            </td>
                        </tr>

                        <!-- Institutional Footer -->
                        <tr>
                            <td style=\"background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 26px 35px; text-align: center; color: #94a3b8; font-size: 12px; line-height: 1.7;\">
                                <div style=\"font-weight: 700; color: #475569; margin-bottom: 4px;\">
                                    Greenfield College &bull; Academic ERP Systems
                                </div>
                                <div>
                                    Autonomous Higher Education Institution &bull; 120-Acre Campus
                                </div>
                                <div style=\"margin: 6px 0; color: #94a3b8;\">
                                    &copy; {$year} Greenfield College. All rights reserved.
                                </div>
                                <div style=\"font-size: 11px; color: #cbd5e1;\">
                                    This is an automated system notification. Please do not reply directly to this email address.
                                </div>
                            </td>
                        </tr>

                    </table>
                    
                </td>
            </tr>
        </table>
        
    </body>
    </html>
    ";
}

/**
 * Builds a clean plaintext alternative body for email clients that do not render HTML.
 *
 * @param array $params
 * @return string
 */
function build_credentials_email_text(array $params): string {
    $name         = $params['name'] ?? 'User';
    $email        = $params['email'] ?? '';
    $password     = $params['password'] ?? '';
    $roleTitle    = $params['role_title'] ?? 'Authorized User';
    $portalType   = strtolower($params['portal_type'] ?? 'student');
    $extraDetails = $params['extra_details'] ?? [];

    switch ($portalType) {
        case 'admin':
            $portalLabel = 'Admin Control Matrix';
            break;
        case 'faculty':
            $portalLabel = 'Faculty Academic Portal';
            break;
        case 'student':
        default:
            $portalLabel = 'Student Learning Portal';
            break;
    }

    $extraText = "";
    if (!empty($extraDetails) && is_array($extraDetails)) {
        foreach ($extraDetails as $label => $value) {
            if (!empty($value)) {
                $extraText .= "{$label}: {$value}\n";
            }
        }
    }

    return "Hello {$name},

An official account has been created for you in the Greenfield College Management System.

==============================================
YOUR ACCOUNT CREDENTIALS
==============================================
Target Portal: {$portalLabel}
Role: {$roleTitle}
Login Email: {$email}
{$extraText}Temporary Password: {$password}

SIGN-IN INSTRUCTIONS:
Please navigate to your college management portal, select the {$portalLabel}, and sign in with the credentials provided above.

SECURITY ADVISORY:
- Please change your temporary password immediately upon your first sign in.
- Do not share your password with anyone.

If you have questions, please reach out to the Greenfield College IT Administration or Campus Helpdesk.

© " . date('Y') . " Greenfield College. All rights reserved.
";
}

/**
 * Sends the welcome and login credentials email to a newly created user.
 *
 * @param string $recipientEmail User's email address
 * @param string $recipientName  User's full name
 * @param string $password       Plaintext password created for the user
 * @param string $roleTitle      Role title or designation (e.g., 'HOD', 'Assistant Professor', 'Student')
 * @param string $portalType     'admin' | 'faculty' | 'student'
 * @param array  $extraDetails   Key-value pairs of additional info (e.g. ['Roll No' => '123'])
 * @return bool True if successfully sent, false otherwise
 */
function send_account_credentials_email(
    string $recipientEmail,
    string $recipientName,
    string $password,
    string $roleTitle,
    string $portalType = 'student',
    array $extraDetails = []
): bool {
    $GLOBALS['last_mail_error'] = '';

    if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['last_mail_error'] = 'Invalid recipient email address.';
        return false;
    }

    try {
        $mail = get_smtp_mailer();

        // Recipient
        $mail->addAddress($recipientEmail, $recipientName);

        // Subject
        $portalLabel = ucfirst($portalType);
        $mail->Subject = "Your Greenfield College {$portalLabel} Portal Account Credentials";

        // Template Params
        $params = [
            'name'          => $recipientName,
            'email'         => $recipientEmail,
            'password'      => $password,
            'role_title'    => $roleTitle,
            'portal_type'   => $portalType,
            'extra_details' => $extraDetails
        ];

        // Content
        $mail->isHTML(true);
        $mail->Body    = build_credentials_email_html($params);
        $mail->AltBody = build_credentials_email_text($params);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorMsg = "Mailer error to {$recipientEmail}: " . $mail->ErrorInfo;
        error_log($errorMsg);
        $GLOBALS['last_mail_error'] = $mail->ErrorInfo ?: $e->getMessage();
        return false;
    } catch (\Throwable $t) {
        $errorMsg = "Unexpected mailer exception to {$recipientEmail}: " . $t->getMessage();
        error_log($errorMsg);
        $GLOBALS['last_mail_error'] = $t->getMessage();
        return false;
    }
}
