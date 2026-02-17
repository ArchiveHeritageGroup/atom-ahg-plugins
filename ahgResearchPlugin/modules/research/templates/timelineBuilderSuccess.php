<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Timeline</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Timeline Builder</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fas fa-plus me-1"></i> Add Event</button>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <div id="timeline" style="width:100%; height:400px;"></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Events (<?php echo count($events); ?>)</h5></div>
    <div class="card-body">
        <?php if (empty($events)): ?>
            <p class="text-muted">No events yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Label</th><th>Start</th><th>End</th><th>Type</th></tr></thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ev->label); ?></td>
                        <td><?php echo $ev->date_start; ?></td>
                        <td><?php echo $ev->date_end ?? '-'; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($ev->date_type ?? ''); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vis-timeline@7/dist/vis-timeline-graph2d.min.css">
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?> src="https://cdn.jsdelivr.net/npm/vis-timeline@7/dist/vis-timeline-graph2d.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('timeline');
    var items = new vis.DataSet(<?php echo json_encode(array_map(function($ev) {
        return ['id' => $ev->id, 'content' => $ev->label, 'start' => $ev->date_start, 'end' => $ev->date_end ?? null, 'style' => $ev->color ? 'background-color:' . $ev->color : ''];
    }, $events)); ?>);
    var timeline = new vis.Timeline(container, items, {});
});
</script>

<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Label</label><input type="text" id="eventLabel" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea id="eventDesc" class="form-control" rows="2"></textarea></div>
                <div class="row mb-3"><div class="col"><label class="form-label">Start Date</label><input type="date" id="eventStart" class="form-control" required></div><div class="col"><label class="form-label">End Date</label><input type="date" id="eventEnd" class="form-control"></div></div>
                <div class="mb-3"><label class="form-label">Type</label><select id="eventType" class="form-select"><option value="event">Event</option><option value="creation">Creation</option><option value="accession">Accession</option><option value="publication">Publication</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="saveEvent">Save</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('saveEvent')?.addEventListener('click', function() {
    fetch('/research/timeline-event-api', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({form_action:'create', project_id:<?php echo (int) $projectId; ?>, label:document.getElementById('eventLabel').value, description:document.getElementById('eventDesc').value, date_start:document.getElementById('eventStart').value, date_end:document.getElementById('eventEnd').value, date_type:document.getElementById('eventType').value})
    }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
});
</script>
