<?php

/**
 * WikidataAdapter - Wikidata wbsearchentities lookup.
 *
 * Endpoint: https://www.wikidata.org/w/api.php?action=wbsearchentities
 * Auth: none. License: CC0-1.0.
 *
 * Returns ranked Q-id matches with label, description, and the concept URI.
 * Optionally follow-up calls to wbgetentities can pull birth/death dates,
 * occupations, coords, etc.; that enrichment is deferred until a downstream
 * field-fill obviously needs it.
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

class WikidataAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'wikidata';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        $url = 'https://www.wikidata.org/w/api.php?' . http_build_query([
            'action' => 'wbsearchentities',
            'search' => $queryText,
            'language' => 'en',
            'limit' => 10,
            'format' => 'json',
            'type' => 'item',
        ]);
        $decoded = $this->httpGetJson($url);
        if (isset($decoded['error']) && !isset($decoded['search'])) {
            return $decoded;
        }
        $items = is_array($decoded['search'] ?? null) ? $decoded['search'] : [];

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qid = (string) ($item['id'] ?? '');
            $label = (string) ($item['label'] ?? '');
            if ($qid === '' || $label === '') {
                continue;
            }
            $description = (string) ($item['description'] ?? '');
            $uri = (string) ($item['concepturi'] ?? "https://www.wikidata.org/wiki/{$qid}");

            $fields = [
                'authorized_form_of_name' => $label,
                'wikidata_id' => $qid,
            ];
            if ($description !== '') {
                // Wikidata descriptions are short prose - reasonable seed for the
                // ISAAR-CPF "history" field. Marked tentative via provenance.
                $fields['history'] = $description;
            }

            $results[] = [
                'id' => $qid,
                'uri' => $uri,
                'display_name' => $label,
                'fields' => $fields,
                'raw' => $item,
            ];
        }
        return ['results' => $results];
    }
}
