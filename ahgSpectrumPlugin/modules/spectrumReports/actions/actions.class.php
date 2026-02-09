<?php
/**
 * Spectrum Reports Module
 * Reports for Spectrum 5.0 procedures
 */

use Illuminate\Database\Capsule\Manager as DB;

class spectrumReportsActions extends AhgActions
{
    protected function checkAccess()
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
    }

    protected function getCulture(): string
    {
        return $this->context->user->getCulture();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $this->stats = [
            'objectEntry' => DB::table('spectrum_object_entry')->count(),
            'objectExit' => DB::table('spectrum_object_exit')->count(),
            'loanIn' => DB::table('spectrum_loan_in')->count(),
            'loanOut' => DB::table('spectrum_loan_out')->count(),
            'acquisition' => DB::table('spectrum_acquisition')->count(),
            'movement' => DB::table('spectrum_movement')->count(),
            'location' => DB::table('spectrum_location')->count(),
            'conditionCheck' => DB::table('spectrum_condition_check')->count(),
            'conservation' => DB::table('spectrum_conservation')->count(),
            'valuation' => DB::table('spectrum_valuation')->count(),
            'deaccession' => DB::table('spectrum_deaccession')->count(),
        ];
        
        // Recent activity
        $this->recentActivity = DB::table('spectrum_audit_log')
            ->orderBy('action_date', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function executeLoans(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->loansIn = DB::table('spectrum_loan_in as l')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('l.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'l.object_id', '=', 's.object_id')
            ->select('l.*', 'ioi.title', 's.slug')
            ->orderBy('l.created_at', 'desc')
            ->get()
            ->toArray();

        $this->loansOut = DB::table('spectrum_loan_out as l')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('l.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'l.object_id', '=', 's.object_id')
            ->select('l.*', 'ioi.title', 's.slug')
            ->orderBy('l.created_at', 'desc')
            ->get()
            ->toArray();
        
        $this->summary = [
            'totalIn' => count($this->loansIn),
            'totalOut' => count($this->loansOut),
        ];
    }

    public function executeConditions(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->conditions = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('c.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->select('c.*', 'ioi.title', 's.slug')
            ->orderBy('c.check_date', 'desc')
            ->get()
            ->toArray();
        
        $this->byCondition = DB::table('spectrum_condition_check')
            ->select('overall_condition', DB::raw('COUNT(*) as count'))
            ->groupBy('overall_condition')
            ->get()
            ->toArray();
        
        $this->summary = [
            'total' => count($this->conditions),
            'byCondition' => $this->byCondition,
        ];
    }

    public function executeValuations(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->valuations = DB::table('spectrum_valuation as v')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('v.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'v.object_id', '=', 's.object_id')
            ->select('v.*', 'ioi.title', 's.slug')
            ->orderBy('v.valuation_date', 'desc')
            ->get()
            ->toArray();
        
        $this->summary = [
            'total' => count($this->valuations),
            'totalValue' => DB::table('spectrum_valuation')->sum('valuation_amount'),
        ];
    }

    public function executeMovements(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->movements = DB::table('spectrum_movement as m')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.*', 'ioi.title', 's.slug')
            ->orderBy('m.movement_date', 'desc')
            ->get()
            ->toArray();
        
        $this->locations = DB::table('spectrum_location')
            ->orderBy('location_name')
            ->get()
            ->toArray();
    }

    public function executeAcquisitions(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->acquisitions = DB::table('spectrum_acquisition as a')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('a.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'a.object_id', '=', 's.object_id')
            ->select('a.*', 'ioi.title', 's.slug')
            ->orderBy('a.acquisition_date', 'desc')
            ->get()
            ->toArray();
        
        $this->byMethod = DB::table('spectrum_acquisition')
            ->select('acquisition_method', DB::raw('COUNT(*) as count'))
            ->groupBy('acquisition_method')
            ->get()
            ->toArray();
    }

    public function executeConservation(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->treatments = DB::table('spectrum_conservation as c')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('c.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->select('c.*', 'ioi.title', 's.slug')
            ->orderBy('c.created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function executeObjectEntry(sfWebRequest $request)
    {
        $this->checkAccess();
        $culture = $this->getCulture();

        $this->entries = DB::table('spectrum_object_entry as e')
            ->leftJoin('information_object_i18n as ioi', function($join) use ($culture) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'e.object_id', '=', 's.object_id')
            ->select('e.*', 'ioi.title', 's.slug')
            ->orderBy('e.entry_date', 'desc')
            ->get()
            ->toArray();
    }
}
