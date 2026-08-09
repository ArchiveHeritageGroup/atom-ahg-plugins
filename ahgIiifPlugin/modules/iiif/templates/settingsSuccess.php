<?php decorate_with('layout_2col.php') ?>

<?php slot('sidebar') ?>
<div class="sidebar-content">
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('About') ?></h5>
        </div>
        <div class="card-body">
            <p class="small text-muted"><?php echo __('Configure IIIF image display and homepage featured collections.') ?></p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links') ?></h5>
        </div>
        <div class="list-group list-group-flush">
            <a href="<?php echo url_for(['module' => 'iiifCollection', 'action' => 'index']) ?>" class="list-group-item list-group-item-action">
                <i class="fas fa-layer-group me-2"></i><?php echo __('Manage Collections') ?>
            </a>
            <a href="<?php echo url_for('@homepage') ?>" class="list-group-item list-group-item-action" target="_blank">
                <i class="fas fa-home me-2"></i><?php echo __('View Homepage') ?>
            </a>
        </div>
    </div>
</div>
<?php end_slot() ?>

<?php slot('title') ?>
<h1><i class="fas fa-images me-2"></i><?php echo __('IIIF Viewer Settings') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php if ($sf_user->hasFlash('notice')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif ?>

<form method="post" action="<?php echo url_for(['module' => 'iiif', 'action' => 'settings']) ?>">
    
    <!-- Homepage Featured Collection -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-home me-2"></i><?php echo __('Homepage Featured Collection') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="homepage_collection_enabled" value="1" id="homepageEnabled"
                               <?php echo ($settings['homepage_collection_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="homepageEnabled">
                            <strong><?php echo __('Enable homepage carousel') ?></strong>
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Select Collection to Feature') ?></label>
                        <select name="homepage_collection_id" class="form-select" id="homepageCollection">
                            <option value=""><?php echo __('-- Select a collection --') ?></option>
                            <?php foreach ($collections as $col): ?>
                            <option value="<?php echo $col->id ?>" <?php echo ($settings['homepage_collection_id'] ?? '') == $col->id ? 'selected' : '' ?>>
                                <?php echo esc_entities($col->name) ?> 
                                (<?php echo $col->item_count ?> <?php echo __('items') ?>)
                                <?php echo $col->is_public ? '' : ' [' . __('Private') . ']' ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                        <div class="form-text"><?php echo __('Choose which collection to display on the homepage.') ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Carousel Height') ?></label>
                        <select name="homepage_carousel_height" class="form-select">
                            <option value="300px" <?php echo ($settings['homepage_carousel_height'] ?? '') === '300px' ? 'selected' : '' ?>>300px (Small)</option>
                            <option value="400px" <?php echo ($settings['homepage_carousel_height'] ?? '') === '400px' ? 'selected' : '' ?>>400px (Medium)</option>
                            <option value="450px" <?php echo ($settings['homepage_carousel_height'] ?? '450px') === '450px' ? 'selected' : '' ?>>450px (Default)</option>
                            <option value="500px" <?php echo ($settings['homepage_carousel_height'] ?? '') === '500px' ? 'selected' : '' ?>>500px (Large)</option>
                            <option value="600px" <?php echo ($settings['homepage_carousel_height'] ?? '') === '600px' ? 'selected' : '' ?>>600px (Extra Large)</option>
                            <option value="70vh" <?php echo ($settings['homepage_carousel_height'] ?? '') === '70vh' ? 'selected' : '' ?>>70% Viewport</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Max Items to Display') ?></label>
                        <input type="number" name="homepage_max_items" class="form-control" 
                               value="<?php echo $settings['homepage_max_items'] ?? '12' ?>" min="1" max="50">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="homepage_carousel_autoplay" value="1" id="homepageAutoplay"
                               <?php echo ($settings['homepage_carousel_autoplay'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="homepageAutoplay"><?php echo __('Auto-rotate slides') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="homepage_show_captions" value="1" id="homepageCaptions"
                               <?php echo ($settings['homepage_show_captions'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="homepageCaptions"><?php echo __('Show image captions') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Rotation Speed (ms)') ?></label>
                        <input type="number" name="homepage_carousel_interval" class="form-control form-control-sm" 
                               value="<?php echo $settings['homepage_carousel_interval'] ?? '5000' ?>" min="1000" max="15000" step="500">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Page Viewer Settings -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-tv me-2"></i><?php echo __('Record Page Viewer') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label"><?php echo __('Display Type') ?></label>
                    <select name="viewer_type" class="form-select" id="viewerType">
                        <option value="carousel" <?php echo ($settings['viewer_type'] ?? '') === 'carousel' ? 'selected' : '' ?>>
                            <?php echo __('Carousel (Bootstrap 5)') ?>
                        </option>
                        <option value="single" <?php echo ($settings['viewer_type'] ?? '') === 'single' ? 'selected' : '' ?>>
                            <?php echo __('Single Image with Zoom') ?>
                        </option>
                        <option value="openseadragon" <?php echo ($settings['viewer_type'] ?? '') === 'openseadragon' ? 'selected' : '' ?>>
                            <?php echo __('OpenSeadragon (Deep Zoom)') ?>
                        </option>
                        <option value="mirador" <?php echo ($settings['viewer_type'] ?? '') === 'mirador' ? 'selected' : '' ?>>
                            <?php echo __('Mirador (Full IIIF Viewer)') ?>
                        </option>
                    </select>
                    <div class="form-text"><?php echo __('Choose how images are displayed on record pages.') ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?php echo __('Viewer Height') ?></label>
                    <select name="viewer_height" class="form-select">
                        <option value="300px" <?php echo ($settings['viewer_height'] ?? '') === '300px' ? 'selected' : '' ?>>300px (Small)</option>
                        <option value="400px" <?php echo ($settings['viewer_height'] ?? '') === '400px' ? 'selected' : '' ?>>400px (Medium)</option>
                        <option value="500px" <?php echo ($settings['viewer_height'] ?? '500px') === '500px' ? 'selected' : '' ?>>500px (Default)</option>
                        <option value="600px" <?php echo ($settings['viewer_height'] ?? '') === '600px' ? 'selected' : '' ?>>600px (Large)</option>
                        <option value="700px" <?php echo ($settings['viewer_height'] ?? '') === '700px' ? 'selected' : '' ?>>700px (Extra Large)</option>
                        <option value="80vh" <?php echo ($settings['viewer_height'] ?? '') === '80vh' ? 'selected' : '' ?>>80% Viewport</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4" id="carouselOptions">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i><?php echo __('Carousel Options') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="carousel_autoplay" value="1" id="autoplay"
                               <?php echo ($settings['carousel_autoplay'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="autoplay"><?php echo __('Auto-rotate slides') ?></label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Rotation Interval (ms)') ?></label>
                        <input type="number" name="carousel_interval" class="form-control" 
                               value="<?php echo $settings['carousel_interval'] ?? '5000' ?>" min="1000" max="15000" step="500">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="carousel_show_thumbnails" value="1" id="showThumbs"
                               <?php echo ($settings['carousel_show_thumbnails'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="showThumbs"><?php echo __('Show thumbnail navigation') ?></label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="carousel_show_controls" value="1" id="showControls"
                               <?php echo ($settings['carousel_show_controls'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="showControls"><?php echo __('Show prev/next controls') ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-palette me-2"></i><?php echo __('Appearance') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label"><?php echo __('Background Color') ?></label>
                    <input type="color" name="background_color" class="form-control form-control-color w-100" 
                           value="<?php echo $settings['background_color'] ?? '#000000' ?>">
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="enable_fullscreen" value="1" id="fullscreen"
                               <?php echo ($settings['enable_fullscreen'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fullscreen"><?php echo __('Enable fullscreen button') ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="show_zoom_controls" value="1" id="zoomControls"
                               <?php echo ($settings['show_zoom_controls'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="zoomControls"><?php echo __('Show zoom controls') ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Display Locations') ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_on_view" value="1" id="showOnView"
                               <?php echo ($settings['show_on_view'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="showOnView"><?php echo __('Show on record view page') ?></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_on_browse" value="1" id="showOnBrowse"
                               <?php echo ($settings['show_on_browse'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="showOnBrowse"><?php echo __('Show on browse page (cards)') ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success btn-lg">
            <i class="fas fa-save me-2"></i><?php echo __('Save Settings') ?>
        </button>
        <a href="<?php echo url_for('@homepage') ?>" class="btn btn-outline-secondary btn-lg">
            <i class="fas fa-times me-2"></i><?php echo __('Cancel') ?>
        </a>
    </div>
<?php
/*
 * Per-viewer defaults.
 *
 * These were fixed in each viewer plugin's JavaScript, so a repository that
 * wanted the navigator off or rotation on had to patch a plugin and carry the
 * patch across upgrades. Blank means "not configured" and leaves the plugin's
 * own default in place, which is why the switches are not pre-ticked from
 * nothing.
 */
$s = isset($settings) && is_array($settings) ? $settings : [];

/*
 * A checkbox posts nothing when it is off, and the save loop writes every key on
 * every save - so a switch that starts unticked because it has never been
 * configured is saved as OFF the first time an administrator touches anything
 * else on this page. That silently disabled the navigator, maximize and
 * fullscreen the first time it was tried.
 *
 * The switches therefore start from each viewer's own default rather than from
 * blank, so saving without changing anything changes nothing.
 */
$defaults = [
    'seadragon_show_navigator' => 1,
    'seadragon_show_rotation'  => 1,
    'seadragon_show_flip'      => 1,
    'mirador_allow_close'      => 0,
    'mirador_allow_maximize'   => 1,
    'mirador_allow_fullscreen' => 1,
    'mirador_sidebar_open'     => 0,
    'mirador_workspace_panel'  => 0,
    'mirador_zoom_controls'    => 1,
];
$on = function ($k) use ($s, $defaults) {
    $v = array_key_exists($k, $s) ? $s[$k] : ($defaults[$k] ?? 0);

    return !empty($v) ? ' checked' : '';
};
$val = function ($k, $d = '') use ($s) { return htmlspecialchars((string) ($s[$k] ?? $d), ENT_QUOTES); };
?>

<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <?php echo __('OpenSeadragon defaults') ?>
    <small class="d-block"><?php echo __('Applied when ahgSeadragonPlugin is installed') ?></small>
  </div>
  <div class="card-body row g-3">
    <div class="col-md-4">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="seadragon_show_navigator" value="1" id="sdNav"<?php echo $on('seadragon_show_navigator') ?>>
        <label class="form-check-label" for="sdNav"><?php echo __('Show navigator') ?></label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="seadragon_show_rotation" value="1" id="sdRot"<?php echo $on('seadragon_show_rotation') ?>>
        <label class="form-check-label" for="sdRot"><?php echo __('Rotation control') ?></label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="seadragon_show_flip" value="1" id="sdFlip"<?php echo $on('seadragon_show_flip') ?>>
        <label class="form-check-label" for="sdFlip"><?php echo __('Flip control') ?></label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sdNavPos"><?php echo __('Navigator position') ?></label>
      <select class="form-select" name="seadragon_navigator_position" id="sdNavPos">
        <?php foreach (['BOTTOM_RIGHT','BOTTOM_LEFT','TOP_RIGHT','TOP_LEFT'] as $p): ?>
          <option value="<?php echo $p ?>"<?php echo $val('seadragon_navigator_position','BOTTOM_RIGHT') === $p ? ' selected' : '' ?>><?php echo $p ?></option>
        <?php endforeach ?>
      </select>
      <label class="form-label mt-2" for="sdCors"><?php echo __('Cross-origin policy') ?></label>
      <select class="form-select" name="seadragon_cross_origin" id="sdCors">
        <?php foreach (['Anonymous','use-credentials',''] as $p): ?>
          <option value="<?php echo $p ?>"<?php echo $val('seadragon_cross_origin','Anonymous') === $p ? ' selected' : '' ?>><?php echo $p ?: __('none') ?></option>
        <?php endforeach ?>
      </select>
      <div class="form-text"><?php echo __('Anonymous is required when tiles are served from a different host to the site.') ?></div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="sdZoom"><?php echo __('Zoom per click') ?></label>
      <input class="form-control" type="number" step="0.1" min="1" name="seadragon_zoom_per_click" id="sdZoom" value="<?php echo $val('seadragon_zoom_per_click','1.5') ?>">
      <label class="form-label mt-2" for="sdRetry"><?php echo __('Tile retries') ?></label>
      <input class="form-control" type="number" min="0" max="10" name="seadragon_tile_retry_max" id="sdRetry" value="<?php echo $val('seadragon_tile_retry_max','3') ?>">
      <div class="form-text"><?php echo __('A dropped tile request otherwise stays a blank square.') ?></div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <?php echo __('Mirador defaults') ?>
    <small class="d-block"><?php echo __('Applied when ahgMiradorPlugin is installed') ?></small>
  </div>
  <div class="card-body row g-3">
    <div class="col-md-6">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="mirador_allow_close" value="1" id="mClose"<?php echo $on('mirador_allow_close') ?>>
        <label class="form-check-label" for="mClose"><?php echo __('Reader may close the window') ?></label>
      </div>
      <div class="form-text mb-2"><?php echo __('Off is recommended: a closed window leaves an empty workspace with no way back except a reload.') ?></div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="mirador_allow_fullscreen" value="1" id="mFull"<?php echo $on('mirador_allow_fullscreen') ?>>
        <label class="form-check-label" for="mFull"><?php echo __('Fullscreen') ?></label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="mirador_zoom_controls" value="1" id="mZoom"<?php echo $on('mirador_zoom_controls') ?>>
        <label class="form-check-label" for="mZoom"><?php echo __('Zoom controls') ?></label>
      </div>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="mirador_sidebar_open" value="1" id="mSide"<?php echo $on('mirador_sidebar_open') ?>>
        <label class="form-check-label" for="mSide"><?php echo __('Sidebar open by default') ?></label>
      </div>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="mThumb"><?php echo __('Thumbnail navigation') ?></label>
      <select class="form-select" name="mirador_thumbnail_position" id="mThumb">
        <?php foreach (['far-bottom','off','far-right'] as $p): ?>
          <option value="<?php echo $p ?>"<?php echo $val('mirador_thumbnail_position','far-bottom') === $p ? ' selected' : '' ?>><?php echo $p ?></option>
        <?php endforeach ?>
      </select>
      <div class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" name="mirador_workspace_panel" value="1" id="mPanel"<?php echo $on('mirador_workspace_panel') ?>>
        <label class="form-check-label" for="mPanel"><?php echo __('Workspace control panel') ?></label>
      </div>
      <div class="form-text"><?php echo __('Off for a single record; on if readers compare several.') ?></div>
    </div>
  </div>
</div>

</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('viewerType').addEventListener('change', function() {
    var carouselOpts = document.getElementById('carouselOptions');
    carouselOpts.style.display = (this.value === 'carousel') ? 'block' : 'none';
});
// Initial state
document.getElementById('viewerType').dispatchEvent(new Event('change'));
</script>
<?php end_slot() ?>
