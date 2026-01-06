<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class extendedRightsComponents extends sfComponents
{
    protected static $capsule = null;

    protected function getDb()
    {
        if (self::$capsule !== null) {
            return self::$capsule;
        }

        $frameworkPath = sfConfig::get('sf_root_dir').'/atom-framework';
        require_once $frameworkPath.'/vendor/autoload.php';

        $configFile = sfConfig::get('sf_root_dir').'/config/config.php';
        $config = include $configFile;

        $dsn = $config['all']['propel']['param']['dsn'] ?? '';
        $dbname = 'archive';
        $host = 'localhost';
        $port = 3306;
        
        if (preg_match('/dbname=([^;]+)/', $dsn, $m)) $dbname = $m[1];
        if (preg_match('/host=([^;]+)/', $dsn, $m)) $host = $m[1];
        if (preg_match('/port=([^;]+)/', $dsn, $m)) $port = (int) $m[1];

        self::$capsule = new Capsule();
        self::$capsule->addConnection([
            'driver' => 'mysql', 'host' => $host, 'port' => $port, 'database' => $dbname,
            'username' => $config['all']['propel']['param']['username'] ?? 'root',
            'password' => $config['all']['propel']['param']['password'] ?? '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
        ]);
        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();

        return self::$capsule;
    }

    public function executeRightsDisplay(sfWebRequest $request)
    {
        $objectId = $this->objectId ?? null;
        if (!$objectId) return sfView::NONE;

        $this->getDb();
        $objectId = (int) $objectId;

        $this->rightsStatement = Capsule::table('object_rights_statement')
            ->join('rights_statement', Capsule::raw('rights_statement.id'), '=', Capsule::raw('object_rights_statement.rights_statement_id'))
            ->leftJoin('rights_statement_i18n', function ($join) {
                $join->on(Capsule::raw('rights_statement_i18n.rights_statement_id'), '=', Capsule::raw('rights_statement.id'))
                     ->where(Capsule::raw('rights_statement_i18n.culture'), '=', 'en');
            })
            ->where(Capsule::raw('object_rights_statement.object_id'), '=', $objectId)
            ->select(
                Capsule::raw('rights_statement.id'),
                Capsule::raw('rights_statement.code'),
                Capsule::raw('rights_statement.uri'),
                Capsule::raw('rights_statement.icon_url'),
                Capsule::raw('rights_statement_i18n.name')
            )
            ->first();

        $this->creativeCommons = Capsule::table('object_creative_commons')
            ->join('creative_commons_license', Capsule::raw('creative_commons_license.id'), '=', Capsule::raw('object_creative_commons.creative_commons_license_id'))
            ->leftJoin('creative_commons_license_i18n', function ($join) {
                $join->on(Capsule::raw('creative_commons_license_i18n.creative_commons_license_id'), '=', Capsule::raw('creative_commons_license.id'))
                     ->where(Capsule::raw('creative_commons_license_i18n.culture'), '=', 'en');
            })
            ->where(Capsule::raw('object_creative_commons.object_id'), '=', $objectId)
            ->select(
                Capsule::raw('creative_commons_license.id'),
                Capsule::raw('creative_commons_license.code'),
                Capsule::raw('creative_commons_license.uri'),
                Capsule::raw('creative_commons_license.icon_url'),
                Capsule::raw('creative_commons_license_i18n.name')
            )
            ->first();

        $this->tkLabels = Capsule::table('object_tk_label')
            ->join('tk_label', Capsule::raw('tk_label.id'), '=', Capsule::raw('object_tk_label.tk_label_id'))
            ->leftJoin('tk_label_i18n', function ($join) {
                $join->on(Capsule::raw('tk_label_i18n.tk_label_id'), '=', Capsule::raw('tk_label.id'))
                     ->where(Capsule::raw('tk_label_i18n.culture'), '=', 'en');
            })
            ->leftJoin('tk_label_category', Capsule::raw('tk_label_category.id'), '=', Capsule::raw('tk_label.tk_label_category_id'))
            ->leftJoin('tk_label_category_i18n', function ($join) {
                $join->on(Capsule::raw('tk_label_category_i18n.tk_label_category_id'), '=', Capsule::raw('tk_label_category.id'))
                     ->where(Capsule::raw('tk_label_category_i18n.culture'), '=', 'en');
            })
            ->where(Capsule::raw('object_tk_label.object_id'), '=', $objectId)
            ->select(
                Capsule::raw('tk_label.id'),
                Capsule::raw('tk_label.code'),
                Capsule::raw('tk_label.uri'),
                Capsule::raw('tk_label.icon_url'),
                Capsule::raw('tk_label_category.code as category_code'),
                Capsule::raw('tk_label_category_i18n.name as category_name'),
                Capsule::raw('tk_label_i18n.name as name'),
                Capsule::raw('object_tk_label.community_name')
            )
            ->get();

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
            $this->rights = (object)[
                'rs_code' => $this->rightsStatement->code ?? null,
                'rs_uri' => $this->rightsStatement->uri ?? null,
                'rs_name' => $this->rightsStatement->name ?? null,
                'rs_icon_url' => $this->rightsStatement->icon_url ?? null,
                'rs_category' => $this->rightsStatement->category ?? null,
                'rs_definition' => $this->rightsStatement->definition ?? null,
                'cc_code' => $this->creativeCommons->code ?? null,
                'cc_uri' => $this->creativeCommons->uri ?? null,
                'cc_name' => $this->creativeCommons->name ?? null,
                'cc_icon_url' => $this->creativeCommons->icon_url ?? null,
                'tk_labels' => $this->tkLabels
            ];
        }
    }

    public function executeProvenanceDisplay(sfWebRequest $request)
    {
        $objectId = $this->objectId ?? null;
        if (!$objectId) return sfView::NONE;

        $this->getDb();
        $this->provenance = Capsule::table('object_provenance')->where('object_id', (int) $objectId)->first();
        $this->donor = null;

        if ($this->provenance && isset($this->provenance->donor_id)) {
            $this->donor = Capsule::table('donor')
                ->leftJoin('donor_i18n', function ($join) {
                    $join->on(Capsule::raw('donor_i18n.id'), '=', Capsule::raw('donor.id'))
                         ->where(Capsule::raw('donor_i18n.culture'), '=', 'en');
                })
                ->leftJoin('slug', Capsule::raw('slug.object_id'), '=', Capsule::raw('donor.id'))
                ->where(Capsule::raw('donor.id'), '=', $this->provenance->donor_id)
                ->select(Capsule::raw('donor.id'), Capsule::raw('donor_i18n.authorized_form_of_name'), Capsule::raw('slug.slug'))
                ->first();
        }
    }

    public function executeGetTkLabels(sfWebRequest $request)
    {
        $this->getDb();

        $this->tkLabels = Capsule::table('tk_label')
            ->leftJoin('tk_label_i18n', function ($join) {
                $join->on(Capsule::raw('tk_label_i18n.tk_label_id'), '=', Capsule::raw('tk_label.id'))
                     ->where(Capsule::raw('tk_label_i18n.culture'), '=', 'en');
            })
            ->leftJoin('tk_label_category', Capsule::raw('tk_label_category.id'), '=', Capsule::raw('tk_label.tk_label_category_id'))
            ->leftJoin('tk_label_category_i18n', function ($join) {
                $join->on(Capsule::raw('tk_label_category_i18n.tk_label_category_id'), '=', Capsule::raw('tk_label_category.id'))
                     ->where(Capsule::raw('tk_label_category_i18n.culture'), '=', 'en');
            })
            ->where(Capsule::raw('tk_label.is_active'), '=', 1)
            ->orderBy(Capsule::raw('tk_label_category.sort_order'))
            ->orderBy(Capsule::raw('tk_label.sort_order'))
            ->select(
                Capsule::raw('tk_label.id'),
                Capsule::raw('tk_label.code'),
                Capsule::raw('tk_label.uri'),
                Capsule::raw('tk_label.icon_url'),
                Capsule::raw('tk_label_category.code as category_code'),
                Capsule::raw('tk_label_category_i18n.name as category_name'),
                Capsule::raw('tk_label_i18n.name as name')
            )
            ->get();
    }

    public function executeRightsStats(sfWebRequest $request)
    {
        $this->getDb();

        $this->stats = (object) [
            'total_objects' => Capsule::table('information_object')->count(),
            'with_rights_statement' => Capsule::table('object_rights_statement')->distinct()->count('object_id'),
            'with_creative_commons' => Capsule::table('object_creative_commons')->distinct()->count('object_id'),
            'with_tk_labels' => Capsule::table('object_tk_label')->distinct()->count('object_id'),
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

        $this->getDb();
        $objectId = (int) $objectId;

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
            $this->rights = (object)[
                'rs_code' => $this->rightsStatement->code ?? null,
                'rs_uri' => $this->rightsStatement->uri ?? null,
                'rs_name' => $this->rightsStatement->name ?? null,
                'rs_icon_url' => $this->rightsStatement->icon_url ?? null,
                'rs_category' => $this->rightsStatement->category ?? null,
                'rs_definition' => $this->rightsStatement->definition ?? null,
                'cc_code' => $this->creativeCommons->code ?? null,
                'cc_uri' => $this->creativeCommons->uri ?? null,
                'cc_name' => $this->creativeCommons->name ?? null,
                'cc_icon_url' => $this->creativeCommons->icon_url ?? null,
                'tk_labels' => $this->tkLabels
            ];
        }
    }

}
