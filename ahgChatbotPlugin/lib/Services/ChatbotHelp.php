<?php

namespace AhgChatbotPlugin\Services;

use AtomExtensions\Services\AhgSettingsService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * "Help using the site": answers how-to questions from the help articles that
 * ahgHelpPlugin keeps in help_article / help_section. Read only - that plugin
 * is not touched, and when its tables are absent the help mode is simply not
 * offered.
 *
 * Most of the corpus is written for staff or system administrators, and some
 * of it is internal (plugin references, server setup, security reports, plans).
 * Both audiences therefore get an ALLOW-list of categories, so a new category
 * stays out until someone chooses to add it:
 *   - visitors and researchers: chatbot_help_categories_public;
 *   - editors, contributors and administrators: chatbot_help_categories_staff.
 */
class ChatbotHelp
{
    private const MAX_SECTIONS = 6;
    private const CANDIDATES = 20;
    private const MAX_CHARS = 5000;
    private const PUBLIC_CATEGORIES = 'Public Access,Browse & Search,Research,Viewers & Media';
    private const STAFF_CATEGORIES = 'User Guide,User Manual,Admin & Settings,Collection Mgmt,Import/Export,Rights,Compliance,Exhibitions,GLAM Sectors,Labels & Forms,AI & Automation';

    private static ?bool $available = null;

    public static function available(): bool
    {
        if (null === self::$available) {
            try {
                self::$available = DB::getSchemaBuilder()->hasTable('help_section')
                    && DB::table('help_article')->where('is_published', 1)->exists();
            } catch (\Throwable $e) {
                self::$available = false;
            }
        }

        return self::$available;
    }

    /**
     * @return array<int,object> rows: slug, title, heading, anchor, text
     */
    public static function retrieve(string $question, bool $staff): array
    {
        if (!self::available()) {
            return [];
        }

        try {
            $rows = self::match($question, 'NATURAL LANGUAGE MODE', $staff);
            if ([] === $rows) {
                $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY);
                $prefixes = array_unique(array_map(static fn ($w) => preg_replace('/(ies|es|s)$/u', '', $w).'*',
                    array_filter($words, static fn ($w) => mb_strlen($w) >= 4)));
                $rows = [] === $prefixes ? [] : self::match(implode(' ', $prefixes), 'BOOLEAN MODE', $staff);
            }
        } catch (\Throwable $e) {
            error_log('chatbot.help_failed: '.$e->getMessage());

            return [];
        }

        // At most two sections per article, so one long manual cannot fill the context.
        $out = [];
        $perArticle = [];
        foreach ($rows as $r) {
            if (($perArticle[$r->slug] ?? 0) >= 2) {
                continue;
            }
            $perArticle[$r->slug] = ($perArticle[$r->slug] ?? 0) + 1;
            $out[] = $r;
            if (count($out) >= self::MAX_SECTIONS) {
                break;
            }
        }

        return $out;
    }

    /** @param array<int,object> $rows */
    public static function context(array $rows): string
    {
        if ([] === $rows) {
            return 'No help article covers this question.';
        }
        $out = '';
        foreach ($rows as $r) {
            $block = '--- '.$r->title.' > '.$r->heading." ---\n".mb_substr(trim((string) $r->text), 0, 1200)."\n\n";
            if (strlen($out) + strlen($block) > self::MAX_CHARS) {
                break;
            }
            $out .= $block;
        }

        return $out;
    }

    /** @param array<int,object> $rows */
    public static function sources(array $rows, string $root): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            if (isset($seen[$r->slug])) {
                continue;
            }
            $seen[$r->slug] = true;
            $out[] = [
                'slug' => $r->slug,
                'title' => $r->title,
                'identifier' => null,
                'thumbnail' => null,
                'url' => $root.'/help/article/'.rawurlencode($r->slug).($r->anchor ? '#'.rawurlencode($r->anchor) : ''),
            ];
        }

        return $out;
    }

    /** @return array<int,object> */
    private static function match(string $terms, string $mode, bool $staff): array
    {
        $q = DB::table('help_section as hs')
            ->join('help_article as ha', 'ha.id', '=', 'hs.article_id')
            ->where('ha.is_published', 1)
            ->whereRaw("MATCH(hs.heading, hs.body_text) AGAINST(? IN {$mode})", [$terms])
            // Section relevance only: adding the article's score let one long
            // guide fill every slot (tested on PSIS, 2026-10-07).
            // ponytail: full-text cannot find "zoom" in a guide that says
            // "magnify"; embedding help sections into Qdrant is the upgrade.
            ->orderByRaw("MATCH(hs.heading, hs.body_text) AGAINST(? IN {$mode}) DESC", [$terms])
            ->limit(self::CANDIDATES);

        $q->whereIn('ha.category', $staff
            ? self::list('chatbot_help_categories_staff', self::PUBLIC_CATEGORIES.','.self::STAFF_CATEGORIES)
            : self::list('chatbot_help_categories_public', self::PUBLIC_CATEGORIES));

        return $q->get(['ha.slug', 'ha.title', 'hs.heading', 'hs.anchor', 'hs.body_text as text'])->all();
    }

    /** @return string[] */
    private static function list(string $key, string $default): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) AhgSettingsService::get($key, $default)))));
    }
}
