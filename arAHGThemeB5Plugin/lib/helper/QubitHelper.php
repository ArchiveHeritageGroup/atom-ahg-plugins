<?php

/**
 * QubitHelper - Wrapper for backward compatibility
 * Delegates to AtomFramework\Helpers\QubitHelper
 */

require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/QubitHelper.php';

use AtomFramework\Helpers\QubitHelper as FrameworkQubitHelper;

function format_script($script_iso, $culture = null)
{
    return FrameworkQubitHelper::formatScript($script_iso, $culture);
}

function render_field($field, $resource = null, array $options = [])
{
    return FrameworkQubitHelper::renderField($field, $resource, $options);
}

function render_title($resource, $fallback = true)
{
    if (null === $resource || '' === $resource) {
        return '';
    }

    if (is_string($resource)) {
        return esc_specialchars($resource);
    }

    if (is_array($resource)) {
        return esc_specialchars($resource['title'] ?? $resource['name'] ?? '');
    }

    // Use methods - safer than property access on AtoM objects
    if (method_exists($resource, 'getTitle')) {
        try {
            $title = $resource->getTitle(['cultureFallback' => $fallback]);
            if (!empty($title)) {
                return esc_specialchars($title);
            }
        } catch (Exception $e) {}
    }

    if (method_exists($resource, 'getAuthorizedFormOfName')) {
        try {
            $name = $resource->getAuthorizedFormOfName(['cultureFallback' => $fallback]);
            if (!empty($name)) {
                return esc_specialchars($name);
            }
        } catch (Exception $e) {}
    }

    if (method_exists($resource, 'getName')) {
        try {
            $name = $resource->getName(['cultureFallback' => $fallback]);
            if (!empty($name)) {
                return esc_specialchars($name);
            }
        } catch (Exception $e) {}
    }

    if (method_exists($resource, 'getLabel')) {
        try {
            $label = $resource->getLabel();
            if (!empty($label)) {
                return esc_specialchars($label);
            }
        } catch (Exception $e) {}
    }

    if (method_exists($resource, '__toString')) {
        try {
            $str = (string) $resource;
            if (!empty($str)) {
                return esc_specialchars($str);
            }
        } catch (Exception $e) {}
    }

    if (method_exists($resource, 'getSlug')) {
        try {
            $slug = $resource->getSlug();
            if (!empty($slug)) {
                return esc_specialchars($slug);
            }
        } catch (Exception $e) {}
    }

    return '';
}

function get_search_i18n($doc, $field, $options = [])
{
    $allowEmpty = $options['allowEmpty'] ?? true;
    
    try {
        $culture = sfContext::getInstance()->getUser()->getCulture();
    } catch (Exception $e) {
        $culture = 'en';
    }

    // Try culture-specific nested field
    if (isset($doc['i18n'][$culture][$field]) && !empty($doc['i18n'][$culture][$field])) {
        return $doc['i18n'][$culture][$field];
    }

    // Try direct field
    if (isset($doc[$field]) && !empty($doc[$field])) {
        return $doc[$field];
    }

    // Try any language fallback
    if (isset($doc['i18n']) && is_array($doc['i18n'])) {
        foreach ($doc['i18n'] as $lang => $fields) {
            if (isset($fields[$field]) && !empty($fields[$field])) {
                return $fields[$field];
            }
        }
    }

    return $allowEmpty ? '' : '[Untitled]';
}

function render_value_inline($value)
{
    return empty($value) ? '' : esc_specialchars($value);
}

function render_value($value)
{
    return empty($value) ? '' : esc_specialchars($value);
}

function render_value_html($value)
{
    return empty($value) ? '' : $value;
}

function render_show($label, $value, $options = [])
{
    if (empty($value) && empty($options['allowEmpty'])) {
        return '';
    }
    $class = !empty($options['isSubField']) ? 'field subfield' : 'field';
    $html = '<div class="'.$class.'">';
    $html .= '<h3>'.esc_specialchars($label).'</h3>';
    $html .= '<div class="value">'.$value.'</div>';
    $html .= '</div>';
    return $html;
}

function check_field_visibility($fieldName, $options = [])
{
    return true;
}

function strip_markdown($text)
{
    if (empty($text)) {
        return '';
    }
    $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
    $text = preg_replace('/[*_]{1,3}([^*_]+)[*_]{1,3}/', '$1', $text);
    $text = preg_replace('/^#+\s*/m', '', $text);
    $text = preg_replace('/`([^`]+)`/', '$1', $text);
    return strip_tags($text);
}
