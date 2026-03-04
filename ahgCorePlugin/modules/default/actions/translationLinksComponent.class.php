<?php

/**
 * Translation links stub.
 * Shows available translations for multilingual resources.
 */
class DefaultTranslationLinksComponent extends sfComponent
{
    public function execute($request)
    {
        // Require I18NHelper for format_language()
        $helperPath = sfConfig::get('sf_root_dir', '').'/vendor/symfony/lib/helper/I18NHelper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }

        $currentCulture = $this->getUser()->getCulture();
        $i18ns = [];
        $propertyName = null;
        $sourceCultureProperty = '';

        switch (get_class($this->resource)) {
            case 'QubitInformationObject':
                $this->module = 'informationobject';
                $i18ns = $this->resource->informationObjectI18ns;
                $propertyName = 'title';
                $sourceCultureProperty = $this->resource->getTitle(['sourceCulture' => true]);
                break;

            case 'QubitActor':
                $this->module = 'actor';
                $i18ns = $this->resource->actorI18ns;
                $propertyName = 'authorizedFormOfName';
                $sourceCultureProperty = $this->resource->getAuthorizedFormOfName(['sourceCulture' => true]);
                break;

            case 'QubitRepository':
                $this->module = 'repository';
                $i18ns = $this->resource->actorI18ns;
                $propertyName = 'authorizedFormOfName';
                $sourceCultureProperty = $this->resource->getAuthorizedFormOfName(['sourceCulture' => true]);
                break;

            case 'QubitAccession':
                $this->module = 'accession';
                $i18ns = $this->resource->accessionI18ns;
                $sourceCultureProperty = $this->resource->identifier;
                break;

            case 'QubitDeaccession':
                $this->module = 'deaccession';
                $i18ns = $this->resource->deaccessionI18ns;
                $sourceCultureProperty = $this->resource->identifier;
                break;

            case 'QubitDonor':
                $this->module = 'donor';
                $i18ns = $this->resource->actorI18ns;
                $propertyName = 'authorizedFormOfName';
                $sourceCultureProperty = $this->resource->getAuthorizedFormOfName(['sourceCulture' => true]);
                break;

            case 'QubitFunctionObject':
                $this->module = 'function';
                $i18ns = $this->resource->functionObjectI18ns;
                $propertyName = 'authorizedFormOfName';
                $sourceCultureProperty = $this->resource->getAuthorizedFormOfName(['sourceCulture' => true]);
                break;

            case 'QubitPhysicalObject':
                $this->module = 'physicalobject';
                $i18ns = $this->resource->physicalObjectI18ns;
                $propertyName = 'name';
                $sourceCultureProperty = $this->resource->getName(['sourceCulture' => true]);
                break;

            case 'QubitRightsHolder':
                $this->module = 'rightsholder';
                $i18ns = $this->resource->actorI18ns;
                $propertyName = 'authorizedFormOfName';
                $sourceCultureProperty = $this->resource->getAuthorizedFormOfName(['sourceCulture' => true]);
                break;

            case 'QubitTerm':
                $this->module = 'term';
                $i18ns = $this->resource->termI18ns;
                $propertyName = 'name';
                $sourceCultureProperty = $this->resource->getName(['sourceCulture' => true]);
                break;

            default:
                return sfView::NONE;
        }

        if (1 == count($i18ns) && $i18ns[0]->culture == $currentCulture) {
            return sfView::NONE;
        }

        $this->translations = self::getOtherCulturesAvailable($i18ns, $propertyName, $sourceCultureProperty, $currentCulture);
    }

    public static function getOtherCulturesAvailable($i18ns, $propertyName, $sourceCultureProperty, $currentCulture = null)
    {
        $translations = [];

        foreach ($i18ns as $i18n) {
            if ($currentCulture && $i18n->culture == $currentCulture) {
                continue;
            }

            $name = isset($propertyName) && isset($i18n->{$propertyName}) ? $i18n->{$propertyName} : $sourceCultureProperty;
            $langCode = $i18n->culture;
            $langName = function_exists('format_language') ? format_language($langCode) : $langCode;

            $translations[$langCode] = [
                'name' => $name,
                'language' => ucfirst($langName),
            ];
        }

        return $translations;
    }
}
