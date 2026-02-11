<?php use_helper('Date') ?>
<?php
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$equipmentTypes = $taxonomyService->getEquipmentTypes(false);
$equipmentConditions = $taxonomyService->getEquipmentConditions(false);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-tools me-2"></i>
                Reading Room Equipment
            </h1>
        </div>
    </div>

    <!-- Room Selector -->
    <div class="row mb-4">
        <div class="col-md-4">
            <label class="form-label">Select Reading Room</label>
            <select class="form-select" onchange="window.location.href='?room_id=' + this.value">
                <option value="">-- Select Room --</option>
                <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room->id ?>" <?php echo ($currentRoom && $currentRoom->id == $room->id) ? 'selected' : '' ?>>
                    <?php echo htmlspecialchars($room->name) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($currentRoom && !empty($typeCounts)): ?>
        <div class="col-md-8">
            <label class="form-label">Equipment by Type</label>
            <div>
                <?php foreach ($typeCounts as $type): ?>
                <span class="badge bg-secondary me-2">
                    <?php echo ucfirst(str_replace('_', ' ', $type->equipment_type)) ?>: <?php echo $type->count ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($currentRoom): ?>
    <div class="row">
        <!-- Equipment List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Equipment in <?php echo htmlspecialchars($currentRoom->name) ?></h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                        <i class="fas fa-plus me-1"></i> Add Equipment
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($equipment)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No equipment configured for this room.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Brand/Model</th>
                                    <th>Location</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipment as $item): ?>
                                <tr class="<?php echo $item->is_available ? '' : 'table-warning' ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($item->name) ?></strong>
                                        <?php if ($item->code): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item->code) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst(str_replace('_', ' ', $item->equipment_type)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item->brand || $item->model): ?>
                                        <?php echo htmlspecialchars(trim($item->brand . ' ' . $item->model)) ?>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item->location ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $conditionClass = match($item->condition_status) {
                                            'excellent' => 'success',
                                            'good' => 'primary',
                                            'fair' => 'warning',
                                            'needs_repair' => 'danger',
                                            'out_of_service' => 'dark',
                                            default => 'secondary',
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $conditionClass ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item->condition_status ?? 'unknown')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item->is_available): ?>
                                        <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editEquipment(<?php echo htmlspecialchars(json_encode($item)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                onclick="logMaintenance(<?php echo $item->id ?>)">
                                            <i class="fas fa-wrench"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Equipment Types Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Equipment Types</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li><i class="fas fa-film text-muted me-2"></i> Microfilm Reader</li>
                        <li><i class="fas fa-th text-muted me-2"></i> Microfiche Reader</li>
                        <li><i class="fas fa-print text-muted me-2"></i> Scanner</li>
                        <li><i class="fas fa-desktop text-muted me-2"></i> Computer</li>
                        <li><i class="fas fa-search-plus text-muted me-2"></i> Magnifier</li>
                        <li><i class="fas fa-book-open text-muted me-2"></i> Book Cradle</li>
                        <li><i class="fas fa-lightbulb text-muted me-2"></i> Light Box</li>
                        <li><i class="fas fa-camera text-muted me-2"></i> Camera Stand</li>
                        <li><i class="fas fa-hand-paper text-muted me-2"></i> Cotton Gloves</li>
                        <li><i class="fas fa-weight-hanging text-muted me-2"></i> Page Weights</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="equipmentForm">
                <input type="hidden" name="form_action" id="equipmentAction" value="create">
                <input type="hidden" name="equipment_id" id="equipmentId">
                <div class="modal-header">
                    <h5 class="modal-title" id="equipmentModalTitle">Add Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" id="eqName" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Code/ID</label>
                                <input type="text" name="code" id="eqCode" class="form-control" placeholder="e.g., MF-001">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Type *</label>
                                <select name="equipment_type" id="eqType" class="form-select" required>
                                    <?php foreach ($equipmentTypes as $code => $label): ?>
                                    <option value="<?php echo $code ?>"><?php echo __($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" id="eqLocation" class="form-control" placeholder="e.g., Table A">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" id="eqBrand" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Model</label>
                                <input type="text" name="model" id="eqModel" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Serial Number</label>
                                <input type="text" name="serial_number" id="eqSerial" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Max Booking Hours</label>
                                <input type="number" name="max_booking_hours" id="eqMaxHours" class="form-control" value="4" min="1" max="8">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="eqDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="requires_training" id="eqTraining" class="form-check-input" value="1">
                        <label class="form-check-label" for="eqTraining">Requires training to use</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Modal -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="maintenance">
                <input type="hidden" name="equipment_id" id="maintenanceEquipmentId">
                <div class="modal-header">
                    <h5 class="modal-title">Log Maintenance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="maintenance_description" class="form-control" rows="3" required
                                  placeholder="Describe the maintenance performed..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Condition Status</label>
                        <select name="new_condition" class="form-select">
                            <?php foreach ($equipmentConditions as $code => $label): ?>
                            <option value="<?php echo $code ?>" <?php echo $code === 'good' ? 'selected' : '' ?>><?php echo __($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Maintenance Date</label>
                        <input type="date" name="next_maintenance_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function editEquipment(item) {
    document.getElementById('equipmentModalTitle').textContent = 'Edit Equipment';
    document.getElementById('equipmentAction').value = 'update';
    document.getElementById('equipmentId').value = item.id;
    document.getElementById('eqName').value = item.name;
    document.getElementById('eqCode').value = item.code || '';
    document.getElementById('eqType').value = item.equipment_type;
    document.getElementById('eqLocation').value = item.location || '';
    document.getElementById('eqBrand').value = item.brand || '';
    document.getElementById('eqModel').value = item.model || '';
    document.getElementById('eqSerial').value = item.serial_number || '';
    document.getElementById('eqMaxHours').value = item.max_booking_hours || 4;
    document.getElementById('eqDescription').value = item.description || '';
    document.getElementById('eqTraining').checked = item.requires_training == 1;

    new bootstrap.Modal(document.getElementById('addEquipmentModal')).show();
}

function logMaintenance(equipmentId) {
    document.getElementById('maintenanceEquipmentId').value = equipmentId;
    new bootstrap.Modal(document.getElementById('maintenanceModal')).show();
}

document.getElementById('addEquipmentModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('equipmentModalTitle').textContent = 'Add Equipment';
    document.getElementById('equipmentAction').value = 'create';
    document.getElementById('equipmentForm').reset();
});
</script>
