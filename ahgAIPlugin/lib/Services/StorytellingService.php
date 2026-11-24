<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Storytelling engine (#155, parity with heratio#1202): a theme, hand-picked
 * records and optional background become a short public "story of the
 * collection", written by the AI gateway from real catalogue records, then
 * saved as a draft or published at /stories/<slug>. Table ahg_story is
 * Heratio's own.
 *
 * Only records an anonymous visitor may see are ever used: published, and not
 * on SearchAccessFilterService's list (classification, donor, full embargo,
 * ICIP, ODRL). If that check fails, nothing is used. A record that stops being
 * public later withdraws the whole story from the public at render, because
 * its text may quote the record; the stored text is never changed.
 */
class StorytellingService
{
    public const TABLE = 'ahg_story';
    private const STATUS_TYPE_PUBLICATION = 158;
    private const STATUS_PUBLISHED = 160;
    private const SOURCE_TYPES = ['note', 'url', 'upload', 'record'];
    private const HOURS_ERROR = 'Stories can only be written between 06:00 and 18:00 South African time, when the AI servers are on. Please try again then.';
    private const CHECK_ERROR = 'The access check on records could not be completed, so nothing was used. Try again, and report it if this continues.';

    /** @param ?\DateTimeInterface $now fixed clock for the hours guard; null = the real time */
    public function __construct(private ?\DateTimeInterface $now = null)
    {
    }

    /** @return array{ok: bool, theme: string, story: string, objects: array, error: string} */
    public function generate(string $theme, array $recordIds = [], string $context = '', int $max = 10): array
    {
        $theme = trim($theme);
        $out = ['ok' => false, 'theme' => $theme, 'story' => '', 'objects' => [], 'error' => ''];
        if (!$this->inAiHours()) {
            return ['error' => self::HOURS_ERROR] + $out;
        }

        try {
            $picked = $this->records(array_values(array_unique(array_filter(array_map('intval', $recordIds)))));
            $seen = array_column($picked, 'id');
            $matched = '' !== $theme ? array_filter($this->candidates($theme, $max), fn ($o) => !in_array($o['id'], $seen, true)) : [];
            $out['objects'] = array_slice(array_merge($picked, $matched), 0, max($max, count($picked)));
            // Background-only stories never reach a record query, so check the filter itself.
            $this->restrictedIds();
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return ['error' => self::CHECK_ERROR, 'objects' => []] + $out;
        }
        if (!$out['objects'] && '' === trim($context)) {
            return ['error' => 'No published records match that theme. Pick records or add background.'] + $out;
        }
        $out['story'] = $this->write('' !== $theme ? $theme : 'the material below', $out['objects'], $context);
        $out['ok'] = '' !== $out['story'];
        if (!$out['ok']) {
            $out['error'] = 'The AI gateway did not return a story. Try again in a moment.';
        }

        return $out;
    }

    /** Records dated today (any year), else this month. */
    public function onThisDay(int $max = 10): array
    {
        if (!$this->inAiHours()) {
            return ['ok' => false, 'theme' => '', 'story' => '', 'objects' => [], 'error' => self::HOURS_ERROR];
        }
        $month = (int) date('n');
        $day = (int) date('j');

        try {
            $objects = $this->dated($month, $day, $max);
            $scope = 'day';
            if (!$objects) {
                $objects = $this->dated($month, null, $max);
                $scope = 'month';
            }
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return ['ok' => false, 'theme' => '', 'story' => '', 'objects' => [], 'error' => self::CHECK_ERROR];
        }
        $label = 'day' === $scope ? 'On this day, the '.date('jS \o\f F').', in our collection' : 'This month, '.date('F').', in our collection';
        $out = ['ok' => false, 'theme' => $label, 'story' => '', 'objects' => $objects, 'error' => ''];
        if (!$objects) {
            return ['error' => 'No published records are dated in '.date('F').'.'] + $out;
        }
        $hint = 'day' === $scope ? 'These records are all dated the '.date('jS \o\f F').' in some year.' : 'These records are all dated in '.date('F').' in some year.';
        $out['story'] = $this->write($label, $objects, $hint);
        $out['ok'] = '' !== $out['story'];
        $out['error'] = $out['ok'] ? '' : 'The AI gateway did not return a story. Try again in a moment.';

        return $out;
    }

