<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Entity Resolution</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Entity Resolution</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proposeMatchModal"><i class="fas fa-plus me-1"></i>Propose Match</button>
</div>

<!-- Filter bar -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="research"><input type="hidden" name="action" value="entityResolution">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="proposed" <?php echo ($sf_request->getParameter('status') === 'proposed') ? 'selected' : ''; ?>>Proposed</option>
                    <option value="accepted" <?php echo ($sf_request->getParameter('status') === 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo ($sf_request->getParameter('status') === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Entity Type</label>
                <select name="entity_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="actor" <?php echo ($sf_request->getParameter('entity_type') === 'actor') ? 'selected' : ''; ?>>Actor</option>
                    <option value="information_object" <?php echo ($sf_request->getParameter('entity_type') === 'information_object') ? 'selected' : ''; ?>>Information Object</option>
                    <option value="repository" <?php echo ($sf_request->getParameter('entity_type') === 'repository') ? 'selected' : ''; ?>>Repository</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-0">Relationship</label>
                <select name="relationship_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="sameAs" <?php echo ($sf_request->getParameter('relationship_type') === 'sameAs') ? 'selected' : ''; ?>>sameAs</option>
                    <option value="relatedTo" <?php echo ($sf_request->getParameter('relationship_type') === 'relatedTo') ? 'selected' : ''; ?>>relatedTo</option>
                    <option value="partOf" <?php echo ($sf_request->getParameter('relationship_type') === 'partOf') ? 'selected' : ''; ?>>partOf</option>
                    <option value="memberOf" <?php echo ($sf_request->getParameter('relationship_type') === 'memberOf') ? 'selected' : ''; ?>>memberOf</option>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
        </form>
    </div>
</div>

<?php if (empty($proposals['items'] ?? [])): ?>
    <div class="alert alert-info">No entity resolution proposals matching your filters.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Entity A</th>
                <th></th>
                <th>Entity B</th>
                <th>Relationship</th>
                <th>Confidence</th>
                <th>Method</th>
                <th>Evidence</th>
                <th>Resolver</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($proposals['items'] as $p): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($p->entity_a_label ?? ($p->entity_a_type . ':' . $p->entity_a_id)); ?></strong>
                    <br><small class="text-muted"><?php echo htmlspecialchars($p->entity_a_type); ?> #<?php echo (int) $p->entity_a_id; ?></small>
                </td>
                <td><i class="fas fa-exchange-alt text-muted"></i></td>
                <td>
                    <strong><?php echo htmlspecialchars($p->entity_b_label ?? ($p->entity_b_type . ':' . $p->entity_b_id)); ?></strong>
                    <br><small class="text-muted"><?php echo htmlspecialchars($p->entity_b_type); ?> #<?php echo (int) $p->entity_b_id; ?></small>
                </td>
                <td><span class="badge bg-info"><?php echo htmlspecialchars($p->relationship_type ?? 'sameAs'); ?></span></td>
                <td>
                    <?php if ($p->confidence !== null): ?>
                    <div class="d-flex align-items-center gap-1">
                        <div class="progress" style="width:60px;height:6px">
                            <?php $pct = round((float)$p->confidence * 100); $color = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger'); ?>
                            <div class="progress-bar bg-<?php echo $color; ?>" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <small><?php echo $pct; ?>%</small>
                    </div>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><small class="text-muted"><?php echo htmlspecialchars($p->match_method ?? '-'); ?></small></td>
                <td>
                    <?php
                    $evidence = $p->evidence ?? [];
                    if (!empty($evidence)):
                    ?>
                        <button class="btn btn-sm btn-outline-info evidence-btn" data-evidence="<?php echo htmlspecialchars(json_encode($evidence)); ?>" title="View evidence"><i class="fas fa-file-alt"></i> <?php echo count($evidence); ?></button>
                    <?php else: ?>
                        <small class="text-muted">-</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($p->resolver_first_name)): ?>
                        <small><?php echo htmlspecialchars($p->resolver_first_name . ' ' . $p->resolver_last_name); ?></small>
                    <?php else: ?>
                        <small class="text-muted">-</small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?php echo match($p->status) { 'proposed' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', default => 'secondary' }; ?>"><?php echo ucfirst($p->status); ?></span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <?php if ($p->status === 'proposed'): ?>
                        <button class="btn btn-outline-warning check-conflicts-btn" data-id="<?php echo (int) $p->id; ?>" title="Check conflicts"><i class="fas fa-exclamation-triangle"></i></button>
                        <button class="btn btn-success resolve-btn" data-id="<?php echo (int) $p->id; ?>" data-status="accepted" title="Accept"><i class="fas fa-check"></i></button>
                        <button class="btn btn-danger resolve-btn" data-id="<?php echo (int) $p->id; ?>" data-status="rejected" title="Reject"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Propose Match Modal -->
<div class="modal fade" id="proposeMatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Propose Entity Match</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Entity A</h6>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">Type</label>
                            <select id="proposeEntityAType" class="form-select form-select-sm">
                                <option value="actor">Actor</option>
                                <option value="information_object">Information Object</option>
                                <option value="repository">Repository</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">ID</label>
                            <input type="number" id="proposeEntityAId" class="form-control form-control-sm" placeholder="Entity A ID">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Entity B</h6>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">Type</label>
                            <select id="proposeEntityBType" class="form-select form-select-sm">
                                <option value="actor">Actor</option>
                                <option value="information_object">Information Object</option>
                                <option value="repository">Repository</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label form-label-sm">ID</label>
                            <input type="number" id="proposeEntityBId" class="form-control form-control-sm" placeholder="Entity B ID">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Relationship Type</label>
                    <select id="proposeRelType" class="form-select form-select-sm">
                        <option value="sameAs">sameAs (identical entities)</option>
                        <option value="relatedTo">relatedTo (associated entities)</option>
                        <option value="partOf">partOf (hierarchical)</option>
                        <option value="memberOf">memberOf (group membership)</option>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Match Method</label>
                        <select id="proposeMethod" class="form-select form-select-sm">
                            <option value="manual">Manual</option>
                            <option value="name_similarity">Name Similarity</option>
                            <option value="identifier_match">Identifier Match</option>
                            <option value="authority_record">Authority Record</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm">Confidence (0-1)</label>
                        <input type="number" id="proposeConfidence" class="form-control form-control-sm" min="0" max="1" step="0.01" value="0.8">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Notes</label>
                    <textarea id="proposeNotes" class="form-control form-control-sm" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-sm">Evidence (one per line: source_type | source_id | note)</label>
                    <textarea id="proposeEvidence" class="form-control form-control-sm font-monospace" rows="3" placeholder="authority_record | 123 | VIAF match confirmed&#10;document | 456 | Cross-reference in finding aid"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitProposalBtn" class="btn btn-primary">Propose Match</button>
            </div>
        </div>
    </div>
</div>

<!-- Evidence Preview Modal -->
<div class="modal fade" id="evidenceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Evidence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="evidenceBody"></div>
        </div>
    </div>
</div>

<!-- Conflict Warning Modal -->
<div class="modal fade" id="conflictModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Conflict Check</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="conflictBody"></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Submit proposal
    document.getElementById('submitProposalBtn').addEventListener('click', function() {
        var evidenceRaw = document.getElementById('proposeEvidence').value.trim();
        var evidence = [];
        if (evidenceRaw) {
            evidenceRaw.split('\n').forEach(function(line) {
                var parts = line.split('|').map(function(s) { return s.trim(); });
                if (parts.length >= 2) {
                    evidence.push({
                        source_type: parts[0],
                        source_id: parts[1],
                        note: parts[2] || ''
                    });
                }
            });
        }
        var payload = {
            entity_a_type: document.getElementById('proposeEntityAType').value,
            entity_a_id: parseInt(document.getElementById('proposeEntityAId').value),
            entity_b_type: document.getElementById('proposeEntityBType').value,
            entity_b_id: parseInt(document.getElementById('proposeEntityBId').value),
            relationship_type: document.getElementById('proposeRelType').value,
            match_method: document.getElementById('proposeMethod').value,
            confidence: parseFloat(document.getElementById('proposeConfidence').value),
            notes: document.getElementById('proposeNotes').value,
            evidence: evidence
        };
        fetch('/research/entity-resolution/propose', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) location.reload();
            else alert(d.error || 'Error');
        });
    });

    // Accept/Reject
    document.querySelectorAll('.resolve-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var status = this.dataset.status;
            if (status === 'rejected') {
                var reason = prompt('Rejection reason:');
                if (reason === null) return;
            }
            fetch('/research/entity-resolution/' + this.dataset.id + '/resolve', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({status: status})
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) location.reload();
                else alert(d.error || 'Error');
            });
        });
    });

    // View evidence
    document.querySelectorAll('.evidence-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var evidence = JSON.parse(this.dataset.evidence);
            var html = '<table class="table table-sm"><thead><tr><th>Source Type</th><th>Source ID</th><th>Note</th></tr></thead><tbody>';
            evidence.forEach(function(e) {
                html += '<tr><td>' + (e.source_type || '-') + '</td><td>' + (e.source_id || '-') + '</td><td>' + (e.note || '-') + '</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('evidenceBody').innerHTML = html;
            new bootstrap.Modal(document.getElementById('evidenceModal')).show();
        });
    });

    // Check conflicts
    document.querySelectorAll('.check-conflicts-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var resolutionId = this.dataset.id;
            document.getElementById('conflictBody').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Checking...</div>';
            new bootstrap.Modal(document.getElementById('conflictModal')).show();
            fetch('/research/entity-resolution/' + resolutionId + '/resolve', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({check_conflicts: true})
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.conflicts && d.conflicts.length > 0) {
                    var html = '<div class="alert alert-warning"><strong>' + d.conflicts.length + ' conflicting assertion(s) found:</strong></div><ul class="list-group">';
                    d.conflicts.forEach(function(c) {
                        html += '<li class="list-group-item"><strong>' + (c.predicate || 'unknown') + '</strong> — ' + (c.subject_type || '') + ' #' + (c.subject_id || '') + ' → ' + (c.object_type || '') + ' #' + (c.object_id || '') + ' <span class="badge bg-' + (c.status === 'accepted' ? 'success' : 'warning') + '">' + (c.status || '') + '</span></li>';
                    });
                    html += '</ul>';
                    document.getElementById('conflictBody').innerHTML = html;
                } else {
                    document.getElementById('conflictBody').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>No conflicting assertions found. Safe to accept.</div>';
                }
            });
        });
    });
});
</script>
