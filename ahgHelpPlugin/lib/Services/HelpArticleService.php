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
    /** Categories restricted to administrators only */
    public const ADMIN_CATEGORIES = ['Technical', 'Plugin Reference'];

    /** Cached list of enabled plugin names */
    protected static $enabledPlugins = null;

    /**
     * Check if the current user is an administrator.
     */
    public static function isAdmin(): bool
    {
        try {
            $context = \sfContext::getInstance();

            return $context->getUser()->isAdministrator();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the list of enabled plugin names (cached per request).
     */
    public static function getEnabledPlugins(): array
    {
        if (self::$enabledPlugins !== null) {
            return self::$enabledPlugins;
        }

        try {
            self::$enabledPlugins = DB::table('atom_plugin')
                ->where('is_enabled', 1)
                ->pluck('name')
                ->all();
        } catch (\Exception $e) {
            // If atom_plugin table is unavailable, don't filter
            self::$enabledPlugins = [];
        }

        return self::$enabledPlugins;
    }

    /**
     * Apply admin-only category filter to a query.
     */
    protected static function applyAdminFilter($query): void
    {
        if (!self::isAdmin()) {
            $query->whereNotIn('category', self::ADMIN_CATEGORIES);
        }
    }

    /**
     * Apply enabled-plugin filter: hide articles whose related_plugin
     * is set but not enabled on this deployment.
     * Articles with no related_plugin (general docs) always show.
     */
    protected static function applyPluginFilter($query): void
    {
        $enabled = self::getEnabledPlugins();
        if (empty($enabled)) {
            return; // No plugin list available — show all
        }

        $query->where(function ($q) use ($enabled) {
            $q->whereNull('related_plugin')
              ->orWhereIn('related_plugin', $enabled);
        });
    }

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

            self::applyAdminFilter($query);
            self::applyPluginFilter($query);

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
            $query = DB::table('help_article')
                ->where('slug', $slug)
                ->where('is_published', 1);

            self::applyAdminFilter($query);
            self::applyPluginFilter($query);

            $row = $query->first();

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
            $query = DB::table('help_article')
                ->where('is_published', 1);

            self::applyAdminFilter($query);
            self::applyPluginFilter($query);

            return $query->select('category', DB::raw('COUNT(*) as article_count'))
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
            // Block non-admins from accessing admin-only categories
            if (!self::isAdmin() && in_array($category, self::ADMIN_CATEGORIES)) {
                return [];
            }

            $query = DB::table('help_article')
                ->where('category', $category)
                ->where('is_published', 1);

            self::applyPluginFilter($query);

            return $query->select('id', 'slug', 'title', 'subcategory', 'word_count', 'related_plugin', 'tags', 'updated_at')
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

            $q = DB::table('help_article')
                ->where('is_published', 1)
                ->whereRaw('MATCH(title, body_text) AGAINST(? IN BOOLEAN MODE)', [$query . '*']);

            self::applyAdminFilter($q);
            self::applyPluginFilter($q);

            return $q->select(
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

            $q = DB::table('help_section as hs')
                ->join('help_article as ha', 'hs.article_id', '=', 'ha.id')
                ->where('ha.is_published', 1)
                ->whereRaw('MATCH(hs.heading, hs.body_text) AGAINST(? IN BOOLEAN MODE)', [$query . '*']);

            if (!self::isAdmin()) {
                $q->whereNotIn('ha.category', self::ADMIN_CATEGORIES);
            }

            // Plugin filter on joined table alias
            $enabled = self::getEnabledPlugins();
            if (!empty($enabled)) {
                $q->where(function ($sub) use ($enabled) {
                    $sub->whereNull('ha.related_plugin')
                        ->orWhereIn('ha.related_plugin', $enabled);
                });
            }

            return $q->select(
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
     * Suggest help articles for an arbitrary request path.
     *
     * The curated context map in the help module only covers a handful of URL
     * patterns, so on most of the site F1 had nothing to open. This derives
     * candidates from the path instead, using related_plugin (set on 265 of the
     * articles), then slug and title.
     *
     * Returns ['results' => [...], 'best' => ['slug' => ..., 'title' => ...]|null].
     * 'best' is only set when the top hit belongs to the plugin the path names,
     * which is confident enough to open directly rather than offer as a list.
     */
    public static function suggestForPath(string $path, int $limit = 6): array
    {
        $empty = ['results' => [], 'best' => null];

        $tokens = self::pathTokens($path);
        if (empty($tokens)) {
            return $empty;
        }

        $primary = $tokens[0];
        $pluginGuess = 'ahg'.ucfirst($primary).'Plugin';

        try {
            $rows = DB::table('help_article')
                ->where('is_published', 1)
                ->where(function ($q) use ($tokens, $pluginGuess) {
                    $q->where('related_plugin', $pluginGuess);
                    foreach ($tokens as $t) {
                        $q->orWhere('related_plugin', 'like', '%'.$t.'%')
                            ->orWhere('slug', 'like', '%'.$t.'%')
                            ->orWhere('title', 'like', '%'.$t.'%');
                    }
                })
                ->select('slug', 'title', 'category', 'related_plugin')
                ->orderByRaw(
                    'CASE WHEN related_plugin = ? THEN 0'
                    .' WHEN slug LIKE ? THEN 1'
                    .' WHEN related_plugin LIKE ? THEN 2 ELSE 3 END',
                    [$pluginGuess, $primary.'%', '%'.$primary.'%']
                )
                // Someone pressing F1 wants the user guide, not the plugin's
                // technical reference (slugged as the bare plugin name).
                ->orderByRaw(
                    "CASE WHEN category = 'Plugin Reference' OR slug LIKE 'ahg%' THEN 1 ELSE 0 END"
                )
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Exception $e) {
            return $empty;
        }

        $best = null;
        if (!empty($rows) && 0 === strcasecmp((string) $rows[0]['related_plugin'], $pluginGuess)) {
            $best = ['slug' => $rows[0]['slug'], 'title' => $rows[0]['title']];
        }

        return ['results' => $rows, 'best' => $best];
    }

    /**
     * Reduce a request path to the one or two words worth searching on.
     *
     * Record slugs (which contain hyphens), numeric ids and generic CRUD verbs
     * carry no help signal, so they are dropped.
     */
    private static function pathTokens(string $path): array
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = preg_replace('#^/?index\.php#', '', (string) $path);

        $skip = ['index', 'edit', 'add', 'new', 'create', 'update', 'delete', 'view', 'show', 'admin', 'api'];
        $tokens = [];

        foreach (explode('/', trim((string) $path, '/')) as $i => $part) {
            $part = strtolower(trim($part));

            if ('' === $part || is_numeric($part) || in_array($part, $skip, true)) {
                continue;
            }

            // Beyond the first segment, a hyphenated value is a record slug.
            if ($i > 0 && str_contains($part, '-')) {
                continue;
            }

            $tokens[] = $part;

            if (count($tokens) >= 2) {
                break;
            }
        }

        return $tokens;
    }

    /**
     * Get recently updated articles.
     */
    public static function getRecentlyUpdated(int $limit = 5): array
    {
        try {
            $query = DB::table('help_article')
                ->where('is_published', 1);

            self::applyAdminFilter($query);
            self::applyPluginFilter($query);

            return $query->select('slug', 'title', 'category', 'subcategory', 'updated_at')
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
