<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Image viewers'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($tableMissing)) { ?>
    <div class="alert alert-warning">
      <?php echo __('The iiif_viewer_settings table is missing, so nothing can be saved here. It belongs to ahgIiifPlugin - install and enable that plugin, or ignore this page.'); ?>
    </div>
  <?php } ?>

  <p class="text-muted">
    <?php echo __('Defaults for the IIIF image viewers. These were fixed in each viewer plugin until now, so changing one meant editing code. A setting on an individual record still wins over anything set here.'); ?>
  </p>

  <form method="post" action="<?php echo url_for(['module' => 'settings', 'action' => 'viewers']); ?>">

    <div class="row mb-4">
      <div class="col-md-4">
        <label class="form-label" for="viewer_height"><?php echo __('Viewer height'); ?></label>
        <input class="form-control" type="text" id="viewer_height" name="viewer_height"
               value="<?php echo esc_specialchars($settings['viewer_height']); ?>">
        <div class="form-text"><?php echo __('A CSS length: 600px, 80vh or 100%.'); ?></div>
      </div>
    </div>

    <h2 class="h5 mt-4"><?php echo __('OpenSeadragon'); ?></h2>
    <p class="text-muted small">
      <?php echo $seadragon
        ? __('ahgSeadragonPlugin is installed, so these apply.')
        : __('ahgSeadragonPlugin is not installed. These are stored and will apply if it is added later.'); ?>
    </p>

    <div class="row">
      <div class="col-md-4 mb-3">
        <?php foreach ([
          'seadragon_show_navigator' => __('Show navigator'),
          'seadragon_show_rotation' => __('Rotation control'),
          'seadragon_show_flip' => __('Flip control'),
        ] as $key => $label) { ?>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="<?php echo $key; ?>"
                   name="<?php echo $key; ?>" value="1"
                   <?php echo '1' === $settings[$key] ? ' checked' : ''; ?>>
            <label class="form-check-label" for="<?php echo $key; ?>"><?php echo $label; ?></label>
          </div>
        <?php } ?>
        <div class="form-text"><?php echo __('Scanned material arrives sideways often enough that rotation is worth leaving on.'); ?></div>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label" for="seadragon_navigator_position"><?php echo __('Navigator position'); ?></label>
        <select class="form-select" id="seadragon_navigator_position" name="seadragon_navigator_position">
          <?php foreach (['BOTTOM_RIGHT', 'BOTTOM_LEFT', 'TOP_RIGHT', 'TOP_LEFT'] as $v) { ?>
            <option value="<?php echo $v; ?>"<?php echo $settings['seadragon_navigator_position'] === $v ? ' selected' : ''; ?>><?php echo $v; ?></option>
          <?php } ?>
        </select>

        <label class="form-label mt-3" for="seadragon_cross_origin"><?php echo __('Cross-origin policy'); ?></label>
        <select class="form-select" id="seadragon_cross_origin" name="seadragon_cross_origin">
          <?php foreach (['Anonymous', 'use-credentials', ''] as $v) { ?>
            <option value="<?php echo $v; ?>"<?php echo $settings['seadragon_cross_origin'] === $v ? ' selected' : ''; ?>><?php echo '' === $v ? __('none') : $v; ?></option>
          <?php } ?>
        </select>
        <div class="form-text"><?php echo __('Anonymous is required when the image server answers on a different host to the site.'); ?></div>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label" for="seadragon_zoom_per_click"><?php echo __('Zoom per click'); ?></label>
        <input class="form-control" type="text" id="seadragon_zoom_per_click" name="seadragon_zoom_per_click"
               value="<?php echo esc_specialchars($settings['seadragon_zoom_per_click']); ?>">

        <label class="form-label mt-3" for="seadragon_tile_retry_max"><?php echo __('Tile retries'); ?></label>
        <input class="form-control" type="text" id="seadragon_tile_retry_max" name="seadragon_tile_retry_max"
               value="<?php echo esc_specialchars($settings['seadragon_tile_retry_max']); ?>">
        <div class="form-text"><?php echo __('A dropped tile request otherwise stays a blank square.'); ?></div>
      </div>
    </div>

    <h2 class="h5 mt-4"><?php echo __('Mirador'); ?></h2>
    <p class="text-muted small">
      <?php echo $mirador
        ? __('ahgMiradorPlugin is installed, so these apply.')
        : __('ahgMiradorPlugin is not installed. These are stored and will apply if it is added later.'); ?>
    </p>

    <div class="row">
      <div class="col-md-6 mb-3">
        <?php foreach ([
          'mirador_allow_close' => __('Reader may close the window'),
          'mirador_allow_maximize' => __('Maximize'),
          'mirador_allow_fullscreen' => __('Fullscreen'),
          'mirador_zoom_controls' => __('Zoom controls'),
          'mirador_sidebar_open' => __('Sidebar open by default'),
          'mirador_workspace_panel' => __('Workspace control panel'),
        ] as $key => $label) { ?>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="<?php echo $key; ?>"
                   name="<?php echo $key; ?>" value="1"
                   <?php echo '1' === $settings[$key] ? ' checked' : ''; ?>>
            <label class="form-check-label" for="<?php echo $key; ?>"><?php echo $label; ?></label>
          </div>
        <?php } ?>
        <div class="form-text">
          <?php echo __('Leave "may close the window" off: a closed window leaves an empty workspace with no way back except a reload.'); ?>
        </div>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label" for="mirador_thumbnail_position"><?php echo __('Thumbnail navigation'); ?></label>
        <select class="form-select" id="mirador_thumbnail_position" name="mirador_thumbnail_position">
          <?php foreach (['far-bottom', 'far-right', 'off'] as $v) { ?>
            <option value="<?php echo $v; ?>"<?php echo $settings['mirador_thumbnail_position'] === $v ? ' selected' : ''; ?>><?php echo $v; ?></option>
          <?php } ?>
        </select>
        <div class="form-text"><?php echo __('The workspace control panel is worth turning on only where readers compare several items.'); ?></div>
      </div>
    </div>

    <input class="btn btn-primary" type="submit" value="<?php echo __('Save'); ?>">

  </form>

<?php end_slot(); ?>
