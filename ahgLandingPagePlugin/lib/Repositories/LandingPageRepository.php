<?php

namespace AtomExtensions\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Landing Page Repository
 *
 * Data access layer for landing pages and blocks
 * Uses Laravel Query Builder (Illuminate\Database)
 */
class LandingPageRepository
{
    // =========================================================================
    // PAGE QUERIES
    // =========================================================================

    /**
     * Get page by slug
     */
    public function getPageBySlug(string $slug): ?object
    {
        return DB::table('atom_landing_page')
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get default landing page
     */
    public function getDefaultPage(): ?object
    {
        return DB::table('atom_landing_page')
            ->where('is_default', 1)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get page by ID
     */
    public function getPageById(int $id): ?object
    {
        return DB::table('atom_landing_page')
            ->where('id', $id)
            ->first();
    }

    /**
     * Get all pages
     */
    public function getAllPages(bool $activeOnly = false): Collection
    {
        $query = DB::table('atom_landing_page')
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->get();
    }

    /**
     * Check if slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table('atom_landing_page')
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // =========================================================================
    // PAGE MUTATIONS
    // =========================================================================

    /**
     * Create new page
     */
    public function createPage(array $data): int
    {
        // If this is default, unset other defaults
        if (!empty($data['is_default'])) {
            DB::table('atom_landing_page')
                ->where('is_default', 1)
                ->update(['is_default' => 0]);
        }

        return DB::table('atom_landing_page')->insertGetId([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_default' => $data['is_default'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'layout' => $data['layout'] ?? 'default',
            'css_classes' => $data['css_classes'] ?? null,
            'custom_css' => $data['custom_css'] ?? null,
            'custom_js' => $data['custom_js'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update page
     */
    public function updatePage(int $pageId, array $data): bool
    {
        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            DB::table('atom_landing_page')
                ->where('is_default', 1)
                ->where('id', '!=', $pageId)
                ->update(['is_default' => 0]);
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'is_default' => isset($data['is_default']) ? (int) $data['is_default'] : null,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'layout' => $data['layout'] ?? null,
            'css_classes' => $data['css_classes'] ?? null,
            'custom_css' => $data['custom_css'] ?? null,
            'custom_js' => $data['custom_js'] ?? null,
            'user_id' => $data['user_id'] ?? null,
        ], function ($value) {
            return $value !== null;
        });

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('atom_landing_page')
            ->where('id', $pageId)
            ->update($updateData) >= 0;
    }

    /**
     * Delete page
     */
    public function deletePage(int $pageId): bool
    {
        // Blocks are deleted via CASCADE
        return DB::table('atom_landing_page')
            ->where('id', $pageId)
            ->delete() > 0;
    }

    // =========================================================================
    // BLOCK QUERIES
    // =========================================================================

    /**
     * Get blocks for a page
     */
    public function getPageBlocks(int $pageId, bool $visibleOnly = true): Collection
    {
        $query = DB::table('atom_landing_page_block as b')
            ->join('atom_landing_page_block_type as bt', 'b.block_type_id', '=', 'bt.id')
            ->where('b.page_id', $pageId)
            ->whereNull('b.parent_block_id') // Only root-level blocks
            ->orderBy('b.position', 'asc')
            ->select([
                'b.id',
                'b.page_id',
                'b.block_type_id',
                'b.parent_block_id',
                'b.column_slot',
                'b.title',
                'b.position',
                'b.config',
                'b.css_classes',
                'b.container_type',
                'b.background_color',
                'b.text_color',
                'b.padding_top',
                'b.padding_bottom',
                'b.col_span',
                'b.is_visible',
                'bt.machine_name',
                'bt.label as type_label',
                'bt.icon as type_icon',
                'bt.config_schema',
                'bt.default_config',
                'bt.is_container',
            ]);

        if ($visibleOnly) {
            $query->where('b.is_visible', 1);
        }

        return $query->get()->map(function ($block) {
            $block->config = json_decode($block->config, true) ?: [];
            $block->config_schema = json_decode($block->config_schema, true) ?: [];
            $block->default_config = json_decode($block->default_config, true) ?: [];
            return $block;
        });
    }

    /**
     * Get child blocks (for column layouts)
     */
    public function getChildBlocks(int $parentBlockId): Collection
    {
        return DB::table('atom_landing_page_block as b')
            ->join('atom_landing_page_block_type as bt', 'b.block_type_id', '=', 'bt.id')
            ->where('b.parent_block_id', $parentBlockId)
            ->orderBy('b.column_slot', 'asc')
            ->orderBy('b.position', 'asc')
            ->select([
                'b.id',
                'b.page_id',
                'b.block_type_id',
                'b.parent_block_id',
                'b.column_slot',
                'b.title',
                'b.position',
                'b.config',
                'b.css_classes',
                'b.container_type',
                'b.background_color',
                'b.text_color',
                'b.padding_top',
                'b.padding_bottom',
                'b.col_span',
                'b.is_visible',
                'bt.machine_name',
                'bt.label as type_label',
                'bt.icon as type_icon',
                'bt.config_schema',
                'bt.default_config',
                'bt.is_container',
            ])
            ->get()
            ->map(function ($block) {
                $block->config = json_decode($block->config, true) ?: [];
                $block->config_schema = json_decode($block->config_schema, true) ?: [];
                $block->default_config = json_decode($block->default_config, true) ?: [];
                return $block;
            });
    }

    /**
     * Get block by ID
     */
    public function getBlockById(int $blockId): ?object
    {
        $block = DB::table('atom_landing_page_block as b')
            ->join('atom_landing_page_block_type as bt', 'b.block_type_id', '=', 'bt.id')
            ->where('b.id', $blockId)
            ->select([
                'b.id',
                'b.page_id',
                'b.block_type_id',
                'b.parent_block_id',
                'b.column_slot',
                'b.title',
                'b.position',
                'b.config',
                'b.css_classes',
                'b.container_type',
                'b.background_color',
                'b.text_color',
                'b.padding_top',
                'b.padding_bottom',
                'b.col_span',
                'b.is_visible',
                'bt.machine_name',
                'bt.label as type_label',
                'bt.icon as type_icon',
                'bt.config_schema',
                'bt.default_config',
                'bt.is_container',
            ])
            ->first();

        if ($block) {
            $block->config = json_decode($block->config, true) ?: [];
            $block->config_schema = json_decode($block->config_schema, true) ?: [];
            $block->default_config = json_decode($block->default_config, true) ?: [];
        }

        return $block;
    }

    /**
     * Get all block types
     */
    public function getAllBlockTypes(): Collection
    {
        return DB::table('atom_landing_page_block_type')
            ->where('is_active', 1)
            ->orderBy('category', 'asc')
            ->orderBy('load_order', 'asc')
            ->get()
            ->map(function ($type) {
                $type->config_schema = json_decode($type->config_schema, true) ?: [];
                $type->default_config = json_decode($type->default_config, true) ?: [];
                return $type;
            });
    }

    /**
     * Get block type by ID
     */
    public function getBlockTypeById(int $typeId): ?object
    {
        $type = DB::table('atom_landing_page_block_type')
            ->where('id', $typeId)
            ->first();

        if ($type) {
            $type->config_schema = json_decode($type->config_schema, true) ?: [];
            $type->default_config = json_decode($type->default_config, true) ?: [];
        }

        return $type;
    }

    // =========================================================================
    // BLOCK MUTATIONS
    // =========================================================================

    /**
     * Create block
     */
    public function createBlock(array $data): int
    {
        // Get next position
        $maxPosition = DB::table('atom_landing_page_block')
            ->where('page_id', $data['page_id'])
            ->whereNull('parent_block_id')
            ->max('position') ?? 0;

        return DB::table('atom_landing_page_block')->insertGetId([
            'page_id' => $data['page_id'],
            'block_type_id' => $data['block_type_id'],
            'parent_block_id' => $data['parent_block_id'] ?? null,
            'column_slot' => $data['column_slot'] ?? null,
            'title' => $data['title'] ?? null,
            'position' => $data['position'] ?? ($maxPosition + 1),
            'config' => json_encode($data['config'] ?? []),
            'css_classes' => $data['css_classes'] ?? null,
            'container_type' => $data['container_type'] ?? 'container',
            'background_color' => $data['background_color'] ?? null,
            'text_color' => $data['text_color'] ?? null,
            'padding_top' => $data['padding_top'] ?? 'py-4',
            'padding_bottom' => $data['padding_bottom'] ?? 'py-4',
            'col_span' => $data['col_span'] ?? 12,
            'is_visible' => $data['is_visible'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update block
     */
    public function updateBlock(int $blockId, array $data): bool
    {
        $updateData = [];

        // Handle config specially - merge or replace
        if (isset($data['config'])) {
            $updateData['config'] = json_encode($data['config']);
        }

        // Map other fields
        $fields = [
            'title', 'position', 'css_classes', 'container_type',
            'background_color', 'text_color', 'padding_top', 'padding_bottom',
            'col_span', 'is_visible', 'parent_block_id', 'column_slot',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('atom_landing_page_block')
            ->where('id', $blockId)
            ->update($updateData) >= 0;
    }

    /**
     * Delete block
     */
    public function deleteBlock(int $blockId): bool
    {
        // Child blocks are deleted via CASCADE
        return DB::table('atom_landing_page_block')
            ->where('id', $blockId)
            ->delete() > 0;
    }

    /**
     * Duplicate block
     */
    public function duplicateBlock(int $blockId): int
    {
        $block = $this->getBlockById($blockId);
        if (!$block) {
            throw new \Exception('Block not found');
        }

        return $this->createBlock([
            'page_id' => $block->page_id,
            'block_type_id' => $block->block_type_id,
            'parent_block_id' => $block->parent_block_id,
            'column_slot' => $block->column_slot,
            'title' => $block->title ? $block->title.' (Copy)' : null,
            'position' => $block->position + 1,
            'config' => $block->config,
            'css_classes' => $block->css_classes,
            'container_type' => $block->container_type,
            'background_color' => $block->background_color,
            'text_color' => $block->text_color,
            'padding_top' => $block->padding_top,
            'padding_bottom' => $block->padding_bottom,
            'col_span' => $block->col_span,
            'is_visible' => $block->is_visible,
        ]);
    }

    /**
     * Reorder blocks
     */
    public function reorderBlocks(int $pageId, array $order): bool
    {
        DB::beginTransaction();
        try {
            foreach ($order as $position => $blockId) {
                DB::table('atom_landing_page_block')
                    ->where('id', $blockId)
                    ->where('page_id', $pageId)
                    ->update(['position' => $position]);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Move block to column
     */
    public function moveBlockToColumn(int $blockId, ?int $parentBlockId, ?string $columnSlot): bool
    {
        return DB::table('atom_landing_page_block')
            ->where('id', $blockId)
            ->update([
                'parent_block_id' => $parentBlockId,
                'column_slot' => $columnSlot,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) >= 0;
    }

    // =========================================================================
    // VERSION MANAGEMENT
    // =========================================================================

    /**
     * Create version snapshot
     */
    public function createVersion(int $pageId, string $status = 'draft', ?int $userId = null, ?string $notes = null): int
    {
        // Get current version number
        $maxVersion = DB::table('atom_landing_page_version')
            ->where('page_id', $pageId)
            ->max('version_number') ?? 0;

        // Create snapshot of current state
        $page = $this->getPageById($pageId);
        $blocks = $this->getPageBlocks($pageId, false);

        $snapshot = [
            'page' => (array) $page,
            'blocks' => $blocks->map(function ($block) {
                return (array) $block;
            })->toArray(),
        ];

        return DB::table('atom_landing_page_version')->insertGetId([
            'page_id' => $pageId,
            'version_number' => $maxVersion + 1,
            'status' => $status,
            'snapshot' => json_encode($snapshot),
            'notes' => $notes,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Restore version
     */
    public function restoreVersion(int $versionId): bool
    {
        $version = DB::table('atom_landing_page_version')
            ->where('id', $versionId)
            ->first();

        if (!$version) {
            throw new \Exception('Version not found');
        }

        $snapshot = json_decode($version->snapshot, true);
        $pageId = $version->page_id;

        DB::beginTransaction();
        try {
            // Delete current blocks
            DB::table('atom_landing_page_block')
                ->where('page_id', $pageId)
                ->delete();

            // Restore blocks from snapshot
            foreach ($snapshot['blocks'] as $blockData) {
                // Remove computed fields
                unset(
                    $blockData['type_label'],
                    $blockData['type_icon'],
                    $blockData['config_schema'],
                    $blockData['default_config'],
                    $blockData['is_container']
                );

                // Ensure config is JSON
                if (is_array($blockData['config'])) {
                    $blockData['config'] = json_encode($blockData['config']);
                }

                $blockData['created_at'] = date('Y-m-d H:i:s');
                $blockData['updated_at'] = date('Y-m-d H:i:s');

                // Remove ID for fresh insert
                unset($blockData['id']);

                DB::table('atom_landing_page_block')->insert($blockData);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get page versions
     */
    public function getPageVersions(int $pageId, int $limit = 10): Collection
    {
        return DB::table('atom_landing_page_version')
            ->where('page_id', $pageId)
            ->orderBy('version_number', 'desc')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // AUDIT LOG
    // =========================================================================

    /**
     * Log audit entry
     */
    public function logAudit(
        string $action,
        ?int $pageId,
        ?int $blockId,
        ?array $data,
        ?int $userId
    ): int {
        return DB::table('atom_landing_page_audit_log')->insertGetId([
            'action' => $action,
            'page_id' => $pageId,
            'block_id' => $blockId,
            'data' => $data ? json_encode($data) : null,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
                : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
