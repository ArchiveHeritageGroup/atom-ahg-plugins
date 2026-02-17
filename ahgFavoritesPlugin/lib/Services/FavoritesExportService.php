<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

require_once dirname(__DIR__).'/Repositories/FavoritesRepository.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Repositories\FavoritesRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Favorites Export Service - CSV, PDF, BibTeX, RIS, JSON, Print HTML
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FavoritesExportService
{
    private FavoritesRepository $repository;

    public function __construct()
    {
        $this->repository = new FavoritesRepository();
    }

    /**
     * Get the current user culture
     */
    private function getCulture(): string
    {
        try {
            return \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Exception $e) {
            return 'en';
        }
    }

    /**
     * Get enriched favourites with full metadata for export
     */
    public function getEnrichedFavorites(int $userId, ?int $folderId = null): array
    {
        $params = ['limit' => 10000];
        if ($folderId) {
            $params['folder_id'] = $folderId;
        }

        $result = $this->repository->browse($userId, $params);
        $culture = $this->getCulture();
        $enriched = [];

        foreach ($result['hits'] as $fav) {
            $objectId = $fav->archival_description_id;

            // Check object still exists
            if (!$objectId || !DB::table('object')->where('id', $objectId)->exists()) {
                continue;
            }

            // Resolve title
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->value('title');
            if (!$title && $culture !== 'en') {
                $title = DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', 'en')
                    ->value('title');
            }

            // Resolve slug
            $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');

            // Get IO details
            $io = DB::table('information_object')
                ->where('id', $objectId)
                ->first();

            // Level of description
            $lod = null;
            if ($io && $io->level_of_description_id) {
                $lod = DB::table('term_i18n')
                    ->where('id', $io->level_of_description_id)
                    ->where('culture', $culture)
                    ->value('name');
                if (!$lod && $culture !== 'en') {
                    $lod = DB::table('term_i18n')
                        ->where('id', $io->level_of_description_id)
                        ->where('culture', 'en')
                        ->value('name');
                }
            }

            // Date range
            $dateRange = '';
            if ($io) {
                $parts = [];
                if (!empty($io->start_date)) {
                    $parts[] = $io->start_date;
                }
                if (!empty($io->end_date)) {
                    $parts[] = $io->end_date;
                }
                $dateRange = implode(' - ', $parts);
            }

            // Repository name
            $repositoryName = '';
            if ($io && $io->repository_id) {
                $repositoryName = DB::table('actor_i18n')
                    ->where('id', $io->repository_id)
                    ->where('culture', $culture)
                    ->value('authorized_form_of_name') ?? '';
                if (!$repositoryName && $culture !== 'en') {
                    $repositoryName = DB::table('actor_i18n')
                        ->where('id', $io->repository_id)
                        ->where('culture', 'en')
                        ->value('authorized_form_of_name') ?? '';
                }
            }

            // Folder name
            $folderName = '';
            if ($fav->folder_id) {
                $folderName = DB::table('favorites_folder')
                    ->where('id', $fav->folder_id)
                    ->value('name') ?? '';
            }

            $enriched[] = [
                'id' => $fav->id,
                'title' => $title ?? $fav->archival_description ?? 'Untitled',
                'reference_code' => $fav->reference_code ?? ($io->identifier ?? ''),
                'level_of_description' => $lod ?? '',
                'date_range' => $dateRange,
                'repository' => $repositoryName,
                'slug' => $slug ?? $fav->slug ?? '',
                'notes' => $fav->notes ?? '',
                'folder' => $folderName,
                'object_type' => $fav->object_type ?? 'information_object',
                'created_at' => $fav->created_at,
            ];
        }

        return $enriched;
    }

    /**
     * Export as CSV — returns temp file path
     */
    public function exportCsv(int $userId, ?int $folderId = null): string
    {
        $items = $this->getEnrichedFavorites($userId, $folderId);

        $tmpFile = tempnam(sys_get_temp_dir(), 'fav_') . '.csv';
        $fp = fopen($tmpFile, 'w');

        // UTF-8 BOM for Excel compatibility
        fwrite($fp, "\xEF\xBB\xBF");

        // Header
        fputcsv($fp, [
            \__('Title'), \__('Reference Code'), \__('Level'), \__('Dates'), \__('Repository'),
            \__('Slug'), \__('Notes'), \__('Folder'), \__('Date Added'),
        ]);

        foreach ($items as $item) {
            fputcsv($fp, [
                $item['title'],
                $item['reference_code'],
                $item['level_of_description'],
                $item['date_range'],
                $item['repository'],
                $item['slug'],
                $item['notes'],
                $item['folder'],
                $item['created_at'],
            ]);
        }

        fclose($fp);

        return $tmpFile;
    }

    /**
     * Export as PDF — returns temp file path
     */
    public function exportPdf(int $userId, ?int $folderId = null): string
    {
        $frameworkPath = \sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $items = $this->getEnrichedFavorites($userId, $folderId);

        // Get user name
        $userName = '';
        try {
            $user = \sfContext::getInstance()->getUser();
            $userName = $user->getAttribute('display_name', $user->getAttribute('username', ''));
        } catch (\Exception $e) {
        }

        $date = date('Y-m-d H:i');
        $title = \__('My Favorites');
        if ($folderId) {
            $folderName = DB::table('favorites_folder')->where('id', $folderId)->value('name');
            if ($folderName) {
                $title = \__('Favorites: %1%', ['%1%' => htmlspecialchars($folderName, ENT_QUOTES, 'UTF-8')]);
            }
        }

        // Build HTML
        $html = '<h1 style="font-size:18px;color:#1a1a2e;margin-bottom:5px;">' . $title . '</h1>';
        $html .= '<p style="font-size:11px;color:#666;margin-bottom:15px;">';
        if ($userName) {
            $html .= \__('Exported by %1% on %2%', ['%1%' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'), '%2%' => $date]);
        } else {
            $html .= \__('Exported on %1%', ['%1%' => $date]);
        }
        $html .= ' &mdash; ' . count($items) . ' ' . \__('items') . '</p>';

        if (empty($items)) {
            $html .= '<p style="color:#666;">' . \__('No favorites to export.') . '</p>';
        } else {
            $html .= '<table style="width:100%;border-collapse:collapse;font-size:10px;">';
            $html .= '<thead><tr style="background:#1a1a2e;color:#fff;">';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Title') . '</th>';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Reference Code') . '</th>';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Level') . '</th>';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Dates') . '</th>';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Repository') . '</th>';
            $html .= '<th style="padding:6px;text-align:left;">' . \__('Notes') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($items as $i => $item) {
                $bg = ($i % 2 === 0) ? '#fff' : '#f8f9fa';
                $html .= '<tr style="background:' . $bg . ';">';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['reference_code'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['level_of_description'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['date_range'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['repository'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td style="padding:5px;border-bottom:1px solid #dee2e6;">' . htmlspecialchars($item['notes'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '</title></head><body style="font-family:DejaVu Sans,sans-serif;">'
            . $html . '</body></html>';

        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);

        $dompdf->loadHtml($fullHtml);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $tmpFile = tempnam(sys_get_temp_dir(), 'fav_') . '.pdf';
        file_put_contents($tmpFile, $dompdf->output());

        return $tmpFile;
    }

    /**
     * Export as BibTeX — returns BibTeX string
     */
    public function exportBibTeX(int $userId, ?int $folderId = null): string
    {
        $items = $this->getEnrichedFavorites($userId, $folderId);
        $output = '';

        foreach ($items as $item) {
            $key = 'fav' . $item['id'];
            $fields = [];

            if ($item['title']) {
                $fields[] = '  title = {' . $this->escapeBibTeX($item['title']) . '}';
            }
            if ($item['reference_code']) {
                $fields[] = '  note = {' . $this->escapeBibTeX(\__('Reference Code') . ': ' . $item['reference_code']) . '}';
            }
            if ($item['slug']) {
                $fields[] = '  howpublished = {\\url{' . $this->escapeBibTeX($item['slug']) . '}}';
            }
            if ($item['repository']) {
                $fields[] = '  organization = {' . $this->escapeBibTeX($item['repository']) . '}';
            }
            if ($item['date_range']) {
                $year = preg_match('/\d{4}/', $item['date_range'], $m) ? $m[0] : '';
                if ($year) {
                    $fields[] = '  year = {' . $year . '}';
                }
            }
            if ($item['notes']) {
                $fields[] = '  annote = {' . $this->escapeBibTeX($item['notes']) . '}';
            }

            $output .= "@misc{{$key},\n" . implode(",\n", $fields) . "\n}\n\n";
        }

        return $output;
    }

    /**
     * Export as RIS — returns RIS string
     */
    public function exportRis(int $userId, ?int $folderId = null): string
    {
        $items = $this->getEnrichedFavorites($userId, $folderId);
        $output = '';

        foreach ($items as $item) {
            $lines = [];
            $lines[] = 'TY  - GEN';

            if ($item['title']) {
                $lines[] = 'TI  - ' . $item['title'];
            }
            if ($item['reference_code']) {
                $lines[] = 'AN  - ' . $item['reference_code'];
            }
            if ($item['slug']) {
                $lines[] = 'UR  - ' . $item['slug'];
            }
            if ($item['repository']) {
                $lines[] = 'PB  - ' . $item['repository'];
            }
            if ($item['date_range']) {
                $lines[] = 'PY  - ' . $item['date_range'];
            }
            if ($item['notes']) {
                $lines[] = 'N1  - ' . $item['notes'];
            }
            if ($item['level_of_description']) {
                $lines[] = 'M1  - ' . $item['level_of_description'];
            }

            $lines[] = 'ER  - ';
            $output .= implode("\n", $lines) . "\n\n";
        }

        return $output;
    }

    /**
     * Export as JSON — returns JSON string
     */
    public function exportJson(int $userId, ?int $folderId = null): string
    {
        $items = $this->getEnrichedFavorites($userId, $folderId);

        return json_encode([
            'exported_at' => date('c'),
            'count' => count($items),
            'favorites' => $items,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export as print-friendly HTML
     */
    public function exportPrintHtml(int $userId, ?int $folderId = null): string
    {
        $items = $this->getEnrichedFavorites($userId, $folderId);

        $title = \__('My Favorites');
        if ($folderId) {
            $folderName = DB::table('favorites_folder')->where('id', $folderId)->value('name');
            if ($folderName) {
                $title = \__('Favorites: %1%', ['%1%' => htmlspecialchars($folderName, ENT_QUOTES, 'UTF-8')]);
            }
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<title>' . $title . '</title>'
            . '<style>'
            . 'body { font-family: Arial, sans-serif; margin: 20px; }'
            . 'h1 { font-size: 20px; color: #1a1a2e; margin-bottom: 5px; }'
            . '.meta { font-size: 12px; color: #666; margin-bottom: 20px; }'
            . 'table { width: 100%; border-collapse: collapse; font-size: 12px; }'
            . 'th { background: #1a1a2e; color: #fff; padding: 8px; text-align: left; }'
            . 'td { padding: 6px 8px; border-bottom: 1px solid #dee2e6; }'
            . 'tr:nth-child(even) { background: #f8f9fa; }'
            . '@media print { body { margin: 0; } }'
            . '</style></head><body>';

        $html .= '<h1>' . $title . '</h1>';
        $html .= '<p class="meta">' . \__('Printed on %1%', ['%1%' => date('Y-m-d H:i')]) . ' &mdash; ' . count($items) . ' ' . \__('items') . '</p>';

        if (!empty($items)) {
            $html .= '<table><thead><tr>';
            $html .= '<th>' . \__('Title') . '</th><th>' . \__('Reference Code') . '</th><th>' . \__('Level') . '</th>';
            $html .= '<th>' . \__('Dates') . '</th><th>' . \__('Repository') . '</th><th>' . \__('Notes') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['reference_code'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['level_of_description'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['date_range'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['repository'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['notes'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p>' . \__('No favorites to display.') . '</p>';
        }

        $html .= '<script>window.print();</script></body></html>';

        return $html;
    }

    /**
     * Escape special BibTeX characters
     */
    private function escapeBibTeX(string $text): string
    {
        return str_replace(
            ['&', '%', '$', '#', '_', '{', '}', '~', '^'],
            ['\\&', '\\%', '\\$', '\\#', '\\_', '\\{', '\\}', '\\textasciitilde{}', '\\textasciicircum{}'],
            $text
        );
    }
}
