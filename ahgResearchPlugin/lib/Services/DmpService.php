<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Data Management Plan service (Science Europe / Horizon Europe core structure).
 *
 * DMPs are owned by a researcher and optionally linked to a research_project.
 * Pure CRUD + completeness scoring + Markdown/JSON export over research_dmp /
 * research_dmp_dataset.
 */
class DmpService
{
    public const STATUSES = ['draft', 'active', 'final'];
    public const SENSITIVITIES = ['open', 'restricted', 'sensitive'];

    /** DMP narrative sections, in template order: column => label. */
    public const SECTIONS = [
        'data_description' => '1. Data summary',
        'fair_findable' => '2a. Findable',
        'fair_accessible' => '2b. Accessible',
        'fair_interoperable' => '2c. Interoperable',
        'fair_reusable' => '2d. Re-usable',
        'resources_costs' => '3. Allocation of resources',
        'data_security' => '4. Data security',
        'ethics_legal' => '5. Ethical aspects',
        'other_issues' => '6. Other issues',
    ];

    // ---- DMP CRUD ---------------------------------------------------------

    public function listForResearcher(int $researcherId): array
    {
        return DB::table('research_dmp as d')
            ->leftJoin('research_project as p', 'p.id', '=', 'd.project_id')
            ->where('d.researcher_id', $researcherId)
            ->orderByDesc('d.updated_at')
            ->get(['d.*', 'p.title as project_title'])
            ->all();
    }

    public function listForProject(int $projectId): array
    {
        return DB::table('research_dmp')->where('project_id', $projectId)->orderByDesc('updated_at')->get()->all();
    }

    public function get(int $id): ?object
    {
        return DB::table('research_dmp')->where('id', $id)->first();
    }

    public function ownsDmp(int $id, int $researcherId): bool
    {
        return DB::table('research_dmp')->where('id', $id)->where('researcher_id', $researcherId)->exists();
    }

    public function create(int $researcherId, array $data): int
    {
        $row = $this->mapFields($data);
        $row['researcher_id'] = $researcherId;
        $row['title'] = trim((string) ($data['title'] ?? '')) ?: 'Untitled DMP';

        return (int) DB::table('research_dmp')->insertGetId($row);
    }

    public function update(int $id, array $data): bool
    {
        $row = $this->mapFields($data);
        if (isset($data['title'])) {
            $row['title'] = trim((string) $data['title']) ?: 'Untitled DMP';
        }

        return DB::table('research_dmp')->where('id', $id)->update($row) >= 0;
    }

    public function delete(int $id): void
    {
        DB::table('research_dmp_dataset')->where('dmp_id', $id)->delete();
        DB::table('research_dmp')->where('id', $id)->delete();
    }

    private function mapFields(array $data): array
    {
        $row = [
            'project_id' => ($data['project_id'] ?? '') !== '' ? (int) $data['project_id'] : null,
            'funder' => $this->n($data['funder'] ?? null),
            'grant_number' => $this->n($data['grant_number'] ?? null),
            'status' => in_array($data['status'] ?? '', self::STATUSES, true) ? $data['status'] : 'draft',
            'version' => $this->n($data['version'] ?? null) ?? '1.0',
        ];
        foreach (array_keys(self::SECTIONS) as $col) {
            if (array_key_exists($col, $data)) {
                $row[$col] = $this->n($data[$col]);
            }
        }

        return $row;
    }

    // ---- datasets ---------------------------------------------------------

    public function datasets(int $dmpId): array
    {
        return DB::table('research_dmp_dataset')->where('dmp_id', $dmpId)->orderBy('id')->get()->all();
    }

    public function addDataset(int $dmpId, array $data): int
    {
        return (int) DB::table('research_dmp_dataset')->insertGetId([
            'dmp_id' => $dmpId,
            'name' => trim((string) ($data['name'] ?? '')) ?: 'Untitled dataset',
            'description' => $this->n($data['description'] ?? null),
            'data_type' => $this->n($data['data_type'] ?? null),
            'formats' => $this->n($data['formats'] ?? null),
            'est_volume' => $this->n($data['est_volume'] ?? null),
            'sensitivity' => in_array($data['sensitivity'] ?? '', self::SENSITIVITIES, true) ? $data['sensitivity'] : 'open',
            'personal_data' => !empty($data['personal_data']) ? 1 : 0,
            'license' => $this->n($data['license'] ?? null),
            'repository' => $this->n($data['repository'] ?? null),
            'retention_period' => $this->n($data['retention_period'] ?? null),
            'sharing_policy' => $this->n($data['sharing_policy'] ?? null),
        ]);
    }

    public function deleteDataset(int $datasetId, int $dmpId): void
    {
        DB::table('research_dmp_dataset')->where('id', $datasetId)->where('dmp_id', $dmpId)->delete();
    }

    // ---- completeness + export -------------------------------------------

    /** Percentage of narrative sections that are filled (0-100). */
    public function completeness(object $dmp): int
    {
        $total = count(self::SECTIONS);
        $filled = 0;
        foreach (array_keys(self::SECTIONS) as $col) {
            if (trim((string) ($dmp->$col ?? '')) !== '') {
                $filled++;
            }
        }

        return $total ? (int) round($filled * 100 / $total) : 0;
    }

    public function exportMarkdown(int $id): string
    {
        $dmp = $this->get($id);
        if (!$dmp) {
            return '';
        }
        $md = '# ' . $dmp->title . "\n\n";
        $md .= '_' . trim(($dmp->funder ? $dmp->funder . ' · ' : '') . 'v' . $dmp->version . ' · ' . ucfirst((string) $dmp->status)) . "_\n\n";
        foreach (self::SECTIONS as $col => $label) {
            $md .= '## ' . $label . "\n\n" . (trim((string) ($dmp->$col ?? '')) ?: '_(not yet documented)_') . "\n\n";
        }
        $datasets = $this->datasets($id);
        if ($datasets) {
            $md .= "## Datasets\n\n";
            foreach ($datasets as $d) {
                $md .= '### ' . $d->name . "\n\n";
                $md .= '- Type: ' . ($d->data_type ?: '—') . "\n";
                $md .= '- Formats: ' . ($d->formats ?: '—') . "\n";
                $md .= '- Volume: ' . ($d->est_volume ?: '—') . "\n";
                $md .= '- Sensitivity: ' . $d->sensitivity . ($d->personal_data ? ' (personal data)' : '') . "\n";
                $md .= '- License: ' . ($d->license ?: '—') . "\n";
                $md .= '- Repository: ' . ($d->repository ?: '—') . "\n";
                $md .= '- Retention: ' . ($d->retention_period ?: '—') . "\n\n";
            }
        }

        return $md;
    }

    private function n($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;

        return ($v === null || $v === '') ? null : (string) $v;
    }
}
