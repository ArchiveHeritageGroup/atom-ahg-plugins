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

<?php
// Unique per entity so several panels on one page cannot collide.
$cfPanelId = 'cf-panel-' . preg_replace('/[^a-z0-9]+/i', '-', $entityType) . '-' . $objectId;
?>
<section class="card mb-3">
    <div class="card-header p-0">
        <?php // Open by default. This panel returns early when the record has no
              // values, so if it renders at all there IS data - and starting
              // collapsed hid real content and made populated records look empty.
              // The toggle stays, for collapsing a long panel out of the way. ?>
        <button class="btn btn-link w-100 text-start text-decoration-none d-flex align-items-center justify-content-between p-3"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?php echo $cfPanelId; ?>"
                aria-expanded="true"
                aria-controls="<?php echo $cfPanelId; ?>">
            <span class="h5 mb-0"><i class="bi bi-input-cursor-text"></i> <?php echo __('Additional Fields'); ?></span>
            <i class="bi bi-chevron-down cf-panel-chevron" aria-hidden="true"></i>
        </button>
    </div>
    <div id="<?php echo $cfPanelId; ?>" class="collapse show">
        <div class="card-body">
            <?php echo $html; ?>
        </div>
    </div>
</section>
