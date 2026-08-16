<?php

declare(strict_types=1);

namespace AhgSiteRecordPlugin\Listeners;

use AhgSiteRecordPlugin\Services\LocalityVisibilityService;

/**
 * Withholds a raw ISAAR field that carries locality, from readers without
 * clearance.
 *
 * Structuring locality into ahg_site_record gates the structured copy, but the
 * ORIGINAL field keeps rendering the same information to everyone. Measured on
 * the RARI development instance after seeding 7,585 site records: the site
 * record panel correctly showed nothing to the public while the authority record
 * page, four rows further up, still printed
 *
 *     Internal structures/genealogy   E?.3.1 pl location th i: x491 Map sheet: 1821DA
 *
 * RARI's own instance deals with this by commenting the render call out of the
 * ISAAR templates, which hides it from staff too - exactly what issue #299 says
 * to stop doing. `plugins/sfIsaarPlugin` is base AtoM and locked, so the gate
 * lives here instead, and the field can go back to being rendered normally.
 *
 * OFF BY DEFAULT. "Internal structures/genealogy" is a general-purpose ISAAR
 * field; only some deployments keep locality in it, and blanking it everywhere
 * would destroy legitimate public content on the rest. Enable per instance with
 * the `site_record_gate_locality_field` setting.
 */
class LocalityFieldRedactor
{
    /** Authority records are served by the standard module, not an 'actor' one. */
    private const VIEW_MODULES = ['actor', 'sfIsaarPlugin'];

    private const SETTING = 'site_record_gate_locality_field';

    /** The ISAAR field to withhold. Overridable for a translated interface. */
    private const LABEL_SETTING = 'site_record_locality_field_label';
    private const DEFAULT_LABEL = 'Internal structures/genealogy';

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->redact($event, $content);
        } catch (\Throwable $e) {
            // Never take a record page down. Note this fails OPEN - the field
            // stays visible - which is why the fix belongs at the source too:
            // an instance relying on this should also consider whether the field
            // needs to hold locality at all.
            return $content;
        }
    }

    private function redact(\sfEvent $event, $content)
    {
        if (!$this->enabled()) {
            return $content;
        }

        // Cleared readers see the field untouched.
        if (LocalityVisibilityService::userHasClearance()) {
            return $content;
        }

        $context = \sfContext::getInstance();

        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true)
            || 'index' !== $context->getActionName()) {
            return $content;
        }

        $label = $this->label();

        if (false === stripos((string) $content, $label)) {
            return $content;
        }

        // Matches one rendered field row and replaces only the value, leaving the
        // row and its heading in place - so the reader can tell the field exists
        // and is withheld, rather than silently seeing nothing.
        $pattern = '#(<div class="field[^"]*"[^>]*>\s*<h3[^>]*>\s*'
            .preg_quote($label, '#')
            .'\s*</h3>\s*<div[^>]*>)(.*?)(</div>\s*</div>)#si';

        $notice = '<span class="text-muted fst-italic">'
            .'Withheld. Site locality is restricted to authorised staff.</span>';

        $out = preg_replace_callback(
            $pattern,
            static function (array $m) use ($notice) {
                // An empty field stays empty - a notice on a blank row would
                // imply something is being hidden when nothing is.
                return '' === trim($m[2]) ? $m[0] : $m[1].$notice.$m[3];
            },
            (string) $content,
            1
        );

        return null === $out ? $content : $out;
    }

    private function enabled(): bool
    {
        return $this->setting(self::SETTING, '0') === '1';
    }

    private function label(): string
    {
        $label = $this->setting(self::LABEL_SETTING, '');

        return '' !== $label ? $label : self::DEFAULT_LABEL;
    }

    private function setting(string $key, string $default): string
    {
        if (class_exists('\AtomExtensions\Services\AhgSettingsService')) {
            try {
                return (string) \AtomExtensions\Services\AhgSettingsService::get($key, $default);
            } catch (\Throwable $e) {
                return $default;
            }
        }

        return $default;
    }
}
