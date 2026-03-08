<?php
// holdAction always redirects — this template should never render.
// If it does, redirect to the OPAC index.
?>
<?php $sf_context->getController()->redirect(url_for(['module' => 'opac', 'action' => 'index'])); ?>
