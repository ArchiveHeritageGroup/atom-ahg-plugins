<?php

namespace AhgReports;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Spatial Analysis Export Service
 *
 * Exports site records with GPS coordinates for GIS/spatial analysis.
 * Supports filtering by place access points and subject access points,
 * with configurable coordinate sources and tradition classification.
 *
 * Generic implementation usable across any AtoM installation.
 */
class SpatialAnalysisExport
{
    /**
     * Default painted tradition terms (case-insensitive partial match)
     */
    protected array $paintedTerms = [
        'brush painted',
        'finger painted',
        'painted',
        'paint',
        'pigment',
        'ochre',
        'San painting',
        'rock painting',
    ];

    /**
     * Default engraved tradition terms (case-insensitive partial match)
     */
    protected array $engravedTerms = [
        'engraving',
        'engraved',
        'pecking',
        'pecked',
        'incising',
        'incised',
        'scratched',
        'abraded',
        'Khoekhoen',
        'Khoi',
        'geometric',
    ];

    /**
     * Coordinate source configuration
     */
    protected string $coordinateSource = 'property'; // property, site_record, nmmz_site, dam_metadata, contact_info

    /**
     * Property names for coordinates (when using property source)
     */
    protected string $latitudePropertyName = 'latitude';
    protected string $longitudePropertyName = 'longitude';

    /**
     * Culture for i18n queries
     */
    protected string $culture = 'en';

    /**
     * Set painted tradition terms
     */
    public function setPaintedTerms(array $terms): self
    {
        $this->paintedTerms = $terms;
        return $this;
    }

    /**
     * Set engraved tradition terms
     */
    public function setEngravedTerms(array $terms): self
    {
        $this->engravedTerms = $terms;
        return $this;
    }

    /**
     * Set coordinate source
     * @param string $source One of: property, site_record, nmmz_site, dam_metadata, contact_info, scope_field
     */
    public function setCoordinateSource(string $source): self
    {
        $this->coordinateSource = $source;
        return $this;
    }

    /**
     * Set property names for latitude/longitude
     */
    public function setCoordinatePropertyNames(string $latitude, string $longitude): self
    {
        $this->latitudePropertyName = $latitude;
        $this->longitudePropertyName = $longitude;
        return $this;
    }

    /**
     * Set culture for i18n
     */
    public function setCulture(string $culture): self
    {
        $this->culture = $culture;
        return $this;
    }

