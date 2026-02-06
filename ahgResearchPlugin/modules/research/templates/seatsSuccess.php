<?php use_helper('Date') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-chair me-2"></i>
                Reading Room Seats
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
                    (Capacity: <?php echo $room->capacity ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($currentRoom && $occupancy): ?>
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center py-2">
                            <h4 class="mb-0"><?php echo $occupancy['total_seats'] ?></h4>
                            <small>Total Seats</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center py-2">
                            <h4 class="mb-0"><?php echo $occupancy['available_seats'] ?></h4>
                            <small>Available</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center py-2">
                            <h4 class="mb-0"><?php echo $occupancy['occupied_seats'] ?></h4>
                            <small>Occupied</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center py-2">
                            <h4 class="mb-0"><?php echo $occupancy['occupancy_percentage'] ?>%</h4>
                            <small>Occupancy</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($currentRoom): ?>
    <div class="row">
        <!-- Seat List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Seats in <?php echo htmlspecialchars($currentRoom->name) ?></h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSeatModal">
                        <i class="fas fa-plus me-1"></i> Add Seat
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($seats)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No seats configured. Add seats to enable seat assignment.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Seat #</th>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Zone</th>
                                    <th>Amenities</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seats as $seat): ?>
                                <tr class="<?php echo $seat->is_active ? '' : 'table-secondary' ?>">
                                    <td><strong><?php echo htmlspecialchars($seat->seat_number) ?></strong></td>
                                    <td><?php echo htmlspecialchars($seat->seat_label ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($seat->seat_type) ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($seat->zone ?? '-') ?></td>
                                    <td>
                                        <?php if ($seat->has_power): ?><i class="fas fa-plug text-success me-1" title="Power"></i><?php endif; ?>
                                        <?php if ($seat->has_lamp): ?><i class="fas fa-lightbulb text-warning me-1" title="Lamp"></i><?php endif; ?>
                                        <?php if ($seat->has_computer): ?><i class="fas fa-desktop text-primary me-1" title="Computer"></i><?php endif; ?>
                                        <?php if ($seat->has_magnifier): ?><i class="fas fa-search-plus text-info" title="Magnifier"></i><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($seat->is_active): ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editSeat(<?php echo htmlspecialchars(json_encode($seat)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($seat->is_active): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Deactivate this seat?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="seat_id" value="<?php echo $seat->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
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

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-magic me-2"></i>Bulk Create Seats</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="bulk_create">
                        <div class="mb-3">
                            <label class="form-label">Pattern</label>
                            <input type="text" name="pattern" class="form-control" placeholder="e.g., A1-A10 or 1-20" required>
                            <small class="text-muted">Examples: A1-A10, 1-20, B1-B5</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Seat Type</label>
                            <select name="seat_type" class="form-select">
                                <option value="standard">Standard</option>
                                <option value="accessible">Accessible</option>
                                <option value="computer">Computer</option>
                                <option value="microfilm">Microfilm Reader</option>
                                <option value="oversize">Oversize</option>
                                <option value="quiet">Quiet Zone</option>
                                <option value="group">Group Table</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Zone (optional)</label>
                            <input type="text" name="zone" class="form-control" placeholder="e.g., Main Hall, Annex">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-1"></i> Create Seats
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Seat Types</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><span class="badge bg-secondary">standard</span> Regular desk/table</li>
                        <li><span class="badge bg-info">accessible</span> Wheelchair accessible</li>
                        <li><span class="badge bg-primary">computer</span> With workstation</li>
                        <li><span class="badge bg-warning text-dark">microfilm</span> Microfilm reader</li>
                        <li><span class="badge bg-success">oversize</span> Large format materials</li>
                        <li><span class="badge bg-dark">quiet</span> Silent study zone</li>
                        <li><span class="badge bg-secondary">group</span> Group/collaborative</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Seat Modal -->
<div class="modal fade" id="addSeatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="seatForm">
                <input type="hidden" name="action" id="seatAction" value="create">
                <input type="hidden" name="seat_id" id="seatId">
                <div class="modal-header">
                    <h5 class="modal-title" id="seatModalTitle">Add Seat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Seat Number *</label>
                                <input type="text" name="seat_number" id="seatNumber" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" name="seat_label" id="seatLabel" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="seat_type" id="seatType" class="form-select">
                                    <option value="standard">Standard</option>
                                    <option value="accessible">Accessible</option>
                                    <option value="computer">Computer</option>
                                    <option value="microfilm">Microfilm Reader</option>
                                    <option value="oversize">Oversize</option>
                                    <option value="quiet">Quiet Zone</option>
                                    <option value="group">Group Table</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Zone</label>
                                <input type="text" name="zone" id="seatZone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amenities</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check">
                                    <input type="checkbox" name="has_power" id="hasPower" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="hasPower">Power outlet</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="has_lamp" id="hasLamp" class="form-check-input" value="1" checked>
                                    <label class="form-check-label" for="hasLamp">Reading lamp</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input type="checkbox" name="has_computer" id="hasComputer" class="form-check-input" value="1">
                                    <label class="form-check-label" for="hasComputer">Computer</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="has_magnifier" id="hasMagnifier" class="form-check-input" value="1">
                                    <label class="form-check-label" for="hasMagnifier">Magnifier</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="activeField" style="display: none;">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="isActive" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="seatNotes" class="form-control" rows="2"></textarea>
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

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function editSeat(seat) {
    document.getElementById('seatModalTitle').textContent = 'Edit Seat';
    document.getElementById('seatAction').value = 'update';
    document.getElementById('seatId').value = seat.id;
    document.getElementById('seatNumber').value = seat.seat_number;
    document.getElementById('seatLabel').value = seat.seat_label || '';
    document.getElementById('seatType').value = seat.seat_type;
    document.getElementById('seatZone').value = seat.zone || '';
    document.getElementById('hasPower').checked = seat.has_power == 1;
    document.getElementById('hasLamp').checked = seat.has_lamp == 1;
    document.getElementById('hasComputer').checked = seat.has_computer == 1;
    document.getElementById('hasMagnifier').checked = seat.has_magnifier == 1;
    document.getElementById('isActive').checked = seat.is_active == 1;
    document.getElementById('seatNotes').value = seat.notes || '';
    document.getElementById('activeField').style.display = 'block';

    new bootstrap.Modal(document.getElementById('addSeatModal')).show();
}

// Reset modal on close
document.getElementById('addSeatModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('seatModalTitle').textContent = 'Add Seat';
    document.getElementById('seatAction').value = 'create';
    document.getElementById('seatForm').reset();
    document.getElementById('activeField').style.display = 'none';
});
</script>
