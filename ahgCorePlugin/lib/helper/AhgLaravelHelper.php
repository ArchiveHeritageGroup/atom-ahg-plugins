<?php

/*
 * Theme-support helpers, held in ahgCorePlugin.
 *
 * These functions - ahg_get_subject_access_points and friends - are called by
 * ahgThemeB5Plugin templates, but lived only in ahgUiOverridesPlugin, which the
 * theme required by absolute path:
 *
 *   require_once sfConfig::get('sf_plugins_dir').'/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php';
 *
 * A bare require of a plugin that is not installed is an immediate fatal, so
 * viewing a record with access points died on any site that had the theme but
 * not ahgUiOverridesPlugin.
 *
 * Core is the dependency the theme can rely on. Every function is guarded with
 * function_exists so the original copy can still load alongside this one without
 * a redeclare error.
 */

use Illuminate\Database\Capsule\Manager as DB;

if (!function_exists('ahg_get_subject_access_points')) {
function ahg_get_subject_access_points($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    $subjectTaxonomyId = 35;
    
    return DB::table('object_term_relation as otr')
        ->join('term as t', 'otr.term_id', '=', 't.id')
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('t.id', '=', 'ti_en.id')->where('ti_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->leftJoin('slug', 't.id', '=', 'slug.object_id')
        ->where('otr.object_id', $resourceId)
        ->where('t.taxonomy_id', $subjectTaxonomyId)
        ->select(['t.id', 'slug.slug', DB::raw('COALESCE(ti.name, ti_en.name) as name')])
        ->orderBy(DB::raw('COALESCE(ti.name, ti_en.name)'))
        ->get()->toArray();
}
}

if (!function_exists('ahg_get_place_access_points')) {
function ahg_get_place_access_points($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    $placeTaxonomyId = 42;
    
    return DB::table('object_term_relation as otr')
        ->join('term as t', 'otr.term_id', '=', 't.id')
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('t.id', '=', 'ti.id')->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('t.id', '=', 'ti_en.id')->where('ti_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->leftJoin('slug', 't.id', '=', 'slug.object_id')
        ->where('otr.object_id', $resourceId)
        ->where('t.taxonomy_id', $placeTaxonomyId)
        ->select(['t.id', 'slug.slug', DB::raw('COALESCE(ti.name, ti_en.name) as name')])
        ->orderBy(DB::raw('COALESCE(ti.name, ti_en.name)'))
        ->get()->toArray();
}
}

if (!function_exists('ahg_get_actor_events')) {
function ahg_get_actor_events($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('event as e')
        ->join('actor as a', 'e.actor_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')->where('ai_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('e.type_id', '=', 'ti.id')->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('e.type_id', '=', 'ti_en.id')->where('ti_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
        ->where('e.object_id', $resourceId)
        ->whereNotNull('e.actor_id')
        ->select(['a.id', 'e.type_id', 'slug.slug',
            DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
            DB::raw('COALESCE(ti.name, ti_en.name) as event_type')])
        ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
        ->get()->toArray();
}
}

if (!function_exists('ahg_get_name_access_relations')) {
function ahg_get_name_access_relations($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('relation as r')
        ->join('actor as a', 'r.object_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')->where('ai_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
        ->where('r.subject_id', $resourceId)
        ->where('r.type_id', 161)
        ->select(['a.id', 'slug.slug',
            DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name')])
        ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
        ->get()->toArray();
}
}

if (!function_exists('ahg_get_collection_root_id')) {
function ahg_get_collection_root_id($resource): ?int
{
    if (!$resource || !isset($resource->lft) || !isset($resource->rgt)) {
        return $resource->id ?? null;
    }
    $root = DB::table('information_object')
        ->where('lft', '<=', $resource->lft)
        ->where('rgt', '>=', $resource->rgt)
        ->where('parent_id', 1)
        ->orderBy('lft')->first();
    return $root ? $root->id : ($resource->id ?? null);
}
}

if (!function_exists('ahg_has_digital_object')) {
function ahg_has_digital_object($resourceId): bool
{
    return $resourceId ? DB::table('digital_object')->where('object_id', $resourceId)->exists() : false;
}
}

if (!function_exists('ahg_show_inventory')) {
function ahg_show_inventory($resource): bool
{
    return ($resource && isset($resource->id)) ? DB::table('information_object')->where('parent_id', $resource->id)->exists() : false;
}
}

if (!function_exists('ahg_url_for_dc_export')) {
function ahg_url_for_dc_export($resource): string
{
    $slug = $resource->slug ?? null;
    return $slug ? url_for(['module' => 'sfDcPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml'])
                 : url_for(['module' => 'sfDcPlugin', 'action' => 'index', 'id' => $resource->id, 'sf_format' => 'xml']);
}
}

if (!function_exists('ahg_url_for_ead_export')) {
function ahg_url_for_ead_export($resource): string
{
    $slug = $resource->slug ?? null;
    return $slug ? url_for(['module' => 'sfEadPlugin', 'action' => 'index', 'slug' => $slug, 'sf_format' => 'xml'])
                 : url_for(['module' => 'sfEadPlugin', 'action' => 'index', 'id' => $resource->id, 'sf_format' => 'xml']);
}
}

if (!function_exists('ahg_resource_url')) {
function ahg_resource_url($resource, string $module, string $action): string
{
    $slug = is_object($resource) ? ($resource->slug ?? null) : null;
    return $slug ? url_for(['module' => $module, 'action' => $action, 'slug' => $slug])
                 : url_for(['module' => $module, 'action' => $action, 'id' => is_object($resource) ? ($resource->id ?? null) : $resource]);
}
}

if (!function_exists('ahg_is_plugin_enabled')) {
function ahg_is_plugin_enabled(string $pluginName): bool
{
    static $cache = [];
    if (isset($cache[$pluginName])) {
        return $cache[$pluginName];
    }
    try {
        $cache[$pluginName] = DB::table('atom_plugin')
            ->where('name', $pluginName)
            ->where('is_enabled', 1)
            ->exists();
    } catch (\Exception $e) {
        $cache[$pluginName] = false;
    }
    return $cache[$pluginName];
}
}
