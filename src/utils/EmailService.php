<?php
/**
 * BuzzarFeed - Email Service
 * 
 * Handles email sending using PHP mail() function with SMTP headers
 * Compatible with InfinityFree hosting
 * 
 * @package BuzzarFeed
 * @version 2.0
 */

namespace BuzzarFeed\Utils;

class EmailService
{
    private static $instance = null;
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpUsername;
    private $smtpPassword;
    private $smtpPort;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->fromEmail = Env::get('MAIL_FROM_ADDRESS', 'noreply@buzzarfeed.com');
        $this->fromName = Env::get('MAIL_FROM_NAME', 'BuzzarFeed');
        $this->smtpHost = 'smtp-relay.brevo.com';
        $this->smtpUsername = Env::get('BREVO_SMTP_EMAIL', '');
        $this->smtpPassword = Env::get('BREVO_SMTP_KEY', '');
        $this->smtpPort = 587;
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): EmailService
    {
        if (self::$instance === null) {
            self::$instance = new EmailService();
        }
        return self::$instance;
    }
    
    /**
     * Send email using Brevo HTTP API (works on InfinityFree)
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string $textBody Plain text email body
     * @return bool Success status
     */
    private function sendMail(string $to, string $toName, string $subject, string $htmlBody, string $textBody): bool
    {
        try {
            // Brevo API configuration
            $brevoApiKey = $this->smtpPassword; // Use the SMTP key as API key
            
            if (empty($brevoApiKey)) {
                $errorMsg = "CRITICAL: Brevo API key not configured in .env file";
                error_log($errorMsg);
                if (DEVELOPMENT_MODE) {
                    throw new \Exception($errorMsg);
                }
                return false;
            }
            
            // Log attempt
            error_log("=== ATTEMPTING TO SEND EMAIL ===");
            error_log("To: {$to} ({$toName})");
            error_log("Subject: {$subject}");
            error_log("From: {$this->fromEmail} ({$this->fromName})");
            error_log("API Key present: " . (empty($brevoApiKey) ? 'NO' : 'YES (length: ' . strlen($brevoApiKey) . ')'));
            
            // Prepare API request
            $apiUrl = 'https://api.brevo.com/v3/smtp/email';
            
            $data = [
                'sender' => [
                    'name' => $this->fromName,
                    'email' => $this->fromEmail
                ],
                'to' => [
                    [
                        'email' => $to,
                        'name' => $toName
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $htmlBody,
                'textContent' => $textBody
            ];
            
            $jsonData = json_encode($data);
            error_log("JSON payload size: " . strlen($jsonData) . " bytes");
            
            // Use cURL to send API request
            $ch = curl_init($apiUrl);
            
            if ($ch === false) {
                error_log("CRITICAL: Failed to initialize cURL");
                return false;
            }
            
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'api-key: ' . $brevoApiKey,
                'content-type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);
            
            // Log response
            error_log("HTTP Status Code: {$httpCode}");
            error_log("cURL Error Code: {$curlErrno}");
            if (!empty($curlError)) {
                error_log("cURL Error: {$curlError}");
            }
            error_log("API Response: " . substr($response, 0, 500)); // First 500 chars
            
            if ($httpCode === 201) {
                error_log("✓ Email sent successfully via Brevo API to: {$to}");
                error_log("=================================");
                return true;
            } else {
                error_log("✗ Email sending FAILED");
                error_log("HTTP Code: {$httpCode}");
                error_log("Response: {$response}");
                error_log("cURL Error: {$curlError}");
                error_log("=================================");
                
                // In development, throw exception with details
                if (DEVELOPMENT_MODE && $httpCode !== 201) {
                    $errorDetails = json_decode($response, true);
                    $errorMessage = isset($errorDetails['message']) ? $errorDetails['message'] : 'Unknown error';
                    throw new \Exception("Brevo API Error (HTTP {$httpCode}): {$errorMessage}");
                }
                
                return false;
            }
            
        } catch (\Exception $e) {
            error_log("Email sending exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Re-throw in development mode
            if (DEVELOPMENT_MODE) {
                throw $e;
            }
            
            return false;
        }
    }
    
    /**
     * Send a welcome email to new user
     * 
     * @param string $email User's email address
     * @param string $name User's name
     * @return bool True if email sent successfully
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        $subject = 'Welcome to BuzzarFeed!';
        $htmlBody = $this->getWelcomeEmailTemplate($name);
        $textBody = $this->getWelcomeEmailTextVersion($name);
        
        return $this->sendMail($email, $name, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email User's email address
     * @param string $name User's name
     * @param string $resetToken Password reset token
     * @param string $resetLink Full reset link URL
     * @return bool True if email sent successfully
     */
    public function sendPasswordResetEmail(string $email, string $name, string $resetToken, string $resetLink): bool
    {
        $subject = 'Reset Your BuzzarFeed Password';
        $htmlBody = $this->getPasswordResetEmailTemplate($name, $resetLink);
        $textBody = $this->getPasswordResetEmailTextVersion($name, $resetLink);
        
        return $this->sendMail($email, $name, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Get HTML template for welcome email
     */
    private function getWelcomeEmailTemplate(string $name): string
    {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $logoUrl = BASE_URL . 'assets/images/Logo-Footer.png';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to BuzzarFeed</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Sora', Arial, sans-serif; background-color: #F5F5F5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F5F5F5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Logo Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <img src="{$logoUrl}" alt="BuzzarFeed" style="max-width: 200px; height: auto; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    
                    <!-- Welcome Header -->
                    <tr>
                        <td style="background-color: #FEEED5; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 32px; color: #2C2C2C; font-weight: 700;">Welcome to BuzzarFeed!</h1>
                            <p style="margin: 10px 0 0 0; font-size: 16px; color: #666666;">Your culinary adventure starts here</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="font-size: 18px; color: #2C2C2C; margin: 0 0 20px 0;">Hi <strong>{$escapedName}</strong>,</p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 20px 0;">
                                Thank you for joining BuzzarFeed! We're excited to have you as part of our community of food enthusiasts exploring the amazing flavors of BGC Night Market.
                            </p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 30px 0;">
                                With your account, you can:
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 30px 0;">
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 30px; vertical-align: top;">
                                                    <div style="width: 24px; height: 24px; background-color: #489A44; border-radius: 50%; text-align: center; line-height: 24px; color: white; font-weight: bold; font-size: 14px;">✓</div>
                                                </td>
                                                <td style="font-size: 16px; color: #666666; padding-left: 10px;">Discover and review food stalls</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 30px; vertical-align: top;">
                                                    <div style="width: 24px; height: 24px; background-color: #489A44; border-radius: 50%; text-align: center; line-height: 24px; color: white; font-weight: bold; font-size: 14px;">✓</div>
                                                </td>
                                                <td style="font-size: 16px; color: #666666; padding-left: 10px;">Share your culinary experiences</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 30px; vertical-align: top;">
                                                    <div style="width: 24px; height: 24px; background-color: #489A44; border-radius: 50%; text-align: center; line-height: 24px; color: white; font-weight: bold; font-size: 14px;">✓</div>
                                                </td>
                                                <td style="font-size: 16px; color: #666666; padding-left: 10px;">Connect with fellow food lovers</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width: 30px; vertical-align: top;">
                                                    <div style="width: 24px; height: 24px; background-color: #489A44; border-radius: 50%; text-align: center; line-height: 24px; color: white; font-weight: bold; font-size: 14px;">✓</div>
                                                </td>
                                                <td style="font-size: 16px; color: #666666; padding-left: 10px;">Get personalized recommendations</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="https://buzzarfeed.free.nf" style="display: inline-block; padding: 16px 40px; background-color: #489A44; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(72, 154, 68, 0.3);">
                                    Start Exploring Now
                                </a>
                            </div>
                            
                            <p style="font-size: 14px; color: #999999; line-height: 1.6; margin: 30px 0 0 0; border-top: 1px solid #DDDDDD; padding-top: 20px;">
                                If you have any questions, feel free to reach out to our support team at 
                                <a href="mailto:support@buzzarfeed.com" style="color: #489A44; text-decoration: none;">support@buzzarfeed.com</a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2C2C2C; padding: 30px; text-align: center; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; font-weight: 600;">
                                © 2025 BuzzarFeed. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.6);">
                                BGC Night Market, Bonifacio Global City, Manila
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Get plain text version of welcome email
     */
    private function getWelcomeEmailTextVersion(string $name): string
    {
        return <<<TEXT
Welcome to BuzzarFeed!

Hi {$name},

Thank you for joining BuzzarFeed! We're excited to have you as part of our community of food enthusiasts exploring the amazing flavors of BGC Night Market.

With your account, you can:
- Discover and review food stalls
- Share your culinary experiences
- Connect with fellow food lovers
- Get personalized recommendations

Start exploring at: https://buzzarfeed.com

If you have any questions, feel free to reach out to our support team.

© 2025 BuzzarFeed. All rights reserved.
BGC Night Market, Bonifacio Global City
TEXT;
    }
    
    /**
     * Get HTML template for password reset email
     */
    private function getPasswordResetEmailTemplate(string $name, string $resetLink): string
    {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $escapedLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $logoUrl = BASE_URL . 'assets/images/Logo-Footer.png';
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Sora', Arial, sans-serif; background-color: #F5F5F5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F5F5F5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Logo Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <img src="{$logoUrl}" alt="BuzzarFeed" style="max-width: 200px; height: auto; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    
                    <!-- Title Header -->
                    <tr>
                        <td style="background-color: #FEEED5; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 28px; color: #2C2C2C; font-weight: 700;">Password Reset Request</h1>
                            <p style="margin: 10px 0 0 0; font-size: 14px; color: #666666;">Secure your account with a new password</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="font-size: 18px; color: #2C2C2C; margin: 0 0 20px 0;">Hi <strong>{$escapedName}</strong>,</p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 20px 0;">
                                We received a request to reset your BuzzarFeed password. Click the button below to create a new password:
                            </p>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{$escapedLink}" style="display: inline-block; padding: 16px 40px; background-color: #ED6027; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(237, 96, 39, 0.4);">
                                    Reset Your Password
                                </a>
                            </div>
                            
                            <p style="font-size: 14px; color: #999999; line-height: 1.6; margin: 20px 0; text-align: center;">
                                Or copy and paste this link into your browser:
                            </p>
                            
                            <div style="background-color: #F9F9F9; padding: 15px; border-radius: 6px; border: 1px solid #E0E0E0; margin: 20px 0;">
                                <p style="margin: 0; font-size: 13px; color: #666666; word-break: break-all; text-align: center;">
                                    <a href="{$escapedLink}" style="color: #489A44; text-decoration: none;">{$escapedLink}</a>
                                </p>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); border-left: 4px solid #ED6027; padding: 20px; margin: 30px 0; border-radius: 6px;">
                                <p style="margin: 0 0 10px 0; font-size: 15px; color: #2C2C2C; font-weight: 600;">Security Note:</p>
                                <p style="margin: 0; font-size: 14px; color: #666666; line-height: 1.6;">
                                    This link will expire in <strong>1 hour</strong>. If you didn't request a password reset, please ignore this email or contact support if you're concerned about your account security.
                                </p>
                            </div>
                            
                            <table cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0 0 0; border-top: 1px solid #DDDDDD; padding-top: 20px;">
                                <tr>
                                    <td style="padding: 10px 0;">
                                        <p style="margin: 0; font-size: 14px; color: #999999; line-height: 1.6;">
                                            <strong style="color: #666666;">Need help?</strong><br>
                                            Contact our support team at 
                                            <a href="mailto:support@buzzarfeed.com" style="color: #489A44; text-decoration: none;">support@buzzarfeed.com</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2C2C2C; padding: 30px; text-align: center; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; font-weight: 600;">
                                © 2025 BuzzarFeed. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.6);">
                                BGC Night Market, Bonifacio Global City, Manila
                            </p>
                            <div style="margin-top: 20px;">
                                <a href="https://facebook.com/buzzarfeed" style="display: inline-block; margin: 0 8px; color: #FFFFFF; text-decoration: none;">
                                    <span style="font-size: 20px;">📘</span>
                                </a>
                                <a href="https://instagram.com/buzzarfeed" style="display: inline-block; margin: 0 8px; color: #FFFFFF; text-decoration: none;">
                                    <span style="font-size: 20px;">📷</span>
                                </a>
                                <a href="https://twitter.com/buzzarfeed" style="display: inline-block; margin: 0 8px; color: #FFFFFF; text-decoration: none;">
                                    <span style="font-size: 20px;">🐦</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Get plain text version of password reset email
     */
    private function getPasswordResetEmailTextVersion(string $name, string $resetLink): string
    {
        return <<<TEXT
Password Reset Request

Hi {$name},

We received a request to reset your BuzzarFeed password. Click the link below to create a new password:

{$resetLink}

Security Note:
This link will expire in 1 hour. If you didn't request a password reset, please ignore this email or contact support if you're concerned about your account security.

Need help? Contact our support team at support@buzzarfeed.com

© 2025 BuzzarFeed. All rights reserved.
BGC Night Market, Bonifacio Global City
TEXT;
    }
    
    /**
     * Send application approval email
     * 
     * @param string $email Applicant's email address
     * @param string $name Applicant's name
     * @param string $stallName Name of approved stall
     * @param string $reviewNotes Admin notes (optional)
     * @return bool True if email sent successfully
     */
    public function sendApplicationApprovalEmail(string $email, string $name, string $stallName, string $reviewNotes = ''): bool
    {
        $subject = '🎉 Your Stall Application Has Been Approved!';
        $htmlBody = $this->getApplicationApprovalTemplate($name, $stallName, $reviewNotes);
        $textBody = $this->getApplicationApprovalTextVersion($name, $stallName, $reviewNotes);
        
        return $this->sendMail($email, $name, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Send application decline email
     * 
     * @param string $email Applicant's email address
     * @param string $name Applicant's name
     * @param string $stallName Name of declined stall
     * @param string $reviewNotes Admin notes (optional)
     * @return bool True if email sent successfully
     */
    public function sendApplicationDeclineEmail(string $email, string $name, string $stallName, string $reviewNotes = ''): bool
    {
        $subject = 'Update on Your Stall Application';
        $htmlBody = $this->getApplicationDeclineTemplate($name, $stallName, $reviewNotes);
        $textBody = $this->getApplicationDeclineTextVersion($name, $stallName, $reviewNotes);
        
        return $this->sendMail($email, $name, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Send a custom email with HTML body
     * 
     * @param string $to Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @return bool True if email sent successfully
     */
    public function sendCustomEmail(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        // Create a simple text version by stripping HTML tags
        $textBody = strip_tags($htmlBody);
        $textBody = html_entity_decode($textBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $this->sendMail($to, $toName, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Get HTML template for application approval email
     */
    private function getApplicationApprovalTemplate(string $name, string $stallName, string $reviewNotes): string
    {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $escapedStallName = htmlspecialchars($stallName, ENT_QUOTES, 'UTF-8');
        $escapedNotes = htmlspecialchars($reviewNotes, ENT_QUOTES, 'UTF-8');
        $logoUrl = BASE_URL . 'assets/images/Logo-Footer.png';
        $manageStallUrl = BASE_URL . 'manage-stall.php';
        
        $notesSection = '';
        if (!empty($reviewNotes)) {
            $notesSection = <<<HTML
                            <div style="background-color: #FEEED5; border-left: 4px solid #489A44; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666; font-weight: 600;">Admin Notes:</p>
                                <p style="margin: 0; font-size: 16px; color: #2C2C2C; line-height: 1.6;">{$escapedNotes}</p>
                            </div>
HTML;
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved!</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Sora', Arial, sans-serif; background-color: #F5F5F5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F5F5F5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Logo Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #489A44 0%, #3d8239 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <img src="{$logoUrl}" alt="BuzzarFeed" style="max-width: 200px; height: auto; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    
                    <!-- Success Icon -->
                    <tr>
                        <td style="background-color: #FEEED5; padding: 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 32px; color: #2C2C2C; font-weight: 700;">Congratulations!</h1>
                            <p style="margin: 10px 0 0 0; font-size: 18px; color: #489A44; font-weight: 600;">Your Application Has Been Approved</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="font-size: 18px; color: #2C2C2C; margin: 0 0 20px 0;">Hi <strong>{$escapedName}</strong>,</p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 20px 0;">
                                Great news! We're thrilled to inform you that your stall application for <strong style="color: #489A44;">"{$escapedStallName}"</strong> has been approved!
                            </p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 30px 0;">
                                Your stall is now live on BuzzarFeed and ready to welcome hungry customers from the BGC Night Market community.
                            </p>
                            
                            {$notesSection}
                            
                            <div style="background-color: #F5F5F5; padding: 25px; border-radius: 8px; margin: 30px 0;">
                                <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #2C2C2C;">What's Next?</h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">📝</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Complete your stall information and upload your menu items</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">📍</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Set your stall location on the interactive map</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">⏰</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Update your operating hours to let customers know when you're open</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">⭐</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Monitor and respond to customer reviews</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 40px 0;">
                                <a href="{$manageStallUrl}" style="display: inline-block; background-color: #489A44; color: #FFFFFF; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(72,154,68,0.3);">
                                    Manage Your Stall
                                </a>
                            </div>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 30px 0 0 0;">
                                Welcome to the BuzzarFeed family! We're excited to have you join our vibrant food community.
                            </p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 20px 0 0 0;">
                                If you have any questions, feel free to reach out to our support team.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2C2C2C; padding: 30px; text-align: center; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; font-weight: 600;">
                                © 2025 BuzzarFeed. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.6);">
                                BGC Night Market, Bonifacio Global City, Manila
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Get HTML template for application decline email
     */
    private function getApplicationDeclineTemplate(string $name, string $stallName, string $reviewNotes): string
    {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $escapedStallName = htmlspecialchars($stallName, ENT_QUOTES, 'UTF-8');
        $escapedNotes = htmlspecialchars($reviewNotes, ENT_QUOTES, 'UTF-8');
        $logoUrl = BASE_URL . 'assets/images/Logo-Footer.png';
        $applyUrl = BASE_URL . 'register-stall.php';
        
        $reasonSection = '';
        if (!empty($reviewNotes)) {
            $reasonSection = <<<HTML
                            <div style="background-color: #FFE5E5; border-left: 4px solid #ED6027; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666666; font-weight: 600;">Reason for Decline:</p>
                                <p style="margin: 0; font-size: 16px; color: #2C2C2C; line-height: 1.6;">{$escapedNotes}</p>
                            </div>
HTML;
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Update</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Sora', Arial, sans-serif; background-color: #F5F5F5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F5F5F5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <!-- Logo Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ED6027 0%, #E8663E 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <img src="{$logoUrl}" alt="BuzzarFeed" style="max-width: 200px; height: auto; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #FEEED5; padding: 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 28px; color: #2C2C2C; font-weight: 700;">Application Update</h1>
                            <p style="margin: 10px 0 0 0; font-size: 16px; color: #666666;">Regarding your stall application</p>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="font-size: 18px; color: #2C2C2C; margin: 0 0 20px 0;">Hi <strong>{$escapedName}</strong>,</p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 20px 0;">
                                Thank you for your interest in joining the BGC Night Market community through BuzzarFeed.
                            </p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 30px 0;">
                                After careful review, we are unable to approve your application for <strong>"{$escapedStallName}"</strong> at this time.
                            </p>
                            
                            {$reasonSection}
                            
                            <div style="background-color: #F5F5F5; padding: 25px; border-radius: 8px; margin: 30px 0;">
                                <h3 style="margin: 0 0 15px 0; font-size: 18px; color: #2C2C2C;">What You Can Do Next</h3>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">📋</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Review the application requirements and ensure all documentation is complete</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">✏️</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Address the feedback provided in the reason above</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width: 30px; vertical-align: top;">
                                                        <span style="font-size: 20px;">📤</span>
                                                    </td>
                                                    <td style="font-size: 15px; color: #666666; padding-left: 10px;">Submit a new application when you're ready</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 0 0 20px 0;">
                                We encourage you to reapply in the future. Our goal is to maintain high-quality standards for all vendors on our platform to ensure the best experience for our customers.
                            </p>
                            
                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 40px 0;">
                                <a href="{$applyUrl}" style="display: inline-block; background-color: #ED6027; color: #FFFFFF; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(237,96,39,0.3);">
                                    Submit New Application
                                </a>
                            </div>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 30px 0 0 0;">
                                If you have any questions or need clarification, please don't hesitate to contact our support team.
                            </p>
                            
                            <p style="font-size: 16px; color: #666666; line-height: 1.6; margin: 20px 0 0 0;">
                                Thank you for your understanding.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2C2C2C; padding: 30px; text-align: center; border-radius: 0 0 12px 12px;">
                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; font-weight: 600;">
                                © 2025 BuzzarFeed. All rights reserved.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.6);">
                                BGC Night Market, Bonifacio Global City, Manila
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
    
    /**
     * Get plain text version of application approval email
     */
    private function getApplicationApprovalTextVersion(string $name, string $stallName, string $reviewNotes): string
    {
        $notesText = !empty($reviewNotes) ? "\n\nAdmin Notes:\n{$reviewNotes}\n" : '';
        $manageStallUrl = BASE_URL . 'manage-stall.php';
        
        return <<<TEXT
CONGRATULATIONS! Your Application Has Been Approved

Hi {$name},

Great news! We're thrilled to inform you that your stall application for "{$stallName}" has been approved!

Your stall is now live on BuzzarFeed and ready to welcome hungry customers from the BGC Night Market community.
{$notesText}
What's Next?

📝 Complete your stall information and upload your menu items
📍 Set your stall location on the interactive map
⏰ Update your operating hours to let customers know when you're open
⭐ Monitor and respond to customer reviews

Manage Your Stall: {$manageStallUrl}

Welcome to the BuzzarFeed family! We're excited to have you join our vibrant food community.

If you have any questions, feel free to reach out to our support team.

© 2025 BuzzarFeed. All rights reserved.
BGC Night Market, Bonifacio Global City
TEXT;
    }
    
    /**
     * Get plain text version of application decline email
     */
    private function getApplicationDeclineTextVersion(string $name, string $stallName, string $reviewNotes): string
    {
        $reasonText = !empty($reviewNotes) ? "\n\nReason for Decline:\n{$reviewNotes}\n" : '';
        $applyUrl = BASE_URL . 'register-stall.php';
        
        return <<<TEXT
Application Update

Hi {$name},

Thank you for your interest in joining the BGC Night Market community through BuzzarFeed.

After careful review, we are unable to approve your application for "{$stallName}" at this time.
{$reasonText}
What You Can Do Next:

📋 Review the application requirements and ensure all documentation is complete
✏️ Address the feedback provided in the reason above
📤 Submit a new application when you're ready

We encourage you to reapply in the future. Our goal is to maintain high-quality standards for all vendors on our platform to ensure the best experience for our customers.

Submit New Application: {$applyUrl}

If you have any questions or need clarification, please don't hesitate to contact our support team.

Thank you for your understanding.

© 2025 BuzzarFeed. All rights reserved.
BGC Night Market, Bonifacio Global City
TEXT;
    }
}
