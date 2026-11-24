<?php decorate_with('layout_1col'); ?>
<?php $pending = $sf_data->getRaw('pending'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Cultural notice'); ?></h1>
<?php end_slot(); ?>

<p><?php echo __('Please read and acknowledge the following before continuing.'); ?></p>

<?php foreach ($pending as $notice) { ?>
  <?php $severity = strtolower((string) $notice->severity); ?>
  <div class="alert alert-<?php echo 'critical' === $severity ? 'danger' : ('warning' === $severity ? 'warning' : 'info'); ?>" role="alert">
    <h2 class="h5"><?php echo esc_specialchars((string) $notice->name); ?></h2>
    <p><?php echo esc_specialchars((string) ($notice->custom_text ?: $notice->default_text)); ?></p>
    <form method="post" action="<?php echo url_for('@icip_acknowledge?notice_id='.(int) $notice->id); ?>">
      <input type="hidden" name="<?php echo esc_specialchars($csrfField); ?>" value="<?php echo esc_specialchars($csrfToken); ?>">
      <input type="hidden" name="return" value="<?php echo esc_specialchars($sf_data->getRaw('returnTo')); ?>">
      <button type="submit" class="btn btn-primary"><?php echo __('I acknowledge this notice'); ?></button>
    </form>
  </div>
<?php } ?>
