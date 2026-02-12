<?php

require_once sfConfig::get('sf_root_dir').'/apps/qubit/modules/actor/actions/indexAction.class.php';

class sfIsaarPluginIndexAction extends ActorIndexAction
{
    public function execute($request)
    {
        parent::execute($request);

        if (sfConfig::get('app_enable_institutional_scoping')) {
            // remove search-realm
            $this->context->user->removeAttribute('search-realm');
        }

        $this->isaar = new sfIsaarPlugin($this->resource);

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        if (\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->authorizedFormOfName = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Authorized form of name%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#5.1.2">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );
            $values['authorizedFormOfName'] = $this->resource->getAuthorizedFormOfName(['cultureFallback' => true]);

            $validatorSchema->datesOfExistence = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Dates of existence%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#5.2.1">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );
            $values['datesOfExistence'] = $this->resource->getDatesOfExistence(['cultureFallback' => true]);

            $validatorSchema->descriptionIdentifier = new sfValidatorAnd([
                new sfValidatorString(
                    ['required' => true],
                    ['required' => $this->context->i18n->__(
                        '%1%Authority record identifier%2% - This is a %3%mandatory%4% element.',
                        [
                            '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#5.4.1">',
                            '%2%' => '</a>',
                            '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#4.7">',
                            '%4%' => '</a>',
                        ]
                    )]
                ),
                new QubitValidatorActorDescriptionIdentifier(['resource' => $this->resource]),
            ]);
            $values['descriptionIdentifier'] = $this->resource->descriptionIdentifier;

            $validatorSchema->entityType = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Type of entity%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#5.1.1">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-2#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );
            $values['entityType'] = $this->resource->entityType;

            try {
                $validatorSchema->clean($values);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }
    }
}
