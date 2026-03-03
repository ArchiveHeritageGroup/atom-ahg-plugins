<?php
/**
 * Custom fields view partial (read-only display).
 * Include in entity view pages.
 *
 * Required variables: $entityType (string), $objectId (int)
 * Optional: $publicOnly (bool, default true)
 */

$pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin';
require_once $pluginDir . '/lib/Repository/FieldDefinitionRepository.php';
require_once $pluginDir . '/lib/Repository/FieldValueRepository.php';
require_once $pluginDir . '/lib/Service/CustomFieldService.php';
require_once $pluginDir . '/lib/Service/CustomFieldRenderService.php';

$renderService = new \AhgCustomFieldsPlugin\Service\CustomFieldRenderService();
$publicOnly = $publicOnly ?? true;
$html = $renderService->renderViewFields($entityType, $objectId, $publicOnly);

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
