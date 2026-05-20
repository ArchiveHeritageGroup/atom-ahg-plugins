<?php
/**
 * Partial: "View full context" modal (Authority Resolution review screen).
 *
 * Renders an empty BS5 modal shell whose body is populated by JS from
 * GET /admin/authorityResolution/:id/context. The endpoint returns the full
 * concatenated source text of the mention's information object plus the
 * character + paragraph offsets of the mention occurrence. The JS slices the
 * raw string at the character offsets, HTML-escapes each slice, then wraps
 * the mention span in <mark> and shades the enclosing paragraph - so the
 * archivist sees the whole document with the occurrence highlighted.
 *
 * Locals expected:
 *   $mention - object from ahg_mention (only ->id is used here)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<div class="modal fade" id="ar-context-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Full context'); ?>
          <span class="text-muted small">#<?php echo (int) $mention->id; ?></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
      </div>
      <div class="modal-body">
        <div id="ar-context-loading" class="text-center text-muted py-4">
          <i class="fas fa-spinner fa-spin me-1"></i><?php echo __('Loading source text...'); ?>
        </div>
        <div id="ar-context-error" class="alert alert-danger d-none" role="alert"></div>
        <div id="ar-context-note" class="alert alert-warning small d-none" role="alert">
          <i class="fas fa-info-circle me-1"></i>
          <?php echo __('Exact position not recorded for this mention - showing the full source text.'); ?>
        </div>
        <div id="ar-context-body" class="border rounded p-3 bg-light d-none"
             style="white-space: pre-wrap; line-height: 1.7; max-height: 60vh; overflow-y: auto;"></div>
      </div>
      <div class="modal-footer">
        <span class="me-auto small text-muted">
          <mark class="bg-warning px-1"><?php echo __('mention'); ?></mark>
          <span class="ms-2 px-1" style="background-color: rgba(255,193,7,0.18);"><?php echo __('enclosing paragraph'); ?></span>
        </span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
