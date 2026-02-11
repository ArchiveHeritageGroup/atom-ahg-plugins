<?php

use AtomFramework\Http\Controllers\AhgController;
class libraryBrowseAction extends AhgController
{
    public function execute($request)
    {
        // Load framework
        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/bootstrap.php';

        $db = \Illuminate\Database\Capsule\Manager::connection();
        $culture = $this->getContext()->user->getCulture() ?? 'en';

        // Pagination
        $this->limit = 20;
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $offset = ($this->page - 1) * $this->limit;

        // Get total count
        $this->total = $db->table('information_object as io')
            ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
            ->where('io.source_standard', 'library')
            ->count();

        // Get items with library data
        $items = $db->table('information_object as io')
            ->join('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $culture);
            })
            ->join('library_item as li', 'io.id', '=', 'li.information_object_id')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
            ->leftJoin('term_i18n as ti', function($join) use ($culture) {
                $join->on('io.level_of_description_id', '=', 'ti.id')
                     ->where('ti.culture', '=', $culture);
            })
            ->select([
                'io.id',
                'ioi.title',
                's.slug',
                'li.isbn',
                'do.path as cover_path',
                'do.name as cover_name',
                'li.publisher',
                'li.publication_date',
                'li.material_type',
                'li.call_number',
                'ti.name as level_name'
            ])
            ->orderBy('ioi.title')
            ->offset($offset)
            ->limit($this->limit)
            ->get();

        // Get creators for each item
        $this->items = [];
        foreach ($items as $item) {
            $itemArray = (array) $item;
            
            // Get creators
            $libraryItemId = $db->table('library_item')
                ->where('information_object_id', $item->id)
                ->value('id');
            
            if ($libraryItemId) {
                $creators = $db->table('library_item_creator')
                    ->where('library_item_id', $libraryItemId)
                    ->orderBy('sort_order')
                    ->pluck('name')
                    ->toArray();
                $itemArray['creators'] = $creators;
            } else {
                $itemArray['creators'] = [];
            }
            
            $this->items[] = $itemArray;
        }

        // Calculate pagination
        $this->totalPages = ceil($this->total / $this->limit);
    }
}
