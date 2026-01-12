<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RIC Sync Listener
 *
 * Listens to AtoM entity events and syncs changes to the RIC triplestore.
 * Uses singleton pattern with static handler methods for Symfony dispatcher.
 *
 * @package    ahgRicExplorerPlugin
 * @author     The AHG / Plain Sailing
 * @version    1.0.1
 */
class RicSyncListener
{
    protected static $instance = null;
    protected $syncService = null;
    protected $enabled = true;
    protected $useQueue = true;
    protected $initialized = false;

    protected const SYNCABLE_ENTITIES = [
        'QubitInformationObject' => 'informationobject',
        'QubitActor' => 'actor',
        'QubitRepository' => 'repository',
        'QubitFunction' => 'function',
        'QubitEvent' => 'event',
    ];

    // =========================================================================
    // STATIC HANDLERS (Called by Symfony event dispatcher)
    // =========================================================================

    /**
     * Static handler for save events (insert/update)
     */
    public static function handleSave(sfEvent $event): void
    {
        $instance = self::getInstance();
        $instance->onSave($event);
    }

    /**
     * Static handler for delete events
     */
    public static function handleDelete(sfEvent $event): void
    {
        $instance = self::getInstance();
        $instance->onDelete($event);
    }

    /**
     * Static handler for move events
     */
    public static function handleMove(sfEvent $event): void
    {
        $instance = self::getInstance();
        $instance->onMove($event);
    }

    // =========================================================================
    // SINGLETON
    // =========================================================================

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
        if ($this->initialized) {
            return;
        }

        try {
            $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBootstrap)) {
                require_once $frameworkBootstrap;
                $this->syncService = new \AtomFramework\Services\RicSyncService();
                $this->initialized = true;
                $this->log('RIC Sync: Service initialized successfully');
            } else {
                $this->log('RIC Sync: Framework bootstrap not found at ' . $frameworkBootstrap, 'err');
                $this->enabled = false;
            }
        } catch (\Exception $e) {
            $this->log('RIC Sync: Failed to initialize service: ' . $e->getMessage(), 'err');
            $this->enabled = false;
        }
    }

    protected function loadConfiguration(): void
    {
        try {
            // Load from ric_sync_config table
            $syncEnabled = DB::table('ric_sync_config')
                ->where('config_key', 'sync_enabled')
                ->value('config_value');
            $this->enabled = $syncEnabled === null || $syncEnabled === '1';

            $queueEnabled = DB::table('ric_sync_config')
                ->where('config_key', 'queue_enabled')
                ->value('config_value');
            $this->useQueue = $queueEnabled === null || $queueEnabled === '1';
        } catch (\Exception $e) {
            // Use defaults if table doesn't exist yet
            $this->enabled = true;
            $this->useQueue = true;
        }
    }

    // =========================================================================
    // INSTANCE HANDLERS
    // =========================================================================

    public function onSave(sfEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if (!isset(self::SYNCABLE_ENTITIES[$className])) {
            return;
        }

        $entityType = self::SYNCABLE_ENTITIES[$className];
        $entityId = $object->id;

        // Determine if this is create or update based on event name
        $eventName = $event->getName();
        $operation = strpos($eventName, '.insert.') !== false ? 'create' : 'update';

        $this->log("RIC Sync: Processing {$operation} for {$entityType}/{$entityId}");

        try {
            if ($this->useQueue) {
                $this->queueSync($entityType, $entityId, $operation);
                $this->log("RIC Sync: Queued {$operation} for {$entityType}/{$entityId}");
            } elseif ($this->syncService) {
                $this->syncService->syncRecord($entityType, $entityId, $operation);
                $this->log("RIC Sync: Immediate sync completed for {$entityType}/{$entityId}");
            }
        } catch (\Exception $e) {
            $this->log("RIC Sync: Failed to sync {$entityType}/{$entityId}: " . $e->getMessage(), 'err');
        }
    }

    public function onDelete(sfEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if (!isset(self::SYNCABLE_ENTITIES[$className])) {
            return;
        }

        $entityType = self::SYNCABLE_ENTITIES[$className];
        $entityId = $object->id;

        $this->log("RIC Sync: Processing delete for {$entityType}/{$entityId}");

        try {
            if ($this->useQueue) {
                $this->queueSync($entityType, $entityId, 'delete');
                $this->log("RIC Sync: Queued delete for {$entityType}/{$entityId}");
            } elseif ($this->syncService) {
                $this->syncService->handleDeletion($entityType, $entityId, true);
                $this->log("RIC Sync: Immediate delete completed for {$entityType}/{$entityId}");
            }
        } catch (\Exception $e) {
            $this->log("RIC Sync: Failed to delete {$entityType}/{$entityId}: " . $e->getMessage(), 'err');
        }
    }

    public function onMove(sfEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $object = $event->getSubject();
        $className = get_class($object);

        if ($className !== 'QubitInformationObject') {
            return;
        }

        $entityType = 'informationobject';
        $entityId = $object->id;
        $oldParentId = $event->offsetExists('oldParentId') ? $event->offsetGet('oldParentId') : null;
        $newParentId = $object->parentId;

        // Only process if parent actually changed
        if ($oldParentId === $newParentId) {
            return;
        }

        $this->log("RIC Sync: Processing move for {$entityType}/{$entityId} from {$oldParentId} to {$newParentId}");

        try {
            if ($this->useQueue) {
                $this->queueMove($entityType, $entityId, $oldParentId, $newParentId);
            } elseif ($this->syncService) {
                $this->syncService->handleMove($entityType, $entityId, $oldParentId, $newParentId);
            }
        } catch (\Exception $e) {
            $this->log("RIC Sync: Failed to handle move for {$entityType}/{$entityId}: " . $e->getMessage(), 'err');
        }
    }

    // =========================================================================
    // QUEUE OPERATIONS
    // =========================================================================

    protected function queueSync(string $entityType, int $entityId, string $operation): void
    {
        DB::table('ric_sync_queue')->insert([
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
        DB::table('ric_sync_queue')->insert([
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

    // =========================================================================
    // UTILITIES
    // =========================================================================

    protected function log(string $message, string $level = 'info'): void
    {
        try {
            if (sfContext::hasInstance()) {
                $logger = sfContext::getInstance()->getLogger();
                if ($level === 'err') {
                    $logger->err($message);
                } else {
                    $logger->info($message);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if logging not available
        }
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
