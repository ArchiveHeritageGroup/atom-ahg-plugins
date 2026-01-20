<?php
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

// Load PII masking helper if privacy plugin is enabled
$piiEnabled = in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins());
if ($piiEnabled) {
    require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/helper/PiiHelper.php';
}

$resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
if (!$resourceId) { return; }
$places = ahg_get_place_access_points($resourceId);
if (empty($places)) { return; }
$isSidebar = isset($sidebar) && $sidebar;
?>
<?php if ($isSidebar) { ?>
  <section id="placeAccessPointsSection">
    <h4><?php echo __('Place access points'); ?></h4>
    <ul class="list-unstyled">
      <?php foreach ($places as $place) {
        $placeName = $place->name ?? '';
        $isMasked = false;
        if ($piiEnabled && function_exists('pii_mask_value')) {
            $maskResult = pii_mask_value($resourceId, $placeName, 'GPE');
            if ($maskResult['masked']) {
                $placeName = $maskResult['value'];
                $isMasked = true;
            }
        }
      ?>
        <li>
          <?php if ($isMasked): ?>
            <span class="text-danger"><?php echo htmlspecialchars($placeName); ?></span>
          <?php elseif ($place->slug): ?>
            <a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $place->slug]); ?>"><?php echo htmlspecialchars($placeName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($placeName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
  <div class="field">
    <h3><?php echo __('Place access points'); ?></h3>
    <div><ul>
      <?php foreach ($places as $place) {
        $placeName = $place->name ?? '';
        $isMasked = false;
        if ($piiEnabled && function_exists('pii_mask_value')) {
            $maskResult = pii_mask_value($resourceId, $placeName, 'GPE');
            if ($maskResult['masked']) {
                $placeName = $maskResult['value'];
                $isMasked = true;
            }
        }
      ?>
        <li>
          <?php if ($isMasked): ?>
            <span class="text-danger"><?php echo htmlspecialchars($placeName); ?></span>
          <?php elseif ($place->slug): ?>
            <a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $place->slug]); ?>"><?php echo htmlspecialchars($placeName); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($placeName); ?>
          <?php endif; ?>
        </li>
      <?php } ?>
    </ul></div>
  </div>
<?php } ?>
