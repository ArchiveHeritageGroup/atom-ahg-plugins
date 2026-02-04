<?php

namespace ahgDataMigrationPlugin\Exporters;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Base class for sector-specific CSV exporters.
 */
abstract class BaseExporter
{
    protected array $data = [];
    protected array $errors = [];
    protected array $warnings = [];
    protected string $culture = 'en';

    /**
     * Get the sector code.
     */
    abstract public function getSectorCode(): string;

    /**
     * Get the required CSV columns for AtoM import.
     */
    abstract public function getColumns(): array;

    /**
     * Map a transformed record to the sector-specific CSV format.
     */
    abstract public function mapRecord(array $record): array;

    /**
     * Set the data to export.
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set culture for i18n fields.
     */
    public function setCulture(string $culture): self
    {
        $this->culture = $culture;

        return $this;
    }

    /**
     * Export to CSV string.
     */
    public function export(): string
    {
        $columns = $this->getColumns();
        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, $columns);

        // Write data rows
        foreach ($this->data as $record) {
            $mapped = $this->mapRecord($record);
            $row = [];
            foreach ($columns as $col) {
                $row[] = $mapped[$col] ?? '';
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export records directly from database.
     *
     * @param array<int> $objectIds Array of information_object IDs to export
     *
     * @return string CSV content
     */
    public function exportFromDatabase(array $objectIds): string
    {
        if (empty($objectIds)) {
            return $this->export(); // Return empty CSV with headers
        }

        $records = [];

        foreach ($objectIds as $id) {
            $record = $this->loadRecordFromDatabase((int) $id);
            if (null !== $record) {
                $records[] = $record;
            }
        }

        $this->setData($records);

        return $this->export();
    }

    /**
     * Load a single record from database.
     *
     * @param int $id information_object ID
     *
     * @return array|null Record data or null if not found
     */
    protected function loadRecordFromDatabase(int $id): ?array
    {
        // Get base information object
        $io = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as io_i18n', function ($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                    ->where('io_i18n.culture', '=', $this->culture);
            })
            ->leftJoin('slug', function ($join) {
                $join->on('io.id', '=', 'slug.object_id');
            })
            ->where('io.id', $id)
            ->select(
                'io.id',
                'io.identifier',
                'io.parent_id',
                'io.level_of_description_id',
                'io.repository_id',
                'io_i18n.title',
                'io_i18n.extent_and_medium',
                'io_i18n.archival_history',
                'io_i18n.acquisition',
                'io_i18n.scope_and_content',
                'io_i18n.appraisal',
                'io_i18n.accruals',
                'io_i18n.arrangement',
                'io_i18n.access_conditions',
                'io_i18n.reproduction_conditions',
                'io_i18n.physical_characteristics',
                'io_i18n.finding_aids',
                'io_i18n.location_of_originals',
                'io_i18n.location_of_copies',
                'io_i18n.related_units_of_description',
                'io_i18n.rules',
                'io_i18n.sources',
                'io_i18n.revision_history',
                'slug.slug'
            )
            ->first();

        if (!$io) {
            return null;
        }

        $record = (array) $io;

        // Get legacy ID from keymap
        $record['legacyId'] = $this->getLegacyId($id);

        // Get parent legacy ID
        if ($io->parent_id && $io->parent_id > 1) {
            $record['parentId'] = $this->getLegacyId($io->parent_id) ?: $this->getParentIdentifier($io->parent_id);
        }

        // Get level of description
        $record['levelOfDescription'] = $this->getLevelOfDescription($io->level_of_description_id);

        // Get repository name
        $record['repository'] = $this->getRepositoryName($io->repository_id);

        // Get dates
        $dates = $this->getDates($id);
        $record = array_merge($record, $dates);

        // Get creators
        $record['creators'] = $this->getCreators($id);

        // Get access points
        $record['subjectAccessPoints'] = $this->getAccessPoints($id, \QubitTaxonomy::SUBJECT_ID);
        $record['placeAccessPoints'] = $this->getAccessPoints($id, \QubitTaxonomy::PLACE_ID);
        $record['nameAccessPoints'] = $this->getNameAccessPoints($id);
        $record['genreAccessPoints'] = $this->getAccessPoints($id, \QubitTaxonomy::GENRE_ID);

        // Get digital object
        $digitalObject = $this->getDigitalObject($id);
        $record = array_merge($record, $digitalObject);

        // Get notes
        $record['notes'] = $this->getNotes($id);

        return $record;
    }

