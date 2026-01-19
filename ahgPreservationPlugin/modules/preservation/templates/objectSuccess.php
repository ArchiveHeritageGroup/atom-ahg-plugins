<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-file-earmark-binary text-primary me-2"></i><?php echo __('Preservation Details'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>"><?php echo __('Preservation'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($digitalObject->name ?? 'Object'); ?></li>
    </ol>
</nav>

<!-- Object Info -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-info-circle me-2"></i><?php echo __('Digital Object Information'); ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="150"><?php echo __('ID'); ?></th>
                        <td><?php echo $digitalObject->id; ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Filename'); ?></th>
                        <td><?php echo htmlspecialchars($digitalObject->name ?? 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('Parent Object'); ?></th>
                        <td>
                            <?php if ($digitalObject->slug): ?>
                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $digitalObject->slug]); ?>">
                                    <?php echo htmlspecialchars($digitalObject->object_title ?? 'View'); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo __('File Size'); ?></th>
                        <td><?php echo number_format($digitalObject->byte_size ?? 0); ?> bytes</td>
                    </tr>
                    <tr>
                        <th><?php echo __('MIME Type'); ?></th>
                        <td><?php echo htmlspecialchars($digitalObject->mime_type ?? 'Unknown'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <?php if ($formatInfo): ?>
                <div class="alert <?php echo $formatInfo->risk_level === 'low' ? 'alert-success' : ($formatInfo->risk_level === 'high' || $formatInfo->risk_level === 'critical' ? 'alert-danger' : 'alert-warning'); ?>">
                    <h6><i class="bi bi-file-code me-1"></i><?php echo __('Format Information'); ?></h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($formatInfo->format_name); ?></strong></p>
                    <p class="mb-1">Risk Level: <strong><?php echo ucfirst($formatInfo->risk_level ?? 'unknown'); ?></strong></p>
                    <?php if ($formatInfo->is_preservation_format): ?>
                        <span class="badge bg-success"><?php echo __('Preservation Format'); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="generateChecksums(<?php echo $digitalObject->id; ?>)">
                        <i class="bi bi-arrow-repeat me-1"></i><?php echo __('Regenerate Checksums'); ?>
                    </button>
                    <button class="btn btn-outline-primary" onclick="verifyFixity(<?php echo $digitalObject->id; ?>)">
                        <i class="bi bi-check-circle me-1"></i><?php echo __('Verify Fixity Now'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checksums -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-fingerprint me-2"></i><?php echo __('Checksums'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Value'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Generated'); ?></th>
                    <th><?php echo __('Last Verified'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($checksums)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No checksums generated yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($checksums as $cs): ?>
                    <tr>
                        <td><strong><?php echo strtoupper($cs->algorithm); ?></strong></td>
                        <td><code style="font-size: 0.8em;"><?php echo $cs->checksum_value; ?></code></td>
                        <td>
                            <?php if ($cs->verification_status === 'valid'): ?>
                                <span class="badge bg-success">Valid</span>
                            <?php elseif ($cs->verification_status === 'invalid'): ?>
                                <span class="badge bg-danger">Invalid</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($cs->verification_status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('Y-m-d H:i', strtotime($cs->generated_at)); ?></small></td>
                        <td><small><?php echo $cs->verified_at ? date('Y-m-d H:i', strtotime($cs->verified_at)) : '-'; ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Fixity History -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i><?php echo __('Fixity Check History'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Status'); ?></th>
                    <th><?php echo __('Checked By'); ?></th>
                    <th><?php echo __('Duration'); ?></th>
                    <th><?php echo __('Checked At'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fixityHistory)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No fixity checks performed yet'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($fixityHistory as $check): ?>
                    <tr>
                        <td><?php echo strtoupper($check->algorithm); ?></td>
                        <td>
                            <?php if ($check->status === 'pass'): ?>
                                <span class="badge bg-success">Pass</span>
                            <?php elseif ($check->status === 'fail'): ?>
                                <span class="badge bg-danger">Fail</span>
                            <?php else: ?>
                                <span class="badge bg-warning"><?php echo ucfirst($check->status); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($check->checked_by); ?></td>
                        <td><?php echo $check->duration_ms; ?>ms</td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($check->checked_at)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Preservation Events -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-calendar-event me-2"></i><?php echo __('Preservation Events (PREMIS)'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Event Type'); ?></th>
                    <th><?php echo __('Detail'); ?></th>
                    <th><?php echo __('Outcome'); ?></th>
                    <th><?php echo __('Agent'); ?></th>
                    <th><?php echo __('Date/Time'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <?php echo __('No preservation events recorded'); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo str_replace('_', ' ', $event->event_type); ?></span></td>
                        <td><?php echo htmlspecialchars(substr($event->event_detail ?? '', 0, 50)); ?></td>
                        <td>
                            <?php if ($event->event_outcome === 'success'): ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Success</span>
                            <?php elseif ($event->event_outcome === 'failure'): ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> Failure</span>
                            <?php else: ?>
                                <span class="text-muted"><?php echo ucfirst($event->event_outcome); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($event->linking_agent_value ?? '-'); ?></small></td>
                        <td><small><?php echo date('Y-m-d H:i:s', strtotime($event->event_datetime)); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function generateChecksums(id) {
    if (!confirm('Generate new checksums for this object?')) return;

    fetch('/api/preservation/checksum/' + id + '/generate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Checksums generated successfully');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}

function verifyFixity(id) {
    fetch('/api/preservation/fixity/' + id + '/verify', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = 'Fixity verification complete:\n';
            for (let algo in data.results) {
                msg += algo.toUpperCase() + ': ' + data.results[algo].status + '\n';
            }
            alert(msg);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(e => alert('Error: ' + e));
}
</script>

<?php end_slot() ?>
