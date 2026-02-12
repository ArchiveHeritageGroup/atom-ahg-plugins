<?php

require_once sfConfig::get('sf_root_dir').'/apps/qubit/modules/repository/actions/indexAction.class.php';

class sfIsdiahPluginIndexAction extends RepositoryIndexAction
{
    public function execute($request)
    {
        parent::execute($request);

        $this->isdiah = new sfIsdiahPlugin($this->resource);

        if (
            null !== $this->resource->id
            && sfConfig::get('app_enable_institutional_scoping')
        ) {
            // Add repo to the user session as realm
            $this->context->user->setAttribute('search-realm', $this->resource->id);
        }

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->htmlSnippet = $this->getPurifiedHtmlSnippet();

        if (\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->authorizedFormOfName = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Authorized form of name%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#5.1.2">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );
            $values['authorizedFormOfName'] = $this->resource->getAuthorizedFormOfName(['culltureFallback' => true]);

            $validatorSchema->identifier = new sfValidatorString(
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Identifier%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#5.1.1">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );
            $values['identifier'] = $this->resource->identifier;

            $validatorSchema->primaryContact = new sfValidatorAnd(
                [
                    new QubitValidatorCountable(),
                    new sfValidatorOr(
                        [
                            new sfValidatorSchema(
                                ['city' => new sfValidatorString(['required' => true])],
                                ['allow_extra_fields' => true]
                            ),
                            new sfValidatorSchema(
                                ['countryCode' => new sfValidatorString(['required' => true])],
                                ['allow_extra_fields' => true]
                            ),
                            new sfValidatorSchema(
                                ['postalCode' => new sfValidatorString(['required' => true])],
                                ['allow_extra_fields' => true]
                            ),
                            new sfValidatorSchema(
                                ['region' => new sfValidatorString(['required' => true])],
                                ['allow_extra_fields' => true]
                            ),
                            new sfValidatorSchema(
                                ['streetAddress' => new sfValidatorString(['required' => true])],
                                ['allow_extra_fields' => true]
                            ),
                        ],
                        ['required' => true],
                        ['invalid' => $this->context->i18n->__(
                            '%1%Contact information%2% - You %3%must%4% at least include one of the following location or address fields: city, country, postal code, region or street address.',
                            [
                                '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#5.2.1">',
                                '%2%' => '</a>',
                                '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#4.7">',
                                '%4%' => '</a>',
                            ]
                        )]
                    ),
                ],
                ['required' => true],
                ['required' => $this->context->i18n->__(
                    '%1%Contact information%2% - This is a %3%mandatory%4% element.',
                    [
                        '%1%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#5.2.1">',
                        '%2%' => '</a>',
                        '%3%' => '<a class="alert-link" href="http://ica-atom.org/doc/RS-3#4.7">',
                        '%4%' => '</a>',
                    ]
                )]
            );

            if (null !== $this->resource->getPrimaryContact()) {
                $values['primaryContact']['city'] = $this->resource->getPrimaryContact()->getCity(['culltureFallback' => true]);
                $values['primaryContact']['countryCode'] = $this->resource->getPrimaryContact()->countryCode;
                $values['primaryContact']['postalCode'] = $this->resource->getPrimaryContact()->postalCode;
                $values['primaryContact']['region'] = $this->resource->getPrimaryContact()->getRegion(['culltureFallback' => true]);
                $values['primaryContact']['streetAddress'] = $this->resource->getPrimaryContact()->streetAddress;
            }

            try {
                $validatorSchema->clean($values);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }

        if (null !== $contact = $this->resource->getPrimaryContact()) {
            if (isset($contact->latitude, $contact->longitude)) {
                $this->latitude = $contact->latitude;
                $this->longitude = $contact->longitude;
            }
        }
    }

    protected function getPurifiedHtmlSnippet()
    {
        $cacheKey = 'repository:htmlsnippet:'.$this->resource->id;
        $cache = QubitCache::getInstance();

        if (null === $cache) {
            return;
        }

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $content = $this->resource->getHtmlSnippet();
        $content = QubitHtmlPurifier::getInstance()->purify($content);

        $cache->set($cacheKey, $content);

        return $content;
    }
}
