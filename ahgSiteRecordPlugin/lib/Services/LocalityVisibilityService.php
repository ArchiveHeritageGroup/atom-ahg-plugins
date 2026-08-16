<?php

namespace AhgSiteRecordPlugin\Services;

/**
 * The single place that decides who sees where a site is.
 *
 * For a rock art or archaeological site, precise location is the most dangerous
 * field in the record: it is what makes looting and vandalism possible. RARI's
 * current AtoM instance deals with this bluntly, by commenting the locality field
 * out of the authority record template so that nobody can see it, staff included.
 * The requirement (issue #299) is to restrict, not remove.
 *
 * WHY THIS IS A SERVICE AND NOT TEMPLATE CODE
 *
 * The equivalent logic already exists once, inline, in
 * ahgCustomFieldsPlugin/templates/display/_site_location_map.php - and only there.
 * It is opt-in per call site, with no shared resolver. Today that leaks nothing,
 * because custom fields have no export or API surface at all. But it also means
 * there is no enforcement to inherit: the first person to write a CSV export, a
 * report or an API endpoint has to remember the rule and reimplement it. That is
 * how coordinates escape - not through a bug in the gate, but through a path that
 * never consulted one.
 *
 * So: nothing reads latitude/longitude directly. View, panel, map, browse, export,
 * print view, CLI and any future API all call present(), and get back a value that
 * is already safe to render.
 *
 * BEHAVIOUR
 *
 *   - Someone with clearance gets the exact position.
 *   - Everyone else gets a coarsened position - rounded to ~0.1 degrees, which is
 *     an area roughly 11 km across - plus a note saying it is deliberately
 *     imprecise. Never a precise pin, and never the raw text fields.
 *   - A record that has never had its sensitivity set is treated as SENSITIVE.
 *     Protection is the default; exposure has to be a decision.
 *
 * Coarsening is one-way. Rounding discards the low-order digits rather than
 * offsetting them, so repeated reads cannot be averaged back to the true point.
 */
class LocalityVisibilityService
{
    /**
     * Degrees to round to for anyone without clearance. 0.1 deg is ~11 km of
     * latitude - coarse enough that it identifies a district, not a site.
     */
    public const COARSE_DEGREES = 0.1;

    /** Approximate width of that area, for the note shown to the reader. */
    public const COARSE_KM = 11;

    /**
     * Credentials that may see an exact position.
     *
     * Deliberately not "any authenticated user": a self-registered researcher is
     * authenticated, and on RARI most accounts are exactly that. Seeing a site's
     * true coordinates is a staff decision.
     */
    public const EXACT_CREDENTIALS = ['editor', 'administrator'];

