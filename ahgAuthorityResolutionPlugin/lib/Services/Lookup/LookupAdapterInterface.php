<?php

/**
 * LookupAdapterInterface - external authority lookup contract.
 *
 * Implementations wrap one external authority API (VIAF, Wikidata, GeoNames,
 * TGN, GND, ISNI, SAGNC, ...). Each implementation reads its own settings
 * group from ahg_settings (lookup.<source>.enabled / rate_limit / cache_ttl /
 * license_note) and caches responses in ahg_authority_lookup_cache.
 *
 * All implementations MUST:
 *   - return an empty array when their `enabled` setting is 0 (no HTTP call).
 *   - return an empty array when no result is found (never a non-array).
 *   - cache successful responses in ahg_authority_lookup_cache by
 *     (source, entity_type, query_text).
 *   - respect cache_ttl by skipping HTTP when a fresh cache row exists.
 *
 * Mirror of the Laravel-side Adapter contract; both codebases produce the
 * same shape so the PrefillEngine code is portable.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution\Lookup;

interface LookupAdapterInterface
{
    /**
     * Stable identifier matching the settings key suffix and cache `source`
     * column (e.g. 'viaf', 'wikidata', 'geonames').
     */
    public function source(): string;

    /**
     * Return true when this adapter is configured to make HTTP calls.
     * MUST check ahg_settings.authority_resolution.lookup.<source>.enabled.
     */
    public function isEnabled(): bool;

    /**
     * Run a lookup for the given mention value.
     *
     * @param string $entityType One of PERSON | ORG | PLACE (caller normalises).
     * @param string $queryText  Free-text query - typically the mention's
     *                           entity_value, optionally augmented with context.
     * @return array{
     *   source: string,
     *   results: array<int, array{
     *     id?: string,
     *     uri?: string,
     *     display_name?: string,
     *     fields?: array<string, mixed>,
     *     raw?: mixed
     *   }>,
     *   cached?: bool,
     *   error?: string
     * }
     */
    public function lookup(string $entityType, string $queryText): array;
}
