<?php use_helper('Javascript'); ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
          <i class="fas fa-layer-group me-2"></i>
          <?php echo __('Levels of Description'); ?>
        </h1>
        <a href="<?php echo url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description'])]); ?>"
           class="btn btn-success" target="_blank">
          <i class="fas fa-plus me-1"></i><?php echo __('Add new term in Taxonomy'); ?>
        </a>
      </div>

      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('notice'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error'); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Info Box -->
      <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong><?php echo __('How it works:'); ?></strong>
        <?php echo __('Select which levels appear in each sector. Only sectors with enabled plugins are shown. Archive levels are always available.'); ?>
      </div>

      <div class="row">
        <!-- Sector Tabs - Only show available sectors -->
        <div class="col-12 mb-4">
          <ul class="nav nav-tabs">
            <?php foreach ($sf_data->getRaw('availableSectors') as $sector): ?>
              <?php $count = $sf_data->getRaw('sectorCounts')[$sector] ?? 0; ?>
              <li class="nav-item">
                <a class="nav-link <?php echo $currentSector === $sector ? 'active' : ''; ?>"
                   href="<?php echo url_for(['module' => 'settings', 'action' => 'levels', 'sector' => $sector]); ?>">
                  <i class="fas fa-<?php echo getSectorIcon($sector); ?> me-1"></i>
                  <?php echo ucfirst($sector); ?>
                  <span class="badge bg-secondary ms-1"><?php echo $count; ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Sector Levels Management -->
        <div class="col-lg-8">
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-<?php echo getSectorIcon($currentSector); ?> me-2"></i>
                <?php echo ucfirst($currentSector); ?> <?php echo __('Levels'); ?>
              </h5>
            </div>
            <div class="card-body">
              <form method="post" action="<?php echo url_for(['module' => 'settings', 'action' => 'levels', 'sector' => $currentSector]); ?>">
                <input type="hidden" name="action_type" value="update_sector">
                <input type="hidden" name="sector" value="<?php echo $currentSector; ?>">

                <p class="text-muted mb-3"><?php echo __('Select which levels appear in the %1% sector:', ['%1%' => '<strong>' . ucfirst($currentSector) . '</strong>']); ?></p>

                <?php $availableLevels = $sf_data->getRaw('sectorAvailableLevels'); ?>
                <?php if ($availableLevels === null || $availableLevels->isEmpty()): ?>
                  <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo __('No levels available for this sector. The required terms may not exist in the database. Please add them via the Taxonomy.'); ?>
                  </div>
                <?php else: ?>
                  <div class="row">
                    <?php foreach ($availableLevels as $level): ?>
                      <div class="col-md-6 col-lg-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox"
                                 name="levels[]" value="<?php echo $level->id; ?>"
                                 id="level_<?php echo $level->id; ?>"
                                 <?php echo in_array($level->id, $sf_data->getRaw('sectorLevelIds')) ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="level_<?php echo $level->id; ?>">
                            <?php echo esc_entities($level->name); ?>
                            <a href="<?php echo url_for(['module' => 'term', 'slug' => $level->slug]); ?>"
                               class="text-muted ms-1" title="<?php echo __('Edit in Taxonomy'); ?>" target="_blank">
                              <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                          </label>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <hr>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?>
                </button>
              </form>
            </div>
          </div>

          <!-- Display Order -->
          <?php $currentSectorLevels = $sf_data->getRaw('sectorLevels'); ?>
          <?php if (count($currentSectorLevels) > 0): ?>
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-sort me-2"></i><?php echo __('Display Order'); ?></h5>
            </div>
            <div class="card-body">
              <form method="post" action="<?php echo url_for(['module' => 'settings', 'action' => 'levels', 'sector' => $currentSector]); ?>">
                <input type="hidden" name="action_type" value="update_order">
                <input type="hidden" name="sector" value="<?php echo $currentSector; ?>">

                <table class="table table-sm table-hover">
                  <thead>
                    <tr>
                      <th><?php echo __('Level'); ?></th>
                      <th style="width: 100px;"><?php echo __('Order'); ?></th>
                      <th style="width: 80px;"><?php echo __('Actions'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($currentSectorLevels as $level): ?>
                      <tr>
                        <td>
                          <?php echo esc_entities($level->name); ?>
                        </td>
                        <td>
                          <input type="number" class="form-control form-control-sm"
                                 name="order[<?php echo $level->id; ?>]"
                                 value="<?php echo $level->display_order; ?>"
                                 min="0" step="10">
                        </td>
                        <td>
                          <a href="<?php echo url_for(['module' => 'term', 'slug' => $level->slug]); ?>"
                             class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>" target="_blank">
                            <i class="fas fa-edit"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <button type="submit" class="btn btn-secondary btn-sm">
                  <i class="fas fa-sort me-1"></i> <?php echo __('Update Order'); ?>
                </button>
              </form>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Sector Info -->
          <div class="card mb-3">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('About Sectors'); ?></h5>
            </div>
            <div class="card-body small">
              <dl class="mb-0">
                <dt><i class="fas fa-archive me-1"></i> <?php echo __('Archive'); ?></dt>
                <dd class="text-muted"><?php echo __('Traditional archival levels (ISAD(G), RAD, DACS)'); ?></dd>

                <?php if (in_array('museum', $sf_data->getRaw('availableSectors'))): ?>
                <dt><i class="fas fa-landmark me-1"></i> <?php echo __('Museum'); ?></dt>
                <dd class="text-muted"><?php echo __('Object-based descriptions (CCO/CDWA, Spectrum)'); ?></dd>
                <?php endif; ?>

                <?php if (in_array('library', $sf_data->getRaw('availableSectors'))): ?>
                <dt><i class="fas fa-book me-1"></i> <?php echo __('Library'); ?></dt>
                <dd class="text-muted"><?php echo __('Bibliographic materials (books, journals, articles)'); ?></dd>
                <?php endif; ?>

                <?php if (in_array('gallery', $sf_data->getRaw('availableSectors'))): ?>
                <dt><i class="fas fa-image me-1"></i> <?php echo __('Gallery'); ?></dt>
                <dd class="text-muted"><?php echo __('Artwork and visual materials'); ?></dd>
                <?php endif; ?>

                <?php if (in_array('dam', $sf_data->getRaw('availableSectors'))): ?>
                <dt><i class="fas fa-photo-video me-1"></i> <?php echo __('DAM'); ?></dt>
                <dd class="text-muted mb-0"><?php echo __('Digital Asset Management (media files)'); ?></dd>
                <?php endif; ?>
              </dl>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links'); ?></h5>
            </div>
            <div class="list-group list-group-flush">
              <a href="<?php echo url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description']); ?>"
                 class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-list me-2"></i><?php echo __('Browse all levels in Taxonomy'); ?>
                <i class="fas fa-external-link-alt fa-xs float-end mt-1"></i>
              </a>
              <a href="<?php echo url_for(['module' => 'term', 'action' => 'add', 'taxonomy' => url_for(['module' => 'taxonomy', 'slug' => 'levels-of-description'])]); ?>"
                 class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-plus me-2"></i><?php echo __('Create new level term'); ?>
                <i class="fas fa-external-link-alt fa-xs float-end mt-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<hr>
<div class="d-flex justify-content-start">
    <a href="<?php echo url_for('admin/ahg-settings'); ?>" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Settings'); ?>
    </a>
</div>

<?php
function getSectorIcon($sector) {
    return match($sector) {
        'archive' => 'archive',
        'museum' => 'landmark',
        'library' => 'book',
        'gallery' => 'image',
        'dam' => 'photo-video',
        default => 'folder',
    };
}
?>
