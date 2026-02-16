<?php use_helper('Text'); ?>
<?php
// Query the database directly to bypass Symfony's sfOutputEscaper pipeline
// which corrupts Laravel Collections and grouped arrays.
use Illuminate\Database\Capsule\Manager as Capsule;

$rightsStatementsRaw = Capsule::table('rights_statement')
    ->leftJoin('rights_statement_i18n', function ($j) {
        $j->on('rights_statement_i18n.rights_statement_id', '=', 'rights_statement.id')
          ->where('rights_statement_i18n.culture', '=', 'en');
    })
    ->where('rights_statement.is_active', '=', 1)
    ->orderBy('rights_statement.category')
    ->orderBy('rights_statement.sort_order')
    ->select([
        'rights_statement.id',
        'rights_statement.code',
        'rights_statement.uri',
        'rights_statement.category',
        'rights_statement.icon_filename',
        'rights_statement.icon_url',
        'rights_statement_i18n.name',
        'rights_statement_i18n.definition as description',
    ])->get();

$ccLicensesRaw = Capsule::table('rights_cc_license')
    ->leftJoin('rights_cc_license_i18n', function ($j) {
        $j->on('rights_cc_license_i18n.id', '=', 'rights_cc_license.id')
          ->where('rights_cc_license_i18n.culture', '=', 'en');
    })
    ->where('rights_cc_license.is_active', '=', 1)
    ->orderBy('rights_cc_license.sort_order')
    ->select([
        'rights_cc_license.id',
        'rights_cc_license.code',
        'rights_cc_license.uri',
        'rights_cc_license_i18n.name',
        'rights_cc_license_i18n.description',
    ])->get();

$tkLabelsRaw = Capsule::table('rights_tk_label')
    ->leftJoin('rights_tk_label_i18n', function ($j) {
        $j->on('rights_tk_label_i18n.id', '=', 'rights_tk_label.id')
          ->where('rights_tk_label_i18n.culture', '=', 'en');
    })
    ->where('rights_tk_label.is_active', '=', 1)
    ->orderBy('rights_tk_label.category')
    ->orderBy('rights_tk_label.sort_order')
    ->select([
        'rights_tk_label.id',
        'rights_tk_label.code',
        'rights_tk_label.uri',
        'rights_tk_label.category',
        'rights_tk_label.color',
        'rights_tk_label_i18n.name',
        'rights_tk_label_i18n.description',
    ])->get();

$statsRaw = isset($sf_data) ? $sf_data->getRaw('stats') : (isset($stats) ? $stats : null);
?>

<div class="row">
  <div class="col-md-3">
    <div class="card mb-4">
      <div class="card-header"><strong>Rights Vocabularies</strong></div>
      <div class="card-body">
        <ul class="nav flex-column">
          <li class="nav-item"><a class="nav-link" href="#rights-statements">RightsStatements.org</a></li>
          <li class="nav-item"><a class="nav-link" href="#creative-commons">Creative Commons</a></li>
          <li class="nav-item"><a class="nav-link" href="#tk-labels">TK Labels</a></li>
        </ul>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <h1 class="mb-4">Extended Rights Management</h1>

    <?php if ($sf_user->hasFlash('notice')): ?>
      <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Rights Statements -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="rights-statements">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">RightsStatements.org</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Standardized rights statements for cultural heritage institutions.</p>
            <?php if (count($rightsStatementsRaw) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($rightsStatementsRaw as $rs): ?>
                  <li class="mb-2">
                    <?php if (!empty($rs->uri)): ?>
                      <a href="<?php echo htmlspecialchars($rs->uri); ?>" target="_blank" title="<?php echo htmlspecialchars($rs->description ?? ''); ?>">
                        <?php echo htmlspecialchars($rs->name ?? $rs->code ?? ''); ?>
                      </a>
                    <?php else: ?>
                      <?php echo htmlspecialchars($rs->name ?? $rs->code ?? ''); ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No rights statements configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Creative Commons -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="creative-commons">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">Creative Commons</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Open licensing for sharing and reuse.</p>
            <?php if (count($ccLicensesRaw) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($ccLicensesRaw as $cc): ?>
                  <li class="mb-2">
                    <?php if (!empty($cc->uri)): ?>
                      <a href="<?php echo htmlspecialchars($cc->uri); ?>" target="_blank">
                        <?php echo htmlspecialchars($cc->name ?? $cc->code ?? ''); ?>
                      </a>
                    <?php else: ?>
                      <?php echo htmlspecialchars($cc->name ?? $cc->code ?? ''); ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No Creative Commons licenses configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- TK Labels -->
      <div class="col-md-4 mb-4">
        <div class="card h-100" id="tk-labels">
          <div class="card-header" style="background-color: #1a4d2e; color: white;">
            <h5 class="mb-0">Traditional Knowledge Labels</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small">Labels for Indigenous cultural heritage.</p>
            <?php if (count($tkLabelsRaw) > 0): ?>
              <ul class="list-unstyled">
                <?php foreach ($tkLabelsRaw as $tk): ?>
                  <li class="mb-2">
                    <?php if (!empty($tk->icon_url)): ?>
                      <img src="<?php echo htmlspecialchars($tk->icon_url); ?>" alt="" style="width: 20px; height: 20px;" class="me-1">
                    <?php endif; ?>
                    <?php if (!empty($tk->uri)): ?>
                      <a href="<?php echo htmlspecialchars($tk->uri); ?>" target="_blank">
                        <?php echo htmlspecialchars($tk->name ?? $tk->code ?? ''); ?>
                      </a>
                    <?php else: ?>
                      <?php echo htmlspecialchars($tk->name ?? $tk->code ?? ''); ?>
                    <?php endif; ?>
                    <?php if (!empty($tk->category)): ?>
                      <small class="text-muted">(<?php echo htmlspecialchars($tk->category); ?>)</small>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted">No TK Labels configured.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <?php if (isset($statsRaw)): ?>
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">Rights Coverage Statistics</h5>
      </div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col">
            <h3><?php echo number_format($statsRaw->total_objects ?? 0); ?></h3>
            <small class="text-muted">Total Objects</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($statsRaw->with_rights_statement ?? 0); ?></h3>
            <small class="text-muted">With Rights Statement</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($statsRaw->with_creative_commons ?? 0); ?></h3>
            <small class="text-muted">With CC License</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($statsRaw->with_tk_labels ?? 0); ?></h3>
            <small class="text-muted">With TK Labels</small>
          </div>
          <div class="col">
            <h3><?php echo number_format($statsRaw->active_embargoes ?? 0); ?></h3>
            <small class="text-muted">Active Embargoes</small>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Admin Actions -->
    <?php if ($sf_user->isAuthenticated()): ?>
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">Administration</h5>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'batch']); ?>" class="btn btn-primary">
          <i class="fas fa-layer-group me-1"></i> Batch Assign Rights
        </a>
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'embargoes']); ?>" class="btn btn-warning">
          <i class="fas fa-lock me-1"></i> Manage Embargoes
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
