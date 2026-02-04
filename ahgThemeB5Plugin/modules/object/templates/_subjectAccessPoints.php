<?php
require_once sfConfig::get('sf_plugins_dir').'/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php';

// Load PII masking helper if privacy plugin is enabled
$piiEnabled = in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins());
if ($piiEnabled) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/helper/PiiHelper.php';
}

$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
if (!$resourceId) { return; }
$subjects = ahg_get_subject_access_points($resourceId);
if (empty($subjects)) { return; }
$isSidebar = isset($sidebar) && $sidebar;

// Get base path for URLs - AtoM uses /:slug routing
$basePath = sfContext::getInstance()->getRequest()->getScriptName();
?>
<?php if ($isSidebar) { ?>
  <section id="subjectAccessPointsSection">
    <h4><?php echo __('Subject access points'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($subjects as $subject) {
        $subjectName = $subject->name ?? '';
        $isMasked = false;
        if ($piiEnabled && function_exists('pii_mask_value')) {
            $maskResult = pii_mask_value($resourceId, $subjectName, 'PERSON');
            if ($maskResult['masked']) {
                $subjectName = $maskResult['value'];
                $isMasked = true;
            }
        }
      ?>
        <li>
          <?php if ($isMasked): ?>
            <span class="text-danger"><?php echo htmlspecialchars($subjectName); ?></span>
          <?php elseif ($subject->slug): ?>
            <a href="<?php echo $basePath; ?>/<?php echo rawurlencode($subject->slug); ?>"><?php echo htmlspecialchars($subjectName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($subjectName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
<div class="field<?php echo isset($sidebar) ? '' : ' '.render_b5_show_field_css_classes(); ?>">

  <?php echo render_b5_show_label(__('Subject access points')); ?>

  <div<?php echo isset($sidebar) ? '' : ' class="'.render_b5_show_value_css_classes().'"'; ?>>
    <ul class="<?php echo isset($sidebar) ? 'list-unstyled' : render_b5_show_list_css_classes(); ?>">
      <?php foreach ($subjects as $subject) {
        $subjectName = $subject->name ?? '';
        $isMasked = false;
        if ($piiEnabled && function_exists('pii_mask_value')) {
            $maskResult = pii_mask_value($resourceId, $subjectName, 'PERSON');
            if ($maskResult['masked']) {
                $subjectName = $maskResult['value'];
                $isMasked = true;
            }
        }
      ?>
        <li>
          <?php if ($isMasked): ?>
            <span class="text-danger"><?php echo htmlspecialchars($subjectName); ?></span>
          <?php elseif ($subject->slug): ?>
            <a href="<?php echo $basePath; ?>/<?php echo rawurlencode($subject->slug); ?>"><?php echo htmlspecialchars($subjectName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($subjectName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul>
  </div>

</div>
<?php } ?>
