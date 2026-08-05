<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
<div class="d-flex align-items-center mb-3">
    <i class="fas fa-trash-alt fa-2x text-danger me-3"></i>
    <div>
        <h1 class="h3 mb-0"><?php echo __('Delete Feedback') ?></h1>
    </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Confirm Deletion') ?>
            </div>
            <div class="card-body text-center py-4">
                <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                <h5><?php echo __('Are you sure you want to delete this feedback?') ?></h5>
                <p class="text-muted mb-0">
                    <strong><?php echo esc_entities($resource->name) ?></strong><br>
                    <?php echo __('From: %1%', ['%1%' => esc_entities($resource->feed_name . ' ' . $resource->feed_surname)]) ?>
                </p>
            </div>
            <div class="card-footer bg-light">
                <?php
                // Confirmation is a POST, not a link. The action requires POST or
                // DELETE precisely so a GET - a prefetch, a crawler, an <img> tag
                // pointed at this URL - cannot delete feedback, which means the
                // previous anchor could never have worked even once it rendered.
                //
                // It did not render: url_for([$resource, ...]) is the legacy form,
                // and with $resource now a plain row object rather than a Propel
                // model it threw 'Cannot use object of type stdClass as array' out
                // of QubitRoute. The exception escaped mid-slot, so the content slot
                // was captured only as far as Cancel and the page lost both the
                // confirm button and the rest of its layout - which is why it
                // appeared to have no styling.
                ?>
                <form method="post" action="<?php echo url_for(['module' => 'feedback', 'action' => 'delete', 'id' => $resource->id]) ?>">
                    <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">
                    <input type="hidden" name="confirm" value="1">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'feedback', 'action' => 'browse']) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> <?php echo __('Cancel') ?>
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> <?php echo __('Yes, delete') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php end_slot() ?>
