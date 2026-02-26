<?php

namespace AhgDiscovery\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Step 4: Result Enricher
 *
 * Batch-fetches metadata for all results: titles, scope & content,
 * NER entities, dates, levels, extent, creators, thumbnails.
 *
 * NO AI CALLS. Just database reads on existing data.
 */
class ResultEnricher
{
    private string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Enrich a list of scored results with metadata.
     *
     * @param array $results  [{object_id, score, match_reasons, ...}, ...]
     * @param int   $maxItems Max items to enrich (performance cap)
     * @return array Enriched results
     */
    public function enrich(array $results, int $maxItems = 100): array
    {
        $results = array_slice($results, 0, $maxItems);
        $ids = array_column($results, 'object_id');

        if (empty($ids)) {
            return [];
        }

        // Batch fetch all metadata
        $titles      = $this->fetchTitles($ids);
        $entities    = $this->fetchEntities($ids);
        $metadata    = $this->fetchMetadata($ids);
        $thumbnails  = $this->fetchThumbnails($ids);
        $slugs       = $this->fetchSlugs($ids);

        // Attach metadata to each result
        foreach ($results as &$result) {
            $id = $result['object_id'];

            // Title and scope_and_content
            if (isset($titles[$id])) {
                $result['title']             = $titles[$id]['title'];
                $result['scope_and_content'] = $this->trimToSentences($titles[$id]['scope_and_content'], 2);
            } else {
                $result['title']             = 'Untitled';
                $result['scope_and_content'] = '';
            }

            // NER entities
            $result['entities'] = $entities[$id] ?? [];

            // Metadata: level, dates, extent, creator
            if (isset($metadata[$id])) {
                $result['level_of_description'] = $metadata[$id]['level'] ?? '';
                $result['date_range']           = $metadata[$id]['dates'] ?? '';
                $result['extent']               = $metadata[$id]['extent'] ?? '';
                $result['creator']              = $metadata[$id]['creator'] ?? '';
                $result['repository']           = $metadata[$id]['repository'] ?? '';
            } else {
                $result['level_of_description'] = '';
                $result['date_range']           = '';
                $result['extent']               = '';
                $result['creator']              = '';
                $result['repository']           = '';
            }

            // Thumbnail
            $result['thumbnail_url'] = $thumbnails[$id] ?? null;

            // Slug
            if (empty($result['slug'])) {
                $result['slug'] = $slugs[$id] ?? '';
            }
        }

        return $results;
    }

