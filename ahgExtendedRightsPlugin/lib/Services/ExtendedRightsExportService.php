<?php

namespace ahgExtendedRightsPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class ExtendedRightsExportService
{
    protected $culture;

    public function __construct($culture = 'en')
    {
        $this->culture = $culture;
    }

    public function getRightsStatistics()
    {
        $stats = [];

        try {
            $stats['total_with_rights'] = DB::table('object_rights_statement')->distinct()->count('object_id')
                + DB::table('extended_rights')->distinct()->count('object_id')
                + DB::table('rights_object_tk_label')->distinct()->count('object_id');
        } catch (\Exception $e) {
            $stats['total_with_rights'] = 0;
        }

        try {
            $stats['active_embargoes'] = DB::table('rights_embargo')->where('status', '=', 'active')->count();
        } catch (\Exception $e) {
            $stats['active_embargoes'] = 0;
        }

        try {
            $stats['embargoes_expiring_soon'] = DB::table('rights_embargo')
                ->where('status', '=', 'active')
                ->whereNotNull('end_date')
                ->where('end_date', '<=', date('Y-m-d', strtotime('+30 days')))
                ->count();
        } catch (\Exception $e) {
            $stats['embargoes_expiring_soon'] = 0;
        }

        try {
            $stats['inherited_rights'] = 0; // Placeholder
        } catch (\Exception $e) {
            $stats['inherited_rights'] = 0;
        }

        $stats['by_rights_statement'] = $this->getRightsStatementCounts();
        $stats['by_cc_license'] = $this->getCCLicenseCounts();
        $stats['tk_labels_usage'] = $this->getTkLabelCounts();

        return $stats;
    }

    public function getRightsStatementCounts()
    {
        try {
            return DB::table('object_rights_statement')
                ->join('rights_statement', DB::raw('rights_statement.id'), '=', DB::raw('object_rights_statement.rights_statement_id'))
                ->leftJoin('rights_statement_i18n', function ($join) {
                    $join->on(DB::raw('rights_statement_i18n.rights_statement_id'), '=', DB::raw('rights_statement.id'))
                         ->where(DB::raw('rights_statement_i18n.culture'), '=', $this->culture);
                })
                ->groupBy(DB::raw('rights_statement.id'), DB::raw('rights_statement.code'), DB::raw('rights_statement_i18n.name'))
                ->select([
                    DB::raw('rights_statement.code'),
                    DB::raw('rights_statement_i18n.name'),
                    DB::raw('COUNT(*) as count')
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getCCLicenseCounts()
    {
        try {
            return DB::table('extended_rights')
                ->join('rights_cc_license', DB::raw('rights_cc_license.id'), '=', DB::raw('extended_rights.creative_commons_license_id'))
                ->leftJoin('rights_cc_license_i18n', function ($join) {
                    $join->on(DB::raw('rights_cc_license_i18n.id'), '=', DB::raw('rights_cc_license.id'))
                         ->where(DB::raw('rights_cc_license_i18n.culture'), '=', $this->culture);
                })
                ->groupBy(DB::raw('rights_cc_license.id'), DB::raw('rights_cc_license.code'), DB::raw('rights_cc_license_i18n.name'))
                ->select([
                    DB::raw('rights_cc_license.code'),
                    DB::raw('rights_cc_license_i18n.name'),
                    DB::raw('COUNT(*) as count')
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTkLabelCounts()
    {
        try {
            return DB::table('rights_object_tk_label')
                ->join('rights_tk_label', DB::raw('rights_tk_label.id'), '=', DB::raw('rights_object_rights_tk_label.tk_label_id'))
                ->leftJoin('tk_label_i18n', function ($join) {
                    $join->on(DB::raw('tk_label_i18n.tk_label_id'), '=', DB::raw('rights_tk_label.id'))
                         ->where(DB::raw('tk_label_i18n.culture'), '=', $this->culture);
                })
                ->groupBy(DB::raw('rights_tk_label.id'), DB::raw('rights_tk_label.code'), DB::raw('tk_label_i18n.name'))
                ->select([
                    DB::raw('rights_tk_label.code'),
                    DB::raw('tk_label_i18n.name'),
                    DB::raw('COUNT(*) as count')
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function exportObjectRights($objectId = null)
    {
        $query = DB::table('information_object')
            ->join('slug', DB::raw('slug.object_id'), '=', DB::raw('information_object.id'))
            ->leftJoin('information_object_i18n', function ($join) {
                $join->on(DB::raw('information_object_i18n.id'), '=', DB::raw('information_object.id'))
                     ->where(DB::raw('information_object_i18n.culture'), '=', $this->culture);
            })
            ->leftJoin('object_rights_statement', DB::raw('object_rights_statement.object_id'), '=', DB::raw('information_object.id'))
            ->leftJoin('rights_statement', DB::raw('rights_statement.id'), '=', DB::raw('object_rights_statement.rights_statement_id'))
            ->leftJoin('rights_statement_i18n', function ($join) {
                $join->on(DB::raw('rights_statement_i18n.rights_statement_id'), '=', DB::raw('rights_statement.id'))
                     ->where(DB::raw('rights_statement_i18n.culture'), '=', $this->culture);
            })
            ->leftJoin('extended_rights', DB::raw('extended_rights.object_id'), '=', DB::raw('information_object.id'))
            ->leftJoin('rights_cc_license', DB::raw('rights_cc_license.id'), '=', DB::raw('extended_rights.creative_commons_license_id'))
            ->leftJoin('rights_cc_license_i18n', function ($join) {
                $join->on(DB::raw('rights_cc_license_i18n.id'), '=', DB::raw('rights_cc_license.id'))
                     ->where(DB::raw('rights_cc_license_i18n.culture'), '=', $this->culture);
            });

        if ($objectId) {
            $query->where(DB::raw('information_object.id'), '=', $objectId);
        }

        return $query->select([
            DB::raw('information_object.id'),
            DB::raw('slug.slug'),
            DB::raw('information_object.identifier'),
            DB::raw('information_object_i18n.title'),
            DB::raw('rights_statement.code as rights_statement_code'),
            DB::raw('rights_statement_i18n.name as rights_statement_name'),
            DB::raw('rights_cc_license.code as cc_license_code'),
            DB::raw('rights_cc_license_i18n.name as cc_license_name')
        ])->get();
    }
}
