<?php

/**
 * EmailService wrapper for backward compatibility.
 *
 * @deprecated Use \AhgCore\Services\EmailService directly.
 *             This file will be removed in a future version.
 */

// Load the canonical EmailService from ahgCorePlugin
$coreEmailService = sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Services/EmailService.php';
if (file_exists($coreEmailService)) {
    require_once $coreEmailService;

    // Create an alias for backward compatibility
    if (!class_exists('EmailService', false)) {
        class_alias(\AhgCore\Services\EmailService::class, 'EmailService');
    }
} else {
    // Fallback: define a stub that logs deprecation warning
    if (!class_exists('EmailService', false)) {
        class EmailService
        {
            public static function __callStatic($name, $arguments)
            {
                error_log('EmailService: ahgCorePlugin not loaded. Please enable ahgCorePlugin.');

                return false;
            }
        }
    }
}