    /** Published records whose title matches the query, for hand-picking. */
    public function search(string $q, int $limit = 12): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        try {
            return $this->published()->where('i.title', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orderBy('i.title')->limit($limit)->get(['io.id', 'i.title'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'title' => (string) $r->title])->all();
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Create or update a story. The slug is made from the title on first save
     * and never changes, so a published link stays stable.
     *
     * @return array{ok: bool, id: int, slug: string, status: string, error: string}
     */
    public function save(array $d): array
    {
        $title = mb_substr(trim((string) ($d['title'] ?? '')) ?: trim((string) ($d['theme'] ?? '')), 0, 200);
        $body = trim((string) ($d['body'] ?? ''));
        if ('' === $title || '' === $body) {
            return ['ok' => false, 'id' => 0, 'slug' => '', 'status' => 'draft', 'error' => 'A story needs a title and text.'];
        }
        $status = 'published' === ($d['status'] ?? '') ? 'published' : 'draft';
        $id = (int) ($d['id'] ?? 0);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($d['object_ids'] ?? [])))));
        $refuse = fn (string $error) => ['ok' => false, 'id' => $id, 'slug' => '', 'status' => $status, 'error' => $error];

        // Refused, not silently dropped: the text may quote a record that has
        // stopped being public, and dropping its link would hide that from the
        // render check. The links already stored count for the same reason.
        try {
            $this->restrictedIds();
            $old = $id > 0 ? $this->find($id) : null;
            $stored = $old ? self::linkedIds($old) : [];
            if (null === $stored) {
                return $refuse(self::CHECK_ERROR);
            }
            if ($blocked = $this->blocked(array_values(array_unique(array_merge($ids, $stored))))) {
                return $refuse('Not saved. These records are not public: '.implode('; ', $blocked).'. Write the story again without them, or have the restriction lifted.');
            }
            $titles = array_map(fn ($o) => mb_substr($o['title'], 0, 200), $this->records($ids));
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return $refuse(self::CHECK_ERROR);
        }
        $sources = [];
        foreach ((array) ($d['sources'] ?? []) as $s) {
            $label = is_array($s) ? mb_substr(trim((string) ($s['label'] ?? '')), 0, 200) : '';
            if ('' === $label) {
                continue;
            }
            $type = in_array($s['type'] ?? '', self::SOURCE_TYPES, true) ? $s['type'] : 'note';
            if ('record' === $type && !in_array($label, $titles, true)) {
                continue; // A record credit must name a record still linked.
            }
            $entry = ['type' => $type, 'label' => $label];
            if ('url' === $type && preg_match('#^https?://#i', (string) ($s['url'] ?? ''))) {
                $entry['url'] = mb_substr((string) $s['url'], 0, 500);
            }
            $sources[] = $entry;
        }
        $row = ['title' => $title, 'theme' => mb_substr(trim((string) ($d['theme'] ?? '')), 0, 200), 'body' => $body,
            'object_ids' => json_encode($ids), 'sources_json' => json_encode($sources), 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];

        if ($id > 0 && $slug = DB::table(self::TABLE)->where('id', $id)->value('slug')) {
            DB::table(self::TABLE)->where('id', $id)->update($row);

            return ['ok' => true, 'id' => $id, 'slug' => (string) $slug, 'status' => $status, 'error' => ''];
        }
        $slug = $this->uniqueSlug($title);
        $id = (int) DB::table(self::TABLE)->insertGetId($row + ['slug' => $slug, 'created_at' => $row['updated_at']]);

        return ['ok' => true, 'id' => $id, 'slug' => $slug, 'status' => $status, 'error' => ''];
    }

    public function find(int $id): ?object
    {
        return DB::table(self::TABLE)->where('id', $id)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return DB::table(self::TABLE)->where('slug', $slug)->first();
    }

    public function listSaved(int $limit = 50): array
    {
        return DB::table(self::TABLE)->orderByDesc('updated_at')->limit($limit)->get(['id', 'slug', 'title', 'theme', 'status', 'updated_at'])->all();
    }

    /** A story's records that are still published, in the saved order, with slugs. */
    public function storyRecords(object $story): array
    {
        try {
            $recs = $this->records(self::linkedIds($story) ?? []);
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return [];
        }
        $slugs = $recs ? DB::table('slug')->whereIn('object_id', array_column($recs, 'id'))->pluck('slug', 'object_id')->all() : [];
        foreach ($recs as &$r) {
            $r['slug'] = $slugs[$r['id']] ?? null;
        }

        return $recs;
    }

    /**
     * Why this story may not be shown to the public; empty = it may. Staff see
     * these reasons. Any failure to check counts as a reason.
     *
     * @return string[]
     */
    public function withheldReasons(object $story): array
    {
        try {
            $this->restrictedIds();
            $ids = self::linkedIds($story);

            return null === $ids ? ['The list of linked records cannot be read.'] : $this->blocked($ids);
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling access check: '.$e->getMessage());

            return ['The access check on linked records could not be completed.'];
        }
    }

    public function storySources(object $story): array
    {
        $s = json_decode((string) $story->sources_json, true);

        return is_array($s) ? $s : [];
    }

    private function inAiHours(): bool
    {
        require_once __DIR__.'/PageClassifier.php';

        return \AhgAIPlugin\Services\PageClassifier::gpuHours($this->now);
    }

    /** The anonymous list filter (includes ICIP). Throws on failure, so callers fail closed. */
    private function restrictedIds(): array
    {
        return array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds(null, true));
    }

    /** @return ?int[] the story's record ids; null when the stored list is unreadable */
    private static function linkedIds(object $story): ?array
    {
        $raw = trim((string) ($story->object_ids ?? ''));
        if ('' === $raw) {
            return [];
        }
        $ids = json_decode($raw, true);

        return is_array($ids) ? array_values(array_unique(array_map(fn ($v) => is_scalar($v) ? (int) $v : -1, $ids))) : null;
    }

    /**
     * One reason per id that is not public now. Throws if the check fails.
     *
     * @return string[]
     */
    private function blocked(array $ids): array
    {
        $missing = $ids ? array_values(array_diff($ids, array_column($this->records($ids), 'id'))) : [];
        if (!$missing) {
            return [];
        }
        $restricted = $this->restrictedIds();
        $titles = DB::table('information_object_i18n')->whereIn('id', $missing)->where('title', '!=', '')->pluck('title', 'id')->all();

        return array_map(fn ($id) => 'record '.$id.(isset($titles[$id]) ? ' ('.trim(strip_tags((string) $titles[$id])).')' : '')
            .(in_array($id, $restricted, true) ? ' is restricted from public view' : ' is not published or no longer exists'), $missing);
    }

    private function write(string $theme, array $objects, string $context): string
    {
        $list = $objects
            ? implode("\n", array_map(fn ($o) => '- '.$o['title'].('' !== $o['scope'] ? ': '.$o['scope'] : ''), $objects))
            : '(none selected; ground the story in the background material below)';
        $prompt = "Write an engaging, warm public 'story of the collection' for a general audience, about 180 to 220 words, on the theme: \"{$theme}\". "
            .'Weave in these real records from the collection, referring to them naturally by name. Be vivid but factual: do not invent specific dates, people or events that the material does not support. '
            ."No headings, no markdown, no preamble: just the story.\n\nRECORDS:\n".$list;
        if ('' !== trim($context)) {
            $prompt .= "\n\nADDITIONAL BACKGROUND (use it to inform the story, do not contradict the records, do not copy it word for word):\n".$context;
        }
        try {
            $r = \AtomFramework\Services\AI\AiGatewayClient::fromSettings()->generate($prompt, null,
                ['temperature' => 0.7, 'max_tokens' => 700, 'think' => false, 'timeout' => 120]);
            if (empty($r['success'])) {
                error_log('[ahgAI] storytelling: '.($r['error'] ?? 'gateway failed'));

                return '';
            }

            // The model sometimes adds markdown emphasis despite the prompt; the page shows plain text.
            $text = preg_replace('#<think>.*?</think>#s', '', (string) $r['text']);
            $text = preg_replace(['/(\*\*|__)(\S(?:.*?\S)?)\1/u', '/(?<![\w*])\*(\S(?:[^*\n]*?\S)?)\*(?![\w*])/u', '/^#+\s+/mu'], ['$2', '$1', ''], $text);

            // House style: no long dashes.
            $text = preg_replace(['/(\d)[\x{2013}\x{2014}](\d)/u', '/\s*[\x{2013}\x{2014}\x{2015}]\s*/u'], ['$1-$2', ' - '], $text);

            return trim($text);
        } catch (\Throwable $e) {
            error_log('[ahgAI] storytelling: '.$e->getMessage());

            return '';
        }
    }

    /** Every record query starts here: published and not restricted. Throws if the filter fails. */
    private function published()
    {
        return DB::table('information_object as io')
            ->join('status as st', fn ($j) => $j->on('st.object_id', '=', 'io.id')->where('st.type_id', self::STATUS_TYPE_PUBLICATION))
            ->join('information_object_i18n as i', fn ($j) => $j->on('i.id', '=', 'io.id')->on('i.culture', '=', 'io.source_culture'))
            ->where('st.status_id', self::STATUS_PUBLISHED)->where('io.id', '>', 1)->where('i.title', '!=', '')
            ->whereIntegerNotInRaw('io.id', $this->restrictedIds());
    }

    /** Published records by id, in the order given. */
    private function records(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $rows = $this->published()->whereIn('io.id', $ids)->get(['io.id', 'i.title', 'i.scope_and_content'])->keyBy('id');
        $out = [];
        foreach ($ids as $id) {
            if (isset($rows[$id])) {
                $out[] = self::object($rows[$id]);
            }
        }

        return $out;
    }

    /** Published records matching the theme words, title matches first. */
    private function candidates(string $theme, int $limit): array
    {
        $tokens = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($theme)), fn ($t) => mb_strlen($t) >= 3));
        if (!$tokens) {
            return [];
        }
        $rows = $this->published()->where(function ($q) use ($tokens) {
            foreach ($tokens as $t) {
                $like = '%'.addcslashes($t, '%_\\').'%';
                $q->orWhere('i.title', 'like', $like)->orWhere('i.scope_and_content', 'like', $like);
            }
        })->limit(200)->get(['io.id', 'i.title', 'i.scope_and_content']);
        $scored = [];
        foreach ($rows as $r) {
            $title = mb_strtolower((string) $r->title);
            $scope = mb_strtolower(strip_tags((string) $r->scope_and_content));
            $s = 0;
            foreach ($tokens as $t) {
                $s += (str_contains($title, $t) ? 2 : 0) + (str_contains($scope, $t) ? 1 : 0);
            }
            if ($s > 0) {
                $scored[] = ['score' => $s] + self::object($r);
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($o) => array_diff_key($o, ['score' => 0]), array_slice($scored, 0, $limit));
    }

    /** Published records with an event dated in this month (and day). */
    private function dated(int $month, ?int $day, int $limit): array
    {
        $q = $this->published()->join('event as e', 'e.object_id', '=', 'io.id')->whereNotNull('e.start_date')
            ->whereRaw('MONTH(e.start_date) = ?', [$month]);
        $day ? $q->whereRaw('DAY(e.start_date) = ?', [$day]) : $q->whereRaw('DAY(e.start_date) BETWEEN 1 AND 31');
        $out = [];
        foreach ($q->orderByRaw('YEAR(e.start_date)')->limit($limit * 3)->get(['io.id', 'i.title', 'i.scope_and_content', 'e.start_date']) as $r) {
            if (!isset($out[$r->id]) && count($out) < $limit) {
                $o = self::object($r);
                $o['scope'] = trim('['.substr((string) $r->start_date, 0, 4).'] '.$o['scope']);
                $out[$r->id] = $o;
            }
        }

        return array_values($out);
    }

    private static function object(object $r): array
    {
        return ['id' => (int) $r->id, 'title' => trim(strip_tags((string) $r->title)),
            'scope' => self::mask(trim(mb_substr(preg_replace('/\s+/u', ' ', strip_tags((string) $r->scope_and_content)), 0, 160)))];
    }

    /**
     * Published text can still hold personal data (an email address, an ID or
     * phone number), so it is masked before it reaches the model or a story.
     * ponytail: two patterns, not the privacy plugin's scanner (plugins stay
     * separate); staff review every story before it is published.
     */
    public static function mask(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text);

        // 9+ digits (phone, ID, account numbers), unless every group is a year (1831-1833, 1914 1918).
        return preg_replace_callback('/(?<![\w])\+?\d(?:[\d \-]{5,}\d)(?![\w])/', function ($m) {
            $groups = preg_split('/[\s-]+/', ltrim($m[0], '+'));
            $years = !array_filter($groups, fn ($g) => !preg_match('/^(1[0-9]|20)\d\d$/', $g));

            return strlen(preg_replace('/\D/', '', $m[0])) >= 9 && !$years ? '[number]' : $m[0];
        }, $text);
    }

    private function uniqueSlug(string $title): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title)), '-');
        $base = mb_substr('' !== $base ? $base : 'story', 0, 80);
        $slug = $base;
        for ($n = 2; DB::table(self::TABLE)->where('slug', $slug)->exists(); ++$n) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
