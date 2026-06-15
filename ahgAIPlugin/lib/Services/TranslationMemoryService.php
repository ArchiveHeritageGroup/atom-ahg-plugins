<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Translation memory (DB-audit build-order #4).
 *
 * A reuse cache over ahg_translation_memory keyed by (sha256(source), target_lang):
 * before the translator runs, lookup() returns a prior translation of the same
 * source into the same language (bumping its hit counter); after a fresh
 * translation, store() records it. Saves re-translating identical strings and
 * keeps terminology consistent across records.
 *
 * Self-contained and best-effort: a missing table never breaks translation —
 * lookup() returns null and store() silently no-ops.
 */
class TranslationMemoryService
{
    /** Return a remembered translation of $text into $to, or null on a miss. */
    public static function lookup(string $text, string $from, string $to): ?string
    {
        $text = trim($text);
        if ('' === $text || '' === $to) {
            return null;
        }

        try {
            $hash = hash('sha256', $text);
            $row = DB::table('ahg_translation_memory')
                ->where('source_text_hash', $hash)
                ->where('target_lang', $to)
                ->first(['id', 'target_text']);

            if (!$row) {
                return null;
            }

            DB::table('ahg_translation_memory')->where('id', $row->id)->update([
                'hit_count' => DB::raw('hit_count + 1'),
                'last_used_at' => date('Y-m-d H:i:s'),
            ]);

            return (string) $row->target_text;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Remember a translation. Upsert on (hash, target_lang); an existing entry is
     * only overwritten when the new provenance is at least as authoritative
     * (human/reviewed beats machine), so a human edit is never clobbered by a
     * later machine pass.
     */
    public static function store(
        string $sourceText,
        string $from,
        string $to,
        string $targetText,
        string $provenance = 'machine',
        ?float $confidence = null
    ): void {
        $sourceText = trim($sourceText);
        $targetText = trim($targetText);
        if ('' === $sourceText || '' === $targetText || '' === $to) {
            return;
        }

        try {
            $hash = hash('sha256', $sourceText);
            $now = date('Y-m-d H:i:s');
            $existing = DB::table('ahg_translation_memory')
                ->where('source_text_hash', $hash)->where('target_lang', $to)
                ->first(['id', 'provenance']);

            if ($existing) {
                if (self::rank($provenance) < self::rank((string) $existing->provenance)) {
                    return; // don't downgrade a more authoritative entry.
                }
                DB::table('ahg_translation_memory')->where('id', $existing->id)->update([
                    'source_lang' => $from,
                    'source_text' => $sourceText,
                    'target_text' => $targetText,
                    'provenance' => $provenance,
                    'confidence' => $confidence,
                ]);

                return;
            }

            DB::table('ahg_translation_memory')->insert([
                'source_text_hash' => $hash,
                'source_lang' => $from,
                'target_lang' => $to,
                'source_text' => $sourceText,
                'target_text' => $targetText,
                'provenance' => $provenance,
                'confidence' => $confidence,
                'hit_count' => 0,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            // table absent / write failure: TM is an optimisation, never fatal.
        }
    }

    /** Aggregate stats for a dashboard. @return array{entries:int,hits:int,by_provenance:array} */
    public static function stats(): array
    {
        try {
            return [
                'entries' => (int) DB::table('ahg_translation_memory')->count(),
                'hits' => (int) DB::table('ahg_translation_memory')->sum('hit_count'),
                'by_provenance' => DB::table('ahg_translation_memory')
                    ->select('provenance', DB::raw('COUNT(*) AS c'))
                    ->groupBy('provenance')->pluck('c', 'provenance')->all(),
            ];
        } catch (\Throwable $e) {
            return ['entries' => 0, 'hits' => 0, 'by_provenance' => []];
        }
    }

    /** Provenance authority order: reviewed > human > machine. */
    private static function rank(string $provenance): int
    {
        switch (strtolower($provenance)) {
            case 'reviewed': return 3;
            case 'human': return 2;
            default: return 1; // machine / unknown
        }
    }
}
