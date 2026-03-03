<?php
/**
 * Display panel template for custom fields.
 * Loaded by the display panel system via extension.json.
 *
 * Available variables from display panel system:
 *   $resource - the entity object
 *   $context  - the display context (informationobject, actor, accession, repository)
 */

$pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin';
require_once $pluginDir . '/lib/Repository/FieldDefinitionRepository.php';
require_once $pluginDir . '/lib/Repository/FieldValueRepository.php';
require_once $pluginDir . '/lib/Service/CustomFieldService.php';
require_once $pluginDir . '/lib/Service/CustomFieldRenderService.php';

// Determine entity type from context
$entityType = $context ?? 'informationobject';
$objectId = isset($resource->id) ? (int) $resource->id : 0;

if ($objectId <= 0) {
    return;
}

$renderService = new \AhgCustomFieldsPlugin\Service\CustomFieldRenderService();
$html = $renderService->renderViewFields($entityType, $objectId, true);

if (empty($html)) {
    return;
}
?>

<section class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-input-cursor-text"></i> Additional Fields</h5>
    </div>
    <div class="card-body">
        <?php echo $html; ?>
    </div>
</section>