    /**
     * Batch fetch titles and scope_and_content.
     */
    private function fetchTitles(array $ids): array
    {
        $rows = DB::table('information_object_i18n')
            ->select('id', 'title', 'scope_and_content')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->id] = [
                'title'             => $row->title ?: 'Untitled',
                'scope_and_content' => $row->scope_and_content ?: '',
            ];
        }

        return $map;
    }

    /**
     * Batch fetch NER entities.
     */
    private function fetchEntities(array $ids): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_ner_entity'");
            if (empty($exists)) {
                return [];
            }

            $rows = DB::table('ahg_ner_entity')
                ->select('object_id', 'entity_type', 'entity_value')
                ->whereIn('object_id', $ids)
                ->whereIn('status', ['approved', 'pending'])
                ->orderBy('object_id')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $id = (int)$row->object_id;
                if (!isset($map[$id])) {
                    $map[$id] = [];
                }
                // Limit entities per record to prevent massive lists
                if (count($map[$id]) < 10) {
                    $map[$id][] = [
                        'type'  => $row->entity_type,
                        'value' => $row->entity_value,
                    ];
                }
            }

            return $map;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Batch fetch metadata: level of description, dates, extent, creator, repository.
     */
    private function fetchMetadata(array $ids): array
    {
        $map = [];

        // Level of description
        $levels = DB::table('information_object as io')
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('io.level_of_description_id', '=', 'ti.id')
                     ->where('ti.culture', '=', $this->culture);
            })
            ->select('io.id', 'ti.name as level')
            ->whereIn('io.id', $ids)
            ->get();

        foreach ($levels as $row) {
            $id = (int)$row->id;
            $map[$id] = ['level' => $row->level ?: ''];
        }

        // Extent and medium
        $extents = DB::table('information_object_i18n')
            ->select('id', 'extent_and_medium')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->get();

        foreach ($extents as $row) {
            $id = (int)$row->id;
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            $map[$id]['extent'] = $row->extent_and_medium ?: '';
        }

        // Dates from event table
        $dates = DB::table('event')
            ->select('object_id', 'start_date', 'end_date')
            ->whereIn('object_id', $ids)
            ->where('type_id', \QubitTerm::CREATION_ID)
            ->get();

        foreach ($dates as $row) {
            $id = (int)$row->object_id;
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            $start = $row->start_date ? substr($row->start_date, 0, 4) : '';
            $end = $row->end_date ? substr($row->end_date, 0, 4) : '';
            if ($start && $end && $start !== $end) {
                $map[$id]['dates'] = $start . '–' . $end;
            } elseif ($start) {
                $map[$id]['dates'] = $start;
            }
        }

        // Date display string (from event_i18n)
        $dateDisplays = DB::table('event')
            ->join('event_i18n', 'event.id', '=', 'event_i18n.id')
            ->select('event.object_id', 'event_i18n.date as date_display')
            ->whereIn('event.object_id', $ids)
            ->where('event.type_id', \QubitTerm::CREATION_ID)
            ->where('event_i18n.culture', $this->culture)
            ->get();

        foreach ($dateDisplays as $row) {
            $id = (int)$row->object_id;
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            if (!empty($row->date_display) && empty($map[$id]['dates'])) {
                $map[$id]['dates'] = $row->date_display;
            }
        }

        // Creator (from event + actor)
        $creators = DB::table('event')
            ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->select('event.object_id', 'actor_i18n.authorized_form_of_name as creator')
            ->whereIn('event.object_id', $ids)
            ->where('event.type_id', \QubitTerm::CREATION_ID)
            ->where('actor_i18n.culture', $this->culture)
            ->get();

        foreach ($creators as $row) {
            $id = (int)$row->object_id;
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            $map[$id]['creator'] = $row->creator ?: '';
        }

        // Repository (name lives in actor_i18n since repository extends actor)
        $repos = DB::table('information_object as io')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('io.repository_id', '=', 'ai.id')
                     ->where('ai.culture', '=', $this->culture);
            })
            ->select('io.id', 'ai.authorized_form_of_name as repository')
            ->whereIn('io.id', $ids)
            ->whereNotNull('io.repository_id')
            ->get();

        foreach ($repos as $row) {
            $id = (int)$row->id;
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            $map[$id]['repository'] = $row->repository ?: '';
        }

        return $map;
    }

    /**
     * Batch fetch thumbnails.
     */
    private function fetchThumbnails(array $ids): array
    {
        $rows = DB::table('digital_object')
            ->select('object_id', 'path', 'name')
            ->whereIn('object_id', $ids)
            ->where('usage_id', \QubitTerm::THUMBNAIL_ID)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $id = (int)$row->object_id;
            if (!isset($map[$id])) {
                $map[$id] = '/uploads/' . ltrim($row->path, '/') . $row->name;
            }
        }

        return $map;
    }

    /**
     * Batch fetch slugs.
     */
    private function fetchSlugs(array $ids): array
    {
        $rows = DB::table('slug')
            ->select('object_id', 'slug')
            ->whereIn('object_id', $ids)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row->object_id] = $row->slug;
        }

        return $map;
    }

    /**
     * Trim text to first N sentences.
     */
    private function trimToSentences(string $text, int $count): string
    {
        if (empty($text)) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim($text);

        // Split by sentence-ending punctuation
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, $count + 1);

        if (count($sentences) <= $count) {
            return $text;
        }

        return implode(' ', array_slice($sentences, 0, $count));
    }
}
