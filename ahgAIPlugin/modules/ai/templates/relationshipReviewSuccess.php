<?php
/**
 * AI > Relationship review: relationships between people and organisations
 * proposed through the API (GLAM MCP). Approve writes an actor relation that
 * shows on both authority records.
 */
$proposals = $sf_data->getRaw('proposals');
$counts = $sf_data->getRaw('counts');
$status = $sf_data->getRaw('status');
$h = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$side = function (string $name, array $r) use ($h) {
    if (!$r['id']) {
        return $h($name).' <span class="badge bg-warning text-dark">'.__('not linked yet').'</span>';
    }

    return ($r['slug'] ? '<a href="/'.$h($r['slug']).'" target="_blank" rel="noopener">'.$h($r['name']).'</a>' : $h($r['name']))
        .($r['name'] !== $name ? ' <small class="text-muted">('.__('proposed as').' '.$h($name).')</small>' : '');
};
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-diagram-3 me-2"></i><?php echo __('Relationship review'); ?></h1>
    <div>
      <a href="/ai/ner/review" class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-tags me-1"></i><?php echo __('NER Review'); ?></a>
      <a href="/ai/suggest/review" class="btn btn-outline-primary btn-sm"><i class="bi bi-magic me-1"></i><?php echo __('Suggestions'); ?></a>
    </div>
  </div>

  <?php foreach (['notice' => 'success', 'error' => 'danger'] as $flash => $cls): ?>
    <?php if ($sf_user->hasFlash($flash)): ?>
      <div class="alert alert-<?php echo $cls; ?>"><?php echo $h($sf_user->getFlash($flash)); ?></div>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if (!$sf_data->getRaw('available')): ?>
    <div class="alert alert-warning"><?php echo __('Relationship proposals need the ahg_relationship_proposal table from the AI plugin\'s install.sql.'); ?></div>
  <?php else: ?>
    <ul class="nav nav-tabs mb-3">
      <?php foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')] as $key => $label): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $status === $key ? 'active' : ''; ?>" href="/ai/relationship/review?status=<?php echo $key; ?>">
            <?php echo $label; ?> <span class="badge bg-secondary"><?php echo (int) ($counts[$key] ?? 0); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!$proposals): ?>
      <p class="text-muted"><?php echo __('Nothing here.'); ?></p>
    <?php endif; ?>

    <?php foreach ($proposals as $p): ?>
      <div class="card mb-3">
        <div class="card-body">
          <p class="fs-5 mb-1">
            <?php echo $side($p->subject_name, $p->subject); ?>
            <strong class="mx-1"><?php echo $h($p->relation); ?></strong>
            <?php echo $side($p->object_name, $p->object); ?>
            <?php if ($p->date_text || $p->start_date): ?>
              <span class="text-muted ms-2"><?php echo $h($p->date_text ?: trim($p->start_date.' - '.$p->end_date, ' -')); ?></span>
            <?php endif; ?>
          </p>
          <p class="small text-muted mb-2">
            <?php echo __('From'); ?>
            <?php if ($p->slug): ?><a href="/<?php echo $h($p->slug); ?>" target="_blank" rel="noopener"><?php echo $h($p->title ?: $p->slug); ?></a><?php endif; ?>
            - <?php echo __('proposed by'); ?> <?php echo $h($p->proposed_by ?: '?'); ?>, <?php echo $h($p->created_at); ?>
            <?php if ($p->reviewed_at): ?> - <?php echo __('reviewed'); ?> <?php echo $h($p->reviewed_at); ?><?php endif; ?>
          </p>
          <?php if ($p->evidence): ?>
            <p class="mb-1"><i class="bi bi-quote me-1"></i><?php echo $h($p->evidence); ?></p>
          <?php endif; ?>
          <?php foreach (json_decode((string) $p->sources, true) ?: [] as $url): ?>
            <a href="<?php echo $h($url); ?>" class="small d-block" target="_blank" rel="noopener nofollow"><?php echo $h($url); ?></a>
          <?php endforeach; ?>
          <?php if ($p->review_notes): ?>
            <p class="small mb-0"><?php echo __('Notes'); ?>: <?php echo $h($p->review_notes); ?></p>
          <?php endif; ?>

          <?php if ('pending' === $p->status): ?>
            <form method="post" action="/ai/relationship/<?php echo (int) $p->id; ?>/decision" class="d-flex gap-2 mt-2 flex-wrap">
              <input type="text" name="notes" class="form-control form-control-sm w-auto" placeholder="<?php echo __('Notes (optional)'); ?>">
              <button type="submit" name="decision" value="approve" class="btn btn-sm btn-success" <?php echo $p->subject['id'] && $p->object['id'] ? '' : 'disabled title="'.__('Link both names first').'"'; ?>>
                <i class="bi bi-check-lg me-1"></i><?php echo __('Approve'); ?>
              </button>
              <button type="submit" name="decision" value="reject" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg me-1"></i><?php echo __('Reject'); ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
