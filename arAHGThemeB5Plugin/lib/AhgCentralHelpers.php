<?php
/**
 * AHG Central Helpers - Shared utility functions for all plugins.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgCentralHelpers
{
    /**
     * Format bytes to human-readable string.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), $precision) . ' ' . $sizes[$i];
    }

    /**
     * Parse human-readable size to bytes.
     */
    public static function parseSize(string $size): int
    {
        $size = strtoupper(trim($size));
        $units = ['B' => 0, 'K' => 1, 'KB' => 1, 'M' => 2, 'MB' => 2, 'G' => 3, 'GB' => 3, 'T' => 4, 'TB' => 4];
        if (preg_match('/^(\d+(?:\.\d+)?)\s*([A-Z]+)?$/', $size, $matches)) {
            $value = (float) $matches[1];
            $unit = $matches[2] ?? 'B';
            $exp = $units[$unit] ?? 0;
            return (int) ($value * pow(1024, $exp));
        }
        return (int) $size;
    }

    /**
     * Get maximum upload size from PHP config.
     */
    public static function getMaxUploadSize(): int
    {
        $uploadMax = self::parseSize(ini_get('upload_max_filesize'));
        $postMax = self::parseSize(ini_get('post_max_size'));
        return min($uploadMax, $postMax);
    }

    /**
     * Sanitize filename for safe storage.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }

    /**
     * Get file extension.
     */
    public static function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Truncate text to specified length.
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Escape HTML special characters.
     */
    public static function escape(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate UUID v4.
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Format date for display.
     */
    public static function formatDate(?string $date, string $format = 'Y-m-d'): string
    {
        if (!$date) {
            return '';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '';
    }

    /**
     * Format datetime for display.
     */
    public static function formatDateTime(?string $date, string $format = 'Y-m-d H:i:s'): string
    {
        return self::formatDate($date, $format);
    }

    /**
     * Get relative time (e.g., "2 hours ago").
     */
    public static function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
        if ($diff < 604800) return floor($diff / 86400) . ' days ago';
        if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
        
        return date('Y-m-d', $time);
    }

    /**
     * Get current culture/locale.
     */
    public static function getCurrentCulture(): string
    {
        try {
            return sfContext::getInstance()->getUser()->getCulture();
        } catch (Exception $e) {
            return 'en';
        }
    }

    /**
     * Check if current user is administrator.
     */
    public static function isAdmin(): bool
    {
        try {
            $user = sfContext::getInstance()->getUser();
            return $user->isAdministrator() || $user->isSuperUser();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user has report access.
     */
    public static function hasReportAccess(): bool
    {
        try {
            $user = sfContext::getInstance()->getUser();
            return $user->isAdministrator() 
                || $user->isSuperUser() 
                || (method_exists($user, 'isReportUser') && $user->isReportUser());
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a Monolog logger instance.
     */
    public static function createLogger(string $channel, string $filename = null): \Psr\Log\LoggerInterface
    {
        $logger = new \Monolog\Logger($channel);
        $logDir = sfConfig::get('sf_log_dir', '/var/log/archive');
        $logFile = $filename ?? $channel . '.log';
        
        $handler = new \Monolog\Handler\StreamHandler(
            $logDir . '/' . $logFile,
            \Monolog\Logger::INFO
        );
        $logger->pushHandler($handler);
        
        return $logger;
    }

    /**
     * Get report logger.
     */
    public static function getReportLogger(string $name = 'reports'): \Psr\Log\LoggerInterface
    {
        static $loggers = [];
        
        if (!isset($loggers[$name])) {
            $loggers[$name] = self::createLogger($name, 'atom-reports.log');
        }
        
        return $loggers[$name];
    }

    /**
     * JSON encode with error handling.
     */
    public static function jsonEncode($data, int $flags = JSON_PRETTY_PRINT): string
    {
        $json = json_encode($data, $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON encode error: ' . json_last_error_msg());
        }
        return $json;
    }

    /**
     * JSON decode with error handling.
     */
    public static function jsonDecode(string $json, bool $assoc = true)
    {
        $data = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON decode error: ' . json_last_error_msg());
        }
        return $data;
    }

    /**
     * Set flash message.
     */
    public static function setFlash(string $type, string $message): void
    {
        try {
            sfContext::getInstance()->getUser()->setFlash($type, $message);
        } catch (Exception $e) {
            // Ignore
        }
    }

    /**
     * Send JSON response (for use in sfAction context).
     *
     * @param sfAction $action The action instance
     * @param mixed $data Data to encode
     * @param int $status HTTP status code
     * @return string sfView::NONE
     */
    public static function apiJsonResponse($action, $data, int $status = 200): string
    {
        $action->getResponse()->setStatusCode($status);
        $action->getResponse()->setContentType('application/json');
        $action->getResponse()->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return sfView::NONE;
    }

    /**
     * Send JSON error response.
     *
     * @param sfAction $action The action instance
     * @param string $message Error message
     * @param int $status HTTP status code
     * @return string sfView::NONE
     */
    public static function apiJsonError($action, string $message, int $status = 400): string
    {
        return self::apiJsonResponse($action, ['error' => $message], $status);
    }
}
