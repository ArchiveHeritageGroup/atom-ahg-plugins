<?php

/**
 * ViafAdapter - VIAF AutoSuggest lookup.
 *
 * Endpoint: https://viaf.org/viaf/AutoSuggest?query=<text>
 * Auth: none. License: CC0-1.0.
 *
 * Returns ranked records keyed by VIAF id, each with a display name, the
 * authoritative URI, and the small set of fields VIAF AutoSuggest exposes:
 * primary heading, name type (Personal/Corporate/Geographic), and dates
 * where the suggest endpoint includes them.
 *
 * For richer fields (mandates, birth/death dates, places) the prefill engine
 * is welcome to follow-up with a SRU call to https://viaf.org/viaf/<id>/viaf.json
 * - that fetch is intentionally deferred until a user reports the autosuggest
 * fields are insufficient.
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

class ViafAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'viaf';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        $url = 'https://viaf.org/viaf/AutoSuggest?' . http_build_query([
            'query' => $queryText,
        ]);
        $decoded = $this->httpGetJson($url);
        if (isset($decoded['error'])) {
            return $decoded;
        }
        $items = is_array($decoded['result'] ?? null) ? $decoded['result'] : [];

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $viafId = (string) ($item['viafid'] ?? '');
            $term = (string) ($item['term'] ?? '');
            if ($viafId === '' || $term === '') {
                continue;
            }
            $nameType = strtolower((string) ($item['nametype'] ?? ''));
            if (!$this->typeMatches($entityType, $nameType)) {
                continue;
            }
            $fields = [
                'authorized_form_of_name' => $term,
                'viaf_id' => $viafId,
                'name_type' => $nameType ?: null,
            ];
            // VIAF AutoSuggest sometimes returns birth/death year on persons.
            foreach (['birthDate', 'deathDate', 'displayForm', 'lc', 'wkp'] as $k) {
                if (isset($item[$k]) && $item[$k] !== '') {
                    $fields[$k] = $item[$k];
                }
            }
            // Map well-known external identifiers when present.
            if (!empty($item['wkp'])) {
                $fields['wikidata_id'] = (string) $item['wkp'];
            }
            $results[] = [
                'id' => $viafId,
                'uri' => 'https://viaf.org/viaf/' . $viafId,
                'display_name' => $term,
                'fields' => $fields,
                'raw' => $item,
            ];
        }
        return ['results' => $results];
    }

    private function typeMatches(string $entityType, string $nameType): bool
    {
        $entityType = strtoupper($entityType);
        if ($nameType === '') {
            return true; // be permissive when VIAF omits the type
        }
        if ($entityType === 'PERSON') {
            return strpos($nameType, 'personal') !== false;
        }
        if ($entityType === 'ORG') {
            return strpos($nameType, 'corporate') !== false;
        }
        if ($entityType === 'PLACE') {
            return strpos($nameType, 'geographic') !== false;
        }
        return true;
    }
}
