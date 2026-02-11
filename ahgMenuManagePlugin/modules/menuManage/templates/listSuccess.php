<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Menus'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if ($sf_user->hasFlash('notice')) { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $sf_user->getFlash('notice'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php } ?>

  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $sf_user->getFlash('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
    </div>
  <?php } ?>

  <div class="table-responsive mb-3">
    <table class="table table-bordered table-sm mb-0">
      <thead>
        <tr>
          <th><?php echo __('Name / Label'); ?></th>
          <th><?php echo __('Path'); ?></th>
          <th class="text-center" style="width: 100px;"><?php echo __('Reorder'); ?></th>
          <th class="text-end" style="width: 140px;"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $rawTree = $sf_data->getRaw('menuTree'); ?>
        <?php if (empty($rawTree)) { ?>
          <tr>
            <td colspan="4" class="text-center text-muted">
              <?php echo __('No menu items found.'); ?>
            </td>
          </tr>
        <?php } else { ?>
          <?php foreach ($rawTree as $item) { ?>
            <tr>
              <td>
                <?php
                  $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $item['depth']);
                  $depthIndicator = $item['depth'] > 0 ? str_repeat('- ', $item['depth']) : '';
                  $displayLabel = $item['label'] ?: $item['name'] ?: __('(unnamed)');
                ?>
                <?php echo $indent; ?>
                <?php if ($item['isProtected']) { ?>
                  <i class="fas fa-lock text-muted me-1" title="<?php echo __('Protected'); ?>"></i>
                <?php } ?>
                <?php if ($item['hasChildren']) { ?>
                  <i class="fas fa-folder-open text-warning me-1"></i>
                <?php } ?>
                <?php echo link_to(
                    esc_specialchars($displayLabel),
                    '@menu_edit?id=' . $item['id'],
                    ['class' => 'text-decoration-none']
                ); ?>
                <?php if ($item['name']) { ?>
                  <span class="text-muted small ms-1">(<?php echo esc_specialchars($item['name']); ?>)</span>
                <?php } ?>
              </td>
              <td>
                <?php if ($item['path']) { ?>
                  <code class="small"><?php echo esc_specialchars($item['path']); ?></code>
                <?php } else { ?>
                  <span class="text-muted">-</span>
                <?php } ?>
              </td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                  <?php if ($item['prevSiblingId']) { ?>
                    <?php echo link_to(
                        '<i class="fas fa-arrow-up"></i>',
                        '@menu_list?move=' . $item['id'] . '&before=' . $item['prevSiblingId'],
                        ['class' => 'btn btn-sm atom-btn-outline-light', 'title' => __('Move up')]
                    ); ?>
                  <?php } else { ?>
                    <span class="btn btn-sm atom-btn-outline-light disabled"><i class="fas fa-arrow-up"></i></span>
                  <?php } ?>
                  <?php if ($item['nextSiblingId']) { ?>
                    <?php echo link_to(
                        '<i class="fas fa-arrow-down"></i>',
                        '@menu_list?move=' . $item['id'] . '&after=' . $item['nextSiblingId'],
                        ['class' => 'btn btn-sm atom-btn-outline-light', 'title' => __('Move down')]
                    ); ?>
                  <?php } else { ?>
                    <span class="btn btn-sm atom-btn-outline-light disabled"><i class="fas fa-arrow-down"></i></span>
                  <?php } ?>
                </div>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <?php echo link_to(
                      __('Edit'),
                      '@menu_edit?id=' . $item['id'],
                      ['class' => 'btn btn-sm atom-btn-outline-light']
                  ); ?>
                  <?php if (!$item['isProtected']) { ?>
                    <?php echo link_to(
                        __('Delete'),
                        '@menu_delete?id=' . $item['id'],
                        ['class' => 'btn btn-sm atom-btn-outline-danger']
                    ); ?>
                  <?php } ?>
                </div>
              </td>
            </tr>
          <?php } ?>
        <?php } ?>
      </tbody>
    </table>
  </div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>

  <section class="actions mb-3">
    <?php echo link_to(__('Add new'), '@menu_add', ['class' => 'btn atom-btn-outline-light']); ?>
  </section>

<?php end_slot(); ?>
