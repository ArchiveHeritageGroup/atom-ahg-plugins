<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RedactionContentFilter - applies field-level redaction (#130) to the rendered
 * information-object view for unauthorised (public / non-staff) viewers, via the
 * Symfony `response.filter_content` event. Staff and researchers holding an
 * active access agreement are served the full record. No base-AtoM / theme files
 * are modified.
 *
 * ## Why this layer is best-effort, and what that forces
 *
 * Redaction here operates on rendered HTML: it replaces the stored field value
 * with its redacted form by string substitution. That can only ever be
 * best-effort, because the rendered form of a value need not equal the stored
 * form - the theme may truncate it, apply nl2br, normalise whitespace, or the
 * field may itself contain markup. When the needle does not match, the original
 * text stays on the page.
 *
 * Previously that failure was silent and the page was served anyway, which made
 * a missed substitution an information disclosure. It now works the other way:
 * once a record is known to carry redaction rules, the filter VERIFIES that each
 * redacted value is absent from the output, and serves a withheld-notice page
 * instead of the record if any survived. Fail closed, never fail open.
 *
 * Known limits, stated rather than hidden:
 *  - Verification compares visible text (tags stripped, entities decoded,
 *    whitespace collapsed). A value leaking only inside an attribute value is
 *    not detected, though the substitution pass does cover the common escaped
 *    forms.
 *  - Values shorter than VERIFY_MIN_LEN are substituted but NOT verified. A bare
 *    year would otherwise match unrelated page text and withhold every record
 *    that used it. Short values remain a poor choice for whole-value redaction.
 *  - Only rendered HTML views pass through this filter. Exports (EAD/CSV),
 *    OAI-PMH, the search index and the REST API do not, and each needs its own
 *    enforcement. The REST layer has its own; see RedactionAccess.
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
        // #130 refinement 1 - additional i18n text fields that render verbatim.
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
     * #130 refinement 1 - event-date / related-entity fields. These render on
     * the IO view but live outside information_object_i18n, so each is loaded
     * from its own source. Short values such as a bare year are substituted but
     * not verified (see VERIFY_MIN_LEN); prefer these only where the rendered
     * string is distinctive, e.g. a full dates-of-existence phrase.
     */
    private const EXTRA_FIELDS = ['creator_dates', 'event_dates'];

    /**
     * Normalised values at least this long are verified after substitution, and
     * their leading VERIFY_MIN_LEN characters are the probe used to detect them.
     *
     * Probing with a leading slice rather than the whole value is what catches a
     * rendering that truncated the text - matching on the full value would find
     * nothing precisely when the theme shortened it, which is one of the cases
     * this verification exists for. Shorter values are not verified at all: they
     * collide with ordinary page text, and withholding every record that shows a
     * bare year would be a worse failure than the one being prevented.
     *
     * A probe that matches text which is not in fact the leaked value withholds
     * the record. That is the safe direction, and in practice a match on the
     * leading characters of a field marked for redaction usually IS that field
     * rendered somewhere the substitution did not reach.
     */
    private const VERIFY_MIN_LEN = 12;

    /** Modules that render a single IO description (per descriptive standard). */
    private const VIEW_MODULES = ['informationobject', 'sfIsadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfRadPlugin'];

    /**
     * Write / administrative actions that never render a public view. Everything
     * else in VIEW_MODULES is treated as a view and redacted.
     *
     * This is deliberately a deny-list. The previous allow-list of six action
     * names meant any view action added later - by AtoM, by a plugin, or by a
     * descriptive standard - silently escaped redaction. A deny-list fails the
     * safe way: an unrecognised action is redacted, not exempted. Actions that
     * render no single record are no-ops anyway, because a slug is required.
     */
    private const NON_VIEW_ACTIONS = [
        'edit', 'new', 'create', 'update', 'delete', 'add', 'multiEdit',
        'editPhysicalObject', 'move', 'copy', 'rename',
    ];

    /** Field names the filter can redact on the public view (admin UI picker). */
    public static function supportedFields(): array
    {
        return array_merge(array_keys(self::I18N_FIELDS), self::EXTRA_FIELDS);
    }

    /**
     * @param string $content rendered HTML
     * @return string redacted HTML, the original HTML when no rules apply, or a
     *                withheld notice when redaction could not be guaranteed
     */
    public static function filter(string $content, \sfWebRequest $request, \sfUser $user): string
    {
        // Set once the record is known to carry redaction rules. From that point
        // on, any failure must withhold the record rather than serve it.
        $ioId = 0;

        try {
            if (!in_array($request->getParameter('module'), self::VIEW_MODULES, true)
                || in_array($request->getParameter('action'), self::NON_VIEW_ACTIONS, true)) {
                return $content;
            }

            // Staff, and authenticated researchers with an active access
            // agreement, see the full record (#130 refinement 3).
            if (RedactionAccess::userMaySeeUnredacted($user)) {
                return $content;
            }

            $slug = $request->getParameter('slug');
            if (!$slug) {
                return $content;
            }
            $candidateId = (int) DB::table('slug')->where('slug', $slug)->value('object_id');
            if ($candidateId <= 0) {
                return $content;
            }

            $svc = new PrivacyRedactionService();
            $fields = $svc->getFields($candidateId);
            if (empty($fields)) {
                return $content;
            }

            $values = self::loadI18nValues($candidateId, $fields);
            $values += self::loadExtraValues($candidateId, $fields);
            if (empty($values)) {
                return $content;
            }

            // Past this line the record is protected: fail closed on any error.
            $ioId = $candidateId;

            $redacted = $svc->applyRedaction($ioId, $values, $user->getAttribute('user_id'), true);

            foreach ($values as $field => $orig) {
                if (null === $orig || '' === $orig || !isset($redacted[$field])) {
                    continue;
                }
                $content = self::substitute($content, (string) $orig, (string) $redacted[$field]);
            }

            $leaked = self::detectLeaks($content, $values, $redacted);

            // null means the page could not be normalised for comparison, so the
            // absence of the value was never established. Withhold.
            if (null === $leaked) {
                self::reportFailure($ioId, $user, [], 'rendered page could not be normalised for verification');

                return self::withheldNotice();
            }

            if (!empty($leaked)) {
                self::reportFailure($ioId, $user, $leaked, 'value still present after substitution');

                return self::withheldNotice();
            }

            return $content;
        } catch (\Throwable $e) {
            if ($ioId > 0) {
                self::reportFailure($ioId, $user, [], 'exception: ' . $e->getMessage());

                return self::withheldNotice();
            }

            // No protected value was in play, so serving the page discloses
            // nothing. Surfaced via the error log for the administrator.
            error_log('[ahgPrivacy] redaction filter error (no record in scope): ' . $e->getMessage());

            return $content;
        }
    }

    /**
     * Replace every rendered encoding of $orig with the matching encoding of
     * $redacted. Covers the raw value, both htmlspecialchars variants, and the
     * nl2br forms the theme produces for multi-line text.
     */
    private static function substitute(string $content, string $orig, string $redacted): string
    {
        $pairs = [];
        foreach ([null, ENT_QUOTES, ENT_COMPAT] as $flags) {
            $needle = null === $flags ? $orig : htmlspecialchars($orig, $flags);
            $rep = null === $flags ? $redacted : htmlspecialchars($redacted, $flags);
            $pairs[$needle] = $rep;
            // Multi-line values are commonly rendered through nl2br().
            $withBreaks = nl2br($needle, false);
            if ($withBreaks !== $needle) {
                $pairs[$withBreaks] = $rep;
            }
            $withBreaksXhtml = nl2br($needle, true);
            if ($withBreaksXhtml !== $needle) {
                $pairs[$withBreaksXhtml] = $rep;
            }
        }

        foreach ($pairs as $needle => $rep) {
            if ('' !== $needle) {
                $content = str_replace((string) $needle, (string) $rep, $content);
            }
        }

        return $content;
    }

    /**
     * Return the field names whose original value is still visible in $content.
     *
     * Comparison is on normalised visible text, and uses a leading slice of the
     * value so that a truncated rendering still counts as a leak.
     *
     * @param array<string,mixed> $values   original values
     * @param array<string,mixed> $redacted redacted values
     * @return array<int,string> leaking field names
     */
    private static function detectLeaks(string $content, array $values, array $redacted): ?array
    {
        $haystack = self::normalise($content);
        if (null === $haystack) {
            return null;
        }

        $leaks = [];

        foreach ($values as $field => $orig) {
            if (null === $orig || '' === $orig || !isset($redacted[$field])) {
                continue;
            }
            // Field passed through unchanged: nothing was meant to be removed.
            if ((string) $redacted[$field] === (string) $orig) {
                continue;
            }

            $normOrig = self::normalise((string) $orig);
            if (mb_strlen($normOrig) < self::VERIFY_MIN_LEN) {
                continue;
            }

            $needle = mb_substr($normOrig, 0, self::VERIFY_MIN_LEN);
            if ('' !== $needle && false !== mb_strpos($haystack, $needle)) {
                $leaks[] = (string) $field;
            }
        }

        return $leaks;
    }

    /**
     * Reduce HTML to comparable visible text, or null when that is not possible.
     *
     * Returning null matters. preg_replace() yields null on failure - invalid
     * UTF-8 in the rendered page is enough, and the /u collapse below is exactly
     * where that surfaces. Casting that null to a string produced an EMPTY
     * haystack, in which nothing is ever found, so detectLeaks() reported no leak
     * and the record was served. A verification step that answers "clean" when it
     * could not read the page is worse than none, because it is trusted.
     */
    private static function normalise(string $html): ?string
    {
        $text = preg_replace('#<br\s*/?>#i', "\n", $html);
        if (null === $text) {
            return null;
        }

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $collapsed = preg_replace('/\s+/u', ' ', $text);
        if (null === $collapsed) {
            return null;
        }

        return trim($collapsed);
    }

    /**
     * Record a redaction failure to the error log and the privacy audit trail.
     * Never throws: a failure to log must not stop the record being withheld.
     *
     * @param array<int,string> $leaked
     */
    private static function reportFailure(int $ioId, \sfUser $user, array $leaked, string $why): void
    {
        $detail = empty($leaked) ? $why : $why . ' [' . implode(', ', $leaked) . ']';
        error_log(sprintf(
            '[ahgPrivacy] redaction FAILED for information_object %d, record withheld: %s',
            $ioId,
            $detail
        ));

        try {
            $uid = $user->getAttribute('user_id');
            (new PrivacyRedactionService())->log(
                $ioId,
                $uid ? (int) $uid : null,
                'redaction_failed_closed',
                empty($leaked) ? null : substr((string) $leaked[0], 0, 100)
            );
        } catch (\Throwable $e) {
            // Audit is best-effort; withholding the record is not.
        }
    }

    /**
     * Page served in place of a record whose redaction could not be guaranteed.
     * Deliberately carries no record content of any kind.
     */
    private static function withheldNotice(): string
    {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex, nofollow">'
            . '<title>Record temporarily unavailable</title></head>'
            . '<body style="margin:0;padding:2rem;font-family:system-ui,-apple-system,\'Segoe UI\',sans-serif;'
            . 'background:#f8f9fa;color:#212529;">'
            . '<div style="max-width:38rem;margin:3rem auto;padding:1.75rem 2rem;background:#fff;'
            . 'border:1px solid #dee2e6;border-radius:.5rem;">'
            . '<h1 style="margin:0 0 .75rem;font-size:1.4rem;">Record temporarily unavailable</h1>'
            . '<p style="margin:0 0 .75rem;line-height:1.5;">This description carries privacy restrictions '
            . 'that could not be applied to the page, so it has been withheld rather than shown in full.</p>'
            . '<p style="margin:0;line-height:1.5;color:#495057;">This is a precaution, not an error on your part. '
            . 'If you need access to this record, please contact the repository.</p>'
            . '</div></body></html>';
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
     * #130 refinement 1 - load event-date / related-entity field values that
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
