<?php
require_once sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/lib/helper/AhgLaravelHelper.php';

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
      <?php foreach ($subjects as $subject) { ?>
        <li><?php if ($subject->slug) { ?><a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $subject->slug]); ?>"><?php echo htmlspecialchars($subject->name ?? ''); ?></a><?php } else { echo htmlspecialchars($subject->name ?? ''); } ?></li>
      <?php } ?>
    </ul>
  </section>
<?php } else { ?>
  <div class="field">
    <h3><?php echo __('Subject access points'); ?></h3>
    <div><ul>
      <?php foreach ($subjects as $subject) { ?>
        <li><?php if ($subject->slug) { ?><a href="<?php echo url_for(['module' => 'term', 'action' => 'index', 'slug' => $subject->slug]); ?>"><?php echo htmlspecialchars($subject->name ?? ''); ?></a><?php } else { echo htmlspecialchars($subject->name ?? ''); } ?></li>
      <?php } ?>
    </ul></div>
  </div>
<?php } ?>
