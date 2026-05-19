<?php

/**
 * FusekiAgentAdapter - service for AtoM Heratio (STUB)
 *
 * Future home of the SPARQL query against the AHG Fuseki dataset for
 * rico:Agent nodes (rico:hasOrHadAgentName / skos:prefLabel match).
 *
 * Stub-returns [] today so CandidateGeneratorService can wire it into
 * the adapter list without conditional logic. When Task 8 / a later
 * task adds the read path it will:
 *   - SELECT ?agent ?label WHERE { ?agent a rico:Agent ; skos:prefLabel ?label . FILTER CONTAINS(...) }
 *   - emit rows with source='fuseki_agent', authority_id=null,
 *     fuseki_uri=<bound IRI>, display_name=<label>.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution\Adapters;

class FusekiAgentAdapter implements CandidateAdapterInterface
{
    private const SUPPORTED_TYPES = ['PERSON', 'ORG'];

    public function supports(string $entityType): bool
    {
        return in_array($entityType, self::SUPPORTED_TYPES, true);
    }

    /**
     * Stub. Returns no candidates today. See class docblock for the
     * planned SPARQL contract.
     */
    public function search(string $query, string $entityType, int $limit): array
    {
        return [];
    }
}
