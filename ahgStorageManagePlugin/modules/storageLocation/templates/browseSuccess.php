<?php use_helper('I18N', 'Date') ?>
<?php decorate_with('layout_1col'); ?>

<?php slot('content'); ?>

<div class="container-fluid">
  <?php if ($sf_user->hasFlash('notice')) { ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
  <?php } ?>
  <?php if ($sf_user->hasFlash('error')) { ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
  <?php } ?>

    <div class="row">
    <div class="col-md-3">
      <!-- Sidebar with location tree -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Storage Location Hierarchy') ?></h5>
        </div>
        <div class="card-body">
          <?php include_partial('storageLocation/tree', array('tree' => $tree)) ?>
        </div>
      </div>

      <!-- Search form -->
      <div class="card mb-3">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Search Locations') ?></h5>
        </div>
        <div class="card-body">
          <form method="get" action="<?php echo url_for('storageLocation/browse') ?>">
            <div class="form-group">
              <input type="text" class="form-control" name="search" value="<?php echo $sf_request->getParameter('search', '') ?>" placeholder="<?php echo __('Search locations...') ?>">
            </div>
            <div class="form-group">
              <select class="form-control" name="type">
                <option value=""><?php echo __('All Types') ?></option>
                <option value="building" <?php echo ($type == 'building' ? 'selected' : '') ?>><?php echo __('Building') ?></option>
                <option value="floor" <?php echo ($type == 'floor' ? 'selected' : '') ?>><?php echo __('Floor') ?></option>
                <option value="room" <?php echo ($type == 'room' ? 'selected' : '') ?>><?php echo __('Room') ?></option>
                <option value="aisle" <?php echo ($type == 'aisle' ? 'selected' : '') ?>><?php echo __('Aisle') ?></option>
                <option value="bay" <?php echo ($type == 'bay' ? 'selected' : '') ?>><?php echo __('Bay') ?></option>
                <option value="rack" <?php echo ($type == 'rack' ? 'selected' : '') ?>><?php echo __('Rack') ?></option>
                <option value="shelf" <?php echo ($type == 'shelf' ? 'selected' : '') ?>><?php echo __('Shelf') ?></option>
                <option value="container" <?php echo ($type == 'container' ? 'selected' : '') ?>><?php echo __('Container') ?></option>
                <option value="storage_unit" <?php echo ($type == 'storage_unit' ? 'selected' : '') ?>><?php echo __('Storage Unit') ?></option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo __('Search') ?></button>
            <a href="<?php echo url_for('storageLocation/browse') ?>" class="btn btn-secondary"><?php echo __('Clear') ?></a>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-9">
      <!-- Main content -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?php echo __('Browse Storage Locations') ?></h2>
        <a href="<?php echo url_for('storageLocation/create') ?>" class="btn btn-primary">
          <?php echo __('Create New Location') ?>
        </a>
      </div>

      <?php if (count($locations) > 0): ?>
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th><?php echo __('Name') ?></th>
                <th><?php echo __('Type') ?></th>
                <th><?php echo __('Level') ?></th>
                <th><?php echo __('Capacity') ?></th>
                <th><?php echo __('Actions') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($locations as $location): ?>
                <tr>
                  <td>
                    <?php echo str_repeat('&nbsp;&nbsp;', $location['level']) ?>
                    <?php echo link_to($location['name'], 'storageLocation/view?id=' . $location['id']) ?>
                  </td>
                  <td><?php echo __($location['location_type']) ?></td>
                  <td><?php echo $location['level'] ?></td>
                  <td>
                    <?php if ($location['capacity_value']): ?>
                      <?php echo format_number_choice('[0]No capacity|{1}1 unit|[1,Inf]%1% units', array('%1%' => $location['capacity_value']), $location['capacity_value']) ?>
                      <?php echo ' (' . __($location['capacity_unit']) . ')' ?>
                    <?php else: ?>
                      <?php echo __('N/A') ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <?php echo link_to(__('View'), 'storageLocation/view?id=' . $location['id'], array('class' => 'btn btn-sm btn-outline-primary')) ?>
                      <?php echo link_to(__('Edit'), 'storageLocation/edit?id=' . $location['id'], array('class' => 'btn btn-sm btn-outline-secondary')) ?>
                      <?php echo link_to(__('Delete'), 'storageLocation/delete?id=' . $location['id'], array('class' => 'btn btn-sm btn-outline-danger')) ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pages > 1) { ?>
          <nav aria-label="<?php echo __('Page navigation') ?>">
            <ul class="pagination justify-content-center">
              <?php
                // The action pages the rows and passes $page and $pages. Counting
                // the rows here instead would count one page and always render 1.
                $query = array_diff_key($sf_request->getGetParameters(), ['page' => true]);
                $link = function ($n) use ($query) {
                    $query['page'] = $n;

                    return url_for('storageLocation/browse?'.http_build_query($query));
                };
              ?>
              <?php if ($page > 1) { ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo $link($page - 1) ?>"><?php echo __('Previous') ?></a>
                </li>
              <?php } ?>

              <?php for ($i = 1; $i <= $pages; $i++) { ?>
                <li class="page-item <?php echo $i === $page ? 'active' : '' ?>">
                  <a class="page-link" href="<?php echo $link($i) ?>"><?php echo $i ?></a>
                </li>
              <?php } ?>

              <?php if ($page < $pages) { ?>
                <li class="page-item">
                  <a class="page-link" href="<?php echo $link($page + 1) ?>"><?php echo __('Next') ?></a>
                </li>
              <?php } ?>
            </ul>
          </nav>
        <?php } ?>

      <?php else: ?>
        <div class="alert alert-info">
          <?php echo __('No storage locations found.') ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
