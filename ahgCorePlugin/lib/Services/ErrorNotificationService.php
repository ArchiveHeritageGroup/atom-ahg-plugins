<?php

namespace AhgCore\Services;

/**
 * Error Notification Service
 *
 * Registers global error/exception handlers and sends email
 * notifications to the configured admin address on fatal errors.
 *
 * Rate-limits emails via temp lock files to prevent mail floods.
 */
class ErrorNotificationService
{
    private static bool $registered = false;
    private static string $lockDir = '/tmp/ahg_error_locks';
    private static int $lockTtl = 300; // 5 minutes per unique error

    /**
     * Register error/exception/shutdown handlers.
     *
     * Only activates when SMTP is enabled and notify_errors is set.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        set_exception_handler([self::class, 'handleException']);

        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle uncaught exceptions.
     */
    public static function handleException(\Throwable $e): void
    {
        // Log it first (always)
        error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        self::sendErrorEmail(
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        // Re-throw for Symfony's handler if available
        if (function_exists('sfContext') || class_exists('sfContext', false)) {
            try {
                $context = \sfContext::getInstance();
                if ($context) {
                    // Let Symfony handle the display
                    return;
                }
            } catch (\Exception $ex) {
                // sfContext not available, fall through
            }
        }
    }

    /**
     * Handle fatal errors on shutdown.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        // Only handle fatal errors
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($error['type'], $fatalTypes)) {
            return;
        }

        self::sendErrorEmail(
            $error['message'],
            $error['file'],
            $error['line'],
            'Fatal error (no stack trace available)'
        );
    }

    /**
     * Send error notification email if configured and not rate-limited.
     */
    private static function sendErrorEmail(string $message, string $file, int $line, string $trace): void
    {
        try {
            // Ensure EmailService is available
            $emailServiceFile = dirname(__DIR__) . '/Services/EmailService.php';
            if (!class_exists('\AhgCore\Services\EmailService', false)) {
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                } else {
                    return;
                }
            }

            // Check if email is enabled
            if (!EmailService::isEnabled()) {
                return;
            }

            // Check if error notification address is configured
            $notifyEmail = EmailService::getSetting('notify_errors');
            if (empty($notifyEmail)) {
                return;
            }

            // Rate-limit: one email per unique error per 5 minutes
            if (!self::acquireLock($file, $line, $message)) {
                return;
            }

            // Build email content
            $subject = EmailService::getSetting('email_error_alert_subject', 'System Error Alert');
            $bodyTemplate = EmailService::getSetting('email_error_alert_body');

            $url = self::getCurrentUrl();
            $timestamp = date('Y-m-d H:i:s');
            $hostname = gethostname() ?: 'unknown';

            if ($bodyTemplate) {
                $body = EmailService::parseTemplate($bodyTemplate, [
                    'message' => $message,
                    'file' => $file,
                    'line' => (string) $line,
                    'url' => $url,
                    'timestamp' => $timestamp,
                    'trace' => $trace,
                    'hostname' => $hostname,
                ]);
            } else {
                $body = "System Error Alert\n"
                    . "==================\n\n"
                    . "Time: {$timestamp}\n"
                    . "Host: {$hostname}\n"
                    . "URL: {$url}\n\n"
                    . "Error: {$message}\n"
                    . "File: {$file}\n"
                    . "Line: {$line}\n\n"
                    . "Stack Trace:\n{$trace}\n";
            }

            EmailService::send($notifyEmail, $subject, $body);
        } catch (\Throwable $e) {
            // Never let the error notification itself cause an error
            error_log('ErrorNotificationService: Failed to send error email: ' . $e->getMessage());
        }
    }

    /**
     * Rate-limit via temp lock files.
     *
     * Returns true if we should send, false if rate-limited.
     */
    private static function acquireLock(string $file, int $line, string $message): bool
    {
        if (!is_dir(self::$lockDir)) {
            @mkdir(self::$lockDir, 0755, true);
        }

        // Create a unique key for this error
        $key = md5($file . ':' . $line . ':' . substr($message, 0, 200));
        $lockFile = self::$lockDir . '/err_' . $key . '.lock';

        // Check if lock exists and is still valid
        if (file_exists($lockFile)) {
            $age = time() - filemtime($lockFile);
            if ($age < self::$lockTtl) {
                return false; // Rate-limited
            }
        }

        // Create/update lock file
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));

        // Clean up old lock files (older than 1 hour)
        self::cleanupLocks();

        return true;
    }

    /**
     * Remove stale lock files.
     */
    private static function cleanupLocks(): void
    {
        // Only clean up 1% of the time to avoid overhead
        if (mt_rand(1, 100) !== 1) {
            return;
        }

        $files = @glob(self::$lockDir . '/err_*.lock');
        if (!$files) {
            return;
        }

        $expiry = time() - 3600; // 1 hour
        foreach ($files as $f) {
            if (filemtime($f) < $expiry) {
                @unlink($f);
            }
        }
    }

    /**
     * Get the current request URL if available.
     */
    private static function getCurrentUrl(): string
    {
        if (php_sapi_name() === 'cli') {
            global $argv;

            return 'CLI: ' . implode(' ', $argv ?? ['unknown']);
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $scheme . '://' . $host . $uri;
    }
}
