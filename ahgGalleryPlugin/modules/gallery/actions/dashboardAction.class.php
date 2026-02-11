<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class galleryDashboardAction extends AhgController
{
    public function execute($request)
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        // Get gallery items count
        $this->totalItems = DB::table('information_object')
            ->where('display_standard_id', \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery'))
            ->count();

        // Get recent gallery items
        $this->recentItems = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('io.display_standard_id', \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery'))
            ->orderBy('io.id', 'desc')
            ->limit(10)
            ->select('io.id', 'io.identifier', 'ioi.title', 's.slug', 'do.id as digital_object_id')
            ->get()
            ->all();

        // Get work type statistics
        $this->workTypeStats = DB::table('property as p')
            ->join('property_i18n as pi', function($j) {
                $j->on('p.id', '=', 'pi.id')->where('pi.culture', '=', 'en');
            })
            ->join('information_object as io', 'p.object_id', '=', 'io.id')
            ->where('p.name', 'galleryData')
            ->where('io.display_standard_id', \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery'))
            ->select('pi.value')
            ->get()
            ->all();

        // Count items with digital objects
        $this->itemsWithMedia = DB::table('information_object as io')
            ->join('digital_object as do', 'io.id', '=', 'do.object_id')
            ->where('io.display_standard_id', \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery'))
            ->count();
    }
}
