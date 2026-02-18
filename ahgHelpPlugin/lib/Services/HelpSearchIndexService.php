<?php

namespace AhgHelp\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Builds the FlexSearch JSON index for client-side instant search.
 *
 * Generates a compact index with truncated content suitable for
 * browser-side FlexSearch (~200KB for 160+ articles).
 */
class HelpSearchIndexService
{
    /**
     * Build FlexSearch Document index data.
     *
     * @return array {documents: [{id, slug, title, category, subcategory, content}]}
     */
    public static function buildFlexSearchIndex(): array
    {
        try {
            $articles = DB::table('help_article')
                ->where('is_published', 1)
                ->select('id', 'slug', 'title', 'category', 'subcategory', 'body_text')
                ->orderBy('title')
                ->get();

            $documents = [];
            foreach ($articles as $article) {
                // Truncate body to ~500 chars to keep index size manageable
                $content = mb_substr($article->body_text, 0, 500);

                // Get section headings for this article
                $headings = DB::table('help_section')
                    ->where('article_id', $article->id)
                    ->pluck('heading')
                    ->implode(' ');

                $documents[] = [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'category' => $article->category,
                    'subcategory' => $article->subcategory ?? '',
                    'headings' => $headings,
                    'content' => $content,
                ];
            }

            return ['documents' => $documents];
        } catch (\Exception $e) {
            error_log('ahgHelpPlugin buildFlexSearchIndex error: ' . $e->getMessage());

            return ['documents' => []];
        }
    }
}
