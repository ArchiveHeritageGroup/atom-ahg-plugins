<?php

/**
 * OfflineSyncService - drain a localStorage queue posted from the offline
 * mobile UI and apply each entry to its appropriate target table.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §2.7
 *
 * Supported kinds:
 *   - journal_entry:        insert into research_journal_entry
 *   - annotation:           insert into research_annotation
 *   - source:               insert into research_annotation (annotation_type=source)
 *   - metadata_suggestion:  insert into research_metadata_suggestion (curator review queue)
 *   - file:                 write to uploads/research-offline/ + research_offline_attachment
 *   - collection_item_note: update research_collection_item.notes (ownership-checked)
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
                } elseif ($kind === 'source') {
                    $this->applySource($researcherId, $entry);
                    $applied++;
                } elseif ($kind === 'metadata_suggestion') {
                    $this->applyMetadataSuggestion($researcherId, $entry);
                    $applied++;
                } elseif ($kind === 'file') {
                    $this->applyFile($researcherId, $entry);
                    $applied++;
                } elseif ($kind === 'collection_item_note') {
                    $this->applyCollectionItemNote($researcherId, $entry);
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

    /**
     * A source/citation captured offline. Stored as the researcher's own
     * annotation (annotation_type=source); the citation fields are composed into
     * the content so it survives without a bibliography container.
     */
    protected function applySource(int $researcherId, array $entry): void
    {
        $parts = array_filter([
            trim((string) ($entry['title'] ?? '')),
            trim((string) ($entry['author'] ?? '')) !== '' ? 'by ' . $entry['author'] : '',
            trim((string) ($entry['year'] ?? '')) !== '' ? '(' . $entry['year'] . ')' : '',
            trim((string) ($entry['url'] ?? '')),
        ], fn ($v) => $v !== '');
        $content = trim((string) ($entry['content'] ?? implode(' ', $parts)));
        if ($content === '') {
            return;
        }

        DB::table('research_annotation')->insert([
            'researcher_id'   => $researcherId,
            'project_id'      => $entry['project_id'] ?? null,
            'object_id'       => $entry['object_id'] ?? null,
            'entity_type'     => 'information_object',
            'annotation_type' => 'source',
            'title'           => isset($entry['title']) ? mb_substr((string) $entry['title'], 0, 255) : null,
            'content'         => $content,
            'content_format'  => 'text',
            'tags'            => 'source',
            'is_private'      => 1,
            'visibility'      => 'private',
            'created_at'      => $this->safeTimestamp($entry['created_at'] ?? null),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * A proposed metadata correction/addition — queued for curator review, never a
     * live catalogue edit.
     */
    protected function applyMetadataSuggestion(int $researcherId, array $entry): void
    {
        $field = trim((string) ($entry['field'] ?? ''));
        $suggestion = trim((string) ($entry['suggestion'] ?? $entry['content'] ?? ''));
        $objectId = (int) ($entry['object_id'] ?? 0);
        if ($field === '' || $suggestion === '' || $objectId <= 0) {
            return;
        }

        DB::table('research_metadata_suggestion')->insert([
            'researcher_id' => $researcherId,
            'object_id'     => $objectId,
            'field'         => mb_substr($field, 0, 191),
            'suggestion'    => $suggestion,
            'status'        => 'open',
            'created_at'    => $this->safeTimestamp($entry['created_at'] ?? null),
        ]);
    }

    /**
     * A file attached offline (data URL). Decoded, written under
     * uploads/research-offline/, and recorded in research_offline_attachment.
     * Size-capped to guard against oversized base64 payloads.
     */
    protected function applyFile(int $researcherId, array $entry): void
    {
        $dataUrl = (string) ($entry['data'] ?? '');
        $name = trim((string) ($entry['name'] ?? 'attachment'));
        if ($dataUrl === '' || strpos($dataUrl, 'base64,') === false) {
            return;
        }

        [$meta, $b64] = explode('base64,', $dataUrl, 2);
        $binary = base64_decode($b64, true);
        if ($binary === false) {
            return;
        }
        $maxBytes = 5 * 1024 * 1024; // 5 MB cap
        if (strlen($binary) > $maxBytes) {
            throw new \RuntimeException('Attachment "' . $name . '" exceeds the 5 MB offline limit.');
        }

        $atomRoot = rtrim((string) \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive'), '/');
        $relDir = '/uploads/research-offline/' . $researcherId;
        $absDir = $atomRoot . $relDir;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'attachment';
        $safeName = date('YmdHis') . '_' . substr(md5($b64), 0, 8) . '_' . $safeName;
        $absPath = $absDir . '/' . $safeName;
        if (file_put_contents($absPath, $binary) === false) {
            throw new \RuntimeException('Could not write attachment "' . $name . '".');
        }

        $mime = '';
        if (preg_match('#data:([^;]+);#', 'data:' . $meta . ';', $m)) {
            $mime = $m[1];
        }

        DB::table('research_offline_attachment')->insert([
            'researcher_id' => $researcherId,
            'object_id'     => isset($entry['object_id']) ? (int) $entry['object_id'] : null,
            'file_name'     => mb_substr($name, 0, 500),
            'mime_type'     => $mime !== '' ? mb_substr($mime, 0, 255) : ($entry['type'] ?? null),
            'file_size'     => strlen($binary),
            'file_path'     => $relDir . '/' . $safeName,
            'created_at'    => $this->safeTimestamp($entry['created_at'] ?? null),
        ]);
    }

    /**
     * Update the per-item note on a research_collection_item — but ONLY when the
     * item's collection belongs to THIS researcher (server-side ownership check;
     * the payload's researcher is never trusted).
     */
    protected function applyCollectionItemNote(int $researcherId, array $entry): void
    {
        $note = (string) ($entry['content'] ?? $entry['note'] ?? '');
        $collectionId = (int) ($entry['collection_id'] ?? 0);
        $objectId = (int) ($entry['object_id'] ?? 0);
        if ($collectionId <= 0 || $objectId <= 0) {
            return;
        }

        $owns = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcherId)
            ->exists();
        if (!$owns) {
            throw new \RuntimeException('Collection ' . $collectionId . ' is not yours.');
        }

        DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->where('object_id', $objectId)
            ->update(['notes' => $note]);
    }

    protected function safeTimestamp($value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $value)) {
            return date('Y-m-d H:i:s', strtotime($value));
        }
        return date('Y-m-d H:i:s');
    }
}
