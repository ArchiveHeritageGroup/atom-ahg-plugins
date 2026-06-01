<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * LectureService - Lecture Builder for the research portal (#116).
 *
 * PSIS-parity port of Heratio AhgResearch\Services\ResearchLectureService.
 *
 * One model, three uses (the `type` column):
 *   - curriculum: teaching content that feeds the training curriculum.
 *   - talk:       a public lecture/seminar record (speaker, schedule, recording).
 *   - standalone: a reusable authored lecture (ordered sections + media).
 *
 * A lecture has ordered content sections and a list of resources (readings,
 * slides, links, files).
 *
 * @package ahgResearchPlugin
 * @version 1.0.0
 */
class LectureService
{
    public const TYPES          = ['curriculum', 'talk', 'standalone'];
    public const STATUSES       = ['draft', 'scheduled', 'delivered', 'published', 'archived'];
    public const MEDIA_TYPES    = ['image', 'video', 'audio', 'embed'];
    public const RESOURCE_TYPES = ['reading', 'slides', 'video', 'link', 'file'];

    // ── Lectures ─────────────────────────────────────────────────────────

    public function listLectures(?string $type = null): array
    {
        $q = DB::table('research_lecture')
            ->orderByDesc('scheduled_at')
            ->orderByDesc('updated_at');
        if ($type !== null) {
            $q->where('type', $type);
        }

        return $q->get()->map(function ($l) {
            return (array) $l;
        })->all();
    }

    public function getLecture(int $id): ?array
    {
        $row = DB::table('research_lecture')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createLecture(array $data): int
    {
        $p = $this->lecturePayload($data, true);

        return (int) DB::table('research_lecture')->insertGetId($p);
    }

    public function updateLecture(int $id, array $data): bool
    {
        return DB::table('research_lecture')->where('id', $id)
            ->update($this->lecturePayload($data, false)) >= 0;
    }

    public function deleteLecture(int $id): void
    {
        DB::table('research_lecture_section')->where('lecture_id', $id)->delete();
        DB::table('research_lecture_resource')->where('lecture_id', $id)->delete();
        DB::table('research_lecture')->where('id', $id)->delete();
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        return DB::table('research_lecture')->where('id', $id)
            ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function publish(int $id, bool $publish = true): bool
    {
        return $this->setStatus($id, $publish ? 'published' : 'draft');
    }

    private function lecturePayload(array $d, bool $isNew): array
    {
        $now = date('Y-m-d H:i:s');
        $p = [
            'type'                => in_array(($d['type'] ?? null), self::TYPES, true) ? $d['type'] : 'standalone',
            'title'               => trim((string) ($d['title'] ?? 'Untitled lecture')),
            'subtitle'            => $d['subtitle'] ?? null,
            'summary'             => $d['summary'] ?? null,
            'speaker_name'        => $d['speaker_name'] ?? null,
            'speaker_affiliation' => $d['speaker_affiliation'] ?? null,
            'scheduled_at'        => !empty($d['scheduled_at']) ? $d['scheduled_at'] : null,
            'location'            => $d['location'] ?? null,
            'duration_minutes'    => (isset($d['duration_minutes']) && $d['duration_minutes'] !== '') ? (int) $d['duration_minutes'] : null,
            'recording_url'       => $d['recording_url'] ?? null,
            'slides_url'          => $d['slides_url'] ?? null,
            'curriculum_ref'      => $d['curriculum_ref'] ?? null,
            'updated_at'          => $now,
        ];
        if ($isNew) {
            $p['researcher_id'] = $d['researcher_id'] ?? null;
            $p['status']        = in_array(($d['status'] ?? null), self::STATUSES, true) ? $d['status'] : 'draft';
            $p['created_at']    = $now;
        } elseif (isset($d['status']) && in_array($d['status'], self::STATUSES, true)) {
            $p['status'] = $d['status'];
        }

        return $p;
    }

    // ── Sections ──────────────────────────────────────────────────────────

    public function listSections(int $lectureId): array
    {
        return DB::table('research_lecture_section')->where('lecture_id', $lectureId)
            ->orderBy('sort_order')->orderBy('id')
            ->get()->map(function ($s) {
                return (array) $s;
            })->all();
    }

    public function getSection(int $id): ?array
    {
        $row = DB::table('research_lecture_section')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    public function createSection(int $lectureId, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $markdown = (string) ($data['body_markdown'] ?? '');

        return (int) DB::table('research_lecture_section')->insertGetId([
            'lecture_id'    => $lectureId,
            'heading'       => $data['heading'] ?? null,
            'body_markdown' => $markdown,
            'body_html'     => $this->renderMarkdown($markdown),
            'media_url'     => $data['media_url'] ?? null,
            'media_type'    => in_array(($data['media_type'] ?? null), self::MEDIA_TYPES, true) ? $data['media_type'] : null,
            'sort_order'    => (int) ($data['sort_order'] ?? $this->nextSectionOrder($lectureId)),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function updateSection(int $id, array $data): bool
    {
        $markdown = (string) ($data['body_markdown'] ?? '');

        return DB::table('research_lecture_section')->where('id', $id)->update([
            'heading'       => $data['heading'] ?? null,
            'body_markdown' => $markdown,
            'body_html'     => $this->renderMarkdown($markdown),
            'media_url'     => $data['media_url'] ?? null,
            'media_type'    => in_array(($data['media_type'] ?? null), self::MEDIA_TYPES, true) ? $data['media_type'] : null,
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]) >= 0;
    }

    public function deleteSection(int $id): void
    {
        DB::table('research_lecture_section')->where('id', $id)->delete();
    }

    private function nextSectionOrder(int $lectureId): int
    {
        return (int) DB::table('research_lecture_section')->where('lecture_id', $lectureId)->max('sort_order') + 1;
    }

    // ── Resources ───────────────────────────────────────────────────────────

    public function listResources(int $lectureId): array
    {
        return DB::table('research_lecture_resource')->where('lecture_id', $lectureId)
            ->orderBy('sort_order')->orderBy('id')
            ->get()->map(function ($r) {
                return (array) $r;
            })->all();
    }

    public function createResource(int $lectureId, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return (int) DB::table('research_lecture_resource')->insertGetId([
            'lecture_id'    => $lectureId,
            'label'         => trim((string) ($data['label'] ?? 'Resource')),
            'url'           => $data['url'] ?? null,
            'resource_type' => in_array(($data['resource_type'] ?? null), self::RESOURCE_TYPES, true) ? $data['resource_type'] : 'link',
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function deleteResource(int $id): void
    {
        DB::table('research_lecture_resource')->where('id', $id)->delete();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * Render a (safe) subset of Markdown to HTML.
     *
     * Heratio uses League\CommonMark; that library is not available in the
     * AtoM/Symfony vendor tree, so this is a self-contained, dependency-free
     * renderer covering headings, bold/italic/code, links, lists, blockquotes
     * and paragraphs. All HTML in the source is escaped first (html_input=strip
     * equivalent), so output is XSS-safe.
     */
    public function renderMarkdown(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }

        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);

        $html = '';
        $listOpen = false;
        $paraBuffer = [];

        $flushPara = function () use (&$paraBuffer, &$html) {
            if (!empty($paraBuffer)) {
                $text = $this->inlineMarkdown(implode(' ', $paraBuffer));
                $html .= '<p>' . $text . "</p>\n";
                $paraBuffer = [];
            }
        };
        $closeList = function () use (&$listOpen, &$html) {
            if ($listOpen) {
                $html .= "</ul>\n";
                $listOpen = false;
            }
        };

        foreach ($lines as $line) {
            $trim = trim($line);

            if ($trim === '') {
                $flushPara();
                $closeList();
                continue;
            }

            // Headings (# .. ######)
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                $flushPara();
                $closeList();
                $level = strlen($m[1]);
                $html .= '<h' . $level . '>' . $this->inlineMarkdown($m[2]) . '</h' . $level . ">\n";
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s?(.*)$/', $trim, $m)) {
                $flushPara();
                $closeList();
                $html .= '<blockquote>' . $this->inlineMarkdown($m[1]) . "</blockquote>\n";
                continue;
            }

            // Unordered list item
            if (preg_match('/^[-*+]\s+(.*)$/', $trim, $m)) {
                $flushPara();
                if (!$listOpen) {
                    $html .= "<ul>\n";
                    $listOpen = true;
                }
                $html .= '<li>' . $this->inlineMarkdown($m[1]) . "</li>\n";
                continue;
            }

            // Regular paragraph text
            $closeList();
            $paraBuffer[] = $trim;
        }

        $flushPara();
        $closeList();

        return trim($html);
    }

    /**
     * Inline-level Markdown: escape HTML first, then apply code/bold/italic/links.
     */
    private function inlineMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Inline code `code`
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) {
            return '<code>' . $m[1] . '</code>';
        }, $text);

        // Links [label](http(s)://url) — only safe http(s) schemes
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/', function ($m) {
            return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        }, $text);

        // Bold **text**
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        // Italic *text*
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);

        return $text;
    }
}
