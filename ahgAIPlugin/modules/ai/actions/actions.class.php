<?php

require_once dirname(__FILE__).'/../../../lib/Services/NerService.php';

class aiActions extends sfActions
{
    public function executeExtract(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $objectId = $request->getParameter('id');
        $object = QubitInformationObject::getById($objectId);
        
        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        $text = $this->getObjectText($object);
        $pdfPath = $this->getDigitalObjectPath($object);
        
        $nerService = new ahgNerService();
        
        if ($pdfPath && file_exists($pdfPath)) {
            $result = $nerService->extractFromPdf($pdfPath);
            
            if (isset($result['success']) && $result['success'] && !empty($text)) {
                $metaResult = $nerService->extract($text);
                if (isset($metaResult['success']) && $metaResult['success']) {
                    foreach ($metaResult['entities'] as $type => $values) {
                        if (!isset($result['entities'][$type])) {
                            $result['entities'][$type] = [];
                        }
                        $result['entities'][$type] = array_values(array_unique(
                            array_merge($result['entities'][$type], $values)
                        ));
                    }
                    $result['entity_count'] = array_sum(array_map('count', $result['entities']));
                }
            }
        } else {
            if (empty(trim($text))) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'No text content found']));
            }
            $result = $nerService->extract($text);
        }

        if (!isset($result['success']) || !$result['success']) {
            return $this->renderText(json_encode($result));
        }

        $this->saveExtraction($objectId, $result['entities']);

        return $this->renderText(json_encode([
            'success' => true,
            'entities' => $result['entities'],
            'entity_count' => $result['entity_count'] ?? 0,
            'processing_time_ms' => $result['processing_time_ms'] ?? 0,
            'source' => $pdfPath ? 'pdf+metadata' : 'metadata'
        ]));
    }

    public function executeReview(sfWebRequest $request)
    {
        $this->pendingCount = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('status', 'pending')
            ->count();
        
        $this->pendingObjects = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->join('information_object', 'ahg_ner_entity.object_id', '=', 'information_object.id')
            ->join('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->where('ahg_ner_entity.status', 'pending')
            ->select(
                'information_object.id',
                'slug.slug',
                'information_object_i18n.title',
                Illuminate\Database\Capsule\Manager::raw('COUNT(*) as pending_count')
            )
            ->groupBy('information_object.id', 'slug.slug', 'information_object_i18n.title')
            ->orderBy('pending_count', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function executeGetEntities(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $objectId = $request->getParameter('id');
        
        $entities = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->where('status', 'pending')
            ->orderBy('entity_type')
            ->orderBy('entity_value')
            ->get()
            ->toArray();

        $grouped = [];
        foreach ($entities as $entity) {
            $type = $entity->entity_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            
            // Find matches based on type
            if ($type === 'PERSON' || $type === 'ORG') {
                $matches = $this->findMatchingActors($entity->entity_value);
            } elseif ($type === 'GPE') {
                $matches = $this->findMatchingPlaces($entity->entity_value);
            } elseif ($type === 'DATE') {
                $matches = ['exact' => [], 'partial' => []]; // Dates create Events, not Subject links
            } else {
                $matches = ['exact' => [], 'partial' => []];
            }
            
            $grouped[$type][] = [
                'id' => $entity->id,
                'value' => $entity->entity_value,
                'status' => $entity->status,
                'exact_matches' => $matches['exact'],
                'partial_matches' => $matches['partial']
            ];
        }

        return $this->renderText(json_encode([
            'success' => true,
            'entities' => $grouped
        ]));
    }

    public function executeUpdateEntity(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $entityId = $request->getParameter('entity_id');
        $action = $request->getParameter('decision');
        $targetId = $request->getParameter('target_id');
        
        $userId = $this->getUser()->getAttribute('user_id');

        $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->first();

        if (!$entity) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity not found']));
        }

        $update = [
            'status' => $action === 'link' ? 'linked' : $action,
            'reviewed_by' => $userId,
            'reviewed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($targetId) {
            $update['linked_actor_id'] = $targetId;
        }

        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update($update);

        // If linking, add appropriate access point
        if ($action === 'link' && $targetId) {
            if ($entity->entity_type === 'PERSON' || $entity->entity_type === 'ORG') {
                $this->linkActorToObject($entity->object_id, $targetId);
            } elseif ($entity->entity_type === 'GPE') {
                $this->linkPlaceToObject($entity->object_id, $targetId);
            } elseif ($entity->entity_type === 'DATE') {
                $this->linkSubjectToObject($entity->object_id, $targetId);
            }
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    public function executeCreateActor(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $entityId = $request->getParameter('entity_id');
        $entityType = $request->getParameter('entity_type');
        
        $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->first();

        if (!$entity) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity not found']));
        }

        // Check if actor already exists
        $existing = Illuminate\Database\Capsule\Manager::table('actor_i18n')
            ->where('authorized_form_of_name', $entity->entity_value)
            ->first();

        if ($existing) {
            // Link to existing
            $this->linkActorToObject($entity->object_id, $existing->id);
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entityId)
                ->update([
                    'status' => 'linked',
                    'linked_actor_id' => $existing->id,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);
            return $this->renderText(json_encode(['success' => true, 'action' => 'linked_existing', 'id' => $existing->id]));
        }

        // Determine entity type ID
        $entityTypeId = ($entityType === 'ORG') ? QubitTerm::CORPORATE_BODY_ID : QubitTerm::PERSON_ID;

        // Create actor using Propel
        $actor = new QubitActor();
        $actor->entityTypeId = $entityTypeId;
        $actor->authorizedFormOfName = $entity->entity_value;
        $actor->save();

        // Link to information object
        $this->linkActorToObject($entity->object_id, $actor->id);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $actor->id,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $actor->id]));
    }

    public function executeCreatePlace(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $entityId = $request->getParameter('entity_id');
        
        $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->first();

        if (!$entity) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity not found']));
        }

        // Check if place term already exists
        $existing = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::PLACE_ID)
            ->where('term_i18n.name', $entity->entity_value)
            ->first();

        if ($existing) {
            // Link to existing
            $this->linkPlaceToObject($entity->object_id, $existing->id);
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entityId)
                ->update([
                    'status' => 'linked',
                    'linked_actor_id' => $existing->id,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);
            return $this->renderText(json_encode(['success' => true, 'action' => 'linked_existing', 'id' => $existing->id]));
        }

        // Create place term
        $term = new QubitTerm();
        $term->taxonomyId = QubitTaxonomy::PLACE_ID;
        $term->name = $entity->entity_value;
        $term->save();

        // Link to information object
        $this->linkPlaceToObject($entity->object_id, $term->id);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $term->id,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $term->id]));
    }

    public function executeCreateSubject(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $entityId = $request->getParameter('entity_id');
        
        $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->first();

        if (!$entity) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity not found']));
        }

        // Check if subject term already exists
        $existing = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::SUBJECT_ID)
            ->where('term_i18n.name', $entity->entity_value)
            ->first();

        if ($existing) {
            // Link to existing
            $this->linkSubjectToObject($entity->object_id, $existing->id);
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entityId)
                ->update([
                    'status' => 'linked',
                    'linked_actor_id' => $existing->id,
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);
            return $this->renderText(json_encode(['success' => true, 'action' => 'linked_existing', 'id' => $existing->id]));
        }

        // Create subject term
        $term = new QubitTerm();
        $term->taxonomyId = QubitTaxonomy::SUBJECT_ID;
        $term->name = $entity->entity_value;
        $term->save();

        // Link to information object
        $this->linkSubjectToObject($entity->object_id, $term->id);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $term->id,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $term->id]));
    }

    public function executeHealth(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        $nerService = new ahgNerService();
        return $this->renderText(json_encode($nerService->health()));
    }

    // Helper methods

    private function getObjectText($object)
    {
        $parts = [];
        if (!empty($object->title)) $parts[] = $object->title;
        if (!empty($object->scopeAndContent)) $parts[] = $object->scopeAndContent;
        if (!empty($object->archivalHistory)) $parts[] = $object->archivalHistory;
        if (!empty($object->extentAndMedium)) $parts[] = $object->extentAndMedium;
        if (!empty($object->arrangement)) $parts[] = $object->arrangement;
        return implode("\n\n", $parts);
    }

    private function getDigitalObjectPath($object)
    {
        $digitalObject = $object->getDigitalObject();
        if (!$digitalObject) return null;

        $path = $digitalObject->getAbsolutePath();
        if ($path && file_exists($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'pdf') return $path;
        }

        $uploadPath = sfConfig::get('sf_upload_dir');
        $objectPath = $uploadPath . '/r/' . $digitalObject->id;
        if (is_dir($objectPath)) {
            $files = glob($objectPath . '/*.pdf');
            if (!empty($files)) return $files[0];
        }
        return null;
    }

    private function saveExtraction($objectId, $entities)
    {
        try {
            // Delete existing pending entities
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->where('status', 'pending')
                ->delete();

            $extractionId = Illuminate\Database\Capsule\Manager::table('ahg_ner_extraction')->insertGetId([
                'object_id' => $objectId,
                'backend_used' => 'local',
                'status' => 'pending',
                'extracted_at' => date('Y-m-d H:i:s')
            ]);

            // Deduplicate entities
            $uniqueEntities = [];
            foreach ($entities as $type => $values) {
                $seen = [];
                foreach ($values as $value) {
                    $key = strtolower(trim($value));
                    if (!isset($seen[$key])) {
                        $seen[$key] = $value;
                    }
                }
                $uniqueEntities[$type] = array_values($seen);
            }

            foreach ($uniqueEntities as $type => $values) {
                foreach ($values as $value) {
                    Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')->insert([
                        'extraction_id' => $extractionId,
                        'object_id' => $objectId,
                        'entity_type' => $type,
                        'entity_value' => $value,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("NER save error: " . $e->getMessage());
        }
    }

    private function findMatchingActors($entityValue)
    {
        $exact = Illuminate\Database\Capsule\Manager::table('actor_i18n')
            ->join('actor', 'actor.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.authorized_form_of_name', $entityValue)
            ->select('actor.id', 'actor_i18n.authorized_form_of_name as name')
            ->get()
            ->toArray();

        $partial = Illuminate\Database\Capsule\Manager::table('actor_i18n')
            ->join('actor', 'actor.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $entityValue . '%')
            ->whereNotIn('actor.id', array_column($exact, 'id'))
            ->select('actor.id', 'actor_i18n.authorized_form_of_name as name')
            ->limit(5)
            ->get()
            ->toArray();

        return ['exact' => $exact, 'partial' => $partial];
    }

    private function findMatchingPlaces($entityValue)
    {
        $exact = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::PLACE_ID)
            ->where('term_i18n.name', $entityValue)
            ->select('term.id', 'term_i18n.name')
            ->get()
            ->toArray();

        $partial = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::PLACE_ID)
            ->where('term_i18n.name', 'LIKE', '%' . $entityValue . '%')
            ->whereNotIn('term.id', array_column($exact, 'id'))
            ->select('term.id', 'term_i18n.name')
            ->limit(5)
            ->get()
            ->toArray();

        return ['exact' => $exact, 'partial' => $partial];
    }

    private function findMatchingSubjects($entityValue)
    {
        $exact = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::SUBJECT_ID)
            ->where('term_i18n.name', $entityValue)
            ->select('term.id', 'term_i18n.name')
            ->get()
            ->toArray();

        $partial = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::SUBJECT_ID)
            ->where('term_i18n.name', 'LIKE', '%' . $entityValue . '%')
            ->whereNotIn('term.id', array_column($exact, 'id'))
            ->select('term.id', 'term_i18n.name')
            ->limit(5)
            ->get()
            ->toArray();

        return ['exact' => $exact, 'partial' => $partial];
    }

    private function linkActorToObject($objectId, $actorId)
    {
        $exists = Illuminate\Database\Capsule\Manager::table('relation')
            ->where('subject_id', $objectId)
            ->where('object_id', $actorId)
            ->where('type_id', QubitTerm::NAME_ACCESS_POINT_ID)
            ->exists();

        if (!$exists) {
            // Get next ID from object table
            $nextId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                'class_name' => 'QubitRelation',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Illuminate\Database\Capsule\Manager::table('relation')->insert([
                'source_culture' => 'en',
                'id' => $nextId,
                'subject_id' => $objectId,
                'object_id' => $actorId,
                'type_id' => QubitTerm::NAME_ACCESS_POINT_ID
            ]);
        }
    }

    private function linkPlaceToObject($objectId, $termId)
    {
        $exists = Illuminate\Database\Capsule\Manager::table('object_term_relation')
            ->where('object_id', $objectId)
            ->where('term_id', $termId)
            ->exists();

        if (!$exists) {
            // Get next ID from object table
            $nextId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                'class_name' => 'QubitObjectTermRelation',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            Illuminate\Database\Capsule\Manager::table('object_term_relation')->insert([
                'id' => $nextId,
                'object_id' => $objectId,
                'term_id' => $termId
            ]);
        }
    }

    private function linkSubjectToObject($objectId, $termId)
    {
        // Same as linkPlaceToObject - uses object_term_relation
        $this->linkPlaceToObject($objectId, $termId);
    }

    private function linkDateToObject($objectId, $dateString, $eventTypeId = 111)
    {
        $parsedDate = $this->parseDateString($dateString);
        if (!$parsedDate) {
            return false;
        }
        $exists = Illuminate\Database\Capsule\Manager::table('event')
            ->where('object_id', $objectId)
            ->where('type_id', $eventTypeId)
            ->where('start_date', $parsedDate['start'])
            ->exists();
        if (!$exists) {
            $nextId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            Illuminate\Database\Capsule\Manager::table('event')->insert([
                'id' => $nextId,
                'object_id' => $objectId,
                'type_id' => $eventTypeId,
                'start_date' => $parsedDate['start'],
                'end_date' => $parsedDate['end'],
                'source_culture' => 'en'
            ]);
            Illuminate\Database\Capsule\Manager::table('event_i18n')->insert([
                'id' => $nextId,
                'culture' => 'en',
                'date' => $dateString
            ]);
            return $nextId;
        }
        return false;
    }

    private function parseDateString($dateString)
    {
        $dateString = trim($dateString);
        $months = [
            'january' => '01', 'february' => '02', 'march' => '03', 'april' => '04',
            'may' => '05', 'june' => '06', 'july' => '07', 'august' => '08',
            'september' => '09', 'october' => '10', 'november' => '11', 'december' => '12'
        ];
        if (preg_match('/^(\d{1,2})\s+(\w+)\s+(\d{4})$/i', $dateString, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($m[2])] ?? null;
            $year = $m[3];
            if ($month) {
                $date = "{$year}-{$month}-{$day}";
                return ['start' => $date, 'end' => $date];
            }
        }
        if (preg_match('/^(\w+)\s+(\d{4})$/i', $dateString, $m)) {
            $month = $months[strtolower($m[1])] ?? null;
            $year = $m[2];
            if ($month) {
                $startDate = "{$year}-{$month}-01";
                $lastDay = date('t', strtotime($startDate));
                $endDate = "{$year}-{$month}-{$lastDay}";
                return ['start' => $startDate, 'end' => $endDate];
            }
        }
        if (preg_match('/^(\d{4})$/', $dateString, $m)) {
            $year = $m[1];
            return ['start' => "{$year}-01-01", 'end' => "{$year}-12-31"];
        }
        if (preg_match('/^(\d{4})-(\d{4})$/', $dateString, $m)) {
            return ['start' => "{$m[1]}-01-01", 'end' => "{$m[2]}-12-31"];
        }
        return null;
    }

    public function executeBulkSave(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['decisions'])) {
            $data = ['decisions' => json_decode($request->getParameter('decisions'), true)];
        }
        
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($data['decisions'] as $decision) {
            try {
                $entityId = $decision['entity_id'];
                $action = $decision['action'];
                
                $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                    ->where('id', $entityId)
                    ->first();
                
                if (!$entity) {
                    $results['failed']++;
                    $results['errors'][] = "Entity $entityId not found";
                    continue;
                }
                
                                // Track corrections for training feedback
                $hasValueEdit = isset($decision['edited_value']) && !empty($decision['edited_value']) && $decision['edited_value'] !== $entity->entity_value;
                $hasTypeEdit = isset($decision['edited_type']) && !empty($decision['edited_type']) && $decision['edited_type'] !== $entity->entity_type;
                
                // Store original values before updating
                $updateData = [];
                if ($hasValueEdit) {
                    $updateData['original_value'] = $entity->entity_value;
                    $updateData['entity_value'] = $decision['edited_value'];
                    $entity->entity_value = $decision['edited_value'];
                }
                if ($hasTypeEdit) {
                    $updateData['original_type'] = $entity->entity_type;
                    $updateData['entity_type'] = $decision['edited_type'];
                    $entity->entity_type = $decision['edited_type'];
                }
                
                // Set correction type for training
                if ($hasValueEdit && $hasTypeEdit) {
                    $updateData['correction_type'] = 'both';
                } elseif ($hasValueEdit) {
                    $updateData['correction_type'] = 'value_edit';
                } elseif ($hasTypeEdit) {
                    $updateData['correction_type'] = 'type_change';
                }
                
                if (!empty($updateData)) {
                    Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                        ->where('id', $entityId)
                        ->update($updateData);
                }
                
                if ($action === 'create') {
                    $createType = $decision['create_type'] ?? 'create_subject';
                    $this->processCreate($entity, $createType);
                } elseif ($action === 'link') {
                    $targetId = $decision['target_id'];
                    $this->processLink($entity, $targetId);
                } elseif ($action === 'reject' || $action === 'approved') {
                    $correctionType = $action === 'reject' ? 'rejected' : 'approved';
                    Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                        ->where('id', $entityId)
                        ->update([
                            'status' => $action,
                            'correction_type' => $correctionType,
                            'reviewed_at' => date('Y-m-d H:i:s')
                        ]);
                }
                
                $results['success']++;
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }
        
        return $this->renderText(json_encode(['success' => true, 'results' => $results]));
    }
    
    private function processCreate($entity, $createType)
    {
        $type = str_replace('create_', '', $createType);
        
        if ($type === 'actor') {
            // Check if actor exists
            $existing = Illuminate\Database\Capsule\Manager::table('actor_i18n')
                ->where('authorized_form_of_name', $entity->entity_value)
                ->first();
            
            if ($existing) {
                $this->linkActorToObject($entity->object_id, $existing->id);
                $actorId = $existing->id;
            } else {
                // Create actor with raw SQL (faster than Propel)
                $entityTypeId = ($entity->entity_type === 'ORG') ? QubitTerm::CORPORATE_BODY_ID : QubitTerm::PERSON_ID;
                
                // Insert into object table first
                $actorId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                    'class_name' => 'QubitActor',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Insert into actor table
                Illuminate\Database\Capsule\Manager::table('actor')->insert([
                    'id' => $actorId,
                    'entity_type_id' => $entityTypeId,
                    'source_culture' => 'en'
                ]);
                
                // Insert into actor_i18n table
                Illuminate\Database\Capsule\Manager::table('actor_i18n')->insert([
                    'id' => $actorId,
                    'culture' => 'en',
                    'authorized_form_of_name' => $entity->entity_value
                ]);
                
                // Create slug
                $slug = $this->generateSlug($entity->entity_value);
                Illuminate\Database\Capsule\Manager::table('slug')->insert([
                    'object_id' => $actorId,
                    'slug' => $slug
                ]);
                
                $this->linkActorToObject($entity->object_id, $actorId);
            }
            
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entity->id)
                ->update(['status' => 'linked', 'linked_actor_id' => $actorId, 'reviewed_at' => date('Y-m-d H:i:s')]);
                
        } elseif ($type === 'place') {
            $existing = Illuminate\Database\Capsule\Manager::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', QubitTaxonomy::PLACE_ID)
                ->where('term_i18n.name', $entity->entity_value)
                ->first();
            
            if ($existing) {
                $this->linkPlaceToObject($entity->object_id, $existing->id);
                $termId = $existing->id;
            } else {
                // Create term with raw SQL
                $termId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                    'class_name' => 'QubitTerm',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                Illuminate\Database\Capsule\Manager::table('term')->insert([
                    'id' => $termId,
                    'taxonomy_id' => QubitTaxonomy::PLACE_ID,
                    'source_culture' => 'en'
                ]);
                
                Illuminate\Database\Capsule\Manager::table('term_i18n')->insert([
                    'id' => $termId,
                    'culture' => 'en',
                    'name' => $entity->entity_value
                ]);
                
                $slug = $this->generateSlug($entity->entity_value);
                Illuminate\Database\Capsule\Manager::table('slug')->insert([
                    'object_id' => $termId,
                    'slug' => $slug
                ]);
                
                $this->linkPlaceToObject($entity->object_id, $termId);
            }
            
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entity->id)
                ->update(['status' => 'linked', 'linked_actor_id' => $termId, 'reviewed_at' => date('Y-m-d H:i:s')]);
                
        } elseif ($type === 'subject') {
            $existing = Illuminate\Database\Capsule\Manager::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', QubitTaxonomy::SUBJECT_ID)
                ->where('term_i18n.name', $entity->entity_value)
                ->first();
            
            if ($existing) {
                $this->linkSubjectToObject($entity->object_id, $existing->id);
                $termId = $existing->id;
            } else {
                $termId = Illuminate\Database\Capsule\Manager::table('object')->insertGetId([
                    'class_name' => 'QubitTerm',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                Illuminate\Database\Capsule\Manager::table('term')->insert([
                    'id' => $termId,
                    'taxonomy_id' => QubitTaxonomy::SUBJECT_ID,
                    'source_culture' => 'en'
                ]);
                
                Illuminate\Database\Capsule\Manager::table('term_i18n')->insert([
                    'id' => $termId,
                    'culture' => 'en',
                    'name' => $entity->entity_value
                ]);
                
                $slug = $this->generateSlug($entity->entity_value);
                Illuminate\Database\Capsule\Manager::table('slug')->insert([
                    'object_id' => $termId,
                    'slug' => $slug
                ]);
                
                $this->linkSubjectToObject($entity->object_id, $termId);
            }
            
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entity->id)
                ->update(['status' => 'linked', 'linked_actor_id' => $termId, 'reviewed_at' => date('Y-m-d H:i:s')]);
        } elseif ($type === 'date') {
            $eventId = $this->linkDateToObject($entity->object_id, $entity->entity_value);
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entity->id)
                ->update(['status' => 'linked', 'linked_actor_id' => $eventId, 'reviewed_at' => date('Y-m-d H:i:s')]);
        }
    }
    
    private function generateSlug($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (Illuminate\Database\Capsule\Manager::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function processLink($entity, $targetId)
    {
        if ($entity->entity_type === 'PERSON' || $entity->entity_type === 'ORG') {
            $this->linkActorToObject($entity->object_id, $targetId);
        } elseif ($entity->entity_type === 'GPE') {
            $this->linkPlaceToObject($entity->object_id, $targetId);
        } else {
            $this->linkDateToObject($entity->object_id, $entity->entity_value);
        }
        
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entity->id)
            ->update(['status' => 'linked', 'linked_actor_id' => $targetId, 'reviewed_at' => date('Y-m-d H:i:s')]);
    }
    /**
     * Generate summary and save to Scope & Content field
     */
    public function executeSummarize(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $maxLength = $request->getParameter('max_length', 1000);
        $minLength = $request->getParameter('min_length', 100);

        $object = QubitInformationObject::getById($objectId);

        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        // Get text to summarize - prefer PDF, fallback to metadata
        $pdfPath = $this->getDigitalObjectPath($object);
        $nerService = new ahgNerService();

        // Check if summarizer is available
        if (!$nerService->isSummarizerAvailable()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Summarizer service not available']));
        }

        $result = null;

        if ($pdfPath && file_exists($pdfPath)) {
            // Summarize from PDF
            $result = $nerService->summarizeFromPdf($pdfPath, $maxLength, $minLength);
        } else {
            // Summarize from metadata text
            $text = $this->getObjectTextForSummary($object);

            if (empty(trim($text))) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'No text content found to summarize']));
            }

            $result = $nerService->summarize($text, $maxLength, $minLength);
        }

        if (!isset($result['success']) || !$result['success']) {
            return $this->renderText(json_encode($result));
        }

        $summary = $result['summary'] ?? null;

        if (empty($summary)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No summary generated']));
        }

        // Save summary to Scope & Content field
        $saved = $this->saveScopeAndContent($objectId, $summary);

        return $this->renderText(json_encode([
            'success' => true,
            'summary' => $summary,
            'summary_length' => strlen($summary),
            'original_length' => $result['original_length'] ?? 0,
            'processing_time_ms' => $result['processing_time_ms'] ?? 0,
            'saved' => $saved,
            'source' => $pdfPath ? 'pdf' : 'metadata'
        ]));
    }

    /**
     * Get text suitable for summarization (excludes scope_and_content to avoid circular reference)
     */
    private function getObjectTextForSummary($object)
    {
        $parts = [];

        // Get title
        if (!empty($object->title)) {
            $parts[] = $object->title;
        }

        // Get archival history
        if (!empty($object->archivalHistory)) {
            $parts[] = $object->archivalHistory;
        }

        // Get extent and medium
        if (!empty($object->extentAndMedium)) {
            $parts[] = $object->extentAndMedium;
        }

        // Get arrangement
        if (!empty($object->arrangement)) {
            $parts[] = $object->arrangement;
        }

        // Get physical characteristics
        if (!empty($object->physicalCharacteristics)) {
            $parts[] = $object->physicalCharacteristics;
        }

        // Get acquisition info
        if (!empty($object->acquisition)) {
            $parts[] = $object->acquisition;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Save summary to Scope & Content field
     */
    private function saveScopeAndContent($objectId, $summary)
    {
        try {
            // Check if i18n record exists
            $exists = Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->exists();

            if ($exists) {
                // Update existing
                Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', 'en')
                    ->update(['scope_and_content' => $summary]);
            } else {
                // Insert new
                Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                    ->insert([
                        'id' => $objectId,
                        'culture' => 'en',
                        'scope_and_content' => $summary
                    ]);
            }


            return true;
        } catch (Exception $e) {
            error_log("Error saving scope and content: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Translate record fields using NLLB-200
     */
    public function executeTranslate(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid JSON data']));
        }

        $sourceLang = $data['source'] ?? 'en';
        $targetLang = $data['target'] ?? 'af';
        $fields = $data['fields'] ?? ['title' => true, 'scope_content' => true];

        $object = QubitInformationObject::getById($objectId);

        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        $startTime = microtime(true);

        // Get field values
        $translations = [];
        $errors = [];

        // Translate title
        if (!empty($fields['title']) && !empty($object->title)) {
            $result = $this->callTranslationApi($object->title, $sourceLang, $targetLang);
            if ($result['success']) {
                $translations['title'] = [
                    'original' => $object->title,
                    'translated' => $result['translated']
                ];
            } else {
                $errors[] = 'Title: ' . ($result['error'] ?? 'Translation failed');
            }
        }

        // Translate scope and content
        if (!empty($fields['scope_content']) && !empty($object->scopeAndContent)) {
            $result = $this->callTranslationApi($object->scopeAndContent, $sourceLang, $targetLang);
            if ($result['success']) {
                $translations['scope_content'] = [
                    'original' => $object->scopeAndContent,
                    'translated' => $result['translated']
                ];
            } else {
                $errors[] = 'Scope & Content: ' . ($result['error'] ?? 'Translation failed');
            }
        }

        $processingTime = round((microtime(true) - $startTime) * 1000);

        if (empty($translations) && !empty($errors)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => implode('; ', $errors)
            ]));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'translations' => $translations,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'processing_time_ms' => $processingTime,
            'errors' => $errors
        ]));
    }

    /**
     * Get available languages
     */
    public function executeTranslateLanguages(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $apiUrl = sfConfig::get('app_ai_api_url', 'http://192.168.0.112:5004');
        $url = $apiUrl . '/ai/v1/translate/languages';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Could not fetch languages'
            ]));
        }

        return $this->renderText($response);
    }

    /**
     * Call the translation API
     */
    private function callTranslationApi($text, $sourceLang, $targetLang)
    {
        $apiUrl = sfConfig::get('app_ai_api_url', 'http://192.168.0.112:5004');
        $apiKey = sfConfig::get('app_ai_api_key', 'ahg_ai_demo_internal_2026');

        $url = $apiUrl . '/ai/v1/translate';

        $payload = json_encode([
            'text' => $text,
            'source' => $sourceLang,
            'target' => $targetLang
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'Connection error: ' . $error];
        }

        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            return ['success' => false, 'error' => $data['error'] ?? "HTTP $httpCode"];
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['success']) || !$data['success']) {
            return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
        }

        return [
            'success' => true,
            'translated' => $data['translated'] ?? ''
        ];
    }
}
