<?php

/**
 * CandidateAdapterInterface - service for AtoM Heratio
 *
 * Contract for an authority-candidate source. Each adapter knows how to
 * search exactly one underlying store (MySQL actor, MySQL term, Fuseki
 * rico:Agent, Fuseki rico:Place, ...) and return a uniform candidate
 * row shape so CandidateGeneratorService can blend results regardless
 * of origin.
 *
 * Adapter rules:
 *   - search() MUST return a list of associative arrays with exactly the
 *     keys: source, authority_id, fuseki_uri, display_name.
 *   - source is a short slug matching ahg_mention_candidate.candidate_source
 *     ('mysql_actor', 'mysql_term', 'fuseki_agent', 'fuseki_place').
 *   - authority_id is the integer FK into actor.id or term.id where
 *     applicable; null for Fuseki-only candidates.
 *   - fuseki_uri is the absolute RiC IRI for Fuseki-sourced rows; null
 *     for MySQL-sourced rows.
 *   - display_name is a human-readable label (authorized form / preferred
 *     term name). Used both for UI rendering AND name-similarity scoring,
 *     so it MUST be the form a user would type when looking the entity up.
 *
 * Symfony 1.4 has no PSR-4 autoloader for our namespaced plugin classes,
 * so concrete adapters live alongside this interface and the consuming
 * task file MUST require_once them explicitly.
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

interface CandidateAdapterInterface
{
    /**
     * Is this adapter willing to serve candidates for the given entity_type?
     *
     * @param string $entityType  PERSON | ORG | GPE | PLACE | LOC
     */
    public function supports(string $entityType): bool;

    /**
     * Find candidates whose display name matches $query for the given
     * $entityType. Implementations should return at most $limit rows;
     * the caller blends across adapters before re-trimming to top-N.
     *
     * Return shape (per row):
     *   [
     *     'source'        => 'mysql_actor' | 'mysql_term' | 'fuseki_agent' | 'fuseki_place',
     *     'authority_id'  => int|null,
     *     'fuseki_uri'    => string|null,
     *     'display_name'  => string,
     *   ]
     *
     * @return array<int, array{source:string, authority_id:?int, fuseki_uri:?string, display_name:string}>
     */
    public function search(string $query, string $entityType, int $limit): array;
}
