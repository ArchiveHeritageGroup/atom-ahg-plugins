<?php

use AtomExtensions\Services\NumberingService;

/**
 * Identifier Generator Component
 *
 * Provides auto-generated identifier preview for add/edit forms.
 * Include in templates with: include_component('informationobject', 'identifierGenerator', ['sector' => 'museum'])
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class InformationobjectIdentifierGeneratorComponent extends sfComponent
{
    public function execute($request)
    {
        // Get sector from parameter or detect from context
        $this->sector = $this->getVar('sector', 'archive');
        $this->repositoryId = $this->getVar('repository_id');
        $this->currentIdentifier = $this->getVar('current_identifier', '');
        $this->fieldName = $this->getVar('field_name', 'identifier');

        // Build context from available data
        $context = [];
        if ($this->getVar('repository_code')) {
            $context['repo'] = $this->getVar('repository_code');
        }
        if ($this->getVar('fonds_code')) {
            $context['fonds'] = $this->getVar('fonds_code');
        }
        if ($this->getVar('series_code')) {
            $context['series'] = $this->getVar('series_code');
        }
        if ($this->getVar('department')) {
            $context['dept'] = $this->getVar('department');
        }
        if ($this->getVar('media_type')) {
            $context['type'] = $this->getVar('media_type');
        }

        // Get numbering info
        try {
            $service = NumberingService::getInstance();
            $this->numberingInfo = $service->getNumberingInfo($this->sector, $context, $this->repositoryId);
        } catch (\Exception $e) {
            $this->numberingInfo = [
                'enabled' => false,
                'auto_generate' => false,
                'allow_override' => true,
                'next_reference' => null,
                'pattern' => null,
                'scheme_name' => null,
            ];
        }
    }
}
