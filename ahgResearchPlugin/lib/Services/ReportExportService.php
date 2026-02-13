<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ReportExportService - PDF/DOCX Export for Reports, Notes, Bibliographies, Journals
 *
 * Uses Dompdf for PDF generation and PhpWord for DOCX generation.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class ReportExportService
{
    protected $reportService;

    public function __construct()
    {
        $pluginsDir = sfConfig::get('sf_plugins_dir');
        require_once $pluginsDir . '/ahgResearchPlugin/lib/Services/ReportService.php';
        $this->reportService = new ReportService();
    }

    // =========================================================================
    // PDF EXPORT (Dompdf)
    // =========================================================================

    /**
     * Export a report as PDF.
     */
    public function exportReportPdf(int $reportId): ?string
    {
        $report = $this->reportService->getReport($reportId);
        if (!$report) {
            return null;
        }

        $html = $this->buildReportHtml($report);
        return $this->buildPdfFromHtml($html, $report->title);
    }

    /**
     * Export a report as DOCX.
     */
    public function exportReportDocx(int $reportId): ?string
    {
        $report = $this->reportService->getReport($reportId);
        if (!$report) {
            return null;
        }

        return $this->buildDocxFromSections($report);
    }

    /**
     * Export annotations/notes as PDF.
     */
    public function exportNotesPdf(int $researcherId, array $filters = []): ?string
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('a.researcher_id', $researcherId)
            ->select('a.*', 'ioi.title as object_title')
            ->orderBy('a.created_at', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('a.project_id', $filters['project_id']);
        }

        $annotations = $query->get()->toArray();
        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        $html = $this->buildNotesHtml($annotations, $researcher);
        return $this->buildPdfFromHtml($html, 'Research Notes');
    }

    /**
     * Export annotations/notes as DOCX.
     */
    public function exportNotesDocx(int $researcherId, array $filters = []): ?string
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('a.researcher_id', $researcherId)
            ->select('a.*', 'ioi.title as object_title')
            ->orderBy('a.created_at', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('a.project_id', $filters['project_id']);
        }

        $annotations = $query->get()->toArray();
        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        return $this->buildNotesDocx($annotations, $researcher);
    }

    /**
     * Export journal as PDF.
     */
    public function exportJournalPdf(int $researcherId, array $filters = []): ?string
    {
        $query = DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.researcher_id', $researcherId)
            ->select('j.*', 'p.title as project_title')
            ->orderBy('j.entry_date', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('j.project_id', $filters['project_id']);
        }

        $entries = $query->get()->toArray();
        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        $html = $this->buildJournalHtml($entries, $researcher);
        return $this->buildPdfFromHtml($html, 'Research Journal');
    }

    /**
     * Export journal as DOCX.
     */
    public function exportJournalDocx(int $researcherId, array $filters = []): ?string
    {
        $query = DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.researcher_id', $researcherId)
            ->select('j.*', 'p.title as project_title')
            ->orderBy('j.entry_date', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('j.project_id', $filters['project_id']);
        }

        $entries = $query->get()->toArray();
        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        return $this->buildJournalDocx($entries, $researcher);
    }

    /**
     * Export a collection as a finding aid (PDF).
     */
    public function exportFindingAidPdf(int $collectionId, int $researcherId): ?string
    {
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcherId)
            ->first();

        if (!$collection) {
            return null;
        }

        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ci.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'ci.object_id', '=', 's.object_id')
            ->where('ci.collection_id', $collectionId)
            ->orderBy('ci.sort_order')
            ->select('ci.*', 'ioi.title as item_title', 's.slug')
            ->get()->toArray();

        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        $html = $this->buildFindingAidHtml($collection, $items, $researcher);
        return $this->buildPdfFromHtml($html, 'Finding Aid - ' . $collection->name);
    }

    /**
     * Export a collection as a finding aid (DOCX).
     */
    public function exportFindingAidDocx(int $collectionId, int $researcherId): ?string
    {
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcherId)
            ->first();

        if (!$collection) {
            return null;
        }

        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ci.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('ci.collection_id', $collectionId)
            ->orderBy('ci.sort_order')
            ->select('ci.*', 'ioi.title as item_title')
            ->get()->toArray();

        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        return $this->buildFindingAidDocx($collection, $items, $researcher);
    }

    // =========================================================================
    // HTML BUILDERS
    // =========================================================================

    protected function buildReportHtml(object $report): string
    {
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';
        $html .= $this->reportService->renderReportHtml($report->id);
        $html .= '</div>';
        return $html;
    }

    protected function buildNotesHtml(array $annotations, ?object $researcher): string
    {
        $name = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';
        $html .= '<h1>Research Notes</h1>';
        $html .= '<p class="subtitle">' . htmlspecialchars($name) . ' — ' . date('F j, Y') . '</p>';
        $html .= '<hr>';

        foreach ($annotations as $note) {
            $html .= '<div class="note-entry">';
            $html .= '<h3>' . htmlspecialchars($note->title ?: 'Untitled Note') . '</h3>';
            if (!empty($note->object_title)) {
                $html .= '<p class="linked-item"><em>Item: ' . htmlspecialchars($note->object_title) . '</em></p>';
            }
            if (($note->content_format ?? 'text') === 'html') {
                $html .= $note->content;
            } else {
                $html .= '<p>' . nl2br(htmlspecialchars($note->content ?? '')) . '</p>';
            }
            $html .= '<p class="date">' . date('M j, Y H:i', strtotime($note->created_at)) . '</p>';
            $html .= '</div><hr>';
        }

        $html .= '</div>';
        return $html;
    }

    protected function buildJournalHtml(array $entries, ?object $researcher): string
    {
        $name = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';
        $html .= '<h1>Research Journal</h1>';
        $html .= '<p class="subtitle">' . htmlspecialchars($name) . ' — ' . date('F j, Y') . '</p>';
        $html .= '<hr>';

        foreach ($entries as $entry) {
            $html .= '<div class="journal-entry">';
            $html .= '<h3>' . htmlspecialchars($entry->title ?: date('F j, Y', strtotime($entry->entry_date))) . '</h3>';
            $html .= '<p class="meta">' . date('l, F j, Y', strtotime($entry->entry_date));
            if (!empty($entry->project_title)) {
                $html .= ' — Project: ' . htmlspecialchars($entry->project_title);
            }
            if (!empty($entry->time_spent_minutes)) {
                $hours = floor($entry->time_spent_minutes / 60);
                $mins = $entry->time_spent_minutes % 60;
                $html .= ' — Time: ' . ($hours ? $hours . 'h ' : '') . $mins . 'm';
            }
            $html .= '</p>';
            if (($entry->content_format ?? 'text') === 'html') {
                $html .= $entry->content;
            } else {
                $html .= '<p>' . nl2br(htmlspecialchars($entry->content ?? '')) . '</p>';
            }
            $html .= '</div><hr>';
        }

        $html .= '</div>';
        return $html;
    }

    protected function buildFindingAidHtml(object $collection, array $items, ?object $researcher): string
    {
        $name = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';
        $html .= '<h1>Finding Aid</h1>';
        $html .= '<h2>' . htmlspecialchars($collection->name) . '</h2>';
        $html .= '<p class="subtitle">Compiled by ' . htmlspecialchars($name) . ' — ' . date('F j, Y') . '</p>';
        if (!empty($collection->description)) {
            $html .= '<h3>Description</h3><p>' . nl2br(htmlspecialchars($collection->description)) . '</p>';
        }
        $html .= '<h3>Items (' . count($items) . ')</h3>';
        $html .= '<table><thead><tr><th>#</th><th>Title</th><th>Notes</th></tr></thead><tbody>';
        foreach ($items as $i => $item) {
            $html .= '<tr><td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($item->item_title ?? 'Untitled') . '</td>';
            $html .= '<td>' . htmlspecialchars($item->notes ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    // =========================================================================
    // PDF GENERATION (Dompdf)
    // =========================================================================

    protected function buildPdfFromHtml(string $html, string $title): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $dompdf = new \Dompdf\Dompdf([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);

        $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head><body>' . $html . '</body></html>';
        $dompdf->loadHtml($fullHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $tmpFile = tempnam(sys_get_temp_dir(), 'report_') . '.pdf';
        file_put_contents($tmpFile, $dompdf->output());

        return $tmpFile;
    }

    // =========================================================================
    // DOCX GENERATION (PhpWord)
    // =========================================================================

    protected function buildDocxFromSections(object $report): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getDocInfo()->setTitle($report->title);
        $phpWord->getDocInfo()->setCreator(($report->first_name ?? '') . ' ' . ($report->last_name ?? ''));

        foreach ($report->sections as $section) {
            $docSection = $phpWord->addSection();
            $this->addSectionToDocx($docSection, $section, $report);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'report_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    protected function addSectionToDocx($docSection, object $section, object $report): void
    {
        switch ($section->section_type) {
            case 'title_page':
                $docSection->addText($report->title, ['size' => 24, 'bold' => true]);
                $docSection->addText(($report->first_name ?? '') . ' ' . ($report->last_name ?? ''), ['size' => 14]);
                $docSection->addText(date('F j, Y', strtotime($report->created_at)), ['size' => 12, 'color' => '666666']);
                break;

            case 'toc':
                $docSection->addText('Table of Contents', ['size' => 16, 'bold' => true]);
                $docSection->addTOC();
                break;

            case 'heading':
                $docSection->addTitle($section->title ?? '', 1);
                break;

            default:
                if ($section->title) {
                    $docSection->addTitle($section->title, 2);
                }
                $content = strip_tags($section->content ?? '');
                if ($content) {
                    $docSection->addText($content);
                }
                break;
        }
    }

    protected function buildNotesDocx(array $annotations, ?object $researcher): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getDocInfo()->setTitle('Research Notes');

        $section = $phpWord->addSection();
        $section->addText('Research Notes', ['size' => 24, 'bold' => true]);
        if ($researcher) {
            $section->addText($researcher->first_name . ' ' . $researcher->last_name, ['size' => 14]);
        }
        $section->addText(date('F j, Y'), ['size' => 12, 'color' => '666666']);

        foreach ($annotations as $note) {
            $section->addTextBreak();
            $section->addText($note->title ?: 'Untitled Note', ['size' => 14, 'bold' => true]);
            if (!empty($note->object_title)) {
                $section->addText('Item: ' . $note->object_title, ['italic' => true]);
            }
            $section->addText(strip_tags($note->content ?? ''));
            $section->addText(date('M j, Y H:i', strtotime($note->created_at)), ['size' => 10, 'color' => '999999']);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'notes_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    protected function buildJournalDocx(array $entries, ?object $researcher): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getDocInfo()->setTitle('Research Journal');

        $section = $phpWord->addSection();
        $section->addText('Research Journal', ['size' => 24, 'bold' => true]);
        if ($researcher) {
            $section->addText($researcher->first_name . ' ' . $researcher->last_name, ['size' => 14]);
        }

        foreach ($entries as $entry) {
            $section->addTextBreak();
            $section->addText(
                $entry->title ?: date('F j, Y', strtotime($entry->entry_date)),
                ['size' => 14, 'bold' => true]
            );
            if (!empty($entry->project_title)) {
                $section->addText('Project: ' . $entry->project_title, ['italic' => true]);
            }
            $section->addText(strip_tags($entry->content ?? ''));
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'journal_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    protected function buildFindingAidDocx(object $collection, array $items, ?object $researcher): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->getDocInfo()->setTitle('Finding Aid - ' . $collection->name);

        $section = $phpWord->addSection();
        $section->addText('Finding Aid', ['size' => 24, 'bold' => true]);
        $section->addText($collection->name, ['size' => 18, 'bold' => true]);
        if ($researcher) {
            $section->addText('Compiled by ' . $researcher->first_name . ' ' . $researcher->last_name, ['size' => 12]);
        }

        if (!empty($collection->description)) {
            $section->addTextBreak();
            $section->addText('Description', ['size' => 14, 'bold' => true]);
            $section->addText($collection->description);
        }

        $section->addTextBreak();
        $section->addText('Items (' . count($items) . ')', ['size' => 14, 'bold' => true]);

        $table = $section->addTable(['borderSize' => 1, 'borderColor' => 'cccccc']);
        $table->addRow();
        $table->addCell(600)->addText('#', ['bold' => true]);
        $table->addCell(5000)->addText('Title', ['bold' => true]);
        $table->addCell(3000)->addText('Notes', ['bold' => true]);

        foreach ($items as $i => $item) {
            $table->addRow();
            $table->addCell(600)->addText((string) ($i + 1));
            $table->addCell(5000)->addText($item->item_title ?? 'Untitled');
            $table->addCell(3000)->addText($item->notes ?? '');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'findingaid_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function getDocumentCss(): string
    {
        return '<style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6; color: #333; }
            .document { max-width: 800px; margin: 0 auto; padding: 20px; }
            h1 { font-size: 24pt; margin-bottom: 5px; }
            h2 { font-size: 18pt; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
            h3 { font-size: 14pt; margin-top: 15px; }
            .subtitle { color: #666; font-size: 12pt; }
            .meta { color: #666; font-size: 10pt; }
            .date { color: #999; font-size: 10pt; }
            .linked-item { color: #0066cc; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
            th { background: #f5f5f5; font-weight: bold; }
            hr { border: none; border-top: 1px solid #ddd; margin: 15px 0; }
            .title-page { text-align: center; padding: 60px 0; }
            .toc-list li { margin: 5px 0; }
        </style>';
    }
}
