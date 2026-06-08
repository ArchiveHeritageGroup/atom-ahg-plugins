<?php if (isset($link)) { ?>
  <?php // Prefer the web-optimized PDF sibling for the click-through so big scans open page-1-fast; non-PDF / no sibling returns $link unchanged. ?>
  <?php $link = ahgWebPdf::linkFor(isset($resource) ? $resource : (isset($representation) ? $representation : null), $link); ?>
  <?php echo link_to(image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Open original %1%', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]), 'class' => 'img-thumbnail']), $link, ['target' => '_blank']); ?>
<?php } else { ?>
  <?php echo image_tag($representation->getFullPath(), ['alt' => __($resource->getDigitalObjectAltText() ?: 'Original %1% not accessible', ['%1%' => sfConfig::get('app_ui_label_digitalobject')]), 'class' => 'img-thumbnail']); ?>
<?php } ?>
