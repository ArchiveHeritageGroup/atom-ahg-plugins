<?php

namespace AhgCore\Listeners;

/**
 * Let an instance run in its own timezone.
 *
 * AtoM ships `default_timezone: America/Vancouver` in
 * apps/qubit/config/settings.yml - Artefactual's own timezone, and a sensible
 * default for them. Every deployment elsewhere inherits it, and because symfony
 * applies it with date_default_timezone_set(), everything PHP timestamps lands in
 * Vancouver time.
 *
 * On PSIS that put every row in ahg_audit_log 9 hours behind local time - the
 * audit trail said 23:28 while the clock on the wall said 08:28 the next morning.
 * For a log kept for POPIA and NARSSA purposes that is not cosmetic, and the
 * from/to date filters on the audit screen inherit the same skew, so a search for
 * "today" quietly misses most of today.
 *
 * WHY A LISTENER
 *
 * settings.yml lives under apps/, which is base AtoM and not ours to edit. There
 * is no project-level settings.yml to override it from, and an app-level value
 * would win over one anyway. A plugin's initialize() runs BEFORE symfony's
 * initConfiguration(), so anything set there is overwritten moments later.
 *
 * context.load_factories fires once per request, after configuration and before
 * any action runs, which is the first point where the value will stick. It is the
 * same hook forty other AHG listeners already use.
 *
 * OPT-IN, DELIBERATELY
 *
 * Nothing changes unless an instance sets ahg_settings.default_timezone. AtoM
 * is deployed internationally and this code is shared across every instance, so
 * there is no correct value to hardcode here - only a correct value per
 * deployment. An unset or unrecognised value leaves AtoM's behaviour untouched.
 */
class TimezoneOverride
{
    private static bool $applied = false;

    public static function apply(\sfEvent $event): void
    {
        try {
            // Once per request. context.load_factories can fire again for a
            // second context, and re-applying is pointless work on every page.
            if (self::$applied) {
                return;
            }

            $timezone = self::configured();

            if (null === $timezone) {
                return;
            }

            self::$applied = true;

            if ($timezone === date_default_timezone_get()) {
                return;
            }

            date_default_timezone_set($timezone);
        } catch (\Throwable $e) {
            // A wrong timezone is a bad day; a white page is a worse one.
        }
    }

    /**
     * The instance's configured timezone, or null to leave AtoM's alone.
     *
     * Validated against PHP's own list rather than trusted: an unrecognised
     * name makes date_default_timezone_set() emit a warning and keep the old
     * value, which on a busy site is a warning per request and no fix.
     */
    private static function configured(): ?string
    {
        try {
            $value = \AtomExtensions\Services\AhgSettingsService::get('default_timezone', '');
        } catch (\Throwable $e) {
            return null;
        }

        $value = trim((string) $value);

        if ('' === $value) {
            return null;
        }

        return in_array($value, \DateTimeZone::listIdentifiers(), true) ? $value : null;
    }
}
