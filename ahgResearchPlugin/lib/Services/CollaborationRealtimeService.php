<?php

/**
 * CollaborationRealtimeService - project-scoped presence + polling-based comment thread.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §2.3
 *
 * Updates every 3s via polling (no WebSocket broker on the AHG host).
 *
 * Note: the spec called for a new `research_evidence_comment` table; the
 * existing `research_comment` table already has a polymorphic
 * (entity_type, entity_id) shape with parent/resolve fields, so we reuse it
 * with entity_type='project' / 'collection_item' / 'collection'.
 */

use Illuminate\Database\Capsule\Manager as DB;

class CollaborationRealtimeService
{
    /** Seconds of inactivity before a presence row is treated as stale */
    public const PRESENCE_TIMEOUT_SECONDS = 90;

    /** Distinct colour palette assigned to live collaborators */
    public const PRESENCE_COLORS = ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#20c997', '#d63384', '#0dcaf0'];

    /**
     * Heartbeat: register/refresh the researcher's presence in the project.
     */
    public function join(int $projectId, int $researcherId, ?string $cursorTarget = null): array
    {
        $existing = DB::table('research_collaboration_presence')
            ->where('project_id', $projectId)
            ->where('researcher_id', $researcherId)
            ->first();

        $color = $existing->user_color ?? $this->pickColor($projectId);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            DB::table('research_collaboration_presence')
                ->where('id', $existing->id)
                ->update([
                    'cursor_target' => $cursorTarget,
                    'user_color'    => $color,
                    'last_seen_at'  => $now,
                ]);
            $presenceId = (int) $existing->id;
        } else {
            $presenceId = DB::table('research_collaboration_presence')->insertGetId([
                'project_id'    => $projectId,
                'researcher_id' => $researcherId,
                'cursor_target' => $cursorTarget,
                'user_color'    => $color,
                'last_seen_at'  => $now,
            ]);
        }

        return [
            'presence_id' => $presenceId,
            'color'       => $color,
        ];
    }

    /**
     * Poll: return current presence list + new comments since the cursor id.
     */
    public function poll(int $projectId, int $sinceCommentId = 0): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - self::PRESENCE_TIMEOUT_SECONDS);

        $presence = DB::table('research_collaboration_presence as p')
            ->leftJoin('research_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->where('p.project_id', $projectId)
            ->where('p.last_seen_at', '>=', $cutoff)
            ->select(
                'p.researcher_id',
                'p.cursor_target',
                'p.user_color',
                'p.last_seen_at',
                'r.first_name',
                'r.last_name'
            )
            ->orderBy('p.last_seen_at', 'desc')
            ->get()
            ->map(fn ($p) => [
                'researcher_id' => (int) $p->researcher_id,
                'name'          => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: '#' . $p->researcher_id,
                'color'         => $p->user_color ?? '#6c757d',
                'cursor'        => $p->cursor_target,
                'last_seen'     => $p->last_seen_at,
            ])
            ->all();

        $comments = DB::table('research_comment as c')
            ->leftJoin('research_researcher as r', 'c.researcher_id', '=', 'r.id')
            ->where('c.entity_type', 'project')
            ->where('c.entity_id', $projectId)
            ->where('c.id', '>', $sinceCommentId)
            ->select(
                'c.id', 'c.researcher_id', 'c.parent_id', 'c.content', 'c.is_resolved',
                'c.resolved_by', 'c.resolved_at', 'c.created_at',
                'r.first_name', 'r.last_name'
            )
            ->orderBy('c.id')
            ->get()
            ->map(fn ($c) => [
                'id'           => (int) $c->id,
                'parent_id'    => $c->parent_id ? (int) $c->parent_id : null,
                'author_id'    => (int) $c->researcher_id,
                'author_name'  => trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')) ?: '#' . $c->researcher_id,
                'body'         => (string) $c->content,
                'is_resolved'  => (bool) $c->is_resolved,
                'created_at'   => $c->created_at,
            ])
            ->all();

        $cursor = !empty($comments) ? end($comments)['id'] : $sinceCommentId;

        return [
            'presence' => $presence,
            'comments' => $comments,
            'cursor'   => $cursor,
            'server_time' => date('c'),
        ];
    }

    /**
     * Append a comment to the project thread.
     */
    public function comment(int $projectId, int $researcherId, string $body, ?int $parentId = null): int
    {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Comment body required');
        }
        return DB::table('research_comment')->insertGetId([
            'researcher_id' => $researcherId,
            'entity_type'   => 'project',
            'entity_id'     => $projectId,
            'parent_id'     => $parentId,
            'content'       => $body,
            'is_resolved'   => 0,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function resolveComment(int $commentId, int $researcherId): bool
    {
        return (bool) DB::table('research_comment')
            ->where('id', $commentId)
            ->update([
                'is_resolved' => 1,
                'resolved_by' => $researcherId,
                'resolved_at' => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Pick a colour not currently in use by another active collaborator.
     */
    protected function pickColor(int $projectId): string
    {
        $cutoff = date('Y-m-d H:i:s', time() - self::PRESENCE_TIMEOUT_SECONDS);
        $used = DB::table('research_collaboration_presence')
            ->where('project_id', $projectId)
            ->where('last_seen_at', '>=', $cutoff)
            ->pluck('user_color')
            ->filter()
            ->all();
        foreach (self::PRESENCE_COLORS as $c) {
            if (!in_array($c, $used, true)) {
                return $c;
            }
        }
        // Fallback to deterministic hash colour
        return self::PRESENCE_COLORS[crc32($projectId . '-' . microtime()) % count(self::PRESENCE_COLORS)];
    }
}
