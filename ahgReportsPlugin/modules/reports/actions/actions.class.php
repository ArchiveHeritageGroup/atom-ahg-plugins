<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Reports module actions
 */

use Illuminate\Database\Capsule\Manager as DB;

class reportsActions extends AhgController
{
    public function executeIndex($request)
    {
        $this->stats = $this->getReportStats();
    }

    public function executeDescriptions($request)
    {
        // Redirect to existing report
        $this->forward('reports', 'reportInformationObject');
    }

    public function executeAuthorities($request)
    {
        $this->forward('reports', 'reportAuthorityRecord');
    }

    public function executeRepositories($request)
    {
        $this->forward('reports', 'reportRepository');
    }

    public function executeAccessions($request)
    {
        $this->forward('reports', 'reportAccession');
    }

    public function executeStorage($request)
    {
        $this->forward('reports', 'reportPhysicalStorage');
    }

    public function executeRecent($request)
    {
        $this->forward('reports', 'reportUpdates');
    }

    public function executeActivity($request)
    {
        $this->forward('reports', 'reportUser');
    }

    public function executeDonors($request)
    {
        $this->forward('reports', 'reportDonor');
    }

    public function executeReportDonor($request)
    {
        $this->form = new sfForm();
        $this->form->setWidget("culture", new sfWidgetFormChoice(["choices" => ["en" => "English", "fr" => "French"]]));
        $this->form->setWidget("dateStart", new sfWidgetFormInput());
        $this->form->setWidget("dateEnd", new sfWidgetFormInput());
        
        $culture = $request->getParameter("culture", "en");
        $dateStart = $request->getParameter("dateStart");
        $dateEnd = $request->getParameter("dateEnd");
        
        $query = DB::table("donor as d")
            ->leftJoin("donor_i18n as di", function($join) use ($culture) {
                $join->on("d.id", "=", "di.id")->where("di.culture", "=", $culture);
            })
            ->leftJoin("contact_information as ci", "d.id", "=", "ci.actor_id")
            ->select("d.id", "di.authorized_form_of_name as name", "ci.email", "ci.telephone", "d.created_at");
        
        if ($dateStart) {
            $query->where("d.created_at", ">=", $dateStart);
        }
        if ($dateEnd) {
            $query->where("d.created_at", "<=", $dateEnd);
        }
        
        $this->donors = $query->orderBy("di.authorized_form_of_name")->get()->toArray();
        $this->culture = $culture;
    }

    public function executeTaxonomy($request)
    {
        $this->forward('reports', 'reportTaxomomy');
    }

    protected function getReportStats()
    {
        // Recent updates - join with object table for updated_at
        $recentUpdates = DB::table('information_object')
            ->join('object', 'information_object.id', '=', 'object.id')
            ->where('information_object.id', '!=', 1)
            ->where('object.updated_at', '>=', date('Y-m-d', strtotime('-7 days')))
            ->count();

        // Get publication status counts using status table
        $draftCount = DB::table('status')
            ->join('information_object', 'status.object_id', '=', 'information_object.id')
            ->where('status.type_id', 158) // Publication status type
            ->where('status.status_id', 159) // Draft
            ->where('information_object.id', '!=', 1)
            ->count();

        $publishedCount = DB::table('status')
            ->join('information_object', 'status.object_id', '=', 'information_object.id')
            ->where('status.type_id', 158)
            ->where('status.status_id', 160) // Published
            ->where('information_object.id', '!=', 1)
            ->count();

        return [
            'totalDescriptions' => DB::table('information_object')->where('id', '!=', 1)->count(),
            'totalActors' => DB::table('actor')->where('id', '!=', 1)->count(),
            'totalRepositories' => DB::table('repository')->count(),
            'totalDigitalObjects' => DB::table('digital_object')->count(),
            'totalAccessions' => DB::table('accession')->count(),
            'recentUpdates' => $recentUpdates,
            'draftRecords' => $draftCount,
            'publishedRecords' => $publishedCount
        ];
    }
}

