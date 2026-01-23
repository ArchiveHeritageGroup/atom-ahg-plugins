<?php
use Illuminate\Database\Capsule\Manager as DB;

// Load AhgAccessGate for embargo checks
require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Access/AhgAccessGate.php';

class ahgGalleryPluginIndexAction extends sfAction
{
    public function execute($request)
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $slug = $request->getParameter('slug');
        
        if (!$slug) {
            $this->forward404();
        }

        // Load resource using Laravel
        $this->resource = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('term as t', 'io.level_of_description_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->where('s.slug', $slug)
            ->select(
                'io.id',
                'io.identifier',
                'io.parent_id',
                'io.repository_id',
                'io.level_of_description_id',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ti.name as level_name',
                's.slug'
            )
            ->first();

        if (!$this->resource) {
            $this->forward404();
        }

        if ($this->resource->id == 1) {
            $this->forward404();
        }

        // Load gallery data from property
        $prop = DB::table('property as p')
            ->join('property_i18n as pi', function($j) {
                $j->on('p.id', '=', 'pi.id')->where('pi.culture', '=', 'en');
            })
            ->where('p.object_id', $this->resource->id)
            ->where('p.name', 'galleryData')
            ->select('pi.value')
            ->first();

        $this->galleryData = $prop ? (json_decode($prop->value, true) ?: []) : [];

        // Look up work_type term name if it is an ID
        if (isset($this->galleryData['work_type']) && is_numeric($this->galleryData['work_type'])) {
            $workTypeTerm = DB::table('term_i18n')
                ->where('id', $this->galleryData['work_type'])
                ->where('culture', 'en')
                ->value('name');
            if ($workTypeTerm) {
                $this->galleryData['work_type_name'] = $workTypeTerm;
            }
        }
        $this->creatorName = null;
        $creator = DB::table('event as e')
            ->join('actor_i18n as ai', function($j) {
                $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('e.object_id', $this->resource->id)
            ->where('e.type_id', 111)
            ->select('ai.authorized_form_of_name')
            ->first();
        if ($creator) {
            $this->creatorName = $creator->authorized_form_of_name;
        }

        // Check digital object
        $this->digitalObject = DB::table('digital_object')
            ->where('object_id', $this->resource->id)
            ->first();

        // Get derivatives
        $this->derivatives = [];
        if ($this->digitalObject) {
            $this->derivatives = DB::table('digital_object as d')
                ->leftJoin('term_i18n as ti', function($j) {
                    $j->on('d.usage_id', '=', 'ti.id')->where('ti.culture', '=', 'en');
                })
                ->where('d.parent_id', $this->digitalObject->id)
                ->select('d.*', 'ti.name as usage_name')
                ->get()
                ->all();
        }

        // Check permissions
        $this->canEdit = $this->getUser()->isAuthenticated();
        
        // Resource already loaded via Laravel - use for ACL components
        $this->qubitResource = QubitInformationObject::getById($this->resource->id);
        
        // Check embargo access
        if (!\AhgCore\Access\AhgAccessGate::canView($this->resource->id, $this)) {
            return sfView::NONE;
        }

        // Load item physical location
        $locRepoPath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        if (file_exists($locRepoPath)) {
            require_once $locRepoPath;
        }
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
    }
}
