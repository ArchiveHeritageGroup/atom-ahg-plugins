<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Museum Dashboard Action
 *
 * Displays museum collection statistics and quick actions.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class museumDashboardAction extends AhgController
{
    public function execute($request)
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $museumTermId = \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('museum');

        // Get museum items count
        $this->totalItems = DB::table('information_object')
            ->where('display_standard_id', $museumTermId)
            ->count();

        // Get recent museum items
        $this->recentItems = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('io.display_standard_id', $museumTermId)
            ->orderBy('io.id', 'desc')
            ->limit(10)
            ->select('io.id', 'io.identifier', 'ioi.title', 's.slug', 'do.id as digital_object_id')
            ->get()
            ->all();

        // Count items with digital objects
        $this->itemsWithMedia = DB::table('information_object as io')
            ->join('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('io.display_standard_id', $museumTermId)
            ->count();

        // Get condition statistics (handle missing table gracefully)
        $this->itemsWithCondition = 0;
        try {
            if (DB::schema()->hasTable('spectrum_procedure')) {
                $conditionStats = DB::table('spectrum_procedure as sp')
                    ->join('information_object as io', 'sp.information_object_id', '=', 'io.id')
                    ->where('io.display_standard_id', $museumTermId)
                    ->where('sp.procedure_type', 'condition_check')
                    ->count();
                $this->itemsWithCondition = $conditionStats;
            }
        } catch (\Exception $e) {
            // Table doesn't exist, keep default 0
        }

        // Get work type statistics (handle missing table gracefully)
        $this->workTypeStats = [];
        try {
            if (DB::schema()->hasTable('museum_object')) {
                $this->workTypeStats = DB::table('museum_object as mo')
                    ->join('information_object as io', 'mo.information_object_id', '=', 'io.id')
                    ->leftJoin('term_i18n as ti', function ($j) {
                        $j->on('mo.work_type_id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                    })
                    ->where('io.display_standard_id', $museumTermId)
                    ->whereNotNull('mo.work_type_id')
                    ->groupBy('mo.work_type_id', 'ti.name')
                    ->select('ti.name as work_type', DB::raw('COUNT(*) as count'))
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get()
                    ->all();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, keep default empty
        }

        // Get provenance count (handle missing table gracefully)
        $this->itemsWithProvenance = 0;
        try {
            if (DB::schema()->hasTable('cco_provenance')) {
                $this->itemsWithProvenance = DB::table('cco_provenance as cp')
                    ->join('information_object as io', 'cp.information_object_id', '=', 'io.id')
                    ->where('io.display_standard_id', $museumTermId)
                    ->distinct('cp.information_object_id')
                    ->count('cp.information_object_id');
            }
        } catch (\Exception $e) {
            // Table doesn't exist, keep default 0
        }
    }
}
