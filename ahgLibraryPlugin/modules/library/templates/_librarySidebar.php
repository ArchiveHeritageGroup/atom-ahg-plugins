<?php
// Library-specific sidebar
?>
<?php echo get_component('repository', 'logo'); ?>

<!-- Library Hierarchy Navigation -->
<?php if ($resource): ?>
<?php
  $children = $resource->getChildren();
  $totalChildren = count($children);
  $hasMany = $totalChildren > 10;
?>
<section class="sidebar-widget">
  <h4>
    <?php echo __('Holdings'); ?>
    <?php if ($totalChildren > 0): ?>
      <span class="badge bg-secondary float-end"><?php echo $totalChildren; ?></span>
    <?php endif; ?>
  </h4>

  <!-- Parent link (outside scrollable area) -->
  <?php if ($resource->parent && $resource->parent->id != QubitInformationObject::ROOT_ID): ?>
  <div class="list-group mb-2">
    <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $resource->parent->slug]); ?>" class="list-group-item list-group-item-action">
      <i class="fas fa-level-up-alt me-2"></i>
      <?php echo $resource->parent->getTitle(['cultureFallback' => true]) ?: '[Untitled]'; ?>
    </a>
  </div>
  <?php endif; ?>

  <!-- Current item header -->
  <div class="list-group-item active mb-2">
    <i class="fas fa-book me-2"></i>
    <?php echo $resource->getTitle(['cultureFallback' => true]); ?>
  </div>

  <!-- Scrollable children list -->
  <?php if ($totalChildren > 0): ?>
  <div class="holdings-scroll-container<?php echo $hasMany ? ' has-scroll' : ''; ?>" style="<?php echo $hasMany ? 'max-height: 300px; overflow-y: auto;' : ''; ?>">
    <ul class="list-group list-group-flush">
      <?php foreach ($children as $child): ?>
      <li class="list-group-item list-group-item-action ps-4 py-2">
        <i class="fas fa-file me-2 text-muted"></i>
        <?php echo link_to($child->getTitle(['cultureFallback' => true]) ?: '[Untitled]', ['module' => 'library', 'action' => 'index', 'slug' => $child->slug]); ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php if ($hasMany): ?>
  <div class="text-center text-muted small mt-2">
    <i class="fas fa-arrows-alt-v me-1"></i><?php echo __('Scroll to see all %1% items', ['%1%' => $totalChildren]); ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</section>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.holdings-scroll-container.has-scroll {
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
}
.holdings-scroll-container.has-scroll::-webkit-scrollbar {
  width: 8px;
}
.holdings-scroll-container.has-scroll::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}
.holdings-scroll-container.has-scroll::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}
.holdings-scroll-container.has-scroll::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
<?php endif; ?>

<?php echo get_component('menu', 'staticPagesMenu'); ?>

<?php
use Illuminate\Database\Capsule\Manager as DB;
// Collections Management section
if (!function_exists('isLibraryPluginActive')) {
    function isLibraryPluginActive($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $pluginNames = DB::table('atom_plugin')
                    ->where('is_enabled', 1)
                    ->pluck('name')
                    ->toArray();
                $plugins = array_flip($pluginNames);
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
$hasNer = isLibraryPluginActive('ahgAIPlugin');
$hasExtendedRights = isLibraryPluginActive('ahgExtendedRightsPlugin');

if ($resource && ($hasCondition || $hasSpectrum || $hasResearch)):
?>
<section class="sidebar-widget">
  <h4><?php echo __('Collections Management'); ?></h4>
  <ul>
    <?php if ($hasCondition): ?>
    <li><a href="<?php echo url_for(['module' => 'condition', 'action' => 'conditionCheck', 'slug' => $resource->slug]); ?>"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Condition assessment'); ?></a></li>
    <?php endif; ?>
    <?php if ($hasSpectrum): ?>
    <li><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Spectrum data'); ?></a></li>
    <li><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug]); ?>"><i class="fas fa-tasks me-2"></i><?php echo __('Workflow Status'); ?></a></li>
    <?php endif; ?>
    <?php if (isLibraryPluginActive('ahgProvenancePlugin')): ?>
    <li><a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]); ?>"><i class="fas fa-sitemap me-2"></i><?php echo __('Provenance'); ?></a></li>
    <?php endif; ?>
    <?php if ($hasResearch): ?>
    <li><a href="<?php echo url_for(['module' => 'research', 'action' => 'cite', 'slug' => $resource->slug]); ?>"><i class="fas fa-quote-left me-2"></i><?php echo __('Cite this Record'); ?></a></li>
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
