<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class EntityUrlService
{
    protected string $table = 'registry_entity_url';

    public const TYPES = [
        'website' => 'Website',
        'atom_instance' => 'AtoM instance',
        'repository' => 'Digital repository',
        'catalogue' => 'Online catalogue',
        'blog' => 'Blog',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'github' => 'GitHub',
        'gitlab' => 'GitLab',
        'other' => 'Other',
    ];

    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Replace all URLs for an entity with the provided list.
     * Each row in $urls is ['url' => ..., 'link_type' => ..., 'label' => ?].
     * Empty URLs are skipped.
     */
    public function replaceForEntity(string $type, int $id, array $urls): void
    {
        DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->delete();

        $order = 10;
        foreach ($urls as $row) {
            $url = trim($row['url'] ?? '');
            if ('' === $url) {
                continue;
            }
            $linkType = $row['link_type'] ?? 'website';
            if (!isset(self::TYPES[$linkType])) {
                $linkType = 'other';
            }
            $label = trim($row['label'] ?? '');
            DB::table($this->table)->insert([
                'entity_type' => $type,
                'entity_id' => $id,
                'link_type' => $linkType,
                'url' => $url,
                'label' => '' !== $label ? $label : null,
                'sort_order' => $order,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $order += 10;
        }
    }

    /**
     * Find the primary website URL for an entity (first website, then first of any type).
     */
    public function primaryWebsite(string $type, int $id): ?string
    {
        $row = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('link_type', 'website')
            ->orderBy('sort_order')
            ->first();
        if ($row) {
            return $row->url;
        }
        $row = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('sort_order')
            ->first();

        return $row ? $row->url : null;
    }

    public function labelFor(string $linkType): string
    {
        return self::TYPES[$linkType] ?? ucfirst($linkType);
    }
}
