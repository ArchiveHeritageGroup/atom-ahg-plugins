<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class galleryBrowseAction extends AhgController
{
    public function execute($request)
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get thumbnail usage ID
        $thumbnailUsageId = DB::table('term_i18n')
            ->where('name', 'Thumbnail')
            ->value('id') ?? 142;

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('digital_object as do_master', function ($j) {
                $j->on('io.id', '=', 'do_master.object_id')
                  ->whereNull('do_master.parent_id');
            })
            ->leftJoin('digital_object as do_thumb', function ($j) use ($thumbnailUsageId) {
                $j->on('do_thumb.parent_id', '=', 'do_master.id')
                  ->where('do_thumb.usage_id', '=', $thumbnailUsageId);
            })
            ->where('io.display_standard_id', \AtomFramework\Helpers\DisplayStandardHelper::getTermIdByCode('gallery'));

        $this->total = $query->count();
        $this->totalPages = ceil($this->total / $limit);
        $this->currentPage = $page;

        $this->items = $query->orderBy('ioi.title')
            ->offset($offset)
            ->limit($limit)
            ->select(
                'io.id',
                'io.identifier',
                'ioi.title',
                's.slug',
                'do_master.id as digital_object_id',
                'do_master.path as master_path',
                'do_master.name as master_name',
                'do_master.mime_type',
                'do_thumb.path as thumb_path',
                'do_thumb.name as thumb_name'
            )
            ->get()
            ->all();
    }
}
