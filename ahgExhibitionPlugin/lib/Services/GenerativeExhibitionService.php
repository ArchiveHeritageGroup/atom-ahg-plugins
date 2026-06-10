<?php

/**
 * GenerativeExhibitionService - PSIS Symfony port of heratio#1186 (AI Exhibition Designer).
 *
 * Given a curator-supplied THEME, retrieve candidate objects from the AtoM catalogue,
 * ask the LLM (via ahgAIPlugin \LlmService) to curate them into a draft exhibition
 * (rooms, each with a title + a short selection of objects + a one-line label), and -
 * once the curator approves - build the real ahg_exhibition_space rooms + placements
 * via the existing ExhibitionSpaceService.
 *
 * Adapted from /usr/share/nginx/heratio/packages/ahg-exhibition/src/Services/
 * GenerativeExhibitionService.php to PSIS conventions:
 *   - Illuminate Capsule DB (no Laravel facades / Carbon)
 *   - LLM via require_once + (new \LlmService())->complete($sys,$user,null,$opts)
 *   - sibling rooms built with create() + shared building_id (no addBuildingRoom helper)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class GenerativeExhibitionService
{
    /** Publication status: type 158 = "publication status", status 160 = "published". */
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLICATION_STATUS_PUBLISHED_ID = 160;

    private ExhibitionSpaceService $spaces;

    public function __construct(?ExhibitionSpaceService $spaces = null)
    {
        if ($spaces === null) {
            require_once dirname(__FILE__).'/ExhibitionSpaceService.php';
            $spaces = new ExhibitionSpaceService();
        }
        $this->spaces = $spaces;
    }

    /**
     * Curate a draft exhibition for a theme.
     *
     * @return array{ok:bool, theme:string, rooms:array, candidate_count:int, error?:string}
     */
    public function suggest(string $theme, int $maxObjects = 12, bool $publishedOnly = true): array
    {
        $theme = trim($theme);
        $out = ['ok' => false, 'theme' => $theme, 'rooms' => [], 'candidate_count' => 0];
        if ($theme === '') {
            $out['error'] = 'Please enter a theme.';

            return $out;
        }

        $candidates = $this->candidateObjects($theme, 60, $publishedOnly);
        $out['candidate_count'] = count($candidates);
        if (!$candidates) {
            $out['error'] = 'No catalogue objects matched that theme.';

            return $out;
        }

        $rooms = $this->curate($theme, $candidates, $maxObjects);
        if (!$rooms) {
            $out['error'] = 'The AI could not curate an exhibition from these objects. Try a different or broader theme.';

            return $out;
        }

        $out['rooms'] = $rooms;
        $out['ok'] = true;

        return $out;
    }

    /**
     * Turn a reviewed draft into real Exhibition Spaces. Creates one room
     * (a sibling ahg_exhibition_space sharing a building_id) per draft room and lays
     * each chosen object out along the room walls as a real placement.
     *
     * @param  array{theme?:string, rooms:array<int,array{title?:string,room?:string,objects:array<int,array{id:int}>}>}  $draft
     * @return array{ok:bool, spaces:array, rooms:int, placed:int, error?:string}
     */
    public function buildExhibition(array $draft): array
    {
        $theme = trim((string) ($draft['theme'] ?? ''));
        $rooms = array_values(array_filter((array) ($draft['rooms'] ?? []), 'is_array'));
        if (!$rooms) {
            return ['ok' => false, 'spaces' => [], 'rooms' => 0, 'placed' => 0, 'error' => 'Empty draft.'];
        }

        try {
            return DB::connection()->transaction(function () use ($theme, $rooms) {
                $built = [];
                $placed = 0;
                $buildingId = null;

                foreach ($rooms as $idx => $room) {
                    $name = trim((string) ($room['title'] ?? $room['room'] ?? ''));
                    if ($name === '') {
                        $name = $theme !== '' ? $theme.' - Room '.($idx + 1) : 'Room '.($idx + 1);
                    }

                    $spaceId = $this->spaces->create([
                        'name' => $name,
                        'space_type' => 'gallery',
                        'capacity_unit' => 'linear_wall_meters',
                        'room_w' => 10, 'room_d' => 8, 'room_h' => 4,
                        'building_id' => $buildingId,
                        'building_seq' => $idx,
                    ]);
                    $space = $this->spaces->getById($spaceId);

                    // The first room's slug becomes the shared building_id so the rest
                    // of the rooms are siblings of one building (mirrors Heratio).
                    if ($buildingId === null) {
                        $buildingId = (string) $space->slug;
                        DB::table('ahg_exhibition_space')->where('id', $spaceId)->update([
                            'building_id' => $buildingId,
                            'building_seq' => 0,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $space = $this->spaces->getById($spaceId);
                    }

                    $placed += $this->placeRoomObjects($spaceId, (array) ($room['objects'] ?? []));
                    $built[] = ['id' => (int) $space->id, 'slug' => (string) $space->slug, 'name' => (string) $space->name];
                }

                return ['ok' => true, 'spaces' => $built, 'rooms' => count($built), 'placed' => $placed];
            });
        } catch (\Throwable $e) {
            error_log('[ahgExhibitionPlugin] buildExhibition failed: '.$e->getMessage());

            return ['ok' => false, 'spaces' => [], 'rooms' => 0, 'placed' => 0, 'error' => 'Build failed: '.$e->getMessage()];
        }
    }

    /**
     * Lay a room's objects out along the walls and create a real placement for each.
     * Objects spread evenly along the back wall, wrapping to the front wall past the
     * halfway mark, so the walkthrough has something coherent to render.
     */
    private function placeRoomObjects(int $roomId, array $objects): int
    {
        $ids = [];
        foreach ($objects as $o) {
            $ioId = (int) (is_array($o) ? ($o['id'] ?? 0) : $o);
            if ($ioId > 0 && !in_array($ioId, $ids, true)) {
                $ids[] = $ioId;
            }
        }
        if (!$ids) {
            return 0;
        }

        $perWall = (int) ceil(count($ids) / 2);   // back wall first, then front wall
        $n = 0;
        foreach ($ids as $i => $ioId) {
            $onBack = $i < $perWall;
            $wallCount = $onBack ? min($perWall, count($ids)) : (count($ids) - $perWall);
            $slot = $onBack ? $i : ($i - $perWall);
            $posX = $wallCount > 0 ? ($slot + 1) / ($wallCount + 1) : 0.5;
            $posY = $onBack ? 0.12 : 0.88;
            try {
                $this->spaces->createPlacementAt($roomId, $ioId, $posX, $posY);
                $n++;
            } catch (\Throwable $e) {
                error_log('[ahgExhibitionPlugin] buildExhibition: skipped object '.$ioId.' - '.$e->getMessage());
            }
        }

        return $n;
    }

    /**
     * Theme-ranked candidate pool. Prefers objects already placed in exhibition rooms
     * (real, curated, contextual), then falls back to a catalogue keyword search.
     * Mirrors how ahgAIPlugin CollectionChatbotService::retrieve() joins
     * information_object + information_object_i18n + status to pull published rows.
     *
     * @param  bool  $publishedOnly  restrict to published records (status type 158 = published 160)
     * @return array<int,array{id:int,title:string,scope:string,year:?int}>
     */
    private function candidateObjects(string $theme, int $limit, bool $publishedOnly = true): array
    {
        $tokens = array_values(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($theme)) ?: [],
            function ($t) { return mb_strlen($t) >= 3; }
        ));

        // 1) Objects already placed in exhibition rooms.
        $q1 = DB::table('ahg_exhibition_placement as ep')
            ->join('information_object_i18n as i', function ($j) {
                $j->on('i.id', '=', 'ep.information_object_id')->where('i.culture', '=', 'en');
            })
            ->whereNotNull('ep.information_object_id')
            ->whereNotNull('i.title')->where('i.title', '!=', '');
        $this->applyPublishedFilter($q1, 'ep.information_object_id', $publishedOnly);
        $rows = $q1->select('ep.information_object_id as id', 'i.title', 'i.scope_and_content')->distinct()->get();

        // 2) Fallback to the catalogue if no room objects exist yet (fresh installs).
        if ($rows->isEmpty()) {
            $rows = $this->catalogueSearch($theme, $tokens, $publishedOnly);
        }

        // Rank by theme-keyword overlap (title weighted), so the most on-theme objects lead.
        $scored = [];
        foreach ($rows as $r) {
            $title = mb_strtolower((string) $r->title);
            $scope = mb_strtolower(strip_tags((string) ($r->scope_and_content ?? '')));
            $score = 0;
            foreach ($tokens as $t) {
                if (mb_strpos($title, $t) !== false) {
                    $score += 2;
                }
                if (mb_strpos($scope, $t) !== false) {
                    $score += 1;
                }
            }
            $scored[] = [
                'id' => (int) $r->id,
                'title' => (string) $r->title,
                'score' => $score,
                'scope' => trim(mb_substr(strip_tags((string) ($r->scope_and_content ?? '')), 0, 240)),
            ];
        }
        usort($scored, function ($a, $b) { return $b['score'] <=> $a['score']; });

        $out = [];
        foreach (array_slice($scored, 0, $limit) as $c) {
            $out[$c['id']] = ['id' => $c['id'], 'title' => $c['title'], 'scope' => $c['scope']];
        }

        return $this->enrichWithYears($out);
    }

    /**
     * Catalogue keyword search over information_object + information_object_i18n.
     * Tries MySQL FULLTEXT MATCH...AGAINST first (like CollectionChatbotService),
     * falling back to LIKE on the theme tokens when FULLTEXT is unavailable/empty.
     */
    private function catalogueSearch(string $theme, array $tokens, bool $publishedOnly)
    {
        // FULLTEXT attempt (natural language over title + scope_and_content).
        try {
            $q = DB::table('information_object_i18n as i')
                ->join('information_object as io', 'io.id', '=', 'i.id')
                ->where('i.culture', 'en')->where('io.parent_id', '!=', 1)
                ->whereNotNull('i.title')->where('i.title', '!=', '')
                ->whereRaw(
                    '(MATCH(i.title) AGAINST(? IN NATURAL LANGUAGE MODE) '
                    .'OR MATCH(i.scope_and_content) AGAINST(? IN NATURAL LANGUAGE MODE))',
                    [$theme, $theme]
                );
            $this->applyPublishedFilter($q, 'io.id', $publishedOnly);
            $rows = $q->select('io.id', 'i.title', 'i.scope_and_content')->limit(80)->get();
            if (!$rows->isEmpty()) {
                return $rows;
            }
        } catch (\Throwable $e) {
            error_log('[ahgExhibitionPlugin] candidate FULLTEXT failed, falling back to LIKE: '.$e->getMessage());
        }

        // LIKE fallback on the theme tokens.
        if (!$tokens) {
            return collect();
        }
        $q2 = DB::table('information_object_i18n as i')
            ->join('information_object as io', 'io.id', '=', 'i.id')
            ->where('i.culture', 'en')->where('io.parent_id', '!=', 1)
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $q->orWhere('i.title', 'like', '%'.$t.'%')->orWhere('i.scope_and_content', 'like', '%'.$t.'%');
                }
            })
            ->whereNotNull('i.title')->where('i.title', '!=', '');
        $this->applyPublishedFilter($q2, 'io.id', $publishedOnly);

        return $q2->select('io.id', 'i.title', 'i.scope_and_content')->limit(80)->get();
    }

    /** Restrict a query to published records (publication status type 158 = published 160). */
    private function applyPublishedFilter($query, string $idColumn, bool $publishedOnly): void
    {
        if (!$publishedOnly) {
            return;
        }
        $query->join('status as pub_st', function ($j) use ($idColumn) {
            $j->on('pub_st.object_id', '=', $idColumn)
                ->where('pub_st.type_id', '=', self::PUBLICATION_STATUS_TYPE_ID);
        })->where('pub_st.status_id', '=', self::PUBLICATION_STATUS_PUBLISHED_ID);
    }

    /**
     * Attach the earliest real calendar year to each candidate (date/era awareness).
     * Year-only AtoM dates store month/day as 00; we read the 4-digit year prefix.
     *
     * @param  array<int,array<string,mixed>>  $candidates
     * @return array<int,array<string,mixed>>
     */
    private function enrichWithYears(array $candidates): array
    {
        if (!$candidates) {
            return $candidates;
        }
        $ids = array_keys($candidates);
        $years = DB::table('event')
            ->whereIn('object_id', $ids)
            ->whereNotNull('start_date')->where('start_date', '!=', '0000-00-00')
            ->selectRaw('object_id, MIN(start_date) as first_date')
            ->groupBy('object_id')->pluck('first_date', 'object_id');
        foreach ($candidates as $id => &$c) {
            $year = isset($years[$id]) ? (int) substr((string) $years[$id], 0, 4) : 0;
            $c['year'] = $year > 0 ? $year : null;
        }
        unset($c);

        return $candidates;
    }

    /**
     * Ask the AI to curate candidates into grouped rooms with labels. Candidates are
     * presented with short 1-based NUMBERS (not their 6-digit ids) - the model copies
     * small numbers reliably, whereas it tends to half-invent long ids. The returned
     * small numbers are mapped back to real information_object ids here. Returns [] on
     * failure.
     *
     * @param  array<int,array<string,mixed>>  $candidates
     * @return array<int,array{title:string,label:string,objects:array}>
     */
    private function curate(string $theme, array $candidates, int $maxObjects): array
    {
        $ordered = array_values(array_slice($candidates, 0, 50, true));   // position -> candidate
        $lines = [];
        $anyYear = false;
        foreach ($ordered as $i => $c) {
            $year = $c['year'] ?? null;
            if ($year) {
                $anyYear = true;
            }
            $lines[] = ($i + 1).'. '.$c['title'].($year ? ' ('.$year.')' : '');
        }
        $list = implode("\n", $lines);

        $eraHint = $anyYear
            ? 'Some objects show a year in parentheses. Use these dates: where a clear chronology or era emerges, group rooms by period and order them earliest-to-latest, and let the room titles reflect the era. '
            : '';

        $system = 'You are a museum curator designing an exhibition. Return ONLY valid JSON, no preamble, '
            .'no markdown fences. Refer to each object ONLY by its number from the candidate list - never '
            .'invent objects.';

        $user = "Design an exhibition on the theme: \"{$theme}\".\n"
            ."From the numbered candidate objects below, select the most relevant (up to {$maxObjects}) and "
            .'arrange them into 2 to 4 themed rooms. '
            .$eraHint
            ."For each chosen object write a one-line label that explains WHY it fits the theme or what it "
            ."contributes - do NOT just repeat the object's title.\n"
            ."Return JSON in exactly this shape:\n"
            ."[{\"room\":\"Room title\",\"objects\":[{\"n\":1,\"label\":\"one line\"}]}]\n\n"
            .'CANDIDATES:'."\n".$list;

        try {
            require_once \sfConfig::get('sf_plugins_dir').'/ahgAIPlugin/lib/Services/LlmService.php';
            $result = (new \LlmService())->complete($system, $user, null, [
                'max_tokens' => 900,
                'temperature' => 0.5,
                'purpose' => 'exhibition_curation',
                'data_scope' => 'internal',
            ]);
        } catch (\Throwable $e) {
            error_log('[ahgExhibitionPlugin] generative curate LLM failed: '.$e->getMessage());

            return [];
        }

        if (empty($result['success']) || empty($result['text'])) {
            error_log('[ahgExhibitionPlugin] generative curate: LLM returned no text ('.($result['error'] ?? 'unknown').')');

            return [];
        }

        $json = $this->extractJson((string) $result['text']);
        $parsed = $json !== null ? json_decode($json, true) : null;
        if (!is_array($parsed)) {
            return [];
        }

        $rooms = [];
        foreach ($parsed as $room) {
            if (!is_array($room)) {
                continue;
            }
            $objs = [];
            foreach (($room['objects'] ?? []) as $o) {
                $n = (int) (is_array($o) ? ($o['n'] ?? $o['id'] ?? 0) : $o);   // accept "n" (preferred) or stray "id" = the number
                $c = ($n >= 1 && isset($ordered[$n - 1])) ? $ordered[$n - 1] : null;
                if ($c) {
                    $objs[] = [
                        'id' => $c['id'],
                        'title' => $c['title'],
                        'label' => trim((string) (is_array($o) ? ($o['label'] ?? '') : '')),
                        'year' => $c['year'] ?? null,
                        'thumb_url' => $this->spaces->thumbnailUrl((int) $c['id']),
                    ];
                }
            }
            if ($objs) {
                $title = trim((string) ($room['room'] ?? $room['title'] ?? 'Room')) ?: 'Room';
                $rooms[] = ['title' => $title, 'label' => trim((string) ($room['label'] ?? '')), 'objects' => $objs];
            }
        }

        return $rooms;
    }

    /** Pull the first JSON array out of a model response. */
    private function extractJson(string $resp): ?string
    {
        $resp = trim($resp);
        $start = strpos($resp, '[');
        $end = strrpos($resp, ']');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($resp, $start, $end - $start + 1);
        }

        return null;
    }
}
