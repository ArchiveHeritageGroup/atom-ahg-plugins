<?php

/**
 * IsniAdapter - International Standard Name Identifier lookup.
 *
 * STUB. Real integration deferred until institutional ISNI credentials are
 * provisioned. Intended endpoint is the SRU service at
 * http://isni.oclc.org/sru/?query=... (institutional auth required).
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

class IsniAdapter extends AbstractLookupAdapter
{
    public function source(): string
    {
        return 'isni';
    }

    protected function executeRemote(string $entityType, string $queryText): array
    {
        // STUB: endpoint integration deferred. ISNI SRU requires institutional
        // credentials negotiated through ISNI directly; once provisioned the
        // call shape is GET http://isni.oclc.org/sru/?query=pica.nw=<text>...
        return ['results' => []];
    }
}
