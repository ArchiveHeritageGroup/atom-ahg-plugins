<?php

declare(strict_types=1);

/**
 * LibraryItem Model
 *
 * Represents a library/book collection item with MARC-inspired metadata
 * Uses Laravel Query Builder for all database operations
 *
 * @package    ahgLibraryPlugin
 * @subpackage Model
 */

use Illuminate\Database\Capsule\Manager as DB;

class LibraryItem
{
    // Material types
    public const TYPE_MONOGRAPH = 'monograph';
    public const TYPE_SERIAL = 'serial';
    public const TYPE_VOLUME = 'volume';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_CHAPTER = 'chapter';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_MANUSCRIPT = 'manuscript';
    public const TYPE_MAP = 'map';
    public const TYPE_PAMPHLET = 'pamphlet';
    public const TYPE_SCORE = 'score';
    public const TYPE_ELECTRONIC = 'electronic';

    // Classification schemes
    public const SCHEME_DEWEY = 'dewey';
    public const SCHEME_LCC = 'lcc';
    public const SCHEME_UDC = 'udc';
    public const SCHEME_BLISS = 'bliss';
    public const SCHEME_COLON = 'colon';
    public const SCHEME_CUSTOM = 'custom';

    // Circulation status
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ON_LOAN = 'on_loan';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_LOST = 'lost';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_REFERENCE = 'reference';
    public const STATUS_RESERVED = 'reserved';

    // Cataloging rules
    public const RULES_AACR2 = 'aacr2';
    public const RULES_RDA = 'rda';
    public const RULES_ISBD = 'isbd';

    protected static string $table = 'library_item';

    public ?int $id = null;
    public ?int $information_object_id = null;
    public string $material_type = self::TYPE_MONOGRAPH;
    public ?string $call_number = null;
    public ?string $classification_scheme = null;
    public ?string $classification_number = null;
    public ?string $cutter_number = null;
    public ?string $shelf_location = null;
    public ?string $copy_number = null;
    public ?string $volume_designation = null;
    public ?string $isbn = null;
    public ?string $issn = null;
    public ?string $lccn = null;
    public ?string $oclc_number = null;
    public ?string $doi = null;
    public ?string $barcode = null;
    public ?string $edition = null;
    public ?string $edition_statement = null;
    public ?string $publisher = null;
    public ?string $publication_place = null;
    public ?string $publication_date = null;
    public ?string $copyright_date = null;
    public ?string $printing = null;
    public ?string $pagination = null;
    public ?string $dimensions = null;
    public ?string $physical_details = null;
    public ?string $accompanying_material = null;
    public ?string $series_title = null;
    public ?string $series_number = null;
    public ?string $series_issn = null;
    public ?string $subseries_title = null;
    public ?string $general_note = null;
    public ?string $bibliography_note = null;
    public ?string $contents_note = null;
    public ?string $summary = null;
    public ?string $target_audience = null;
    public ?string $system_requirements = null;
    public ?string $binding_note = null;
    public ?string $frequency = null;
    public ?string $former_frequency = null;
    public ?string $numbering_peculiarities = null;
    public ?string $publication_start_date = null;
    public ?string $publication_end_date = null;
    public ?string $publication_status = null;
    public int $total_copies = 1;
    public int $available_copies = 1;
    public string $circulation_status = self::STATUS_AVAILABLE;
    public ?string $cataloging_source = null;
    public ?string $cataloging_rules = null;
    public ?string $encoding_level = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    // Related data (loaded separately)
    public array $creators = [];
    public array $subjects = [];
    public array $copies = [];
    public array $serial_holdings = [];

    /**
     * Create from database row
     */
    public static function fromRow(object $row): self
    {
        $item = new self();

        foreach ($row as $key => $value) {
            if (property_exists($item, $key)) {
                $item->{$key} = $value;
            }
        }

        return $item;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $item = new self();

        foreach ($data as $key => $value) {
            $property = self::snakeToCamel($key);
            if (property_exists($item, $key)) {
                $item->{$key} = $value;
            } elseif (property_exists($item, $property)) {
                $item->{$property} = $value;
            }
        }

        return $item;
    }

