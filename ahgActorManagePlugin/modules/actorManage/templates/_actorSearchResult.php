<?php
// Unwrap Symfony output escaper decorators
$doc = sfOutputEscaper::unescape($doc);
$entityTypeNames = sfOutputEscaper::unescape($entityTypeNames);
$browseService = sfOutputEscaper::unescape($browseService);
$culture = sfOutputEscaper::unescape($culture);
$clipboardType = sfOutputEscaper::unescape($clipboardType);

$name = $browseService->extractI18nField($doc, 'authorizedFormOfName');
if (empty($name)) {
    $name = __('[Untitled]');
}
?>
<article class="search-result row g-0 p-3 border-bottom">
  <?php if (!empty($doc['hasDigitalObject'])) { ?>
    <div class="col-12 col-lg-3 pb-2 pb-lg-0 pe-lg-3">
      <a href="<?php echo url_for('@actor_view_override?slug=' . $doc['slug']); ?>">
        <?php
        $thumbPath = $doc['digitalObject']['thumbnailPath'] ?? '';
        if (empty($thumbPath)) {
            $mediaTypeId = $doc['digitalObject']['mediaTypeId'] ?? null;
            $thumbPath = QubitDigitalObject::getGenericIconPathByMediaTypeId($mediaTypeId);
        }
        $altText = $doc['digitalObject']['digitalObjectAltText'] ?? '';
        if (empty($altText)) {
            $altText = strip_markdown($name);
        }
        ?>
        <?php echo image_tag($thumbPath, [
            'alt' => $altText,
            'class' => 'img-thumbnail',
        ]); ?>
      </a>
    </div>
  <?php } ?>

  <div class="col-12<?php echo empty($doc['hasDigitalObject']) ? '' : ' col-lg-9'; ?> d-flex flex-column gap-1">
    <div class="d-flex align-items-center gap-2 mw-100">
      <?php echo link_to(
          render_title($name),
          '@actor_view_override?slug=' . $doc['slug'],
          ['class' => 'h5 mb-0 text-truncate']
      ); ?>

      <?php echo get_component('clipboard', 'button', [
          'slug' => $doc['slug'],
          'type' => $clipboardType,
          'wide' => false,
      ]); ?>
    </div>

    <div class="d-flex flex-column gap-2">
      <div class="d-flex flex-wrap">
        <?php $showDash = false; ?>

        <?php if (!empty($doc['descriptionIdentifier'])) { ?>
          <span class="text-primary">
            <?php echo esc_entities($doc['descriptionIdentifier']); ?>
          </span>
          <?php $showDash = true; ?>
        <?php } ?>

        <?php if (!empty($doc['entityTypeId']) && isset($entityTypeNames[(int) $doc['entityTypeId']])) { ?>
          <?php if ($showDash) { ?>
            <span class="text-muted mx-2"> &middot; </span>
          <?php } ?>
          <span class="text-muted">
            <?php echo esc_entities($entityTypeNames[(int) $doc['entityTypeId']]); ?>
          </span>
          <?php $showDash = true; ?>
        <?php } ?>

        <?php
        $dates = $browseService->extractI18nField($doc, 'datesOfExistence');
        if (strlen($dates) > 0) { ?>
          <?php if ($showDash) { ?>
            <span class="text-muted mx-2"> &middot; </span>
          <?php } ?>
          <span class="text-muted">
            <?php echo render_value_inline($dates); ?>
          </span>
        <?php } ?>
      </div>

      <?php
      $history = $browseService->extractI18nField($doc, 'history');
      if (strlen($history) > 0) { ?>
        <span class="text-block d-none">
          <?php echo render_value($history); ?>
        </span>
      <?php } ?>
    </div>
  </div>
</article>
