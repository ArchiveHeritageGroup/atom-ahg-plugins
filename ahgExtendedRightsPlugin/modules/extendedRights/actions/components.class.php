<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class extendedRightsComponents extends AhgComponents
{
    public function executeRightsDisplay(sfWebRequest $request)
    {
        $objectId = $this->objectId ?? null;
        if (!$objectId) {
            return sfView::NONE;
        }


        $objectId = (int) $objectId;

        // Get Rights Statement
        $this->rightsStatement = Capsule::table('object_rights_statement')
            ->join('rights_statement', 'rights_statement.id', '=', 'object_rights_statement.rights_statement_id')
            ->leftJoin('rights_statement_i18n', function ($join) {
                $join->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
                     ->where('rights_statement_i18n.culture', '=', 'en');
            })
            ->where('object_rights_statement.object_id', '=', $objectId)
            ->select(
                'rights_statement.id',
                'rights_statement.code',
                'rights_statement.uri',
                'rights_statement.icon_filename',
                'rights_statement_i18n.name',
                'rights_statement_i18n.definition'
            )
            ->first();

        // Get Creative Commons
        $this->creativeCommons = Capsule::table('extended_rights')
            ->join('rights_cc_license', 'rights_cc_license.id', '=', 'extended_rights.creative_commons_license_id')
            ->leftJoin('rights_cc_license_i18n', function ($join) {
                $join->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
                     ->where('rights_cc_license_i18n.culture', '=', 'en');
            })
            ->where('extended_rights.object_id', '=', $objectId)
            ->select(
                'rights_cc_license.id',
                'rights_cc_license.code',
                'rights_cc_license.uri',
                'rights_cc_license_i18n.name'
            )
            ->first();

        // Get TK Labels - simplified without category join (category is a column now)
        $this->tkLabels = Capsule::table('rights_object_tk_label')
            ->join('rights_tk_label', 'rights_tk_label.id', '=', 'rights_object_tk_label.tk_label_id')
            ->leftJoin('rights_tk_label_i18n', function ($join) {
                $join->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                     ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_object_tk_label.object_id', '=', $objectId)
            ->select(
                'rights_tk_label.id',
                'rights_tk_label.code',
                'rights_tk_label.uri',
                'rights_tk_label.category',
                'rights_tk_label.color',
                'rights_tk_label_i18n.name'
            )
            ->get();

        // Get Embargo
        $this->embargo = Capsule::table('rights_embargo')
            ->where('object_id', '=', $objectId)
            ->where('status', '=', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>', date('Y-m-d'));
            })
            ->first();

        // Combine into single rights object for template
        $this->rights = null;
        if ($this->rightsStatement || $this->creativeCommons || count($this->tkLabels) > 0) {
            $this->rights = (object) [
                'rs_code' => $this->rightsStatement->code ?? null,
                'rs_uri' => $this->rightsStatement->uri ?? null,
                'rs_name' => $this->rightsStatement->name ?? null,
                'rs_icon_filename' => $this->rightsStatement->icon_filename ?? null,
                'rs_definition' => $this->rightsStatement->definition ?? null,
                'cc_code' => $this->creativeCommons->code ?? null,
                'cc_uri' => $this->creativeCommons->uri ?? null,
                'cc_name' => $this->creativeCommons->name ?? null,
                'tk_labels' => $this->tkLabels,
            ];
        }
    }

    public function executeProvenanceDisplay(sfWebRequest $request)
    {
        $objectId = $this->objectId ?? null;
        if (!$objectId) {
            return sfView::NONE;
        }


        $this->provenance = Capsule::table('object_provenance')->where('object_id', (int) $objectId)->first();
        $this->donor = null;

        if ($this->provenance && isset($this->provenance->donor_id)) {
            $this->donor = Capsule::table('donor')
                ->leftJoin('donor_i18n', function ($join) {
                    $join->on('donor_i18n.id', '=', 'donor.id')
                         ->where('donor_i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'slug.object_id', '=', 'donor.id')
                ->where('donor.id', '=', $this->provenance->donor_id)
                ->select('donor.id', 'donor_i18n.authorized_form_of_name', 'slug.slug')
                ->first();
        }
    }

    public function executeGetTkLabels(sfWebRequest $request)
    {


        $this->tkLabels = Capsule::table('rights_tk_label')
            ->leftJoin('rights_tk_label_i18n', function ($join) {
                $join->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
                     ->where('rights_tk_label_i18n.culture', '=', 'en');
            })
            ->where('rights_tk_label.is_active', '=', 1)
            ->orderBy('rights_tk_label.category')
            ->orderBy('rights_tk_label.sort_order')
            ->select(
                'rights_tk_label.id',
                'rights_tk_label.code',
                'rights_tk_label.uri',
                'rights_tk_label.category',
                'rights_tk_label.color',
                'rights_tk_label_i18n.name'
            )
            ->get();
    }

    public function executeRightsStats(sfWebRequest $request)
    {


        $this->stats = (object) [
            'total_objects' => Capsule::table('information_object')->where('id', '>', 1)->count(),
            'with_rights_statement' => Capsule::table('object_rights_statement')->distinct()->count('object_id'),
            'with_creative_commons' => Capsule::table('extended_rights')->whereNotNull('creative_commons_license_id')->distinct()->count('object_id'),
            'with_tk_labels' => Capsule::table('rights_object_tk_label')->distinct()->count('object_id'),
            'active_embargoes' => Capsule::table('rights_embargo')->where('status', 'active')->count(),
        ];
    }

    public function executeEmbargoStatus(sfWebRequest $request)
    {
        $objectId = $this->objectId ?? null;
        if (!$objectId) {
            $this->embargo = null;

            return sfView::NONE;
        }


        $objectId = (int) $objectId;

        $this->embargo = Capsule::table('rights_embargo')
            ->where('object_id', '=', $objectId)
            ->where('status', '=', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>', date('Y-m-d'));
            })
            ->first();
    }
}
