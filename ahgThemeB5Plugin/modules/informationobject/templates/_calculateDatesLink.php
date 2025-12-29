<?php if ($sf_user->isAdministrator() && isset($hasChildren) && $hasChildren && isset($slug)) { ?>
  <?php if (isset($contextMenu) && $contextMenu) { ?>
    <h4 class="h5 mb-2"><?php echo __('Calculate dates'); ?></h4>
    <ul class="list-unstyled">
      <li>
        <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'calculateDates', 'slug' => $slug]); ?>">
          <i class="fas fa-fw fa-calendar me-1" aria-hidden="true"></i><?php echo __('Calculate dates'); ?>
        </a>
      </li>
    </ul>
  <?php } ?>
<?php } ?>
