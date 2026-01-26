<?php echo get_partial('layout_start'); ?>
<?php include(sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_adminNotifications.php'); ?>
<div id="wrapper" class="container-xxl pt-3 flex-grow-1">
  <?php echo get_partial('alerts'); ?>
  <div class="row">
    <div id="left-column" class="col-md-3">
      <?php if (!include_slot('left')) { ?>
        <?php include_slot('sidebar'); ?>
      <?php } ?>
    </div>
    <div id="main-column" class="col-md-6" role="main">
      <?php include_slot('title'); ?>
      <?php include_slot('before-content'); ?>
      <?php if (!include_slot('content')) { ?>
        <div id="content">
          <?php echo $sf_content; ?>
        </div>
      <?php } ?>
      <?php include_slot('after-content'); ?>
    </div>
    <div id="right-column" class="col-md-3">
      <?php if (!include_slot('right')) { ?>
        <?php include_slot('context-menu'); ?>
      <?php } ?>
    </div>
  </div>
</div>
<?php echo get_partial('layout_end'); ?>
