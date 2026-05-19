<?php

/**
 * SagncAdapter - South African Geographical Names Council lookup.
 *
 * STUB. Real integration deferred. SAGNC does not currently publish a
 * machine-readable API; the canonical source is the printed Gazette and
 * a downloadable spreadsheet. A future iteration may ingest the spreadsheet
 * into a local table and serve from there.
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

class SagncAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'sagnc';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        // STUB: endpoint integration deferred. No public SAGNC JSON API today.
        return ['results' => []];
    }
}
