<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomFramework\Services\Write\WriteServiceFactory;
use Illuminate\Database\Capsule\Manager as DB;
require_once dirname(__FILE__).'/../../../lib/Services/NerService.php';
require_once dirname(__FILE__).'/../../../lib/Services/DescriptionService.php';
require_once dirname(__FILE__).'/../../../lib/Services/LlmService.php';
require_once dirname(__FILE__).'/../../../lib/Services/PromptService.php';

class aiActions extends AhgController
{
    // ─── Propel-free constants ──────────────────────────────────────
    private const CORPORATE_BODY_ID = 131;
    private const PERSON_ID = 132;
    private const NAME_ACCESS_POINT_ID = 161;
    private const TAXONOMY_PLACE_ID = 42;
    private const TAXONOMY_SUBJECT_ID = 35;

    public function executeExtract($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $objectId = $request->getParameter('id');
        $object = $this->getInformationObject($objectId);

        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        $text = $this->getObjectText($object);
        $pdfPath = $this->getDigitalObjectPath($objectId, 'pdf');  // Only get PDF files

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

    public function executeReview($request)
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

    public function executeGetEntities($request)
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

    public function executeUpdateEntity($request)
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

        // Dispatch event for entity approval (for entity cache sync)
        if (in_array($action, ['link', 'approved'])) {
            $this->dispatchEntityApproved($entity, $targetId);
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Dispatch event when NER entity is approved/linked.
     * Used by heritage entity cache sync service.
     */
    protected function dispatchEntityApproved($entity, $targetId = null)
    {
        try {
            if (isset($this->dispatcher) && class_exists('sfEvent', false)) {
                $this->dispatcher->notify(new \sfEvent($this, 'ner.entity_approved', [
                    'entity_id' => $entity->id,
                    'object_id' => $entity->object_id,
                    'entity_type' => $entity->entity_type,
                    'entity_value' => $entity->entity_value,
                    'confidence' => $entity->confidence ?? 1.0,
                    'linked_id' => $targetId,
                ]));
            }
        } catch (\Exception $e) {
            // Log but don't fail the main operation
            error_log('NER entity approved event dispatch error: ' . $e->getMessage());
        }
    }

    public function executeCreateActor($request)
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
        $entityTypeId = ($entityType === 'ORG') ? self::CORPORATE_BODY_ID : self::PERSON_ID;

        // Create actor via WriteServiceFactory
        $actorId = WriteServiceFactory::actor()->createActor([
            'entity_type_id' => $entityTypeId,
            'authorized_form_of_name' => $entity->entity_value,
        ], $culture ?? 'en');

        // Link to information object
        $this->linkActorToObject($entity->object_id, $actorId);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $actorId,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $actorId]));
    }

    public function executeCreatePlace($request)
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
            ->where('term.taxonomy_id', self::TAXONOMY_PLACE_ID)
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

        // Create place term via WriteServiceFactory
        $termObj = WriteServiceFactory::term()->createTerm(self::TAXONOMY_PLACE_ID, $entity->entity_value);
        $termId = $termObj->id;

        // Link to information object
        $this->linkPlaceToObject($entity->object_id, $termId);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $termId,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $termId]));
    }

    public function executeCreateSubject($request)
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
            ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
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

        // Create subject term via WriteServiceFactory
        $termObj = WriteServiceFactory::term()->createTerm(self::TAXONOMY_SUBJECT_ID, $entity->entity_value);
        $termId = $termObj->id;

        // Link to information object
        $this->linkSubjectToObject($entity->object_id, $termId);

        // Update entity status
        Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => 'linked',
                'linked_actor_id' => $termId,
                'reviewed_at' => date('Y-m-d H:i:s')
            ]);

        return $this->renderText(json_encode(['success' => true, 'action' => 'created', 'id' => $termId]));
    }

    public function executeHealth($request)
    {
        $this->getResponse()->setContentType('application/json');
        $nerService = new ahgNerService();
        return $this->renderText(json_encode($nerService->health()));
    }

    // Helper methods

    /**
     * Get information object by ID using Laravel QB.
     */
    private function getInformationObject(int $objectId): ?object
    {
        return DB::table('information_object')
            ->leftJoin('information_object_i18n', function ($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->where('information_object.id', $objectId)
            ->select(
                'information_object.id',
                'information_object.parent_id',
                'information_object.repository_id',
                'information_object.level_of_description_id',
                'information_object_i18n.title',
                'information_object_i18n.scope_and_content as scopeAndContent',
                'information_object_i18n.archival_history as archivalHistory',
                'information_object_i18n.extent_and_medium as extentAndMedium',
                'information_object_i18n.arrangement',
                'information_object_i18n.physical_characteristics as physicalCharacteristics',
                'information_object_i18n.acquisition'
            )
            ->first();
    }

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

    private function getDigitalObjectPath($objectId, $type = 'any')
    {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->first();
        if (!$digitalObject) return null;

        // Supported extensions by type
        $pdfExts = ['pdf'];
        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff', 'bmp', 'webp'];

        if ($type === 'pdf') {
            $allowedExts = $pdfExts;
        } elseif ($type === 'image') {
            $allowedExts = $imageExts;
        } else {
            $allowedExts = array_merge($pdfExts, $imageExts);
        }

        $rootDir = defined('SF_ROOT_DIR') ? SF_ROOT_DIR : '/usr/share/nginx/archive';

        // Try path from database (path + name)
        $path = $digitalObject->path ?? null;
        $name = $digitalObject->name ?? null;
        if ($path && $name) {
            $fullPath = $rootDir . '/' . ltrim($path, '/') . $name;
            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts)) return $fullPath;
            }
        }

        // Try uploads directory with path + name
        if ($path && $name) {
            $fullPath = $rootDir . '/uploads/' . ltrim(str_replace('/uploads/', '', $path), '/') . $name;
            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExts)) return $fullPath;
            }
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
            ->where('term.taxonomy_id', self::TAXONOMY_PLACE_ID)
            ->where('term_i18n.name', $entityValue)
            ->select('term.id', 'term_i18n.name')
            ->get()
            ->toArray();

        $partial = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', self::TAXONOMY_PLACE_ID)
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
            ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
            ->where('term_i18n.name', $entityValue)
            ->select('term.id', 'term_i18n.name')
            ->get()
            ->toArray();

        $partial = Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
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
            ->where('type_id', self::NAME_ACCESS_POINT_ID)
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
                'type_id' => self::NAME_ACCESS_POINT_ID
            ]);
        }
    }

    private function linkPlaceToObject($objectId, $termId)
    {
        // Verify the term exists in the term table before linking
        $termExists = Illuminate\Database\Capsule\Manager::table('term')
            ->where('id', $termId)
            ->exists();

        if (!$termExists) {
            throw new \Exception("Term ID {$termId} does not exist in term table");
        }

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
        // ISO date format YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateString, $m)) {
            return ['start' => $dateString, 'end' => $dateString];
        }
        return null;
    }

    /**
     * Split compound dates (semicolon, comma, or "and" separated) into individual dates.
     * Examples: "1843-08-12; 1847-03-04; 1856" or "1909, 1910, 1911"
     */
    private function splitCompoundDates($dateString)
    {
        $dateString = trim($dateString);

        // Split by semicolon, comma, or " and "
        $parts = preg_split('/\s*[;,]\s*|\s+and\s+/i', $dateString);

        $dates = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (!empty($part)) {
                $dates[] = $part;
            }
        }

        return count($dates) > 1 ? $dates : [$dateString];
    }

    /**
     * Create date events from a compound date string.
     * Returns array of created event IDs.
     */
    private function createDatesFromCompound($objectId, $compoundDateString, $eventTypeId = 111)
    {
        $dates = $this->splitCompoundDates($compoundDateString);
        $createdIds = [];

        foreach ($dates as $dateString) {
            $eventId = $this->linkDateToObject($objectId, $dateString, $eventTypeId);
            if ($eventId) {
                $createdIds[] = $eventId;
            }
        }

        return $createdIds;
    }

    /**
     * Create date event(s) from NER entity - handles compound dates.
     */
    public function executeCreateDate($request)
    {
        $this->getResponse()->setContentType('application/json');

        $entityId = $request->getParameter('entity_id');
        $splitDates = $request->getParameter('split_dates', true); // Default to splitting

        $entity = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->first();

        if (!$entity) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity not found']));
        }

        if ($entity->entity_type !== 'DATE') {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Entity is not a DATE type']));
        }

        $createdIds = [];
        $dateString = $entity->entity_value;

        if ($splitDates) {
            // Split compound dates and create individual events
            $createdIds = $this->createDatesFromCompound($entity->object_id, $dateString);
        } else {
            // Create single event with original string
            $eventId = $this->linkDateToObject($entity->object_id, $dateString);
            if ($eventId) {
                $createdIds[] = $eventId;
            }
        }

        if (count($createdIds) > 0) {
            // Update entity status
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entityId)
                ->update([
                    'status' => 'linked',
                    'linked_actor_id' => $createdIds[0], // Store first event ID
                    'reviewed_by' => $this->getUser()->getAttribute('user_id'),
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);

            return $this->renderText(json_encode([
                'success' => true,
                'action' => 'created',
                'event_ids' => $createdIds,
                'count' => count($createdIds),
                'message' => count($createdIds) . ' date event(s) created'
            ]));
        }

        return $this->renderText(json_encode([
            'success' => false,
            'error' => 'Could not parse date(s): ' . $dateString
        ]));
    }

    /**
     * Preview compound date splitting without creating events.
     */
    public function executePreviewDateSplit($request)
    {
        $this->getResponse()->setContentType('application/json');

        $dateString = $request->getParameter('date_string');
        if (!$dateString) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No date string provided']));
        }

        $dates = $this->splitCompoundDates($dateString);
        $parsed = [];

        foreach ($dates as $date) {
            $result = $this->parseDateString($date);
            $parsed[] = [
                'original' => $date,
                'parsed' => $result,
                'valid' => $result !== null
            ];
        }

        return $this->renderText(json_encode([
            'success' => true,
            'original' => $dateString,
            'split_count' => count($dates),
            'dates' => $parsed
        ]));
    }

    public function executeBulkSave($request)
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
                
                $shouldDispatchEvent = false;
                $linkedTargetId = null;

                if ($action === 'create') {
                    $createType = $decision['create_type'] ?? 'create_subject';
                    $this->processCreate($entity, $createType);
                    $shouldDispatchEvent = true;
                } elseif ($action === 'create_date') {
                    // Handle date creation with optional splitting
                    $splitDates = isset($decision['split_dates']) ? (bool)$decision['split_dates'] : true;
                    $this->processDateCreate($entity, $splitDates);
                    $shouldDispatchEvent = true;
                } elseif ($action === 'link') {
                    $linkedTargetId = $decision['target_id'];
                    $this->processLink($entity, $linkedTargetId);
                    $shouldDispatchEvent = true;
                } elseif ($action === 'reject' || $action === 'approved') {
                    $correctionType = $action === 'reject' ? 'rejected' : 'approved';
                    Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                        ->where('id', $entityId)
                        ->update([
                            'status' => $action,
                            'correction_type' => $correctionType,
                            'reviewed_at' => date('Y-m-d H:i:s')
                        ]);
                    // Dispatch event for approved entities (not rejected)
                    $shouldDispatchEvent = ($action === 'approved');
                }

                // Dispatch entity approval event for cache sync
                if ($shouldDispatchEvent) {
                    $this->dispatchEntityApproved($entity, $linkedTargetId);
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
                $entityTypeId = ($entity->entity_type === 'ORG') ? self::CORPORATE_BODY_ID : self::PERSON_ID;
                
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
                ->where('term.taxonomy_id', self::TAXONOMY_PLACE_ID)
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
                    'taxonomy_id' => self::TAXONOMY_PLACE_ID,
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
                ->where('term.taxonomy_id', self::TAXONOMY_SUBJECT_ID)
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
                    'taxonomy_id' => self::TAXONOMY_SUBJECT_ID,
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
     * Process date entity creation - handles compound dates.
     */
    private function processDateCreate($entity, $splitDates = true)
    {
        $dateString = $entity->entity_value;

        if ($splitDates) {
            // Split compound dates and create individual events
            $createdIds = $this->createDatesFromCompound($entity->object_id, $dateString);
        } else {
            // Create single event with original string
            $eventId = $this->linkDateToObject($entity->object_id, $dateString);
            $createdIds = $eventId ? [$eventId] : [];
        }

        if (count($createdIds) > 0) {
            Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
                ->where('id', $entity->id)
                ->update([
                    'status' => 'linked',
                    'linked_actor_id' => $createdIds[0],
                    'reviewed_by' => $this->getUser()->getAttribute('user_id'),
                    'reviewed_at' => date('Y-m-d H:i:s')
                ]);
        }
    }

    /**
     * Generate summary and save to Scope & Content field
     */
    public function executeSummarize($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $maxLength = $request->getParameter('max_length', 1000);
        $minLength = $request->getParameter('min_length', 100);

        $object = $this->getInformationObject($objectId);

        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        // Get text to summarize - prefer PDF, fallback to metadata
        $pdfPath = $this->getDigitalObjectPath($objectId);
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
    public function executeTranslate($request)
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

        $object = $this->getInformationObject($objectId);

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
    public function executeTranslateLanguages($request)
    {
        $this->getResponse()->setContentType('application/json');

        $apiUrl = $this->config('app_ai_api_url', 'http://192.168.0.112:5004');
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
        $apiUrl = $this->config('app_ai_api_url', 'http://192.168.0.112:5004');
        $apiKey = $this->config('app_ai_api_key', 'ahg_ai_demo_internal_2026');

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

    /**
     * Handwriting Text Recognition (HTR) with Zone Detection
     * Uses models at /opt/ahg-ai/models/date, digits, letters
     * Zone detection enabled by default - detects text lines and processes each separately
     */
    public function executeHtr($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true);
        $mode = $data['mode'] ?? 'all';
        $useZones = $data['use_zones'] ?? true;  // Zone detection enabled by default

        $object = $this->getInformationObject($objectId);

        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        // Get digital object path (images only for HTR)
        $imagePath = $this->getDigitalObjectPath($objectId, 'image');

        if (!$imagePath || !file_exists($imagePath)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No image found for HTR. Path checked: ' . ($imagePath ?: 'none')]));
        }

        $startTime = microtime(true);

        // Stub mode for testing - returns test data without calling API
        if ($mode === 'stub') {
            $processingTime = round((microtime(true) - $startTime) * 1000);
            return $this->renderText(json_encode([
                'success' => true,
                'results' => ['Test Result 1', 'Test Result 2', '1923-04-15', 'Sample Text'],
                'zones' => [
                    ['zone_id' => 0, 'bbox' => ['x' => 10, 'y' => 20, 'w' => 200, 'h' => 30], 'text' => 'Test Result 1'],
                    ['zone_id' => 1, 'bbox' => ['x' => 10, 'y' => 60, 'w' => 200, 'h' => 30], 'text' => 'Test Result 2'],
                    ['zone_id' => 2, 'bbox' => ['x' => 10, 'y' => 100, 'w' => 150, 'h' => 30], 'text' => '1923-04-15'],
                    ['zone_id' => 3, 'bbox' => ['x' => 10, 'y' => 140, 'w' => 180, 'h' => 30], 'text' => 'Sample Text']
                ],
                'zones_detected' => 4,
                'count' => 4,
                'processing_time_ms' => $processingTime,
                'image_path' => $imagePath,
                'use_zones' => true,
                'debug' => 'Stub mode - API not called'
            ]));
        }

        // Call the Python HTR API
        $apiUrl = $this->config('app_ai_api_url', 'http://192.168.0.112:5004');
        $apiKey = $this->config('app_ai_api_key', 'ahg_ai_demo_internal_2026');

        $url = $apiUrl . '/ai/v1/htr';

        $payload = json_encode([
            'image_path' => $imagePath,
            'mode' => $mode,  // all, date, digits, letters
            'use_zones' => $useZones  // Enable zone detection
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $processingTime = round((microtime(true) - $startTime) * 1000);

        if ($error) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Connection error: ' . $error
            ]));
        }

        if ($httpCode !== 200) {
            $data = json_decode($response, true);
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $data['error'] ?? "HTTP $httpCode"
            ]));
        }

        $result = json_decode($response, true);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $result['error'] ?? 'HTR extraction failed'
            ]));
        }

        // Build response with zone information
        $response = [
            'success' => true,
            'mode' => $mode,
            'use_zones' => $result['use_zones'] ?? $useZones,
            'results' => $result['results'] ?? [],
            'text' => $result['text'] ?? '',
            'count' => count($result['results'] ?? []),
            'processing_time_ms' => $processingTime,
            'image_path' => $imagePath
        ];

        // Include zone info if available
        if (!empty($result['zones'])) {
            $response['zones'] = $result['zones'];
            $response['zones_detected'] = $result['zones_detected'] ?? count($result['zones']);
        }

        return $this->renderText(json_encode($response));
    }

    // =========================================================================
    // LLM DESCRIPTION SUGGESTION ACTIONS
    // =========================================================================

    /**
     * Generate a description suggestion for an object
     * POST /ai/suggest/:id
     */
    public function executeSuggest($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true) ?: [];

        $templateId = $data['template_id'] ?? null;
        $llmConfigId = $data['llm_config_id'] ?? null;

        $object = $this->getInformationObject($objectId);
        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        $userId = $this->getUser()->getAttribute('user_id');

        $service = new DescriptionService();
        $result = $service->generateSuggestion($objectId, $templateId, $llmConfigId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Get suggestion review dashboard
     * GET /ai/suggest/review
     */
    public function executeSuggestReview($request)
    {
        $service = new DescriptionService();

        $repositoryId = $request->getParameter('repository');
        $this->pendingSuggestions = $service->getPendingSuggestions($repositoryId, 100);
        $this->stats = $service->getStatistics();

        // Get repositories for filter dropdown
        $this->repositories = Illuminate\Database\Capsule\Manager::table('actor as a')
            ->join('actor_i18n as ai', 'a.id', '=', 'ai.id')
            ->whereIn('a.id', function ($query) {
                $query->select('repository_id')
                    ->from('information_object')
                    ->whereNotNull('repository_id')
                    ->distinct();
            })
            ->where('ai.culture', 'en')
            ->select('a.id', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        $this->selectedRepository = $repositoryId;
    }

    /**
     * View a single suggestion
     * GET /ai/suggest/:id/view
     */
    public function executeSuggestView($request)
    {
        $this->getResponse()->setContentType('application/json');

        $suggestionId = $request->getParameter('id');

        $service = new DescriptionService();
        $suggestion = $service->getSuggestion($suggestionId);

        if (!$suggestion) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Suggestion not found']));
        }

        // Get object title
        $object = Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $suggestion->object_id)
            ->where('culture', 'en')
            ->first();

        $slug = Illuminate\Database\Capsule\Manager::table('slug')
            ->where('object_id', $suggestion->object_id)
            ->first();

        return $this->renderText(json_encode([
            'success' => true,
            'suggestion' => $suggestion,
            'object_title' => $object->title ?? 'Untitled',
            'object_slug' => $slug->slug ?? null,
        ]));
    }

    /**
     * Process suggestion decision (approve/reject)
     * POST /ai/suggest/:id/decision
     */
    public function executeSuggestDecision($request)
    {
        $this->getResponse()->setContentType('application/json');

        $suggestionId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true) ?: [];

        $decision = $data['decision'] ?? '';
        $editedText = $data['edited_text'] ?? null;
        $notes = $data['notes'] ?? null;

        $userId = $this->getUser()->getAttribute('user_id');

        $service = new DescriptionService();

        if ($decision === 'approve') {
            $result = $service->approveSuggestion($suggestionId, $userId, $editedText, $notes);
        } elseif ($decision === 'reject') {
            $result = $service->rejectSuggestion($suggestionId, $userId, $notes);
        } else {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid decision']));
        }

        return $this->renderText(json_encode($result));
    }

    /**
     * Get suggestions for a specific object
     * GET /ai/suggest/object/:id
     */
    public function executeSuggestObject($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $status = $request->getParameter('status');

        $service = new DescriptionService();
        $suggestions = $service->getSuggestionsForObject($objectId, $status);

        return $this->renderText(json_encode([
            'success' => true,
            'suggestions' => $suggestions,
        ]));
    }

    /**
     * Get LLM configurations
     * GET /ai/llm/configs
     */
    public function executeLlmConfigs($request)
    {
        $this->getResponse()->setContentType('application/json');

        $activeOnly = $request->getParameter('active', '1') === '1';

        $service = new LlmService();
        $configs = $service->getConfigurations($activeOnly);

        // Remove encrypted API keys from response
        foreach ($configs as &$config) {
            unset($config->api_key_encrypted);
            $config->has_api_key = !empty($config->api_key_encrypted);
        }

        return $this->renderText(json_encode([
            'success' => true,
            'configs' => $configs,
        ]));
    }

    /**
     * Get LLM health status
     * GET /ai/llm/health
     */
    public function executeLlmHealth($request)
    {
        $this->getResponse()->setContentType('application/json');

        $service = new LlmService();
        $health = $service->getAllHealth();

        return $this->renderText(json_encode([
            'success' => true,
            'providers' => $health,
        ]));
    }

    /**
     * Get prompt templates
     * GET /ai/templates
     */
    public function executeTemplates($request)
    {
        $this->getResponse()->setContentType('application/json');

        $activeOnly = $request->getParameter('active', '1') === '1';

        $service = new PromptService();
        $templates = $service->getTemplates($activeOnly);
        $variables = $service->getTemplateVariables();

        return $this->renderText(json_encode([
            'success' => true,
            'templates' => $templates,
            'variables' => $variables,
        ]));
    }

    /**
     * Preview a suggestion without saving
     * POST /ai/suggest/:id/preview
     */
    public function executeSuggestPreview($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        $data = json_decode($request->getContent(), true) ?: [];

        $templateId = $data['template_id'] ?? null;
        $llmConfigId = $data['llm_config_id'] ?? null;

        $object = $this->getInformationObject($objectId);
        if (!$object) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object not found']));
        }

        // Gather context
        $descService = new DescriptionService();
        $context = $descService->gatherContext($objectId);

        if (!$context['success']) {
            return $this->renderText(json_encode($context));
        }

        // Get template and build prompt
        $promptService = new PromptService();
        $template = $promptService->getTemplateForObject($objectId, $templateId);

        if (!$template) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No template available']));
        }

        $prompts = $promptService->buildPrompt($template, $context['data']);

        // Generate (but don't save)
        $llmService = new LlmService();
        $result = $llmService->complete($prompts['system'], $prompts['user'], $llmConfigId);

        if (!$result['success']) {
            return $this->renderText(json_encode($result));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'preview_text' => $result['text'],
            'existing_text' => $context['data']['scope_and_content'] ?? null,
            'tokens_used' => $result['tokens_used'],
            'model_used' => $result['model'],
            'generation_time_ms' => $result['generation_time_ms'] ?? 0,
            'template_name' => $template->name,
            'has_ocr' => !empty($context['data']['ocr_text']),
            'context_summary' => [
                'title' => $context['data']['title'],
                'identifier' => $context['data']['identifier'],
                'level' => $context['data']['level_of_description'],
                'date_range' => $context['data']['date_range'],
            ],
        ]));
    }

    // =========================================================================
    // BATCH JOB QUEUE ACTIONS
    // =========================================================================

    /**
     * Batch job queue dashboard
     * GET /ai/batch
     */
    public function executeBatch($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $service = new \ahgAIPlugin\Services\JobQueueService();

        $this->batches = $service->getBatches([], 20);
        $this->taskTypes = $service::getTaskTypes();

        // Get repositories for filter
        $this->repositories = Illuminate\Database\Capsule\Manager::table('actor')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->where('actor.class_name', 'QubitRepository')
            ->where('actor_i18n.culture', 'en')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->select('actor.id', 'actor_i18n.authorized_form_of_name as name')
            ->get()
            ->toArray();
    }

    /**
     * Create new batch job
     * POST /ai/batch/create
     */
    public function executeBatchCreate($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            $data = $request->getParameterHolder()->getAll();
        }

        if (empty($data['name']) || empty($data['task_types'])) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Name and task types are required']));
        }

        $service = new \ahgAIPlugin\Services\JobQueueService();

        try {
            // Create batch
            $batchId = $service->createBatch([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'task_types' => $data['task_types'],
                'priority' => $data['priority'] ?? 5,
                'max_concurrent' => $data['max_concurrent'] ?? 5,
                'delay_between_ms' => $data['delay_between_ms'] ?? 1000,
                'max_retries' => $data['max_retries'] ?? 3,
                'options' => $data['options'] ?? null,
                'created_by' => $this->getUser()->getUserId(),
            ]);

            // Get object IDs
            $objectIds = [];

            if (!empty($data['object_ids'])) {
                $objectIds = is_array($data['object_ids']) ? $data['object_ids'] : explode(',', $data['object_ids']);
            } elseif (!empty($data['repository_id'])) {
                // Get objects from repository
                $query = Illuminate\Database\Capsule\Manager::table('information_object')
                    ->where('repository_id', $data['repository_id'])
                    ->where('id', '!=', 1);

                if (!empty($data['level_id'])) {
                    $query->where('level_of_description_id', $data['level_id']);
                }

                if (!empty($data['empty_scope_only'])) {
                    $query->whereNotExists(function($q) {
                        $q->select(Illuminate\Database\Capsule\Manager::raw(1))
                          ->from('information_object_i18n')
                          ->whereRaw('information_object_i18n.id = information_object.id')
                          ->whereNotNull('scope_and_content')
                          ->where('scope_and_content', '!=', '');
                    });
                }

                $objectIds = $query->limit($data['limit'] ?? 1000)->pluck('id')->toArray();
            } elseif (!empty($data['search_query'])) {
                // Search-based selection using title and scope_and_content
                $searchTerm = '%' . trim($data['search_query']) . '%';
                $query = Illuminate\Database\Capsule\Manager::table('information_object')
                    ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                    ->where('information_object.id', '!=', 1)
                    ->where('information_object_i18n.culture', '=', 'en')
                    ->where(function ($q) use ($searchTerm) {
                        $q->where('information_object_i18n.title', 'LIKE', $searchTerm)
                          ->orWhere('information_object_i18n.scope_and_content', 'LIKE', $searchTerm)
                          ->orWhere('information_object.identifier', 'LIKE', $searchTerm);
                    });

                // Optional repository filter
                if (!empty($data['repository_id'])) {
                    $query->where('information_object.repository_id', $data['repository_id']);
                }

                // Optional level filter
                if (!empty($data['level_id'])) {
                    $query->where('information_object.level_of_description_id', $data['level_id']);
                }

                // Filter for empty scope if requested
                if (!empty($data['empty_scope_only'])) {
                    $query->where(function ($q) {
                        $q->whereNull('information_object_i18n.scope_and_content')
                          ->orWhere('information_object_i18n.scope_and_content', '=', '');
                    });
                }

                $objectIds = $query->limit($data['limit'] ?? 1000)
                    ->pluck('information_object.id')
                    ->toArray();
            }

            if (empty($objectIds)) {
                $service->deleteBatch($batchId);
                return $this->renderText(json_encode(['success' => false, 'error' => 'No objects selected']));
            }

            // Add items
            $itemCount = $service->addItemsToBatch($batchId, $objectIds, $data['task_types']);

            // Auto-start if requested
            if (!empty($data['auto_start'])) {
                $service->startBatch($batchId);
            }

            return $this->renderText(json_encode([
                'success' => true,
                'batch_id' => $batchId,
                'item_count' => $itemCount,
                'message' => "Batch created with {$itemCount} items",
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Get batch details
     * GET /ai/batch/:id
     */
    public function executeBatchView($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $batchId = (int) $request->getParameter('id');
        $service = new \ahgAIPlugin\Services\JobQueueService();

        $this->batch = $service->getBatch($batchId);
        if (!$this->batch) {
            $this->forward404();
        }

        $this->stats = $service->getBatchStats($batchId);
        $this->jobs = $service->getBatchJobs($batchId, [], 100);
        $this->logs = $service->getLogEvents($batchId, 50);
        $this->taskTypes = $service::getTaskTypes();

        // Get object info for display
        $objectIds = array_map(function($job) { return $job->object_id; }, $this->jobs);
        $this->objects = [];
        if (!empty($objectIds)) {
            $objects = Illuminate\Database\Capsule\Manager::table('information_object')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->leftJoin('information_object_i18n', function($join) {
                    $join->on('information_object.id', '=', 'information_object_i18n.id')
                         ->where('information_object_i18n.culture', '=', 'en');
                })
                ->whereIn('information_object.id', $objectIds)
                ->select('information_object.id', 'slug.slug', 'information_object_i18n.title')
                ->get();
            foreach ($objects as $obj) {
                $this->objects[$obj->id] = $obj;
            }
        }
    }

    /**
     * Get batch progress (AJAX)
     * GET /ai/batch/:id/progress
     */
    public function executeBatchProgress($request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $batchId = (int) $request->getParameter('id');
        $service = new \ahgAIPlugin\Services\JobQueueService();

        $batch = $service->getBatch($batchId);
        if (!$batch) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Batch not found']));
        }

        $stats = $service->getBatchStats($batchId);

        return $this->renderText(json_encode([
            'success' => true,
            'status' => $batch->status,
            'progress_percent' => (float) $batch->progress_percent,
            'total' => (int) $batch->total_items,
            'completed' => (int) $batch->completed_items,
            'failed' => (int) $batch->failed_items,
            'stats' => $stats,
        ]));
    }

    /**
     * Batch action (start, pause, resume, cancel, retry)
     * POST /ai/batch/:id/action
     */
    public function executeBatchAction($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $batchId = (int) $request->getParameter('id');
        $data = json_decode($request->getContent(), true) ?: $request->getParameterHolder()->getAll();
        $action = $data['action'] ?? '';

        $service = new \ahgAIPlugin\Services\JobQueueService();

        try {
            switch ($action) {
                case 'start':
                    $result = $service->startBatch($batchId);
                    $message = $result ? 'Batch started' : 'Could not start batch';
                    break;

                case 'pause':
                    $result = $service->pauseBatch($batchId);
                    $message = $result ? 'Batch paused' : 'Could not pause batch';
                    break;

                case 'resume':
                    $result = $service->resumeBatch($batchId);
                    $message = $result ? 'Batch resumed' : 'Could not resume batch';
                    break;

                case 'cancel':
                    $result = $service->cancelBatch($batchId);
                    $message = $result ? 'Batch cancelled' : 'Could not cancel batch';
                    break;

                case 'retry':
                    $count = $service->retryFailed($batchId);
                    $result = $count > 0;
                    $message = $result ? "Retrying {$count} failed jobs" : 'No failed jobs to retry';
                    break;

                case 'delete':
                    $result = $service->deleteBatch($batchId);
                    $message = $result ? 'Batch deleted' : 'Could not delete batch';
                    break;

                default:
                    return $this->renderText(json_encode(['success' => false, 'error' => 'Unknown action']));
            }

            return $this->renderText(json_encode([
                'success' => $result,
                'message' => $message,
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Process next jobs (called by cron or manually)
     * POST /ai/batch/process
     */
    public function executeBatchProcess($request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $service = new \ahgAIPlugin\Services\JobQueueService();

        // Check server load
        if (!$service->checkServerLoad()) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Server under high load',
            ]));
        }

        // Get running batches
        $batches = $service->getBatches(['status' => 'running']);
        $processed = 0;

        foreach ($batches as $batch) {
            // Get pending jobs
            $jobs = Illuminate\Database\Capsule\Manager::table('ahg_ai_job')
                ->where('batch_id', $batch->id)
                ->where('status', 'pending')
                ->orderBy('priority')
                ->limit($batch->max_concurrent)
                ->get();

            foreach ($jobs as $job) {
                $result = $service->processJob($job->id);
                $processed++;

                // Apply delay
                if ($batch->delay_between_ms > 0) {
                    usleep($batch->delay_between_ms * 1000);
                }
            }
        }

        return $this->renderText(json_encode([
            'success' => true,
            'processed' => $processed,
        ]));
    }

    /**
     * Get job details
     * GET /ai/job/:id
     */
    public function executeJobView($request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once dirname(__FILE__).'/../../../lib/Services/JobQueueService.php';

        $jobId = (int) $request->getParameter('id');
        $service = new \ahgAIPlugin\Services\JobQueueService();

        $job = $service->getJob($jobId);
        if (!$job) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Job not found']));
        }

        // Get object info
        $object = Illuminate\Database\Capsule\Manager::table('information_object')
            ->join('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->where('information_object.id', $job->object_id)
            ->select('slug.slug', 'information_object_i18n.title')
            ->first();

        return $this->renderText(json_encode([
            'success' => true,
            'job' => $job,
            'object' => $object,
        ]));
    }

    // =========================================================================
    // NER PDF OVERLAY DISPLAY ACTIONS (Issue #20)
    // =========================================================================

    /**
     * Get approved/linked NER entities for an object
     * Used by the PDF overlay viewer to highlight entities on documents
     *
     * GET /ai/ner/approved-entities/:id
     */
    public function executeGetApprovedEntities($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        if (!$objectId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Object ID required']));
        }

        // Get approved and linked entities only
        $entities = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->whereIn('status', ['approved', 'linked'])
            ->select(
                'id',
                'entity_type',
                'entity_value',
                'confidence',
                'status',
                'linked_actor_id'
            )
            ->orderBy('entity_type')
            ->orderBy('entity_value')
            ->get()
            ->toArray();

        // Group by entity type for easier frontend processing
        $grouped = [];
        $typeConfig = [
            'PERSON' => ['color' => 'rgba(78, 121, 167, 0.35)', 'border' => '#4e79a7', 'label' => 'Person'],
            'PER' => ['color' => 'rgba(78, 121, 167, 0.35)', 'border' => '#4e79a7', 'label' => 'Person'],
            'ORG' => ['color' => 'rgba(89, 161, 79, 0.35)', 'border' => '#59a14f', 'label' => 'Organization'],
            'GPE' => ['color' => 'rgba(225, 87, 89, 0.35)', 'border' => '#e15759', 'label' => 'Place'],
            'LOC' => ['color' => 'rgba(225, 87, 89, 0.35)', 'border' => '#e15759', 'label' => 'Location'],
            'DATE' => ['color' => 'rgba(176, 122, 161, 0.35)', 'border' => '#b07aa1', 'label' => 'Date'],
            'TIME' => ['color' => 'rgba(176, 122, 161, 0.35)', 'border' => '#b07aa1', 'label' => 'Time'],
            'EVENT' => ['color' => 'rgba(118, 183, 178, 0.35)', 'border' => '#76b7b2', 'label' => 'Event'],
            'WORK_OF_ART' => ['color' => 'rgba(255, 157, 167, 0.35)', 'border' => '#ff9da7', 'label' => 'Work'],
        ];

        foreach ($entities as $entity) {
            $type = $entity->entity_type;
            if (!isset($grouped[$type])) {
                $config = $typeConfig[$type] ?? ['color' => 'rgba(186, 186, 186, 0.35)', 'border' => '#bababa', 'label' => $type];
                $grouped[$type] = [
                    'type' => $type,
                    'label' => $config['label'],
                    'color' => $config['color'],
                    'borderColor' => $config['border'],
                    'entities' => [],
                ];
            }
            $grouped[$type]['entities'][] = [
                'id' => $entity->id,
                'value' => $entity->entity_value,
                'confidence' => (float) $entity->confidence,
                'status' => $entity->status,
                'linkedActorId' => $entity->linked_actor_id,
            ];
        }

        // Get linked actor/term names for entities with linked_actor_id
        foreach ($grouped as $type => &$group) {
            foreach ($group['entities'] as &$entity) {
                if ($entity['linkedActorId']) {
                    $linkedName = $this->getLinkedEntityName($entity['linkedActorId'], $type);
                    if ($linkedName) {
                        $entity['linkedName'] = $linkedName;
                    }
                }
            }
        }

        return $this->renderText(json_encode([
            'success' => true,
            'object_id' => (int) $objectId,
            'entity_count' => count($entities),
            'entity_types' => array_values($grouped),
        ]));
    }

    /**
     * Get linked entity name (actor or term)
     */
    private function getLinkedEntityName($linkedId, $entityType)
    {
        if (!$linkedId) {
            return null;
        }

        // Try actor first
        if (in_array($entityType, ['PERSON', 'PER', 'ORG'])) {
            $actor = Illuminate\Database\Capsule\Manager::table('actor_i18n')
                ->where('id', $linkedId)
                ->where('culture', 'en')
                ->first();
            if ($actor) {
                return $actor->authorized_form_of_name;
            }
        }

        // Try term (for places, subjects)
        $term = Illuminate\Database\Capsule\Manager::table('term_i18n')
            ->where('id', $linkedId)
            ->where('culture', 'en')
            ->first();
        if ($term) {
            return $term->name;
        }

        return null;
    }

    /**
     * PDF Overlay Viewer - Display PDF with NER entity highlights
     *
     * GET /ai/ner/pdf-overlay/:id
     */
    public function executePdfOverlay($request)
    {
        $objectId = $request->getParameter('id');

        // Get information object
        $object = $this->getInformationObject($objectId);
        if (!$object) {
            $this->forward404('Object not found');
        }

        $this->object = $object;
        $this->objectId = $objectId;

        // Get digital object info
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->whereNull('parent_id')
            ->orderBy('id', 'desc')
            ->first();
        $this->docInfo = null;

        if ($digitalObject) {
            $mimeType = $digitalObject->mime_type ?? '';
            $isPdf = strpos($mimeType, 'pdf') !== false;

            // Get file path and name from digital object
            $doPath = $digitalObject->path ?? '';
            $doName = $digitalObject->name ?? '';

            // Build full path - path contains directory, name contains filename
            $webPath = rtrim($doPath, '/') . '/' . $doName;

            $rootDir = defined('SF_ROOT_DIR') ? SF_ROOT_DIR : '/usr/share/nginx/archive';
            // Get absolute path for page count
            $absolutePath = $rootDir . '/' . ltrim($webPath, '/');

            // Get page count for PDFs
            $pageCount = 1;
            if ($isPdf && $absolutePath && file_exists($absolutePath)) {
                // Try to get page count using pdfinfo
                $output = [];
                exec("pdfinfo " . escapeshellarg($absolutePath) . " 2>/dev/null | grep Pages", $output);
                if (!empty($output[0])) {
                    $pageCount = (int) preg_replace('/[^0-9]/', '', $output[0]);
                }
            }

            $this->docInfo = [
                'is_pdf' => $isPdf,
                'mime_type' => $mimeType,
                'url' => $webPath,
                'page_count' => $pageCount,
                'filename' => $doName,
            ];
        }

        // Get entity counts
        $entityCounts = Illuminate\Database\Capsule\Manager::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->whereIn('status', ['approved', 'linked'])
            ->select('entity_type', Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'))
            ->groupBy('entity_type')
            ->get()
            ->pluck('count', 'entity_type')
            ->toArray();

        $this->entityCounts = $entityCounts;
        $this->totalEntities = array_sum($entityCounts);
    }
}
