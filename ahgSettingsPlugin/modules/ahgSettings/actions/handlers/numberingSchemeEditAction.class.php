<?php

use AtomExtensions\Services\NumberingService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Numbering Scheme Edit Action
 *
 * Create/edit numbering schemes with pattern builder.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class AhgSettingsNumberingSchemeEditAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = sfContext::getInstance()->i18n;
        $this->service = NumberingService::getInstance();

        $this->schemeId = $request->getParameter('id');
        $this->scheme = null;
        $this->isNew = true;

        if ($this->schemeId) {
            $this->scheme = $this->service->getSchemeById((int) $this->schemeId);
            $this->isNew = false;
        }

        // Available sectors
        $this->sectors = [
            'archive' => 'Archive',
            'library' => 'Library',
            'museum' => 'Museum',
            'gallery' => 'Gallery',
            'dam' => 'DAM',
        ];

        // Available tokens
        $this->tokens = $this->service->getAvailableTokens();

        // Build form
        $this->form = new sfForm();
        $this->setupForm();

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();
                $this->getUser()->setFlash('notice', $this->i18n->__('Numbering scheme saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'numberingSchemes']);
            }
        }
    }

    private function setupForm(): void
    {
        // Name
        $this->form->setWidget('name', new sfWidgetFormInput([], ['class' => 'form-control']));
        $this->form->setValidator('name', new sfValidatorString(['required' => true, 'max_length' => 100]));
        $this->form->setDefault('name', $this->scheme->name ?? '');

        // Sector
        $sectorChoices = ['' => '-- Select --'] + $this->sectors;
        $this->form->setWidget('sector', new sfWidgetFormChoice(['choices' => $sectorChoices], ['class' => 'form-select']));
        $this->form->setValidator('sector', new sfValidatorChoice(['required' => true, 'choices' => array_keys($this->sectors)]));
        $this->form->setDefault('sector', $this->scheme->sector ?? '');

        // Pattern
        $this->form->setWidget('pattern', new sfWidgetFormInput([], ['class' => 'form-control', 'id' => 'pattern-input']));
        $this->form->setValidator('pattern', new sfValidatorString(['required' => true, 'max_length' => 255]));
        $this->form->setDefault('pattern', $this->scheme->pattern ?? '');

        // Description
        $this->form->setWidget('description', new sfWidgetFormTextarea([], ['class' => 'form-control', 'rows' => 2]));
        $this->form->setValidator('description', new sfValidatorString(['required' => false]));
        $this->form->setDefault('description', $this->scheme->description ?? '');

        // Current sequence
        $this->form->setWidget('current_sequence', new sfWidgetFormInput([], ['class' => 'form-control', 'type' => 'number']));
        $this->form->setValidator('current_sequence', new sfValidatorInteger(['required' => false, 'min' => 0]));
        $this->form->setDefault('current_sequence', $this->scheme->current_sequence ?? 0);

        // Sequence reset
        $resetChoices = [
            'never' => 'Never',
            'yearly' => 'Yearly (Jan 1)',
            'monthly' => 'Monthly',
        ];
        $this->form->setWidget('sequence_reset', new sfWidgetFormChoice(['choices' => $resetChoices], ['class' => 'form-select']));
        $this->form->setValidator('sequence_reset', new sfValidatorChoice(['choices' => array_keys($resetChoices)]));
        $this->form->setDefault('sequence_reset', $this->scheme->sequence_reset ?? 'never');

        // Fill gaps
        $this->form->setWidget('fill_gaps', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('fill_gaps', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('fill_gaps', $this->scheme->fill_gaps ?? false);

        // Validation regex
        $this->form->setWidget('validation_regex', new sfWidgetFormInput([], ['class' => 'form-control', 'placeholder' => 'e.g., ^LIB\d{9}$']));
        $this->form->setValidator('validation_regex', new sfValidatorString(['required' => false]));
        $this->form->setDefault('validation_regex', $this->scheme->validation_regex ?? '');

        // Allow manual override
        $this->form->setWidget('allow_manual_override', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('allow_manual_override', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('allow_manual_override', $this->scheme->allow_manual_override ?? true);

        // Is active
        $this->form->setWidget('is_active', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('is_active', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('is_active', $this->scheme->is_active ?? true);

        // Is default
        $this->form->setWidget('is_default', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('is_default', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('is_default', $this->scheme->is_default ?? false);

        // Auto-generate
        $this->form->setWidget('auto_generate', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('auto_generate', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('auto_generate', $this->scheme->auto_generate ?? true);
    }

    private function processForm(): void
    {
        $data = [
            'name' => $this->form->getValue('name'),
            'sector' => $this->form->getValue('sector'),
            'pattern' => $this->form->getValue('pattern'),
            'description' => $this->form->getValue('description'),
            'current_sequence' => (int) $this->form->getValue('current_sequence'),
            'sequence_reset' => $this->form->getValue('sequence_reset'),
            'fill_gaps' => $this->form->getValue('fill_gaps') ? 1 : 0,
            'validation_regex' => $this->form->getValue('validation_regex'),
            'allow_manual_override' => $this->form->getValue('allow_manual_override') ? 1 : 0,
            'auto_generate' => $this->form->getValue('auto_generate') ? 1 : 0,
            'is_active' => $this->form->getValue('is_active') ? 1 : 0,
            'is_default' => $this->form->getValue('is_default') ? 1 : 0,
        ];

        if ($this->isNew) {
            $this->service->createScheme($data);
        } else {
            $this->service->updateScheme((int) $this->schemeId, $data);
        }
    }
}
