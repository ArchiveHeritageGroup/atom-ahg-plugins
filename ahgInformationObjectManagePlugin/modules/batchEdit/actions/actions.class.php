<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Batch edit and batch rename of archival descriptions (#204).
 *
 * One action, three steps, all POST: the clipboard posts the selected slugs
 * (step "form"), the form posts the operations back for a preview (step
 * "preview"), and the preview posts them once more to write (step "apply").
 * The slugs and the operations travel with every step as form fields, so
 * nothing is kept in the session.
 */
class batchEditActions extends AhgController
{
    /** Posted fields carried from step to step. */
    public const FIELDS = [
        'levelId', 'repositoryId', 'pubStatusId', 'accessConditions', 'reproductionConditions',
        'languages', 'languagesMode', 'subject', 'place', 'genre', 'creators',
        'eventTypeId', 'eventDate', 'eventStart', 'eventEnd',
        'renameFind', 'renameReplace', 'renameCi', 'renamePrefix', 'renameSuffix',
    ];

    public function executeBatch($request)
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID) || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'clipboard', 'action' => 'view']);
        }

        $svc = '\\AhgInformationObjectManage\\Services\\BatchEditService';
        $culture = $this->culture();

        $this->slugs = $svc::normaliseSlugs($request->getPostParameter('slugs', []));
        $this->tooMany = count($this->slugs) > $svc::MAX_RECORDS;
        $this->maxRecords = $svc::MAX_RECORDS;
        [$records, $this->missing] = $svc::loadRecords($this->slugs);
        $this->slugs = array_values(array_map(static function ($r) { return (string) $r->slug; }, $records));
        $this->labels = $svc::labels($culture);
        $this->errors = [];
        $i18n = $this->context->i18n;
        $this->fieldLabels = [
            'title' => $i18n->__('Title'), 'levelId' => $i18n->__('Level of description'),
            'repositoryId' => $i18n->__('Repository'), 'pubStatusId' => $i18n->__('Publication status'),
            'accessConditions' => $i18n->__('Conditions governing access'),
            'reproductionConditions' => $i18n->__('Conditions governing reproduction'),
            'languages' => $i18n->__('Language(s) of material'), 'subject' => $i18n->__('Subject access points'),
            'place' => $i18n->__('Place access points'), 'genre' => $i18n->__('Genre access points'),
            'event' => $i18n->__('Date'), 'creators' => $i18n->__('Creator(s)'),
        ];

        $this->values = [];
        foreach (self::FIELDS as $field) {
            $value = $request->getPostParameter($field);
            if (null !== $value) {
                $this->values[$field] = $value;
            }
        }

        $step = (string) $request->getPostParameter('step', 'form');
        if (!$records) {
            $this->errors[] = $this->context->i18n->__('No descriptions selected. Add descriptions to the clipboard first.');
            $step = 'form';
        }

        if ('form' !== $step && $records) {
            [$ops, $errors] = $svc::parseOperations($this->values, $culture, $this->labels);
            foreach ($errors as $error) {
                $this->errors[] = $this->context->i18n->__($error);
            }
            if (!$errors && !$svc::hasOperations($ops)) {
                $this->errors[] = $this->context->i18n->__('Nothing to change: fill in at least one field.');
            }
            if ($this->errors) {
                $step = 'form';
            }
        }

        $this->records = [];
        foreach ($records as $id => $resource) {
            $this->records[$id] = ['slug' => (string) $resource->slug, 'title' => (string) $resource->getTitle(['cultureFallback' => true])];
        }

        if ('apply' === $step) {
            @set_time_limit(0);
            $batchId = bin2hex(random_bytes(8));
            $this->results = $svc::apply($records, $ops, $this->labels, $culture, $batchId);
            $this->setTemplate('result');

            return;
        }

        if ('preview' === $step) {
            $this->rows = $svc::preview($records, $ops, $this->labels, $culture);
            $this->setTemplate('preview');

            return;
        }

        $this->eventTypeDefault = $svc::CREATION_EVENT_ID;
    }
}
