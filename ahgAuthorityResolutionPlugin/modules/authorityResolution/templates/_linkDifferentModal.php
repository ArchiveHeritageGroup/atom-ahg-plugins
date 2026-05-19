<?php
/**
 * Partial: "Link to a different authority" modal.
 *
 * Locals expected:
 *   $mention - object from ahg_mention
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<div class="modal fade" id="ar-link-different-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@ar_auth_res_link_different?id=' . (int) $mention->id); ?>">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-search me-2"></i><?php echo __('Link to a different authority'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            <?php echo __('Search the local authority store for a record other than those listed in the ranked candidates. Selected record will be linked and a link_different decision will be recorded.'); ?>
          </p>

          <div class="mb-3">
            <label for="ar-link-different-search" class="form-label"><?php echo __('Search'); ?></label>
            <input type="text" id="ar-link-different-search" class="form-control"
                   placeholder="<?php echo __('Type a name...'); ?>" autocomplete="off"
                   data-entity-type="<?php echo htmlspecialchars((string) $mention->entity_type); ?>">
            <div id="ar-link-different-results" class="list-group mt-2" style="max-height: 320px; overflow-y: auto;"></div>
          </div>

          <input type="hidden" name="authority_id" id="ar-link-different-authority-id" value="">
          <div id="ar-link-different-selected" class="alert alert-success d-none">
            <strong><?php echo __('Selected'); ?>:</strong>
            <span id="ar-link-different-selected-name"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" id="ar-link-different-submit" class="btn btn-warning" disabled>
            <i class="fas fa-link me-1"></i><?php echo __('Link to selected'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
