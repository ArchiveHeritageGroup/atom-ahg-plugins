<?php

declare(strict_types=1);

namespace AhgCore\Core;

/**
 * A voice for catch blocks that return an empty value (#293).
 *
 * About 250 catches across the plugins swallow an exception and return null,
 * false, [] or 0. Most are right to: a missing optional table really does mean
 * "nothing here". The trouble is that the code cannot say which, so a wrong
 * query looks exactly like no data - IiifManifestV3Service read a column that
 * does not exist, and every manifest silently lost its reading direction.
 *
 * This changes nothing the caller sees. It records where and why, and only
 * when asked: set `app_ahg_log_swallowed: true` (config/app.yml, all:) or the
 * environment variable AHG_LOG_SWALLOWED=1, reproduce, and read the PHP error
 * log. Off by default, because the legitimate cases fire on ordinary page
 * views and would bury real errors - which is also why this does not write to
 * ahg_error_log, the log administrators read.
 *
 * Each location is reported once per request. Never throws.
 */
final class AhgLog
{
    private static ?bool $enabled = null;

    /** @var array<string, true> */
    private static array $seen = [];

    public static function swallowed(\Throwable $e, string $where): void
    {
        try {
            if (null === self::$enabled) {
                self::$enabled = '1' === (string) getenv('AHG_LOG_SWALLOWED')
                    || (class_exists('sfConfig', false) && (bool) \sfConfig::get('app_ahg_log_swallowed', false));
            }
            if (!self::$enabled || isset(self::$seen[$where])) {
                return;
            }
            self::$seen[$where] = true;

            error_log(sprintf(
                '[ahg swallowed] %s: %s: %s (%s:%d)',
                $where,
                get_class($e),
                $e->getMessage(),
                basename($e->getFile()),
                $e->getLine()
            ));
        } catch (\Throwable $ignored) {
            // Logging must never add a failure to the one being handled.
        }
    }
}