    /**
     * Export sites matching criteria to CSV
     *
     * @param array $options Export options:
     *   - placeTerms: array of place names to filter by (e.g., ['South Africa', 'Lesotho'])
     *   - subjectTerms: array of subject terms that must be present (any match)
     *   - levelOfDescription: level name to filter by (e.g., 'Site', 'Collection')
     *   - topLevelOnly: bool - only include top-level records (parent_id = 1)
     *   - requireCoordinates: bool - exclude records without coordinates
     *   - limit: int - max records to export (0 = no limit)
     * @return array ['headers' => [...], 'rows' => [...], 'count' => int]
     */
    public function export(array $options = []): array
    {
        \AhgCore\Core\AhgDb::init();

        $placeTerms = $options['placeTerms'] ?? [];
        $subjectTerms = $options['subjectTerms'] ?? [];
        $levelOfDescription = $options['levelOfDescription'] ?? null;
        $topLevelOnly = $options['topLevelOnly'] ?? true;
        $requireCoordinates = $options['requireCoordinates'] ?? true;
        $limit = $options['limit'] ?? 0;

        // Build base query
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('term as lod', 'io.level_of_description_id', '=', 'lod.id')
            ->leftJoin('term_i18n as lodi', function ($join) {
                $join->on('lod.id', '=', 'lodi.id')
                    ->where('lodi.culture', '=', $this->culture);
            })
            ->where('io.id', '!=', 1) // Exclude root
            ->select([
                'io.id',
                'io.identifier as reference_code',
                'ioi.title as site_name',
                'lodi.name as level_of_description',
            ]);

        // Filter by top-level only
        if ($topLevelOnly) {
            $query->where('io.parent_id', '=', 1);
        }

        // Filter by level of description
        if ($levelOfDescription) {
            $query->where('lodi.name', '=', $levelOfDescription);
        }

        // Filter by place access points
        if (!empty($placeTerms)) {
            $query->whereExists(function ($subquery) use ($placeTerms) {
                $subquery->select(DB::raw(1))
                    ->from('object_term_relation as otr_place')
                    ->join('term as t_place', 'otr_place.term_id', '=', 't_place.id')
                    ->join('term_i18n as ti_place', function ($join) {
                        $join->on('t_place.id', '=', 'ti_place.id')
                            ->where('ti_place.culture', '=', $this->culture);
                    })
                    ->whereRaw('otr_place.object_id = io.id')
                    ->where('t_place.taxonomy_id', '=', 42) // Places taxonomy
                    ->where(function ($q) use ($placeTerms) {
                        foreach ($placeTerms as $term) {
                            $q->orWhere('ti_place.name', 'LIKE', '%' . $term . '%');
                        }
                    });
            });
        }

        // Filter by subject access points (require at least one match)
        if (!empty($subjectTerms)) {
            $query->whereExists(function ($subquery) use ($subjectTerms) {
                $subquery->select(DB::raw(1))
                    ->from('object_term_relation as otr_subj')
                    ->join('term as t_subj', 'otr_subj.term_id', '=', 't_subj.id')
                    ->join('term_i18n as ti_subj', function ($join) {
                        $join->on('t_subj.id', '=', 'ti_subj.id')
                            ->where('ti_subj.culture', '=', $this->culture);
                    })
                    ->whereRaw('otr_subj.object_id = io.id')
                    ->where('t_subj.taxonomy_id', '=', 35) // Subjects taxonomy
                    ->where(function ($q) use ($subjectTerms) {
                        foreach ($subjectTerms as $term) {
                            $q->orWhere('ti_subj.name', 'LIKE', '%' . $term . '%');
                        }
                    });
            });
        }

        // Add coordinate columns based on source
        $this->addCoordinateColumns($query);

        // Filter by coordinates if required
        if ($requireCoordinates) {
            $this->addCoordinateFilter($query);
        }

        // Apply limit
        if ($limit > 0) {
            $query->limit($limit);
        }

        // Order by reference code
        $query->orderBy('io.identifier');

        // Execute query
        $results = $query->get();

        // Post-process results to add computed fields
        $rows = [];
        foreach ($results as $row) {
            $rowArray = (array) $row;

            // Get all subject terms for this record
            $subjects = $this->getSubjectTerms($row->id);
            $rowArray['subjects_concatenated'] = implode('; ', $subjects);

            // Get place access points
            $places = $this->getPlaceTerms($row->id);
            $rowArray['place_country'] = $this->extractCountry($places, $placeTerms);

            // Classify traditions
            $rowArray['is_painted'] = $this->matchesTerms($subjects, $this->paintedTerms) ? 'TRUE' : 'FALSE';
            $rowArray['is_engraved'] = $this->matchesTerms($subjects, $this->engravedTerms) ? 'TRUE' : 'FALSE';

            // An export must not be a way around the locality rule.
            //
            // Site record coordinates are gated: LocalityVisibilityService decides who
            // sees an exact position, and everyone else gets a coarsened one with the
            // map sheet withheld. A CSV that joined ahg_site_record directly would hand
            // out exact positions of archaeological sites to anyone who could reach the
            // report - which is the single thing that gating exists to prevent.
            //
            // So the same service that governs the record view governs the export. If
            // the plugin is absent there is nothing to gate and the row passes through.
            if ('site_record' === $this->coordinateSource) {
                $rowArray = $this->applyLocalityVisibility($rowArray);
            }

            $rows[] = $rowArray;
        }

        // Define output headers
        $headers = [
            'reference_code' => 'Site Reference Code',
            'site_name' => 'Site Name',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'place_country' => 'Country',
            'is_painted' => 'Is Painted',
            'is_engraved' => 'Is Engraved',
            'subjects_concatenated' => 'Subject Tags',
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    /**
     * Add coordinate columns to query based on configured source
     */
    protected function addCoordinateColumns($query): void
    {
        switch ($this->coordinateSource) {
            case 'property':
                // The value is in property_i18n, not property.
                //
                // AtoM's `property` table carries object_id, scope, name and culture
                // only - the value is a translated column in `property_i18n`. Selecting
                // prop_lat.value made the default coordinate source fail outright with
                // "Unknown column 'prop_lat.value'", so this report could never run in
                // its own default mode (found 2026-08-18).
                $query->leftJoin('property as prop_lat', function ($join) {
                    $join->on('io.id', '=', 'prop_lat.object_id')
                        ->where('prop_lat.name', '=', $this->latitudePropertyName);
                });
                $query->leftJoin('property_i18n as prop_lat_i18n', function ($join) {
                    $join->on('prop_lat.id', '=', 'prop_lat_i18n.id')
                        ->where('prop_lat_i18n.culture', '=', $this->culture);
                });
                $query->leftJoin('property as prop_lng', function ($join) {
                    $join->on('io.id', '=', 'prop_lng.object_id')
                        ->where('prop_lng.name', '=', $this->longitudePropertyName);
                });
                $query->leftJoin('property_i18n as prop_lng_i18n', function ($join) {
                    $join->on('prop_lng.id', '=', 'prop_lng_i18n.id')
                        ->where('prop_lng_i18n.culture', '=', $this->culture);
                });
                $query->addSelect([
                    'prop_lat_i18n.value as latitude',
                    'prop_lng_i18n.value as longitude',
                ]);
                break;

            case 'site_record':
                // Coordinates held by ahgSiteRecordPlugin.
                //
                // A site record extends the AUTHORITY RECORD (ahg_site_record.actor_id),
                // not the description, so the link runs through the description's
                // creator event: information_object -> event.actor_id -> ahg_site_record.
                //
                // Added 2026-08-18: before this the report had no way to reach site
                // record coordinates at all, which is where RARI and archaeology now
                // keep them.
                $query->leftJoin('event as sr_event', function ($join) {
                    $join->on('io.id', '=', 'sr_event.object_id')
                        ->whereNotNull('sr_event.actor_id');
                });
                $query->leftJoin('ahg_site_record as sr', 'sr_event.actor_id', '=', 'sr.actor_id');
                $query->addSelect([
                    'sr.latitude as latitude',
                    'sr.longitude as longitude',
                    'sr.map_sheet as map_sheet',
                    'sr.locality_sensitive as locality_sensitive',
                ]);
                break;

            case 'nmmz_site':
                $query->leftJoin('nmmz_archaeological_site as nmmz', 'io.id', '=', 'nmmz.information_object_id');
                $query->addSelect([
                    'nmmz.gps_latitude as latitude',
                    'nmmz.gps_longitude as longitude',
                ]);
                break;

            case 'dam_metadata':
                $query->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
                    ->leftJoin('dam_iptc_metadata as dim', 'do.id', '=', 'dim.digital_object_id');
                $query->addSelect([
                    'dim.gps_latitude as latitude',
                    'dim.gps_longitude as longitude',
                ]);
                break;

            case 'contact_info':
                // For repository-level coordinates
                $query->leftJoin('contact_information as ci', 'io.repository_id', '=', 'ci.actor_id');
                $query->addSelect([
                    'ci.latitude',
                    'ci.longitude',
                ]);
                break;

            case 'scope_field':
                // Coordinates stored in scope_and_content - will need post-processing
                $query->addSelect([
                    'ioi.scope_and_content as raw_scope',
                    DB::raw('NULL as latitude'),
                    DB::raw('NULL as longitude'),
                ]);
                break;

            default:
                $query->addSelect([
                    DB::raw('NULL as latitude'),
                    DB::raw('NULL as longitude'),
                ]);
        }
    }

    /**
     * Add coordinate filter to query
     */
    protected function addCoordinateFilter($query): void
    {
        switch ($this->coordinateSource) {
            case 'property':
                // Filter the i18n value, matching the join in addCoordinateColumns.
                $query->whereNotNull('prop_lat_i18n.value')
                    ->where('prop_lat_i18n.value', '!=', '')
                    ->where('prop_lat_i18n.value', '!=', '0');
                break;

            case 'site_record':
                $query->whereNotNull('sr.latitude')
                    ->where('sr.latitude', '!=', 0);
                break;

            case 'nmmz_site':
                $query->whereNotNull('nmmz.gps_latitude')
                    ->where('nmmz.gps_latitude', '!=', 0);
                break;

            case 'dam_metadata':
                $query->whereNotNull('dim.gps_latitude')
                    ->where('dim.gps_latitude', '!=', 0);
                break;

            case 'contact_info':
                $query->whereNotNull('ci.latitude')
                    ->where('ci.latitude', '!=', 0);
                break;
        }
    }

    /**
     * Get all subject terms for an information object
     */
    protected function getSubjectTerms(int $objectId): array
    {
        $terms = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', '=', $objectId)
            ->where('t.taxonomy_id', '=', 35) // Subjects taxonomy
            ->pluck('ti.name')
            ->toArray();

        return $terms;
    }

    /**
     * Get all place terms for an information object
     */
    protected function getPlaceTerms(int $objectId): array
    {
        $terms = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', '=', $objectId)
            ->where('t.taxonomy_id', '=', 42) // Places taxonomy
            ->pluck('ti.name')
            ->toArray();

        return $terms;
    }

    /**
     * Extract country from place terms
     */
    protected function extractCountry(array $places, array $countryList): string
    {
        foreach ($places as $place) {
            foreach ($countryList as $country) {
                if (stripos($place, $country) !== false) {
                    return $country;
                }
            }
        }
        // Return first place if no match
        return $places[0] ?? '';
    }

    /**
     * Check if any subjects match the given terms
     */
    protected function matchesTerms(array $subjects, array $terms): bool
    {
        foreach ($subjects as $subject) {
            foreach ($terms as $term) {
                if (stripos($subject, $term) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Generate CSV string from export results
     */
    public function toCsv(array $exportResult): string
    {
        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, array_values($exportResult['headers']));

        // Write rows
        foreach ($exportResult['rows'] as $row) {
            $csvRow = [];
            foreach (array_keys($exportResult['headers']) as $key) {
                $csvRow[] = $row[$key] ?? '';
            }
            fputcsv($output, $csvRow);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Write CSV to file
     */
    public function writeCsv(array $exportResult, string $filePath): int
    {
        $csv = $this->toCsv($exportResult);
        return file_put_contents($filePath, $csv);
    }

    /**
     * Get available coordinate sources
     */
    public static function getAvailableSources(): array
    {
        return [
            'property' => 'Property Table (custom fields)',
            'nmmz_site' => 'NMMZ Archaeological Site Table',
            'dam_metadata' => 'DAM IPTC Metadata (from images)',
            'contact_info' => 'Repository Contact Information',
            'scope_field' => 'Scope and Content Field (requires parsing)',
        ];
    }

    /**
     * Get configurable tradition terms
     */
    public function getTraditionConfig(): array
    {
        return [
            'painted' => $this->paintedTerms,
            'engraved' => $this->engravedTerms,
        ];
    }

    /**
     * Coarsen or withhold coordinates unless the caller may see them exactly.
     *
     * Delegates to ahgSiteRecordPlugin's LocalityVisibilityService rather than
     * re-implementing the rule - a second copy of a disclosure rule is a second place
     * for it to drift, and the whole point of that service is that there is one path
     * to a coordinate.
     */
    protected function applyLocalityVisibility(array $row): array
    {
        $service = 'AhgSiteRecordPlugin\\Services\\LocalityVisibilityService';

        if (!class_exists($service)) {
            return $row;   // plugin not installed: nothing gated, nothing to enforce
        }

        try {
            $user = \sfContext::hasInstance() ? \sfContext::getInstance()->getUser() : null;
            $sensitive = !isset($row['locality_sensitive']) || (bool) $row['locality_sensitive'];

            if (!$sensitive) {
                return $row;
            }

            if (method_exists($service, 'canSeeExact') && $service::canSeeExact($user, (object) $row)) {
                return $row;
            }

            // Not cleared: coarsen the position and withhold the map sheet, exactly as
            // the record view does.
            if (method_exists($service, 'coarsen')) {
                $coarse = $service::coarsen($row['latitude'] ?? null, $row['longitude'] ?? null);
                $row['latitude'] = $coarse['latitude'] ?? null;
                $row['longitude'] = $coarse['longitude'] ?? null;
            } else {
                $row['latitude'] = null;
                $row['longitude'] = null;
            }

            $row['map_sheet'] = '';
            $row['coordinate_precision'] = 'coarsened';
        } catch (\Throwable $e) {
            // Never fail open on a disclosure rule: if the decision cannot be made,
            // withhold.
            $row['latitude'] = null;
            $row['longitude'] = null;
            $row['map_sheet'] = '';
            $row['coordinate_precision'] = 'withheld';
        }

        return $row;
    }
}
