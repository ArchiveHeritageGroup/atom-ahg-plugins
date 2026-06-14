<?php
$s = $sf_data->getRaw('summary') ?: [];
$byRisk = $s['systems_by_risk'] ?? [];
$riskColors = ['prohibited' => 'dark', 'high' => 'danger', 'limited' => 'warning', 'minimal' => 'success'];
function aiact_card($href, $icon, $label, $value, $sub = '', $tone = 'primary')
{
    echo '<div class="col-md-3 mb-3"><a class="text-decoration-none" href="' . $href . '">';
    echo '<div class="card h-100 border-' . $tone . '"><div class="card-body">';
    echo '<div class="text-muted small text-uppercase"><i class="fas ' . $icon . ' me-1"></i>' . $label . '</div>';
    echo '<div class="display-6">' . (int) $value . '</div>';
    if ($sub !== '') {
        echo '<div class="small text-' . $tone . '">' . $sub . '</div>';
    }
    echo '</div></div></a></div>';
}
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-scale-balanced me-2"></i><?php echo __('EU AI Act Governance'); ?></h1>
  </div>

  <p class="text-muted">
    <?php echo __('Inventory, risk management and conformity records for the EU AI Act (Articles 6, 9, 10, 11, 13, 14, 47/48). The tamper-evident Article 12 inference receipt chain is recorded separately.'); ?>
  </p>

  <div class="row">
    <?php
        aiact_card(url_for(['module' => 'aiActGovernance', 'action' => 'systems']), 'fa-microchip', __('AI systems'), $s['systems_total'] ?? 0, ($s['systems_review_overdue'] ?? 0) ? (((int) $s['systems_review_overdue']) . ' ' . __('review overdue')) : '', ($s['systems_review_overdue'] ?? 0) ? 'warning' : 'primary');
        aiact_card(url_for(['module' => 'aiActGovernance', 'action' => 'models']), 'fa-cubes', __('Models'), $s['models_total'] ?? 0, '', 'primary');
        aiact_card(url_for(['module' => 'aiActGovernance', 'action' => 'risks']), 'fa-triangle-exclamation', __('Open risks'), $s['risks_open'] ?? 0, ($s['risks_high'] ?? 0) ? (((int) $s['risks_high']) . ' ' . __('high/critical')) : '', ($s['risks_high'] ?? 0) ? 'danger' : 'success');
        aiact_card(url_for(['module' => 'aiActGovernance', 'action' => 'attestations']), 'fa-file-signature', __('Attestations'), $s['attestations_total'] ?? 0, ($s['attestations_overdue'] ?? 0) ? (((int) $s['attestations_overdue']) . ' ' . __('overdue')) : (((int) ($s['attestations_attested'] ?? 0)) . ' ' . __('attested')), ($s['attestations_overdue'] ?? 0) ? 'warning' : 'success');
    ?>
  </div>

  <div class="card mt-2">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Systems by risk classification'); ?></h5></div>
    <div class="card-body">
      <?php foreach ($riskColors as $rc => $tone): ?>
        <span class="badge bg-<?php echo $tone; ?> me-2 mb-2 p-2">
          <?php echo __(ucfirst($rc)); ?>: <?php echo (int) ($byRisk[$rc] ?? 0); ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</main>
