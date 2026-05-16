<?php

/**
 * OfflineSyncService - drain a localStorage queue posted from the offline
 * mobile UI and apply each entry to its appropriate target table.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §2.7
 *
 * Supported kinds:
 *   - journal_entry: insert into research_journal_entry
 *   - annotation:    insert into research_annotation
 */

use Illuminate\Database\Capsule\Manager as DB;

class OfflineSyncService
{
    /**
     * @return array{applied:int, conflicts:int, log_id:int|null}
     */
    public function applyQueue(int $researcherId, array $queue): array
    {
        $applied   = 0;
        $conflicts = 0;
        $hash      = hash('sha256', json_encode($queue));

        $logId = null;
        try {
            $logId = DB::table('research_offline_sync_log')->insertGetId([
                'researcher_id'   => $researcherId,
                'sync_started_at' => date('Y-m-d H:i:s'),
                'queued_count'    => count($queue),
                'payload_hash'    => $hash,
            ]);
        } catch (\Throwable $e) {
            // Logging is non-fatal — sync still proceeds
        }

        $errors = [];
        foreach ($queue as $entry) {
            if (!is_array($entry)) {
                $conflicts++;
                continue;
            }
            $kind = $entry['kind'] ?? null;
            try {
                if ($kind === 'journal_entry') {
                    $this->applyJournalEntry($researcherId, $entry);
                    $applied++;
                } elseif ($kind === 'annotation') {
                    $this->applyAnnotation($researcherId, $entry);
                    $applied++;
                } else {
                    $conflicts++;
                    $errors[] = "Unknown kind: " . (string) $kind;
                }
            } catch (\Throwable $e) {
                $conflicts++;
                $errors[] = $e->getMessage();
            }
        }

        if ($logId) {
            try {
                DB::table('research_offline_sync_log')->where('id', $logId)->update([
                    'sync_completed_at' => date('Y-m-d H:i:s'),
                    'applied_count'     => $applied,
                    'conflict_count'    => $conflicts,
                    'error_text'        => $errors ? implode("\n", array_slice($errors, 0, 50)) : null,
                ]);
            } catch (\Throwable $e) {
                // non-fatal
            }
        }

        return ['applied' => $applied, 'conflicts' => $conflicts, 'log_id' => $logId];
    }

    protected function applyJournalEntry(int $researcherId, array $entry): void
    {
        DB::table('research_journal_entry')->insert([
            'researcher_id'  => $researcherId,
            'project_id'     => $entry['project_id'] ?? null,
            'entry_date'     => isset($entry['entry_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry['entry_date'])
                                ? $entry['entry_date']
                                : date('Y-m-d'),
            'title'          => isset($entry['title']) ? mb_substr((string) $entry['title'], 0, 500) : null,
            'content'        => (string) ($entry['content'] ?? $entry['body'] ?? ''),
            'content_format' => 'text',
            'entry_type'     => 'manual',
            'tags'           => isset($entry['tags']) ? mb_substr((string) $entry['tags'], 0, 500) : null,
            'is_private'     => 1,
            'created_at'     => $this->safeTimestamp($entry['created_at'] ?? null),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    protected function applyAnnotation(int $researcherId, array $entry): void
    {
        DB::table('research_annotation')->insert([
            'researcher_id'     => $researcherId,
            'project_id'        => $entry['project_id'] ?? null,
            'object_id'         => $entry['object_id'] ?? null,
            'entity_type'       => 'information_object',
            'annotation_type'   => $entry['annotation_type'] ?? 'note',
            'title'             => isset($entry['title']) ? mb_substr((string) $entry['title'], 0, 255) : null,
            'content'           => (string) ($entry['content'] ?? $entry['body'] ?? ''),
            'content_format'    => 'text',
            'target_selector'   => $entry['target_selector'] ?? null,
            'tags'              => isset($entry['tags']) ? mb_substr((string) $entry['tags'], 0, 500) : null,
            'is_private'        => 1,
            'visibility'        => 'private',
            'created_at'        => $this->safeTimestamp($entry['created_at'] ?? null),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    protected function safeTimestamp($value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $value)) {
            return date('Y-m-d H:i:s', strtotime($value));
        }
        return date('Y-m-d H:i:s');
    }
}
