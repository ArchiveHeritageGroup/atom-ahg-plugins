<?php

namespace AhgAPI\Services;

/**
 * WebhookEventListener - Triggers webhooks on entity events
 *
 * This class provides static methods to trigger webhooks when
 * entities are created, updated, or deleted.
 *
 * Usage in save/delete methods:
 * ```php
 * // After saving an information object
 * WebhookEventListener::onEntitySaved('informationobject', $id, $isNew);
 *
 * // After deleting an information object
 * WebhookEventListener::onEntityDeleted('informationobject', $id, $data);
 * ```
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class WebhookEventListener
{
    /**
     * Called when an entity is saved (created or updated)
     *
     * @param string $entityType Entity type (informationobject, actor, repository, etc.)
     * @param int $entityId Entity ID
     * @param bool $isNew Whether this is a new entity (create) or existing (update)
     * @param array $data Additional data to include in payload
     */
    public static function onEntitySaved(string $entityType, int $entityId, bool $isNew, array $data = []): void
    {
        try {
            $event = $isNew ? WebhookService::EVENT_CREATED : WebhookService::EVENT_UPDATED;

            // Build payload with entity data
            $payload = array_merge([
                'id' => $entityId,
                'action' => $isNew ? 'created' : 'updated',
            ], $data);

            // Add slug if available
            if (isset($data['slug'])) {
                $payload['slug'] = $data['slug'];
            }

            WebhookService::trigger($event, $entityType, $entityId, $payload);
        } catch (\Exception $e) {
            error_log('WebhookEventListener::onEntitySaved error: ' . $e->getMessage());
        }
    }

    /**
     * Called when an entity is deleted
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $data Additional data (e.g., title, slug before deletion)
     */
    public static function onEntityDeleted(string $entityType, int $entityId, array $data = []): void
    {
        try {
            $payload = array_merge([
                'id' => $entityId,
                'action' => 'deleted',
            ], $data);

            WebhookService::trigger(WebhookService::EVENT_DELETED, $entityType, $entityId, $payload);
        } catch (\Exception $e) {
            error_log('WebhookEventListener::onEntityDeleted error: ' . $e->getMessage());
        }
    }

    /**
     * Called when an entity is published
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $data Additional data
     */
    public static function onEntityPublished(string $entityType, int $entityId, array $data = []): void
    {
        try {
            $payload = array_merge([
                'id' => $entityId,
                'action' => 'published',
            ], $data);

            WebhookService::trigger(WebhookService::EVENT_PUBLISHED, $entityType, $entityId, $payload);
        } catch (\Exception $e) {
            error_log('WebhookEventListener::onEntityPublished error: ' . $e->getMessage());
        }
    }

    /**
     * Called when an entity is unpublished
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $data Additional data
     */
    public static function onEntityUnpublished(string $entityType, int $entityId, array $data = []): void
    {
        try {
            $payload = array_merge([
                'id' => $entityId,
                'action' => 'unpublished',
            ], $data);

            WebhookService::trigger(WebhookService::EVENT_UNPUBLISHED, $entityType, $entityId, $payload);
        } catch (\Exception $e) {
            error_log('WebhookEventListener::onEntityUnpublished error: ' . $e->getMessage());
        }
    }

    /**
     * Helper to build payload from QubitInformationObject
     *
     * @param \QubitInformationObject $object The information object
     * @return array Payload data
     */
    public static function buildInformationObjectPayload($object): array
    {
        return [
            'id' => $object->id,
            'slug' => $object->slug,
            'title' => $object->getTitle(['cultureFallback' => true]),
            'identifier' => $object->identifier,
            'level_of_description_id' => $object->levelOfDescriptionId,
            'repository_id' => $object->repositoryId,
            'parent_id' => $object->parentId,
            'publication_status_id' => $object->publicationStatusId,
            'updated_at' => date('c'),
        ];
    }

    /**
     * Helper to build payload from QubitActor
     *
     * @param \QubitActor $actor The actor
     * @return array Payload data
     */
    public static function buildActorPayload($actor): array
    {
        return [
            'id' => $actor->id,
            'slug' => $actor->slug,
            'authorized_form_of_name' => $actor->getAuthorizedFormOfName(['cultureFallback' => true]),
            'entity_type_id' => $actor->entityTypeId,
            'updated_at' => date('c'),
        ];
    }

    /**
     * Helper to build payload from QubitRepository
     *
     * @param \QubitRepository $repository The repository
     * @return array Payload data
     */
    public static function buildRepositoryPayload($repository): array
    {
        return [
            'id' => $repository->id,
            'slug' => $repository->slug,
            'authorized_form_of_name' => $repository->getAuthorizedFormOfName(['cultureFallback' => true]),
            'identifier' => $repository->identifier,
            'updated_at' => date('c'),
        ];
    }

    /**
     * Connect to Symfony event dispatcher
     *
     * Call this in the plugin configuration to automatically
     * hook into AtoM save/delete events.
     *
     * @param \sfEventDispatcher $dispatcher The event dispatcher
     */
    public static function connect(\sfEventDispatcher $dispatcher): void
    {
        // Note: AtoM doesn't have built-in save/delete events for all objects.
        // This method is provided for future use if such events are added.
        // Currently, webhook triggers must be called manually from controllers
        // or via the API actions.

        // Example if AtoM had events:
        // $dispatcher->connect('information_object.save', [self::class, 'handleInformationObjectSave']);
        // $dispatcher->connect('information_object.delete', [self::class, 'handleInformationObjectDelete']);
    }
}
