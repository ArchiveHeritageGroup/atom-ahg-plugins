<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Email Service for sending notifications.
 *
 * Provides centralized email functionality for all AHG plugins.
 * Supports both PHPMailer (SMTP) and native PHP mail().
 */
class EmailService
{
    private static array $settings = [];
    public static bool $loaded = false;

    /**
     * Load settings from database.
     */
    private static function loadSettings(): void
    {
        if (self::$loaded) {
            return;
        }

        try {
            $rows = DB::table('email_setting')->get();
            foreach ($rows as $row) {
                self::$settings[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            error_log('EmailService: Failed to load settings: ' . $e->getMessage());
        }
        self::$loaded = true;
    }

    /**
     * Get a setting value.
     */
    public static function getSetting(string $key, $default = null)
    {
        self::loadSettings();

        return self::$settings[$key] ?? $default;
    }

    /**
     * Check if email is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) self::getSetting('smtp_enabled', false);
    }

    /**
     * Send email using SMTP.
     */
    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        if (!self::isEnabled()) {
            error_log("Email not sent (disabled): To: {$to}, Subject: {$subject}");

            return false;
        }

        $host = self::getSetting('smtp_host');
        $port = (int) self::getSetting('smtp_port', 587);
        $encryption = self::getSetting('smtp_encryption', 'tls');
        $username = self::getSetting('smtp_username');
        $password = self::getSetting('smtp_password');
        $fromEmail = self::getSetting('smtp_from_email');
        $fromName = self::getSetting('smtp_from_name');

        try {
            // Use PHPMailer if available, otherwise use mail()
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return self::sendWithPHPMailer($to, $subject, $body, [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'username' => $username,
                    'password' => $password,
                    'from_email' => $fromEmail,
                    'from_name' => $fromName,
                ]);
            } else {
                return self::sendWithMail($to, $subject, $body, $fromEmail, $fromName);
            }
        } catch (\Exception $e) {
            error_log('Email send failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send using PHP mail().
     */
    private static function sendWithMail(string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
    {
        $headers = [
            'From' => "{$fromName} <{$fromEmail}>",
            'Reply-To' => $fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $body, $headerString);
    }

    /**
     * Send using PHPMailer.
     */
    private static function sendWithPHPMailer(string $to, string $subject, string $body, array $config): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        if ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($config['encryption'] === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    }

    /**
     * Replace placeholders in template.
     */
    public static function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    /**
     * Get base URL for email links.
     */
    private static function getBaseUrl(): string
    {
        if (class_exists('sfConfig')) {
            return \sfConfig::get('app_siteBaseUrl', '');
        }

        return '';
    }

    /**
     * Send researcher registration pending email.
     */
    public static function sendResearcherPending(object $researcher): bool
    {
        $subject = self::getSetting('email_researcher_pending_subject');
        $body = self::getSetting('email_researcher_pending_body');

        $body = self::parseTemplate($body, [
            'name' => $researcher->first_name . ' ' . $researcher->last_name,
            'email' => $researcher->email,
        ]);

        $sent = self::send($researcher->email, $subject, $body);

        // Notify admin
        $adminEmail = self::getSetting('notify_new_researcher');
        if ($adminEmail) {
            $adminSubject = self::getSetting('email_admin_new_researcher_subject');
            $adminBody = self::getSetting('email_admin_new_researcher_body');

            $baseUrl = self::getBaseUrl();
            $adminBody = self::parseTemplate($adminBody, [
                'name' => $researcher->first_name . ' ' . $researcher->last_name,
                'email' => $researcher->email,
                'institution' => $researcher->institution ?? 'Not specified',
                'review_url' => $baseUrl . '/index.php/research/viewResearcher?id=' . $researcher->id,
            ]);

            self::send($adminEmail, $adminSubject, $adminBody);
        }

        return $sent;
    }

    /**
     * Send researcher approved email.
     */
    public static function sendResearcherApproved(object $researcher): bool
    {
        $subject = self::getSetting('email_researcher_approved_subject');
        $body = self::getSetting('email_researcher_approved_body');

        $baseUrl = self::getBaseUrl();
        $body = self::parseTemplate($body, [
            'name' => $researcher->first_name . ' ' . $researcher->last_name,
            'login_url' => $baseUrl . '/index.php/user/login',
        ]);

        return self::send($researcher->email, $subject, $body);
    }

    /**
     * Send researcher rejected email.
     */
    public static function sendResearcherRejected(object $researcher, string $reason = ''): bool
    {
        $subject = self::getSetting('email_researcher_rejected_subject');
        $body = self::getSetting('email_researcher_rejected_body');

        $body = self::parseTemplate($body, [
            'name' => $researcher->first_name . ' ' . $researcher->last_name,
            'reason' => $reason ?: 'Not specified',
        ]);

        return self::send($researcher->email, $subject, $body);
    }

    /**
     * Send password reset email.
     */
    public static function sendPasswordReset(object $user, string $resetUrl): bool
    {
        $subject = self::getSetting('email_password_reset_subject');
        $body = self::getSetting('email_password_reset_body');

        $body = self::parseTemplate($body, [
            'name' => $user->username,
            'reset_url' => $resetUrl,
        ]);

        return self::send($user->email, $subject, $body);
    }

    /**
     * Send booking confirmed email.
     */
    public static function sendBookingConfirmed(object $booking, object $researcher): bool
    {
        $subject = self::getSetting('email_booking_confirmed_subject');
        $body = self::getSetting('email_booking_confirmed_body');

        $body = self::parseTemplate($body, [
            'name' => $researcher->first_name . ' ' . $researcher->last_name,
            'date' => $booking->booking_date,
            'time' => substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5),
            'room' => $booking->room_name ?? 'Reading Room',
        ]);

        return self::send($researcher->email, $subject, $body);
    }

    /**
     * Test email configuration.
     */
    public static function testConnection(string $testEmail): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Email is disabled'];
        }

        try {
            $sent = self::send($testEmail, 'Test Email', 'This is a test email from the Archive system.');

            return [
                'success' => $sent,
                'message' => $sent ? 'Test email sent successfully' : 'Failed to send test email',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reset loaded settings (for testing).
     */
    public static function reset(): void
    {
        self::$settings = [];
        self::$loaded = false;
    }
}
