<?php
use Illuminate\Database\Capsule\Manager as DB;

class galleryActions extends AhgActions
{
    protected $service;

    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgGalleryPlugin/lib/Services/GalleryService.php';
        $this->service = new GalleryService();
    }

    public function executeIndex(sfWebRequest $request) { $this->redirect('gallery/dashboard'); }

    public function executeDashboard(sfWebRequest $request)
    {
        $this->stats = $this->service->getDashboardStats();
    }

    // =========== EXHIBITIONS ===========
    // Exhibition functionality moved to standalone ahgExhibitionPlugin
    // These actions redirect to the unified exhibition module

    public function executeExhibitions(sfWebRequest $request)
    {
        $this->redirect('exhibition/index');
    }

    public function executeCreateExhibition(sfWebRequest $request)
    {
        $this->redirect('exhibition/add');
    }

    public function executeViewExhibition(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $this->redirect('exhibition/show?id=' . $id);
    }

    public function executeAddExhibitionObject(sfWebRequest $request)
    {
        $id = $request->getParameter('exhibition_id');
        $this->redirect('exhibition/objects?id=' . $id);
    }

    // =========== LOANS ===========

    public function executeLoans(sfWebRequest $request)
    {
        $this->loans = $this->service->getLoans([
            'type' => $request->getParameter('type'),
            'status' => $request->getParameter('status'),
        ]);
        $this->currentType = $request->getParameter('type');
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeCreateLoan(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        // Get exhibitions from unified exhibition table
        $this->exhibitions = DB::table('exhibition')
            ->whereIn('status', ['planning', 'preparation'])
            ->orderBy('title')
            ->get()
            ->toArray();
        if ($request->isMethod('post')) {
            $id = $this->service->createLoan([
                'loan_type' => $request->getParameter('loan_type'),
                'purpose' => $request->getParameter('purpose'),
                'exhibition_id' => $request->getParameter('exhibition_id') ?: null,
                'institution_name' => $request->getParameter('institution_name'),
                'institution_address' => $request->getParameter('institution_address'),
                'contact_name' => $request->getParameter('contact_name'),
                'contact_email' => $request->getParameter('contact_email'),
                'contact_phone' => $request->getParameter('contact_phone'),
                'loan_start_date' => $request->getParameter('loan_start_date'),
                'loan_end_date' => $request->getParameter('loan_end_date'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->getUser()->setFlash('success', 'Loan record created');
            $this->redirect('gallery/viewLoan?id=' . $id);
        }
    }

    public function executeViewLoan(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id');
        $this->loan = $this->service->getLoan($id);
        if (!$this->loan) { $this->forward404('Loan not found'); }

        if ($request->isMethod('post')) {
            $do = $request->getParameter('do');
            if ($do === 'update_status') {
                $this->service->updateLoan($id, ['status' => $request->getParameter('status')]);
                $this->getUser()->setFlash('success', 'Status updated');
            } elseif ($do === 'update') {
                $this->service->updateLoan($id, [
                    'insurance_value' => $request->getParameter('insurance_value'),
                    'insurance_provider' => $request->getParameter('insurance_provider'),
                    'special_conditions' => $request->getParameter('special_conditions'),
                ]);
                $this->getUser()->setFlash('success', 'Loan updated');
            }
            $this->redirect('gallery/viewLoan?id=' . $id);
        }
    }

    public function executeFacilityReport(sfWebRequest $request)
    {
        $loanId = (int) $request->getParameter('loan_id');
        $this->loan = $this->service->getLoan($loanId);
        if (!$this->loan) { $this->forward404('Loan not found'); }

        if ($request->isMethod('post')) {
            $this->service->createFacilityReport($loanId, [
                'report_type' => $this->loan->loan_type === 'outgoing' ? 'outgoing' : 'incoming',
                'institution_name' => $request->getParameter('institution_name'),
                'building_age' => $request->getParameter('building_age'),
                'construction_type' => $request->getParameter('construction_type'),
                'fire_detection' => $request->getParameter('fire_detection') ? 1 : 0,
                'fire_suppression' => $request->getParameter('fire_suppression') ? 1 : 0,
                'security_24hr' => $request->getParameter('security_24hr') ? 1 : 0,
                'security_guards' => $request->getParameter('security_guards') ? 1 : 0,
                'cctv' => $request->getParameter('cctv') ? 1 : 0,
                'intrusion_detection' => $request->getParameter('intrusion_detection') ? 1 : 0,
                'climate_controlled' => $request->getParameter('climate_controlled') ? 1 : 0,
                'temperature_range' => $request->getParameter('temperature_range'),
                'humidity_range' => $request->getParameter('humidity_range'),
                'uv_filtering' => $request->getParameter('uv_filtering') ? 1 : 0,
                'trained_handlers' => $request->getParameter('trained_handlers') ? 1 : 0,
                'loading_dock' => $request->getParameter('loading_dock') ? 1 : 0,
                'completed_by' => $request->getParameter('completed_by'),
                'completed_date' => $request->getParameter('completed_date') ?: date('Y-m-d'),
            ]);
            $this->service->updateLoan($loanId, ['facility_report_received' => 1]);
            $this->getUser()->setFlash('success', 'Facility report saved');
            $this->redirect('gallery/viewLoan?id=' . $loanId);
        }
    }

    // =========== VALUATIONS ===========

    public function executeValuations(sfWebRequest $request)
    {
        $objectId = (int) $request->getParameter('object_id');
        if ($objectId) {
            $this->objectId = $objectId;
            $this->object = DB::table('information_object_i18n')->where('id', $objectId)->where('culture', 'en')->first();
            $this->valuations = $this->service->getValuations($objectId);
        } else {
            $this->valuations = DB::table('gallery_valuation as v')
                ->leftJoin('information_object_i18n as i18n', function($j) {
                    $j->on('v.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })
                ->where('v.is_current', 1)
                ->select('v.*', 'i18n.title as object_title')
                ->orderBy('v.valuation_date', 'desc')->limit(100)->get()->toArray();
        }
    }

    public function executeCreateValuation(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->objectId = (int) $request->getParameter('object_id');
        $this->object = DB::table('information_object_i18n')->where('id', $this->objectId)->where('culture', 'en')->first();

        if ($request->isMethod('post')) {
            $this->service->createValuation([
                'object_id' => $this->objectId,
                'valuation_type' => $request->getParameter('valuation_type'),
                'value_amount' => $request->getParameter('value_amount'),
                'currency' => $request->getParameter('currency') ?: 'ZAR',
                'valuation_date' => $request->getParameter('valuation_date') ?: date('Y-m-d'),
                'valid_until' => $request->getParameter('valid_until'),
                'appraiser_name' => $request->getParameter('appraiser_name'),
                'appraiser_credentials' => $request->getParameter('appraiser_credentials'),
                'appraiser_organization' => $request->getParameter('appraiser_organization'),
                'methodology' => $request->getParameter('methodology'),
                'notes' => $request->getParameter('notes'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
            ]);
            $this->getUser()->setFlash('success', 'Valuation recorded');
            $this->redirect('gallery/valuations?object_id=' . $this->objectId);
        }
    }

    // =========== ARTISTS ===========

    public function executeArtists(sfWebRequest $request)
    {
        $this->artists = $this->service->getArtists([
            'represented' => $request->getParameter('represented'),
            'search' => $request->getParameter('q'),
        ]);
    }

    public function executeCreateArtist(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        if ($request->isMethod('post')) {
            $id = $this->service->createArtist([
                'display_name' => $request->getParameter('display_name'),
                'sort_name' => $request->getParameter('sort_name'),
                'birth_date' => $request->getParameter('birth_date'),
                'birth_place' => $request->getParameter('birth_place'),
                'death_date' => $request->getParameter('death_date'),
                'nationality' => $request->getParameter('nationality'),
                'artist_type' => $request->getParameter('artist_type'),
                'medium_specialty' => $request->getParameter('medium_specialty'),
                'biography' => $request->getParameter('biography'),
                'represented' => $request->getParameter('represented') ? 1 : 0,
                'email' => $request->getParameter('email'),
                'phone' => $request->getParameter('phone'),
                'website' => $request->getParameter('website'),
            ]);
            $this->getUser()->setFlash('success', 'Artist created');
            $this->redirect('gallery/viewArtist?id=' . $id);
        }
    }

    public function executeViewArtist(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id');
        $this->artist = $this->service->getArtist($id);
        if (!$this->artist) { $this->forward404('Artist not found'); }

        if ($request->isMethod('post')) {
            $do = $request->getParameter('do');
            if ($do === 'update') {
                $this->service->updateArtist($id, [
                    'display_name' => $request->getParameter('display_name'),
                    'biography' => $request->getParameter('biography'),
                    'artist_statement' => $request->getParameter('artist_statement'),
                    'represented' => $request->getParameter('represented') ? 1 : 0,
                    'representation_terms' => $request->getParameter('representation_terms'),
                    'commission_rate' => $request->getParameter('commission_rate'),
                    'email' => $request->getParameter('email'),
                    'phone' => $request->getParameter('phone'),
                    'website' => $request->getParameter('website'),
                ]);
                $this->getUser()->setFlash('success', 'Artist updated');
            } elseif ($do === 'add_biblio') {
                $this->service->addBibliography($id, [
                    'entry_type' => $request->getParameter('entry_type'),
                    'title' => $request->getParameter('biblio_title'),
                    'author' => $request->getParameter('author'),
                    'publication' => $request->getParameter('publication'),
                    'publication_date' => $request->getParameter('publication_date'),
                    'url' => $request->getParameter('biblio_url'),
                ]);
                $this->getUser()->setFlash('success', 'Bibliography entry added');
            } elseif ($do === 'add_exhibition') {
                $this->service->addExhibitionHistory($id, [
                    'exhibition_type' => $request->getParameter('exh_type'),
                    'title' => $request->getParameter('exh_title'),
                    'venue' => $request->getParameter('exh_venue'),
                    'city' => $request->getParameter('exh_city'),
                    'country' => $request->getParameter('exh_country'),
                    'start_date' => $request->getParameter('exh_start'),
                    'end_date' => $request->getParameter('exh_end'),
                ]);
                $this->getUser()->setFlash('success', 'Exhibition added');
            }
            $this->redirect('gallery/viewArtist?id=' . $id);
        }
    }

    // =========== VENUES ===========

    public function executeVenues(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->venues = DB::table('gallery_venue')->orderBy('name')->get()->toArray();
        foreach ($this->venues as &$v) {
            $v->spaces = DB::table('gallery_space')->where('venue_id', $v->id)->get()->toArray();
        }
    }

    public function executeCreateVenue(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        if ($request->isMethod('post')) {
            $id = DB::table('gallery_venue')->insertGetId([
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'address' => $request->getParameter('address'),
                'total_area_sqm' => $request->getParameter('total_area_sqm') ?: null,
                'max_capacity' => $request->getParameter('max_capacity') ?: null,
                'climate_controlled' => $request->getParameter('climate_controlled') ? 1 : 0,
                'security_level' => $request->getParameter('security_level'),
                'contact_name' => $request->getParameter('contact_name'),
                'contact_email' => $request->getParameter('contact_email'),
                'contact_phone' => $request->getParameter('contact_phone'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->getUser()->setFlash('success', 'Venue created');
            $this->redirect('gallery/viewVenue?id=' . $id);
        }
    }

    public function executeViewVenue(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $id = (int) $request->getParameter('id');
        $this->venue = DB::table('gallery_venue')->where('id', $id)->first();
        if (!$this->venue) { $this->forward404('Venue not found'); }
        $this->spaces = DB::table('gallery_space')->where('venue_id', $id)->orderBy('name')->get()->toArray();

        if ($request->isMethod('post')) {
            $do = $request->getParameter('do');
            if ($do === 'update') {
                DB::table('gallery_venue')->where('id', $id)->update([
                    'name' => $request->getParameter('name'),
                    'description' => $request->getParameter('description'),
                    'address' => $request->getParameter('address'),
                    'total_area_sqm' => $request->getParameter('total_area_sqm') ?: null,
                    'max_capacity' => $request->getParameter('max_capacity') ?: null,
                    'climate_controlled' => $request->getParameter('climate_controlled') ? 1 : 0,
                    'security_level' => $request->getParameter('security_level'),
                    'contact_name' => $request->getParameter('contact_name'),
                    'contact_email' => $request->getParameter('contact_email'),
                    'contact_phone' => $request->getParameter('contact_phone'),
                    'is_active' => $request->getParameter('is_active') ? 1 : 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('success', 'Venue updated');
            } elseif ($do === 'add_space') {
                DB::table('gallery_space')->insert([
                    'venue_id' => $id,
                    'name' => $request->getParameter('space_name'),
                    'description' => $request->getParameter('space_description'),
                    'area_sqm' => $request->getParameter('area_sqm') ?: null,
                    'wall_length_m' => $request->getParameter('wall_length_m') ?: null,
                    'height_m' => $request->getParameter('height_m') ?: null,
                    'lighting_type' => $request->getParameter('lighting_type'),
                    'climate_controlled' => $request->getParameter('space_climate') ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('success', 'Space added');
            } elseif ($do === 'delete_space') {
                DB::table('gallery_space')->where('id', (int)$request->getParameter('space_id'))->delete();
                $this->getUser()->setFlash('success', 'Space deleted');
            }
            $this->redirect('gallery/viewVenue?id=' . $id);
        }
    }
}
