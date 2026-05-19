<?php

/**
 * GeoNamesAdapter - GeoNames searchJSON lookup.
 *
 * Endpoint: https://secure.geonames.org/searchJSON?q=<text>&username=<u>
 * Auth: required (free username). License: CC BY 4.0.
 *
 * Returns ranked place matches with geoname id, lat/lng, country, admin
 * hierarchy, and feature class. The first hit is the canonical pick;
 * subsequent hits surface in the merged_fields debug output for manual
 * override.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution\Lookup\Adapters;

require_once dirname(__FILE__) . '/../AbstractLookupAdapter.php';

use AtomFramework\Services\AuthorityResolution\Lookup\AbstractLookupAdapter;

class GeoNamesAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'geonames';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        // GeoNames is a place-only source; non-place lookups return empty.
        if (strtoupper($entityType) !== 'PLACE') {
            return ['results' => []];
        }

        $username = trim((string) $this->setting('username', ''));
        if ($username === '') {
            return ['error' => 'geonames username not configured'];
        }

        $url = 'https://secure.geonames.org/searchJSON?' . http_build_query([
            'q' => $queryText,
            'maxRows' => 10,
            'username' => $username,
            'orderby' => 'relevance',
        ]);
        $decoded = $this->httpGetJson($url);
        if (isset($decoded['error']) && !isset($decoded['geonames'])) {
            return $decoded;
        }
        $items = is_array($decoded['geonames'] ?? null) ? $decoded['geonames'] : [];

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $gid = (string) ($item['geonameId'] ?? '');
            $name = (string) ($item['name'] ?? '');
            if ($gid === '' || $name === '') {
                continue;
            }
            $lat = isset($item['lat']) ? (float) $item['lat'] : null;
            $lng = isset($item['lng']) ? (float) $item['lng'] : null;
            $country = (string) ($item['countryName'] ?? '');
            $admin1 = (string) ($item['adminName1'] ?? '');
            $featureClass = (string) ($item['fclName'] ?? ($item['fcl'] ?? ''));

            $fields = [
                'name' => $name,
                'geonames_id' => $gid,
            ];
            if ($lat !== null && $lng !== null) {
                // Pre-fill description with a "lat,lng" pattern so the existing
                // place-coordinate regex in actions.class.php::resolvePlaceCoord
                // picks it up automatically.
                $fields['description'] = $lat . ',' . $lng
                    . ($country !== '' ? ' - ' . $country : '')
                    . ($admin1 !== '' ? ', ' . $admin1 : '');
                $fields['lat'] = $lat;
                $fields['lng'] = $lng;
            }
            if ($country !== '') {
                $fields['country'] = $country;
            }
            if ($admin1 !== '') {
                $fields['admin1'] = $admin1;
            }
            if ($featureClass !== '') {
                $fields['feature_class'] = $featureClass;
            }

            $results[] = [
                'id' => $gid,
                'uri' => 'https://www.geonames.org/' . $gid . '/',
                'display_name' => $name . ($country !== '' ? ', ' . $country : ''),
                'fields' => $fields,
                'raw' => $item,
            ];
        }
        return ['results' => $results];
    }
}
