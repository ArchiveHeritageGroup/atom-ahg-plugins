<?php
/**
 * Heritage Platform OAI-PMH Metadata Format Template
 *
 * Generates heritage:record XML for OAI-PMH GetRecord and ListRecords responses.
 *
 * @param QubitInformationObject $resource The information object to serialize
 */

// Use the OaiHeritageMetadataFormat class to generate XML
require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/OaiHeritageMetadataFormat.php';

$culture = sfContext::getInstance()->user->getCulture() ?: 'en';

// Output the heritage XML
echo \AhgFederation\OaiHeritageMetadataFormat::generateXml($resource, $culture);
