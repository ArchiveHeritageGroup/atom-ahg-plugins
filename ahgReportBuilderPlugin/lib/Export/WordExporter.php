<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Word Document Exporter for Report Builder.
 *
 * Generates .docx files with cover page, TOC, headers/footers, and styled sections.
 * Uses PhpWord library (already installed).
 */
class WordExporter
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
     * Generate a Word document.
     *
     * @param string|null $filePath Save to file path (null = output to browser)
     */
    public function generate(?string $filePath = null): void
    {
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            throw new RuntimeException('PhpWord library is required for Word export');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // Document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('AtoM Report Builder');
        $properties->setTitle($this->report->name);
        $properties->setDescription($this->report->description ?? '');
        $properties->setLastModifiedBy('AtoM Report Builder');

        // Default styles
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Define heading styles
        $phpWord->addTitleStyle(1, ['size' => 20, 'bold' => true, 'color' => '1a3a5c'], ['spaceAfter' => 200]);
        $phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '2c5f8a'], ['spaceAfter' => 150]);
        $phpWord->addTitleStyle(3, ['size' => 13, 'bold' => true, 'color' => '333333'], ['spaceAfter' => 100]);

        // Cover page
        $this->addCoverPage($phpWord);

        // Table of Contents
        $this->addTableOfContents($phpWord);

        // Report sections
        $this->addReportSections($phpWord);

        // Data table (if report has columns)
        if (!empty($this->report->columns) && !empty($this->reportData['results'])) {
            $this->addDataSection($phpWord);
        }

        // Output
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

        if ($filePath) {
            $writer->save($filePath);
        } else {
            $filename = $this->sanitizeFilename($this->report->name) . '_' . date('Y-m-d') . '.docx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }
    }

    /**
     * Add a cover page to the document.
     */
    private function addCoverPage(\PhpOffice\PhpWord\PhpWord $phpWord): void
    {
        $section = $phpWord->addSection([
            'marginTop' => 2000,
            'marginBottom' => 2000,
        ]);

        // Header
        $header = $section->addHeader();
        $header->addText(
            'The Archive and Heritage Group',
            ['size' => 8, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );

        // Spacer
        $section->addTextBreak(6);

        // Title
        $section->addText(
            $this->report->name,
            ['size' => 28, 'bold' => true, 'color' => '1a3a5c'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]
        );

        // Subtitle / description
        if (!empty($this->report->description)) {
            $section->addText(
                $this->report->description,
                ['size' => 14, 'color' => '555555', 'italic' => true],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 400]
            );
        }

        // Divider line
        $section->addText(
            str_repeat('─', 60),
            ['size' => 10, 'color' => 'cccccc'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 400]
        );

        // Metadata
        $section->addTextBreak(2);
        $metaStyle = ['size' => 11, 'color' => '666666'];
        $metaParagraph = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100];

        $section->addText('Generated: ' . date('d F Y, H:i'), $metaStyle, $metaParagraph);

        if (isset($this->reportData['total'])) {
            $section->addText('Total Records: ' . number_format($this->reportData['total']), $metaStyle, $metaParagraph);
        }

        $status = $this->report->status ?? 'draft';
        $section->addText('Status: ' . ucfirst(str_replace('_', ' ', $status)), $metaStyle, $metaParagraph);

        // Footer
        $footer = $section->addFooter();
        $footer->addText(
            'Confidential - AtoM Report Builder',
            ['size' => 8, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
    }

    /**
     * Add a table of contents.
     */
    private function addTableOfContents(\PhpOffice\PhpWord\PhpWord $phpWord): void
    {
        $section = $phpWord->addSection();

        // Headers and footers
        $this->addStandardHeaders($section);

        $section->addTitle('Table of Contents', 1);
        $section->addTOC(['tabLeader' => \PhpOffice\PhpWord\Style\TOC::TAB_LEADER_DOT, 'indent' => 200]);
    }

    /**
     * Add report sections (narrative, charts, etc.)
     */
    private function addReportSections(\PhpOffice\PhpWord\PhpWord $phpWord): void
    {
        if (empty($this->sections)) {
            return;
        }

        $section = $phpWord->addSection();
        $this->addStandardHeaders($section);

        foreach ($this->sections as $sectionData) {
            $title = $sectionData->title ?? ucfirst(str_replace('_', ' ', $sectionData->section_type));
            $section->addTitle($title, 2);

            switch ($sectionData->section_type) {
                case 'narrative':
                    $this->addNarrativeContent($section, $sectionData);
                    break;

                case 'table':
                    $this->addTableContent($section, $sectionData);
                    break;

                case 'chart':
                    $section->addText(
                        '[Chart: ' . ($sectionData->title ?? 'Data Visualization') . ']',
                        ['italic' => true, 'color' => '888888'],
                        ['spaceAfter' => 200]
                    );
                    break;

                case 'summary_card':
                    $this->addSummaryCardContent($section, $sectionData);
                    break;

                case 'links':
                    $this->addLinksContent($section, $sectionData);
                    break;

                case 'sql_query':
                    $section->addText(
                        '[SQL Query Results]',
                        ['italic' => true, 'color' => '888888'],
                        ['spaceAfter' => 200]
                    );
                    break;

                default:
                    if (!empty($sectionData->content)) {
                        $text = HtmlSanitizer::toPlainText($sectionData->content);
                        $section->addText($text, [], ['spaceAfter' => 200]);
                    }
            }

            $section->addTextBreak(1);
        }
    }

    /**
     * Add narrative (rich text) content.
     */
    private function addNarrativeContent($section, object $sectionData): void
    {
        if (empty($sectionData->content)) {
            return;
        }

        // Convert HTML to plain text paragraphs for PhpWord
        $html = $sectionData->content;
        $paragraphs = preg_split('/<\/?p[^>]*>/i', $html);

        foreach ($paragraphs as $para) {
            $text = trim(strip_tags($para));
            if (empty($text)) {
                continue;
            }

            // Detect formatting from tags
            $isBold = preg_match('/<(strong|b)\b/i', $para);
            $isItalic = preg_match('/<(em|i)\b/i', $para);

            $fontStyle = ['size' => 11];
            if ($isBold) {
                $fontStyle['bold'] = true;
            }
            if ($isItalic) {
                $fontStyle['italic'] = true;
            }

            $section->addText(
                htmlspecialchars_decode($text, ENT_QUOTES),
                $fontStyle,
                ['spaceAfter' => 120]
            );
        }
    }

    /**
     * Add table content from section config.
     */
    private function addTableContent($section, object $sectionData): void
    {
        $config = $sectionData->config;
        if (empty($this->reportData['results'])) {
            $section->addText('No data available.', ['italic' => true, 'color' => '888888']);

            return;
        }

        $columns = $config['columns'] ?? $this->report->columns ?? [];
        if (empty($columns)) {
            return;
        }

        // Create table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'dddddd',
            'cellMargin' => 50,
        ];
        $table = $section->addTable($tableStyle);

        // Header row
        $table->addRow(null, ['tblHeader' => true]);
        foreach ($columns as $col) {
            $label = $this->allColumns[$col]['label'] ?? $col;
            $table->addCell(2000, ['bgColor' => 'e8e8e8'])
                  ->addText($label, ['bold' => true, 'size' => 9]);
        }

        // Data rows (limit to 100 for Word export)
        $rows = array_slice($this->reportData['results'], 0, 100);
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($columns as $col) {
                $value = $row->{$col} ?? '';
                if (strlen($value) > 80) {
                    $value = substr($value, 0, 80) . '...';
                }
                $table->addCell(2000)->addText(
                    htmlspecialchars_decode((string) $value, ENT_QUOTES),
                    ['size' => 9]
                );
            }
        }

        if (count($this->reportData['results']) > 100) {
            $section->addText(
                '... and ' . (count($this->reportData['results']) - 100) . ' more rows (see full data export)',
                ['italic' => true, 'color' => '888888', 'size' => 9],
                ['spaceAfter' => 200]
            );
        }
    }

    /**
     * Add summary card content.
     */
    private function addSummaryCardContent($section, object $sectionData): void
    {
        $config = $sectionData->config;
        $cards = $config['cards'] ?? [];

        if (empty($cards)) {
            $section->addText('Total Records: ' . number_format($this->reportData['total'] ?? 0), ['bold' => true, 'size' => 14]);

            return;
        }

        foreach ($cards as $card) {
            $section->addText(
                ($card['label'] ?? 'Value') . ': ' . ($card['value'] ?? '--'),
                ['size' => 12, 'bold' => true],
                ['spaceAfter' => 100]
            );
        }
    }

    /**
     * Add links content.
     */
    private function addLinksContent($section, object $sectionData): void
    {
        $reportId = $sectionData->report_id;

        try {
            $links = DB::table('report_link')
                ->where('section_id', $sectionData->id)
                ->orderBy('position')
                ->get();

            foreach ($links as $link) {
                $textRun = $section->addTextRun(['spaceAfter' => 80]);
                $textRun->addText('• ', ['size' => 10]);

                if (!empty($link->url)) {
                    $textRun->addLink(
                        $link->url,
                        $link->title ?? $link->url,
                        ['color' => '0563C1', 'underline' => 'single', 'size' => 10]
                    );
                } else {
                    $textRun->addText($link->title ?? 'Untitled', ['size' => 10, 'bold' => true]);
                }

                if (!empty($link->description)) {
                    $section->addText(
                        '  ' . $link->description,
                        ['size' => 9, 'color' => '666666'],
                        ['indentation' => ['left' => 300], 'spaceAfter' => 100]
                    );
                }
            }
        } catch (\Exception $e) {
            // Table may not exist yet
        }
    }

    /**
     * Add the main data section.
     */
    private function addDataSection(\PhpOffice\PhpWord\PhpWord $phpWord): void
    {
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $this->addStandardHeaders($section);

        $section->addTitle('Report Data', 2);

        $columns = is_array($this->report->columns) ? $this->report->columns : [];
        if (empty($columns)) {
            return;
        }

        // Create table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'dddddd',
            'cellMargin' => 50,
        ];
        $table = $section->addTable($tableStyle);

        // Calculate cell width
        $cellWidth = intval(14000 / max(1, count($columns)));

        // Header row
        $table->addRow(null, ['tblHeader' => true]);
        foreach ($columns as $col) {
            $label = $this->allColumns[$col]['label'] ?? $col;
            $table->addCell($cellWidth, ['bgColor' => 'e8e8e8'])
                  ->addText($label, ['bold' => true, 'size' => 8]);
        }

        // Data rows
        $rows = array_slice($this->reportData['results'], 0, 500);
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($columns as $col) {
                $value = $row->{$col} ?? '';
                if (strlen($value) > 60) {
                    $value = substr($value, 0, 60) . '...';
                }
                $table->addCell($cellWidth)->addText(
                    htmlspecialchars_decode((string) $value, ENT_QUOTES),
                    ['size' => 8]
                );
            }
        }
    }

    /**
     * Add standard headers and footers to a section.
     */
    private function addStandardHeaders($section): void
    {
        $header = $section->addHeader();
        $headerTable = $header->addTable();
        $headerTable->addRow();
        $headerTable->addCell(5000)->addText(
            $this->report->name,
            ['size' => 8, 'color' => '888888']
        );
        $headerTable->addCell(5000)->addText(
            date('d F Y'),
            ['size' => 8, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Page {PAGE} of {NUMPAGES}',
            ['size' => 8, 'color' => '888888'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
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
