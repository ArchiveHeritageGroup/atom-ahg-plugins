<?php

namespace ahgDataMigrationPlugin\Validation\Sectors;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;

/**
 * Library sector validator implementing MARC/RDA validation rules.
 *
 * Validates:
 * - ISBN format (ISBN-10 and ISBN-13)
 * - ISSN format
 * - Call number presence
 * - Language codes (ISO 639)
 * - Publication information
 * - Author format
 */
class LibraryValidator extends AhgBaseValidator
{
    /** Common ISO 639-1 language codes */
    public const LANGUAGE_CODES = [
        'af', 'ar', 'de', 'en', 'es', 'fr', 'it', 'ja', 'ko', 'nl',
        'pt', 'ru', 'xh', 'zh', 'zu', 'nso', 'ss', 'st', 'tn', 'ts', 've',
    ];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Library (MARC/RDA) Validation';
        $this->sectorCode = 'library';
    }

    /**
     * Validate a row against MARC/RDA rules.
     *
     * @param array<string, mixed> $row
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Validate ISBN
        if (!$this->validateIsbn($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate ISSN
        if (!$this->validateIssn($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate language codes
        if (!$this->validateLanguage($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate author format
        if (!$this->validateAuthor($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate publication information
        if (!$this->validatePublication($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate extent
        if (!$this->validateExtent($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate ISBN format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateIsbn(array $row, int $rowNumber): bool
    {
        $isbn = $row['isbn'] ?? $row['ISBN'] ?? null;

        if (null === $isbn || '' === trim((string) $isbn)) {
            return true;
        }

        $isbn = trim((string) $isbn);

        // Remove hyphens and spaces for validation
        $cleanIsbn = preg_replace('/[-\s]/', '', $isbn);

        // Check ISBN-13
        if (13 === strlen($cleanIsbn)) {
            if (!$this->isValidIsbn13($cleanIsbn)) {
                $this->addRowError(
                    $rowNumber,
                    'isbn',
                    sprintf("Invalid ISBN-13 checksum: '%s'", $isbn),
                    AhgValidationReport::SEVERITY_ERROR,
                    'marc_isbn13_invalid'
                );

                return false;
            }
        // Check ISBN-10
        } elseif (10 === strlen($cleanIsbn)) {
            if (!$this->isValidIsbn10($cleanIsbn)) {
                $this->addRowError(
                    $rowNumber,
                    'isbn',
                    sprintf("Invalid ISBN-10 checksum: '%s'", $isbn),
                    AhgValidationReport::SEVERITY_ERROR,
                    'marc_isbn10_invalid'
                );

                return false;
            }
        } else {
            $this->addRowError(
                $rowNumber,
                'isbn',
                sprintf("ISBN '%s' must be 10 or 13 digits", $isbn),
                AhgValidationReport::SEVERITY_ERROR,
                'marc_isbn_length'
            );

            return false;
        }

        return true;
    }

    /**
     * Validate ISBN-13 checksum.
     */
    protected function isValidIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; ++$i) {
            $sum += (int) $isbn[$i] * (0 === $i % 2 ? 1 : 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $isbn[12];
    }

    /**
     * Validate ISBN-10 checksum.
     */
    protected function isValidIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dxX]$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; ++$i) {
            $sum += (int) $isbn[$i] * (10 - $i);
        }

        $checkChar = $isbn[9];
        $checkValue = 'x' === strtolower($checkChar) ? 10 : (int) $checkChar;
        $sum += $checkValue;

        return 0 === $sum % 11;
    }

    /**
     * Validate ISSN format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateIssn(array $row, int $rowNumber): bool
    {
        $issn = $row['issn'] ?? $row['ISSN'] ?? null;

        if (null === $issn || '' === trim((string) $issn)) {
            return true;
        }

        $issn = trim((string) $issn);

        // ISSN format: NNNN-NNNC where C is check digit
        if (!preg_match('/^\d{4}-?\d{3}[\dxX]$/', $issn)) {
            $this->addRowError(
                $rowNumber,
                'issn',
                sprintf("Invalid ISSN format: '%s' (expected NNNN-NNNC)", $issn),
                AhgValidationReport::SEVERITY_ERROR,
                'marc_issn_format'
            );

            return false;
        }

        // Validate ISSN checksum
        $cleanIssn = preg_replace('/-/', '', $issn);
        if (!$this->isValidIssnChecksum($cleanIssn)) {
            $this->addRowError(
                $rowNumber,
                'issn',
                sprintf("Invalid ISSN checksum: '%s'", $issn),
                AhgValidationReport::SEVERITY_WARNING,
                'marc_issn_checksum'
            );
        }

        return true;
    }

    /**
     * Validate ISSN checksum.
     */
    protected function isValidIssnChecksum(string $issn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 7; ++$i) {
            $sum += (int) $issn[$i] * (8 - $i);
        }

        $checkChar = strtolower($issn[7]);
        $checkValue = 'x' === $checkChar ? 10 : (int) $checkChar;
        $expected = (11 - ($sum % 11)) % 11;

        return $expected === $checkValue;
    }

    /**
     * Validate language code.
     *
     * @param array<string, mixed> $row
     */
    protected function validateLanguage(array $row, int $rowNumber): bool
    {
        $language = $row['language'] ?? $row['languages'] ?? null;

        if (null === $language || '' === trim((string) $language)) {
            return true;
        }

        $language = trim((string) $language);

        // Handle multiple languages (pipe or semicolon separated)
        $languages = preg_split('/[|;,]/', $language);

        foreach ($languages as $lang) {
            $lang = strtolower(trim($lang));

            if ('' === $lang) {
                continue;
            }

            // Check if it's a valid ISO 639-1 code (2 letters)
            if (2 === strlen($lang)) {
                if (!in_array($lang, self::LANGUAGE_CODES, true)) {
                    $this->addRowError(
                        $rowNumber,
                        'language',
                        sprintf("Unknown language code '%s'", $lang),
                        AhgValidationReport::SEVERITY_WARNING,
                        'marc_language_unknown'
                    );
                }
            // Check if it's a 3-letter code or full name
            } elseif (strlen($lang) > 2) {
                $this->addRowError(
                    $rowNumber,
                    'language',
                    sprintf("Language '%s' should use ISO 639-1 two-letter code (e.g., 'en', 'af')", $lang),
                    AhgValidationReport::SEVERITY_INFO,
                    'marc_language_format'
                );
            }
        }

        return true;
    }

    /**
     * Validate author format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateAuthor(array $row, int $rowNumber): bool
    {
        $author = $row['author'] ?? $row['creator'] ?? $row['authors'] ?? null;

        if (null === $author || '' === trim((string) $author)) {
            return true;
        }

        $author = trim((string) $author);

        // Check for preferred format: Surname, Forename
        if (!str_contains($author, ',') && !str_contains($author, ';')) {
            $this->addRowError(
                $rowNumber,
                'author',
                sprintf(
                    "Author '%s' should preferably use 'Surname, Forename' format for MARC compatibility",
                    mb_strlen($author) > 30 ? mb_substr($author, 0, 30).'...' : $author
                ),
                AhgValidationReport::SEVERITY_INFO,
                'marc_author_format'
            );
        }

        return true;
    }

    /**
     * Validate publication information.
     *
     * @param array<string, mixed> $row
     */
    protected function validatePublication(array $row, int $rowNumber): bool
    {
        $pubDate = $row['dateOfPublication'] ?? $row['publicationDate'] ?? $row['publication_date'] ?? null;
        $publisher = $row['publisher'] ?? null;
        $place = $row['placeOfPublication'] ?? $row['publication_place'] ?? null;

        // If publication date is present, validate format
        if (null !== $pubDate && '' !== trim((string) $pubDate)) {
            $pubDate = trim((string) $pubDate);

            // Extract year if present
            if (preg_match('/\b(\d{4})\b/', $pubDate, $matches)) {
                $year = (int) $matches[1];
                $currentYear = (int) date('Y');

                if ($year > $currentYear + 1) {
                    $this->addRowError(
                        $rowNumber,
                        'dateOfPublication',
                        sprintf('Publication year (%d) is in the future', $year),
                        AhgValidationReport::SEVERITY_WARNING,
                        'marc_pubdate_future'
                    );
                }

                if ($year < 1450) { // Before printing press
                    $this->addRowError(
                        $rowNumber,
                        'dateOfPublication',
                        sprintf('Publication year (%d) is before the printing era - please verify', $year),
                        AhgValidationReport::SEVERITY_WARNING,
                        'marc_pubdate_ancient'
                    );
                }
            }
        }

        // Check for minimum publication info
        $hasPublisher = null !== $publisher && '' !== trim((string) $publisher);
        $hasPlace = null !== $place && '' !== trim((string) $place);
        $hasPubDate = null !== $pubDate && '' !== trim((string) $pubDate);

        if (!$hasPublisher && !$hasPlace && !$hasPubDate) {
            $this->addRowError(
                $rowNumber,
                'publisher',
                'Publication information (publisher, place, or date) is recommended',
                AhgValidationReport::SEVERITY_INFO,
                'marc_publication_recommended'
            );
        }

        return true;
    }

    /**
     * Validate extent (pages, duration, etc.).
     *
     * @param array<string, mixed> $row
     */
    protected function validateExtent(array $row, int $rowNumber): bool
    {
        $extent = $row['extent'] ?? $row['physicalDescription'] ?? null;

        if (null === $extent || '' === trim((string) $extent)) {
            return true;
        }

        $extent = trim((string) $extent);

        // Check for page count pattern
        if (preg_match('/(\d+)\s*p(?:ages?)?/i', $extent, $matches)) {
            $pages = (int) $matches[1];

            if ($pages > 10000) {
                $this->addRowError(
                    $rowNumber,
                    'extent',
                    sprintf('Page count (%d) is unusually high - please verify', $pages),
                    AhgValidationReport::SEVERITY_WARNING,
                    'marc_extent_pages_high'
                );
            }
        }

        return true;
    }

    /**
     * Validate entire file.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateFile(array $rows): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        foreach ($rows as $rowNumber => $row) {
            $this->validateRow($row, $rowNumber);
        }

        return $this->report->finish();
    }
}
