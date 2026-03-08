<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('New ILL Request'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-primary text-white">
    <i class="fas fa-exchange-alt me-2"></i><?php echo __('New Interlibrary Loan Request'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'ill', 'action' => 'edit']); ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label for="ill_direction" class="form-label"><?php echo __('Direction'); ?></label>
          <select class="form-select" id="ill_direction" name="direction">
            <option value="borrow"><?php echo __('Borrow (we request from another library)'); ?></option>
            <option value="lend"><?php echo __('Lend (another library requests from us)'); ?></option>
          </select>
        </div>

        <div class="col-md-6">
          <label for="ill_patron_id" class="form-label"><?php echo __('Patron ID'); ?></label>
          <input type="number" class="form-control" id="ill_patron_id" name="patron_id"
                 placeholder="<?php echo __('Optional — link to a patron record'); ?>">
        </div>

        <div class="col-md-6">
          <label for="ill_title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="ill_title" name="title" required>
        </div>

        <div class="col-md-6">
          <label for="ill_author" class="form-label"><?php echo __('Author'); ?></label>
          <input type="text" class="form-control" id="ill_author" name="author">
        </div>

        <div class="col-md-4">
          <label for="ill_isbn" class="form-label"><?php echo __('ISBN'); ?></label>
          <input type="text" class="form-control" id="ill_isbn" name="isbn">
        </div>

        <div class="col-md-4">
          <label for="ill_issn" class="form-label"><?php echo __('ISSN'); ?></label>
          <input type="text" class="form-control" id="ill_issn" name="issn">
        </div>

        <div class="col-md-4">
          <label for="ill_volume_issue" class="form-label"><?php echo __('Volume / Issue'); ?></label>
          <input type="text" class="form-control" id="ill_volume_issue" name="volume_issue">
        </div>

        <div class="col-md-4">
          <label for="ill_pages_needed" class="form-label"><?php echo __('Pages needed'); ?></label>
          <input type="text" class="form-control" id="ill_pages_needed" name="pages_needed"
                 placeholder="<?php echo __('e.g., 45-67'); ?>">
        </div>

        <div class="col-md-4">
          <label for="ill_requesting_library" class="form-label"><?php echo __('Requesting library'); ?></label>
          <input type="text" class="form-control" id="ill_requesting_library" name="requesting_library">
        </div>

        <div class="col-md-4">
          <label for="ill_lending_library" class="form-label"><?php echo __('Lending library'); ?></label>
          <input type="text" class="form-control" id="ill_lending_library" name="lending_library">
        </div>

        <div class="col-md-4">
          <label for="ill_needed_by" class="form-label"><?php echo __('Needed by date'); ?></label>
          <input type="date" class="form-control" id="ill_needed_by" name="needed_by_date">
        </div>

        <div class="col-12">
          <label for="ill_notes" class="form-label"><?php echo __('Notes'); ?></label>
          <textarea class="form-control" id="ill_notes" name="notes" rows="3"></textarea>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
        </button>
        <a href="<?php echo url_for(['module' => 'ill', 'action' => 'index']); ?>" class="btn btn-outline-secondary ms-2">
          <?php echo __('Cancel'); ?>
        </a>
      </div>
    </form>
  </div>
</div>
