<?php

/**
 * ContextDerivationService — service for AtoM Heratio
 *
 * Pure-PHP analyzer. Given a source text + a mention value + the co-occurring
 * NER entities for the same object, derives the neighbourhood-context packet
 * stored in ahg_mention_context: offsets, surrounding text, co-occurring
 * entities filtered by paragraph proximity, nearby dates/places, and
 * role-language tokens within range.
 *
 * No DB calls. No I/O. Single public method: derive().
 * Mirror of the Heratio-side ahg-authority-resolution package; logic kept
 * line-for-line equivalent so both codebases produce the same context shape.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution;

class ContextDerivationService
{
    private const SURROUNDING_TEXT_WINDOW = 150;
    private const ROLE_LANGUAGE_CHAR_WINDOW = 120;

    private const PLACE_TYPES = ['GPE', 'LOC', 'PLACE', 'ISAD_PLACE'];
    private const DATE_TYPES = ['DATE', 'ISAD_DATE'];

    /**
     * Derive the full context packet for a mention.
     *
     * @param string     $sourceText
     * @param string     $mentionValue
     * @param string     $mentionType
     * @param array      $otherEntities  list of {ner_entity_id,value,type}
     * @param array      $roleLanguageTokens  map of kind => list of token strings
     * @param array|null $knownOffset    {start:int,end:int} from entities_v2.
     *                                   When provided the lossy stripos scan is
     *                                   skipped and the API offset is used as
     *                                   the sole occurrence (occurrence_count=1).
     */
    public function derive(
        string $sourceText,
        string $mentionValue,
        string $mentionType,
        array $otherEntities,
        array $roleLanguageTokens,
        ?array $knownOffset = null
    ): array {
        if ($knownOffset !== null
            && isset($knownOffset['start'], $knownOffset['end'])) {
            $occurrences = [[(int) $knownOffset['start'], (int) $knownOffset['end']]];
        } else {
            $occurrences = $this->findAllOccurrences($sourceText, $mentionValue);
        }

        if (empty($occurrences)) {
            return $this->emptyContext(0);
        }

        [$startOffset, $endOffset] = $occurrences[0];

        $paragraph = $this->findEnclosingParagraph($sourceText, $startOffset);
        $surrounding = $this->getSurroundingText($sourceText, $startOffset, $endOffset);
        $coOccurring = $this->findEntitiesInParagraph($paragraph, $otherEntities, $startOffset);

        $nearbyDates = $this->partitionByTypes($coOccurring, self::DATE_TYPES);
        $nearbyPlaces = $this->partitionByTypes($coOccurring, self::PLACE_TYPES);
        $coOccurringFiltered = $this->excludeTypes($coOccurring, array_merge(self::DATE_TYPES, self::PLACE_TYPES));

        $roleTokens = $this->findRoleLanguage($paragraph, $startOffset, $roleLanguageTokens);

        return [
            'character_offset_start' => $startOffset,
            'character_offset_end' => $endOffset,
            'paragraph_offset_start' => $paragraph['start'],
            'paragraph_offset_end' => $paragraph['end'],
            'surrounding_text_before' => $surrounding['before'],
            'surrounding_text_after' => $surrounding['after'],
            'co_occurring_entities' => $coOccurringFiltered,
            'nearby_dates' => $nearbyDates,
            'nearby_places' => $nearbyPlaces,
            'role_language_tokens' => $roleTokens,
            'ambiguity' => [
                'occurrence_count' => count($occurrences),
            ],
        ];
    }

    private function findAllOccurrences(string $haystack, string $needle): array
    {
        if ($needle === '' || $haystack === '') {
            return [];
        }
        $found = [];
        $offset = 0;
        $needleLen = strlen($needle);
        while (($pos = stripos($haystack, $needle, $offset)) !== false) {
            $found[] = [$pos, $pos + $needleLen];
            $offset = $pos + $needleLen;
        }
        return $found;
    }

    private function findEnclosingParagraph(string $sourceText, int $offset): array
    {
        $before = substr($sourceText, 0, $offset);
        $after = substr($sourceText, $offset);

        $start = 0;
        if (preg_match_all('/\n\s*\n/', $before, $matches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($matches[0]);
            $start = $lastMatch[1] + strlen($lastMatch[0]);
        }
        $end = strlen($sourceText);
        if (preg_match('/\n\s*\n/', $after, $matches, PREG_OFFSET_CAPTURE)) {
            $end = $offset + $matches[0][1];
        }

        return [
            'start' => $start,
            'end' => $end,
            'text' => substr($sourceText, $start, $end - $start),
        ];
    }

    private function getSurroundingText(string $sourceText, int $startOffset, int $endOffset): array
    {
        $len = strlen($sourceText);
        // Snap to valid UTF-8 char boundaries so substr() doesn't cut mid-sequence
        // (would produce invalid UTF-8 that MySQL rejects under strict modes).
        $beforeStart = max(0, $startOffset - self::SURROUNDING_TEXT_WINDOW);
        while ($beforeStart < $len && $beforeStart < $startOffset && (ord($sourceText[$beforeStart]) & 0xC0) === 0x80) {
            $beforeStart++;
        }
        $before = substr($sourceText, $beforeStart, $startOffset - $beforeStart);

        $afterEnd = min($len, $endOffset + self::SURROUNDING_TEXT_WINDOW);
        while ($afterEnd > $endOffset && $afterEnd < $len && (ord($sourceText[$afterEnd]) & 0xC0) === 0x80) {
            $afterEnd--;
        }
        $after = substr($sourceText, $endOffset, $afterEnd - $endOffset);

        return ['before' => $before, 'after' => $after];
    }

    private function findEntitiesInParagraph(array $paragraph, array $others, int $mentionAbsOffset): array
    {
        $hits = [];
        $paragraphText = $paragraph['text'];
        $mentionInPara = $mentionAbsOffset - $paragraph['start'];

        foreach ($others as $other) {
            if (($other['value'] ?? '') === '') {
                continue;
            }
            $pos = stripos($paragraphText, $other['value']);
            if ($pos === false) {
                continue;
            }
            $hits[] = [
                'ner_entity_id' => $other['ner_entity_id'] ?? null,
                'value' => $other['value'],
                'type' => $other['type'],
                'character_offset_start' => $paragraph['start'] + $pos,
                'distance_chars' => abs($pos - $mentionInPara),
            ];
        }
        return $hits;
    }

    private function partitionByTypes(array $entities, array $types): array
    {
        return array_values(array_filter(
            $entities,
            function ($e) use ($types) {
                return in_array($e['type'] ?? '', $types, true);
            }
        ));
    }

    private function excludeTypes(array $entities, array $types): array
    {
        return array_values(array_filter(
            $entities,
            function ($e) use ($types) {
                return !in_array($e['type'] ?? '', $types, true);
            }
        ));
    }

    private function findRoleLanguage(array $paragraph, int $mentionAbsOffset, array $tokenList): array
    {
        $hits = [];
        $paragraphText = $paragraph['text'];
        $mentionInPara = $mentionAbsOffset - $paragraph['start'];

        foreach ($tokenList as $kind => $tokens) {
            if (!is_array($tokens)) {
                continue;
            }
            foreach ($tokens as $token) {
                if (!is_string($token) || $token === '') {
                    continue;
                }
                $offset = 0;
                while (($pos = stripos($paragraphText, $token, $offset)) !== false) {
                    $distance = abs($pos - $mentionInPara);
                    if ($distance <= self::ROLE_LANGUAGE_CHAR_WINDOW) {
                        $hits[] = [
                            'token' => $token,
                            'kind' => (string) $kind,
                            'position_offset' => $paragraph['start'] + $pos,
                            'distance_chars' => $distance,
                        ];
                    }
                    $offset = $pos + strlen($token);
                }
            }
        }
        return $hits;
    }

    private function emptyContext(int $occurrenceCount): array
    {
        return [
            'character_offset_start' => null,
            'character_offset_end' => null,
            'paragraph_offset_start' => null,
            'paragraph_offset_end' => null,
            'surrounding_text_before' => null,
            'surrounding_text_after' => null,
            'co_occurring_entities' => [],
            'nearby_dates' => [],
            'nearby_places' => [],
            'role_language_tokens' => [],
            'ambiguity' => ['occurrence_count' => $occurrenceCount],
        ];
    }
}
