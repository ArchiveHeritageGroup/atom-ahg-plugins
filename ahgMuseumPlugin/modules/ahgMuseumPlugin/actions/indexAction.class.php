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

use Illuminate\Database\Capsule\Manager as DB;

class ahgMuseumPluginIndexAction extends sfAction
{
    public function execute($request)
    {
        // Get resource from route (returns QubitInformationObject)
        $this->resource = $this->getRoute()->resource;
        error_log("MUSEUM DEBUG: resource = " . ($this->resource ? $this->resource->id : "NULL"));

        // Check that this isn't the root
        error_log("MUSEUM DEBUG: parent_id = " . ($this->resource->parent_id ?? "NULL") . ", parent = " . (isset($this->resource->parent) ? "SET" : "NOT SET"));
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // Check user authorization using Qubit ACL
        if (!($this->getUser()->isAuthenticated() || QubitAcl::check($this->resource, 'read'))) {
            $this->forward("admin", "secure");
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
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
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
            
            // Map database fields to template expected fields
            $this->museumData = [
                'hasMuseumData' => true,
                // Basic identification
                'work_type' => $data['work_type'] ?? null,
                'object_type' => $data['object_type'] ?? null,
                'classification' => $data['classification'] ?? null,
                'object_number' => $this->resource->identifier ?? null,
                
                // Creator information
                'creator_display' => $data['creator_identity'] ?? null,
                'creator_identity' => $data['creator_identity'] ?? null,
                'creator_role' => $data['creator_role'] ?? null,
                'attribution_qualifier' => $data['creator_attribution'] ?? null,
                'creator_attribution' => $data['creator_attribution'] ?? null,
                'school' => $data['school'] ?? null,
                
                // Creation details
                'creation_date_display' => $data['creation_date_display'] ?? null,
                'creation_date_earliest' => $data['creation_date_earliest'] ?? null,
                'creation_date_latest' => $data['creation_date_latest'] ?? null,
                'creation_place' => $data['creation_place'] ?? null,
                
                // Physical description
                'materials' => $this->parseJsonField($data['materials'] ?? null),
                'materials_display' => $this->parseJsonField($data['materials'] ?? null),
                'techniques' => $this->parseJsonField($data['techniques'] ?? null),
                'dimensions' => $data['dimensions'] ?? null,
                'dimensions_display' => $data['dimensions'] ?? $data['measurements'] ?? null,
                'measurements' => $data['measurements'] ?? null,
                'color' => $data['color'] ?? null,
                'shape' => $data['shape'] ?? null,
                'orientation' => $data['orientation'] ?? null,
                
                // Style and period
                'style' => $data['style'] ?? null,
                'period' => $data['period'] ?? null,
                'style_period' => $data['style_period'] ?? null,
                'movement' => $data['movement'] ?? null,
                'cultural_context' => $data['cultural_context'] ?? null,
                'culture' => $data['cultural_context'] ?? null,
                
                // Subject
                'subject_display' => $data['subject_display'] ?? null,
                'subjects_depicted' => $data['subject_display'] ?? null,
                
                // Inscriptions and marks
                'inscriptions' => $data['inscriptions'] ?? $data['inscription'] ?? null,
                'inscription' => $data['inscription'] ?? null,
                'inscription_transcription' => $data['inscription_transcription'] ?? null,
                'inscription_type' => $data['inscription_type'] ?? null,
                'inscription_location' => $data['inscription_location'] ?? null,
                'mark_type' => $data['mark_type'] ?? null,
                'mark_description' => $data['mark_description'] ?? null,
                
                // Condition
                'condition_term' => $data['condition_term'] ?? null,
                'condition_summary' => $data['condition_description'] ?? null,
                'condition_description' => $data['condition_description'] ?? null,
                'condition_date' => $data['condition_date'] ?? null,
                'condition_agent' => $data['condition_agent'] ?? null,
                
                // Treatment
                'treatment_type' => $data['treatment_type'] ?? null,
                'treatment_date' => $data['treatment_date'] ?? null,
                'treatment_agent' => $data['treatment_agent'] ?? null,
                'treatment_description' => $data['treatment_description'] ?? null,
                
                // Provenance and ownership
                'provenance' => $data['provenance'] ?? $data['provenance_text'] ?? null,
                'provenance_text' => $data['provenance_text'] ?? null,
                'ownership_history' => $data['ownership_history'] ?? null,
                'legal_status' => $data['legal_status'] ?? null,
                
                // Rights
                'rights_type' => $data['rights_type'] ?? null,
                'rights_holder' => $data['rights_holder'] ?? null,
                'rights_date' => $data['rights_date'] ?? null,
                'rights_remarks' => $data['rights_remarks'] ?? null,
                
                // Location
                'current_location' => $data['current_location'] ?? $data['current_location_repository'] ?? null,
                'current_location_repository' => $data['current_location_repository'] ?? null,
                'current_location_geography' => $data['current_location_geography'] ?? null,
                
                // Cataloging
                'cataloger_name' => $data['cataloger_name'] ?? null,
                'cataloging_date' => $data['cataloging_date'] ?? null,
                'cataloging_institution' => $data['cataloging_institution'] ?? null,
                
                // Related works
                'related_work_type' => $data['related_work_type'] ?? null,
                'related_work_relationship' => $data['related_work_relationship'] ?? null,
                'related_work_label' => $data['related_work_label'] ?? null,
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
        $grap = DB::table('grap_heritage_asset')
            ->where('object_id', $objectId)
            ->first();

        if (!$grap) {
            return [];
        }

        return (array) $grap;
    }
}
