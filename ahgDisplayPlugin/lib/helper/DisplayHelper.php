<?php
/**
 * Display helper functions for templates
 */

use AhgDisplay\Services\DisplayService;

function get_display_service(): DisplayService
{
    static $service;
    if (!$service) {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayService.php';
        $service = new DisplayService();
    }
    return $service;
}

function get_object_type(int $objectId): string
{
    return get_display_service()->getObjectType($objectId);
}

function get_display_data(int $objectId, ?string $profileCode = null, string $context = 'default'): array
{
    return get_display_service()->prepareForDisplay($objectId, $profileCode, $context);
}

function render_display_object(int $objectId, ?string $profileCode = null, string $context = 'default'): string
{
    $data = get_display_data($objectId, $profileCode, $context);
    if (empty($data)) return '';

    ob_start();
    include sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/templates/_displayObject.php';
    return ob_get_clean();
}

function get_field_value(object $object, string $fieldCode): mixed
{
    return get_display_service()->getFieldValue($object, $fieldCode);
}

function format_field_value(array $field): string
{
    $value = $field['value'];
    
    if (is_array($value)) {
        return implode(', ', $value);
    }
    
    if ($field['type'] === 'textarea') {
        return nl2br(htmlspecialchars($value));
    }
    
    if ($field['type'] === 'date' && $value) {
        return date('Y-m-d', strtotime($value));
    }
    
    return htmlspecialchars($value);
}

function get_level_icon(string $levelCode): string
{
    $level = get_display_service()->getLevel($levelCode);
    return $level->icon ?? 'fa-file';
}

function get_type_icon(string $objectType): string
{
    return match($objectType) {
        'archive' => 'fa-archive',
        'museum' => 'fa-landmark',
        'gallery' => 'fa-palette',
        'library' => 'fa-book',
        'dam' => 'fa-images',
        'audiovisual' => 'fa-film',
        default => 'fa-folder',
    };
}

function get_type_color(string $objectType): string
{
    return match($objectType) {
        'archive' => 'primary',
        'museum' => 'success',
        'gallery' => 'warning',
        'library' => 'info',
        'dam' => 'danger',
        'audiovisual' => 'secondary',
        default => 'dark',
    };
}

function get_layout_icon(string $layoutMode): string
{
    return match($layoutMode) {
        'grid' => 'fa-th',
        'list' => 'fa-list',
        'gallery' => 'fa-image',
        'hierarchy' => 'fa-sitemap',
        'masonry' => 'fa-th-large',
        'catalog' => 'fa-book-open',
        'card' => 'fa-id-card',
        default => 'fa-file-alt',
    };
}
