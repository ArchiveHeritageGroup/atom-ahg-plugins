<?php

namespace AhgRecordsManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * FilePlanService — records-management file plan / classification scheme
 * (nested-set tree of classification nodes). PSIS-parity port of the Heratio
 * AhgRecordsManage\Services\FilePlanService (#118 records-manage gap).
 *
 * Optional companion tables (rm_disposal_class, rm_record_disposal_class) are
 * Schema-guarded; without them, record counts fall back to identifier-prefix
 * matching against information_object.
 *
 * @package ahgRecordsManagePlugin
 */
class FilePlanService
{
    private static function ts(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** Recursive tree from an optional parent. */
    public function getTree(?int $parentId = null): array
    {
        if (!DB::schema()->hasTable('rm_fileplan_node')) {
            return [];
        }
        $query = DB::table('rm_fileplan_node');
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);

        $tree = [];
        foreach ($query->orderBy('code')->get() as $node) {
            $item = (array) $node;
            $item['children'] = $this->getTree($node->id);
            $item['record_count'] = $this->getRecordCountForNode($node->id);
            $tree[] = $item;
        }

        return $tree;
    }

    /** Flat list ordered by lft (for display). */
    public function getTreeFlat(): array
    {
        if (!DB::schema()->hasTable('rm_fileplan_node')) {
            return [];
        }

        return DB::table('rm_fileplan_node')
            ->orderByRaw('COALESCE(lft, 999999)')->orderBy('code')->get()
            ->map(function ($node) {
                $node->record_count = $this->getRecordCountForNode($node->id);

                return $node;
            })->all();
    }

    public function getNode(int $id): ?object
    {
        if (!DB::schema()->hasTable('rm_fileplan_node')) {
            return null;
        }
        $node = DB::table('rm_fileplan_node as n')
            ->leftJoin('rm_fileplan_node as p', 'n.parent_id', '=', 'p.id')
            ->select('n.*', 'p.code as parent_code', 'p.title as parent_title')
            ->where('n.id', $id)->first();
        if (!$node) {
            return null;
        }
        if ($node->disposal_class_id && DB::schema()->hasTable('rm_disposal_class')) {
            $dc = DB::table('rm_disposal_class')->where('id', $node->disposal_class_id)->first();
            $node->disposal_class_code = $dc->code ?? null;
            $node->disposal_class_title = $dc->title ?? null;
        } else {
            $node->disposal_class_code = null;
            $node->disposal_class_title = null;
        }
        $node->record_count = $this->getRecordCountForNode($id);
        $node->child_count = DB::table('rm_fileplan_node')->where('parent_id', $id)->count();

        return $node;
    }

    public function createNode(array $data): int
    {
        $id = DB::table('rm_fileplan_node')->insertGetId([
            'parent_id' => $data['parent_id'] ?? null ?: null,
            'function_object_id' => $data['function_object_id'] ?? null ?: null,
            'node_type' => $data['node_type'] ?? 'series',
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null ?: null,
            'disposal_class_id' => $data['disposal_class_id'] ?? null ?: null,
            'retention_period' => $data['retention_period'] ?? null ?: null,
            'disposal_action' => $data['disposal_action'] ?? null ?: null,
            'status' => $data['status'] ?? 'active',
            'source_department' => $data['source_department'] ?? null ?: null,
            'source_agency_code' => $data['source_agency_code'] ?? null ?: null,
            'depth' => $data['depth'] ?? 0,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => self::ts(),
            'updated_at' => self::ts(),
        ]);
        $this->rebuildNestedSet();

        return (int) $id;
    }

    public function updateNode(int $id, array $data): bool
    {
        $update = [];
        foreach (['parent_id', 'function_object_id', 'node_type', 'code', 'title', 'description',
            'disposal_class_id', 'retention_period', 'disposal_action', 'status',
            'source_department', 'source_agency_code'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field] !== '' ? $data[$field] : null;
            }
        }
        $update['updated_at'] = self::ts();
        $ok = DB::table('rm_fileplan_node')->where('id', $id)->update($update) >= 0;
        $this->rebuildNestedSet();

        return $ok;
    }

    /** Delete only if the node has no children and no linked records. */
    public function deleteNode(int $id): bool
    {
        if (DB::table('rm_fileplan_node')->where('parent_id', $id)->count() > 0) {
            return false;
        }
        if ($this->getRecordCountForNode($id) > 0) {
            return false;
        }
        $deleted = DB::table('rm_fileplan_node')->where('id', $id)->delete();
        if ($deleted) {
            $this->rebuildNestedSet();
        }

        return $deleted > 0;
    }

    public function moveNode(int $nodeId, int $newParentId): bool
    {
        $node = DB::table('rm_fileplan_node')->where('id', $nodeId)->first();
        $parent = DB::table('rm_fileplan_node')->where('id', $newParentId)->first();
        if (!$node || !$parent) {
            return false;
        }
        // Prevent moving a node into its own subtree.
        if ($parent->lft !== null && $node->lft !== null
            && $parent->lft >= $node->lft && $parent->rgt <= $node->rgt) {
            return false;
        }
        DB::table('rm_fileplan_node')->where('id', $nodeId)
            ->update(['parent_id' => $newParentId, 'updated_at' => self::ts()]);
        $this->rebuildNestedSet();

        return true;
    }

    public function getNodeByCode(string $code): ?object
    {
        return DB::table('rm_fileplan_node')->where('code', $code)->first();
    }

    public function getBreadcrumb(int $nodeId): array
    {
        $crumbs = [];
        $current = DB::table('rm_fileplan_node')->where('id', $nodeId)->first();
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent_id
                ? DB::table('rm_fileplan_node')->where('id', $current->parent_id)->first()
                : null;
        }

        return $crumbs;
    }

    public function getNodesForDropdown(): array
    {
        return DB::table('rm_fileplan_node')
            ->orderByRaw('COALESCE(lft, 999999)')->orderBy('code')
            ->select('id', 'code', 'title', 'depth')->get()->all();
    }

    public function getStats(): array
    {
        if (!DB::schema()->hasTable('rm_fileplan_node')) {
            return ['total_nodes' => 0, 'by_type' => [], 'by_status' => []];
        }

        return [
            'total_nodes' => DB::table('rm_fileplan_node')->count(),
            'by_type' => DB::table('rm_fileplan_node')->selectRaw('node_type, COUNT(*) as cnt')
                ->groupBy('node_type')->pluck('cnt', 'node_type')->toArray(),
            'by_status' => DB::table('rm_fileplan_node')->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')->pluck('cnt', 'status')->toArray(),
        ];
    }

    /** Rebuild lft/rgt/depth for the whole tree. */
    public function rebuildNestedSet(): void
    {
        $counter = 0;
        foreach (DB::table('rm_fileplan_node')->whereNull('parent_id')->orderBy('code')->get() as $root) {
            $lft = ++$counter;
            $this->rebuildRecursive($root->id, $counter, 1);
            $rgt = ++$counter;
            DB::table('rm_fileplan_node')->where('id', $root->id)
                ->update(['lft' => $lft, 'rgt' => $rgt, 'depth' => 0]);
        }
    }

    private function rebuildRecursive(int $parentId, int &$counter, int $depth): void
    {
        foreach (DB::table('rm_fileplan_node')->where('parent_id', $parentId)->orderBy('code')->get() as $child) {
            $lft = ++$counter;
            $this->rebuildRecursive($child->id, $counter, $depth + 1);
            $rgt = ++$counter;
            DB::table('rm_fileplan_node')->where('id', $child->id)
                ->update(['lft' => $lft, 'rgt' => $rgt, 'depth' => $depth]);
        }
    }

    private function getRecordCountForNode(int $nodeId): int
    {
        $node = DB::table('rm_fileplan_node')->where('id', $nodeId)->first();
        if (!$node) {
            return 0;
        }
        if (DB::schema()->hasTable('rm_record_disposal_class') && $node->disposal_class_id) {
            return DB::table('rm_record_disposal_class')->where('disposal_class_id', $node->disposal_class_id)->count();
        }

        return DB::table('information_object')->where('identifier', 'LIKE', $node->code . '%')->count();
    }
}
