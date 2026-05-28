<?php

declare(strict_types=1);

/**
 * browseAction — internal library catalog with FRBR work-set clustering.
 *
 * Default view: FRBR ON. Items are grouped by work key so one card shows all
 * manifestations (editions) under the same work. Inline toggle to switch to flat.
 *
 * @package    ahgLibraryPlugin
 * @subpackage library
 */

use AtomFramework\Http\Controllers\AhgController;

class libraryBrowseAction extends AhgController
{
    public function execute($request)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/FrbrService.php';

        $db     = \Illuminate\Database\Capsule\Manager::connection();
        $culture = $this->getContext()->user->getCulture() ?? 'en';

        // ======================================================================
        // FRBR Clustering Toggle  (default ON to match OPAC)
        // ======================================================================
        $frbrOn = (bool) $request->getParameter('frbr_cluster', 1);
        $this->frbrOn  = $frbrOn;
        $this->frbrUrl = rawurlencode($frbrOn ? '0' : '1');

        // ======================================================================
        // Base query (returns all library items — no LIMIT for clustering decision)
        // ======================================================================
        $base = $db->table('information_object as io')
            ->join('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                  ->where('ioi.culture', '=', $culture);
            })
            ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('library_item_creator as lic', function ($j) {
                $j->on('li.id', '=', 'lic.library_item_id')
                  ->where('lic.is_primary', '=', 1);
            })
            ->where('io.source_standard', 'library')
            ->select([
                'li.id as id',
                'li.id as library_item_id',
                'io.id as io_id',
                'ioi.title',
                's.slug',
                'li.isbn',
                'li.issn',
                'li.lccn',
                'li.frbr_work_key',
                'li.frbr_override_type',
                'li.publisher',
                'li.publication_date',
                'li.material_type',
                'li.call_number',
                'li.language',
                'li.description',
                'lic.name as primary_creator',
            ]);

        // ======================================================================
        // FRBR Clustered view
        // ======================================================================
        if ($frbrOn) {
            // Count distinct works (not individual items)
            // Only count works that aren't force_split
            $totalWorks = (clone $base)
                ->whereNotNull('li.frbr_work_key')
                ->where('li.frbr_override_type', '!=', 'force_split')
                ->distinct('li.frbr_work_key')
                ->count('li.frbr_work_key');

            $this->total     = 0;   // not used in FRBR mode
            $this->totalItems = (clone $base)->count();

            // Load all items for clustering (up to 5 000 for reasonably-sized collections)
            $allItems = $base
                ->limit(5000)
                ->orderBy('ioi.title')
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            // Cluster using FrbrService
            $svc = FrbrService::getInstance();
            $clusters = $svc->clusterSearchResults($allItems);

            $this->totalWorks  = $totalWorks;
            $this->total       = count($allItems);
            $this->clusters    = $clusters;
            $this->items       = [];  // flat list unused in FRBR mode
            $this->totalPages  = 1;
            $this->page        = 1;

            // Lazy-load manifestation creators
            $allItemIds = array_map(fn($c) => array_map(fn($m) => (int) ($m['id'] ?? $m->id ?? 0), $c['manifestations']), $clusters);
            $flat = array_merge(...$allItemIds);

            if (!empty($flat)) {
                $creatorRows = \Illuminate\Database\Capsule\Manager::table('library_item_creator')
                    ->whereIn('library_item_id', $flat)
                    ->orderBy('sort_order')
                    ->get()
                    ->all();
                $creatorsByItem = [];
                foreach ($creatorRows as $cr) {
                    $creatorsByItem[(int) $cr->library_item_id][] = $cr->name;
                }
                // Inject creators into each manifestation
                foreach ($this->clusters as &$cluster) {
                    foreach ($cluster['manifestations'] as &$m) {
                        $id = (int) ($m['id'] ?? $m->id ?? 0);
                        $m['creators'] = $creatorsByItem[$id] ?? [];
                    }
                }
            }

        } else {
            // ==================================================================
            // Flat view (original behaviour — paginated)
            // ==================================================================
            $this->limit  = 20;
            $this->page   = max(1, (int) $request->getParameter('page', 1));
            $offset       = ($this->page - 1) * $this->limit;

            $this->total = (clone $base)->count();

            $rows = $db->table('information_object as io')
                ->join('information_object_i18n as ioi', function ($j) use ($culture) {
                    $j->on('io.id', '=', 'ioi.id')
                      ->where('ioi.culture', '=', $culture);
                })
                ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->leftJoin('library_item_creator as lic', function ($j) {
                    $j->on('li.id', '=', 'lic.library_item_id')
                      ->where('lic.is_primary', '=', 1);
                })
                ->where('io.source_standard', 'library')
                ->select([
                    'li.id as id',
                'li.id as library_item_id',
                    'io.id as io_id',
                    'ioi.title',
                    's.slug',
                    'li.isbn',
                    'li.issn',
                    'li.lccn',
                    'li.frbr_work_key',
                    'li.frbr_override_type',
                    'li.publisher',
                    'li.publication_date',
                    'li.material_type',
                    'li.call_number',
                    'li.language',
                    'li.description',
                    'lic.name as primary_creator',
                ])
                ->orderBy('ioi.title')
                ->offset($offset)
                ->limit($this->limit)
                ->get()
                ->all();

            // Attach creators
            $itemIds = array_map(fn($r) => (int) ($r->library_item_id ?? 0), $rows);
            $allCreators = [];
            if (!empty($itemIds)) {
                $crRows = \Illuminate\Database\Capsule\Manager::table('library_item_creator')
                    ->whereIn('library_item_id', $itemIds)
                    ->orderBy('sort_order')
                    ->get()
                    ->all();
                foreach ($crRows as $cr) {
                    $allCreators[(int) $cr->library_item_id][] = $cr->name;
                }
            }

            $this->items = array_map(function ($r) use ($allCreators) {
                $id = (int) ($r->library_item_id ?? 0);
                $arr = (array) $r;
                $arr['creators'] = $allCreators[$id] ?? [];
                return $arr;
            }, $rows);

            $this->clusters   = [];
            $this->totalWorks = 0;
            $this->totalPages = $this->total > 0 ? (int) ceil($this->total / $this->limit) : 1;
        }
    }
}
