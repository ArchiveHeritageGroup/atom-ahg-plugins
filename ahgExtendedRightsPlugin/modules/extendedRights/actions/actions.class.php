<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class extendedRightsActions extends AhgActions
{
    public function executeIndex(sfWebRequest $request)
    {


        // RightsStatements.org
        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function ($j) {
                $j->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
                  ->where('rights_statement_i18n.culture', '=', 'en');
            })
            ->where('rights_statement.is_active', '=', 1)
            ->orderBy('rights_statement.category')
            ->orderBy('rights_statement.sort_order')
            ->select([
                'rights_statement.id',
                'rights_statement.code',
                'rights_statement.uri',
                'rights_statement.category',
                'rights_statement.icon_filename',
                'rights_statement.icon_url',
                'rights_statement_i18n.name',
                'rights_statement_i18n.definition as description',
            ])->get();
        

        // Creative Commons - using correct table name
        $this->ccLicenses = Capsule::table('rights_cc_license')
            ->leftJoin('rights_cc_license_i18n', function ($j) {
                $j->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
                  ->where('rights_cc_license_i18n.culture', '=', 'en');
            })
            ->where('rights_cc_license.is_active', '=', 1)
            ->orderBy('rights_cc_license.sort_order')
            ->select([
                'rights_cc_license.id',
                'rights_cc_license.code',
                'rights_cc_license.uri',
                
                'rights_cc_license_i18n.name',
                'rights_cc_license_i18n.description',
            ])->get();

        // Traditional Knowledge Labels - using correct table name
        $this->tkLabels = Capsule::table('rights_tk_label')
            ->leftJoin('rights_tk_label_i18n', function ($j) {
                $j->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                  ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_tk_label.is_active', '=', 1)
            ->orderBy('rights_tk_label.category')
            ->orderBy('rights_tk_label.sort_order')
            ->select([
                'rights_tk_label.id',
                'rights_tk_label.code',
                'rights_tk_label.uri',
                'rights_tk_label.category',
                'rights_tk_label.color',
                'rights_tk_label_i18n.name',
                'rights_tk_label_i18n.description',
            ])->get();

        // Statistics
        $this->stats = (object) [
            'total_objects' => Capsule::table('information_object')->where('id', '>', 1)->count(),
            'with_rights_statement' => Capsule::table('object_rights_statement')->distinct()->count('object_id'),
            'with_cc_license' => Capsule::table('extended_rights')->whereNotNull('creative_commons_license_id')->distinct()->count('object_id'),
            'with_tk_labels' => Capsule::table('rights_object_tk_label')->distinct()->count('object_id'),
            'active_embargoes' => Capsule::table('rights_embargo')->where('status', '=', 'active')->count(),
        ];
    }

    public function executeEdit(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');


        $this->resource = Capsule::table('information_object as io')
            ->join('slug', 'slug.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'io.id')
                  ->where('ioi.culture', '=', sfContext::getInstance()->getUser()->getCulture());
            })
            ->where('slug.slug', $slug)
            ->select('io.*', 'ioi.title', 'slug.slug')
            ->first();

        if (!$this->resource) {
            $this->forward404('Resource not found');
        }

        $oid = (int) $this->resource->id;

        $this->currentRights = (object) [
            'rights_statement' => Capsule::table('object_rights_statement')->where('object_id', '=', $oid)->first(),
            'cc_license' => Capsule::table('extended_rights')->where('object_id', '=', $oid)->whereNotNull('creative_commons_license_id')->first(),
            'tk_labels' => Capsule::table('rights_object_tk_label')->where('object_id', '=', $oid)->pluck('tk_label_id')->toArray(),
            'embargo' => Capsule::table('rights_embargo')->where('object_id', '=', $oid)->where('status', '=', 'active')->first(),
            'rights_holder' => Capsule::table('object_rights_holder')->where('object_id', '=', $oid)->first(),
        ];

        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function ($j) {
                $j->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
                  ->where('rights_statement_i18n.culture', '=', 'en');
            })
            ->where('rights_statement.is_active', '=', 1)
            ->select(['rights_statement.id', 'rights_statement.code', 'rights_statement_i18n.name'])
            ->get();

        $this->ccLicenses = Capsule::table('rights_cc_license')
            ->leftJoin('rights_cc_license_i18n', function ($j) {
                $j->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
                  ->where('rights_cc_license_i18n.culture', '=', 'en');
            })
            ->where('rights_cc_license.is_active', '=', 1)
            ->select(['rights_cc_license.id', 'rights_cc_license.code', 'rights_cc_license_i18n.name'])
            ->get();

        $this->tkLabels = Capsule::table('rights_tk_label')
            ->leftJoin('rights_tk_label_i18n', function ($j) {
                $j->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                  ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_tk_label.is_active', '=', 1)
            ->select(['rights_tk_label.id', 'rights_tk_label.code', 'rights_tk_label_i18n.name'])
            ->get();

        $this->donors = Capsule::table('donor')
            ->join('slug', 'slug.object_id', '=', 'donor.id')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'donor.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->whereNotNull('actor_i18n.authorized_form_of_name')
            ->select(['donor.id', 'slug.slug', 'actor_i18n.authorized_form_of_name as name'])
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get();

        if ($request->isMethod('post')) {
            $rsId = $request->getParameter('rights_statement_id');
            Capsule::table('object_rights_statement')->where('object_id', '=', $oid)->delete();
            if ($rsId) {
                Capsule::table('object_rights_statement')->insert([
                    'object_id' => $oid,
                    'rights_statement_id' => (int) $rsId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $ccId = $request->getParameter('cc_license_id');
            Capsule::table('extended_rights')->updateOrInsert(
                ['object_id' => $oid],
                ['creative_commons_license_id' => $ccId ?: null, 'updated_at' => date('Y-m-d H:i:s')]
            );

            $tkIds = $request->getParameter('tk_label_ids', []);
            Capsule::table('rights_object_tk_label')->where('object_id', '=', $oid)->delete();
            if (is_array($tkIds)) {
                foreach ($tkIds as $tkId) {
                    Capsule::table('rights_object_tk_label')->insert([
                        'object_id' => $oid,
                        'tk_label_id' => (int) $tkId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $donorId = $request->getParameter('rights_holder_id');
            Capsule::table('object_rights_holder')->where('object_id', '=', $oid)->delete();
            if ($donorId) {
                Capsule::table('object_rights_holder')->insert([
                    'object_id' => $oid,
                    'donor_id' => (int) $donorId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->getUser()->setFlash('notice', 'Rights updated.');
            // Use direct slug URL to avoid routing conflicts
            $this->redirect('/' . $this->resource->slug);
        }
    }

    public function executeBatch(sfWebRequest $request)
    {


        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function ($j) {
                $j->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
                  ->where('rights_statement_i18n.culture', '=', 'en');
            })
            ->where('rights_statement.is_active', '=', 1)
            ->select(['rights_statement.id', 'rights_statement.code', 'rights_statement_i18n.name'])
            ->get();

        $this->ccLicenses = Capsule::table('rights_cc_license')
            ->leftJoin('rights_cc_license_i18n', function ($j) {
                $j->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
                  ->where('rights_cc_license_i18n.culture', '=', 'en');
            })
            ->where('rights_cc_license.is_active', '=', 1)
            ->select(['rights_cc_license.id', 'rights_cc_license.code', 'rights_cc_license_i18n.name'])
            ->get();

        $this->tkLabels = Capsule::table('rights_tk_label')
            ->leftJoin('rights_tk_label_i18n', function ($j) {
                $j->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                  ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_tk_label.is_active', '=', 1)
            ->select(['rights_tk_label.id', 'rights_tk_label.code', 'rights_tk_label_i18n.name'])
            ->get();

        $this->objects = Capsule::table('information_object')
            ->join('slug', 'slug.object_id', '=', 'information_object.id')
            ->leftJoin('information_object_i18n', function ($j) {
                $j->on('information_object_i18n.id', '=', 'information_object.id')
                  ->where('information_object_i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n', function ($j) {
                $j->on('term_i18n.id', '=', 'information_object.level_of_description_id')
                  ->where('term_i18n.culture', '=', 'en');
            })
            ->where('information_object.id', '>', 1)
            ->whereNotNull('information_object_i18n.title')
            ->orderBy('information_object_i18n.title')
            ->limit(500)
            ->select([
                'information_object.id',
                'slug.slug',
                'information_object.identifier',
                'information_object_i18n.title',
                'term_i18n.name as level',
            ])->get();

        if ($request->isMethod('post')) {
            $objectIds = $request->getParameter('object_ids', []);
            $rightsType = $request->getParameter('rights_type');
            $valueId = $request->getParameter('value_id');
            if (empty($objectIds) || !$rightsType || !$valueId) {
                $this->getUser()->setFlash('error', 'Please select objects and rights.');

                return;
            }
            $count = 0;
            foreach ($objectIds as $objId) {
                $objId = (int) $objId;
                if ($rightsType === 'rights_statement') {
                    Capsule::table('object_rights_statement')->where('object_id', '=', $objId)->delete();
                    Capsule::table('object_rights_statement')->insert([
                        'object_id' => $objId,
                        'rights_statement_id' => (int) $valueId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    ++$count;
                } elseif ($rightsType === 'cc_license') {
                    Capsule::table('extended_rights')->updateOrInsert(
                        ['object_id' => $objId],
                        ['creative_commons_license_id' => (int) $valueId, 'updated_at' => date('Y-m-d H:i:s')]
                    );
                    ++$count;
                } elseif ($rightsType === 'tk_label') {
                    if (!Capsule::table('rights_object_tk_label')->where('object_id', '=', $objId)->where('tk_label_id', '=', (int) $valueId)->exists()) {
                        Capsule::table('rights_object_tk_label')->insert([
                            'object_id' => $objId,
                            'tk_label_id' => (int) $valueId,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        ++$count;
                    }
                }
            }
            $this->getUser()->setFlash('notice', "Rights assigned to {$count} objects.");
            $this->redirect(['module' => 'extendedRights', 'action' => 'index']);
        }
    }

    public function executeEmbargoes(sfWebRequest $request)
    {

        $this->embargoes = Capsule::table('rights_embargo')
            ->join('slug', 'slug.object_id', '=', 'rights_embargo.object_id')
            ->leftJoin('information_object_i18n', function ($j) {
                $j->on('information_object_i18n.id', '=', 'rights_embargo.object_id')
                  ->where('information_object_i18n.culture', '=', 'en');
            })
            ->where('rights_embargo.status', '=', 'active')
            ->orderBy('rights_embargo.end_date')
            ->select([
                'rights_embargo.id',
                'rights_embargo.object_id',
                'rights_embargo.embargo_type',
                'rights_embargo.start_date',
                'rights_embargo.end_date',
                'information_object_i18n.title',
                'slug.slug',
            ])->get();
    }

    public function executeLiftEmbargo(sfWebRequest $request)
    {

        Capsule::table('rights_embargo')
            ->where('id', '=', (int) $request->getParameter('id'))
            ->update(['status' => 'lifted', 'lifted_at' => date('Y-m-d H:i:s')]);
        $this->getUser()->setFlash('notice', 'Embargo lifted.');
        $this->redirect(['module' => 'extendedRights', 'action' => 'embargoes']);
    }

    public function executeBrowse(sfWebRequest $request)
    {



        // RightsStatements.org
        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function ($j) {
                $j->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
                  ->where('rights_statement_i18n.culture', '=', 'en');
            })
            ->where('rights_statement.is_active', '=', 1)
            ->orderBy('rights_statement.category')
            ->orderBy('rights_statement.sort_order')
            ->select([
                'rights_statement.id',
                'rights_statement.code',
                'rights_statement.uri',
                'rights_statement.category',
                'rights_statement.icon_filename',
                'rights_statement_i18n.name',
                'rights_statement_i18n.definition as description',
            ])->get();
        

        // Creative Commons
        $this->ccLicenses = Capsule::table('rights_cc_license')
            ->leftJoin('rights_cc_license_i18n', function ($j) {
                $j->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
                  ->where('rights_cc_license_i18n.culture', '=', 'en');
            })
            ->where('rights_cc_license.is_active', '=', 1)
            ->orderBy('rights_cc_license.sort_order')
            ->select([
                'rights_cc_license.id',
                'rights_cc_license.code',
                'rights_cc_license.uri',
                
                'rights_cc_license_i18n.name',
                'rights_cc_license_i18n.description',
            ])->get();

        // Traditional Knowledge Labels
        $this->tkLabels = Capsule::table('rights_tk_label')
            ->leftJoin('rights_tk_label_i18n', function ($j) {
                $j->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                  ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_tk_label.is_active', '=', 1)
            ->orderBy('rights_tk_label.category')
            ->orderBy('rights_tk_label.sort_order')
            ->select([
                'rights_tk_label.id',
                'rights_tk_label.code',
                'rights_tk_label.uri',
                'rights_tk_label.category',
                'rights_tk_label.color',
                'rights_tk_label_i18n.name',
                'rights_tk_label_i18n.description',
            ])->get();

        // Statistics
        $this->stats = (object) [
            'total_objects' => Capsule::table('information_object')->where('id', '>', 1)->count(),
            'with_rights_statement' => Capsule::table('object_rights_statement')->distinct()->count('object_id'),
            'with_cc_license' => Capsule::table('extended_rights')->whereNotNull('creative_commons_license_id')->distinct()->count('object_id'),
            'with_tk_labels' => Capsule::table('rights_object_tk_label')->distinct()->count('object_id'),
            'active_embargoes' => Capsule::table('rights_embargo')->where('status', '=', 'active')->count(),
        ];
        
    }

    public function executeExpiringEmbargoes(sfWebRequest $request)
    {

        $days = (int) $request->getParameter('days', 30);
        $this->days = $days;

        $this->embargoes = Capsule::table('rights_embargo')
            ->join('slug', 'slug.object_id', '=', 'rights_embargo.object_id')
            ->leftJoin('information_object_i18n', function ($j) {
                $j->on('information_object_i18n.id', '=', 'rights_embargo.object_id')
                  ->where('information_object_i18n.culture', '=', 'en');
            })
            ->where('rights_embargo.status', '=', 'active')
            ->whereNotNull('rights_embargo.end_date')
            ->where('rights_embargo.end_date', '<=', date('Y-m-d', strtotime("+{$days} days")))
            ->orderBy('rights_embargo.end_date')
            ->select([
                'rights_embargo.id',
                'rights_embargo.object_id',
                'rights_embargo.embargo_type',
                'rights_embargo.start_date',
                'rights_embargo.end_date',
                'rights_embargo.reason',
                'information_object_i18n.title',
                'slug.slug',
            ])->get();
    }

    /**
     * Clear all extended rights from an information object
     */
    public function executeClear(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');


        // Get the object ID from slug
        $object = Capsule::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$object) {
            $this->forward404('Resource not found');
        }

        $oid = (int) $object->object_id;

        // Clear all extended rights
        Capsule::table('object_rights_statement')->where('object_id', '=', $oid)->delete();
        Capsule::table('extended_rights')->where('object_id', '=', $oid)->delete();
        Capsule::table('rights_object_tk_label')->where('object_id', '=', $oid)->delete();
        Capsule::table('object_rights_holder')->where('object_id', '=', $oid)->delete();
        // Lift any active embargoes
        Capsule::table('rights_embargo')
            ->where('object_id', '=', $oid)
            ->where('status', '=', 'active')
            ->update(['status' => 'lifted', 'lifted_at' => date('Y-m-d H:i:s')]);

        $this->getUser()->setFlash('notice', 'All extended rights have been cleared.');
        $this->redirect('/' . $slug);
    }
}
