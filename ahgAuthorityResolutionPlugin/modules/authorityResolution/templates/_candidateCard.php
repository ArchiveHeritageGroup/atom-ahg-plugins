<?php
/**
 * Partial: one candidate card for the middle column.
 *
 * Locals expected:
 *   $candidate    - object from ahg_mention_candidate
 *   $mention      - object from ahg_mention (for entity_type)
 *   $coord        - ['lat'=>..,'lng'=>..] or null (PLACE only)
 *   $is_top       - bool true for rank 1 (highest composite)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */

$signals = $candidate->evidence_signals !== null && $candidate->evidence_signals !== ''
  ? json_decode((string) $candidate->evidence_signals, true)
  : [];
$evdata  = $candidate->evidence_data !== null && $candidate->evidence_data !== ''
  ? json_decode((string) $candidate->evidence_data, true)
  : [];

$sourceBadge = [
  'mysql_actor'   => ['cls' => 'primary', 'label' => 'Local actor'],
  'fuseki_agent'  => ['cls' => 'info',    'label' => 'Fuseki agent'],
  'mysql_term'    => ['cls' => 'success', 'label' => 'Local term'],
  'fuseki_place'  => ['cls' => 'info',    'label' => 'Fuseki place'],
];
$srcCfg = $sourceBadge[$candidate->candidate_source] ?? ['cls' => 'secondary', 'label' => $candidate->candidate_source];
$compositeScore = $candidate->composite_score !== null ? (float) $candidate->composite_score : null;
?>
<div class="card mb-3 <?php echo $is_top ? 'border-success' : ''; ?>" data-candidate-id="<?php echo (int) $candidate->id; ?>">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong><?php echo htmlspecialchars((string) $candidate->candidate_display_name); ?></strong>
      <?php if ($is_top): ?>
        <span class="badge bg-success ms-1"><i class="fas fa-star"></i> top</span>
      <?php endif; ?>
      <span class="badge bg-<?php echo $srcCfg['cls']; ?> ms-1"><?php echo $srcCfg['label']; ?></span>
    </div>
    <div class="text-end">
      <small class="text-muted d-block">rank #<?php echo (int) $candidate->rank_position; ?></small>
      <?php if ($compositeScore !== null): ?>
        <strong class="text-success">
          <?php echo number_format($compositeScore, 3); ?>
        </strong>
      <?php else: ?>
        <small class="text-muted">no score</small>
      <?php endif; ?>
    </div>
  </div>
  <div class="card-body py-2">
    <div class="d-flex justify-content-between mb-2 small">
      <span>
        <?php if ($candidate->candidate_authority_id !== null): ?>
          <i class="fas fa-id-badge text-muted me-1"></i>
          authority #<?php echo (int) $candidate->candidate_authority_id; ?>
        <?php elseif ($candidate->candidate_fuseki_uri): ?>
          <i class="fas fa-link text-muted me-1"></i>
          <code class="small"><?php echo htmlspecialchars((string) $candidate->candidate_fuseki_uri); ?></code>
        <?php endif; ?>
      </span>
      <span class="text-muted">
        name sim: <?php echo number_format((float) $candidate->name_similarity_score, 3); ?>
      </span>
    </div>

    <?php if (!empty($signals)): ?>
      <table class="table table-sm mb-2">
        <thead class="visually-hidden">
          <tr><th>Dimension</th><th>Signal</th><th>Detail</th></tr>
        </thead>
        <tbody>
          <?php foreach ($signals as $dim => $sig):
            $detail = '';
            if (isset($evdata[$dim]) && is_array($evdata[$dim])) {
              if (isset($evdata[$dim]['reason'])) {
                $detail = (string) $evdata[$dim]['reason'];
              } else {
                $detail = json_encode($evdata[$dim], JSON_UNESCAPED_UNICODE);
                if (strlen($detail) > 120) {
                  $detail = substr($detail, 0, 117) . '...';
                }
              }
            }
            include_partial('authorityResolution/evidenceRow', [
              'dimension' => $dim,
              'signal' => $sig,
              'detail' => $detail,
            ]);
          endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="text-muted small mb-2"><?php echo __('No evidence signals computed.'); ?></p>
    <?php endif; ?>

    <?php if (!empty($candidate->authority_slug)): ?>
      <a href="/<?php echo htmlspecialchars((string) $candidate->authority_slug); ?>"
         class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
        <i class="fas fa-eye me-1"></i><?php echo __('View authority'); ?>
      </a>
    <?php elseif ($candidate->candidate_authority_id !== null): ?>
      <span class="text-muted small">
        <i class="fas fa-id-badge me-1"></i><?php echo __('authority #'); ?><?php echo (int) $candidate->candidate_authority_id; ?>
      </span>
    <?php endif; ?>

    <?php if ($coord !== null): ?>
      <div class="mt-2 ar-map" id="ar-map-cand-<?php echo (int) $candidate->id; ?>"
           data-lat="<?php echo (float) $coord['lat']; ?>"
           data-lng="<?php echo (float) $coord['lng']; ?>"
           style="height: 160px; border: 1px solid #dee2e6; border-radius: 4px;"></div>
    <?php endif; ?>
  </div>

  <div class="card-footer d-flex gap-1 flex-wrap py-2">
    <form method="post" action="<?php echo url_for('@ar_auth_res_link?id=' . (int) $mention->id); ?>" class="d-inline">
      <input type="hidden" name="candidate_id" value="<?php echo (int) $candidate->id; ?>">
      <button type="submit" class="btn btn-sm btn-success">
        <i class="fas fa-check me-1"></i><?php echo __('Link to this'); ?>
      </button>
    </form>
  </div>
</div>
