<?php

/**
 * Spectrum Condition Report Generation Job
 *
 * Background job for generating PDF condition reports
 *
 * @package    ahgSpectrumPlugin
 * @subpackage lib/job
 * @author     Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgSpectrumConditionReportJob extends arBaseJob
{
    /**
     * @see arBaseJob::$requiredParameters
     */
    protected $requiredParameters = ['conditionCheckId'];

    /**
     * Job name for display
     */
    protected $jobName = 'Spectrum: Generate Condition Report PDF';

    /**
     * Execute the job
     */
    public function runJob($parameters)
    {
        $this->info('Starting condition report generation job');

        $conditionCheckId = $parameters['conditionCheckId'];
        $includePhotos = $parameters['includePhotos'] ?? true;
        $format = $parameters['format'] ?? 'pdf';

        // Load condition check data
        $conditionCheck = $this->loadConditionCheck($conditionCheckId);

        if (!$conditionCheck) {
            $this->error('Condition check not found: ' . $conditionCheckId);
            return false;
        }

        // Load related object
        $object = $this->getInformationObjectById($conditionCheck->object_id);

        if (!$object) {
            $this->error('Object not found for condition check');
            return false;
        }

        $this->info('Generating report for: ' . ($object->title ?? $object->slug ?? 'Unknown'));

        // Load photos if requested
        $photos = [];
        if ($includePhotos) {
            $photos = $this->getConditionPhotos($conditionCheckId);
            $this->info(sprintf('Including %d photos', count($photos)));
        }

        // Load conservation records
        $conservation = $this->loadConservationRecords($conditionCheck->object_id);

        // Generate report based on format
        switch ($format) {
            case 'pdf':
                $reportPath = $this->generatePdfReport($conditionCheck, $object, $photos, $conservation);
                break;
            case 'html':
                $reportPath = $this->generateHtmlReport($conditionCheck, $object, $photos, $conservation);
                break;
            case 'docx':
                $reportPath = $this->generateDocxReport($conditionCheck, $object, $photos, $conservation);
                break;
            default:
                $this->error('Unknown format: ' . $format);
                return false;
        }

        if ($reportPath) {
            $this->info('Report generated: ' . $reportPath);

            // Store report path in job output
            $this->job->setStatusNote('Report generated successfully');
            $this->job->setOutput(['report_path' => $reportPath]);
            $this->job->save();

            return true;
        }

        return false;
    }

    /**
     * Load condition check
     */
    protected function loadConditionCheck($id)
    {
        return DB::table('spectrum_condition_check')
            ->where('id', $id)
            ->first();
    }

    /**
     * Load conservation records
     */
    protected function loadConservationRecords($objectId)
    {
        return DB::table('spectrum_conservation')
            ->where('object_id', $objectId)
            ->orderBy('treatment_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get information object by ID with i18n data
     */
    protected function getInformationObjectById(int $id): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $id)
            ->select('io.*', 'i18n.title', 'slug.slug')
            ->first();
    }

    /**
     * Get condition photos for a condition check
     */
    protected function getConditionPhotos(int $conditionCheckId): array
    {
        return DB::table('spectrum_condition_photo')
            ->where('condition_check_id', $conditionCheckId)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get upload directory
     */
    protected function getUploadDir(): string
    {
        return class_exists('sfConfig')
            ? sfConfig::get('sf_upload_dir')
            : sfConfig::get('sf_upload_dir', sfConfig::get('sf_root_dir') . '/uploads');
    }

    /**
     * Get plugins directory
     */
    protected function getPluginsDir(): string
    {
        return class_exists('sfConfig')
            ? sfConfig::get('sf_plugins_dir')
            : sfConfig::get('sf_plugins_dir', sfConfig::get('sf_root_dir') . '/plugins');
    }

    /**
     * Generate PDF report
     */
    protected function generatePdfReport($conditionCheck, $object, $photos, $conservation)
    {
        // Check for TCPDF or similar
        if (!class_exists('TCPDF')) {
            // Try to use Dompdf
            if (class_exists('Dompdf\Dompdf')) {
                return $this->generatePdfWithDompdf($conditionCheck, $object, $photos, $conservation);
            }

            $this->error('No PDF library available (TCPDF or Dompdf)');
            return false;
        }

        $objectTitle = $object->title ?? $object->slug ?? 'Unknown';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('AtoM Spectrum Plugin');
        $pdf->SetAuthor($conditionCheck->checked_by ?? 'Unknown');
        $pdf->SetTitle('Condition Report - ' . $objectTitle);

        // Set margins
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Add page
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'Condition Report', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(5);

        // Object info
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Object Information', 0, 1);
        $pdf->SetFont('helvetica', '', 11);

        $pdf->Cell(50, 6, 'Title:', 0, 0);
        $pdf->Cell(0, 6, $objectTitle, 0, 1);

        $pdf->Cell(50, 6, 'Reference Number:', 0, 0);
        $pdf->Cell(0, 6, $object->identifier ?? 'N/A', 0, 1);

        $pdf->Ln(5);

        // Condition check info
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Condition Check Details', 0, 1);
        $pdf->SetFont('helvetica', '', 11);

        $pdf->Cell(50, 6, 'Reference:', 0, 0);
        $pdf->Cell(0, 6, $conditionCheck->condition_check_reference ?? 'N/A', 0, 1);

        $pdf->Cell(50, 6, 'Check Date:', 0, 0);
        $pdf->Cell(0, 6, $conditionCheck->check_date ?? 'N/A', 0, 1);

        $pdf->Cell(50, 6, 'Checked By:', 0, 0);
        $pdf->Cell(0, 6, $conditionCheck->checked_by ?? 'N/A', 0, 1);

        $pdf->Cell(50, 6, 'Check Reason:', 0, 0);
        $pdf->Cell(0, 6, $conditionCheck->check_reason ?? 'N/A', 0, 1);

        $pdf->Cell(50, 6, 'Condition:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($conditionCheck->condition_status ?? 'N/A'), 0, 1);

        $pdf->Cell(50, 6, 'Completeness:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($conditionCheck->completeness ?? 'N/A'), 0, 1);

        // Condition description
        if (!empty($conditionCheck->condition_description)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, 'Condition Description:', 0, 1);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(0, 5, $conditionCheck->condition_description, 0, 'L');
        }

        // Hazards
        if (!empty($conditionCheck->hazards_noted)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, 'Hazards Noted:', 0, 1);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(0, 5, $conditionCheck->hazards_noted, 0, 'L');
        }

        // Recommendations
        if (!empty($conditionCheck->recommendations)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, 'Recommendations:', 0, 1);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->MultiCell(0, 5, $conditionCheck->recommendations, 0, 'L');
        }

        // Photos
        if (!empty($photos)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Condition Photos', 0, 1);

            $x = 15;
            $y = $pdf->GetY() + 5;
            $photoWidth = 85;
            $photoHeight = 65;
            $count = 0;
            $uploadDir = $this->getUploadDir();

            foreach ($photos as $photo) {
                $photoObj = is_array($photo) ? (object) $photo : $photo;
                $photoPath = $uploadDir . '/' . $photoObj->file_path;

                if (file_exists($photoPath)) {
                    if ($count > 0 && $count % 2 == 0) {
                        $x = 15;
                        $y += $photoHeight + 25;
                    }

                    if ($y > 250) {
                        $pdf->AddPage();
                        $y = 20;
                        $x = 15;
                    }

                    $pdf->Image($photoPath, $x, $y, $photoWidth, $photoHeight, '', '', '', false, 150);

                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->SetXY($x, $y + $photoHeight + 2);
                    $pdf->Cell($photoWidth, 4, $photoObj->caption ?? $photoObj->photo_type ?? '', 0, 0, 'C');

                    $x += $photoWidth + 10;
                    $count++;
                }
            }
        }

        // Conservation history
        if (!empty($conservation)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Conservation History', 0, 1);
            $pdf->SetFont('helvetica', '', 11);

            foreach ($conservation as $record) {
                $recordObj = is_array($record) ? (object) $record : $record;

                $pdf->Ln(3);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 6, ($recordObj->conservation_reference ?? '') . ' - ' . ($recordObj->treatment_date ?? 'N/A'), 0, 1);
                $pdf->SetFont('helvetica', '', 10);

                if (!empty($recordObj->treatment_performed)) {
                    $pdf->MultiCell(0, 5, 'Treatment: ' . $recordObj->treatment_performed, 0, 'L');
                }

                if (!empty($recordObj->conservator_name)) {
                    $pdf->Cell(0, 5, 'Conservator: ' . $recordObj->conservator_name, 0, 1);
                }
            }
        }

        // Save PDF
        $uploadDir = $this->getUploadDir();
        $outputDir = $uploadDir . '/spectrum/reports/' . date('Y/m');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'condition_report_' . $conditionCheck->id . '_' . date('Ymd_His') . '.pdf';
        $outputPath = $outputDir . '/' . $filename;

        $pdf->Output($outputPath, 'F');

        return 'spectrum/reports/' . date('Y/m') . '/' . $filename;
    }

    /**
     * Generate PDF with Dompdf
     */
    protected function generatePdfWithDompdf($conditionCheck, $object, $photos, $conservation)
    {
        $html = $this->generateHtmlContent($conditionCheck, $object, $photos, $conservation);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $uploadDir = $this->getUploadDir();
        $outputDir = $uploadDir . '/spectrum/reports/' . date('Y/m');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'condition_report_' . $conditionCheck->id . '_' . date('Ymd_His') . '.pdf';
        $outputPath = $outputDir . '/' . $filename;

        file_put_contents($outputPath, $dompdf->output());

        return 'spectrum/reports/' . date('Y/m') . '/' . $filename;
    }

    /**
     * Generate HTML report
     */
    protected function generateHtmlReport($conditionCheck, $object, $photos, $conservation)
    {
        $html = $this->generateHtmlContent($conditionCheck, $object, $photos, $conservation);

        $uploadDir = $this->getUploadDir();
        $outputDir = $uploadDir . '/spectrum/reports/' . date('Y/m');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'condition_report_' . $conditionCheck->id . '_' . date('Ymd_His') . '.html';
        $outputPath = $outputDir . '/' . $filename;

        file_put_contents($outputPath, $html);

        return 'spectrum/reports/' . date('Y/m') . '/' . $filename;
    }

    /**
     * Generate HTML content
     */
    protected function generateHtmlContent($conditionCheck, $object, $photos, $conservation)
    {
        $pluginsDir = $this->getPluginsDir();
        $templatePath = $pluginsDir . '/ahgSpectrumPlugin/templates/_conditionReportHtml.php';

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }

        // Fallback: generate basic HTML
        $objectTitle = $object->title ?? $object->slug ?? 'Unknown';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>Condition Report - ' . htmlspecialchars($objectTitle) . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:20px;}h1{color:#333;}</style>';
        $html .= '</head><body>';
        $html .= '<h1>Condition Report</h1>';
        $html .= '<h2>' . htmlspecialchars($objectTitle) . '</h2>';
        $html .= '<p><strong>Reference:</strong> ' . htmlspecialchars($conditionCheck->condition_check_reference ?? 'N/A') . '</p>';
        $html .= '<p><strong>Check Date:</strong> ' . htmlspecialchars($conditionCheck->check_date ?? 'N/A') . '</p>';
        $html .= '<p><strong>Condition:</strong> ' . htmlspecialchars(ucfirst($conditionCheck->condition_status ?? 'N/A')) . '</p>';

        if (!empty($conditionCheck->condition_description)) {
            $html .= '<h3>Condition Description</h3>';
            $html .= '<p>' . nl2br(htmlspecialchars($conditionCheck->condition_description)) . '</p>';
        }

        if (!empty($conditionCheck->recommendations)) {
            $html .= '<h3>Recommendations</h3>';
            $html .= '<p>' . nl2br(htmlspecialchars($conditionCheck->recommendations)) . '</p>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate DOCX report
     */
    protected function generateDocxReport($conditionCheck, $object, $photos, $conservation)
    {
        // Requires PhpWord
        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            $this->error('PhpWord not available');
            return false;
        }

        $objectTitle = $object->title ?? $object->slug ?? 'Unknown';

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // Add title page
        $section = $phpWord->addSection();
        $section->addText('Condition Report', ['bold' => true, 'size' => 24], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $section->addText($objectTitle, ['size' => 18], ['alignment' => 'center']);

        // Add content...
        // (Similar structure to PDF generation)

        $uploadDir = $this->getUploadDir();
        $outputDir = $uploadDir . '/spectrum/reports/' . date('Y/m');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'condition_report_' . $conditionCheck->id . '_' . date('Ymd_His') . '.docx';
        $outputPath = $outputDir . '/' . $filename;

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return 'spectrum/reports/' . date('Y/m') . '/' . $filename;
    }
}