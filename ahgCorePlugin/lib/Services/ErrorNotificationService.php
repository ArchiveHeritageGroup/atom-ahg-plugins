<?php

namespace AhgCore\Services;

/**
 * Error Notification Service
 *
 * Registers global error/exception handlers and sends email
 * notifications to the configured admin address on fatal errors.
 *
 * Features:
 *   - Configurable throttle TTL (per-error dedup window)
 *   - Daily cap on total alert emails
 *   - Environment gating (skip in debug mode)
 *   - Rich context: request ID, HTTP method, client IP, user agent, exception class
 *   - Async dispatch via QueueService with sync fallback
 *   - PII-safe: no POST body, cookies, or auth headers captured
 */
class ErrorNotificationService
{
    private static bool $registered = false;
    private static string $lockDir = '/tmp/ahg_error_locks';
    private static int $lockTtl = 300; // default, overridden by config
    private static int $dailyCap = 50; // default, overridden by config
    private static bool $configLoaded = false;

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
     * Load throttle/cap/gating config from email_setting table.
     */
    private static function loadConfig(): void
    {
        if (self::$configLoaded) {
            return;
        }
        self::$configLoaded = true;

        try {
            $emailServiceFile = dirname(__DIR__) . '/Services/EmailService.php';
            if (!class_exists('\AhgCore\Services\EmailService', false)) {
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                } else {
                    return;
                }
            }

            $ttl = EmailService::getSetting('error_alert_throttle_ttl');
            if ($ttl !== null && is_numeric($ttl) && (int) $ttl > 0) {
                self::$lockTtl = (int) $ttl;
            }

            $cap = EmailService::getSetting('error_alert_daily_cap');
            if ($cap !== null && is_numeric($cap)) {
                self::$dailyCap = (int) $cap;
            }
        } catch (\Throwable $e) {
            // Silently use defaults
        }
    }

    /**
     * Check if alerts are enabled (not in debug mode, setting enabled).
     */
    private static function isAlertEnabled(): bool
    {
        try {
            $emailServiceFile = dirname(__DIR__) . '/Services/EmailService.php';
            if (!class_exists('\AhgCore\Services\EmailService', false)) {
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                } else {
                    return false;
                }
            }

            // Check environment gate — skip in debug mode
            $envGate = EmailService::getSetting('error_alert_env_gate', '1');
            if ($envGate === '1' || $envGate === 'true') {
                // Check Symfony debug flag
                if (defined('SF_DEBUG') && SF_DEBUG) {
                    return false;
                }
                if (class_exists('sfConfig', false) && \sfConfig::get('sf_debug', false)) {
                    return false;
                }
            }

            // Check explicit enabled toggle
            $enabled = EmailService::getSetting('error_alert_enabled', '1');
            if ($enabled === '0' || $enabled === 'false') {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return true; // Default to enabled on config failure
        }
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
            $e->getTraceAsString(),
            $e
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
            'Fatal error (no stack trace available)',
            null
        );
    }

    /**
     * Build rich context data for the error email.
     */
    private static function buildContextData(?\Throwable $exception): array
    {
        $context = [
            'request_id' => 'N/A',
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'user_info' => 'anonymous',
            'exception_class' => $exception ? get_class($exception) : 'FatalError',
        ];

        // Request ID — from middleware static property or sfConfig
        if (class_exists('AtomFramework\Http\Middleware\RequestIdMiddleware', false)
            && \AtomFramework\Http\Middleware\RequestIdMiddleware::$requestId) {
            $context['request_id'] = \AtomFramework\Http\Middleware\RequestIdMiddleware::$requestId;
        } elseif (class_exists('sfConfig', false)) {
            $rid = \sfConfig::get('app_request_id', '');
            if ($rid) {
                $context['request_id'] = $rid;
            }
        }

        // Authenticated user ID (no PII — numeric ID only)
        try {
            if (class_exists('sfContext', false)) {
                $sfContext = \sfContext::getInstance();
                if ($sfContext) {
                    $user = $sfContext->getUser();
                    if ($user && method_exists($user, 'getUserID')) {
                        $uid = $user->getUserID();
                        if ($uid) {
                            $context['user_info'] = 'user_id:' . $uid;
                        }
                    } elseif ($user && $user->isAuthenticated()) {
                        $context['user_info'] = 'authenticated (ID unavailable)';
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore — leave as 'anonymous'
        }

        // PDOException extras
        if ($exception instanceof \PDOException) {
            $context['exception_class'] .= ' [SQLSTATE: ' . ($exception->getCode() ?: 'unknown') . ']';
        }

        return $context;
    }

    /**
     * Check daily cap — returns true if under limit.
     */
    private static function checkDailyCap(): bool
    {
        if (self::$dailyCap <= 0) {
            return true; // 0 = unlimited
        }

        if (!is_dir(self::$lockDir)) {
            @mkdir(self::$lockDir, 0755, true);
        }

        $counterFile = self::$lockDir . '/daily_count_' . date('Y-m-d') . '.count';

        $currentCount = 0;
        if (file_exists($counterFile)) {
            $currentCount = (int) @file_get_contents($counterFile);
        }

        if ($currentCount >= self::$dailyCap) {
            return false;
        }

        @file_put_contents($counterFile, (string) ($currentCount + 1));

        return true;
    }

    /**
     * Send error notification email if configured and not rate-limited.
     */
    private static function sendErrorEmail(string $message, string $file, int $line, string $trace, ?\Throwable $exception = null): void
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

            // Environment gating
            if (!self::isAlertEnabled()) {
                return;
            }

            // Load config for throttle/cap
            self::loadConfig();

            // Rate-limit: one email per unique error per TTL
            if (!self::acquireLock($file, $line, $message)) {
                return;
            }

            // Daily cap check
            if (!self::checkDailyCap()) {
                return;
            }

            // Build rich context
            $contextData = self::buildContextData($exception);

            // Build email content
            $subject = EmailService::getSetting('email_error_alert_subject', 'System Error Alert');
            $bodyTemplate = EmailService::getSetting('email_error_alert_body');

            $url = self::getCurrentUrl();
            $timestamp = date('Y-m-d H:i:s');
            $hostname = gethostname() ?: 'unknown';

            $templateData = [
                'message' => $message,
                'file' => $file,
                'line' => (string) $line,
                'url' => $url,
                'timestamp' => $timestamp,
                'trace' => $trace,
                'hostname' => $hostname,
                'request_id' => $contextData['request_id'],
                'http_method' => $contextData['http_method'],
                'client_ip' => $contextData['client_ip'],
                'user_agent' => $contextData['user_agent'],
                'user_info' => $contextData['user_info'],
                'exception_class' => $contextData['exception_class'],
            ];

            if ($bodyTemplate) {
                $body = EmailService::parseTemplate($bodyTemplate, $templateData);
            } else {
                $body = self::buildDefaultBody($templateData);
            }

            // Try async dispatch via QueueService, fall back to sync
            if (!self::dispatchAsync($notifyEmail, $subject, $body)) {
                EmailService::send($notifyEmail, $subject, $body);
            }
        } catch (\Throwable $e) {
            // Never let the error notification itself cause an error
            error_log('ErrorNotificationService: Failed to send error email: ' . $e->getMessage());
        }
    }

    /**
     * Build the default email body when no template is configured.
     */
    private static function buildDefaultBody(array $data): string
    {
        return "System Error Alert\n"
            . "==================\n\n"
            . "Time: {$data['timestamp']}\n"
            . "Host: {$data['hostname']}\n"
            . "Request ID: {$data['request_id']}\n"
            . "URL: {$data['url']}\n"
            . "Method: {$data['http_method']}\n"
            . "Client IP: {$data['client_ip']}\n"
            . "User Agent: {$data['user_agent']}\n"
            . "User: {$data['user_info']}\n\n"
            . "Exception: {$data['exception_class']}\n"
            . "Error: {$data['message']}\n"
            . "File: {$data['file']}\n"
            . "Line: {$data['line']}\n\n"
            . "Stack Trace:\n{$data['trace']}\n";
    }

    /**
     * Attempt async dispatch via QueueService.
     *
     * @return bool True if dispatched async, false if should fall back to sync
     */
    private static function dispatchAsync(string $to, string $subject, string $body): bool
    {
        try {
            if (!class_exists('\AtomFramework\Services\QueueService', false)
                && !class_exists('\AtomFramework\Services\QueueService')) {
                return false;
            }

            // Check that the queue table exists
            $capsuleClass = '\Illuminate\Database\Capsule\Manager';
            if (!class_exists($capsuleClass, false)) {
                return false;
            }

            try {
                $exists = \Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_queue_job');
                if (!$exists) {
                    return false;
                }
            } catch (\Throwable $e) {
                return false;
            }

            $queueService = new \AtomFramework\Services\QueueService();
            $queueService->dispatch(
                'error:send-alert',
                [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $body,
                ],
                'default',
                10, // high priority
                0,
                1   // max 1 attempt — don't retry error emails
            );

            return true;
        } catch (\Throwable $e) {
            error_log('ErrorNotificationService: Async dispatch failed, falling back to sync: ' . $e->getMessage());

            return false;
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

        // Also clean up old daily count files (older than 2 days)
        $countFiles = @glob(self::$lockDir . '/daily_count_*.count');
        if ($countFiles) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            foreach ($countFiles as $cf) {
                $basename = basename($cf);
                if (strpos($basename, $today) === false && strpos($basename, $yesterday) === false) {
                    @unlink($cf);
                }
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
