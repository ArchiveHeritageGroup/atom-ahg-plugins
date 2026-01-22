<?php
// Library-specific sidebar
?>
<?php echo get_component('repository', 'logo'); ?>

<!-- Library Hierarchy Navigation -->
<?php if ($resource): ?>
<section class="sidebar-widget">
  <h4><?php echo __('Holdings'); ?></h4>
  <ul class="list-group">
    <?php if ($resource->parent && $resource->parent->id != QubitInformationObject::ROOT_ID): ?>
    <li class="list-group-item">
      <i class="fas fa-level-up-alt me-2"></i>
      <?php echo link_to($resource->parent->getTitle(['cultureFallback' => true]) ?: '[Untitled]', ['module' => 'ahgLibraryPlugin', 'action' => 'index', 'slug' => $resource->parent->slug]); ?>
    </li>
    <?php endif; ?>
    <li class="list-group-item active">
      <i class="fas fa-book me-2"></i>
      <?php echo $resource->getTitle(['cultureFallback' => true]); ?>
    </li>
    <?php
      $children = $resource->getChildren();
      $totalChildren = count($children);
      $initialLimit = 10;
      $count = 0;
      foreach ($children as $child):
        $count++;
        $isHidden = $count > $initialLimit;
    ?>
    <li class="list-group-item ps-4<?php echo $isHidden ? ' holdings-extra d-none' : ''; ?>">
      <i class="fas fa-file me-2"></i>
      <?php echo link_to($child->getTitle(['cultureFallback' => true]) ?: '[Untitled]', ['module' => 'ahgLibraryPlugin', 'action' => 'index', 'slug' => $child->slug]); ?>
    </li>
    <?php endforeach; ?>
    <?php if ($totalChildren > $initialLimit): ?>
    <li class="list-group-item text-center holdings-toggle-container">
      <a href="#" class="holdings-show-more text-decoration-none" onclick="toggleHoldings(this); return false;">
        <i class="fas fa-chevron-down me-1"></i>
        <?php echo __('Show %1% more', ['%1%' => $totalChildren - $initialLimit]); ?>
      </a>
      <a href="#" class="holdings-show-less text-decoration-none d-none" onclick="toggleHoldings(this); return false;">
        <i class="fas fa-chevron-up me-1"></i>
        <?php echo __('Show less'); ?>
      </a>
    </li>
    <script>
    function toggleHoldings(el) {
      var container = el.closest('.sidebar-widget');
      var extras = container.querySelectorAll('.holdings-extra');
      var showMore = container.querySelector('.holdings-show-more');
      var showLess = container.querySelector('.holdings-show-less');

      extras.forEach(function(item) {
        item.classList.toggle('d-none');
      });
      showMore.classList.toggle('d-none');
      showLess.classList.toggle('d-none');
    }
    </script>
    <?php endif; ?>
  </ul>
</section>
<?php endif; ?>

<?php echo get_component('menu', 'staticPagesMenu'); ?>

<?php
// Collections Management section
if (!function_exists('isLibraryPluginActive')) {
    function isLibraryPluginActive($pluginName) {
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

$hasCondition = isLibraryPluginActive('ahgConditionPlugin');
$hasSpectrum = isLibraryPluginActive('ahgSpectrumPlugin');
$hasResearch = isLibraryPluginActive('ahgResearchPlugin');
$hasNer = isLibraryPluginActive('ahgNerPlugin');
$hasExtendedRights = isLibraryPluginActive('ahgExtendedRightsPlugin');

if ($resource && ($hasCondition || $hasSpectrum || $hasResearch)):
?>
<section class="sidebar-widget">
  <h4><?php echo __('Collections Management'); ?></h4>
  <ul>
    <?php if ($hasCondition): ?>
    <li><?php echo link_to(__('Condition assessment'), ['module' => 'ahgCondition', 'action' => 'conditionCheck', 'slug' => $resource->slug]); ?></li>
    <?php endif; ?>
    <?php if ($hasSpectrum): ?>
    <li><?php echo link_to(__('Spectrum data'), '/index.php/' . $resource->slug . '/spectrum'); ?></li>
    <?php endif; ?>
    <?php if ($hasResearch): ?>
    <li><?php echo link_to(__('Cite this Record'), ['module' => 'research', 'action' => 'cite', 'slug' => $resource->slug]); ?></li>
    <?php endif; ?>
  </ul>
</section>
<?php endif; ?>

<?php if ($hasNer && $sf_user->isAuthenticated()): ?>
<section class="sidebar-widget">
  <h4><?php echo __('Named Entity Recognition'); ?></h4>
  <ul>
    <li><a href="#" onclick="extractEntities(<?php echo $resource->id ?>); return false;"><i class="bi bi-cpu me-1"></i><?php echo __('Extract Entities'); ?></a></li>
    <li><a href="/ner/review"><i class="bi bi-list-check me-1"></i><?php echo __('Review Dashboard'); ?></a></li>
  </ul>
</section>
<?php endif; ?>

<?php if ($hasExtendedRights && $resource): ?>
<?php if (file_exists(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/modules/informationobject/templates/_extendedRightsContextMenu.php')) { include_partial('informationobject/extendedRightsContextMenu', ['resource' => $resource]); } ?>
<?php endif; ?>
