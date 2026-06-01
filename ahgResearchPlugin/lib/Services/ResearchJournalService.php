<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ResearchJournalService - journal BUILDER for the research portal (#115).
 *
 * PSIS-parity port of the Heratio AhgResearch\Services\ResearchJournalService.
 * Backs two modes against the same set of tables:
 *   - publication: an institutional scholarly journal
 *                  (journal -> issues -> articles -> table of contents -> publish).
 *   - manuscript:  a single article workspace formatted toward an external
 *                  target journal (reference style, abstract, keywords; the
 *                  target rules come from the #114 target-journal directory
 *                  -- table `research_target_journal` -- WHEN PRESENT).
 *
 * NOTE: distinct from the legacy researcher logbook (`JournalService` +
 *       `research_journal_entry`). Tables here are research_journal /
 *       research_journal_issue / research_journal_article.
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
class ResearchJournalService
{
    public const KIND_PUBLICATION = 'publication';
    public const KIND_MANUSCRIPT  = 'manuscript';

    /** Reference styles offered by the manuscript builder. */
    public const REFERENCE_STYLES = ['APA', 'Harvard', 'Vancouver', 'Chicago', 'MLA', 'IEEE'];

    // ── Journals ──────────────────────────────────────────────────────────

    public function listJournals(?string $kind = null): array
    {
        $q = DB::table('research_journal')->orderByDesc('updated_at');
        if ($kind !== null) {
            $q->where('kind', $kind);
        }

        return array_map(fn ($j) => (array) $j, $q->get()->all());
    }

    public function getJournal(int $id): ?array
    {
        $row = DB::table('research_journal')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createJournal(array $data): int
    {
        return (int) DB::table('research_journal')->insertGetId($this->journalPayload($data, true));
    }

    public function updateJournal(int $id, array $data): bool
    {
        return DB::table('research_journal')->where('id', $id)->update($this->journalPayload($data, false)) >= 0;
    }

    public function deleteJournal(int $id): void
    {
        DB::table('research_journal_article')->where('journal_id', $id)->delete();
        DB::table('research_journal_issue')->where('journal_id', $id)->delete();
        DB::table('research_journal')->where('id', $id)->delete();
    }

    public function setJournalStatus(int $id, string $status): bool
    {
        return DB::table('research_journal')->where('id', $id)
            ->update(['status' => $status, 'updated_at' => $this->now()]) > 0;
    }

    private function journalPayload(array $d, bool $isNew): array
    {
        $kind = in_array(($d['kind'] ?? null), [self::KIND_PUBLICATION, self::KIND_MANUSCRIPT], true)
            ? $d['kind'] : self::KIND_PUBLICATION;

        $p = [
            'kind'              => $kind,
            'title'             => trim((string) ($d['title'] ?? 'Untitled journal')),
            'subtitle'          => $d['subtitle'] ?? null,
            'issn'              => $d['issn'] ?? null,
            'eissn'             => $d['eissn'] ?? null,
            'publisher'         => $d['publisher'] ?? null,
            'description'       => $d['description'] ?? null,
            'aims_scope'        => $d['aims_scope'] ?? null,
            'editor_name'       => $d['editor_name'] ?? null,
            'editor_email'      => $d['editor_email'] ?? null,
            'target_journal_id' => $d['target_journal_id'] ?? null,
            'doi'               => $d['doi'] ?? null,
            'updated_at'        => $this->now(),
        ];
        if ($isNew) {
            $p['researcher_id'] = $d['researcher_id'] ?? null;
            $p['status']        = $d['status'] ?? 'draft';
            $p['created_at']    = $this->now();
        } elseif (isset($d['status'])) {
            $p['status'] = $d['status'];
        }

        return $p;
    }

    // ── Issues ────────────────────────────────────────────────────────────

    public function listIssues(int $journalId): array
    {
        $rows = DB::table('research_journal_issue')->where('journal_id', $journalId)
            ->orderBy('sort_order')->orderByDesc('issue_date')->get();

        return array_map(fn ($i) => (array) $i, $rows->all());
    }

    public function getIssue(int $id): ?array
    {
        $row = DB::table('research_journal_issue')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createIssue(int $journalId, array $data): int
    {
        return (int) DB::table('research_journal_issue')->insertGetId([
            'journal_id'  => $journalId,
            'volume'      => $data['volume'] ?? null,
            'number'      => $data['number'] ?? null,
            'title'       => $data['title'] ?? null,
            'issue_date'  => $data['issue_date'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'draft',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'created_at'  => $this->now(),
            'updated_at'  => $this->now(),
        ]);
    }

    public function updateIssue(int $id, array $data): bool
    {
        return DB::table('research_journal_issue')->where('id', $id)->update([
            'volume'      => $data['volume'] ?? null,
            'number'      => $data['number'] ?? null,
            'title'       => $data['title'] ?? null,
            'issue_date'  => $data['issue_date'] ?? null,
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'draft',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'updated_at'  => $this->now(),
        ]) >= 0;
    }

    public function deleteIssue(int $id): void
    {
        // Unassign articles rather than delete them.
        DB::table('research_journal_article')->where('issue_id', $id)->update(['issue_id' => null]);
        DB::table('research_journal_issue')->where('id', $id)->delete();
    }

    // ── Articles ──────────────────────────────────────────────────────────

    public function listArticles(int $journalId, ?int $issueId = null): array
    {
        $q = DB::table('research_journal_article')->where('journal_id', $journalId)
            ->orderBy('sort_order')->orderBy('title');
        if ($issueId !== null) {
            $q->where('issue_id', $issueId);
        }

        return array_map(fn ($a) => (array) $a, $q->get()->all());
    }

    public function getArticle(int $id): ?array
    {
        $row = DB::table('research_journal_article')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createArticle(int $journalId, array $data): int
    {
        $payload = $this->articlePayload($data);
        $payload['journal_id'] = $journalId;
        $payload['created_at'] = $this->now();

        return (int) DB::table('research_journal_article')->insertGetId($payload);
    }

    public function updateArticle(int $id, array $data): bool
    {
        return DB::table('research_journal_article')->where('id', $id)->update($this->articlePayload($data)) >= 0;
    }

    public function deleteArticle(int $id): void
    {
        DB::table('research_journal_article')->where('id', $id)->delete();
    }

    private function articlePayload(array $d): array
    {
        $markdown = (string) ($d['body_markdown'] ?? '');

        return [
            'issue_id'          => $d['issue_id'] ?? null,
            'title'             => trim((string) ($d['title'] ?? 'Untitled article')),
            'authors'           => $d['authors'] ?? null,
            'abstract'          => $d['abstract'] ?? null,
            'keywords'          => $d['keywords'] ?? null,
            'body_markdown'     => $markdown,
            'body_html'         => $this->renderMarkdown($markdown),
            'reference_style'   => $d['reference_style'] ?? null,
            'target_journal_id' => $d['target_journal_id'] ?? null,
            'doi'               => $d['doi'] ?? null,
            'word_count'        => $this->wordCount($markdown),
            'status'            => $d['status'] ?? 'draft',
            'sort_order'        => (int) ($d['sort_order'] ?? 0),
            'updated_at'        => $this->now(),
        ];
    }

    // ── Table of contents ─────────────────────────────────────────────────

    /**
     * TOC for a published-style journal: issues (newest first) each with their
     * ordered articles, followed by any unassigned (manuscript / unplaced)
     * articles. Used by the journal show / publish view.
     */
    public function tableOfContents(int $journalId): array
    {
        $toc = [];
        foreach ($this->listIssues($journalId) as $issue) {
            $issue['articles'] = $this->listArticles($journalId, (int) $issue['id']);
            $toc[] = $issue;
        }

        $unassigned = array_map(
            fn ($a) => (array) $a,
            DB::table('research_journal_article')
                ->where('journal_id', $journalId)->whereNull('issue_id')
                ->orderBy('sort_order')->orderBy('title')->get()->all()
        );
        if ($unassigned) {
            $toc[] = [
                'id'       => null,
                'title'    => 'Unassigned',
                'volume'   => null,
                'number'   => null,
                'status'   => 'draft',
                'articles' => $unassigned,
            ];
        }

        return $toc;
    }

    // ── Manuscript formatting / validation (target journal = #114) ─────────

    /**
     * Look up the external target-journal rules from the #114 directory when it
     * exists. Returns null when the table is absent (feature not yet shipped)
     * or no id supplied, so callers degrade gracefully.
     */
    public function targetJournal(?int $targetJournalId): ?array
    {
        if (! $targetJournalId || ! $this->hasTable('research_target_journal')) {
            return null;
        }
        $row = DB::table('research_target_journal')->where('id', $targetJournalId)->first();

        return $row ? (array) $row : null;
    }

    /** Options from the #114 target-journal directory when it exists, else []. */
    public function targetJournalOptions(): array
    {
        if (! $this->hasTable('research_target_journal')) {
            return [];
        }

        return array_map(
            fn ($r) => (array) $r,
            DB::table('research_target_journal')->orderBy('title')->get(['id', 'title'])->all()
        );
    }

    /**
     * Validate a manuscript article against its target journal's rules (where
     * available) and basic completeness. Returns a list of human-readable
     * problems; empty = ready to assemble.
     */
    public function validateManuscript(array $article): array
    {
        $problems = [];
        if (trim((string) ($article['title'] ?? '')) === '') {
            $problems[] = 'Title is required.';
        }
        if (trim((string) ($article['abstract'] ?? '')) === '') {
            $problems[] = 'An abstract is required for submission.';
        }
        if (trim((string) ($article['authors'] ?? '')) === '') {
            $problems[] = 'At least one author must be listed.';
        }
        if ((int) ($article['word_count'] ?? 0) < 1) {
            $problems[] = 'The manuscript body is empty.';
        }

        $target = $this->targetJournal(isset($article['target_journal_id']) ? (int) $article['target_journal_id'] : null);
        if ($target) {
            if (! empty($target['reference_style']) && ! empty($article['reference_style'])
                && strcasecmp((string) $target['reference_style'], (string) $article['reference_style']) !== 0) {
                $problems[] = "Reference style should be {$target['reference_style']} for this journal.";
            }
            if (! empty($target['max_words']) && (int) ($article['word_count'] ?? 0) > (int) $target['max_words']) {
                $problems[] = "Manuscript exceeds the journal's {$target['max_words']}-word limit (currently " . (int) $article['word_count'] . ').';
            }
        }

        return $problems;
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * Render Markdown to safe HTML. Uses base AtoM's bundled Parsedown when
     * available; otherwise degrades to a minimal paragraph/escaped fallback so
     * the builder still works on hosts without it.
     */
    public function renderMarkdown(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }

        if (! class_exists('Parsedown')) {
            $parsedownFile = $this->config('sf_root_dir') . '/vendor/parsedown/Parsedown.php';
            if (is_file($parsedownFile)) {
                require_once $parsedownFile;
            }
        }
        if (class_exists('Parsedown')) {
            $pd = new \Parsedown();
            if (method_exists($pd, 'setSafeMode')) {
                $pd->setSafeMode(true);
            }

            return (string) $pd->text($markdown);
        }

        // Fallback: escape + paragraph-wrap on blank lines, preserve line breaks.
        $escaped = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
        $blocks  = preg_split('/\n{2,}/', trim($escaped)) ?: [];
        $html    = '';
        foreach ($blocks as $block) {
            $html .= '<p>' . nl2br($block) . '</p>';
        }

        return $html;
    }

    public function wordCount(string $markdown): int
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($this->renderMarkdown($markdown))));

        return $text === '' ? 0 : str_word_count($text);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** Schema-presence check that degrades gracefully if the connection lacks a schema builder. */
    private function hasTable(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            try {
                DB::table($table)->limit(1)->exists();

                return true;
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    private function config(string $key): string
    {
        if (class_exists('sfConfig')) {
            return (string) \sfConfig::get($key, '');
        }

        return '';
    }
}
