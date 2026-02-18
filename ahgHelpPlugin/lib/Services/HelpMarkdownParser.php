<?php

namespace AhgHelp\Services;

/**
 * Markdown to HTML parser with TOC extraction and section splitting.
 *
 * Uses ParsedownExtra (already vendored) to render markdown.
 * Extracts heading structure for Table of Contents and section-level search.
 */
class HelpMarkdownParser
{
    protected static $parsedown = null;

    /**
     * Parse markdown content into structured output.
     *
     * @param string $markdown Raw markdown content
     *
     * @return array{title: string, body_html: string, body_text: string, toc: array, sections: array, word_count: int}
     */
    public static function parse(string $markdown): array
    {
        $parsedown = self::getParsedown();

        // Extract title from first H1
        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
            $title = trim($m[1]);
        }

        // Render markdown to HTML
        $html = $parsedown->text($markdown);

        // Extract TOC and inject anchor IDs on headings
        $toc = [];
        $html = preg_replace_callback(
            '/<h([2-3])>(.*?)<\/h\1>/i',
            function ($match) use (&$toc) {
                $level = (int) $match[1];
                $text = strip_tags($match[2]);
                $anchor = self::slugify($text);

                $toc[] = [
                    'level' => $level,
                    'text' => $text,
                    'anchor' => $anchor,
                ];

                return '<h' . $level . ' id="' . htmlspecialchars($anchor, ENT_QUOTES) . '">' . $match[2] . '</h' . $level . '>';
            },
            $html
        );

        // Also inject ID on H1
        $html = preg_replace_callback(
            '/<h1>(.*?)<\/h1>/i',
            function ($match) {
                $text = strip_tags($match[1]);
                $anchor = self::slugify($text);

                return '<h1 id="' . htmlspecialchars($anchor, ENT_QUOTES) . '">' . $match[1] . '</h1>';
            },
            $html
        );

        // Add Bootstrap table classes
        $html = str_replace('<table>', '<table class="table table-striped table-sm">', $html);

        // Strip HTML for plain text (FULLTEXT search)
        $bodyText = self::htmlToText($html);

        // Split into sections by H2/H3 headings
        $sections = self::extractSections($markdown);

        $wordCount = str_word_count($bodyText);

        return [
            'title' => $title,
            'body_html' => $html,
            'body_text' => $bodyText,
            'toc' => $toc,
            'sections' => $sections,
            'word_count' => $wordCount,
        ];
    }

    /**
     * Extract sections split by H2/H3 headings.
     *
     * @return array Array of {heading, anchor, level, body_text}
     */
    protected static function extractSections(string $markdown): array
    {
        $sections = [];
        // Split on heading lines (## or ###)
        $parts = preg_split('/^(#{2,3})\s+(.+)$/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

        // parts: [preamble, ##, heading, body, ##, heading, body, ...]
        for ($i = 1; $i + 2 < count($parts); $i += 3) {
            $level = strlen($parts[$i]); // 2 or 3
            $heading = trim($parts[$i + 1]);
            $body = trim($parts[$i + 2]);
            $anchor = self::slugify($heading);

            // Convert section body to plain text
            $parsedown = self::getParsedown();
            $sectionHtml = $parsedown->text($body);
            $sectionText = self::htmlToText($sectionHtml);

            $sections[] = [
                'heading' => $heading,
                'anchor' => $anchor,
                'level' => $level,
                'body_text' => $sectionText,
            ];
        }

        return $sections;
    }

    /**
     * Convert HTML to plain text, stripping all tags.
     */
    protected static function htmlToText(string $html): string
    {
        // Remove code blocks first (they add noise to search)
        $text = preg_replace('/<pre[^>]*>.*?<\/pre>/si', ' ', $html);
        $text = preg_replace('/<code[^>]*>.*?<\/code>/si', ' ', $text);

        // Strip all remaining HTML
        $text = strip_tags($text);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Generate URL-safe slug from heading text.
     */
    public static function slugify(string $text): string
    {
        $slug = mb_strtolower($text, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Get or create Parsedown instance.
     */
    protected static function getParsedown(): \ParsedownExtra
    {
        if (self::$parsedown === null) {
            $vendorDir = \sfConfig::get('sf_root_dir') . '/vendor/parsedown';
            if (!class_exists('Parsedown')) {
                require_once $vendorDir . '/Parsedown.php';
            }
            if (!class_exists('ParsedownExtra')) {
                require_once $vendorDir . '/ParsedownExtra.php';
            }
            self::$parsedown = new \ParsedownExtra();
            self::$parsedown->setSafeMode(false);
            self::$parsedown->setMarkupEscaped(false);
        }

        return self::$parsedown;
    }
}
