<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class BlogService
{
    protected string $culture;
    protected string $table = 'registry_blog_post';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Browse & View
    // =========================================================================

    /**
     * Paginated browse with filters.
     */
    public function browse(array $params = []): array
    {
        $query = DB::table($this->table);

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['author_type'])) {
            $query->where('author_type', $params['author_type']);
        }
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        } else {
            // Public browse defaults to published only
            $query->where('status', 'published');
        }
        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $query->where('is_featured', (int) $params['is_featured']);
        }

        $searchTerm = $params['search'] ?? ($params['query'] ?? '');
        $usedLikeFallback = false;
        if (!empty($searchTerm)) {
            $query->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
        }

        $total = $query->count();

        // If FULLTEXT returned 0, fall back to LIKE search
        if ($total === 0 && !empty($searchTerm)) {
            $likeTerm = '%' . $searchTerm . '%';
            $query = DB::table($this->table);

            if (!empty($params['category'])) {
                $query->where('category', $params['category']);
            }
            if (!empty($params['author_type'])) {
                $query->where('author_type', $params['author_type']);
            }
            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            } else {
                $query->where('status', 'published');
            }
            if (isset($params['is_featured']) && $params['is_featured'] !== '') {
                $query->where('is_featured', (int) $params['is_featured']);
            }

            $query->where(function ($q) use ($likeTerm) {
                $q->where('title', 'LIKE', $likeTerm)
                  ->orWhere('content', 'LIKE', $likeTerm)
                  ->orWhere('excerpt', 'LIKE', $likeTerm)
                  ->orWhere('category', 'LIKE', $likeTerm);
            });

            $total = $query->count();
            $usedLikeFallback = true;
        }

        $sort = $params['sort'] ?? 'published_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        if (!empty($searchTerm) && $sort === 'relevance' && !$usedLikeFallback) {
            $query->orderByRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE) DESC", [$searchTerm]);
        } else {
            $query->orderBy('is_pinned', 'desc')->orderBy($sort, $direction);
        }

        $items = $query->limit($limit)->offset($offset)->get();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get blog post by slug, increment view count.
     */
    public function view(string $slug): ?object
    {
        $post = DB::table($this->table)->where('slug', $slug)->first();
        if (!$post) {
            return null;
        }

        // Increment view count
        DB::table($this->table)->where('id', $post->id)->increment('view_count');

        return $post;
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new blog post.
     */
    public function create(array $data): array
    {
        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Title is required'];
        }
        if (empty($data['content'])) {
            return ['success' => false, 'error' => 'Content is required'];
        }
        if (empty($data['author_type'])) {
            return ['success' => false, 'error' => 'Author type is required'];
        }

        $data['slug'] = $this->generateSlug($data['title']);
        $data['status'] = $data['status'] ?? 'draft';
        $data['view_count'] = 0;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        // Generate excerpt if not provided
        if (empty($data['excerpt']) && !empty($data['content'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id, 'slug' => $data['slug']];
    }

    /**
     * Update an existing blog post.
     */
    public function update(int $id, array $data): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        if (isset($data['title']) && $data['title'] !== $post->title) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        // Regenerate excerpt if content changed and no explicit excerpt
        if (isset($data['content']) && !isset($data['excerpt'])) {
            $data['excerpt'] = $this->generateExcerpt($data['content']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete a blog post and its attachments.
     */
    public function delete(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        // Remove attachments
        DB::table('registry_attachment')
            ->where('entity_type', 'blog_post')
            ->where('entity_id', $id)
            ->delete();

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Status Management
    // =========================================================================

    /**
     * Publish a blog post.
     */
    public function publish(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Archive a blog post.
     */
    public function archive(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'status' => 'archived',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    /**
     * Unpublish a blog post (set back to draft).
     */
    public function unpublish(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        DB::table($this->table)->where('id', $id)->update([
            'status' => 'draft',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    // =========================================================================
    // Featured & Pinned
    // =========================================================================

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        $newStatus = $post->is_featured ? 0 : 1;
        DB::table($this->table)->where('id', $id)->update([
            'is_featured' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'is_featured' => $newStatus];
    }

    /**
     * Toggle pinned status.
     */
    public function togglePinned(int $id): array
    {
        $post = DB::table($this->table)->where('id', $id)->first();
        if (!$post) {
            return ['success' => false, 'error' => 'Blog post not found'];
        }

        $newStatus = $post->is_pinned ? 0 : 1;
        DB::table($this->table)->where('id', $id)->update([
            'is_pinned' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'is_pinned' => $newStatus];
    }

    // =========================================================================
    // Public Queries
    // =========================================================================

    /**
     * Get latest published posts.
     */
    public function getPublished(int $limit = 10): array
    {
        return DB::table($this->table)
            ->where('status', 'published')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * Get posts by a specific author.
     */
    public function getByAuthor(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('author_type', $type)
            ->where('author_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique URL-safe slug from title.
     */
    public function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        // Truncate to reasonable length
        if (strlen($slug) > 200) {
            $slug = substr($slug, 0, 200);
            $slug = rtrim($slug, '-');
        }

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table($this->table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate an excerpt from HTML/text content.
     */
    private function generateExcerpt(string $content, int $maxLength = 300): string
    {
        // Strip HTML tags
        $text = strip_tags($content);
        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        // Truncate at word boundary
        $text = substr($text, 0, $maxLength);
        $lastSpace = strrpos($text, ' ');
        if ($lastSpace !== false) {
            $text = substr($text, 0, $lastSpace);
        }

        return $text . '...';
    }
}
