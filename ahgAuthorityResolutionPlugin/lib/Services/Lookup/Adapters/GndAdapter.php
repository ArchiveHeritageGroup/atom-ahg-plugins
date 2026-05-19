<?php

/**
 * GndAdapter - DNB GND (Gemeinsame Normdatei) lookup.
 *
 * STUB. Real integration deferred. The intended endpoint is the lobid wrapper
 * (https://lobid.org/gnd/search?q=...&format=json) which exposes GND records
 * as a JSON-LD feed under CC0.
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

class GndAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'gnd';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        // STUB: endpoint integration deferred. Real call would be GET
        // https://lobid.org/gnd/search?q=<text>&format=json&size=10.
        return ['results' => []];
    }
}
