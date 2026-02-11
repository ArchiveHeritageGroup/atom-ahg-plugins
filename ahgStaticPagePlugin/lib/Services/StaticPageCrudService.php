<?php

namespace AhgStaticPage\Services;

use AhgCore\Services\I18nService;
use AhgCore\Services\ObjectService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Static Page CRUD Service
 *
 * Pure Laravel Query Builder implementation for static page operations.
 * Static pages use: object, static_page, static_page_i18n, and slug tables.
 */
class StaticPageCrudService
{
    /**
     * Protected slugs that cannot be deleted or have their slug changed.
     */
    protected const PROTECTED_SLUGS = ['home'];

    /**
     * Get all static pages with i18n data, ordered by title.
     *
     * @return array List of static page records
     */
    public static function getAll(string $culture = 'en'): array
    {
        try {
            return DB::table('static_page as sp')
                ->leftJoin('static_page_i18n as spi', function ($join) use ($culture) {
                    $join->on('sp.id', '=', 'spi.id')
                        ->where('spi.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'sp.id', '=', 's.object_id')
                ->select(
                    'sp.id',
                    'sp.source_culture',
                    'spi.title',
                    'spi.content',
                    's.slug'
                )
                ->orderBy('spi.title')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->all();
        } catch (\Exception $e) {
            error_log('ahgStaticPagePlugin getAll error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get a single static page by ID with i18n data.
     *
     * @return array|null Page data or null if not found
     */
    public static function getById(int $id, string $culture = 'en'): ?array
    {
        try {
            $page = DB::table('static_page as sp')
                ->join('object as o', 'sp.id', '=', 'o.id')
                ->where('sp.id', $id)
                ->where('o.class_name', 'QubitStaticPage')
                ->select(
                    'sp.id',
                    'sp.source_culture',
                    'o.created_at',
                    'o.updated_at',
                    'o.serial_number'
                )
                ->first();

            if (!$page) {
                return null;
            }

            $i18n = I18nService::getWithFallback('static_page_i18n', $id, $culture);
            $slug = ObjectService::getSlug($id);

            return [
                'id' => $page->id,
                'title' => $i18n->title ?? '',
                'content' => $i18n->content ?? '',
                'slug' => $slug,
                'sourceCulture' => $page->source_culture,
                'createdAt' => $page->created_at,
                'updatedAt' => $page->updated_at,
                'serialNumber' => $page->serial_number,
            ];
        } catch (\Exception $e) {
            error_log('ahgStaticPagePlugin getById error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Resolve the 'home' slug to its static page ID.
     *
     * @return int|null The home page ID or null if not found
     */
    public static function getHomePageId(): ?int
    {
        return ObjectService::resolveSlug('home');
    }

    /**
     * Create a new static page.
     *
     * @return int The new static page ID
     */
    public static function create(array $data, string $culture = 'en'): int
    {
        return DB::transaction(function () use ($data, $culture) {
            // 1. Create object record
            $id = ObjectService::create('QubitStaticPage');

            // 2. Create static_page record
            DB::table('static_page')->insert([
                'id' => $id,
                'source_culture' => $culture,
            ]);

            // 3. Create static_page_i18n record
            $i18nData = [];
            if (array_key_exists('title', $data)) {
                $i18nData['title'] = $data['title'];
            }
            if (array_key_exists('content', $data)) {
                $i18nData['content'] = $data['content'];
            }
            if (!empty($i18nData)) {
                I18nService::save('static_page_i18n', $id, $culture, $i18nData);
            }

            // 4. Generate slug from title or provided slug
            $slugBasis = !empty($data['slug']) ? $data['slug'] : ($data['title'] ?? null);
            ObjectService::generateSlug($id, $slugBasis);

            return $id;
        });
    }

    /**
     * Update an existing static page.
     */
    public static function update(int $id, array $data, string $culture = 'en'): void
    {
        DB::transaction(function () use ($id, $data, $culture) {
            // 1. Update static_page_i18n
            $i18nData = [];
            if (array_key_exists('title', $data)) {
                $i18nData['title'] = $data['title'];
            }
            if (array_key_exists('content', $data)) {
                $i18nData['content'] = $data['content'];
            }
            if (!empty($i18nData)) {
                I18nService::save('static_page_i18n', $id, $culture, $i18nData);
            }

            // 2. Update slug if provided and not protected
            if (array_key_exists('slug', $data) && !empty($data['slug'])) {
                if (!self::isProtected($id)) {
                    // Only update if slug actually changed
                    $currentSlug = ObjectService::getSlug($id);
                    if ($currentSlug !== $data['slug']) {
                        ObjectService::generateSlug($id, $data['slug']);
                    }
                }
            }

            // 3. Touch the object record
            ObjectService::touch($id);
            ObjectService::incrementSerialNumber($id);
        });
    }

    /**
     * Delete a static page and all related data.
     *
     * @throws \RuntimeException if the page is protected
     */
    public static function delete(int $id): void
    {
        if (self::isProtected($id)) {
            throw new \RuntimeException('Cannot delete a protected static page.');
        }

        DB::transaction(function () use ($id) {
            // 1. Delete static_page_i18n
            I18nService::delete('static_page_i18n', $id);

            // 2. Delete static_page record
            DB::table('static_page')->where('id', $id)->delete();

            // 3. Delete slug + object
            ObjectService::deleteObject($id);
        });
    }

    /**
     * Check if a static page is protected (cannot be deleted or have slug changed).
     */
    public static function isProtected(int $id): bool
    {
        $slug = ObjectService::getSlug($id);

        if (!$slug) {
            return false;
        }

        return in_array($slug, self::PROTECTED_SLUGS, true);
    }
}
