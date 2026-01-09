<?php

class dataMigrationExportEadAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        $filepath = $this->getUser()->getAttribute('migration_file');
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');
        $mapping = $this->getUser()->getAttribute('migration_mapping');
        
        if (!$filepath || !file_exists($filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }
        
        // Transform data
        $rows = $detection['rows'] ?? [];
        $headers = $detection['headers'] ?? [];
        $transformed = $this->transformData($rows, $headers, $mapping);
        
        // Generate EAD XML
        $xml = $this->generateEad($transformed, $filename);
        
        // Send response
        $exportFilename = pathinfo($filename, PATHINFO_FILENAME) . '.ead.xml';
        
        $this->getResponse()->setContentType('application/xml');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $exportFilename . '"');
        $this->getResponse()->setContent($xml);
        
        return sfView::NONE;
    }
    
    protected function transformData($rows, $headers, $mapping)
    {
        $transformed = [];
        
        foreach ($rows as $row) {
            $record = [];
            
            foreach ($mapping as $fieldConfig) {
                if (empty($fieldConfig['include'])) continue;
                
                $sourceField = $fieldConfig['source_field'] ?? '';
                $atomField = $fieldConfig['atom_field'] ?? '';
                $constantValue = $fieldConfig['constant_value'] ?? '';
                $concatenate = !empty($fieldConfig['concatenate']);
                $concatConstant = !empty($fieldConfig['concat_constant']);
                $concatSymbol = $fieldConfig['concat_symbol'] ?? '|';
                
                if (empty($atomField)) continue;
                
                $sourceIndex = array_search($sourceField, $headers);
                $value = ($sourceIndex !== false && isset($row[$sourceIndex])) ? $row[$sourceIndex] : '';
                
                if ($concatConstant && $constantValue) {
                    $value = $constantValue . $value;
                } elseif ($constantValue && empty($value)) {
                    $value = $constantValue;
                }
                
                if ($concatenate && isset($record[$atomField])) {
                    $symbol = ($concatSymbol === '\n') ? "\n" : $concatSymbol;
                    $record[$atomField] .= $symbol . $value;
                } else {
                    $record[$atomField] = $value;
                }
            }
            
            if (!empty($record)) {
                $transformed[] = $record;
            }
        }
        
        return $transformed;
    }
    
    protected function generateEad($records, $filename)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // Create EAD root element
        $ead = $dom->createElement('ead');
        $ead->setAttribute('xmlns', 'urn:isbn:1-931666-22-9');
        $ead->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $ead->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $ead->setAttribute('xsi:schemaLocation', 'urn:isbn:1-931666-22-9 http://www.loc.gov/ead/ead.xsd');
        $dom->appendChild($ead);
        
        // EAD Header
        $eadheader = $dom->createElement('eadheader');
        $eadid = $dom->createElement('eadid', pathinfo($filename, PATHINFO_FILENAME));
        $eadheader->appendChild($eadid);
        
        $filedesc = $dom->createElement('filedesc');
        $titlestmt = $dom->createElement('titlestmt');
        $titleproper = $dom->createElement('titleproper', 'Import from ' . htmlspecialchars($filename));
        $titlestmt->appendChild($titleproper);
        $filedesc->appendChild($titlestmt);
        $eadheader->appendChild($filedesc);
        $ead->appendChild($eadheader);
        
        // Archival description
        $archdesc = $dom->createElement('archdesc');
        $archdesc->setAttribute('level', 'collection');
        
        // DID
        $did = $dom->createElement('did');
        $unittitle = $dom->createElement('unittitle', 'Imported Collection');
        $did->appendChild($unittitle);
        $archdesc->appendChild($did);
        
        // DSC with components
        $dsc = $dom->createElement('dsc');
        
        foreach ($records as $record) {
            $c = $dom->createElement('c');
            $c->setAttribute('level', $record['levelOfDescription'] ?? 'item');
            
            $did = $dom->createElement('did');
            
            // Unit ID
            if (!empty($record['identifier'])) {
                $unitid = $dom->createElement('unitid', htmlspecialchars($record['identifier']));
                $did->appendChild($unitid);
            }
            
            // Unit Title
            if (!empty($record['title'])) {
                $unittitle = $dom->createElement('unittitle', htmlspecialchars($record['title']));
                $did->appendChild($unittitle);
            }
            
            // Unit Date
            if (!empty($record['eventDates'])) {
                $unitdate = $dom->createElement('unitdate', htmlspecialchars($record['eventDates']));
                $did->appendChild($unitdate);
            }
            
            // Extent
            if (!empty($record['extentAndMedium'])) {
                $physdesc = $dom->createElement('physdesc');
                $extent = $dom->createElement('extent', htmlspecialchars($record['extentAndMedium']));
                $physdesc->appendChild($extent);
                $did->appendChild($physdesc);
            }
            
            // Language
            if (!empty($record['language'])) {
                $langmaterial = $dom->createElement('langmaterial');
                $language = $dom->createElement('language', htmlspecialchars($record['language']));
                $langmaterial->appendChild($language);
                $did->appendChild($langmaterial);
            }
            
            // Origination (creators)
            if (!empty($record['eventActors'])) {
                $origination = $dom->createElement('origination');
                $persname = $dom->createElement('persname', htmlspecialchars($record['eventActors']));
                $origination->appendChild($persname);
                $did->appendChild($origination);
            }
            
            $c->appendChild($did);
            
            // Scope and Content
            if (!empty($record['scopeAndContent'])) {
                $scopecontent = $dom->createElement('scopecontent');
                $p = $dom->createElement('p', htmlspecialchars($record['scopeAndContent']));
                $scopecontent->appendChild($p);
                $c->appendChild($scopecontent);
            }
            
            // Archival History / Custodial History
            if (!empty($record['archivalHistory'])) {
                $custodhist = $dom->createElement('custodhist');
                $p = $dom->createElement('p', htmlspecialchars($record['archivalHistory']));
                $custodhist->appendChild($p);
                $c->appendChild($custodhist);
            }
            
            // Access Conditions
            if (!empty($record['accessConditions'])) {
                $accessrestrict = $dom->createElement('accessrestrict');
                $p = $dom->createElement('p', htmlspecialchars($record['accessConditions']));
                $accessrestrict->appendChild($p);
                $c->appendChild($accessrestrict);
            }
            
            // General Note
            if (!empty($record['generalNote'])) {
                $note = $dom->createElement('note');
                $p = $dom->createElement('p', htmlspecialchars($record['generalNote']));
                $note->appendChild($p);
                $c->appendChild($note);
            }
            
            // Control Access (subjects, places, names)
            $controlaccess = $dom->createElement('controlaccess');
            $hasControlAccess = false;
            
            if (!empty($record['subjectAccessPoints'])) {
                foreach (explode('|', $record['subjectAccessPoints']) as $subject) {
                    if (trim($subject)) {
                        $subj = $dom->createElement('subject', htmlspecialchars(trim($subject)));
                        $controlaccess->appendChild($subj);
                        $hasControlAccess = true;
                    }
                }
            }
            
            if (!empty($record['placeAccessPoints'])) {
                foreach (explode('|', $record['placeAccessPoints']) as $place) {
                    if (trim($place)) {
                        $geog = $dom->createElement('geogname', htmlspecialchars(trim($place)));
                        $controlaccess->appendChild($geog);
                        $hasControlAccess = true;
                    }
                }
            }
            
            if (!empty($record['nameAccessPoints'])) {
                foreach (explode('|', $record['nameAccessPoints']) as $name) {
                    if (trim($name)) {
                        $persname = $dom->createElement('persname', htmlspecialchars(trim($name)));
                        $controlaccess->appendChild($persname);
                        $hasControlAccess = true;
                    }
                }
            }
            
            if ($hasControlAccess) {
                $c->appendChild($controlaccess);
            }
            
            $dsc->appendChild($c);
        }
        
        $archdesc->appendChild($dsc);
        $ead->appendChild($archdesc);
        
        return $dom->saveXML();
    }
}
