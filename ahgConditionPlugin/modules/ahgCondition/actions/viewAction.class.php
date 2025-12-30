<?php

/**
 * View Condition Check with Template Data
 */
class ahgConditionViewAction extends sfAction
{
    public function execute($request)
    {
        $checkId = $request->getParameter('id');
        
        if (!$checkId) {
            $this->forward404('Condition check ID required');
        }
        
        // Load services
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/ConditionTemplateService.php';
        $templateService = new \AtoM\Framework\Services\ConditionTemplateService();
        
        // Get condition check
        $this->check = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check as c')
            ->leftJoin('slug as s', 's.object_id', '=', 'c.object_id')
            ->leftJoin('information_object_i18n as i', function($join) {
                $join->on('i.id', '=', 'c.object_id')
                     ->where('i.culture', '=', 'en');
            })
            ->where('c.id', $checkId)
            ->select('c.*', 's.slug', 'i.title as object_title')
            ->first();
        
        if (!$this->check) {
            $this->forward404('Condition check not found');
        }
        
        // Get template and field data
        $this->template = null;
        $this->templateData = [];
        $this->fieldsBySection = [];
        
        if ($this->check->template_id) {
            $this->template = $templateService->getTemplate($this->check->template_id);
            $this->templateData = $templateService->getCheckData($checkId);
            
            // Organize data by section for display
            if ($this->template) {
                foreach ($this->template->sections as $section) {
                    $this->fieldsBySection[$section->id] = [
                        'section' => $section,
                        'fields' => []
                    ];
                    foreach ($section->fields as $field) {
                        $value = $this->templateData[$field->id] ?? $this->templateData[$field->field_name] ?? null;
                        $this->fieldsBySection[$section->id]['fields'][] = [
                            'field' => $field,
                            'value' => $value
                        ];
                    }
                }
            }
        }
        
        // Get photos
        $this->photos = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo')
            ->where('condition_check_id', $checkId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
        
        // Get checked by user name
        $this->checkedByUser = null;
        if ($this->check->created_by) {
            $this->checkedByUser = \Illuminate\Database\Capsule\Manager::table('user')
                ->where('id', $this->check->created_by)
                ->value('username');
        }
    }
}
