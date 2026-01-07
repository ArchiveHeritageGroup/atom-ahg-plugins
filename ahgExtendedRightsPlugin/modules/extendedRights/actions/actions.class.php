<?php
use Illuminate\Database\Capsule\Manager as DB;

use Illuminate\Database\Capsule\Manager as Capsule;

class extendedRightsActions extends sfActions
{
    protected static $dbInit = false;

    protected function initDb()
    {
        if (self::$dbInit) return;
        $frameworkPath = sfConfig::get('sf_root_dir').'/atom-framework';
        require_once $frameworkPath.'/vendor/autoload.php';
        $configFile = sfConfig::get('sf_root_dir').'/config/config.php';
        $config = include $configFile;
        $dsn = $config['all']['propel']['param']['dsn'] ?? '';
        $dbname = 'archive'; $host = 'localhost'; $port = 3306;
        if (preg_match('/dbname=([^;]+)/', $dsn, $m)) $dbname = $m[1];
        if (preg_match('/host=([^;]+)/', $dsn, $m)) $host = $m[1];
        if (preg_match('/port=([^;]+)/', $dsn, $m)) $port = (int)$m[1];
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'=>'mysql','host'=>$host,'port'=>$port,'database'=>$dbname,
            'username'=>$config['all']['propel']['param']['username']??'root',
            'password'=>$config['all']['propel']['param']['password']??'',
            'charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        self::$dbInit = true;
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->initDb();
        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function($j) {
                $j->on('rights_statement_i18n.rights_statement_id','=','rights_statement.id')
                  ->where('rights_statement_i18n.culture','=','en');
            })
            ->where('rights_statement.is_active','=',1)
            ->orderBy('rights_statement.category')->orderBy('rights_statement.sort_order')
            ->select([
                Capsule::raw('rights_statement.id'),
                Capsule::raw('rights_statement.code'),
                Capsule::raw('rights_statement.uri'),
                Capsule::raw('rights_statement.category'),
                Capsule::raw('rights_statement.icon_url'),
                Capsule::raw('rights_statement_i18n.name'),
                Capsule::raw('rights_statement_i18n.definition as description')
            ])->get();

        $this->ccLicenses = Capsule::table('creative_commons_license')
            ->leftJoin('creative_commons_license_i18n', function($j) {
                $j->on('creative_commons_license_i18n.creative_commons_license_id','=','creative_commons_license.id')
                  ->where('creative_commons_license_i18n.culture','=','en');
            })
            ->where('creative_commons_license.is_active','=',1)
            ->orderBy('creative_commons_license.sort_order')
            ->select([
                Capsule::raw('creative_commons_license.id'),
                Capsule::raw('creative_commons_license.code'),
                Capsule::raw('creative_commons_license.uri'),
                Capsule::raw('creative_commons_license.icon_url'),
                Capsule::raw('creative_commons_license_i18n.name'),
                Capsule::raw('creative_commons_license_i18n.description')
            ])->get();

        $this->tkLabels = Capsule::table('tk_label')
            ->leftJoin('tk_label_i18n', function($j) {
                $j->on('tk_label_i18n.tk_label_id','=','tk_label.id')
                  ->where('tk_label_i18n.culture','=','en');
            })
            ->leftJoin('tk_label_category','tk_label_category.id','=','tk_label.tk_label_category_id')
            ->leftJoin('tk_label_category_i18n', function($j) {
                $j->on('tk_label_category_i18n.tk_label_category_id','=','tk_label_category.id')
                  ->where('tk_label_category_i18n.culture','=','en');
            })
            ->where('tk_label.is_active','=',1)
            ->orderBy('tk_label_category.sort_order')->orderBy('tk_label.sort_order')
            ->select([
                Capsule::raw('tk_label.id'),
                Capsule::raw('tk_label.code'),
                Capsule::raw('tk_label.uri'),
                Capsule::raw('tk_label.icon_url'),
                Capsule::raw('tk_label_category.code as category_code'),
                Capsule::raw('tk_label_category_i18n.name as category_name'),
                Capsule::raw('tk_label_i18n.name as name'),
                Capsule::raw('tk_label_i18n.description')
            ])->get();

