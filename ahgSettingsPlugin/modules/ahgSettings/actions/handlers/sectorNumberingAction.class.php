<?php

use AtomExtensions\Services\SettingService;
use AtomExtensions\Services\CacheService;

/**
 * Sector-specific numbering settings (GLAM/DAM).
 *
 * Manages sector overrides for:
 *  - accession_mask_enabled / accession_mask / accession_counter
 *  - identifier_mask_enabled / identifier_mask / identifier_counter
 *
 * Sector overrides are stored as settings with names like:
 *   sector_museum__identifier_mask
 *
 * Empty value = inherit global setting.
 */
class AhgSettingsSectorNumberingAction extends sfAction
{
    /**
     * Sector override keys.
     * Note: Accession numbering remains global (single counter).
     * Only identifier numbering is sector-specific.
     */
    private const SECTOR_KEYS = [
        'identifier_mask_enabled',
        'identifier_mask',
        'identifier_counter',
    ];

    private const SECTOR_FIELD_PREFIX = 'sector_';
    private const SECTOR_FIELD_SEP = '__';

    /**
     * Default identifier mask patterns per sector.
     * %Y% = 4-digit year, %y% = 2-digit year, %m% = month, %d% = day
     * %04i% = zero-padded counter (4 digits), %i% = counter without padding
     */
    private const SECTOR_DEFAULTS = [
        'archive' => [
            'identifier_mask' => 'ARCH/%Y%/%04i%',
        ],
        'museum' => [
            'identifier_mask' => 'MUS.%Y%.%04i%',
        ],
        'library' => [
            'identifier_mask' => 'LIB/%Y%/%04i%',
        ],
        'gallery' => [
            'identifier_mask' => 'GAL.%Y%.%04i%',
        ],
        'dam' => [
            'identifier_mask' => 'DAM-%Y%-%06i%',
        ],
    ];

    /** @var array<string,string> */
    public $sectors = [];

    /** @var sfForm */
    public $form;

    /** @var sfI18N */
    protected $i18n;

    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = sfContext::getInstance()->i18n;
        $this->form = new sfForm();

        // Build sector list - use both property and varHolder to ensure template access
        $this->sectors = $this->getAvailableSectors();
        $this->getVarHolder()->set('sectors', $this->sectors);

        // Add sector fields to form
        $this->addSectorFieldsToForm();

        // Get global values for display
        $this->globalValues = $this->getGlobalValues();
        $this->getVarHolder()->set('globalValues', $this->globalValues);

        // Pass sector defaults to template
        $this->sectorDefaults = self::SECTOR_DEFAULTS;
        $this->getVarHolder()->set('sectorDefaults', self::SECTOR_DEFAULTS);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                CacheService::getInstance()->removePattern('settings:i18n:*');

                $this->getUser()->setFlash('notice', $this->i18n->__('Sector numbering settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'sectorNumbering']);
            }
        }
    }

    /**
     * Get global identifier values for reference display.
     */
    private function getGlobalValues(): array
    {
        $values = [];
        foreach (self::SECTOR_KEYS as $key) {
            $setting = SettingService::getByName($key);
            $values[$key] = $setting ? $setting->getValue(['sourceCulture' => true]) : '';
        }
        return $values;
    }

    /**
     * Adds all sector-specific override fields to the form.
     */
    private function addSectorFieldsToForm(): void
    {
        foreach (array_keys($this->sectors) as $sector) {
            foreach (self::SECTOR_KEYS as $baseKey) {
                $this->addSectorField($sector, $baseKey);
            }
        }
    }

    /**
     * Adds a single sector override field.
     */
    private function addSectorField(string $sector, string $baseKey): void
    {
        $fieldName = $this->makeSectorFieldName($sector, $baseKey);
        $settingName = $this->makeSectorSettingName($sector, $baseKey);

        $existing = SettingService::getByName($settingName);
        $default = $existing ? $existing->getValue(['sourceCulture' => true]) : '';

        // Enabled flags: Inherit / No / Yes
        if (in_array($baseKey, ['accession_mask_enabled', 'identifier_mask_enabled'], true)) {
            $choices = [
                '' => $this->i18n->__('Inherit (global)'),
                '0' => $this->i18n->__('No'),
                '1' => $this->i18n->__('Yes'),
            ];

            $this->form->setDefault($fieldName, (string) $default);
            $this->form->setValidator($fieldName, new sfValidatorChoice([
                'required' => false,
                'choices' => array_keys($choices),
            ]));
            $this->form->setWidget($fieldName, new sfWidgetFormChoice([
                'choices' => $choices,
                'expanded' => true,
            ], ['class' => 'radio']));
            return;
        }

        // Masks and counters: text input, empty = inherit
        $this->form->setDefault($fieldName, (string) $default);
        $this->form->setValidator($fieldName, new sfValidatorString(['required' => false]));

        // Get placeholder from sector defaults
        $placeholder = self::SECTOR_DEFAULTS[$sector][$baseKey] ?? '';

        $this->form->setWidget($fieldName, new sfWidgetFormInput([
            'default' => (string) $default,
        ], [
            'placeholder' => $placeholder ?: null,
            'class' => 'form-control',
        ]));
    }

    /**
     * Process and save form values.
     */
    private function processForm(): void
    {
        foreach (array_keys($this->sectors) as $sector) {
            foreach (self::SECTOR_KEYS as $baseKey) {
                $fieldName = $this->makeSectorFieldName($sector, $baseKey);
                $settingName = $this->makeSectorSettingName($sector, $baseKey);
                $value = $this->form->getValue($fieldName);

                // Empty = inherit global: remove sector setting if exists
                if ($value === '' || $value === null) {
                    if (null !== $existing = SettingService::getByName($settingName)) {
                        if (method_exists($existing, 'delete')) {
                            $existing->delete();
                        }
                    }
                    continue;
                }

                // Save sector override
                if (null === $setting = SettingService::getByName($settingName)) {
                    $setting = new QubitSetting();
                    $setting->name = $settingName;
                }

                $setting->setValue((string) $value, ['sourceCulture' => true]);
                $setting->save();
            }
        }
    }

    /**
     * Get all GLAM/DAM sectors for numbering configuration.
     * Always returns all sectors - numbering schemes can be pre-configured
     * even if the sector plugin isn't currently enabled.
     */
    private function getAvailableSectors(): array
    {
        return [
            'archive' => 'Archive',
            'museum' => 'Museum',
            'library' => 'Library',
            'gallery' => 'Gallery',
            'dam' => 'DAM',
        ];
    }

    private function makeSectorFieldName(string $sector, string $baseKey): string
    {
        return self::SECTOR_FIELD_PREFIX . $sector . self::SECTOR_FIELD_SEP . $baseKey;
    }

    private function makeSectorSettingName(string $sector, string $baseKey): string
    {
        return $this->makeSectorFieldName($sector, $baseKey);
    }
}
