<?php if (isset($status) || isset($hasGeneratedFindingAid)) { ?>
  <?php if (isset($contextMenu) && $contextMenu) { ?>
    <h4 class="h5 mb-2"><?php echo __('Finding aid'); ?></h4>
    <ul class="list-unstyled">
      <?php if ($hasGeneratedFindingAid && $collectionRootSlug) { ?>
        <li>
          <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'findingAid', 'slug' => $collectionRootSlug]); ?>">
            <i class="fas fa-fw fa-file-pdf me-1" aria-hidden="true"></i><?php echo __('Download finding aid'); ?>
          </a>
        </li>
      <?php } ?>
      <?php if ($sf_user->isAdministrator() && $collectionRootSlug) { ?>
        <li>
          <a class="atom-icon-link" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'generateFindingAid', 'slug' => $collectionRootSlug]); ?>">
            <i class="fas fa-fw fa-cogs me-1" aria-hidden="true"></i><?php echo __('Generate finding aid'); ?>
          </a>
        </li>
      <?php } ?>
    </ul>
  <?php } else { ?>
    <?php if ($hasGeneratedFindingAid && $collectionRootSlug) { ?>
      <div class="field">
        <h3><?php echo __('Finding aid'); ?></h3>
        <div>
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'findingAid', 'slug' => $collectionRootSlug]); ?>">
            <?php echo __('Download finding aid'); ?>
          </a>
        </div>
      </div>
    <?php } ?>
  <?php } ?>
<?php } ?>
