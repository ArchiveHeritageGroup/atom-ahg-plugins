<?php
/**
 * PSIS / AtoM-AHG - authenticity-coverage report for the admin dashboard.
 *
 * Port of Heratio's CoverageReportService, folded together with the parts of
 * its AuthenticityStatsService snapshot the dashboard reads: how many master
 * digital objects carry signed content credentials, the signed / unsigned
 * split, and a per-repository gap table. Read-only; every query is guarded so
 * a missing table yields zeros, never an error.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class CoverageReportService
{
    private const TOP_N = 25;

    public function __construct(private ProvenanceRecordService $provenance)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function report(): array
    {
        $cap = $this->provenance->capability();
        $report = [
            'enabled'            => ProvenanceRecordService::tableExists('ahg_c2pa_provenance'),
            'can_sign'           => $cap['can_sign_manifest'],
            'capability_summary' => $cap['summary'],
            'total_masters'      => 0,
            'covered_masters'    => 0,
            'uncovered_masters'  => 0,
            'coverage_pct'       => 0.0,
            'records_total'      => 0,
            'signed_records'     => 0,
            'unsigned_records'   => 0,
            'manifests_total'    => 0,
            'ai_manifests'       => 0,
            'last_signed_at'     => null,
            'issuers'            => [],
            'breakdown'          => [],
            'breakdown_truncated' => false,
        ];

        try {
            $report['total_masters'] = (int) $this->masters()->count();

            if ($report['enabled']) {
                $report['records_total'] = (int) DB::table('ahg_c2pa_provenance')->count();
                $report['signed_records'] = (int) DB::table('ahg_c2pa_provenance')->whereNotNull('manifest_id')->count();
                $report['unsigned_records'] = $report['records_total'] - $report['signed_records'];
                $report['covered_masters'] = (int) $this->masters()
                    ->join('ahg_c2pa_provenance as p', 'p.digital_object_id', '=', 'd.id')
                    ->whereNotNull('p.manifest_id')
                    ->distinct()
                    ->count('d.id');
                $report['last_signed_at'] = DB::table('ahg_c2pa_provenance')->whereNotNull('manifest_id')->max('updated_at');
            }

            if (ProvenanceRecordService::tableExists('ahg_c2pa_manifest')) {
                $report['manifests_total'] = (int) DB::table('ahg_c2pa_manifest')->count();
                if (in_array('action', C2paService::manifestColumns(), true)) {
                    $report['ai_manifests'] = (int) DB::table('ahg_c2pa_manifest')->where('action', 'like', 'ai-%')->count();
                }
                $report['issuers'] = DB::table('ahg_c2pa_manifest')
                    ->select('kid', DB::raw('COUNT(*) as c'))
                    ->groupBy('kid')
                    ->orderByDesc('c')
                    ->limit(10)
                    ->get()
                    ->map(fn ($r) => ['kid' => (string) $r->kid, 'count' => (int) $r->c])
                    ->all();
            }
        } catch (Throwable $e) {
            error_log('[c2pa] coverage report query failed: ' . $e->getMessage());
        }

        $report['covered_masters'] = min($report['covered_masters'], $report['total_masters']);
        $report['uncovered_masters'] = max(0, $report['total_masters'] - $report['covered_masters']);
        if ($report['total_masters'] > 0) {
            $report['coverage_pct'] = round($report['covered_masters'] / $report['total_masters'] * 100, 1);
        }

        $truncated = false;
        $report['breakdown'] = $this->repositoryBreakdown($report['enabled'], $truncated);
        $report['breakdown_truncated'] = $truncated;

        return $report;
    }

    /**
     * Master digital objects attached to descriptions (parentless rows).
     */
    private function masters(): \Illuminate\Database\Query\Builder
    {
        return DB::table('digital_object as d')
            ->join('information_object as io', 'io.id', '=', 'd.object_id')
            ->whereNull('d.parent_id');
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function repositoryBreakdown(bool $enabled, bool &$truncated): array
    {
        try {
            $masterRows = $this->masters()
                ->select('io.repository_id as repo', DB::raw('COUNT(*) as masters'))
                ->groupBy('io.repository_id')
                ->get();
            if ($masterRows->isEmpty()) {
                return [];
            }

            $coveredByRepo = [];
            if ($enabled) {
                $coveredRows = $this->masters()
                    ->join('ahg_c2pa_provenance as p', 'p.digital_object_id', '=', 'd.id')
                    ->whereNotNull('p.manifest_id')
                    ->select('io.repository_id as repo', DB::raw('COUNT(DISTINCT d.id) as covered'))
                    ->groupBy('io.repository_id')
                    ->get();
                foreach ($coveredRows as $row) {
                    $coveredByRepo[(string) ($row->repo ?? '')] = (int) $row->covered;
                }
            }

            $ids = $masterRows->pluck('repo')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->all();
            $names = [];
            if ($ids !== []) {
                foreach (DB::table('actor_i18n')->whereIn('id', $ids)->orderByRaw("culture = 'en' DESC")->get(['id', 'authorized_form_of_name']) as $n) {
                    $name = trim((string) ($n->authorized_form_of_name ?? ''));
                    if ($name !== '' && !isset($names[(int) $n->id])) {
                        $names[(int) $n->id] = $name;
                    }
                }
            }

            $rows = [];
            foreach ($masterRows as $mr) {
                $repoId = $mr->repo === null ? null : (int) $mr->repo;
                $masters = (int) $mr->masters;
                $covered = min((int) ($coveredByRepo[(string) ($mr->repo ?? '')] ?? 0), $masters);
                $rows[] = [
                    'id'           => $repoId,
                    'label'        => $repoId === null ? 'Unassigned (no holding repository)' : ($names[$repoId] ?? ('Repository #' . $repoId)),
                    'masters'      => $masters,
                    'covered'      => $covered,
                    'coverage_pct' => $masters > 0 ? round($covered / $masters * 100, 1) : 0.0,
                ];
            }

            usort($rows, static fn (array $a, array $b): int => $b['masters'] <=> $a['masters'] ?: $a['coverage_pct'] <=> $b['coverage_pct']);

            if (count($rows) > self::TOP_N) {
                $truncated = true;
                $rows = array_slice($rows, 0, self::TOP_N);
            }

            return $rows;
        } catch (Throwable $e) {
            error_log('[c2pa] coverage breakdown failed: ' . $e->getMessage());

            return [];
        }
    }
}
