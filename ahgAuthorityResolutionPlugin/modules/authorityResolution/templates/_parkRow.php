<?php
/**
 * Partial: one parked-mention row for the park-queue table (Task 7).
 *
 * Locals expected:
 *   $row         row object from ParkQueueService::listFor
 *   $typeBadges  array of entity-type => bootstrap colour suffix
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<tr<?php echo !empty($row->new_candidate_available) ? ' class="table-warning"' : ''; ?>>
  <td>
    <strong><?php echo htmlspecialchars((string) $row->entity_value); ?></strong>
    <div class="text-muted small">#<?php echo (int) $row->mention_id; ?></div>
  </td>
  <td>
    <span class="badge bg-<?php echo $typeBadges[$row->entity_type] ?? 'secondary'; ?>">
      <?php echo htmlspecialchars((string) $row->entity_type); ?>
    </span>
  </td>
  <td>
    <?php if (!empty($row->io_slug)): ?>
      <a href="/<?php echo htmlspecialchars((string) $row->io_slug); ?>" target="_blank" rel="noopener">
        <?php echo htmlspecialchars($row->io_title ?: ('Object #' . (int) $row->object_id)); ?>
        <i class="fas fa-external-link-alt fa-xs ms-1 text-muted"></i>
      </a>
    <?php else: ?>
      <span class="text-muted">Object #<?php echo (int) $row->object_id; ?></span>
    <?php endif; ?>
  </td>
  <td>
    <span class="text-muted small">
      <?php echo htmlspecialchars($row->parked_by_username ?: ('User #' . (int) $row->parked_by_user_id)); ?>
    </span>
  </td>
  <td class="text-muted small"><?php echo htmlspecialchars((string) $row->parked_at); ?></td>
  <td>
    <small><?php echo nl2br(htmlspecialchars((string) $row->reason)); ?></small>
  </td>
  <td class="text-center">
    <?php if (!empty($row->new_candidate_available)): ?>
      <span class="badge bg-warning text-dark" title="<?php echo htmlspecialchars((string) $row->new_candidate_check_at); ?>">
        <i class="fas fa-bell me-1"></i><?php echo __('New'); ?>
      </span>
    <?php else: ?>
      <span class="text-muted">-</span>
    <?php endif; ?>
  </td>
  <td class="text-nowrap">
    <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $row->mention_id); ?>"
       class="btn btn-sm btn-outline-primary">
      <i class="fas fa-search me-1"></i><?php echo __('View'); ?>
    </a>
    <form method="post" action="<?php echo url_for('@ar_auth_res_unpark?id=' . (int) $row->mention_id); ?>"
          class="d-inline"
          onsubmit="return confirm('<?php echo __('Un-park this mention? Candidates will be regenerated and the mention returns to the pending queue.'); ?>');">
      <button type="submit" class="btn btn-sm btn-outline-success">
        <i class="fas fa-play me-1"></i><?php echo __('Un-park & re-review'); ?>
      </button>
    </form>
  </td>
</tr>
