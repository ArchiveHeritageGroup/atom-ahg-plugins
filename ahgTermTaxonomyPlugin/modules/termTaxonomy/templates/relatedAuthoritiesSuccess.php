<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('Related authority records'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo render_title($resource); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php $rawAuthorities = $sf_data->getRaw('authorities'); ?>

<div class="section">
  <?php if (empty($rawAuthorities)) { ?>
    <p class="text-muted"><?php echo __('No authority records are linked to this term.'); ?></p>
  <?php } else { ?>
    <ul class="list-group">
      <?php foreach ($rawAuthorities as $authority) { ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?php if (!empty($authority->slug)) { ?>
            <?php echo link_to(esc_specialchars($authority->name ?: __('Untitled')), '@actor_view_override?slug=' . $authority->slug); ?>
          <?php } else { ?>
            <?php echo esc_specialchars($authority->name ?: __('Untitled')); ?>
          <?php } ?>
        </li>
      <?php } ?>
    </ul>
  <?php } ?>
</div>

<?php slot('after-content'); ?>
  <ul class="actions mb-3 nav gap-2">
    <li><?php echo link_to(__('Back to term'), '@term_browse_override?slug=' . $resource->slug, ['class' => 'btn atom-btn-outline-light']); ?></li>
  </ul>
<?php end_slot(); ?>
