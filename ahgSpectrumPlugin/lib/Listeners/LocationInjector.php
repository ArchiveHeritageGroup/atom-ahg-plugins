<?php

namespace AhgSpectrum\Listeners;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Show where a record physically is, on the record itself.
 *
 * The capability existed and could not be seen. `spectrum_location` holds
 * building, floor, room, unit, shelf and box with an `is_current` flag, and
 * `spectrum_movement` records every move with date, reason, handler, condition
 * before and after, and a planned return date. None of it appeared on a
 * description: you had to know the Collections Procedures screens existed and go
 * there.
 *
 * For a gallery that matters more than usual. A work hanging in a dean's office
 * is not lost, but a record that cannot say where it is amounts to the same
 * thing when someone asks - and "where was this in 2021" has an answer in the
 * data that nobody could reach.
 *
 * Injected rather than templated, for the reason the other panels are: a
 * description page is rendered by whichever descriptive-standard module the
 * record uses, so there is no single template to edit, and editing base AtoM is
 * not on the table. Skipped when ahgThemeB5Plugin is present, which renders its
 * own panels from the same data and would otherwise duplicate this one.
 */
class LocationInjector
{
    /**
     * A description page is NOT served by the 'informationobject' module - AtoM
     * forwards /{slug} to the module for the record's descriptive standard, so
     * checking only for 'informationobject' means this silently never fires.
     */
    private const VIEW_MODULES = [
        'informationobject',
        'sfIsadPlugin',
        'sfRadPlugin',
        'sfDcPlugin',
        'sfModsPlugin',
        'sfDacsPlugin',
        'gallery',
        'museum',
    ];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // A panel is never worth a failed page. Logged so a broken panel is
            // discoverable rather than merely absent.
            error_log('LocationInjector: '.$e->getMessage());

            return $content;
        }
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if (self::$injected || $this->themeProvidesPanel()) {
            return $content;
        }

        if (false !== stripos((string) $content, 'ahg-location-panel')) {
            return $content;
        }

        if (!$this->isHtmlGet($response)) {
            return $content;
        }

        $context = \sfContext::getInstance();

        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true)
            || 'index' !== $context->getActionName()) {
            return $content;
        }

        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;

        if (!$resource || !isset($resource->id)) {
            return $content;
        }

        $location = $this->currentLocation((int) $resource->id);
        $movements = $this->recentMovements((int) $resource->id);

        // Nothing recorded means no panel. An empty "Location" heading on every
        // description in the catalogue is worse than no heading at all.
        if (!$location && !$movements) {
            return $content;
        }

        $panel = $this->render($location, $movements);

        if ('' === $panel) {
            return $content;
        }

        // Place it inside the record body, not at the end of the document.
        //
        // Appending before </body> put the panel full-width underneath the whole
        // page, below the footer - visible, and obviously not part of the record.
        // The description content lives in #main-column, so the panel goes at the
        // end of that, where the other record panels are.
        $at = stripos($content, '<div id="main-column"');

        if (false === $at) {
            return $content;
        }

        $close = $this->closingTagFor($content, $at);

        if (null === $close) {
            return $content;
        }

        self::$injected = true;

        return substr_replace($content, $panel, $close, 0);
    }


    /**
     * Offset of the </div> that closes the element opening at $from.
     *
     * Counting nested divs rather than taking the next </div>, which would drop
     * the panel inside the first child element instead of at the end of the
     * column.
     */
    private function closingTagFor(string $html, int $from): ?int
    {
        $depth = 0;
        $len = strlen($html);

        for ($i = $from; $i < $len - 4; ++$i) {
            if (0 === substr_compare($html, '<div', $i, 4, true)) {
                ++$depth;
                continue;
            }

            if (0 === substr_compare($html, '</div>', $i, 6, true)) {
                --$depth;

                if (0 === $depth) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * The current location, or null.
     *
     * Only rows flagged is_current: the table keeps history, and the most
     * recently created row is not necessarily where the thing is.
     */
    private function currentLocation(int $objectId): ?object
    {
        if (!$this->hasTable('spectrum_location')) {
            return null;
        }

        return DB::table('spectrum_location')
            ->where('object_id', $objectId)
            ->where('is_current', 1)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * Recent movements, newest first, with the planned return date.
     *
     * planned_return_date is the column that turns this from a history into
     * something actionable - it is what says a work is late.
     */
    private function recentMovements(int $objectId, int $limit = 5): array
    {
        if (!$this->hasTable('spectrum_movement')) {
            return [];
        }

        return DB::table('spectrum_movement')
            ->where('object_id', $objectId)
            ->orderByDesc('movement_date')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function render(?object $location, array $movements): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $today = date('Y-m-d');

        $html = '<div class="ahg-location-panel card mb-3" id="ahg-location-panel">'
              .'<div class="card-header">Location</div><div class="card-body">';

        if ($location) {
            $parts = array_filter([
                $location->location_building ?? null,
                $location->location_floor ?? null,
                $location->location_room ?? null,
                $location->location_unit ?? null,
                $location->location_shelf ?? null,
                $location->location_box ?? null,
            ]);

            $html .= '<p class="mb-1"><strong>'.$esc($location->location_name ?? 'Current location').'</strong></p>';

            if ($parts) {
                $html .= '<p class="text-muted small mb-2">'.$esc(implode(' · ', $parts)).'</p>';
            }
        }

        if ($movements) {
            $html .= '<table class="table table-sm mb-0"><thead><tr>'
                   .'<th>Moved</th><th>Reason</th><th>To</th><th>Due back</th></tr></thead><tbody>';

            foreach ($movements as $m) {
                $due = $m->planned_return_date ?? null;
                $late = $due && $due < $today && empty($m->actual_return_date);

                $html .= '<tr'.($late ? ' class="table-danger"' : '').'>'
                       .'<td>'.$esc(substr((string) ($m->movement_date ?? ''), 0, 10)).'</td>'
                       .'<td>'.$esc($m->movement_reason ?? '').'</td>'
                       .'<td>'.$esc($m->moved_by ?? $m->movement_contact ?? '').'</td>'
                       .'<td>'.$esc($due ?: '').($late ? ' <strong>overdue</strong>' : '').'</td>'
                       .'</tr>';
            }

            $html .= '</tbody></table>';
        }

        return $html.'</div></div>';
    }

    private function themeProvidesPanel(): bool
    {
        return is_dir((string) \sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin');
    }

    private function isHtmlGet($response): bool
    {
        if (!$response instanceof \sfWebResponse) {
            return false;
        }

        if ('GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            return false;
        }

        return false !== stripos((string) $response->getContentType(), 'text/html');
    }

    private function hasTable(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
