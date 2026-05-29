<?php

declare(strict_types=1);

/**
 * AuthorityControlService
 *
 * Subject authority record management (library_subject_authority) and the
 * library_item_authority_link pivot. Used by the authority-control admin
 * module and the MARC editor (6XX fields) for link-based subject heading
 * validation.
 *
 * Ported from the Heratio (Laravel) AhgLibrary\Services\AuthorityControlService.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;

class AuthorityControlService
{
    private const TABLE_AUTHORITY = 'library_subject_authority';
    private const TABLE_LINK      = 'library_item_authority_link';

    /**
     * List authority records with optional search, paginated.
     *
     * @param array $params  page|limit|search|subject_type|source
     * @return array{hits:array,total:int,page:int,limit:int,pages:int}
     */
    public function index(array $params = []): array
    {
        $page   = max(1, (int) ($params['page'] ?? 1));
        $limit  = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $skip   = ($page - 1) * $limit;
        $search = trim((string) ($params['search'] ?? ''));
        $type   = trim((string) ($params['subject_type'] ?? ''));
        $source = trim((string) ($params['source'] ?? ''));

        $query = DB::table(self::TABLE_AUTHORITY);

        if ($search !== '') {
            $query->where('heading', 'LIKE', '%' . $search . '%');
        }
        if ($type !== '') {
            $query->where('subject_type', $type);
        }
        if ($source !== '') {
            $query->where('source', $source);
        }

        $total = (clone $query)->count();

        $rows = $query
            ->orderByDesc('linked_count')
            ->orderBy('heading')
            ->skip($skip)
            ->take($limit)
            ->get()
            ->all();

        return [
            'hits'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * Find a single authority record by ID, with a fresh linked_count.
     */
    public function find(int $id): ?object
    {
        $auth = DB::table(self::TABLE_AUTHORITY)->where('id', $id)->first();

        if ($auth) {
            $count = DB::table(self::TABLE_LINK)->where('authority_id', $id)->count();
            $auth = (object) array_merge((array) $auth, ['linked_count' => $count]);
        }

        return $auth;
    }

    /**
     * Create a new authority record.
     *
     * @param array $data  heading|subject_type|source|uri
     * @return int  authority record ID
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return (int) DB::table(self::TABLE_AUTHORITY)->insertGetId([
            'heading'      => $data['heading'] ?? '',
            'subject_type' => $data['subject_type'] ?? 'topic',
            'source'       => $data['source'] ?? 'local',
            'uri'          => $data['uri'] ?? null,
            'linked_count' => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    /**
     * Update an existing authority record (only the non-null fields supplied).
     */
    public function update(int $id, array $data): bool
    {
        $upd = array_filter([
            'heading'      => $data['heading'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'source'       => $data['source'] ?? null,
            'uri'          => array_key_exists('uri', $data) ? $data['uri'] : null,
        ], static fn ($v) => $v !== null);

        if (empty($upd)) {
            return false;
        }

        $upd['updated_at'] = date('Y-m-d H:i:s');

        return (bool) DB::table(self::TABLE_AUTHORITY)->where('id', $id)->update($upd);
    }

    /**
     * Delete an authority record (FK cascade removes its links).
     */
    public function delete(int $id): bool
    {
        return (bool) DB::table(self::TABLE_AUTHORITY)->where('id', $id)->delete();
    }

    /**
     * Link an authority record to a library item via the pivot, incrementing
     * linked_count. No-op if the link already exists.
     */
    public function linkToItem(int $authorityId, int $libraryItemId, string $tag = '650'): void
    {
        $exists = DB::table(self::TABLE_LINK)
            ->where('library_item_id', $libraryItemId)
            ->where('authority_id', $authorityId)
            ->exists();

        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        DB::table(self::TABLE_LINK)->insert([
            'library_item_id' => $libraryItemId,
            'authority_id'    => $authorityId,
            'source_tag'      => $tag,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        DB::table(self::TABLE_AUTHORITY)->where('id', $authorityId)->increment('linked_count');
    }

    /**
     * Remove a specific authority-to-item link, decrementing linked_count.
     */
    public function unlinkFromItem(int $linkId): void
    {
        $link = DB::table(self::TABLE_LINK)->where('id', $linkId)->first();
        if (!$link) {
            return;
        }

        DB::table(self::TABLE_LINK)->where('id', $linkId)->delete();
        DB::table(self::TABLE_AUTHORITY)
            ->where('id', $link->authority_id)
            ->where('linked_count', '>', 0)
            ->decrement('linked_count');
    }

    /**
     * Typeahead search of authority headings (most-linked first).
     *
     * @return array  list of {id, heading, subject_type, source}
     */
    public function search(string $term, int $max = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        return DB::table(self::TABLE_AUTHORITY)
            ->where('heading', 'LIKE', '%' . $term . '%')
            ->orderByDesc('linked_count')
            ->take($max)
            ->get(['id', 'heading', 'subject_type', 'source'])
            ->all();
    }

    /**
     * All library items linked to an authority record (with their titles).
     *
     * @return array  list of {link_id, library_item_id, source_tag, title}
     */
    public function linkedItems(int $authorityId, string $culture = 'en'): array
    {
        return DB::table(self::TABLE_LINK . ' as link')
            ->join('library_item as li', 'li.id', '=', 'link.library_item_id')
            ->join('information_object as io', 'io.id', '=', 'li.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->where('link.authority_id', $authorityId)
            ->get([
                'link.id as link_id',
                'link.library_item_id',
                'link.source_tag',
                'ioi.title as title',
            ])
            ->all();
    }
}
