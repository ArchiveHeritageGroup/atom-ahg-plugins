<?php

namespace AhgIngestPlugin\Services;

/**
 * Converts one worksheet of an Excel workbook (.xlsx / .xls) into a UTF-8
 * CSV file with the same shape the ingest wizard already parses: the first
 * row is the header row, every following non-empty row is a record.
 *
 * Cell values are written as the text a cataloguer expects to see:
 * - text is passed through unchanged (diacritics and non-Latin scripts kept);
 * - date-formatted cells become ISO text (YYYY-MM-DD, or YYYY-MM-DD HH:MM:SS
 *   when a time part is present, or HH:MM:SS for a time-only cell);
 * - numbers are written without binary float noise (0.1+0.2 gives 0.3);
 * - formulas are written as their value, never as the formula text.
 *
 * ponytail: the whole sheet is loaded into memory by PhpSpreadsheet. Fine for
 * cataloguing spreadsheets (tens of thousands of rows); a chunked read filter
 * is the upgrade path if very large workbooks become common.
 */
class XlsxConverter
{
    public const EXTENSIONS = ['xlsx', 'xls'];

    public static function isSpreadsheet(string $fileName): bool
    {
        return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    protected static function boot(): void
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            $root = class_exists('sfConfig', false)
                ? \sfConfig::get('sf_root_dir')
                : dirname(__DIR__, 4);
            $autoload = $root . '/atom-framework/vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('Spreadsheet support (PhpSpreadsheet) is not installed.');
        }
    }

    protected static function reader(string $path): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        self::boot();
        if (!is_readable($path)) {
            throw new \RuntimeException('Spreadsheet file not found or not readable.');
        }

        // Only the Excel readers: a renamed text or HTML file must fail here
        // rather than be read by the CSV / HTML readers as a "workbook".
        return \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path, [
            \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLSX,
            \PhpOffice\PhpSpreadsheet\IOFactory::READER_XLS,
        ]);
    }

    /**
     * Worksheets in workbook order: [['index' => 0, 'name' => 'Sheet1', 'rows' => 12], ...].
     * 'rows' is the sheet's used range height (header included), as Excel reports it.
     */
    public static function listSheets(string $path): array
    {
        $sheets = [];
        foreach (self::reader($path)->listWorksheetInfo($path) as $i => $info) {
            $sheets[] = [
                'index' => $i,
                'name' => (string) $info['worksheetName'],
                'rows' => (int) $info['totalRows'],
                'columns' => (int) $info['totalColumns'],
            ];
        }

        return $sheets;
    }

    /**
     * Read one worksheet as [headers, rows]. Rows are lists of strings aligned
     * to the headers; fully empty rows are skipped.
     *
     * @param int|string $sheet worksheet index (0-based) or name
     */
    public static function readSheet(string $path, $sheet = 0): array
    {
        $reader = self::reader($path);
        $names = array_column(self::listSheets($path), 'name');
        if (empty($names)) {
            throw new \RuntimeException('The workbook has no worksheets.');
        }
        if (is_int($sheet) || ctype_digit((string) $sheet)) {
            $name = $names[(int) $sheet] ?? null;
        } else {
            $name = in_array($sheet, $names, true) ? $sheet : null;
        }
        if (null === $name) {
            throw new \RuntimeException('Worksheet not found in the workbook.');
        }

        // Styles are needed (not read-data-only): date detection depends on
        // the cell's number format.
        $reader->setLoadSheetsOnly([$name]);
        $book = $reader->load($path);
        $ws = $book->getSheetByName($name) ?? $book->getSheet(0);

        $maxRow = $ws->getHighestDataRow();
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestDataColumn());

        $grid = [];
        $lastUsedCol = 0;
        for ($r = 1; $r <= $maxRow; ++$r) {
            $line = [];
            $any = false;
            for ($c = 1; $c <= $maxCol; ++$c) {
                $v = $ws->cellExists([$c, $r]) ? self::cellText($ws->getCell([$c, $r])) : '';
                $line[] = $v;
                if ('' !== trim($v)) {
                    $any = true;
                    $lastUsedCol = max($lastUsedCol, $c);
                }
            }
            if ($any) {
                $grid[] = $line;
            }
        }
        $book->disconnectWorksheets();

        if (empty($grid)) {
            return ['sheet' => $name, 'headers' => [], 'rows' => []];
        }

        // Trim trailing columns that are empty in every row (formatting-only).
        foreach ($grid as &$line) {
            $line = array_slice($line, 0, $lastUsedCol);
        }
        unset($line);

        $headers = [];
        $seen = [];
        foreach (array_shift($grid) as $i => $h) {
            $h = trim(preg_replace('/\s+/u', ' ', $h));
            if ('' === $h) {
                $h = 'Column ' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            }
            // Duplicate header names would overwrite each other in the row map.
            $base = $h;
            $n = 2;
            while (isset($seen[$h])) {
                $h = $base . ' (' . $n++ . ')';
            }
            $seen[$h] = true;
            $headers[] = $h;
        }

        return ['sheet' => $name, 'headers' => $headers, 'rows' => $grid];
    }

    /**
     * Convert a worksheet to a UTF-8 CSV (comma, double-quote, no BOM).
     * Returns ['sheet' => name, 'headers' => [...], 'row_count' => n].
     */
    public static function toCsv(string $path, string $csvPath, $sheet = 0): array
    {
        $data = self::readSheet($path, $sheet);
        if (empty($data['headers'])) {
            throw new \RuntimeException('The worksheet "' . $data['sheet'] . '" is empty.');
        }

        $fh = fopen($csvPath, 'w');
        if (!$fh) {
            throw new \RuntimeException('Could not write the converted CSV file.');
        }
        fputcsv($fh, $data['headers']);
        foreach ($data['rows'] as $row) {
            fputcsv($fh, array_pad($row, count($data['headers']), ''));
        }
        fclose($fh);

        return ['sheet' => $data['sheet'], 'headers' => $data['headers'], 'row_count' => count($data['rows'])];
    }

    /**
     * The text for one cell.
     */
    public static function cellText(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string
    {
        $value = $cell->getValue();

        if ($cell->isFormula()) {
            // Prefer the value Excel cached when it saved the file; calculate
            // only when there is none (files written by other tools).
            $value = $cell->getOldCalculatedValue();
            if (null === $value) {
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Throwable $e) {
                    $value = '';
                }
            }
        }

        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $value->getPlainText();
        }
        if (null === $value) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell, $value)) {
                return self::excelDateToIso($value);
            }

            return self::numberText($value, (string) $cell->getStyle()->getNumberFormat()->getFormatCode());
        }

        return (string) $value;
    }

    public static function excelDateToIso($serial): string
    {
        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $serial, 'UTC');
        $hasDate = floor((float) $serial) > 0;
        $hasTime = abs((float) $serial - floor((float) $serial)) > 1e-9;
        if ($hasDate && !$hasTime) {
            return $dt->format('Y-m-d');
        }
        if (!$hasDate) {
            return $dt->format('H:i:s');
        }

        return $dt->format('Y-m-d H:i:s');
    }

    public static function numberText($value, string $format = 'General'): string
    {
        if (is_float($value) && (is_nan($value) || is_infinite($value))) {
            return '';
        }
        if (is_int($value) || (floor($value) == $value && abs($value) < 1e15)) {
            $text = sprintf('%.0f', $value);
            if ('-0' === $text) {
                $text = '0';
            }
        } else {
            // 15 significant digits is what Excel itself displays; it drops the
            // binary representation noise (0.30000000000000004 -> 0.3).
            $text = sprintf('%.15G', $value);
            if (false !== stripos($text, 'E')) {
                $text = rtrim(rtrim(number_format((float) $text, 15, '.', ''), '0'), '.');
            }
        }

        // Zero-padded number formats ("00000") are how identifiers like 00042
        // are usually kept in Excel; honour the padding, nothing else.
        if (preg_match('/^0+$/', $format) && ctype_digit($text) && strlen($text) < strlen($format)) {
            $text = str_pad($text, strlen($format), '0', STR_PAD_LEFT);
        }

        return $text;
    }
}
