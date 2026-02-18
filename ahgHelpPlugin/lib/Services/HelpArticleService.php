<?php

namespace AhgHelp\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Help Article Service — CRUD and search operations.
 *
 * Uses Laravel Query Builder for all database access.
 */
class HelpArticleService
{
    /**
     * Get all articles, optionally filtered by category and published status.
     */
    public static function getAll(?string $category = null, bool $publishedOnly = true): array
    {
        try {
            $query = DB::table('help_article')
                ->select('id', 'slug', 'title', 'category', 'subcategory', 'word_count', 'related_plugin', 'tags', 'sort_order', 'updated_at');

            if ($category !== null) {
                $query->where('category', $category);
            }

            if ($publishedOnly) {
                $query->where('is_published', 1);
            }

            return $query->orderBy('sort_order')->orderBy('title')->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin getAll error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get a single article by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        try {
            $row = DB::table('help_article')
                ->where('slug', $slug)
                ->where('is_published', 1)
                ->first();

            return $row ? (array) $row : null;
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin getBySlug error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Get distinct categories with article counts.
     */
    public static function getCategories(): array
    {
        try {
            return DB::table('help_article')
                ->where('is_published', 1)
                ->select('category', DB::raw('COUNT(*) as article_count'))
                ->groupBy('category')
                ->orderBy('category')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin getCategories error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get articles by category, grouped by subcategory.
     */
    public static function getByCategory(string $category): array
    {
        try {
            return DB::table('help_article')
                ->where('category', $category)
                ->where('is_published', 1)
                ->select('id', 'slug', 'title', 'subcategory', 'word_count', 'related_plugin', 'tags', 'updated_at')
                ->orderBy('subcategory')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin getByCategory error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * FULLTEXT search across articles.
     *
     * @return array Results with relevance score and text snippet
     */
    public static function search(string $query, int $limit = 20): array
    {
        try {
            $escaped = addslashes($query);

            return DB::table('help_article')
                ->where('is_published', 1)
                ->whereRaw('MATCH(title, body_text) AGAINST(? IN BOOLEAN MODE)', [$query . '*'])
                ->select(
                    'id',
                    'slug',
                    'title',
                    'category',
                    'subcategory',
                    'word_count',
                    DB::raw("MATCH(title, body_text) AGAINST('{$escaped}*' IN BOOLEAN MODE) AS relevance"),
                    DB::raw('SUBSTRING(body_text, 1, 300) AS snippet')
                )
                ->orderByDesc('relevance')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin search error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * FULLTEXT search at section level (returns article + anchor).
     */
    public static function searchSections(string $query, int $limit = 30): array
    {
        try {
            $escaped = addslashes($query);

            return DB::table('help_section as hs')
                ->join('help_article as ha', 'hs.article_id', '=', 'ha.id')
                ->where('ha.is_published', 1)
                ->whereRaw('MATCH(hs.heading, hs.body_text) AGAINST(? IN BOOLEAN MODE)', [$query . '*'])
                ->select(
                    'ha.slug',
                    'ha.title as article_title',
                    'ha.category',
                    'hs.heading',
                    'hs.anchor',
                    'hs.level',
                    DB::raw("MATCH(hs.heading, hs.body_text) AGAINST('{$escaped}*' IN BOOLEAN MODE) AS relevance"),
                    DB::raw('SUBSTRING(hs.body_text, 1, 200) AS snippet')
                )
                ->orderByDesc('relevance')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin searchSections error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Upsert an article from parsed markdown data.
     */
    public static function upsertFromMarkdown(string $slug, array $data): ?int
    {
        try {
            $existing = DB::table('help_article')->where('slug', $slug)->first();

            $row = [
                'slug' => $slug,
                'title' => $data['title'] ?? $slug,
                'category' => $data['category'] ?? 'User Guide',
                'subcategory' => $data['subcategory'] ?? null,
                'source_file' => $data['source_file'] ?? null,
                'body_markdown' => $data['body_markdown'],
                'body_html' => $data['body_html'],
                'body_text' => $data['body_text'],
                'toc_json' => !empty($data['toc']) ? json_encode($data['toc']) : null,
                'word_count' => $data['word_count'] ?? 0,
                'sort_order' => $data['sort_order'] ?? 100,
                'is_published' => 1,
                'related_plugin' => $data['related_plugin'] ?? null,
                'tags' => $data['tags'] ?? null,
            ];

            if ($existing) {
                $row['updated_at'] = date('Y-m-d H:i:s');
                DB::table('help_article')->where('id', $existing->id)->update($row);
                $articleId = $existing->id;
            } else {
                $articleId = DB::table('help_article')->insertGetId($row);
            }

            // Rebuild sections for this article
            DB::table('help_section')->where('article_id', $articleId)->delete();

            if (!empty($data['sections'])) {
                $sortOrder = 0;
                foreach ($data['sections'] as $section) {
                    DB::table('help_section')->insert([
                        'article_id' => $articleId,
                        'heading' => $section['heading'],
                        'anchor' => $section['anchor'],
                        'level' => $section['level'],
                        'body_text' => $section['body_text'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            return $articleId;
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin upsert error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Get adjacent articles (previous/next) within the same category.
     */
    public static function getAdjacentArticles(int $id, string $category): array
    {
        try {
            $current = DB::table('help_article')->where('id', $id)->first();
            if (!$current) {
                return ['prev' => null, 'next' => null];
            }

            $prev = DB::table('help_article')
                ->where('category', $category)
                ->where('is_published', 1)
                ->where(function ($q) use ($current) {
                    $q->where('sort_order', '<', $current->sort_order)
                        ->orWhere(function ($q2) use ($current) {
                            $q2->where('sort_order', '=', $current->sort_order)
                                ->where('title', '<', $current->title);
                        });
                })
                ->select('slug', 'title')
                ->orderByDesc('sort_order')
                ->orderByDesc('title')
                ->first();

            $next = DB::table('help_article')
                ->where('category', $category)
                ->where('is_published', 1)
                ->where(function ($q) use ($current) {
                    $q->where('sort_order', '>', $current->sort_order)
                        ->orWhere(function ($q2) use ($current) {
                            $q2->where('sort_order', '=', $current->sort_order)
                                ->where('title', '>', $current->title);
                        });
                })
                ->select('slug', 'title')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->first();

            return [
                'prev' => $prev ? (array) $prev : null,
                'next' => $next ? (array) $next : null,
            ];
        } catch (\Exception $e) {
            return ['prev' => null, 'next' => null];
        }
    }

    /**
     * Get articles related to a specific plugin.
     */
    public static function getRelatedByPlugin(string $pluginName): array
    {
        try {
            return DB::table('help_article')
                ->where('related_plugin', $pluginName)
                ->where('is_published', 1)
                ->select('slug', 'title', 'category', 'subcategory')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recently updated articles.
     */
    public static function getRecentlyUpdated(int $limit = 5): array
    {
        try {
            return DB::table('help_article')
                ->where('is_published', 1)
                ->select('slug', 'title', 'category', 'subcategory', 'updated_at')
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }
}
