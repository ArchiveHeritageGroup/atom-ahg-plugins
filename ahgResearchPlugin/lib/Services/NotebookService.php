<?php

/**
 * NotebookService - researcher-owned scratchpad.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §1.5
 *
 * Items: saved queries, AI outputs, pinned source items, freeform notes.
 * One-click promote-to-public turns a notebook into a research project.
 */

use Illuminate\Database\Capsule\Manager as DB;

class NotebookService
{
    public const ITEM_TYPES = ['saved_query', 'ai_output', 'source_pin', 'note'];

    public function listForResearcher(int $researcherId): array
    {
        return DB::table('research_notebook')
            ->where('researcher_id', $researcherId)
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public function get(int $id): ?object
    {
        return DB::table('research_notebook')->where('id', $id)->first();
    }

    public function getItems(int $notebookId): array
    {
        return DB::table('research_notebook_item')
            ->where('notebook_id', $notebookId)
            ->orderBy('pinned', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public function create(int $researcherId, array $data): int
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }
        return DB::table('research_notebook')->insertGetId([
            'researcher_id'    => $researcherId,
            'title'            => mb_substr((string) $data['title'], 0, 255),
            'summary'          => $data['summary'] ?? null,
            'cover_object_id'  => $data['cover_object_id'] ?? null,
            'sort_order'       => $data['sort_order'] ?? 0,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'summary', 'cover_object_id', 'sort_order'];
        $upd = array_intersect_key($data, array_flip($allowed));
        if (empty($upd)) {
            return false;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        return (bool) DB::table('research_notebook')->where('id', $id)->update($upd);
    }

    public function delete(int $id): bool
    {
        DB::table('research_notebook_item')->where('notebook_id', $id)->delete();
        return (bool) DB::table('research_notebook')->where('id', $id)->delete();
    }

    public function addItem(int $notebookId, array $data): int
    {
        $type = $data['item_type'] ?? 'note';
        if (!in_array($type, self::ITEM_TYPES, true)) {
            throw new \InvalidArgumentException("Unknown item_type: {$type}");
        }
        $insertId = DB::table('research_notebook_item')->insertGetId([
            'notebook_id'       => $notebookId,
            'item_type'         => $type,
            'title'             => $data['title']            ?? null,
            'body'              => $data['body']             ?? null,
            'source_object_id'  => $data['source_object_id'] ?? null,
            'saved_search_id'   => $data['saved_search_id']  ?? null,
            'ai_output_payload' => isset($data['ai_output_payload']) ? json_encode($data['ai_output_payload']) : null,
            'pinned'            => !empty($data['pinned']) ? 1 : 0,
            'sort_order'        => $data['sort_order']       ?? 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        $this->touchNotebook($notebookId);
        return $insertId;
    }

    public function updateItem(int $itemId, array $data): bool
    {
        $item = DB::table('research_notebook_item')->where('id', $itemId)->first();
        if (!$item) {
            return false;
        }
        $allowed = ['title', 'body', 'pinned', 'sort_order'];
        $upd = array_intersect_key($data, array_flip($allowed));
        if (empty($upd)) {
            return false;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $ok = (bool) DB::table('research_notebook_item')->where('id', $itemId)->update($upd);
        if ($ok) {
            $this->touchNotebook((int) $item->notebook_id);
        }
        return $ok;
    }

    public function removeItem(int $itemId): bool
    {
        $item = DB::table('research_notebook_item')->where('id', $itemId)->first();
        if (!$item) {
            return false;
        }
        $ok = (bool) DB::table('research_notebook_item')->where('id', $itemId)->delete();
        if ($ok) {
            $this->touchNotebook((int) $item->notebook_id);
        }
        return $ok;
    }

    /**
     * Promote a notebook to a public research project.
     *
     * Idempotent: if promoted_to_project_id is already set, returns it.
     */
    public function promoteToProject(int $notebookId, int $researcherId): ?int
    {
        $notebook = $this->get($notebookId);
        if (!$notebook || $notebook->researcher_id != $researcherId) {
            return null;
        }
        if ($notebook->promoted_to_project_id) {
            return (int) $notebook->promoted_to_project_id;
        }

        try {
            DB::beginTransaction();

            $projectId = DB::table('research_project')->insertGetId([
                'owner_id'     => $researcherId,
                'title'        => $notebook->title,
                'description'  => $notebook->summary,
                'project_type' => 'personal',
                'status'       => 'active',
                'visibility'   => 'public',
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            DB::table('research_project_collaborator')->insert([
                'project_id'    => $projectId,
                'researcher_id' => $researcherId,
                'role'          => 'owner',
                'status'        => 'accepted',
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $collectionId = DB::table('research_collection')->insertGetId([
                'project_id'    => $projectId,
                'researcher_id' => $researcherId,
                'name'          => 'Promoted from notebook: ' . mb_substr($notebook->title, 0, 80),
                'description'   => $notebook->summary,
                'visibility'    => 'public',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            // Copy source_pin items as collection items
            $pins = DB::table('research_notebook_item')
                ->where('notebook_id', $notebookId)
                ->where('item_type', 'source_pin')
                ->whereNotNull('source_object_id')
                ->get();
            foreach ($pins as $pin) {
                try {
                    DB::table('research_collection_item')->insert([
                        'collection_id' => $collectionId,
                        'object_id'     => (int) $pin->source_object_id,
                        'notes'         => $pin->body,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                    // unique key collision on (collection_id, object_id) - skip
                }
            }

            DB::table('research_notebook')->where('id', $notebookId)->update([
                'promoted_to_project_id' => $projectId,
                'promoted_at'            => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            return $projectId;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function touchNotebook(int $notebookId): void
    {
        DB::table('research_notebook')->where('id', $notebookId)->update(['updated_at' => date('Y-m-d H:i:s')]);
    }
}
