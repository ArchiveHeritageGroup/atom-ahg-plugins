<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Autocomplete endpoint for information objects (records).
 * Returns JSON array of {id, title, lod, identifier, slug} matching the query.
 */
class ricExplorerAutocompleteAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('q', ''));
        if (mb_strlen($q) < 2) {
            return $this->renderText(json_encode([]));
        }

        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        $results = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('term_i18n as lod', function ($j) use ($culture) {
                $j->on('io.level_of_description_id', '=', 'lod.id')->where('lod.culture', '=', $culture);
            })
            ->where('io.id', '>', 1)
            ->where(function ($query) use ($q) {
                $query->where('ioi.title', 'LIKE', '%' . $q . '%')
                      ->orWhere('io.identifier', 'LIKE', '%' . $q . '%')
                      ->orWhere('s.slug', 'LIKE', '%' . $q . '%');
            })
            ->select('io.id', 'ioi.title', 'io.identifier', 'lod.name as level_of_description', 's.slug')
            ->orderByRaw('CASE WHEN ioi.title LIKE ? THEN 0 ELSE 1 END', [$q . '%'])
            ->limit(15)
            ->get();

        $items = [];
        foreach ($results as $row) {
            $items[] = [
                'id' => $row->id,
                'title' => $row->title ?: 'Untitled',
                'identifier' => $row->identifier,
                'lod' => $row->level_of_description,
                'slug' => $row->slug,
            ];
        }

        return $this->renderText(json_encode($items));
    }
}
