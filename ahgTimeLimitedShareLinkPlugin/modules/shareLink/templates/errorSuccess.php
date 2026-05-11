<?php
/** @var string $errorMessage */
?>
<div class="alert alert-danger" role="alert">
  <h5><?php echo __('Access denied') ?></h5>
  <p class="mb-0"><?php echo esc_entities($errorMessage ?? __('You are not allowed to view this page.')) ?></p>
</div>
