<?php

/**
 * HTML Sanitizer for Report Builder.
 *
 * Provides XSS-safe HTML sanitization for Quill.js editor output.
 * Uses an allowlist approach with no external dependencies.
 */
class HtmlSanitizer
{
    /**
     * Allowed HTML tags and their permitted attributes.
     */
    private static array $allowedTags = [
        'p' => ['class', 'style'],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => ['class'],
        'u' => [],
        's' => [],
        'strike' => [],
        'sub' => [],
        'sup' => [],
        'h1' => ['class'],
        'h2' => ['class'],
        'h3' => ['class'],
        'h4' => ['class'],
        'h5' => ['class'],
        'h6' => ['class'],
        'blockquote' => ['class'],
        'pre' => ['class'],
        'code' => ['class'],
        'ul' => ['class'],
        'ol' => ['class', 'start', 'type'],
        'li' => ['class', 'data-list'],
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'width', 'height', 'class'],
        'table' => ['class', 'border'],
        'thead' => [],
        'tbody' => [],
        'tr' => ['class'],
        'th' => ['class', 'colspan', 'rowspan'],
        'td' => ['class', 'colspan', 'rowspan'],
        'div' => ['class'],
        'span' => ['class', 'style'],
        'hr' => [],
    ];

    /**
     * Allowed CSS properties for inline styles.
     */
    private static array $allowedStyles = [
        'color',
        'background-color',
        'font-size',
        'font-weight',
        'font-style',
        'text-align',
        'text-decoration',
        'text-indent',
        'line-height',
        'margin',
        'margin-left',
        'margin-right',
        'padding',
        'padding-left',
        'padding-right',
        'list-style-type',
        'width',
        'height',
        'max-width',
        'border',
    ];

    /**
     * Sanitize HTML content from the editor.
     *
     * @param string|null $html The HTML to sanitize
     *
     * @return string The sanitized HTML
     */
    public static function sanitize(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Remove null bytes
        $html = str_replace("\0", '', $html);

        // Build allowed tags string for strip_tags
        $allowedTagStr = '';
        foreach (array_keys(self::$allowedTags) as $tag) {
            $allowedTagStr .= "<{$tag}>";
        }

        // Strip disallowed tags
        $html = strip_tags($html, $allowedTagStr);

        // Process remaining tags to filter attributes
        $html = preg_replace_callback(
            '/<(\w+)((?:\s+[^>]*)?)>/s',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attrString = $matches[2] ?? '';

                if (!isset(self::$allowedTags[$tag])) {
                    return '';
                }

                $allowedAttrs = self::$allowedTags[$tag];
                $cleanAttrs = self::filterAttributes($attrString, $allowedAttrs);

                return "<{$tag}{$cleanAttrs}>";
            },
            $html
        );

        // Sanitize href attributes (prevent javascript: protocol)
        $html = preg_replace_callback(
            '/href\s*=\s*"([^"]*)"/i',
            function ($matches) {
                $url = trim($matches[1]);
                $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

                // Only allow http, https, mailto, and # (anchor)
                if (preg_match('/^(https?:\/\/|mailto:|#)/i', $url) || $url === '') {
                    return 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
                }

                return 'href="#"';
            },
            $html
        );

        // Sanitize src attributes (prevent javascript: protocol)
        $html = preg_replace_callback(
            '/src\s*=\s*"([^"]*)"/i',
            function ($matches) {
                $url = trim($matches[1]);
                $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

                // Only allow http, https, data:image, and relative paths
                if (preg_match('/^(https?:\/\/|data:image\/|\/)/i', $url)) {
                    return 'src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';
                }

                return 'src=""';
            },
            $html
        );

        return $html;
    }

    /**
     * Filter HTML attributes, keeping only allowed ones.
     *
     * @param string $attrString    The attribute string from the tag
     * @param array  $allowedAttrs  List of allowed attribute names
     *
     * @return string The filtered attribute string
     */
    private static function filterAttributes(string $attrString, array $allowedAttrs): string
    {
        if (empty($attrString) || empty($allowedAttrs)) {
            return '';
        }

        $cleanAttrs = '';

        // Match attribute="value" pairs
        preg_match_all(
            '/(\w[\w-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/s',
            $attrString,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $attrName = strtolower($match[1]);
            $attrValue = $match[2] ?? $match[3] ?? $match[4] ?? '';

            if (!in_array($attrName, $allowedAttrs)) {
                continue;
            }

            // Special handling for style attribute
            if ($attrName === 'style') {
                $attrValue = self::sanitizeStyle($attrValue);
                if (empty($attrValue)) {
                    continue;
                }
            }

            // Special handling for class attribute
            if ($attrName === 'class') {
                $attrValue = self::sanitizeClass($attrValue);
            }

            $cleanAttrs .= " {$attrName}=\"" . htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $cleanAttrs;
    }

    /**
     * Sanitize CSS style values.
     *
     * @param string $style The style string
     *
     * @return string The sanitized style string
     */
    private static function sanitizeStyle(string $style): string
    {
        $cleanParts = [];

        // Split into individual properties
        $parts = explode(';', $style);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            $colonPos = strpos($part, ':');
            if ($colonPos === false) {
                continue;
            }

            $property = strtolower(trim(substr($part, 0, $colonPos)));
            $value = trim(substr($part, $colonPos + 1));

            // Only allow known CSS properties
            if (!in_array($property, self::$allowedStyles)) {
                continue;
            }

            // Reject values with dangerous patterns
            if (preg_match('/expression|javascript|vbscript|url\s*\(/i', $value)) {
                continue;
            }

            $cleanParts[] = "{$property}: {$value}";
        }

        return implode('; ', $cleanParts);
    }

    /**
     * Sanitize CSS class names.
     *
     * @param string $classStr The class string
     *
     * @return string The sanitized class string
     */
    private static function sanitizeClass(string $classStr): string
    {
        // Only allow alphanumeric, hyphens, underscores
        $classes = preg_split('/\s+/', $classStr);
        $clean = [];
        foreach ($classes as $class) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $class)) {
                $clean[] = $class;
            }
        }

        return implode(' ', $clean);
    }

    /**
     * Convert HTML to plain text (for PDF/Word fallback).
     *
     * @param string|null $html The HTML content
     *
     * @return string Plain text
     */
    public static function toPlainText(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Convert common elements to text equivalents
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $text = str_replace('</p>', "\n\n", $text);
        $text = str_replace('</li>', "\n", $text);
        $text = str_replace(['</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n\n", $text);

        // Strip all remaining tags
        $text = strip_tags($text);

        // Clean up whitespace
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
