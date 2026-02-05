<?php

/*
 * Extended sfIsaarPlugin related authority record component.
 * Fixes "Unknown record property sf_method" error when editing actors.
 *
 * The base AtoM code passes the actor object directly to routing->generate(),
 * which triggers __isset() on the actor for properties like 'sf_method'.
 * This override generates the URL using the slug instead to avoid the error.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

// Load the base component
require_once sfConfig::get('sf_plugins_dir').'/sfIsaarPlugin/modules/sfIsaarPlugin/actions/relatedAuthorityRecordComponent.class.php';

class AhgRelatedAuthorityRecordComponent extends sfIsaarPluginRelatedAuthorityRecordComponent
{
    protected function addField($name)
    {
        switch ($name) {
            case 'resource':
                // Generate forbidden URL using slug to avoid __isset() error on actor
                // The base code does: $this->context->routing->generate(null, $this->resource)
                // which triggers offsetExists() -> __isset('sf_method') on the actor
                $forbiddenUrl = '';
                if (isset($this->resource) && isset($this->resource->id)) {
                    // Use url_for with explicit module to generate the URL safely
                    $forbiddenUrl = url_for(['module' => 'actor', 'action' => 'index', 'slug' => $this->resource->slug]);
                }

                $validators = [new sfValidatorString()];
                if (!empty($forbiddenUrl)) {
                    $validators[] = new QubitValidatorForbiddenValues(['forbidden_values' => [$forbiddenUrl]]);
                }

                $this->form->setValidator('resource', new sfValidatorAnd($validators, ['required' => true]));
                $this->form->setWidget('resource', new sfWidgetFormSelect(['choices' => []]));

                break;

            default:
                return parent::addField($name);
        }
    }
}
