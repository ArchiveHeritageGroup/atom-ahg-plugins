<?php

/**
 * EvaluatorInterface - per-dimension evidence evaluator contract for AtoM Heratio
 *
 * Implementations score a single evidence dimension (temporal, geographic,
 * relational, role, conflict, hierarchical, prior, co_occurring, scale)
 * for a single (mention, context, candidate) triple. They return a signal
 * enum value and a JSON-serialisable data payload that gets written to
 * ahg_mention_candidate.evidence_data for archivist review.
 *
 * Contract:
 *   dimension(): string
 *     Stable machine slug used as JSON key in evidence_signals + evidence_data.
 *
 *   supports(string $entityType): bool
 *     Per-entity-type opt-in. PERSON/ORG evaluators short-circuit on PLACE
 *     mentions; place evaluators short-circuit on PERSON/ORG mentions.
 *
 *   evaluate($mention, $context, $candidate): array{signal:string, data:array}
 *     Return signal = MATCH | CONFLICT | SILENT | ABSENT and a free-shape
 *     associative array (kept compact; this is shown in the review UI).
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution\Evidence;

interface EvaluatorInterface
{
    /**
     * Stable dimension slug, e.g. 'temporal', 'geographic'.
     */
    public function dimension(): string;

    /**
     * Whether this evaluator handles a given mention entity_type.
     * 'PERSON', 'ORG', 'GPE', 'PLACE', 'LOC' are the values that appear
     * in ahg_mention.entity_type today.
     */
    public function supports(string $entityType): bool;

    /**
     * Evaluate a single (mention, context, candidate) triple.
     *
     * @param object $mentionRow   row from ahg_mention joined with ahg_ner_entity
     * @param object $contextRow   row from ahg_mention_context (may be null-ish stdClass if missing)
     * @param object $candidateRow row from ahg_mention_candidate
     *
     * @return array{signal:string,data:array}
     */
    public function evaluate(object $mentionRow, object $contextRow, object $candidateRow): array;
}
