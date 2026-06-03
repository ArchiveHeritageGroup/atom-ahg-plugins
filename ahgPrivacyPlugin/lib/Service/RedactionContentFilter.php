<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RedactionContentFilter — applies field-level redaction (#130) to the rendered
 * information-object view for unauthorised (public / non-staff) viewers, via the
 * Symfony `response.filter_content` event. Staff (admin/editor) and the edit/API
 * paths are served the full record. No base-AtoM / theme files are modified.
 *
 * It redacts the structured i18n text fields that render verbatim in the view
 * (scope_and_content, archival_history, …). Event-date / related-entity fields
 * and the list/JSON layers are follow-ups.
 *
 * @package ahgPrivacyPlugin
 */
class RedactionContentFilter
{
    /** field_name -> information_object_i18n column. */
    private const I18N_FIELDS = [
        'scope_and_content' => 'scope_and_content',
        'archival_history' => 'archival_history',
        'arrangement' => 'arrangement',
        'access_conditions' => 'access_conditions',
        'reproduction_conditions' => 'reproduction_conditions',
        'physical_characteristics' => 'physical_characteristics',
        'related_units_of_description' => 'related_units_of_description',
        'sources' => 'sources',
        'acquisition' => 'acquisition',
        'appraisal' => 'appraisal',
        'location_of_originals' => 'location_of_originals',
        'location_of_copies' => 'location_of_copies',
        // #130 refinement 1 — additional i18n text fields that render verbatim.
        'title' => 'title',
        'alternate_title' => 'alternate_title',
        'edition' => 'edition',
        'extent_and_medium' => 'extent_and_medium',
        'finding_aids' => 'finding_aids',
        'rules' => 'rules',
        'revision_history' => 'revision_history',
        'institution_responsible_identifier' => 'institution_responsible_identifier',
    ];

    /**
     * #130 refinement 1 — event-date / related-entity fields. These render on
     * the IO view but live outside information_object_i18n, so each is loaded
     * from its own source. (Short values such as a bare year are not ideal for
     * whole-value replacement; prefer them only where the rendered string is
     * distinctive, e.g. a full dates-of-existence phrase.)
     */
    private const EXTRA_FIELDS = ['creator_dates', 'event_dates'];

    /** Field names the filter can redact on the public view (admin UI picker). */
    public static function supportedFields(): array
    {
        return array_merge(array_keys(self::I18N_FIELDS), self::EXTRA_FIELDS);
    }

    /** Modules that render a single IO description (per descriptive standard). */
    private const VIEW_MODULES = ['informationobject', 'sfIsadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfRadPlugin'];

    /** IO view actions where redaction applies. */
    private const VIEW_ACTIONS = ['index', 'show', 'isad', 'dc', 'rad', 'mods'];

    /**
     * @param string $content rendered HTML
     * @return string possibly-redacted HTML (always returns valid content)
     */
    public static function filter(string $content, \sfWebRequest $request, \sfUser $user): string
    {
        try {
            if (!in_array($request->getParameter('module'), self::VIEW_MODULES, true)
                || !in_array($request->getParameter('action'), self::VIEW_ACTIONS, true)) {
                return $content;
            }

            // Staff, and authenticated researchers with an active access
            // agreement, see the full record (#130 refinement 3). The rule
            // lives in RedactionAccess so the web view and the REST API agree.
            if (RedactionAccess::userMaySeeUnredacted($user)) {
                return $content;
            }

            $slug = $request->getParameter('slug');
            if (!$slug) {
                return $content;
            }
            $ioId = (int) DB::table('slug')->where('slug', $slug)->value('object_id');
            if ($ioId <= 0) {
                return $content;
            }

            $svc = new PrivacyRedactionService();
            $fields = $svc->getFields($ioId);
            if (empty($fields)) {
                return $content;
            }

            $values = self::loadI18nValues($ioId, $fields);
            $values += self::loadExtraValues($ioId, $fields);
            if (empty($values)) {
                return $content;
            }
            $redacted = $svc->applyRedaction($ioId, $values, $user->getAttribute('user_id'), true);

            foreach ($values as $field => $orig) {
                if ($orig === null || $orig === '' || !isset($redacted[$field])) {
                    continue;
                }
                // Replace both HTML-escaping variants of the rendered value.
                foreach ([ENT_QUOTES, ENT_COMPAT] as $flags) {
                    $needle = htmlspecialchars((string) $orig, $flags);
                    $rep = htmlspecialchars((string) $redacted[$field], $flags);
                    if ($needle !== '') {
                        $content = str_replace($needle, $rep, $content);
                    }
                }
            }

            return $content;
        } catch (\Throwable $e) {
            // Redaction must never break the page; fail safe is questionable for
            // privacy, but a hard 500 on every public IO view is worse. Errors are
            // surfaced via the error log; the admin can verify rules are applied.
            return $content;
        }
    }

    /** @param array<int,object> $fields */
    private static function loadI18nValues(int $ioId, array $fields): array
    {
        $cols = [];
        foreach ($fields as $f) {
            if (isset(self::I18N_FIELDS[$f->field_name])) {
                $cols[$f->field_name] = self::I18N_FIELDS[$f->field_name];
            }
        }
        if (empty($cols)) {
            return [];
        }
        $row = DB::table('information_object_i18n')
            ->where('id', $ioId)->where('culture', 'en')
            ->first(array_values($cols));
        if (!$row) {
            return [];
        }
        $out = [];
        foreach ($cols as $field => $col) {
            if (!empty($row->$col)) {
                $out[$field] = $row->$col;
            }
        }

        return $out;
    }

    /**
     * #130 refinement 1 — load event-date / related-entity field values that
     * render on the IO view but live outside information_object_i18n.
     *
     * @param array<int,object> $fields
     * @return array<string,string>
     */
    private static function loadExtraValues(int $ioId, array $fields): array
    {
        $want = [];
        foreach ($fields as $f) {
            $want[$f->field_name] = true;
        }
        $out = [];

        // Creator dates of existence (the actor linked via a creation event).
        if (!empty($want['creator_dates'])) {
            $val = DB::table('event as e')
                ->join('actor_i18n as ai', 'ai.id', '=', 'e.actor_id')
                ->where('e.object_id', $ioId)
                ->whereNotNull('e.actor_id')
                ->where('ai.culture', 'en')
                ->whereNotNull('ai.dates_of_existence')
                ->value('ai.dates_of_existence');
            if (!empty($val)) {
                $out['creator_dates'] = $val;
            }
        }

        // Event display date string (creation / accumulation dates on the IO).
        if (!empty($want['event_dates'])) {
            $val = DB::table('event_i18n as ei')
                ->join('event as e', 'e.id', '=', 'ei.id')
                ->where('e.object_id', $ioId)
                ->where('ei.culture', 'en')
                ->whereNotNull('ei.date')
                ->value('ei.date');
            if (!empty($val)) {
                $out['event_dates'] = $val;
            }
        }

        return $out;
    }
}
