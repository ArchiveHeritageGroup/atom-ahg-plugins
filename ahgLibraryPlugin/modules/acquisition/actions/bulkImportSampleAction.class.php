<?php

declare(strict_types=1);

/**
 * bulkImportSampleAction — serves a sample CSV template for bulk import.
 *
 * GET /acquisition/bulk-import-sample
 *
 * @package ahgLibraryPlugin
 */
class bulkImportSampleAction extends AhgController
{
    public function execute($request)
    {
        $csv = implode("\n", [
            'title;author;isbn;issn;doi;publisher;publication_date;publication_place;edition_statement;material_type;language;call_number;dewey_decimal;pagination;description;subjects;barcode;copy_count;location',
            'Introduction to Archival Science;John Smith;9780123456789;;;Routledge;2020;London;2nd edition;book;English;Z672.2.I58 2020;020.72;450;An introduction to archival theory;archival science;records management;9780123456789;1;Main Library',
            'Records Management in South Africa;Jane Doe;9780987654321;;;UNISA Press;2019;Pretoria;;book;English;CDD 020.72368;320;Records management practices in the South African public sector;records management;public sector;0987654321;2;Reference Section',
            'Digital Preservation Handbook;Alan Brown;978-1-234-56789-0;;;Digital Preservation Coalition;2021;London;3rd edition;book;English;Z701.3.P73 2021;280;The essential guide to digital preservation;digital preservation;digital archives;9781234567890;1;Digital Archives',
        ]);

        $response = $this->getResponse();
        $response->setContentType('text/csv; charset=utf-8');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="library_import_sample.csv"');
        $response->setHttpHeader('Pragma', 'no-cache');
        $response->setHttpHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $this->renderText($csv);
    }
}
