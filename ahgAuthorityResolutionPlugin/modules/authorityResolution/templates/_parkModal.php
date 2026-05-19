<?php
/**
 * Partial: park-with-reason modal.
 *
 * Locals expected:
 *   $mention - object from ahg_mention
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<div class="modal fade" id="ar-park-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@ar_auth_res_park?id=' . (int) $mention->id); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-pause-circle me-2"></i><?php echo __('Park this mention'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            <?php echo __('Parking removes the mention from the active queue but keeps it open for revisit when new authority candidates appear. State changes to "parked".'); ?>
          </p>
          <div class="mb-3">
            <label for="ar-park-reason" class="form-label"><?php echo __('Reason'); ?> <span class="text-danger">*</span></label>
            <textarea name="reason" id="ar-park-reason" class="form-control" rows="3"
                      placeholder="<?php echo __('e.g. Awaiting external authority lookup; insufficient distinguishing context.'); ?>"
                      required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-info">
            <i class="fas fa-pause me-1"></i><?php echo __('Park'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
