<?php

/**
 * InferenceRecord - AI inference provenance DTO (atom-ahg-plugins).
 *
 * Port of the Heratio ahg-provenance-ai DTO to the AtoM-AHG side - issue #140.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

namespace AhgProvenancePlugin\Service;

/**
 * Value object describing a single AI inference write.
 *
 * Every AtoM AI action (NER, summarize, HTR, ...) constructs one of these and
 * hands it to InferenceService::record(). The DTO is the contract: if a field
 * is missing here it cannot be persisted, so the contract is enforced at the
 * type level rather than via documentation.
 *
 * Hashes are sha256 hex (64 chars). Excerpts are truncated to 500 chars at
 * call time (see hashAndExcerpt) to keep the DTO purely structural.
 *
 * This mirrors heratio's AhgProvenanceAi\DTO\InferenceRecord field-for-field
 * so a manifest signed on either side carries the same shape.
 */
final class InferenceRecord
{
    public function __construct(
        public readonly string $serviceName,        // 'NER', 'HTR', 'SUMMARIZE', 'TRANSLATION', 'LLM', ...
        public readonly string $modelName,          // free-text identifier from the model
        public readonly string $modelVersion,       // 'unknown' if not retrievable
        public readonly string $inputHash,          // sha256 hex
        public readonly string $outputHash,         // sha256 hex
        public readonly string $targetEntityType,   // 'information_object', 'actor', ...
        public readonly int    $targetEntityId,
        public readonly string $targetField,        // RDF predicate / column being touched
        public readonly ?float  $confidence  = null, // 0.0-1.0; null when model does not expose
        public readonly ?string $standard    = null, // 'ICIP', 'ISAD(G)', 'Spectrum-5.1', ...
        public readonly ?string $endpoint    = null, // URL the inference was performed against
        public readonly ?string $inputExcerpt  = null,
        public readonly ?string $outputExcerpt = null,
        public readonly ?int    $elapsedMs   = null,
        public readonly ?int    $userId      = null, // triggering user; null for batch / cron
    ) {}

    /**
     * Convenience: hash + excerpt a string in one call. Mirrors what every
     * AI call site has to do before constructing the DTO.
     *
     * @return array{0:string,1:string} [hash, excerpt]
     */
    public static function hashAndExcerpt(string $text, int $excerptLen = 500): array
    {
        $hash = hash('sha256', $text);
        $excerpt = mb_strlen($text) > $excerptLen
            ? mb_substr($text, 0, $excerptLen)
            : $text;

        return [$hash, $excerpt];
    }
}
