<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Service;

/**
 * Maps ISBN metadata to AtoM information object fields.
 */
class IsbnMetadataMapper
{
    private const FIELD_MAP = [
        'title' => ['table' => 'information_object_i18n', 'field' => 'title', 'type' => 'string'],
        'authors' => ['table' => 'relation', 'field' => 'creator', 'type' => 'relation', 'entity_type' => 'QubitActor'],
        'publishers' => ['table' => 'relation', 'field' => 'publisher', 'type' => 'relation', 'entity_type' => 'QubitActor'],
        'publish_date' => ['table' => 'event', 'field' => 'date', 'type' => 'event', 'event_type' => 'publication'],
        'number_of_pages' => ['table' => 'information_object_i18n', 'field' => 'extentAndMedium', 'type' => 'string', 'format' => '{value} pages'],
        'subjects' => ['table' => 'object_term_relation', 'field' => 'subject', 'type' => 'term', 'taxonomy' => 'subjects'],
        'description' => ['table' => 'information_object_i18n', 'field' => 'scopeAndContent', 'type' => 'string'],
        'language' => ['table' => 'object_term_relation', 'field' => 'language', 'type' => 'term', 'taxonomy' => 'languages'],
    ];

    public function mapToAtom(array $metadata): array
    {
        $result = [
            'fields' => [],
            'relations' => [],
            'events' => [],
            'notes' => [],
            'terms' => [],
            'properties' => [],
        ];

        foreach ($metadata as $key => $value) {
            if (empty($value) || !isset(self::FIELD_MAP[$key])) {
                continue;
            }

            $mapping = self::FIELD_MAP[$key];

            switch ($mapping['type']) {
                case 'string':
                    $result['fields'][$mapping['field']] = $this->formatValue($value, $mapping);
                    break;

                case 'relation':
                    $values = is_array($value) ? $value : [$value];
                    foreach ($values as $v) {
                        $result['relations'][] = [
                            'type' => $mapping['field'],
                            'entity_type' => $mapping['entity_type'],
                            'name' => $this->cleanName($v),
                        ];
                    }
                    break;

                case 'event':
                    $result['events'][] = [
                        'type' => $mapping['event_type'],
                        'field' => $mapping['field'],
                        'value' => $value,
                    ];
                    break;

                case 'term':
                    $values = is_array($value) ? $value : [$value];
                    foreach ($values as $v) {
                        $termValue = $v;

                        // Handle language ISO codes
                        if ($mapping['taxonomy'] === 'languages' && strlen($v) <= 3) {
                            $termValue = \AtomFramework\Services\LanguageService::getNameFromIsoCode($v);
                        }

                        $result['terms'][] = [
                            'taxonomy' => $mapping['taxonomy'],
                            'term' => $this->cleanTerm($termValue),
                        ];
                    }
                    break;
            }
        }

        return $result;
    }

