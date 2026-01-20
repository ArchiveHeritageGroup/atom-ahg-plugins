<?php
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

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
            <a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $subject->slug]); ?>"><?php echo htmlspecialchars($subjectName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($subjectName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
  <div class="field">
    <h3><?php echo __('Subject access points'); ?></h3>
    <div><ul>
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
            <a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $subject->slug]); ?>"><?php echo htmlspecialchars($subjectName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($subjectName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul></div>
  </div>
<?php } ?>
