<?php

/**
 * Heritage Helper functions for Symfony templates.
 *
 * Provides common utility functions for Heritage Platform templates.
 *
 * Note: esc_specialchars() is already available from Symfony's EscapingHelper
 */

/**
 * Get entity type color.
 *
 * @param string $type Entity type
 *
 * @return string Hex color code
 */
function heritage_entity_color(string $type): string
{
    $colors = [
        'person' => '#4e79a7',
        'organization' => '#59a14f',
        'place' => '#e15759',
        'date' => '#b07aa1',
        'event' => '#76b7b2',
        'work' => '#ff9da7',
        'concept' => '#edc949',
    ];

    return $colors[$type] ?? '#999999';
}

/**
 * Get entity type icon class.
 *
 * @param string $type Entity type
 *
 * @return string Bootstrap icon class
 */
function heritage_entity_icon(string $type): string
{
    $icons = [
        'person' => 'bi-person-fill',
        'organization' => 'bi-building-fill',
        'place' => 'bi-geo-alt-fill',
        'date' => 'bi-calendar-fill',
        'event' => 'bi-calendar-event-fill',
        'work' => 'bi-file-richtext-fill',
        'concept' => 'bi-lightbulb-fill',
    ];

    return $icons[$type] ?? 'bi-tag-fill';
}

/**
 * Format confidence score as percentage.
 *
 * @param float|null $confidence Confidence score (0-1)
 *
 * @return string Formatted percentage
 */
function heritage_confidence_percent(?float $confidence): string
{
    if (null === $confidence) {
        return '0%';
    }

    return round($confidence * 100).'%';
}

/**
 * Get confidence badge class.
 *
 * @param float|null $confidence Confidence score (0-1)
 *
 * @return string Bootstrap badge class
 */
function heritage_confidence_badge(?float $confidence): string
{
    if (null === $confidence) {
        return 'bg-secondary';
    }

    if ($confidence >= 0.9) {
        return 'bg-success';
    }
    if ($confidence >= 0.7) {
        return 'bg-warning text-dark';
    }

    return 'bg-secondary';
}

/**
 * Truncate text to specified length.
 *
 * @param string|null $text   The text to truncate
 * @param int         $length Maximum length
 * @param string      $suffix Suffix to append if truncated
 *
 * @return string Truncated text
 */
function heritage_truncate(?string $text, int $length = 100, string $suffix = '...'): string
{
    if (null === $text) {
        return '';
    }

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length - mb_strlen($suffix)).$suffix;
}

/**
 * Format a count with appropriate suffix.
 *
 * @param int    $count  The count
 * @param string $single Singular label
 * @param string $plural Plural label
 *
 * @return string Formatted string
 */
function heritage_count_label(int $count, string $single, string $plural): string
{
    return number_format($count).' '.($count === 1 ? $single : $plural);
}

/**
 * Generate JSON attributes for HTML element.
 *
 * @param array $data Data to encode
 *
 * @return string HTML-safe JSON string
 */
function heritage_json_attr(array $data): string
{
    return esc_specialchars(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
}

/**
 * Format a relative time string.
 *
 * @param string|null $datetime DateTime string
 *
 * @return string Relative time
 */
function heritage_relative_time(?string $datetime): string
{
    if (null === $datetime) {
        return 'Unknown';
    }

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = floor($diff / 60);

        return $mins.' '.($mins === 1 ? 'minute' : 'minutes').' ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);

        return $hours.' '.($hours === 1 ? 'hour' : 'hours').' ago';
    }
    if ($diff < 604800) {
        $days = floor($diff / 86400);

        return $days.' '.($days === 1 ? 'day' : 'days').' ago';
    }

    return date('M j, Y', $timestamp);
}