    public function getPreviewData(array $metadata): array
    {
        $preview = [];

        if (!empty($metadata['title'])) {
            $title = $metadata['title'];
            if (!empty($metadata['subtitle'])) {
                $title .= ': ' . $metadata['subtitle'];
            }
            $preview['title'] = $title;
        }

        if (!empty($metadata['authors'])) {
            $preview['creators'] = is_array($metadata['authors'])
                ? implode('; ', array_map(function($a) { return $this->cleanName(is_array($a) ? ($a['name'] ?? '') : $a); }, $metadata['authors']))
                : $this->cleanName($metadata['authors']);
        }

        // Individual publisher field for Library form
        if (!empty($metadata['publishers'])) {
            $pubs = is_array($metadata['publishers']) ? $metadata['publishers'] : [$metadata['publishers']];
            $preview['publisher'] = $this->cleanName($pubs[0]);
        }

        // Publication date for Library form
        if (!empty($metadata['publish_date'])) {
            $preview['publication_date'] = $metadata['publish_date'];
        }

        // Publication place for Library form
        if (!empty($metadata['publish_places'])) {
            $places = is_array($metadata['publish_places']) ? $metadata['publish_places'] : [$metadata['publish_places']];
            $preview['publication_place'] = $this->cleanName($places[0] ?? '');
        }

        // Combined publication for ISAD form
        $pubParts = [];
        if (!empty($preview['publisher'])) {
            $pubParts[] = $preview['publisher'];
        }
        if (!empty($preview['publication_date'])) {
            $pubParts[] = $preview['publication_date'];
        }
        if ($pubParts) {
            $preview['publication'] = implode(', ', $pubParts);
        }

        if (!empty($metadata['number_of_pages'])) {
            $preview['extent'] = $metadata['number_of_pages'] . ' pages';
            $preview['pagination'] = $metadata['number_of_pages'];
        }

        if (!empty($metadata['subjects'])) {
            $subjects = is_array($metadata['subjects']) ? $metadata['subjects'] : [$metadata['subjects']];
            // Handle enhanced subject structure from WorldCat (Issue #55)
            // Subjects can be: strings, arrays with 'name' key, or enhanced arrays with 'heading' key
            $preview['subjects'] = array_slice(array_map(function($s) {
                if (is_string($s)) {
                    return $s;
                }
                // Enhanced structure from WorldCat MARC parsing
                if (isset($s['heading'])) {
                    return $s['heading'];
                }
                // Open Library structure
                if (isset($s['name'])) {
                    return $s['name'];
                }
                return (string) $s;
            }, $subjects), 0, 10);

            // Store full subject data if available (for authority linking)
            if (isset($subjects[0]) && is_array($subjects[0]) && isset($subjects[0]['heading'])) {
                $preview['subjects_enhanced'] = array_slice($subjects, 0, 10);
            }
        }

        // Handle backward compatible subjects_simple from WorldCat
        if (!empty($metadata['subjects_simple']) && empty($preview['subjects'])) {
            $preview['subjects'] = array_slice($metadata['subjects_simple'], 0, 10);
        }

        // Classifications from WorldCat (Issue #55)
        if (!empty($metadata['classifications'])) {
            if (!empty($metadata['classifications']['lcc'])) {
                $preview['call_number'] = $metadata['classifications']['lcc'];
            }
            if (!empty($metadata['classifications']['dewey'])) {
                $preview['dewey_decimal'] = $metadata['classifications']['dewey'];
            }
        }

        $preview['identifiers'] = [];
        if (!empty($metadata['isbn_13'])) {
            $preview['identifiers']['ISBN-13'] = $metadata['isbn_13'];
        }
        if (!empty($metadata['isbn_10'])) {
            $preview['identifiers']['ISBN-10'] = $metadata['isbn_10'];
        }

        // LCCN
        if (!empty($metadata['lccn'])) {
            $preview['lccn'] = $metadata['lccn'];
            $preview['identifiers']['LCCN'] = $metadata['lccn'];
        }

        // OCLC Number
        if (!empty($metadata['oclc_number'])) {
            $preview['oclc_number'] = $metadata['oclc_number'];
            $preview['identifiers']['OCLC'] = $metadata['oclc_number'];
        }

        // Language - convert ISO code to name from database
        if (!empty($metadata['language'])) {
            $lang = $metadata['language'];
            if (strlen($lang) <= 3) {
                $preview['language'] = \AtomFramework\Services\LanguageService::getNameFromIsoCode($lang);
            } else {
                $preview['language'] = $lang;
            }
        } elseif (!empty($metadata['languages'])) {
            // Handle languages array from Open Library
            $langs = is_array($metadata['languages']) ? $metadata['languages'] : [$metadata['languages']];
            if (!empty($langs[0])) {
                $langCode = str_replace('/languages/', '', $langs[0]);
                $preview['language'] = \AtomFramework\Services\LanguageService::getNameFromIsoCode($langCode);
            }
        }

        // Cover URL
        if (!empty($metadata['isbn_13']) || !empty($metadata['isbn_10'])) {
            $isbn = $metadata['isbn_13'] ?? $metadata['isbn_10'];
            $preview['cover_url'] = "https://covers.openlibrary.org/b/isbn/{$isbn}-M.jpg";
        } elseif (!empty($metadata['cover_url'])) {
            $preview['cover_url'] = $metadata['cover_url'];
        }

        // Open Library URL
        if (!empty($metadata['url'])) {
            $preview['openlibrary_url'] = $metadata['url'];
        }

        // Description / Summary
        if (!empty($metadata['description'])) {
            $preview['description'] = $this->truncate($metadata['description'], 500);
            $preview['description_source'] = $metadata['description_source'] ?? 'External Source';
        }

        // Notes
        if (!empty($metadata['notes'])) {
            $preview['notes'] = is_array($metadata['notes']) 
                ? implode("\n", $metadata['notes']) 
                : $metadata['notes'];
        }

        // Table of contents
        if (!empty($metadata['table_of_contents'])) {
            $preview['table_of_contents'] = is_array($metadata['table_of_contents'])
                ? implode("\n", array_map(function($t) { return $t['title'] ?? $t; }, $metadata['table_of_contents']))
                : $metadata['table_of_contents'];
        }

        // Edition
        if (!empty($metadata['edition_name'])) {
            $preview['edition'] = $metadata['edition_name'];
        }

        return $preview;
    }

    private function formatValue($value, array $mapping): string
    {
        if (isset($mapping['format'])) {
            return str_replace('{value}', (string) $value, $mapping['format']);
        }
        return is_array($value) ? implode('; ', $value) : (string) $value;
    }

    private function cleanName(string $name): string
    {
        $name = rtrim($name, '.,;:');
        $name = preg_replace('/\s*\(\d{4}-?\d{0,4}\)/', '', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function cleanTerm(string $term): string
    {
        return trim(ucfirst(strtolower(rtrim($term, '.'))));
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }
}
