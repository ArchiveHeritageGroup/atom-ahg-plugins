<?php if (count($tree) > 0): ?>
  <ul class="list-group">
    <?php foreach ($tree as $location): ?>
      <li class="list-group-item">
        <div class="d-flex justify-content-between align-items-center">
          <?php echo link_to($location['name'], 'storageLocation/view?id=' . $location['id']) ?>
          <span class="badge badge-secondary"><?php echo __($location['location_type']) ?></span>
        </div>
        
        <?php if (isset($location['children']) && count($location['children']) > 0): ?>
          <ul class="list-group mt-2">
            <?php include_partial('storageLocation/tree', array('tree' => $location['children'])) ?>
          </ul>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p><?php echo __('No storage locations available.') ?></p>
<?php endif; ?>