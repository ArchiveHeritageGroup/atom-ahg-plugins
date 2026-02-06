<div class="container py-5">
  <div class="alert alert-warning">
    <h4>Heritage Portal</h4>
    <p><?php echo isset($error) ? esc_specialchars($error) : 'The heritage portal is not yet configured for this installation.'; ?></p>
    <a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'homepage']); ?>" class="btn btn-primary mt-2">Return to Homepage</a>
  </div>
</div>
