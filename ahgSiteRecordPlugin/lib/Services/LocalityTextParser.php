<?php

namespace AhgSiteRecordPlugin\Services;

/**
 * Reads locality out of the free text an authority record carries.
 *
 * RARI keeps a site's location in the ISAAR "Internal structures/genealogy"
 * field, mixed with other things:
 *
 *     E?.8.2.G  pl location th i: x100  Map sheet: 3027AC
 *               Map sheet: 3027AC_1965_ED1_GEO  Map sheet: 3027AC_2009_ED3_GEO
 *
 * Separated from the import task so the parsing can be unit tested without a
 * database or a symfony context, and so a script that cannot use the task - the
 * RARI development instance does not load AHG plugins in CLI - runs exactly the
 * same code rather than a copy that drifts.
 */
class LocalityTextParser
{
    /**
     * A South African 1:50,000 sheet: four digits then two letters.
     *
     * The negative lookahead excludes 3027AC_1965_ED1_GEO and friends. Those are
     * scanned EDITIONS of sheet 3027AC, not separate localities, so counting them
     * would multiply one location into several.
     */
    private const SHEET = '/\b(\d{4}[A-Z]{2})\b(?!_)/';

    /**
     * Flatten stored markup to plain text without losing content.
     *
     * The field holds <br>-separated lines, so tags become spaces rather than
     * being stripped outright - dropping them would run "x100" and "Map sheet:"
     * together into one unreadable token.
     */
    public static function normalise(?string $raw): string
    {
        if (null === $raw || '' === $raw) {
            return '';
        }

        $text = preg_replace('#<br\s*/?>#i', ' ', $raw);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * The primary map sheet, or null when the text carries none.
     *
     * Takes the first plain reference. Where a record lists several they are
     * editions or neighbouring sheets for the same site; every one of them stays
     * in the preserved original text, so choosing one for the indexed column
     * loses nothing.
     */
    public static function mapSheet(?string $text): ?string
    {
        if (null === $text || '' === $text) {
            return null;
        }

        return preg_match(self::SHEET, $text, $m) ? $m[1] : null;
    }

    /**
     * Every distinct plain sheet in the text, for reporting.
     *
     * @return string[]
     */
    public static function allMapSheets(?string $text): array
    {
        if (null === $text || '' === $text) {
            return [];
        }

        preg_match_all(self::SHEET, $text, $m);

        return array_values(array_unique($m[1]));
    }
}
