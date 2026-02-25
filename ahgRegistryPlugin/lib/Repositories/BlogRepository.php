<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class BlogRepository
{
    protected string $table = 'registry_blog_post';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function findAll(array $params = []): array
    {
        $query = DB::table($this->table);

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['author_type'])) {
            $query->where('author_type', $params['author_type']);
        }
        if (!empty($params['author_id'])) {
            $query->where('author_id', $params['author_id']);
        }
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (isset($params['is_featured'])) {
            $query->where('is_featured', (int) $params['is_featured']);
        }
        if (isset($params['is_pinned'])) {
            $query->where('is_pinned', (int) $params['is_pinned']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'published_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function search(string $term, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('status', 'published')
            ->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$term]);

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderByRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE) DESC", [$term])
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function getPublished(array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('status', 'published')
            ->where('published_at', '<=', date('Y-m-d H:i:s'));

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('is_pinned', 'desc')
                       ->orderBy('published_at', 'desc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function getFeatured(int $limit = 6): array
    {
        return DB::table($this->table)
            ->where('status', 'published')
            ->where('is_featured', 1)
            ->where('published_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getPinned(): array
    {
        return DB::table($this->table)
            ->where('status', 'published')
            ->where('is_pinned', 1)
            ->where('published_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('published_at', 'desc')
            ->get()
            ->all();
    }

    public function incrementViewCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('view_count');
    }
}
