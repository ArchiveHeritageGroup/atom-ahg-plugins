<?php
/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Modified by The AHG to include:
 * - Sector-filtered Level of Description
 * - Universal Metadata Extraction (EXIF, IPTC, XMP, PDF, Office, Video, Audio)
 * - Face Detection
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

use AtomFramework\Http\Controllers\AhgController;

// Include the metadata extraction trait from ahgMetadataExtractionPlugin
$traitFile = sfConfig::get('sf_plugins_dir') . '/ahgMetadataExtractionPlugin/lib/Services/ahgMetadataExtractionTrait.php';
if (file_exists($traitFile)) {
    require_once $traitFile;
}

class InformationObjectMultiFileUploadAction extends AhgController
{
    // Use the universal metadata extraction trait
    use arMetadataExtractionTrait;

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->resource = $this->getRoute()->resource;

        // Check that object exists and that it is not the root
        if (!isset($this->resource) || !isset($this->resource->parent)) {
            $this->forward404();
        }

        // Check user authorization
        if (!\AtomExtensions\Services\AclService::check($this->resource, 'update') && !$this->getUser()->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID)) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        // Check if uploads are allowed
        if (!QubitDigitalObject::isUploadAllowed()) {
            \AtomExtensions\Services\AclService::forwardToSecureAction();
        }

        // Get max upload size limits
        $this->maxFileSize = QubitDigitalObject::getMaxUploadSize();
        $this->maxPostSize = QubitDigitalObject::getMaxPostSize();

        // Paths for uploader javascript
        $this->uploadResponsePath = "{$this->context->routing->generate(null, ['module' => 'digitalobject', 'action' => 'upload'])}?".http_build_query(['informationObjectId' => $this->resource->id]);

        // Add form fields
        $this->form->setValidator('title', new sfValidatorString());
        $this->form->setWidget('title', new sfWidgetFormInput());
        $this->form->setDefault('title', 'image %dd%');

        $this->form->setValidator('levelOfDescription', new sfValidatorString());
        
        // AHG: Filter level of description by sector based on parent's display standard
        $choices = [];
        $choices[null] = null;
        
        $sector = null;
        if ($this->resource && $this->resource->displayStandardId) {
            $termCode = \Illuminate\Database\Capsule\Manager::table('term')
                ->where('id', $this->resource->displayStandardId)
                ->value('code');
            if ($termCode) {
                $sectorMap = [
                    'isad' => 'archive', 'rad' => 'archive', 'dacs' => 'archive', 'dc' => 'archive',
                    'mods' => 'library', 'library' => 'library',
                    'museum' => 'museum', 'cco' => 'museum',
                    'cdwa' => 'gallery', 'gallery' => 'gallery',
                    'dam' => 'dam',
                ];
                $sector = $sectorMap[strtolower($termCode)] ?? null;
            }
        }
        
        if ($sector) {
            // Get sector-specific levels
            $levels = \Illuminate\Database\Capsule\Manager::table('term as t')
                ->join('term_i18n as ti', 't.id', '=', 'ti.id')
                ->join('level_of_description_sector as los', 't.id', '=', 'los.term_id')
                ->where('t.taxonomy_id', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
                ->where('ti.culture', $this->context->user->getCulture())
                ->where('los.sector', $sector)
                ->orderBy('los.display_order')
                ->select('t.id', 'ti.name')
                ->get();
            
            foreach ($levels as $level) {
                if (term_exists($level->id)) {
                    $slug = \Illuminate\Database\Capsule\Manager::table('slug')->where('object_id', $level->id)->value('slug');
                    if ($slug) {
                        $choices[$this->context->routing->generate(null, ['module' => 'term', 'slug' => $slug])] = $level->name;
                    }
                }
            }
        } else {
            // Fallback to all levels
            foreach (QubitTaxonomy::getTermsById(QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID) as $item) {
                $choices[$this->context->routing->generate(null, [$item, 'module' => 'term'])] = $item;
            }
        }
        
        $this->form->setWidget('levelOfDescription', new sfWidgetFormSelect(['choices' => $choices]));

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters(), $request->getFiles());
            if ($this->form->isValid()) {
                $this->processForm();
            }
        }
    }

    public function processForm()
    {
        $tmpPath = sfConfig::get('sf_upload_dir').'/tmp';

        // Upload files
        $i = 0;
        $informationObjectSlugList = [];

        foreach ($this->form->getValue('files') as $file) {
            if (0 == strlen($file['infoObjectTitle'] || 0 == strlen($file['tmpName']))) {
                continue;
            }

            ++$i;

            // Dual-mode: create information object (Propel model, works via PropelBridge in both modes)
            if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                $informationObject = \AtomFramework\Services\Write\WriteServiceFactory::informationObject()->newInformationObject();
            } else {
                $informationObject = new QubitInformationObject();
            }
            $informationObject->parentId = $this->resource->id;

            if (0 < strlen($title = $file['infoObjectTitle'])) {
                $informationObject->title = $title;
            }

            if (null !== $levelOfDescription = $this->form->getValue('levelOfDescription')) {
                $params = $this->context->routing->parse(Qubit::pathInfo($levelOfDescription));
                $informationObject->levelOfDescription = $params['_sf_route']->resource;
            }

            $informationObject->setStatus(['typeId' => QubitTerm::STATUS_TYPE_PUBLICATION_ID, 'statusId' => sfConfig::get('app_defaultPubStatus')]);

            // Dual-mode: Propel save (works via PropelBridge in both modes)
            if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                $informationObject->save(); // PropelBridge still available; Phase 4 will replace
            } else {
                $informationObject->save();
            }

            $tmpFilePath = "{$tmpPath}/{$file['tmpName']}";
            
            if (file_exists($tmpFilePath)) {
                // Upload asset and create digital object
                $digitalObject = new QubitDigitalObject();
                $digitalObject->object = $informationObject;
                $digitalObject->usageId = QubitTerm::MASTER_ID;
                $digitalObject->assets[] = new QubitAsset($file['name'], file_get_contents($tmpFilePath));
                $digitalObject->save();

                // =============================================================
                // AHG: UNIVERSAL METADATA EXTRACTION
                // =============================================================
                try {
                    $metadata = $this->extractAllMetadata($tmpFilePath);

                    if ($metadata) {
                        // Apply metadata to information object
                        $this->applyMetadataToInformationObject(
                            $informationObject,
                            $metadata,
                            $digitalObject
                        );

                        // Override title with metadata title if form title was auto-generated
                        $keyFields = $metadata['_extractor']['key_fields'] ?? [];
                        if (!empty($keyFields['title']) && strpos($file['infoObjectTitle'], 'image ') === 0) {
                            $informationObject->setTitle($keyFields['title']);
                            // Dual-mode: Propel save (works via PropelBridge in both modes)
                            if (class_exists('\\AtomFramework\\Services\\Write\\WriteServiceFactory')) {
                                $informationObject->save(); // PropelBridge still available; Phase 4 will replace
                            } else {
                                $informationObject->save();
                            }
                        }

                        // Process face detection if enabled and this is an image
                        $fileType = $metadata['_extractor']['file_type'] ?? null;
                        if ($fileType === 'image') {
                            $this->processFaceDetection($tmpFilePath, $informationObject, $digitalObject);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Metadata extraction error: " . $e->getMessage());
                }

                // Clean up temp files
                unlink($tmpFilePath);
            }

            $informationObjectSlugList[] = $informationObject->slug;
        }

        $this->redirect([$this->resource, 'module' => 'informationobject', 'action' => 'multiFileUpdate', 'items' => implode(',', $informationObjectSlugList)]);
    }
}
