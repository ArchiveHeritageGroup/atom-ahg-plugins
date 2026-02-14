<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Builds a FlexSearch-compatible client-side search index from catalogue data.
 *
 * Indexes title, identifier, scope_and_content, creators, subjects, and places
 * for fast client-side full-text search in the portable viewer.
 */
class SearchIndexBuilder
{
    /**
     * Build a search index from extracted catalogue descriptions.
     *
     * Returns an array of indexable documents suitable for FlexSearch.import()
     * on the client side.
     *
     * @param array $descriptions Extracted descriptions from CatalogueExtractor
     *
     * @return array{documents: array, stats: array}
     */
    public function buildIndex(array $descriptions): array
    {
        $documents = [];

        foreach ($descriptions as $desc) {
            $doc = [
                'id' => $desc['id'],
                'title' => $this->cleanText($desc['title'] ?? ''),
                'identifier' => $desc['identifier'] ?? '',
                'content' => $this->cleanText($desc['scope_and_content'] ?? ''),
                'level' => $desc['level_of_description'] ?? '',
                'creators' => implode(' ', $desc['creators'] ?? []),
                'subjects' => implode(' ', $desc['subjects'] ?? []),
                'places' => implode(' ', $desc['places'] ?? []),
                'dates' => $this->extractDateStrings($desc['dates'] ?? []),
                'extent' => $this->cleanText($desc['extent_and_medium'] ?? ''),
            ];

            $documents[] = $doc;
        }

        return [
            'documents' => $documents,
            'stats' => [
                'total_documents' => count($documents),
                'indexed_fields' => [
                    'title', 'identifier', 'content', 'level',
                    'creators', 'subjects', 'places', 'dates', 'extent',
                ],
            ],
        ];
    }

    /**
     * Clean text for indexing — strip HTML, normalize whitespace.
     */
    protected function cleanText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Extract date strings from event data.
     */
    protected function extractDateStrings(array $dates): string
    {
        $parts = [];
        foreach ($dates as $d) {
            if (!empty($d['date'])) {
                $parts[] = $d['date'];
            }
            if (!empty($d['start_date'])) {
                $parts[] = $d['start_date'];
            }
            if (!empty($d['end_date'])) {
                $parts[] = $d['end_date'];
            }
        }

        return implode(' ', $parts);
    }
}
