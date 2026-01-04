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

// Include the metadata extraction trait
require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/Shared/ahgMetadataExtractionTrait.php';

class InformationObjectMultiFileUploadAction extends sfAction
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
        if (!QubitAcl::check($this->resource, 'update') && !$this->getUser()->hasGroup(QubitAclGroup::EDITOR_ID)) {
            QubitAcl::forwardUnauthorized();
        }

        // Check if uploads are allowed
        if (!QubitDigitalObject::isUploadAllowed()) {
            QubitAcl::forwardToSecureAction();
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
                $term = QubitTerm::getById($level->id);
                if ($term) {
                    $choices[$this->context->routing->generate(null, [$term, 'module' => 'term'])] = $term;
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

            // Create an information object for this digital object
            $informationObject = new QubitInformationObject();
            $informationObject->parentId = $this->resource->id;

            if (0 < strlen($title = $file['infoObjectTitle'])) {
                $informationObject->title = $title;
            }

            if (null !== $levelOfDescription = $this->form->getValue('levelOfDescription')) {
                $params = $this->context->routing->parse(Qubit::pathInfo($levelOfDescription));
                $informationObject->levelOfDescription = $params['_sf_route']->resource;
            }

            $informationObject->setStatus(['typeId' => QubitTerm::STATUS_TYPE_PUBLICATION_ID, 'statusId' => sfConfig::get('app_defaultPubStatus')]);

            // Save description
            $informationObject->save();

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
                            $informationObject->save();
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