        $this->stats = (object)[
            'total_objects' => Capsule::table('information_object')->count(),
            'with_rights_statement' => Capsule::table('object_rights_statement')->distinct()->count('object_id'),
            'with_creative_commons' => Capsule::table('object_creative_commons')->distinct()->count('object_id'),
            'with_tk_labels' => Capsule::table('object_tk_label')->distinct()->count('object_id'),
            'active_embargoes' => Capsule::table('rights_embargo')->where('status','=','active')->count()
        ];
    }

    public function executeEdit(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = DB::table("information_object as io")->join("slug", "slug.object_id", "=", "io.id")->leftJoin("information_object_i18n as ioi", function($j) { $j->on("ioi.id", "=", "io.id")->where("ioi.culture", "=", sfContext::getInstance()->getUser()->getCulture()); })->where("slug.slug", $slug)->select("io.*", "ioi.title", "slug.slug")->first();
        if (!$this->resource) $this->forward404('Resource not found');
        $this->initDb();
        $oid = (int)$this->resource->id;

        $this->currentRights = (object)[
            'rights_statement' => Capsule::table('object_rights_statement')->where('object_id','=',$oid)->first(),
            'creative_commons' => Capsule::table('object_creative_commons')->where('object_id','=',$oid)->first(),
            'tk_labels' => Capsule::table('object_tk_label')->where('object_id','=',$oid)->pluck('tk_label_id')->toArray(),
            'embargo' => Capsule::table('rights_embargo')->where('object_id','=',$oid)->where('status','=','active')->first()
            ,'rights_holder' => Capsule::table('object_rights_holder')->where('object_id','=',$oid)->first()
        ];

        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function($j) {
                $j->on('rights_statement_i18n.rights_statement_id','=','rights_statement.id')->where('rights_statement_i18n.culture','=','en');
            })->where('rights_statement.is_active','=',1)->select([Capsule::raw('rights_statement.id'),Capsule::raw('rights_statement.code'),Capsule::raw('rights_statement_i18n.name')])->get();

        $this->ccLicenses = Capsule::table('creative_commons_license')
            ->leftJoin('creative_commons_license_i18n', function($j) {
                $j->on('creative_commons_license_i18n.creative_commons_license_id','=','creative_commons_license.id')->where('creative_commons_license_i18n.culture','=','en');
            })->where('creative_commons_license.is_active','=',1)->select([Capsule::raw('creative_commons_license.id'),Capsule::raw('creative_commons_license.code'),Capsule::raw('creative_commons_license_i18n.name')])->get();

        $this->tkLabels = Capsule::table('tk_label')
            ->leftJoin('tk_label_i18n', function($j) {
                $j->on('tk_label_i18n.tk_label_id','=','tk_label.id')->where('tk_label_i18n.culture','=','en');
            })->where('tk_label.is_active','=',1)->select([Capsule::raw('tk_label.id'),Capsule::raw('tk_label.code'),Capsule::raw('tk_label_i18n.name')])->get();
        $this->donors = Capsule::table('donor')
            ->join('slug', Capsule::raw('slug.object_id'), '=', Capsule::raw('donor.id'))
            ->leftJoin('actor_i18n', function($j) {
                $j->on('actor_i18n.id','=','donor.id')->where('actor_i18n.culture','=','en');
            })->whereNotNull('actor_i18n.authorized_form_of_name')
            ->select([Capsule::raw('donor.id'),Capsule::raw('slug.slug'),Capsule::raw('actor_i18n.authorized_form_of_name as name')])
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get();


        if ($request->isMethod('post')) {
            $rsId = $request->getParameter('rights_statement_id');
            Capsule::table('object_rights_statement')->where('object_id','=',$oid)->delete();
            if ($rsId) Capsule::table('object_rights_statement')->insert(['object_id'=>$oid,'rights_statement_id'=>(int)$rsId,'created_at'=>date('Y-m-d H:i:s')]);

            $ccId = $request->getParameter('cc_license_id');
            Capsule::table('object_creative_commons')->where('object_id','=',$oid)->delete();
            if ($ccId) Capsule::table('object_creative_commons')->insert(['object_id'=>$oid,'creative_commons_license_id'=>(int)$ccId,'created_at'=>date('Y-m-d H:i:s')]);

            $tkIds = $request->getParameter('tk_label_ids', []);
            Capsule::table('object_tk_label')->where('object_id','=',$oid)->delete();
            if (is_array($tkIds)) foreach ($tkIds as $tkId) Capsule::table('object_tk_label')->insert(['object_id'=>$oid,'tk_label_id'=>(int)$tkId,'created_at'=>date('Y-m-d H:i:s')]);

            // Save rights holder (donor)
            $donorId = $request->getParameter('rights_holder_id');
            Capsule::table('object_rights_holder')->where('object_id','=',$oid)->delete();
            if ($donorId) Capsule::table('object_rights_holder')->insert(['object_id'=>$oid,'donor_id'=>(int)$donorId,'created_at'=>date('Y-m-d H:i:s')]);

            $this->getUser()->setFlash('notice', 'Rights updated.');
            $this->redirect(['module'=>'informationobject','action'=>'index','slug'=>$this->resource->slug]);
        }
    }

    public function executeBatch(sfWebRequest $request)
    {
        $this->initDb();

        $this->rightsStatements = Capsule::table('rights_statement')
            ->leftJoin('rights_statement_i18n', function($j) {
                $j->on('rights_statement_i18n.rights_statement_id','=','rights_statement.id')->where('rights_statement_i18n.culture','=','en');
            })->where('rights_statement.is_active','=',1)->select([Capsule::raw('rights_statement.id'),Capsule::raw('rights_statement.code'),Capsule::raw('rights_statement_i18n.name')])->get();

        $this->ccLicenses = Capsule::table('creative_commons_license')
            ->leftJoin('creative_commons_license_i18n', function($j) {
                $j->on('creative_commons_license_i18n.creative_commons_license_id','=','creative_commons_license.id')->where('creative_commons_license_i18n.culture','=','en');
            })->where('creative_commons_license.is_active','=',1)->select([Capsule::raw('creative_commons_license.id'),Capsule::raw('creative_commons_license.code'),Capsule::raw('creative_commons_license_i18n.name')])->get();

        $this->tkLabels = Capsule::table('tk_label')
            ->leftJoin('tk_label_i18n', function($j) {
                $j->on('tk_label_i18n.tk_label_id','=','tk_label.id')->where('tk_label_i18n.culture','=','en');
            })->where('tk_label.is_active','=',1)->select([Capsule::raw('tk_label.id'),Capsule::raw('tk_label.code'),Capsule::raw('tk_label_i18n.name')])->get();

        $this->objects = Capsule::table('information_object')
            ->join('slug','slug.object_id','=','information_object.id')
            ->leftJoin('information_object_i18n', function($j) {
                $j->on('information_object_i18n.id','=','information_object.id')->where('information_object_i18n.culture','=','en');
            })
            ->leftJoin('term_i18n', function($j) {
                $j->on('term_i18n.id','=','information_object.level_of_description_id')->where('term_i18n.culture','=','en');
            })
            ->where('information_object.parent_id','=','active')
            ->whereNotNull('information_object_i18n.title')
            ->orderBy('information_object_i18n.title')
            ->limit(500)
            ->select([
                Capsule::raw('information_object.id'),
                Capsule::raw('slug.slug'),
                Capsule::raw('information_object.identifier'),
                Capsule::raw('information_object_i18n.title'),
                Capsule::raw('term_i18n.name as level')
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
                $objId = (int)$objId;
                if ($rightsType === 'rights_statement') {
                    Capsule::table('object_rights_statement')->where('object_id','=',$objId)->delete();
                    Capsule::table('object_rights_statement')->insert(['object_id'=>$objId,'rights_statement_id'=>(int)$valueId,'created_at'=>date('Y-m-d H:i:s')]);
                    $count++;
                } elseif ($rightsType === 'creative_commons') {
                    Capsule::table('object_creative_commons')->where('object_id','=',$objId)->delete();
                    Capsule::table('object_creative_commons')->insert(['object_id'=>$objId,'creative_commons_license_id'=>(int)$valueId,'created_at'=>date('Y-m-d H:i:s')]);
                    $count++;
                } elseif ($rightsType === 'tk_label') {
                    if (!Capsule::table('object_tk_label')->where('object_id','=',$objId)->where('tk_label_id','=',(int)$valueId)->exists()) {
                        Capsule::table('object_tk_label')->insert(['object_id'=>$objId,'tk_label_id'=>(int)$valueId,'created_at'=>date('Y-m-d H:i:s')]);
                        $count++;
                    }
                }
            }
            $this->getUser()->setFlash('notice', "Rights assigned to {$count} objects.");
            $this->redirect(['module'=>'extendedRights','action'=>'index']);
        }
    }

    public function executeEmbargoes(sfWebRequest $request)
    {
        $this->initDb();
        $this->embargoes = Capsule::table('rights_embargo')
            ->join('slug','slug.object_id','=','rights_embargo.object_id')
            ->leftJoin('information_object_i18n', function($j) {
                $j->on('information_object_i18n.id','=','rights_embargo.object_id')->where('information_object_i18n.culture','=','en');
            })
            ->where('rights_embargo.status','=','active')
            ->orderBy('rights_embargo.end_date')
            ->select([
                Capsule::raw('rights_embargo.id'),
                Capsule::raw('rights_embargo.object_id'),
                Capsule::raw('rights_embargo.embargo_type'),
                Capsule::raw('rights_embargo.start_date'),
                Capsule::raw('rights_embargo.end_date'),
                Capsule::raw('information_object_i18n.title'),
                Capsule::raw('slug.slug')
            ])->get();
    }

    public function executeLiftEmbargo(sfWebRequest $request)
    {
        $this->initDb();
        Capsule::table('rights_embargo')->where('id','=',(int)$request->getParameter('id'))->update(['status'=>'lifted','lifted_at'=>date('Y-m-d H:i:s')]);
        $this->getUser()->setFlash('notice', 'Embargo lifted.');
        $this->redirect(['module'=>'extendedRights','action'=>'embargoes']);
    }

    /**
     * Show expiring embargoes
     */
    public function executeExpiringEmbargoes(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        // Initialize database
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
        $db = \Illuminate\Database\Capsule\Manager::connection();
        
        $days = (int) $request->getParameter('days', 30);
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $this->days = $days;
        $this->embargoes = $db->table('rights_embargo as re')
            ->join('information_object as io', 're.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->select(
                're.id',
                're.object_id',
                'ioi.title',
                's.slug',
                're.end_date',
                're.embargo_type',
                're.reason',
                're.status',
                $db->raw("DATEDIFF(re.end_date, CURDATE()) as days_remaining")
            )
            ->whereNotNull('re.end_date')
            ->where('re.end_date', '<=', $expiryDate)
            ->where('re.end_date', '>=', date('Y-m-d'))
            ->where('re.status', 'active')
            ->orderBy('re.end_date', 'asc')
            ->limit(100)
            ->get()
            ->toArray();
    }
}