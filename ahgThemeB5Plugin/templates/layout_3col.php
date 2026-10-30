<?php echo get_partial('layout_start'); ?>
<?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_adminNotifications.php'); ?>
<div id="wrapper" class="container-xxl pt-3 flex-grow-1">
  <?php echo get_partial('alerts'); ?>
  <div class="row">
    <aside id="left-column" class="col-md-3" aria-label="<?php echo __('Context navigation'); ?>">
      <?php if (!include_slot('left')) { ?>
        <?php include_slot('sidebar'); ?>
      <?php } ?>
    </aside>
    <div id="main-column" class="col-md-6" role="main" tabindex="-1">
      <?php include_slot('title'); ?>
      <?php include_slot('before-content'); ?>
      <?php if (!include_slot('content')) { ?>
        <div id="content">
          <?php echo $sf_content; ?>
        </div>
      <?php } ?>
      <?php include_slot('after-content'); ?>
    </div>
    <aside id="right-column" class="col-md-3" aria-label="<?php echo __('Related information'); ?>">
      <?php if (!include_slot('right')) { ?>
        <?php include_slot('context-menu'); ?>
      <?php } ?>
    </aside>
  </div>
</div>
<?php echo get_partial('layout_end'); ?>
