<?php
/**
 * Partial: assign-to-archivist modal (Task 12: Assign / Workflow).
 *
 * Posts archivist_user_id to @ar_auth_res_assign. Assigning a mention routes
 * it through ahgWorkflowPlugin (an ahg_workflow_task is created or re-used and
 * assigned to the chosen archivist).
 *
 * Locals expected:
 *   $mention    - object from ahg_mention
 *   $archivists - array<int,array{id,username,display}> from AssignmentService
 *   $assignment - object|null with current assignment (assigned_to_username...)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<div class="modal fade" id="ar-assign-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo url_for('@ar_auth_res_assign?id=' . (int) $mention->id); ?>">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-plus me-2"></i><?php echo __('Assign this mention'); ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">
            <?php echo __('Assigning routes this mention through the Workflow plugin and notifies the chosen archivist. Re-assigning moves the existing workflow task.'); ?>
          </p>

          <?php if (!empty($assignment) && !empty($assignment->assigned_to_username)): ?>
            <div class="alert alert-info py-2 small mb-3">
              <i class="fas fa-user-check me-1"></i>
              <?php echo __('Currently assigned to'); ?>:
              <strong><?php echo htmlspecialchars((string) $assignment->assigned_to_username); ?></strong>
              <?php if (!empty($assignment->workflow_task_id)): ?>
                <span class="text-muted">(<?php echo __('workflow task'); ?> #<?php echo (int) $assignment->workflow_task_id; ?>)</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="ar-assign-archivist" class="form-label">
              <?php echo __('Archivist'); ?>
            </label>
            <select name="archivist_user_id" id="ar-assign-archivist" class="form-select" required>
              <option value=""><?php echo __('Select an archivist...'); ?></option>
              <?php foreach ($archivists as $a): ?>
                <option value="<?php echo (int) $a['id']; ?>"
                  <?php echo (!empty($assignment) && (int) $assignment->assigned_to_user_id === (int) $a['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $a['display']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($archivists)): ?>
              <div class="form-text text-danger"><?php echo __('No eligible archivists found.'); ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="ar-assign-reason" class="form-label">
              <?php echo __('Reason / message (optional)'); ?>
            </label>
            <textarea name="reason" id="ar-assign-reason" class="form-control" rows="3"
                      placeholder="<?php echo __('Add a note for the archivist...'); ?>"></textarea>
            <div class="form-text">
              <?php echo __('Recorded on the workflow task as the assignment comment.'); ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i><?php echo __('Assign'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
