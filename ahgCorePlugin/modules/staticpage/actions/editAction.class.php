<?php

/**
 * AHG stub for staticpage/edit action.
 * Replaces apps/qubit/modules/staticpage/actions/editAction.class.php.
 *
 * CRUD form for creating/editing static pages.
 */
class StaticPageEditAction extends DefaultEditAction
{
    public static $NAMES = [
        'title',
        'slug',
        'content',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                $this->processForm();

                $this->resource->save();

                // Invalidate static page content cache entry
                if (!$this->new && null !== $cache = QubitCache::getInstance()) {
                    foreach (sfConfig::get('app_i18n_languages') as $culture) {
                        $cacheKey = 'staticpage:'.$this->resource->id.':'.$culture;
                        $cache->remove($cacheKey);
                    }
                }

                $this->redirect([$this->resource, 'module' => 'staticpage']);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->form->getWidgetSchema()->setIdFormat('edit-%s');

        $this->resource = new QubitStaticPage();
        $title = $this->context->i18n->__('Add new page');

        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;

            $this->new = false;

            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }

            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        } else {
            $this->new = true;
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'content':
                $this->form->setDefault('content', $this->resource->content);
                $this->form->setValidator('content', new sfValidatorString());
                $this->form->setWidget('content', new sfWidgetFormTextarea());

                break;

            case 'slug':
                $this->form->setDefault('slug', $this->resource->slug);
                $this->form->setValidator('slug', new sfValidatorRegex(
                    ['pattern' => '/^[^;]*$/'],
                    ['invalid' => $this->context->i18n->__('Mustn\'t contain ";"')]
                ));
                $this->form->setWidget('slug', new sfWidgetFormInput());

                // no break
            case 'title':
                $this->form->setDefault('title', $this->resource->title);
                $this->form->setValidator('title', new sfValidatorString());
                $this->form->setWidget('title', new sfWidgetFormInput());

                // no break
            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'slug':
                if (!$this->resource->isProtected()) {
                    $this->resource->slug = $this->form->getValue('slug');
                }

                break;

            default:
                return parent::processField($field);
        }
    }
}
