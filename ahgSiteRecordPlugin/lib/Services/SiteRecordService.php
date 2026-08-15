<?php

namespace AhgSiteRecordPlugin\Services;

use AhgSiteRecordPlugin\Repository\SiteRecordRepository;

/**
 * Business logic for site records, and the only path anything user-facing should
 * use to read one.
 *
 * present() and presentMany() apply LocalityVisibilityService, so a caller cannot
 * render a site without the locality rule having been applied. The repository
 * returns raw rows and is not for direct use by an action or template.
 */
class SiteRecordService
{
    /** Taxonomies whose selections are stored in ahg_site_attribute. */
    public const ATTRIBUTE_TAXONOMIES = [
        'site_tradition',
        'site_type',
        'site_damage',
        'site_surface_content',
        'site_excavation_potential',
        'site_mineral_content',
        'site_deposit_depth',
        'site_deposit_content',
    ];

    /** Scalar columns a form may set. Anything not listed here is ignored. */
    private const WRITABLE = [
        'site_number', 'date_visited', 'region_code', 'sub_region_code',
        'latitude', 'longitude', 'coordinate_datum', 'altitude_m', 'map_sheet',
        'locality_original', 'locality_sensitive', 'aspect_code',
        'height_m', 'width_m', 'depth_m', 'site_description',
        'photograph_numbers', 'contact_name', 'contact_email', 'notes',
    ];

    public function __construct(private ?SiteRecordRepository $repo = null)
    {
        $this->repo = $repo ?? new SiteRecordRepository();
    }

    public function repository(): SiteRecordRepository
    {
        return $this->repo;
    }

    /**
     * A site record with its locality already resolved for this reader.
     *
     * The returned object has no latitude/longitude of its own - those live under
     * `locality`, which is either exact or coarsened. Removing the raw properties
     * is deliberate: a template cannot print what is not there.
     *
     * @param mixed $user null to use the current session user
     */
    public function present($record, $user = null): ?object
    {
        if (!$record) {
            return null;
        }

        $locality = LocalityVisibilityService::present($record, $user);

        $safe = clone $record;
        foreach (['latitude', 'longitude', 'altitude_m', 'map_sheet', 'locality_original'] as $raw) {
            unset($safe->{$raw});
        }
        $safe->locality = $locality;

        return $safe;
    }

    /** @param object[] $records */
    public function presentMany(array $records, $user = null): array
    {
        return array_map(fn ($r) => $this->present($r, $user), $records);
    }

    public function findForDisplay(int $id, $user = null): ?object
    {
        return $this->present($this->repo->find($id), $user);
    }

    public function findByActorForDisplay(int $actorId, $user = null): ?object
    {
        return $this->present($this->repo->findByActor($actorId), $user);
    }

    /**
     * Create or update from submitted form values.
     *
     * @return int the site record id
     */
    public function save(?int $id, int $actorId, array $input, ?int $userId = null): int
    {
        $data = [];
        foreach (self::WRITABLE as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $this->normalise($field, $input[$field]);
            }
        }

        // Absent checkbox means "not sensitive"; anything else, including a form
        // that never rendered the control, leaves the record protected.
        $data['locality_sensitive'] = !empty($input['locality_sensitive']) ? 1 : 0;

        if (null === $id) {
            $data['actor_id'] = $actorId;
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            $id = $this->repo->insert($data);
        } else {
            $data['updated_by'] = $userId;
            $this->repo->update($id, $data);
        }

        $selected = [];
        foreach (self::ATTRIBUTE_TAXONOMIES as $taxonomy) {
            $selected[$taxonomy] = (array) ($input[$taxonomy] ?? []);
        }
        $this->repo->replaceAttributes($id, $selected, (array) ($input['attribute_note'] ?? []));

        if (isset($input['recorder'])) {
            $this->repo->replaceRecorders($id, (array) $input['recorder']);
        }

        return $id;
    }

    /**
     * Attributes grouped by taxonomy, with labels resolved from ahg_dropdown.
     *
     * @return array<string, array<int, array{code: string, label: string, note: ?string}>>
     */
    public function attributesByTaxonomy(int $siteRecordId): array
    {
        $out = [];

        foreach ($this->repo->attributes($siteRecordId) as $row) {
            $out[$row->taxonomy][] = [
                'code' => $row->code,
                'label' => $this->label($row->taxonomy, $row->code),
                'note' => $row->note,
            ];
        }

        return $out;
    }

    /**
     * Label for a dropdown code, falling back to the code itself.
     *
     * A code with no matching row still displays as something rather than
     * vanishing - the legacy application rendered a blank in exactly this case.
     */
    public function label(string $taxonomy, ?string $code): string
    {
        if (null === $code || '' === $code) {
            return '';
        }

        // resolveLabelForTaxonomy(), NOT resolveLabel() - the latter takes
        // (table, column, code) and resolves the taxonomy from the column map.
        // Calling it with (taxonomy, code) throws, and the catch below turns that
        // into a silent fallback to the raw code, which looks like a missing
        // vocabulary rather than a wrong call. Cost an hour once already.
        if (class_exists('\AtomExtensions\Services\DropdownService')) {
            try {
                $resolved = \AtomExtensions\Services\DropdownService::resolveLabelForTaxonomy($taxonomy, $code);
                if (!empty($resolved)) {
                    return $resolved;
                }
            } catch (\Throwable $e) {
                // Fall through: a label is presentation, and a missing one must
                // not take the page down. Showing the code is better than a blank
                // cell, which is what the legacy application did here.
            }
        }

        return $code;
    }

    /** Choices for a taxonomy, for rendering a form control. */
    public function choices(string $taxonomy): array
    {
        if (class_exists('\AtomExtensions\Services\DropdownService')) {
            try {
                // includeEmpty false - the templates supply their own blank option,
                // and a checkbox group must not grow an empty box.
                return (array) \AtomExtensions\Services\DropdownService::getChoices($taxonomy, false);
            } catch (\Throwable $e) {
                return [];
            }
        }

        return [];
    }

    private function normalise(string $field, $value)
    {
        if ('' === $value) {
            return null;
        }

        return match ($field) {
            'latitude', 'longitude', 'height_m', 'width_m', 'depth_m' => null === $value ? null : (float) $value,
            'altitude_m' => null === $value ? null : (int) $value,
            default => $value,
        };
    }
}
