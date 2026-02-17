<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php use_helper('Date') ?>
<?php
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$idTypes = $taxonomyService->getIdTypes(true);
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-walking me-2"></i>
                Walk-In Visitor Registration
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
    </div>

    <?php if ($currentRoom): ?>
    <div class="row">
        <!-- Registration Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Register Walk-In Visitor</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="form_action" value="register">

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">ID Type</label>
                                    <select name="id_type" class="form-select">
                                        <?php foreach ($idTypes as $code => $label): ?>
                                        <option value="<?php echo $code ?>"><?php echo __($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">ID Number</label>
                                    <input type="text" name="id_number" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Organization/Institution</label>
                            <input type="text" name="organization" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Purpose of Visit</label>
                            <input type="text" name="purpose" class="form-control" placeholder="e.g., Family history research">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Research Topic</label>
                            <textarea name="research_topic" class="form-control" rows="2"></textarea>
                        </div>

                        <?php if (!empty($availableSeats)): ?>
                        <div class="mb-3">
                            <label class="form-label">Assign Seat (optional)</label>
                            <select name="seat_id" class="form-select">
                                <option value="">-- No seat assignment --</option>
                                <?php foreach ($availableSeats as $seat): ?>
                                <option value="<?php echo $seat->id ?>">
                                    <?php echo htmlspecialchars($seat->seat_number) ?>
                                    <?php if ($seat->seat_label): ?> - <?php echo htmlspecialchars($seat->seat_label) ?><?php endif; ?>
                                    (<?php echo ucfirst($seat->seat_type) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="rules_acknowledged" id="rules_acknowledged"
                                       class="form-check-input" value="1" required>
                                <label class="form-check-label" for="rules_acknowledged">
                                    Visitor acknowledges reading room rules *
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-1"></i> Register & Check In
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($currentRoom->rules): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Reading Room Rules</h6>
                </div>
                <div class="card-body">
                    <div class="small"><?php echo nl2br(htmlspecialchars($currentRoom->rules)) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Current Visitors -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Current Walk-In Visitors
                    </h5>
                    <span class="badge bg-primary"><?php echo count($currentWalkIns) ?> visitor(s)</span>
                </div>
                <div class="card-body">
                    <?php if (empty($currentWalkIns)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        No walk-in visitors currently checked in.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Visitor</th>
                                    <th>Check-In</th>
                                    <th>Seat</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currentWalkIns as $visitor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($visitor->first_name . ' ' . $visitor->last_name) ?></strong>
                                        <?php if ($visitor->organization): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($visitor->organization) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('H:i', strtotime($visitor->check_in_time)) ?>
                                        <br><small class="text-muted"><?php echo format_date($visitor->visit_date, 'D') ?></small>
                                    </td>
                                    <td>
                                        <?php if ($visitor->seat_number): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($visitor->seat_number) ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="form_action" value="checkout">
                                            <input type="hidden" name="visitor_id" value="<?php echo $visitor->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                    onclick="return confirm('Check out this visitor?')">
                                                <i class="fas fa-sign-out-alt"></i> Check Out
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Walk-In Visitors</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Walk-in visitors are unregistered users who need quick access to the reading room.</p>
                    <ul class="mb-0">
                        <li>They do not have a researcher account</li>
                        <li>Cannot request materials in advance</li>
                        <li>Limited to browsing open-access materials</li>
                        <li>Can be converted to registered researchers if needed</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Select a reading room to register walk-in visitors.
    </div>
    <?php endif; ?>
</div>
