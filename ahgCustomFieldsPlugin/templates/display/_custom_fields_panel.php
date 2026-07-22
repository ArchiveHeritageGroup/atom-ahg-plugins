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
// Render as a standard ISAD descriptive area, not a Bootstrap card, so the
// heading matches every other section on the page ("Notes area", "Content and
// structure area", ...). render_b5_section_heading is the same helper those
// sections use, so the look is identical rather than merely similar. The plain
// heading has no collapse toggle - neither do the standard sections.
?>
<section id="additionalFieldsArea" class="border-bottom">
    <?php if (function_exists('render_b5_section_heading')): ?>
        <?php echo render_b5_section_heading(__('Additional Fields')); ?>
    <?php else: ?>
        <h2 class="h5 mb-0 atom-section-header"><div class="d-flex p-3 border-bottom text-primary"><?php echo __('Additional Fields'); ?></div></h2>
    <?php endif; ?>

    <?php echo $html; ?>
</section>
