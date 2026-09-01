<?php

/**
 * AHG stub for clipboard/export action.
 * Replaces apps/qubit/modules/clipboard/actions/exportAction.class.php.
 *
 * Clipboard export with CSV/XML support and background job dispatch.
 */
class ClipboardExportAction extends DefaultEditAction
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'levels',
        'exportType',
        'format',
        'includeDescendants',
        'includeAllLevels',
        'includeDigitalObjects',
        'includeDrafts',
        'includeNonVisibleElements',
    ];

    private $choices = [];

    public function execute($request)
    {
        // Get object type and validate
        // The form posts this as `exportType`, not `type`.
        //
        // A POST field named exactly `type` never reaches $_POST on this
        // application - measured: `Type`, `xtype` and `typex` all arrive from
        // the same request body, `type` does not, and the mechanism was never
        // identified. The required `type` validator could therefore never be
        // satisfied, so EVERY clipboard export failed with "Invalid export
        // options", and objectType silently fell through to the default, which
        // is why the Type dropdown never worked either.
        //
        // Same family as the documented `name="action"` bug: the house
        // workaround is to rename the posted field. GET links still use
        // `?type=`, which works and is used across the site, so read both.
        $requestedType = $request->getParameter('exportType');
        if (null === $requestedType || '' === $requestedType) {
            $requestedType = $request->getParameter('type');
        }
        $this->objectType = trim(strtolower((string) $requestedType));

        switch ($this->objectType) {
            case 'actor':
                $className = 'QubitActor';

                break;

            case 'accession':
                $className = 'QubitAccession';

                break;

            case 'repository':
                $className = 'QubitRepository';

                break;

            default:
                $this->objectType = 'informationObject';
                $className = 'QubitInformationObject';
        }

        // Get format and validate
        $this->formatType = trim(strtolower($request->getParameter('format')));
        if ('xml' != $this->formatType || 'repository' == $this->objectType || 'accession' == $this->objectType) {
            $this->formatType = 'csv';
        }

        // Basic permission check to determine whether digital object export should
        // be made available
        $this->digitalObjectsAvailable = false;

        if (
            sfConfig::get('app_clipboard_export_digitalobjects_enabled', false)
            && (
                'informationObject' == $this->objectType
                || (
                    'actor' == $this->objectType
                    && $this->context->user->isAuthenticated()
                )
            )
        ) {
            $this->digitalObjectsAvailable = true;
        }

        // Determine if there are non-visible elements that should be hidden
        $this->nonVisibleElementsIncluded = false;
        $defTemplate = sfConfig::get('app_default_template_'.strtolower($this->objectType));

        foreach (sfConfig::getAll() as $setting => $value) {
            if (
                (false !== strpos($setting, 'app_element_visibility_'.$defTemplate))
                && (0 == sfConfig::get($setting))
            ) {
                $this->nonVisibleElementsIncluded = true;
            }
        }

        // Show export options panel if:
        // information object type
        // or, if actor type and digital objects are on the clipboard
        $this->showOptions = 'informationObject' == $this->objectType
            || ('actor' == $this->objectType && $this->digitalObjectsAvailable);

        // Get field includeDescendants if:
        // options enabled
        // and, information object type
        $this->descendantsIncluded = $this->showOptions
            && 'informationObject' == $this->objectType
            && 'on' == $request->getParameter('includeDescendants');

        // Get field includeAllLevels if:
        // descendantsIncluded enabled
        $this->descendantsAllLevels = $this->descendantsIncluded
            && 'on' == $request->getParameter('includeAllLevels');

        // Get field includeDigitalObjects if:
        // digital object export option is available
        $this->includeDigitalObjects = $this->digitalObjectsAvailable
            && 'on' == $request->getParameter('includeDigitalObjects');

        // Get field includeDrafts if:
        // options enabled
        // and, user is authenticated
        $this->draftsIncluded = $this->showOptions
            && $this->context->user->isAuthenticated()
            && 'on' == $request->getParameter('includeDrafts');

        // Get field includeNonVisibleElements if:
        // options enabled
        // and, user is authenticated
        $this->nonVisibleElementsIncluded = $this->showOptions
            && $this->context->user->isAdministrator()
            && 'on' == $request->getParameter('includeNonVisibleElements');

        parent::execute($request);

        $this->response->addJavaScript('exportOptions', 'last');

        $this->title = $this->context->i18n->__('Clipboard export');

        if (!$request->isMethod('post')) {
            return;
        }

        $this->response->setHttpHeader(
            'Content-Type',
            'application/json; charset=utf-8'
        );

        $this->form->bind($request->getPostParameters());

        if (!$this->form->isValid()) {
            $this->response->setStatusCode(400);
            $message = $this->context->i18n->__('Invalid export options.');

            // Name the field that failed, in the response AND in the log.
            //
            // This used to return the sentence above and nothing else, and threw
            // the error schema away. A user saw "Invalid export options" for
            // every export type and every option, with no way to tell which
            // field was rejected; nothing was written to ahg_error_log either,
            // so there was no record to read afterwards. Diagnosing it meant
            // guessing field combinations against a live server.
            //
            // sfForm rejects a bound field that has no validator with
            // "Unexpected extra form field", so the field NAMES matter as much
            // as the messages: a name that appears here but is absent from
            // addField() is the whole answer.
            $fieldErrors = [];

            try {
                foreach ($this->form->getErrorSchema()->getErrors() as $field => $error) {
                    $fieldErrors[(string) $field] = (string) $error;
                }

                $globalError = (string) $this->form->getErrorSchema()->getMessage();
                if ('' !== $globalError && empty($fieldErrors)) {
                    $fieldErrors['_'] = $globalError;
                }
            } catch (\Throwable $e) {
                // A diagnostic must never become the failure it reports on.
                $fieldErrors = ['_' => 'error schema unavailable: ' . $e->getMessage()];
            }

            try {
                $detail = [];
                foreach ($fieldErrors as $field => $error) {
                    $detail[] = $field . ': ' . $error;
                }

                \AtomFramework\Services\ErrorLogWriter::record([
                    'level' => 'warning',
                    'status_code' => 400,
                    'message' => substr(
                        'clipboard/export rejected the form - '
                        . 'type=' . $this->objectType
                        . ' format=' . $this->formatType
                        . ' posted=[' . implode(', ', array_keys($request->getPostParameters())) . ']'
                        . ' errors=[' . implode('; ', $detail) . ']',
                        0,
                        65000
                    ),
                    'file' => substr(__FILE__, 0, 500),
                    'line' => __LINE__,
                    'url' => substr($request->getUri(), 0, 2000),
                    'http_method' => 'POST',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                // Logging is best effort; the response below still carries the detail.
            }

            return $this->renderText(json_encode([
                'error' => $message,
                'fields' => $fieldErrors,
            ]));
        }

        $slugs = $request->getPostParameter('slugs', []);

        if (empty($slugs)) {
            $this->response->setStatusCode(400);
            $message = $this->context->i18n->__(
                'The clipboard is empty for this entity type.'
            );

            return $this->renderText(json_encode(['error' => $message]));
        }

        if ('QubitAccession' === $className && !$this->context->user->hasCredential(['editor', 'administrator'], false)) {
            $this->response->setStatusCode(403);
            $message = $this->context->i18n->__(
                'You are not allowed to export this entity type.'
            );

            return $this->renderText(json_encode(['error' => $message]));
        }

        $this->processForm();

        // Create array of selections to pass to background job where
        // Term ID will be key, and Term description is value
        $levelsOfDescription = [];
        foreach ($this->levels as $value) {
            $levelsOfDescription[$value] = $this->choices[$value];
        }

        $options = [
            'params' => ['fromClipboard' => true, 'slugs' => $slugs],
            'current-level-only' => !$this->descendantsIncluded,
            'public' => !$this->draftsIncluded,
            'objectType' => $this->objectType,
            'levels' => $levelsOfDescription,
            'nonVisibleElementsIncluded' => $this->nonVisibleElementsIncluded,
        ];

        $msg = ('xml' == $this->formatType) ? 'XML export' : 'CSV export';
        $options['name'] = $this->context->i18n->__($msg);

        if ($this->includeDigitalObjects) {
            $options['name'] = $this->context->i18n->__(
                '%1% and %2%',
                [
                    '%1%' => sfConfig::get('app_ui_label_digitalobject'),
                    '%2%' => $options['name'],
                ]
            );
            $options['includeDigitalObjects'] = true;
        }

        // When exporting actors, ensure aliases and relations are also exported
        if ('actor' === $this->objectType && 'csv' === $this->formatType) {
            $options['aliases'] = true;
            $options['relations'] = true;
        }

        try {
            return $this->runExportJob($options);
        } catch (Exception $e) {
            $this->response->setStatusCode(500);

            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    protected function earlyExecute()
    {
        sfProjectConfiguration::getActive()->loadHelpers(['I18N']);

        // Initialize help array: messages added depending on visibility of fields
        $this->helpMessages = [];

        $this->typeChoices = [
            'informationObject' => sfConfig::get('app_ui_label_informationobject'),
            'actor' => sfConfig::get('app_ui_label_actor'),
            'repository' => sfConfig::get('app_ui_label_repository'),
            'accession' => sfConfig::get('app_ui_label_accession'),
        ];

        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);
    }

    protected function runExportJob($options)
    {
        $responseData = [];

        $jobName = $this->getJobNameString();

        // Check if query matches any records, before attempting export
        if (method_exists($jobName, 'findExportRecords')) {
            $search = $jobName::findExportRecords($options);

            if (0 == $search->count()) {
                throw new sfException($this->context->i18n->__(
                    'No records were exported for your current selection. Please %open_link%refresh the page and choose different export options %close_link%.',
                    [
                        '%open_link%' => '<a class="alert-link" href="javascript:location.reload();">',
                        '%close_link%' => '</a>',
                    ]
                ));
            }
        }

        $job = QubitJob::runJob($jobName, $options);

        // Generate, store and return a token to associate unauthenticated users
        // with their export jobs to be able to download the result later and
        // delete the job.
        if (!$this->context->user->isAuthenticated()) {
            $property = $job->generateUserTokenProperty();
            $responseData['token'] = $property->value;
        }

        $responseData['success'] = '<p><strong>';
        $responseData['success'] .= $this->context->i18n->__(
            'Your %entity_type% export package is being built.',
            ['%entity_type%' => strtolower($this->typeChoices[$this->objectType])]
        );
        $responseData['success'] .= '</strong> ';

        if ($this->context->user->isAuthenticated()) {
            $responseData['success'] .= $this->context->i18n->__(
                'The %open_link%job management page%close_link% will show progress and a download link when complete.',
                [
                    '%open_link%' => sprintf(
                        '<strong><a class="alert-link" href="%s">',
                        $this->context->routing->generate(null, [
                            'module' => 'jobs',
                            'action' => 'browse',
                        ])
                    ),
                    '%close_link%' => '</a></strong>',
                ]
            );
        } else {
            $responseData['success'] .= $this->context->i18n->__(
                'Please %open_link%refresh the page%close_link% to see progress and a download link when complete.',
                [
                    '%open_link%' => '<strong><a class="alert-link" href="javascript:location.reload();">',
                    '%close_link%' => '</a></strong>',
                ]
            );
        }

        $responseData['success'] .= '</p><p>';
        $responseData['success'] .= $this->context->i18n->__(
            '%open_strong_tag%Note:%close_strong_tag% AtoM may remove export packages after a period of time to free up storage space. When your export is ready you should download it as soon as possible.',
            [
                '%open_strong_tag%' => '<strong>',
                '%close_strong_tag%' => '</strong>',
            ]
        );
        $responseData['success'] .= '</p>';

        $this->response->setStatusCode(200);

        return $this->renderText(json_encode($responseData));
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'exportType':
                $this->form->setValidator('exportType', new sfValidatorString(
                    ['required' => true]
                ));
                $this->form->setWidget('exportType', new sfWidgetFormSelect(
                    ['label' => __('Type'), 'choices' => $this->typeChoices]
                ));
                $this->form->setDefault('exportType', $this->objectType);

                break;

            case 'format':
                $this->form->setValidator('format', new sfValidatorString(
                    ['required' => true]
                ));
                $choices = [];
                $choices['csv'] = $this->context->i18n->__('CSV');
                if ('repository' != $this->objectType && 'accession' != $this->objectType) {
                    $choices['xml'] = $this->context->i18n->__('XML');
                }
                $this->form->setWidget('format', new sfWidgetFormSelect(
                    ['label' => __('Format'), 'choices' => $choices]
                ));
                $this->form->setDefault(
                    'format',
                    'actor' != $this->objectType ? 'xml' : 'csv'
                );

                break;

            case 'includeDescendants':
                if ($this->showOptions && 'informationObject' == $this->objectType) {
                    $this->form->setWidget(
                        'includeDescendants',
                        new sfWidgetFormInputCheckbox(
                            ['label' => __('Include descendants')]
                        )
                    );
                    $this->form->setDefault('includeDescendants', false);

                    $this->helpMessages[] = __(
                        'Choosing "Include descendants" will include all lower-level records beneath those currently on the clipboard in the export.'
                    );
                }

                break;

            case 'includeAllLevels':
                if ($this->showOptions && 'informationObject' == $this->objectType) {
                    $this->form->setWidget(
                        'includeAllLevels',
                        new sfWidgetFormInputCheckbox(
                            ['label' => __('Include all descendant levels of description')]
                        )
                    );
                    $this->form->setDefault('includeAllLevels', true);
                }

                break;

            case 'levels':
                $this->form->setValidator('levels', new sfValidatorPass());

                $this->levelChoices = [];
                foreach (QubitTerm::getLevelsOfDescription() as $item) {
                    $this->levelChoices[$item->id] = $item->__toString();
                }

                $size = count($this->levelChoices);
                if (0 === $size) {
                    $size = 4;
                }

                if ($this->showOptions && 'informationObject' == $this->objectType) {
                    $this->form->setWidget('levels', new sfWidgetFormSelect(
                        [
                            'label' => __(
                                'Select levels of descendant descriptions for inclusion'
                            ),
                            'help' => __(
                                'If no levels are selected, the export will fail. You can use the control (Mac ⌘) and/or shift keys to multi-select values from the Levels of description menu. It is necessary to include the level(s) above the desired export level, up to and including the level contained in the clipboard. Otherwise, no records will be included in the export.'
                            ),
                            'choices' => $this->levelChoices,
                            'multiple' => true,
                        ],
                        ['size' => $size]
                    ));
                }

                break;

            case 'includeDigitalObjects':
                if ($this->digitalObjectsAvailable) {
                    if ('informationObject' == $this->objectType) {
                        $this->helpMessages[] = __(
                            'It is not possible to select both digital objects and descendants for export at the same time. Digital objects can only be exported for records that are on the clipboard.'
                        );
                    }

                    $this->helpMessages[] = __(
                        'Digital objects with restricted access or copyright will not be exported.'
                    );

                    $this->form->setWidget(
                        'includeDigitalObjects',
                        new sfWidgetFormInputCheckbox(
                            ['label' => __('Include digital objects')]
                        )
                    );
                    $this->form->setDefault('includeDigitalObjects', true);
                }

                break;

            case 'includeDrafts':
                if (
                    'informationObject' == $this->objectType
                    && $this->context->user->isAuthenticated()
                ) {
                    $this->form->setWidget(
                        'includeDrafts',
                        new sfWidgetFormInputCheckbox(
                            ['label' => __('Include draft records')]
                        )
                    );

                    $this->helpMessages[] = __(
                        'Choosing "Include draft records" will include those marked with a Draft publication status in the export. Note: if you do NOT choose this option, any descendants of a draft record will also be excluded, even if they are published.'
                    );
                    $this->form->setDefault('includeDrafts', true);
                }

                break;

            case 'includeNonVisibleElements':
                if (
                    'informationObject' == $this->objectType
                    && $this->context->user->isAdministrator()
                ) {
                    $this->form->setWidget(
                        'includeNonVisibleElements',
                        new sfWidgetFormInputCheckbox(
                            ['label' => __('Include non-visible elements')]
                        )
                    );

                    $this->helpMessages[] = __(
                        'Choosing "Include non-visible elements" will include those not marked as Visible Elements.'
                    );
                    $this->form->setDefault('includeNonVisibleElements', false);
                }

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        $name = $field->getName();

        switch ($name) {
            case 'levels':
                $this->levels = $this->form->getValue('levels');
                if (empty($this->levels)) {
                    $this->levels = [];
                }

                break;

            case 'exportType':
            case 'format':
                $this->{$name} = $this->form->getValue($name);

                break;

            default:
                return parent::processField($field);
        }
    }

    private function getJobNameString()
    {
        switch ($this->objectType) {
            case 'informationObject':
                if ('csv' == $this->formatType) {
                    return 'arInformationObjectCsvExportJob';
                }

                return 'arInformationObjectXmlExportJob';

            case 'actor':
                if ('csv' == $this->formatType) {
                    return 'arActorCsvExportJob';
                }

                return 'arActorXmlExportJob';

            case 'accession':
                return 'arAccessionCsvExportJob';

            case 'repository':
                return 'arRepositoryCsvExportJob';

            default:
                throw new sfException(
                    "Invalid object type specified: {$this->objectType}"
                );
        }
    }
}
