<?php
/*
 * Search and holdings forms target GLAM Browse where ahgDisplayPlugin provides
 * it, and fall back to AtoM's own description browse where it does not.
 *
 * They used to call url_for('@glam_browse') directly. url_for() throws on an
 * unknown route, so a site with the theme but without ahgDisplayPlugin returned
 * a 500 on every page carrying a search box - which is most of them. A theme
 * must not require a feature plugin to be installed.
 */
?>
<form class="mb-3" role="search" aria-label="<?php echo sfConfig::get('app_ui_label_holdings'); ?>" action="<?php echo AhgNav::safeUrl('@glam_browse', url_for(['module' => 'informationobject', 'action' => 'browse'])); ?>">
  <input type="hidden" name="repos" value="<?php echo $resource->id; ?>">
  <div class="input-group">
    <input type="text" class="form-control" name="query" aria-label="<?php echo __('Search'); ?>" placeholder="<?php echo __('Search'); ?>">
    <button class="btn atom-btn-white" type="submit" aria-label="<?php echo __('Search'); ?>">
      <i aria-hidden="true" class="fas fa-search"></i>
    </button>
  </div>
</form>
