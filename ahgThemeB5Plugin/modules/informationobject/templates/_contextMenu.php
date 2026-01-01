<?php if ($sf_user->getAttribute('search-realm') && sfConfig::get('app_enable_institutional_scoping')) { ?>
  <?php include_component('repository', 'holdingsInstitution', ['resource' => QubitRepository::getById($sf_user->getAttribute('search-realm'))]); ?>
<?php } else { ?>
  <?php echo get_component('repository', 'logo'); ?>
<?php } ?>
<?php echo get_component('informationobject', 'treeView'); ?>
<?php echo get_component('menu', 'staticPagesMenu'); ?>
<?php
// Check if a plugin is enabled
if (!function_exists('isPluginActive')) {
    function isPluginActive($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $conn = Propel::getConnection();
                $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
                $stmt->execute();
                $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {
                $plugins = [];
            }
        }
        return isset($plugins[$pluginName]);
    }
}

// Museum/CCO specific links for authenticated users
if (isset($resource)) {
  $resourceSlug = null;
  if ($resource instanceof QubitInformationObject) {
    $resourceSlug = $resource->slug;
  } elseif (is_object($resource) && isset($resource->slug)) {
    $resourceSlug = $resource->slug;
  }
  
  // Check which plugins are enabled
  $hasCco = isPluginActive('ahgCcoPlugin');
  $hasCondition = isPluginActive('ahgConditionPlugin');
  $hasSpectrum = isPluginActive('ahgSpectrumPlugin') || isPluginActive('sfMuseumPlugin');
  $hasGrap = isPluginActive('ahgGrapPlugin');
  $hasOais = isPluginActive('ahgOaisPlugin');
  $hasResearch = isPluginActive('ahgResearchPlugin');
  $hasDisplay = isPluginActive('ahgDisplayPlugin');
  
  // Only show section if at least one plugin is enabled
  if ($resourceSlug && ($hasCco || $hasCondition || $hasSpectrum || $hasGrap || $hasOais || $hasResearch || $hasDisplay)) {
?>
<section class="sidebar-widget">
  <h4><?php echo __('Collections Management'); ?></h4>
  <ul>
    <?php if ($hasCco): ?>
    <li><?php echo link_to(__('Provenance'), ['module' => 'cco', 'action' => 'provenance', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasCondition): ?>
    <li><?php echo link_to(__('Condition assessment'), ['module' => 'arCondition', 'action' => 'conditionCheck', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasSpectrum): ?>
    <li><?php echo link_to(__('Spectrum data'), ['module' => 'spectrum', 'action' => 'index', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasGrap): ?>
    <li><?php echo link_to(__('GRAP data'), ['module' => 'grap', 'action' => 'index', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasOais): ?>
    <li><?php echo link_to(__('Digital Preservation (OAIS)'), ['module' => 'oais', 'action' => 'createSip', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasResearch): ?>
    <li><?php echo link_to(__('Cite this Record'), ['module' => 'research', 'action' => 'cite', 'slug' => $resourceSlug]); ?></li>
    <?php endif; ?>
    <?php if ($hasDisplay): ?>
    <li><?php echo link_to(__('GLAM browser'), ['module' => 'display', 'action' => 'browse']); ?></li>
    <?php endif; ?>
  </ul>
</section>
<?php
  }
}
?>
<!-- EXTENDED RIGHTS CONTEXT MENU (Only if plugin enabled) -->
<?php if (isPluginActive('ahgExtendedRightsPlugin')): ?>
<?php include_partial('informationobject/extendedRightsContextMenu', ['resource' => $resource]); ?>
<?php endif; ?>