    /**
     * Whether this user may see locality at all, independent of any one record.
     *
     * For gating a raw field that has no site record behind it - the ISAAR
     * "Internal structures/genealogy" field, where RARI keeps map sheet
     * references as free text. Same credentials as canSeeExact(), so a reader
     * cannot be refused the structured value and shown the unstructured one.
     *
     * @param mixed $user null to use the current session user
     */
    public static function userHasClearance($user = null): bool
    {
        if (null === $user) {
            $user = self::currentUser();
        }

        if (!$user || !$user->isAuthenticated()) {
            return false;
        }

        foreach (self::EXACT_CREDENTIALS as $credential) {
            if ($user->hasCredential($credential)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this record's locality is sensitive.
     *
     * Unset counts as sensitive - a record nobody has classified is protected,
     * not exposed.
     */
    public static function isSensitive($record): bool
    {
        if (!is_object($record) || !property_exists($record, 'locality_sensitive')) {
            return true;
        }

        return null === $record->locality_sensitive ? true : (bool) $record->locality_sensitive;
    }

    /**
     * Whether the given user may see the exact position of this record.
     *
     * @param mixed $user null to use the current session user
     */
    public static function canSeeExact($record, $user = null): bool
    {
        // Explicitly marked non-sensitive: the site is already public knowledge
        // (a declared monument, a published excavation), so there is nothing to
        // protect.
        if (!self::isSensitive($record)) {
            return true;
        }

        if (null === $user) {
            $user = self::currentUser();
        }

        if (!$user || !$user->isAuthenticated()) {
            return false;
        }

        foreach (self::EXACT_CREDENTIALS as $credential) {
            if ($user->hasCredential($credential)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a record's locality into something safe to render.
     *
     * Always returns the same shape, so a caller cannot accidentally fall through
     * to the raw columns:
     *
     *   has_coordinates  bool    whether there is anything to show at all
     *   exact            bool    true only when the reader has clearance
     *   latitude         ?float  exact or coarsened - never the raw value when exact is false
     *   longitude        ?float
     *   datum            string
     *   altitude_m       ?int    withheld unless exact (it narrows a search)
     *   map_sheet        ?string withheld unless exact - text, so it cannot be coarsened
     *   locality_original ?string withheld unless exact - free text, may contain anything
     *   precision_km     ?int    null when exact, otherwise the area's approximate width
     *   note             ?string reader-facing explanation when the value is coarsened
     *
     * @param mixed $user null to use the current session user
     */
    public static function present($record, $user = null): array
    {
        $exact = self::canSeeExact($record, $user);

        $lat = self::toFloat($record->latitude ?? null);
        $lng = self::toFloat($record->longitude ?? null);

        $out = [
            'has_coordinates' => null !== $lat && null !== $lng,
            'exact' => $exact,
            'latitude' => null,
            'longitude' => null,
            'datum' => $record->coordinate_datum ?? 'WGS84',
            'altitude_m' => null,
            'map_sheet' => null,
            'locality_original' => null,
            'precision_km' => null,
            'note' => null,
        ];

        if ($exact) {
            $out['latitude'] = $lat;
            $out['longitude'] = $lng;
            $out['altitude_m'] = isset($record->altitude_m) ? (int) $record->altitude_m : null;
            $out['map_sheet'] = $record->map_sheet ?? null;
            $out['locality_original'] = $record->locality_original ?? null;

            return $out;
        }

        // Not cleared. The text fields are withheld outright - a map sheet
        // reference locates a site to a few kilometres and locality_original is
        // free text that may hold anything at all, so neither can be coarsened
        // the way a number can.
        if ($out['has_coordinates']) {
            [$out['latitude'], $out['longitude']] = self::coarsen($lat, $lng);
            $out['precision_km'] = self::COARSE_KM;
            $out['note'] = sprintf(
                'Location shown to approximately %d km. Precise coordinates are restricted to protect the site.',
                self::COARSE_KM
            );
        }

        return $out;
    }

    /**
     * Reduce a position to a coarse area.
     *
     * Rounding, not offsetting: an offset preserves the information and can be
     * averaged away across several reads, while rounding destroys the low-order
     * digits outright.
     *
     * @return array{0: float, 1: float}
     */
    public static function coarsen(float $lat, float $lng): array
    {
        $step = self::COARSE_DEGREES;

        return [
            round($lat / $step) * $step,
            round($lng / $step) * $step,
        ];
    }

    /**
     * Strip locality from a row destined for export, unless the reader is cleared.
     *
     * Export is where this kind of rule is usually forgotten, so it gets a helper
     * of its own rather than relying on each export author to remember the shape.
     *
     * @param mixed $user null to use the current session user
     */
    public static function redactRow(array $row, $record, $user = null): array
    {
        $locality = self::present($record, $user);

        if ($locality['exact']) {
            return $row;
        }

        foreach (['map_sheet', 'locality_original', 'altitude_m'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = null;
            }
        }

        if (array_key_exists('latitude', $row)) {
            $row['latitude'] = $locality['latitude'];
        }
        if (array_key_exists('longitude', $row)) {
            $row['longitude'] = $locality['longitude'];
        }
        if (array_key_exists('locality_precision_km', $row)) {
            $row['locality_precision_km'] = $locality['precision_km'];
        }

        return $row;
    }

    private static function toFloat($value): ?float
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return (float) $value;
    }

    /**
     * The session user, or null outside a request (CLI, jobs).
     *
     * Returning null means "no clearance", so a CLI export coarsens by default
     * rather than quietly dumping exact coordinates to a file.
     */
    private static function currentUser()
    {
        try {
            if (!class_exists('\sfContext') || !\sfContext::hasInstance()) {
                return null;
            }

            return \sfContext::getInstance()->getUser();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
