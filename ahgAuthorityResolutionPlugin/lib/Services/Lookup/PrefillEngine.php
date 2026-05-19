<?php

/**
 * PrefillEngine - merge mention context + external authority lookups into a
 * pre-filled form payload with per-field provenance.
 *
 * Given an ahg_mention.id, returns:
 *
 *   [
 *     'mention'        => stdClass row,
 *     'context'        => stdClass row,
 *     'lookup_results' => [
 *       'viaf'     => ['results' => [...], 'cached' => bool, 'error' => ?string],
 *       'wikidata' => ...
 *     ],
 *     'merged_fields'  => [
 *       'authorized_form_of_name' => [
 *         'value' => 'Frederick Douglass',
 *         '_provenance' => [
 *           'source'   => 'viaf',
 *           'uri'      => 'https://viaf.org/viaf/12345',
 *           'license'  => 'CC0-1.0',
 *           'cached'   => false,
 *           'at'       => '2026-05-19T10:00:00Z',
 *         ],
 *       ],
 *       ...
 *     ],
 *   ]
 *
 * Precedence is read from ahg_settings.authority_resolution.lookup.precedence
 * (a JSON array); the first source listed wins. Mention context fills any
 * field still empty after the external pass.
 *
 * Mirror of the Laravel-side PrefillEngine; both codebases produce the same
 * merged_fields shape so downstream UI is portable.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution\Lookup;

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/LookupAdapterInterface.php';

class PrefillEngine
{
    /** @var LookupAdapterInterface[] keyed by source() */
    private $adapters = [];

    /** @var string[] precedence order, first wins */
    private $precedence;

    /**
     * @param LookupAdapterInterface[] $adapters
     * @param string[]|null            $precedence override; falls back to ahg_settings
     */
    public function __construct(array $adapters, ?array $precedence = null)
    {
        foreach ($adapters as $adapter) {
            if ($adapter instanceof LookupAdapterInterface) {
                $this->adapters[$adapter->source()] = $adapter;
            }
        }
        $this->precedence = $precedence !== null
            ? $precedence
            : $this->loadPrecedenceFromSettings();
    }

    /**
     * Build the pre-fill payload for one mention.
     *
     * @param int         $mentionId
     * @param string|null $forceSource if set, restricts the external lookup to
     *                                 just that source (debug / single-source UX).
     */
    public function prefill(int $mentionId, ?string $forceSource = null): array
    {
        $mention = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'm.object_id')->where('ioi.culture', '=', 'en');
            })
            ->where('m.id', $mentionId)
            ->first([
                'm.id', 'm.ner_entity_id', 'm.object_id', 'm.entity_type',
                'm.state', 'n.entity_value', 'n.original_value', 'n.confidence',
                'ioi.title as io_title',
            ]);

        if (!$mention) {
            return [
                'mention' => null,
                'context' => null,
                'lookup_results' => [],
                'merged_fields' => [],
                'error' => "mention #{$mentionId} not found",
            ];
        }

        $context = DB::table('ahg_mention_context')->where('mention_id', $mentionId)->first();
        $entityType = $this->normaliseEntityType((string) $mention->entity_type);
        $queryText = (string) ($mention->entity_value ?? '');

        // Run every enabled adapter (or just the forced one).
        $lookupResults = [];
        foreach ($this->adapters as $source => $adapter) {
            if ($forceSource !== null && $forceSource !== $source) {
                continue;
            }
            if (!$adapter->isEnabled()) {
                $lookupResults[$source] = ['source' => $source, 'results' => [], 'cached' => false, 'enabled' => false];
                continue;
            }
            $result = $adapter->lookup($entityType, $queryText);
            $result['enabled'] = true;
            $lookupResults[$source] = $result;
        }

        // Merge fields with precedence; context fills gaps.
        $merged = $this->mergeFields($lookupResults, $context, $mention);

        return [
            'mention' => $mention,
            'context' => $context,
            'lookup_results' => $lookupResults,
            'merged_fields' => $merged,
        ];
    }

    // ---------------------------------------------------------------------
    // Internal helpers
    // ---------------------------------------------------------------------

    private function normaliseEntityType(string $type): string
    {
        $upper = strtoupper($type);
        if (in_array($upper, ['GPE', 'LOC', 'ISAD_PLACE'], true)) {
            return 'PLACE';
        }
        return $upper;
    }

    private function mergeFields(array $lookupResults, $context, $mention): array
    {
        $merged = [];

        // First pass: external sources in precedence order. First-wins per field.
        $ordered = $this->orderedSources(array_keys($lookupResults));
        foreach ($ordered as $source) {
            $payload = $lookupResults[$source] ?? null;
            if (!$payload || empty($payload['enabled'])) {
                continue;
            }
            $first = isset($payload['results'][0]) && is_array($payload['results'][0])
                ? $payload['results'][0]
                : null;
            if ($first === null) {
                continue;
            }
            $fields = is_array($first['fields'] ?? null) ? $first['fields'] : [];
            $uri = (string) ($first['uri'] ?? '');
            $license = (string) ($this->setting($source, 'license_note') ?: '');
            $cached = !empty($payload['cached']);
            foreach ($fields as $key => $value) {
                if (isset($merged[$key])) {
                    continue; // first-wins
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $merged[$key] = [
                    'value' => $value,
                    '_provenance' => [
                        'source' => $source,
                        'uri' => $uri,
                        'license' => $license,
                        'cached' => $cached,
                        'at' => gmdate('Y-m-d\TH:i:s\Z'),
                    ],
                ];
            }
        }

        // Second pass: derive a name from the mention itself if no source filled it.
        $nameKey = $this->isPlace($mention) ? 'name' : 'authorized_form_of_name';
        if (!isset($merged[$nameKey]) && !empty($mention->entity_value)) {
            $merged[$nameKey] = [
                'value' => (string) $mention->entity_value,
                '_provenance' => [
                    'source' => 'mention',
                    'uri' => null,
                    'license' => null,
                    'cached' => false,
                    'at' => gmdate('Y-m-d\TH:i:s\Z'),
                ],
            ];
        }

        // Third pass: context-derived hints for the "places" or "history" field.
        if ($context && !$this->isPlace($mention)) {
            $places = $context->nearby_places ? json_decode((string) $context->nearby_places, true) : null;
            if (is_array($places) && !empty($places) && !isset($merged['places'])) {
                $list = [];
                foreach ($places as $p) {
                    if (!empty($p['value'])) {
                        $list[] = (string) $p['value'];
                    }
                }
                if (!empty($list)) {
                    $merged['places'] = [
                        'value' => implode('; ', array_unique($list)),
                        '_provenance' => [
                            'source' => 'mention_context',
                            'uri' => null,
                            'license' => null,
                            'cached' => false,
                            'at' => gmdate('Y-m-d\TH:i:s\Z'),
                        ],
                    ];
                }
            }
        }

        return $merged;
    }

    private function orderedSources(array $available): array
    {
        $ordered = [];
        foreach ($this->precedence as $source) {
            if (in_array($source, $available, true)) {
                $ordered[] = $source;
            }
        }
        foreach ($available as $source) {
            if (!in_array($source, $ordered, true)) {
                $ordered[] = $source;
            }
        }
        return $ordered;
    }

    private function isPlace($mention): bool
    {
        if (!$mention) {
            return false;
        }
        return in_array(strtoupper((string) $mention->entity_type), ['GPE', 'LOC', 'PLACE', 'ISAD_PLACE'], true);
    }

    private function setting(string $source, string $param)
    {
        try {
            $key = "authority_resolution.lookup.{$source}.{$param}";
            return DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadPrecedenceFromSettings(): array
    {
        try {
            $raw = DB::table('ahg_settings')
                ->where('setting_key', 'authority_resolution.lookup.precedence')
                ->value('setting_value');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('strval', $decoded)));
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return ['viaf', 'wikidata', 'geonames', 'tgn', 'gnd', 'isni', 'sagnc'];
    }
}
