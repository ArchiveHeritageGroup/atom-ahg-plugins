<?php slot('title', __('Format Identification (PRONOM)')); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-fingerprint"></i> Format Identification</h1>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
    </a>
</div>

<!-- Siegfried Status -->
<div class="card mb-4 <?php echo $siegfriedAvailable ? 'border-success' : 'border-danger' ?>">
    <div class="card-header <?php echo $siegfriedAvailable ? 'bg-success text-white' : 'bg-danger text-white' ?>">
        <h5 class="mb-0">
            <i class="fas <?php echo $siegfriedAvailable ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            Siegfried - PRONOM Format Identification
        </h5>
    </div>
    <div class="card-body">
        <?php if ($siegfriedAvailable): ?>
            <div class="row">
                <div class="col-md-4">
                    <strong>Status:</strong> <span class="badge bg-success">Available</span>
                </div>
                <div class="col-md-4">
                    <strong>Version:</strong> <?php echo $siegfriedVersion['version'] ?? 'Unknown' ?>
                </div>
                <div class="col-md-4">
                    <strong>Signature Date:</strong> <?php echo $siegfriedVersion['signature_date'] ?? 'Unknown' ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger mb-0">
                <strong>Siegfried is not installed.</strong>
                <p class="mb-0 mt-2">Install with:</p>
                <code>curl -sL "https://github.com/richardlehane/siegfried/releases/download/v1.11.1/siegfried_1.11.1-1_amd64.deb" -o /tmp/sf.deb && sudo dpkg -i /tmp/sf.deb</code>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo number_format($stats['total_objects'] ?? 0) ?></h3>
                <small>Total Objects</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo number_format($stats['identified'] ?? 0) ?></h3>
                <small>Identified</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3><?php echo number_format($stats['unidentified'] ?? 0) ?></h3>
                <small>Unidentified</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo number_format($stats['coverage_percent'] ?? 0, 1) ?>%</h3>
                <small>Coverage</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Confidence Distribution -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> By Confidence</h5>
            </div>
            <div class="card-body">
                <?php $byConfidence = $stats['by_confidence'] ?? []; ?>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check-double text-success"></i> Certain</span>
                        <span class="badge bg-success"><?php echo $byConfidence['certain'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check text-primary"></i> High</span>
                        <span class="badge bg-primary"><?php echo $byConfidence['high'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-minus text-warning"></i> Medium</span>
                        <span class="badge bg-warning text-dark"><?php echo $byConfidence['medium'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-question text-danger"></i> Low</span>
                        <span class="badge bg-danger"><?php echo $byConfidence['low'] ?? 0 ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Format Registry Risk -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Registry by Risk</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-success"></i> Low Risk</span>
                        <span class="badge bg-success"><?php echo $formatsByRisk['low'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-warning"></i> Medium Risk</span>
                        <span class="badge bg-warning text-dark"><?php echo $formatsByRisk['medium'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-circle text-danger"></i> High Risk</span>
                        <span class="badge bg-danger"><?php echo $formatsByRisk['high'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-exclamation-triangle text-dark"></i> Critical Risk</span>
                        <span class="badge bg-dark"><?php echo $formatsByRisk['critical'] ?? 0 ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- With Warnings -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> With Warnings</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h2 class="<?php echo ($stats['with_warnings'] ?? 0) > 0 ? 'text-warning' : 'text-success' ?>">
                        <?php echo $stats['with_warnings'] ?? 0 ?>
                    </h2>
                    <small class="text-muted">Objects with identification warnings</small>
                </div>
                <?php
                  $warningsArray = $identificationsWithWarnings instanceof sfOutputEscaperArrayDecorator
                      ? $identificationsWithWarnings->getRawValue()
                      : (array) $identificationsWithWarnings;
                ?>
                <?php if (!empty($warningsArray)): ?>
                    <hr>
                    <small class="text-muted">Recent warnings:</small>
                    <ul class="list-unstyled small mt-2">
                        <?php foreach (array_slice($warningsArray, 0, 3) as $item): ?>
                            <li class="text-truncate" title="<?php echo htmlspecialchars($item->warning) ?>">
                                <i class="fas fa-exclamation-circle text-warning"></i>
                                <?php echo htmlspecialchars($item->object_name) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Formats -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list-ol"></i> Top 10 Identified Formats</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>PUID</th>
                        <th>Format Name</th>
                        <th class="text-end">Count</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totalIdentified = $stats['identified'] ?? 1;
                    $rank = 1;
                    foreach ($topFormats as $format):
                        $percentage = $totalIdentified > 0 ? ($format->count / $totalIdentified) * 100 : 0;
                    ?>
                    <tr>
                        <td><?php echo $rank++ ?></td>
                        <td>
                            <?php if ($format->puid): ?>
                                <a href="https://www.nationalarchives.gov.uk/PRONOM/<?php echo htmlspecialchars($format->puid) ?>" target="_blank" class="text-decoration-none">
                                    <code><?php echo htmlspecialchars($format->puid) ?></code>
                                    <i class="fas fa-external-link-alt fa-xs"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($format->format_name ?? 'Unknown') ?></td>
                        <td class="text-end"><?php echo number_format($format->count) ?></td>
                        <td style="width: 200px;">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percentage ?>%">
                                    <?php echo number_format($percentage, 1) ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Identifications -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Identifications</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Object</th>
                        <th>PUID</th>
                        <th>Format</th>
                        <th>MIME Type</th>
                        <th>Confidence</th>
                        <th>Basis</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentIdentifications as $item): ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $item->digital_object_id]) ?>">
                                <?php echo htmlspecialchars($item->object_name ?? "ID:{$item->digital_object_id}") ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($item->puid): ?>
                                <a href="https://www.nationalarchives.gov.uk/PRONOM/<?php echo htmlspecialchars($item->puid) ?>" target="_blank">
                                    <code><?php echo htmlspecialchars($item->puid) ?></code>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item->format_name ?? '-') ?></td>
                        <td><small><?php echo htmlspecialchars($item->mime_type ?? '-') ?></small></td>
                        <td>
                            <?php
                            $confidenceBadge = [
                                'certain' => 'bg-success',
                                'high' => 'bg-primary',
                                'medium' => 'bg-warning text-dark',
                                'low' => 'bg-danger',
                            ];
                            $badge = $confidenceBadge[$item->confidence] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $badge ?>"><?php echo $item->confidence ?></span>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars(substr($item->basis ?? '-', 0, 40)) ?></small></td>
                        <td><small><?php echo date('Y-m-d H:i', strtotime($item->identification_date)) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card border-info">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-terminal"></i> CLI Commands</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Batch Identification</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># Check status
php symfony preservation:identify --status

# Identify unidentified objects
php symfony preservation:identify --limit=500

# Preview without identifying
php symfony preservation:identify --dry-run

# Re-identify all objects
php symfony preservation:identify --all --limit=1000</code></pre>
            </div>
            <div class="col-md-6">
                <h6>Single Object</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># Identify specific object
php symfony preservation:identify --object-id=123

# Force re-identification
php symfony preservation:identify --object-id=123 --reidentify</code></pre>
                <h6 class="mt-3">Cron Schedule</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># Daily identification at 1am
0 1 * * * cd /usr/share/nginx/archive && \
  php symfony preservation:identify --limit=500</code></pre>
            </div>
        </div>
    </div>
</div>
