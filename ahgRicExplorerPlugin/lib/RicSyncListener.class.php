<?php
use Illuminate\Database\Capsule\Manager as DB;

class RicSyncListener
{
    protected static $instance = null;
    protected $syncService = null;
    protected $enabled = true;
    protected $useQueue = true;

    protected const SYNCABLE_ENTITIES = [
        'QubitInformationObject' => 'informationobject',
        'QubitActor' => 'actor',
        'QubitRepository' => 'repository',
        'QubitFunction' => 'function',
        'QubitEvent' => 'event',
    ];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        $this->initializeService();
        $this->loadConfiguration();
    }

    protected function initializeService(): void
    {
        try {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            $this->syncService = new \AtomFramework\Services\RicSyncService();
        } catch (\Exception $e) {
            sfContext::getInstance()->getLogger()->err('RIC Sync: Failed to initialize service: ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    protected function loadConfiguration(): void
    {
        try {
            $config = DB::table('setting')->join('setting_i18n', 'setting_i18n.id', '=', 'setting.id')->where('setting.name', 'ric_sync_enabled')->value('setting_i18n.value');
            $this->enabled = $config ? (bool)$config->getValue(['sourceCulture' => true]) : true;

            $queueConfig = DB::table('setting')->join('setting_i18n', 'setting_i18n.id', '=', 'setting.id')->where('setting.name', 'ric_queue_enabled')->value('setting_i18n.value');
            $this->useQueue = $queueConfig ? (bool)$queueConfig->getValue(['sourceCulture' => true]) : true;
        } catch (\Exception $e) {
            // Use defaults
        }
    }

    public function onSave(sfEvent $event): void
    {
        if (!$this->enabled || !$this->syncService) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if (!isset(self::SYNCABLE_ENTITIES[$className])) {
            return;
        }

        $entityType = self::SYNCABLE_ENTITIES[$className];
        $entityId = $object->id;
        $operation = $object->isNew() ? 'create' : 'update';

        try {
            if ($this->useQueue) {
                $this->queueSync($entityType, $entityId, $operation);
            } else {
                $this->syncService->syncRecord($entityType, $entityId, $operation);
            }
        } catch (\Exception $e) {
            sfContext::getInstance()->getLogger()->err(
                'RIC Sync: Failed to sync ' . $entityType . '/' . $entityId . ': ' . $e->getMessage()
            );
        }
    }

    public function onDelete(sfEvent $event): void
    {
        if (!$this->enabled || !$this->syncService) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if (!isset(self::SYNCABLE_ENTITIES[$className])) {
            return;
        }

        $entityType = self::SYNCABLE_ENTITIES[$className];
        $entityId = $object->id;

        try {
            if ($this->useQueue) {
                $this->queueSync($entityType, $entityId, 'delete');
            } else {
                $this->syncService->handleDeletion($entityType, $entityId, true);
            }
        } catch (\Exception $e) {
            sfContext::getInstance()->getLogger()->err(
                'RIC Sync: Failed to delete ' . $entityType . '/' . $entityId . ': ' . $e->getMessage()
            );
        }
    }

    public function onMove(sfEvent $event): void
    {
        if (!$this->enabled || !$this->syncService) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if (!isset(self::SYNCABLE_ENTITIES[$className])) {
            return;
        }

        $entityType = self::SYNCABLE_ENTITIES[$className];
        $entityId = $object->id;
        $oldParentId = $event->offsetGet('oldParentId');
        $newParentId = $object->parentId;

        try {
            if ($this->useQueue) {
                $this->queueMove($entityType, $entityId, $oldParentId, $newParentId);
            } else {
                $this->syncService->handleMove($entityType, $entityId, $oldParentId, $newParentId);
            }
        } catch (\Exception $e) {
            sfContext::getInstance()->getLogger()->err(
                'RIC Sync: Failed to handle move for ' . $entityType . '/' . $entityId . ': ' . $e->getMessage()
            );
        }
    }

    protected function queueSync(string $entityType, int $entityId, string $operation): void
    {
        \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'priority' => $operation === 'delete' ? 1 : 5,
            'status' => 'queued',
            'scheduled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function queueMove(string $entityType, int $entityId, ?int $oldParentId, ?int $newParentId): void
    {
        \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => 'move',
            'old_parent_id' => $oldParentId,
            'new_parent_id' => $newParentId,
            'priority' => 3,
            'status' => 'queued',
            'scheduled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }
}

class RicSyncBehavior
{
    public static function postSave(BaseObject $object): void
    {
        $listener = RicSyncListener::getInstance();
        if (!$listener->isEnabled()) {
            return;
        }
        $event = new sfEvent($object, get_class($object) . '.save');
        $listener->onSave($event);
    }

    public static function preDelete(BaseObject $object): void
    {
        $listener = RicSyncListener::getInstance();
        if (!$listener->isEnabled()) {
            return;
        }
        $event = new sfEvent($object, get_class($object) . '.delete');
        $listener->onDelete($event);
    }
}
