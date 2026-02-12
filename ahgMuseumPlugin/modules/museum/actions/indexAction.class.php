<?php
/**
 * ahgMuseumPlugin Index Action
 *
 * Displays museum object view page using QubitInformationObject.
 * Uses Laravel Query Builder only for custom CCO/GRAP data.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

// Load AhgAccessGate for embargo checks
require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Access/AhgAccessGate.php';

class museumIndexAction extends AhgController
{
    public function execute($request)
    {
        // Get resource from route (returns QubitInformationObject)
        $this->resource = $this->getRoute()->resource;

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // Check user authorization using Qubit ACL
        if (!($this->getUser()->isAuthenticated() || QubitAcl::check($this->resource, 'read'))) {
            $this->forward("admin", "secure");
        }
        
        // Check embargo access - blocks full embargo for public users
        if (!\AhgCore\Access\AhgAccessGate::canView($this->resource->id, $this)) {
            return sfView::NONE;
        }

        // Log the access
        $this->dispatcher->notify(new sfEvent($this, 'access_log.view', ['object' => $this->resource]));

        // Get scope and content for meta description
        $scopeAndContent = $this->resource->getScopeAndContent(['cultureFallback' => true]);
        if (!empty($scopeAndContent)) {
            $this->getContext()->getConfiguration()->loadHelpers(['Text', 'Qubit']);
            $this->response->addMeta('description', truncate_text(strip_markdown($scopeAndContent), 150));
        }

        // Get digital object link
        $this->digitalObjectLink = $this->resource->getDigitalObjectUrl();

        // Initialize Laravel for custom data
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        // Load CCO/Museum specific data
        $this->loadMuseumData();
        // Load item physical location
        \AhgCore\Core\AhgDb::init();
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];

        // Load GRAP data
        $this->grapData = $this->getGrapData($this->resource->id);

        // Creator history labels for component
        $this->creatorHistoryLabels = [
            'creator' => 'Creator',
            'history' => 'Administrative history / Biographical sketch',
        ];
    }

    /**
     * Load CCO/Museum specific data from museum_metadata table
     */
    protected function loadMuseumData(): void
    {
        // Get CCO template property (for backward compatibility)
        $templateProp = $this->resource->getPropertyByName('ccoTemplate');
        $this->templateId = $templateProp ? $templateProp->getValue(['sourceCulture' => true]) : null;

        // Load from museum_metadata table using Laravel
        $museumRecord = DB::table('museum_metadata')
            ->where('object_id', $this->resource->id)
            ->first();

        if ($museumRecord) {
            $data = (array) $museumRecord;
            
            // Map all database fields to template
            $this->museumData = [
                'hasMuseumData' => true,

                // Object Identification
                'object_number' => $this->resource->identifier ?? null,
                'work_type' => $data['work_type'] ?? null,
                'object_type' => $data['object_type'] ?? null,
                'classification' => $data['classification'] ?? null,
                'object_class' => $data['object_class'] ?? null,
                'object_category' => $data['object_category'] ?? null,
                'object_sub_category' => $data['object_sub_category'] ?? null,

                // Creator Information
                'creator_display' => $data['creator_identity'] ?? null,
                'creator_identity' => $data['creator_identity'] ?? null,
                'creator_role' => $data['creator_role'] ?? null,
                'creator_extent' => $data['creator_extent'] ?? null,
                'creator_qualifier' => $data['creator_qualifier'] ?? null,
                'attribution_qualifier' => $data['creator_attribution'] ?? null,
                'creator_attribution' => $data['creator_attribution'] ?? null,

                // Creation Context
                'creation_date_display' => $data['creation_date_display'] ?? null,
                'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
                'creation_date_latest' => $data['creation_date_latest'] ?? null,
                'creation_date_qualifier' => $data['creation_date_qualifier'] ?? null,
                'creation_place' => $data['creation_place'] ?? null,
                'creation_place_type' => $data['creation_place_type'] ?? null,
                'culture' => $data['cultural_context'] ?? null,
                'cultural_context' => $data['cultural_context'] ?? null,
                'cultural_group' => $data['cultural_group'] ?? null,
                'style' => $data['style'] ?? null,
                'period' => $data['period'] ?? null,
                'style_period' => $data['style_period'] ?? null,
                'movement' => $data['movement'] ?? null,
                'school' => $data['school'] ?? null,
                'dynasty' => $data['dynasty'] ?? null,
                'discovery_place' => $data['discovery_place'] ?? null,
                'discovery_place_type' => $data['discovery_place_type'] ?? null,

                // Physical Description
                'dimensions' => $data['dimensions'] ?? null,
                'dimensions_display' => $data['dimensions'] ?? null,
                'measurements' => $data['measurements'] ?? null,
                'materials' => $this->parseJsonField($data['materials'] ?? null),
                'materials_display' => $this->parseJsonField($data['materials'] ?? null),
                'techniques' => $this->parseJsonField($data['techniques'] ?? null),
                'technique_cco' => $data['technique_cco'] ?? null,
                'technique_qualifier' => $data['technique_qualifier'] ?? null,
                'color' => $data['color'] ?? null,
                'shape' => $data['shape'] ?? null,
                'orientation' => $data['orientation'] ?? null,
                'physical_appearance' => $data['physical_appearance'] ?? null,
                'facture_description' => $data['facture_description'] ?? null,

                // Edition / State
                'edition_description' => $data['edition_description'] ?? null,
                'edition_number' => $data['edition_number'] ?? null,
                'edition_size' => $data['edition_size'] ?? null,
                'state_description' => $data['state_description'] ?? null,
                'state_identification' => $data['state_identification'] ?? null,

                // Subject & Content
                'subject_indexing_type' => $data['subject_indexing_type'] ?? null,
                'subject_display' => $data['subject_display'] ?? null,
                'subject_extent' => $data['subject_extent'] ?? null,
                'historical_context' => $data['historical_context'] ?? null,
                'architectural_context' => $data['architectural_context'] ?? null,
                'archaeological_context' => $data['archaeological_context'] ?? null,

                // Inscriptions & Marks
                'inscriptions' => $data['inscriptions'] ?? $data['inscription'] ?? null,
                'inscription' => $data['inscription'] ?? null,
                'inscription_transcription' => $data['inscription_transcription'] ?? null,
                'inscription_type' => $data['inscription_type'] ?? null,
                'inscription_location' => $data['inscription_location'] ?? null,
                'inscription_language' => $data['inscription_language'] ?? null,
                'inscription_translation' => $data['inscription_translation'] ?? null,
                'mark_type' => $data['mark_type'] ?? null,
                'mark_description' => $data['mark_description'] ?? null,
                'mark_location' => $data['mark_location'] ?? null,

                // Condition & Treatment
                'condition_term' => $data['condition_term'] ?? null,
                'condition_summary' => $data['condition_term'] ?? null,
                'condition_description' => $data['condition_description'] ?? null,
                'condition_date' => $data['condition_date'] ?? null,
                'condition_agent' => $data['condition_agent'] ?? null,
                'condition_notes' => $data['condition_notes'] ?? null,
                'treatment_type' => $data['treatment_type'] ?? null,
                'treatment_description' => $data['treatment_description'] ?? null,
                'treatment_date' => $data['treatment_date'] ?? null,
                'treatment_agent' => $data['treatment_agent'] ?? null,

                // Provenance & Location
                'provenance' => $data['provenance'] ?? null,
                'provenance_text' => $data['provenance_text'] ?? null,
                'ownership_history' => $data['ownership_history'] ?? null,
                'current_location' => $data['current_location'] ?? null,
                'current_location_repository' => $data['current_location_repository'] ?? null,
                'current_location_geography' => $data['current_location_geography'] ?? null,
                'current_location_coordinates' => $data['current_location_coordinates'] ?? null,
                'current_location_ref_number' => $data['current_location_ref_number'] ?? null,

                // Rights
                'legal_status' => $data['legal_status'] ?? null,
                'rights_type' => $data['rights_type'] ?? null,
                'rights_holder' => $data['rights_holder'] ?? null,
                'rights_date' => $data['rights_date'] ?? null,
                'rights_remarks' => $data['rights_remarks'] ?? null,

                // Related Works
                'related_work_type' => $data['related_work_type'] ?? null,
                'related_work_relationship' => $data['related_work_relationship'] ?? null,
                'related_work_label' => $data['related_work_label'] ?? null,
                'related_work_id' => $data['related_work_id'] ?? null,

                // Cataloging
                'cataloger_name' => $data['cataloger_name'] ?? null,
                'cataloging_date' => $data['cataloging_date'] ?? null,
                'cataloging_institution' => $data['cataloging_institution'] ?? null,
                'cataloging_remarks' => $data['cataloging_remarks'] ?? null,
                'record_type' => $data['record_type'] ?? null,
                'record_level' => $data['record_level'] ?? null,
            ];
        } else {
            // Fallback to ccoData property
            $dataProp = $this->resource->getPropertyByName('ccoData');
            $this->ccoData = $dataProp ? json_decode($dataProp->getValue(['sourceCulture' => true]), true) : [];
            $this->museumData = $this->ccoData;
            $this->museumData['hasMuseumData'] = !empty($this->ccoData);
        }
    }
    
    /**
     * Parse JSON field or return as string
     */
    protected function parseJsonField($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return implode(', ', array_filter($decoded));
            }
        }
        return $value;
    }

    /**
     * Get GRAP data for resource using Laravel
     */
    protected function getGrapData(int $objectId): array
    {
        try {
            $grap = DB::table('grap_heritage_asset')
                ->where('object_id', $objectId)
                ->first();

            if (!$grap) {
                return [];
            }

            return (array) $grap;
        } catch (\Exception $e) {
            return [];
        }
    }
}
