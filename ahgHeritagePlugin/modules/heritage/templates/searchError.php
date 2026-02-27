<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1>Search Error</h1>
<?php end_slot() ?>

<div class="alert alert-danger" role="alert">
  <h4 class="alert-heading">Search could not be completed</h4>
  <p><?php echo esc_specialchars($error ?? 'An unexpected error occurred while processing your search.') ?></p>
  <hr>
  <p class="mb-0"><a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']) ?>">Return to heritage discovery</a></p>
</div>