    /**
     * Get legacy ID from keymap.
     */
    protected function getLegacyId(int $id): ?string
    {
        $keymap = DB::table('keymap')
            ->where('target_id', $id)
            ->where('source_name', 'legacyId')
            ->first();

        return $keymap ? $keymap->source_id : null;
    }

    /**
     * Get parent identifier.
     */
    protected function getParentIdentifier(int $parentId): ?string
    {
        $parent = DB::table('information_object')
            ->where('id', $parentId)
            ->first();

        return $parent ? $parent->identifier : null;
    }

    /**
     * Get level of description name.
     */
    protected function getLevelOfDescription(?int $termId): ?string
    {
        if (!$termId) {
            return null;
        }

        $term = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', $this->culture)
            ->first();

        return $term ? $term->name : null;
    }

    /**
     * Get repository name.
     */
    protected function getRepositoryName(?int $repoId): ?string
    {
        if (!$repoId) {
            return null;
        }

        $repo = DB::table('actor_i18n')
            ->where('id', $repoId)
            ->where('culture', $this->culture)
            ->first();

        return $repo ? $repo->authorized_form_of_name : null;
    }

    /**
     * Get dates from events.
     *
     * @return array{dateRange: string|null, dateStart: string|null, dateEnd: string|null}
     */
    protected function getDates(int $id): array
    {
        $event = DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $this->culture);
            })
            ->where('e.object_id', $id)
            ->where('e.type_id', \QubitTerm::CREATION_ID)
            ->first();

        return [
            'dateRange' => $event ? $event->date : null,
            'dateStart' => $event ? $event->start_date : null,
            'dateEnd' => $event ? $event->end_date : null,
        ];
    }

    /**
     * Get creators.
     */
    protected function getCreators(int $id): ?string
    {
        $creators = DB::table('event as e')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('e.actor_id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->where('e.object_id', $id)
            ->where('e.type_id', \QubitTerm::CREATION_ID)
            ->pluck('ai.authorized_form_of_name')
            ->toArray();

        return !empty($creators) ? implode('|', $creators) : null;
    }

    /**
     * Get access points (subjects, places, genres).
     */
    protected function getAccessPoints(int $id, int $taxonomyId): ?string
    {
        $terms = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->where('otr.object_id', $id)
            ->where('t.taxonomy_id', $taxonomyId)
            ->pluck('ti.name')
            ->toArray();

        return !empty($terms) ? implode('|', $terms) : null;
    }

    /**
     * Get name access points.
     */
    protected function getNameAccessPoints(int $id): ?string
    {
        $names = DB::table('relation as r')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('r.object_id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->where('r.subject_id', $id)
            ->where('r.type_id', \QubitTerm::NAME_ACCESS_POINT_ID)
            ->pluck('ai.authorized_form_of_name')
            ->toArray();

        return !empty($names) ? implode('|', $names) : null;
    }

    /**
     * Get digital object info.
     *
     * @return array{digitalObjectPath: string|null, digitalObjectURI: string|null}
     */
    protected function getDigitalObject(int $id): array
    {
        $do = DB::table('digital_object')
            ->where('object_id', $id)
            ->first();

        if (!$do) {
            return ['digitalObjectPath' => null, 'digitalObjectURI' => null];
        }

        // Combine path and name for full file path
        $fullPath = null;
        if (!empty($do->path)) {
            $fullPath = rtrim($do->path, '/');
            if (!empty($do->name)) {
                $fullPath .= '/' . $do->name;
            }
        }

        return [
            'digitalObjectPath' => $fullPath,
            'digitalObjectURI' => $do->uri ?? null,
        ];
    }

    /**
     * Get general notes.
     */
    protected function getNotes(int $id): ?string
    {
        $notes = DB::table('note as n')
            ->join('note_i18n as ni', function ($join) {
                $join->on('n.id', '=', 'ni.id')
                    ->where('ni.culture', '=', $this->culture);
            })
            ->where('n.object_id', $id)
            ->where('n.type_id', \QubitTerm::GENERAL_NOTE_ID)
            ->pluck('ni.content')
            ->toArray();

        return !empty($notes) ? implode(' | ', $notes) : null;
    }

    /**
     * Get export filename.
     */
    public function getFilename(string $baseName): string
    {
        return pathinfo($baseName, PATHINFO_FILENAME).'_'.$this->getSectorCode().'_import.csv';
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
