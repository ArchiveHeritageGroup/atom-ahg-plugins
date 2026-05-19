<?php
/**
 * Partial: reject-with-reason modal (Task 9 plumbing).
 *
 * The rejection reason becomes a training signal for the upstream NER
 * model via ahg_ner_feedback. The reason is OPTIONAL on the backend so
 * legacy form-submit rejects still work, but the modal asks for it
 * explicitly so the captured row has substance.
 *
 * Locals expected:
 *   $mention - object from ahg_mention
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<div class="modal fade" id="ar-reject-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@ar_auth_res_reject?id=' . (int) $mention->id); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i><?php echo __('Reject this mention'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            <?php echo __('Rejecting marks the NER entity as not corresponding to any authority. The reason is captured as a training signal for the NER model.'); ?>
          </p>
          <div class="mb-3">
            <label for="ar-reject-reason" class="form-label">
              <?php echo __('Rejection reason'); ?>
              <span class="text-muted small">(<?php echo __('feeds NER retraining'); ?>)</span>
            </label>
            <textarea name="reason" id="ar-reject-reason" class="form-control" rows="3"
                      placeholder="<?php echo __('e.g. NER mis-typed; this is a date, not a place.'); ?>"></textarea>
            <div class="form-text">
              <?php echo __('Examples: "not an entity", "wrong type (this is a date, not a place)", "OCR artefact", "synonym for an existing record but cannot identify which".'); ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-outline-danger">
            <i class="fas fa-times me-1"></i><?php echo __('Reject'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
