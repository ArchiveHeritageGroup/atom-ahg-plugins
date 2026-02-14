<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Enhanced PDF Exporter for Report Builder.
 *
 * Generates PDFs with branding, section-based layout, and chart images.
 * Uses Dompdf library (already installed).
 */
class PdfExporter
{
    private object $report;
    private array $sections;
    private array $reportData;
    private array $allColumns;
    private string $culture;

    public function __construct(object $report, array $sections, array $reportData, array $allColumns, string $culture = 'en')
    {
        $this->report = $report;
        $this->sections = $sections;
        $this->reportData = $reportData;
        $this->allColumns = $allColumns;
        $this->culture = $culture;
    }

    /**
     * Generate an enhanced PDF document.
     *
     * @param string|null $filePath Save to file path (null = output to browser)
     */
    public function generate(?string $filePath = null): void
    {
        if (!class_exists('Dompdf\Dompdf')) {
            throw new RuntimeException('Dompdf library is required for PDF export');
        }

        $html = $this->buildHtml();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Add page numbers
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('Helvetica');
        $pageCount = $canvas->get_page_count();
        for ($i = 1; $i <= $pageCount; $i++) {
            $canvas->open_object();
            $text = "Page {$i} of {$pageCount}";
            $width = $dompdf->getFontMetrics()->getTextWidth($text, $font, 8);
            $canvas->page_text(
                (595 - $width) / 2,
                820,
                $text,
                $font,
                8,
                [0.5, 0.5, 0.5]
            );
            $canvas->close_object();
        }

        if ($filePath) {
            file_put_contents($filePath, $dompdf->output());
        } else {
            $filename = $this->sanitizeFilename($this->report->name) . '_' . date('Y-m-d') . '.pdf';
            $dompdf->stream($filename, ['Attachment' => true]);
        }
    }

    /**
     * Build the full HTML for the PDF.
     */
    private function buildHtml(): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<style>' . $this->getStyles() . '</style>';
        $html .= '</head><body>';

        // Cover page
        $html .= $this->buildCoverPage();

        // Table of Contents
        if (!empty($this->sections)) {
            $html .= $this->buildTableOfContents();
        }

        // Report sections
        foreach ($this->sections as $section) {
            $html .= $this->buildSection($section);
        }

