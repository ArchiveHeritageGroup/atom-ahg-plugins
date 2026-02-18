<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Timeline</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Timeline Builder</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#autoPopulateModal"><i class="fas fa-magic me-1"></i>Auto-populate</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fas fa-plus me-1"></i> Add Event</button>
    </div>
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
                <thead><tr><th>Label</th><th>Start</th><th>End</th><th>Type</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ev->label); ?></td>
                        <td><?php echo $ev->date_start; ?></td>
                        <td><?php echo $ev->date_end ?? '-'; ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($ev->date_type ?? ''); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-event-btn" data-id="<?php echo (int) $ev->id; ?>" data-label="<?php echo htmlspecialchars($ev->label); ?>" data-desc="<?php echo htmlspecialchars($ev->description ?? ''); ?>" data-start="<?php echo htmlspecialchars($ev->date_start ?? ''); ?>" data-end="<?php echo htmlspecialchars($ev->date_end ?? ''); ?>" data-type="<?php echo htmlspecialchars($ev->date_type ?? ''); ?>" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-event-btn" data-id="<?php echo (int) $ev->id; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
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
    var projectId = <?php echo (int) $projectId; ?>;
    var container = document.getElementById('timeline');
    var items = new vis.DataSet(<?php echo json_encode(array_map(function($ev) {
        return ['id' => $ev->id, 'content' => $ev->label, 'start' => $ev->date_start, 'end' => $ev->date_end ?? null, 'style' => $ev->color ? 'background-color:' . $ev->color : ''];
    }, $events)); ?>);
    var timeline = new vis.Timeline(container, items, {
        editable: {add: false, updateTime: true, updateGroup: false, remove: false},
        onMove: function(item, callback) {
            fetch('/research/timeline-event-api', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({form_action:'update', id: item.id, date_start: item.start instanceof Date ? item.start.toISOString().split('T')[0] : item.start, date_end: item.end ? (item.end instanceof Date ? item.end.toISOString().split('T')[0] : item.end) : null})
            }).then(function(r){return r.json();}).then(function(d){
                if(d.success) callback(item); else { alert(d.error||'Error'); callback(null); }
            });
        }
    });
});
</script>

<!-- Add Event Modal -->
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

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editEventId">
                <div class="mb-3"><label class="form-label">Label</label><input type="text" id="editEventLabel" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea id="editEventDesc" class="form-control" rows="2"></textarea></div>
                <div class="row mb-3"><div class="col"><label class="form-label">Start Date</label><input type="date" id="editEventStart" class="form-control" required></div><div class="col"><label class="form-label">End Date</label><input type="date" id="editEventEnd" class="form-control"></div></div>
                <div class="mb-3"><label class="form-label">Type</label><select id="editEventType" class="form-select"><option value="event">Event</option><option value="creation">Creation</option><option value="accession">Accession</option><option value="publication">Publication</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="updateEvent">Update</button></div>
        </div>
    </div>
</div>

<!-- Auto-populate Modal -->
<div class="modal fade" id="autoPopulateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Auto-populate from Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted">Select an evidence set (collection) to auto-generate timeline events from its item dates.</p>
                <div class="mb-3"><label class="form-label">Collection ID</label><input type="number" id="autoCollectionId" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="autoPopulateBtn">Populate</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var projectId = <?php echo (int) $projectId; ?>;

    // Create event
    document.getElementById('saveEvent')?.addEventListener('click', function() {
        fetch('/research/timeline-event-api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({form_action:'create', project_id:projectId, label:document.getElementById('eventLabel').value, description:document.getElementById('eventDesc').value, date_start:document.getElementById('eventStart').value, date_end:document.getElementById('eventEnd').value, date_type:document.getElementById('eventType').value})
        }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
    });

    // Edit event — open modal
    document.querySelectorAll('.edit-event-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editEventId').value = this.dataset.id;
            document.getElementById('editEventLabel').value = this.dataset.label;
            document.getElementById('editEventDesc').value = this.dataset.desc;
            document.getElementById('editEventStart').value = this.dataset.start;
            document.getElementById('editEventEnd').value = this.dataset.end;
            document.getElementById('editEventType').value = this.dataset.type || 'event';
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        });
    });

    // Update event
    document.getElementById('updateEvent')?.addEventListener('click', function() {
        fetch('/research/timeline-event-api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({form_action:'update', id:parseInt(document.getElementById('editEventId').value), label:document.getElementById('editEventLabel').value, description:document.getElementById('editEventDesc').value, date_start:document.getElementById('editEventStart').value, date_end:document.getElementById('editEventEnd').value, date_type:document.getElementById('editEventType').value})
        }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
    });

    // Delete event
    document.querySelectorAll('.delete-event-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this event?')) return;
            fetch('/research/timeline-event-api', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({form_action:'delete', id:parseInt(this.dataset.id)})
            }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
        });
    });

    // Auto-populate
    document.getElementById('autoPopulateBtn')?.addEventListener('click', function() {
        var cid = parseInt(document.getElementById('autoCollectionId').value);
        if (!cid) { alert('Enter a collection ID'); return; }
        this.disabled = true; this.textContent = 'Populating...';
        fetch('/research/timeline-event-api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({form_action:'auto_populate', project_id:projectId, collection_id:cid})
        }).then(function(r){return r.json();}).then(function(d){
            if(d.success) { alert('Added ' + (d.count||0) + ' events'); location.reload(); }
            else alert(d.error||'Error');
        });
    });
});
</script>
