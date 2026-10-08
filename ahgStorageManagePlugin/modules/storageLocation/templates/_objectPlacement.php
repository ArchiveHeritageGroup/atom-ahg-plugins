<?php
/*
 * A physical object's place in the storage location tree, and its latest moves.
 * Include with ['objectId' => $resource->id] on the physical object's page.
 */
$placement = \AhgStorageManage\Services\StoragePlacementService::placementView((int) $objectId);
if (null === $placement || (!$placement['path'] && !$placement['moves'])) {
    return;
}
?>
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Storage location'); ?></h5>
  </div>
  <div class="card-body">
    <?php if ($placement['path']) { ?>
      <nav aria-label="<?php echo __('Storage location'); ?>">
        <ol class="breadcrumb mb-3">
          <?php foreach ($placement['path'] as $place) { ?>
            <li class="breadcrumb-item">
              <a href="<?php echo url_for('storageLocation/view?id='.(int) $place['id']); ?>"><?php echo esc_entities($place['name']); ?></a>
            </li>
          <?php } ?>
        </ol>
      </nav>
    <?php } else { ?>
      <p class="mb-3 text-muted"><?php echo __('Not in storage.'); ?></p>
    <?php } ?>

    <?php if ($placement['moves']) { ?>
      <h6><?php echo __('Latest moves'); ?></h6>
      <ul class="list-unstyled small mb-0">
        <?php foreach ($placement['moves'] as $move) { ?>
          <li>
            <?php echo esc_entities(substr((string) $move['moved_at'], 0, 16)); ?>:
            <?php echo esc_entities($move['from_location_name'] ?? __('not in storage')); ?>
            &rarr; <?php echo esc_entities($move['to_location_name'] ?? __('not in storage')); ?>
            <?php if (!empty($move['username'])) { ?>(<?php echo esc_entities($move['username']); ?>)<?php } ?>
          </li>
        <?php } ?>
      </ul>
    <?php } ?>
  </div>
</div>
