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
    public function exportNotesPdf(int $researcherId, ?int $noteId = null, ?array $noteIds = null): ?string
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('research_collection as rc', 'a.collection_id', '=', 'rc.id')
            ->where('a.researcher_id', $researcherId)
            ->select('a.*', 'ioi.title as object_title', 'rc.name as collection_name')
            ->orderBy('a.created_at', 'desc');

        if ($noteId) {
            $query->where('a.id', $noteId);
        } elseif ($noteIds) {
            $query->whereIn('a.id', $noteIds);
        }

        $annotations = $query->get()->toArray();
        $researcher = DB::table('research_researcher')->where('id', $researcherId)->first();

        $html = $this->buildNotesHtml($annotations, $researcher);
        return $this->buildPdfFromHtml($html, $noteId ? 'Research Note' : 'Research Notes');
    }

    /**
     * Export annotations/notes as DOCX.
     */
    public function exportNotesDocx(int $researcherId, ?int $noteId = null, ?array $noteIds = null): ?string
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('research_collection as rc', 'a.collection_id', '=', 'rc.id')
            ->where('a.researcher_id', $researcherId)
            ->select('a.*', 'ioi.title as object_title', 'rc.name as collection_name')
            ->orderBy('a.created_at', 'desc');

        if ($noteId) {
            $query->where('a.id', $noteId);
        } elseif ($noteIds) {
            $query->whereIn('a.id', $noteIds);
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
        $authorName = trim(($report->first_name ?? '') . ' ' . ($report->last_name ?? ''));
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';

        // Cover page
        $hasTitlePage = false;
        if (!empty($report->sections)) {
            foreach ($report->sections as $s) {
                if ($s->section_type === 'title_page') { $hasTitlePage = true; break; }
            }
        }
        if (!$hasTitlePage) {
            $html .= '<div class="title-page">';
            $html .= '<h1 style="font-size: 28pt; margin-bottom: 10px;">' . htmlspecialchars($report->title) . '</h1>';
            if (!empty($report->template_type) && $report->template_type !== 'custom') {
                $html .= '<p style="font-size: 14pt; color: #666; margin: 5px 0;">' . htmlspecialchars(ucwords(str_replace('_', ' ', $report->template_type))) . '</p>';
            }
            $html .= '<hr style="width: 60%; margin: 20px auto;">';
            if ($authorName) {
                $html .= '<p style="font-size: 14pt;">' . htmlspecialchars($authorName) . '</p>';
            }
            $html .= '<p style="font-size: 12pt; color: #666;">' . date('F j, Y', strtotime($report->created_at)) . '</p>';
            if (!empty($report->description)) {
                $html .= '<p style="font-size: 11pt; color: #555; margin-top: 30px; font-style: italic;">' . htmlspecialchars(substr(strip_tags($report->description), 0, 300)) . '</p>';
            }
            $html .= '</div>';
            $html .= '<div style="page-break-after: always;"></div>';
        }

        $html .= $this->reportService->renderReportHtml($report->id);
        $html .= '</div>';
        return $html;
    }

    protected function buildNotesHtml(array $annotations, ?object $researcher): string
    {
        $name = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';
        $html = $this->getDocumentCss();
        $html .= '<div class="document">';

        // Cover page
        $html .= '<div class="title-page">';
        $html .= '<h1 style="font-size: 28pt;">Research Notes</h1>';
        $html .= '<hr style="width: 60%; margin: 20px auto;">';
        $html .= '<p style="font-size: 14pt;">' . htmlspecialchars($name) . '</p>';
        $html .= '<p style="font-size: 12pt; color: #666;">' . date('F j, Y') . '</p>';
        $html .= '<p style="font-size: 11pt; color: #888;">' . count($annotations) . ' note(s)</p>';
        $html .= '</div>';
        $html .= '<div style="page-break-after: always;"></div>';

        foreach ($annotations as $note) {
            $html .= '<div class="note-entry">';
            $html .= '<h3>' . htmlspecialchars($note->title ?: 'Untitled Note') . '</h3>';
            // Internal cross-reference with entity type
            if (!empty($note->object_title)) {
                $entityLabel = match($note->entity_type ?? 'information_object') {
                    'actor' => 'Authority Record',
                    'repository' => 'Repository',
                    'accession' => 'Accession',
                    default => 'Item',
                };
                $html .= '<p class="linked-item"><em>' . $entityLabel . ': ' . htmlspecialchars($note->object_title) . '</em></p>';
            }
            if (!empty($note->collection_name)) {
                $html .= '<p class="linked-item"><em>Collection: ' . htmlspecialchars($note->collection_name) . '</em></p>';
            }
            if (!empty($note->tags)) {
                $html .= '<p class="meta">Tags: ' . htmlspecialchars($note->tags) . '</p>';
            }
            if (($note->content_format ?? 'text') === 'html') {
                $html .= $note->content;
            } else {
                $html .= '<p>' . nl2br(htmlspecialchars($note->content ?? '')) . '</p>';
            }
            $visibility = $note->visibility ?? 'private';
            $html .= '<p class="date">' . date('M j, Y H:i', strtotime($note->created_at)) . ' — ' . ucfirst($visibility) . '</p>';
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
        $phpWord->getDocInfo()->setCompany('AtoM Heratio');

        // Default styles
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->addTitleStyle(1, ['size' => 20, 'bold' => true, 'color' => '1a1a2e'], ['spaceAfter' => 200]);
        $phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '333333'], ['spaceAfter' => 150]);
        $phpWord->addTitleStyle(3, ['size' => 13, 'bold' => true, 'color' => '444444'], ['spaceAfter' => 100]);

        $hasCoverPage = false;
        foreach ($report->sections as $section) {
            if ($section->section_type === 'title_page') {
                $hasCoverPage = true;
                break;
            }
        }

        // Auto-add cover page if none defined
        if (!$hasCoverPage) {
            $coverSection = $phpWord->addSection();
            $this->addCoverPage($coverSection, $report);
        }

        foreach ($report->sections as $section) {
            $docSection = $phpWord->addSection();
            $this->addHeadersFooters($docSection, $report);
            $this->addSectionToDocx($docSection, $section, $report);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'report_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpFile);

        return $tmpFile;
    }

    /**
     * Add a professional cover page to DOCX.
     */
    protected function addCoverPage($docSection, object $report): void
    {
        // Top spacing
        for ($i = 0; $i < 6; $i++) {
            $docSection->addTextBreak();
        }

        // Title
        $docSection->addText(
            $report->title,
            ['size' => 28, 'bold' => true, 'color' => '1a1a2e'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]
        );

        // Horizontal rule
        $docSection->addText(
            str_repeat('_', 50),
            ['size' => 10, 'color' => 'cccccc'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]
        );

        // Template type
        if (!empty($report->template_type) && $report->template_type !== 'custom') {
            $docSection->addText(
                ucwords(str_replace('_', ' ', $report->template_type)),
                ['size' => 14, 'italic' => true, 'color' => '666666'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
            );
        }

        // Author
        $authorName = trim(($report->first_name ?? '') . ' ' . ($report->last_name ?? ''));
        if ($authorName) {
            $docSection->addText(
                $authorName,
                ['size' => 14, 'color' => '333333'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
            );
        }

        // Date
        $docSection->addText(
            date('F j, Y', strtotime($report->created_at)),
            ['size' => 12, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
        );

        // Description
        if (!empty($report->description)) {
            for ($i = 0; $i < 3; $i++) {
                $docSection->addTextBreak();
            }
            $docSection->addText(
                strip_tags($report->description),
                ['size' => 11, 'italic' => true, 'color' => '555555'],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
            );
        }
    }

    /**
     * Add headers and footers to a DOCX section.
     */
    protected function addHeadersFooters($docSection, object $report): void
    {
        // Header with report title
        $header = $docSection->addHeader();
        $header->addText(
            $report->title,
            ['size' => 9, 'color' => '999999', 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );

        // Footer with page number and date
        $footer = $docSection->addFooter();
        $footerTable = $footer->addTable();
        $footerTable->addRow();
        $leftCell = $footerTable->addCell(5000);
        $leftCell->addText(
            date('F j, Y'),
            ['size' => 8, 'color' => '999999']
        );
        $rightCell = $footerTable->addCell(5000);
        $textRun = $rightCell->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $textRun->addText('Page ', ['size' => 8, 'color' => '999999']);
        $textRun->addField('PAGE', ['size' => 8, 'color' => '999999']);
        $textRun->addText(' of ', ['size' => 8, 'color' => '999999']);
        $textRun->addField('NUMPAGES', ['size' => 8, 'color' => '999999']);
    }

    protected function addSectionToDocx($docSection, object $section, object $report): void
    {
        switch ($section->section_type) {
            case 'title_page':
                $this->addCoverPage($docSection, $report);
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
                // Handle HTML content with embedded images
                $content = $section->content ?? '';
                if ($content && ($section->content_format ?? 'text') === 'html') {
                    $this->addHtmlContentToDocx($docSection, $content);
                } else {
                    $plainText = strip_tags($content);
                    if ($plainText) {
                        $docSection->addText($plainText);
                    }
                }
                break;
        }
    }

    /**
     * Parse HTML content and add to DOCX with embedded images.
     */
    protected function addHtmlContentToDocx($docSection, string $html): void
    {
        // Extract and embed local images
        $rootDir = sfConfig::get('sf_root_dir');
        $html = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) use ($docSection, $rootDir) {
            $src = $matches[1];
            // Handle local images (uploaded note images, etc.)
            if (strpos($src, '/uploads/') === 0) {
                $localPath = $rootDir . $src;
                if (file_exists($localPath)) {
                    try {
                        $docSection->addImage($localPath, [
                            'width' => 400,
                            'wrappingStyle' => 'inline',
                        ]);
                    } catch (\Exception $e) {
                        // Skip images that can't be embedded
                    }
                    return ''; // Remove from text content
                }
            }
            return ''; // Remove external images
        }, $html);

        // Add remaining text content (stripped of tags)
        $paragraphs = preg_split('/<\/?(?:p|br|div|h[1-6])[^>]*>/i', $html);
        foreach ($paragraphs as $para) {
            $text = trim(strip_tags($para));
            if ($text) {
                $docSection->addText($text, [], ['spaceAfter' => 100]);
            }
        }
    }

    protected function buildNotesDocx(array $annotations, ?object $researcher): string
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->getDocInfo()->setTitle('Research Notes');
        $phpWord->getDocInfo()->setCompany('AtoM Heratio');

        $authorName = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';

        // Cover page section
        $coverSection = $phpWord->addSection();
        for ($i = 0; $i < 6; $i++) { $coverSection->addTextBreak(); }
        $coverSection->addText('Research Notes', ['size' => 28, 'bold' => true, 'color' => '1a1a2e'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]);
        $coverSection->addText(str_repeat('_', 50), ['size' => 10, 'color' => 'cccccc'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]);
        $coverSection->addText($authorName, ['size' => 14, 'color' => '333333'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]);
        $coverSection->addText(date('F j, Y'), ['size' => 12, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $coverSection->addText(count($annotations) . ' note(s)', ['size' => 11, 'italic' => true, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        // Content section with headers/footers
        $section = $phpWord->addSection();
        $header = $section->addHeader();
        $header->addText('Research Notes — ' . $authorName, ['size' => 9, 'color' => '999999', 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $footer = $section->addFooter();
        $footRun = $footer->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $footRun->addText('Page ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('PAGE', ['size' => 8, 'color' => '999999']);
        $footRun->addText(' of ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('NUMPAGES', ['size' => 8, 'color' => '999999']);

        foreach ($annotations as $note) {
            $section->addTextBreak();
            $section->addText($note->title ?: 'Untitled Note', ['size' => 14, 'bold' => true]);
            if (!empty($note->object_title)) {
                $section->addText('Item: ' . $note->object_title, ['italic' => true, 'color' => '0066cc']);
            }
            if (!empty($note->tags)) {
                $section->addText('Tags: ' . $note->tags, ['size' => 10, 'italic' => true, 'color' => '666666']);
            }
            // Handle HTML content with embedded images
            if (($note->content_format ?? 'text') === 'html') {
                $this->addHtmlContentToDocx($section, $note->content ?? '');
            } else {
                $section->addText(strip_tags($note->content ?? ''));
            }
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
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->getDocInfo()->setTitle('Research Journal');
        $phpWord->getDocInfo()->setCompany('AtoM Heratio');

        $authorName = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';

        // Cover page
        $coverSection = $phpWord->addSection();
        for ($i = 0; $i < 6; $i++) { $coverSection->addTextBreak(); }
        $coverSection->addText('Research Journal', ['size' => 28, 'bold' => true, 'color' => '1a1a2e'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]);
        $coverSection->addText(str_repeat('_', 50), ['size' => 10, 'color' => 'cccccc'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]);
        $coverSection->addText($authorName, ['size' => 14, 'color' => '333333'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]);
        $coverSection->addText(date('F j, Y'), ['size' => 12, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $coverSection->addText(count($entries) . ' entries', ['size' => 11, 'italic' => true, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        // Content section
        $section = $phpWord->addSection();
        $header = $section->addHeader();
        $header->addText('Research Journal — ' . $authorName, ['size' => 9, 'color' => '999999', 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $footer = $section->addFooter();
        $footRun = $footer->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $footRun->addText('Page ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('PAGE', ['size' => 8, 'color' => '999999']);
        $footRun->addText(' of ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('NUMPAGES', ['size' => 8, 'color' => '999999']);

        foreach ($entries as $entry) {
            $section->addTextBreak();
            $section->addText(
                $entry->title ?: date('F j, Y', strtotime($entry->entry_date)),
                ['size' => 14, 'bold' => true]
            );
            $meta = date('l, F j, Y', strtotime($entry->entry_date));
            if (!empty($entry->project_title)) {
                $meta .= ' — Project: ' . $entry->project_title;
            }
            if (!empty($entry->time_spent_minutes)) {
                $hours = floor($entry->time_spent_minutes / 60);
                $mins = $entry->time_spent_minutes % 60;
                $meta .= ' — Time: ' . ($hours ? $hours . 'h ' : '') . $mins . 'm';
            }
            $section->addText($meta, ['size' => 10, 'italic' => true, 'color' => '666666']);
            if (!empty($entry->tags)) {
                $section->addText('Tags: ' . $entry->tags, ['size' => 10, 'color' => '888888']);
            }
            if (($entry->content_format ?? 'text') === 'html') {
                $this->addHtmlContentToDocx($section, $entry->content ?? '');
            } else {
                $section->addText(strip_tags($entry->content ?? ''));
            }
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
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->getDocInfo()->setTitle('Finding Aid - ' . $collection->name);
        $phpWord->getDocInfo()->setCompany('AtoM Heratio');

        $authorName = $researcher ? ($researcher->first_name . ' ' . $researcher->last_name) : 'Researcher';

        // Cover page
        $coverSection = $phpWord->addSection();
        for ($i = 0; $i < 6; $i++) { $coverSection->addTextBreak(); }
        $coverSection->addText('Finding Aid', ['size' => 28, 'bold' => true, 'color' => '1a1a2e'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]);
        $coverSection->addText($collection->name, ['size' => 20, 'bold' => true, 'color' => '333333'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]);
        $coverSection->addText(str_repeat('_', 50), ['size' => 10, 'color' => 'cccccc'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]);
        $coverSection->addText('Compiled by ' . $authorName, ['size' => 14, 'color' => '333333'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]);
        $coverSection->addText(date('F j, Y'), ['size' => 12, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $coverSection->addText(count($items) . ' items', ['size' => 11, 'italic' => true, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

        // Content section with headers/footers
        $section = $phpWord->addSection();
        $header = $section->addHeader();
        $header->addText('Finding Aid — ' . $collection->name, ['size' => 9, 'color' => '999999', 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $footer = $section->addFooter();
        $footRun = $footer->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $footRun->addText('Page ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('PAGE', ['size' => 8, 'color' => '999999']);
        $footRun->addText(' of ', ['size' => 8, 'color' => '999999']);
        $footRun->addField('NUMPAGES', ['size' => 8, 'color' => '999999']);

        if (!empty($collection->description)) {
            $section->addText('Description', ['size' => 14, 'bold' => true]);
            $section->addText($collection->description, [], ['spaceAfter' => 200]);
        }

        $section->addText('Items (' . count($items) . ')', ['size' => 14, 'bold' => true], ['spaceAfter' => 100]);

        $headerBg = 'E8E8E8';
        $table = $section->addTable(['borderSize' => 1, 'borderColor' => 'cccccc']);
        $table->addRow();
        $table->addCell(600, ['bgColor' => $headerBg])->addText('#', ['bold' => true]);
        $table->addCell(5000, ['bgColor' => $headerBg])->addText('Title', ['bold' => true]);
        $table->addCell(3000, ['bgColor' => $headerBg])->addText('Notes', ['bold' => true]);

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
    // CSV EXPORT
    // =========================================================================

    /**
     * Export notes as CSV.
     */
    public function exportNotesCsv(int $researcherId, ?int $noteId = null, ?array $noteIds = null): string
    {
        $query = DB::table('research_annotation as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('research_collection as rc', 'a.collection_id', '=', 'rc.id')
            ->where('a.researcher_id', $researcherId)
            ->select('a.id', 'a.title', 'a.content', 'a.visibility', 'a.tags',
                     'ioi.title as object_title', 'rc.name as collection_name',
                     'a.created_at', 'a.updated_at')
            ->orderBy('a.created_at', 'desc');

        if ($noteId) {
            $query->where('a.id', $noteId);
        } elseif ($noteIds) {
            $query->whereIn('a.id', $noteIds);
        }

        $notes = $query->get()->toArray();
        return $this->buildCsv(
            ['ID', 'Title', 'Content', 'Visibility', 'Tags', 'Linked Item', 'Collection', 'Created', 'Updated'],
            array_map(function ($n) {
                return [
                    $n->id, $n->title ?? '', strip_tags($n->content ?? ''),
                    $n->visibility ?? 'private', $n->tags ?? '',
                    $n->object_title ?? '', $n->collection_name ?? '',
                    $n->created_at, $n->updated_at ?? '',
                ];
            }, $notes),
            'notes'
        );
    }

    /**
     * Export collection items as CSV.
     */
    public function exportCollectionCsv(int $collectionId, int $researcherId): ?string
    {
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcherId)
            ->first();
        if (!$collection) return null;

        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ci.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'ci.object_id', '=', 'io.id')
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('io.repository_id', '=', 'ri.id')->where('ri.culture', '=', 'en');
            })
            ->where('ci.collection_id', $collectionId)
            ->orderBy('ci.sort_order')
            ->select('ci.object_id', 'ioi.title', 'ioi.date as date_display',
                     'ioi.extent_and_medium', 'ri.authorized_form_of_name as repository',
                     'ci.notes', 'ci.created_at')
            ->get()->toArray();

        return $this->buildCsv(
            ['Object ID', 'Title', 'Date', 'Extent', 'Repository', 'Notes', 'Added'],
            array_map(function ($i) {
                return [
                    $i->object_id, $i->title ?? '', $i->date_display ?? '',
                    $i->extent_and_medium ?? '', $i->repository ?? '',
                    $i->notes ?? '', $i->created_at,
                ];
            }, $items),
            'collection-' . $collectionId
        );
    }

    /**
     * Export journal entries as CSV.
     */
    public function exportJournalCsv(int $researcherId, array $filters = []): string
    {
        $query = DB::table('research_journal_entry as j')
            ->leftJoin('research_project as p', 'j.project_id', '=', 'p.id')
            ->where('j.researcher_id', $researcherId)
            ->select('j.id', 'j.entry_date', 'j.title', 'j.content', 'j.entry_type',
                     'j.time_spent_minutes', 'j.tags', 'p.title as project_title',
                     'j.created_at')
            ->orderBy('j.entry_date', 'desc');

        if (!empty($filters['project_id'])) {
            $query->where('j.project_id', $filters['project_id']);
        }

        $entries = $query->get()->toArray();
        return $this->buildCsv(
            ['ID', 'Date', 'Title', 'Content', 'Type', 'Time (min)', 'Tags', 'Project', 'Created'],
            array_map(function ($e) {
                return [
                    $e->id, $e->entry_date, $e->title ?? '', strip_tags($e->content ?? ''),
                    $e->entry_type ?? '', $e->time_spent_minutes ?? '',
                    $e->tags ?? '', $e->project_title ?? '', $e->created_at,
                ];
            }, $entries),
            'journal'
        );
    }

    /**
     * Build CSV and write to temp file.
     */
    protected function buildCsv(array $headers, array $rows, string $prefix = 'export'): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), $prefix . '_') . '.csv';
        $fp = fopen($tmpFile, 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
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