    /**
     * Convert to array for database insert/update
     */
    public function toArray(): array
    {
        return [
            'information_object_id' => $this->information_object_id,
            'material_type' => $this->material_type,
            'call_number' => $this->call_number,
            'classification_scheme' => $this->classification_scheme,
            'classification_number' => $this->classification_number,
            'cutter_number' => $this->cutter_number,
            'shelf_location' => $this->shelf_location,
            'copy_number' => $this->copy_number,
            'volume_designation' => $this->volume_designation,
            'isbn' => $this->isbn,
            'issn' => $this->issn,
            'lccn' => $this->lccn,
            'oclc_number' => $this->oclc_number,
            'doi' => $this->doi,
            'barcode' => $this->barcode,
            'edition' => $this->edition,
            'edition_statement' => $this->edition_statement,
            'publisher' => $this->publisher,
            'publication_place' => $this->publication_place,
            'publication_date' => $this->publication_date,
            'copyright_date' => $this->copyright_date,
            'printing' => $this->printing,
            'pagination' => $this->pagination,
            'dimensions' => $this->dimensions,
            'physical_details' => $this->physical_details,
            'accompanying_material' => $this->accompanying_material,
            'series_title' => $this->series_title,
            'series_number' => $this->series_number,
            'series_issn' => $this->series_issn,
            'subseries_title' => $this->subseries_title,
            'general_note' => $this->general_note,
            'bibliography_note' => $this->bibliography_note,
            'contents_note' => $this->contents_note,
            'summary' => $this->summary,
            'target_audience' => $this->target_audience,
            'system_requirements' => $this->system_requirements,
            'binding_note' => $this->binding_note,
            'frequency' => $this->frequency,
            'former_frequency' => $this->former_frequency,
            'numbering_peculiarities' => $this->numbering_peculiarities,
            'publication_start_date' => $this->publication_start_date,
            'publication_end_date' => $this->publication_end_date,
            'publication_status' => $this->publication_status,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'circulation_status' => $this->circulation_status,
            'cataloging_source' => $this->cataloging_source,
            'cataloging_rules' => $this->cataloging_rules,
            'encoding_level' => $this->encoding_level,
        ];
    }

    /**
     * Get material type options
     */
    public static function getMaterialTypes(): array
    {
        return [
            self::TYPE_MONOGRAPH => 'Monograph / Book',
            self::TYPE_SERIAL => 'Serial / Journal',
            self::TYPE_VOLUME => 'Volume',
            self::TYPE_ISSUE => 'Issue',
            self::TYPE_CHAPTER => 'Chapter',
            self::TYPE_ARTICLE => 'Article',
            self::TYPE_MANUSCRIPT => 'Manuscript',
            self::TYPE_MAP => 'Map',
            self::TYPE_PAMPHLET => 'Pamphlet',
            self::TYPE_SCORE => 'Musical Score',
            self::TYPE_ELECTRONIC => 'Electronic Resource',
        ];
    }

    /**
     * Get classification scheme options
     */
    public static function getClassificationSchemes(): array
    {
        return [
            self::SCHEME_DEWEY => 'Dewey Decimal Classification (DDC)',
            self::SCHEME_LCC => 'Library of Congress Classification (LCC)',
            self::SCHEME_UDC => 'Universal Decimal Classification (UDC)',
            self::SCHEME_BLISS => 'Bliss Bibliographic Classification',
            self::SCHEME_COLON => 'Colon Classification',
            self::SCHEME_CUSTOM => 'Custom / Local Scheme',
        ];
    }

    /**
     * Get circulation status options
     */
    public static function getCirculationStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_ON_LOAN => 'On Loan',
            self::STATUS_PROCESSING => 'In Processing',
            self::STATUS_LOST => 'Lost',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_REFERENCE => 'Reference Only',
            self::STATUS_RESERVED => 'Reserved',
        ];
    }

    /**
     * Get cataloging rules options
     */
    public static function getCatalogingRules(): array
    {
        return [
            self::RULES_AACR2 => 'AACR2',
            self::RULES_RDA => 'RDA',
            self::RULES_ISBD => 'ISBD',
        ];
    }

    /**
     * Get frequency options for serials
     */
    public static function getFrequencies(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'semimonthly' => 'Semimonthly',
            'monthly' => 'Monthly',
            'bimonthly' => 'Bimonthly',
            'quarterly' => 'Quarterly',
            'semiannual' => 'Semiannual',
            'annual' => 'Annual',
            'biennial' => 'Biennial',
            'triennial' => 'Triennial',
            'irregular' => 'Irregular',
        ];
    }

    /**
     * Check if this is a serial type
     */
    public function isSerial(): bool
    {
        return in_array($this->material_type, [
            self::TYPE_SERIAL,
            self::TYPE_ISSUE,
            self::TYPE_ARTICLE,
        ]);
    }

    /**
     * Check if this is a monograph type
     */
    public function isMonograph(): bool
    {
        return in_array($this->material_type, [
            self::TYPE_MONOGRAPH,
            self::TYPE_VOLUME,
            self::TYPE_CHAPTER,
        ]);
    }

    /**
     * Get formatted call number
     */
    public function getFormattedCallNumber(): string
    {
        $parts = array_filter([
            $this->call_number,
            $this->copy_number ? 'c.' . $this->copy_number : null,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get primary creator name
     */
    public function getPrimaryCreator(): ?string
    {
        foreach ($this->creators as $creator) {
            if ($creator['is_primary'] ?? false) {
                return $creator['name'];
            }
        }

        return $this->creators[0]['name'] ?? null;
    }

    /**
     * Snake case to camel case conversion
     */
    protected static function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
}