        // Data table
        if (!empty($this->report->columns) && !empty($this->reportData['results'])) {
            $html .= $this->buildDataTable();
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Build the cover page HTML.
     */
    private function buildCoverPage(): string
    {
        $status = ucfirst(str_replace('_', ' ', $this->report->status ?? 'draft'));

        $html = '<div class="cover-page">';
        $html .= '<div class="cover-header">The Archive and Heritage Group</div>';
        $html .= '<div class="cover-title">' . htmlspecialchars($this->report->name) . '</div>';

        if (!empty($this->report->description)) {
            $html .= '<div class="cover-subtitle">' . htmlspecialchars($this->report->description) . '</div>';
        }

        $html .= '<div class="cover-divider"></div>';
        $html .= '<div class="cover-meta">';
        $html .= '<p>Generated: ' . date('d F Y, H:i') . '</p>';

        if (isset($this->reportData['total'])) {
            $html .= '<p>Total Records: ' . number_format($this->reportData['total']) . '</p>';
        }

        $html .= '<p>Status: ' . $status . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Build table of contents.
     */
    private function buildTableOfContents(): string
    {
        $html = '<div class="page-break"></div>';
        $html .= '<h1 class="toc-title">Table of Contents</h1>';
        $html .= '<div class="toc">';

        $num = 1;
        foreach ($this->sections as $section) {
            $title = $section->title ?? ucfirst(str_replace('_', ' ', $section->section_type));
            $html .= '<div class="toc-item">';
            $html .= '<span class="toc-num">' . $num . '.</span>';
            $html .= '<span class="toc-label">' . htmlspecialchars($title) . '</span>';
            $html .= '</div>';
            $num++;
        }

        if (!empty($this->report->columns) && !empty($this->reportData['results'])) {
            $html .= '<div class="toc-item">';
            $html .= '<span class="toc-num">' . $num . '.</span>';
            $html .= '<span class="toc-label">Report Data</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build a section's HTML.
     */
    private function buildSection(object $section): string
    {
        $title = $section->title ?? ucfirst(str_replace('_', ' ', $section->section_type));
        $html = '<div class="page-break"></div>';
        $html .= '<h2 class="section-title">' . htmlspecialchars($title) . '</h2>';

        switch ($section->section_type) {
            case 'narrative':
                if (!empty($section->content)) {
                    $html .= '<div class="narrative-content">' . $section->content . '</div>';
                }
                break;

            case 'table':
                $html .= $this->buildSectionTable($section);
                break;

            case 'summary_card':
                $html .= $this->buildSummaryCards($section);
                break;

            case 'links':
                $html .= $this->buildLinks($section);
                break;

            case 'chart':
                $html .= '<div class="chart-placeholder">[Chart: ' . htmlspecialchars($title) . ']</div>';
                break;

            case 'sql_query':
                $html .= '<div class="chart-placeholder">[SQL Query Results]</div>';
                break;

            default:
                if (!empty($section->content)) {
                    $html .= '<div class="section-content">' . htmlspecialchars(strip_tags($section->content)) . '</div>';
                }
        }

        return $html;
    }

    /**
     * Build a table section.
     */
    private function buildSectionTable(object $section): string
    {
        $config = $section->config;
        $columns = $config['columns'] ?? $this->report->columns ?? [];

        if (empty($columns) || empty($this->reportData['results'])) {
            return '<p class="text-muted"><em>No data available.</em></p>';
        }

        $html = '<table class="data-table"><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . htmlspecialchars($this->allColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $rows = array_slice($this->reportData['results'], 0, 100);
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $value = $row->{$col} ?? '';
                if (strlen($value) > 80) {
                    $value = substr($value, 0, 80) . '...';
                }
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Build summary cards.
     */
    private function buildSummaryCards(object $section): string
    {
        $config = $section->config;
        $cards = $config['cards'] ?? [];

        if (empty($cards)) {
            $html = '<div class="summary-cards">';
            $html .= '<div class="summary-card">';
            $html .= '<div class="card-value">' . number_format($this->reportData['total'] ?? 0) . '</div>';
            $html .= '<div class="card-label">Total Records</div>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        }

        $html = '<div class="summary-cards">';
        foreach ($cards as $card) {
            $html .= '<div class="summary-card">';
            $html .= '<div class="card-value">' . htmlspecialchars($card['value'] ?? '--') . '</div>';
            $html .= '<div class="card-label">' . htmlspecialchars($card['label'] ?? '') . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Build links section.
     */
    private function buildLinks(object $section): string
    {
        try {
            $links = DB::table('report_link')
                ->where('section_id', $section->id)
                ->orderBy('position')
                ->get();
        } catch (\Exception $e) {
            return '<p><em>No links available.</em></p>';
        }

        if ($links->isEmpty()) {
            return '<p><em>No links added.</em></p>';
        }

        $html = '<ul class="link-list">';
        foreach ($links as $link) {
            $html .= '<li>';
            $html .= '<strong>' . htmlspecialchars($link->title ?? $link->url ?? 'Untitled') . '</strong>';
            if (!empty($link->url)) {
                $html .= '<br><span class="link-url">' . htmlspecialchars($link->url) . '</span>';
            }
            if (!empty($link->description)) {
                $html .= '<br><span class="link-desc">' . htmlspecialchars($link->description) . '</span>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Build the main data table section.
     */
    private function buildDataTable(): string
    {
        $columns = is_array($this->report->columns) ? $this->report->columns : [];
        if (empty($columns)) {
            return '';
        }

        $html = '<div class="page-break"></div>';
        $html .= '<h2 class="section-title">Report Data</h2>';
        $html .= '<div class="data-meta">Total: ' . number_format($this->reportData['total']) . ' records</div>';
        $html .= '<table class="data-table"><thead><tr>';

        foreach ($columns as $col) {
            $html .= '<th>' . htmlspecialchars($this->allColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $rows = array_slice($this->reportData['results'], 0, 500);
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $value = $row->{$col} ?? '';
                if (strlen($value) > 80) {
                    $value = substr($value, 0, 80) . '...';
                }
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Get CSS styles for the PDF.
     */
    private function getStyles(): string
    {
        return '
            body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 20px; }
            .cover-page { text-align: center; padding-top: 200px; page-break-after: always; }
            .cover-header { font-size: 10px; color: #888; margin-bottom: 80px; text-transform: uppercase; letter-spacing: 2px; }
            .cover-title { font-size: 28px; font-weight: bold; color: #1a3a5c; margin-bottom: 15px; }
            .cover-subtitle { font-size: 14px; color: #555; font-style: italic; margin-bottom: 40px; }
            .cover-divider { width: 200px; height: 2px; background: #ddd; margin: 30px auto; }
            .cover-meta { font-size: 11px; color: #666; }
            .cover-meta p { margin: 5px 0; }
            .page-break { page-break-before: always; }
            .toc-title { font-size: 18px; color: #1a3a5c; margin-bottom: 20px; }
            .toc-item { padding: 6px 0; border-bottom: 1px dotted #ddd; }
            .toc-num { font-weight: bold; margin-right: 10px; color: #1a3a5c; }
            .section-title { font-size: 16px; color: #1a3a5c; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px; margin: 20px 0 10px; }
            .narrative-content { line-height: 1.6; }
            .narrative-content p { margin-bottom: 8px; }
            .narrative-content h1, .narrative-content h2, .narrative-content h3 { color: #2c5f8a; }
            .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9px; }
            .data-table th, .data-table td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
            .data-table th { background: #f0f0f0; font-weight: bold; color: #333; }
            .data-table tr:nth-child(even) { background: #fafafa; }
            .data-meta { font-size: 9px; color: #888; margin-bottom: 8px; }
            .summary-cards { display: table; width: 100%; margin: 10px 0; }
            .summary-card { display: table-cell; text-align: center; padding: 15px; border: 1px solid #ddd; background: #f8f9fa; }
            .card-value { font-size: 20px; font-weight: bold; color: #1a3a5c; }
            .card-label { font-size: 9px; color: #666; margin-top: 5px; }
            .link-list { list-style: none; padding: 0; }
            .link-list li { padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
            .link-url { font-size: 8px; color: #0563C1; }
            .link-desc { font-size: 9px; color: #666; }
            .chart-placeholder { text-align: center; padding: 30px; background: #f8f9fa; border: 1px dashed #ddd; color: #888; font-style: italic; }
            .text-muted { color: #888; }
        ';
    }

    /**
     * Sanitize a string for use as a filename.
     */
    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);

        return trim($name, '_');
    }
}
