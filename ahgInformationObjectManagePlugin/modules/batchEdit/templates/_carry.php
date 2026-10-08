<?php // Hidden fields carried from step to step: token, slugs and (optionally) the operations. ?>
<?php if (class_exists('\AtomFramework\Services\CsrfService')) { ?>
  <?php echo \AtomFramework\Services\CsrfService::renderHiddenField(); ?>
<?php } ?>
<?php foreach ($sf_data->getRaw('slugs') as $slug) { ?>
  <input type="hidden" name="slugs[]" value="<?php echo esc_entities($slug); ?>">
<?php } ?>
<?php foreach ($sf_data->getRaw('values') as $name => $value) { ?>
  <?php foreach (is_array($value) ? $value : [null => $value] as $item) { ?>
    <?php if (is_string($item)) { ?>
      <input type="hidden" name="<?php echo esc_entities($name).(is_array($value) ? '[]' : ''); ?>" value="<?php echo esc_entities($item); ?>">
    <?php } ?>
  <?php } ?>
<?php } ?>
