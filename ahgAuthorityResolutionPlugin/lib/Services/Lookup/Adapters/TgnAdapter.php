<?php

/**
 * TgnAdapter - Getty Thesaurus of Geographic Names (TGN) lookup.
 *
 * STUB. Real integration deferred until at least one customer requests TGN.
 * The intended endpoint is the Getty SPARQL service at
 * https://vocab.getty.edu/sparql.json with a CONTAINS(LCASE(?name), ...)
 * filter against the TGN graph; that wiring lives in a future iteration.
 *
 * Returning an empty result-set keeps the PrefillEngine code path uniform
 * across enabled-but-stubbed sources and live sources.
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

class TgnAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'tgn';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        // STUB: endpoint integration deferred. Real call would be a SPARQL
        // SELECT against https://vocab.getty.edu/sparql.json for the TGN graph.
        return ['results' => []];
    }
}
