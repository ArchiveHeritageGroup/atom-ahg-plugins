<?php decorate_with('layout_2col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Static pages'); ?></h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <div class="sidebar-content">
    <h3><?php echo __('Admin'); ?></h3>
    <p class="text-muted small"><?php echo __('Manage static pages displayed on the site. Protected pages (e.g. Home) cannot be deleted.'); ?></p>
  </div>
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
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th><?php echo __('Title'); ?></th>
          <th><?php echo __('Slug'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php $rawPages = $sf_data->getRaw('pages'); ?>
        <?php if (empty($rawPages)) { ?>
          <tr>
            <td colspan="3" class="text-center text-muted">
              <?php echo __('No static pages found.'); ?>
            </td>
          </tr>
        <?php } else { ?>
          <?php foreach ($rawPages as $page) { ?>
            <tr>
              <td>
                <?php echo link_to(
                    esc_specialchars($page['title'] ?: __('Untitled')),
                    '@staticpage_edit?id=' . $page['id'],
                    ['class' => 'text-decoration-none']
                ); ?>
              </td>
              <td>
                <code><?php echo esc_specialchars($page['slug'] ?? ''); ?></code>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm" role="group">
                  <?php echo link_to(
                      __('Edit'),
                      '@staticpage_edit?id=' . $page['id'],
                      ['class' => 'btn btn-sm atom-btn-outline-light']
                  ); ?>
                  <?php
                    $isProtected = in_array($page['slug'] ?? '', ['home'], true);
                    if (!$isProtected) {
                        echo link_to(
                            __('Delete'),
                            '@staticpage_delete?id=' . $page['id'],
                            ['class' => 'btn btn-sm atom-btn-outline-danger']
                        );
                    }
                  ?>
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
    <?php echo link_to(__('Add new'), '@staticpage_add', ['class' => 'btn atom-btn-outline-light']); ?>
  </section>

<?php end_slot(); ?>
