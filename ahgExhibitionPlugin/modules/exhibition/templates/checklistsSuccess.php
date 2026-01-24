<?php use_helper('Date'); ?>
<?php
// Convert escaped arrays to raw arrays for PHP array functions
$checklistsRaw = $checklists ?? [];
$checklists = ($checklistsRaw instanceof sfOutputEscaperArrayDecorator) ? $checklistsRaw->getRawValue() : (is_array($checklistsRaw) ? $checklistsRaw : []);
?>

<div class="row">
  <div class="col-md-8">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'index']); ?>">Exhibitions</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]); ?>"><?php echo htmlspecialchars($exhibition['title']); ?></a></li>
        <li class="breadcrumb-item active">Checklists</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Exhibition Checklists</h1>
      <div class="btn-group">
        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
          <i class="fas fa-plus"></i> Create Checklist
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach ($templates as $template): ?>
            <li>
              <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'createChecklist', 'id' => $exhibition['id']]); ?>" style="display: inline;">
                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                <button type="submit" class="dropdown-item">
                  <?php echo htmlspecialchars($template['name']); ?>
                  <small class="text-muted d-block"><?php echo $template['item_count'] ?? 0; ?> items</small>
                </button>
              </form>
            </li>
          <?php endforeach; ?>
          <?php if (empty($templates)): ?>
            <li><span class="dropdown-item text-muted">No templates available</span></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <?php if (empty($checklists)): ?>
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-square-check fa-3x text-muted mb-3"></i>
          <h5>No checklists created yet</h5>
          <p class="text-muted">Create checklists to track tasks for planning, installation, and closing.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($checklists as $checklist): ?>
        <?php
          $total = count($checklist['items'] ?? []);
          $completed = count(array_filter($checklist['items'] ?? [], fn($i) => !empty($i['is_completed'])));
          $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        ?>
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0"><?php echo htmlspecialchars($checklist['name']); ?></h5>
              <small class="text-muted text-capitalize"><?php echo str_replace('_', ' ', $checklist['checklist_type'] ?? 'general'); ?></small>
            </div>
            <div class="d-flex align-items-center gap-3">
              <div class="text-end">
                <span class="badge bg-<?php echo $progress == 100 ? 'success' : ($progress > 50 ? 'info' : 'secondary'); ?> fs-6">
                  <?php echo $progress; ?>%
                </span>
                <br>
                <small class="text-muted"><?php echo $completed; ?>/<?php echo $total; ?> complete</small>
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="progress" style="height: 4px; border-radius: 0;">
              <div class="progress-bar bg-<?php echo $progress == 100 ? 'success' : 'primary'; ?>"
                   style="width: <?php echo $progress; ?>%"></div>
            </div>

            <?php if (!empty($checklist['items'])): ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($checklist['items'] as $item): ?>
                  <li class="list-group-item <?php echo !empty($item['is_completed']) ? 'bg-light' : ''; ?>">
                    <div class="d-flex align-items-start">
                      <div class="form-check me-3">
                        <input type="checkbox" class="form-check-input" style="transform: scale(1.3);"
                               <?php echo !empty($item['is_completed']) ? 'checked disabled' : ''; ?>
                               onchange="completeItem(<?php echo $item['id']; ?>, this.checked)">
                      </div>
                      <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                          <div>
                            <span class="<?php echo !empty($item['is_completed']) ? 'text-decoration-line-through text-muted' : ''; ?>">
                              <?php echo htmlspecialchars($item['name']); ?>
                            </span>
                            <?php if (!empty($item['assigned_to'])): ?>
                              <br><small class="text-muted">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($item['assigned_to']); ?>
                              </small>
                            <?php endif; ?>
                          </div>
                          <div class="text-end">
                            <?php if (!empty($item['due_date'])): ?>
                              <?php
                                $dueDate = new DateTime($item['due_date']);
                                $today = new DateTime();
                                $isOverdue = $dueDate < $today && empty($item['is_completed']);
                              ?>
                              <span class="badge <?php echo $isOverdue ? 'bg-danger' : 'bg-light text-dark'; ?>">
                                Due: <?php echo $item['due_date']; ?>
                              </span>
                            <?php endif; ?>
                            <?php if (!empty($item['is_completed'])): ?>
                              <br><small class="text-success">
                                <i class="fas fa-check me-1"></i>
                                <?php echo date('M j, Y', strtotime($item['is_completed'])); ?>
                              </small>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php if (!empty($item['notes'])): ?>
                          <p class="small text-muted mb-0 mt-1"><?php echo htmlspecialchars($item['notes']); ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="p-4 text-center text-muted">
                <p class="mb-0">No items in this checklist</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="card-footer bg-transparent">
            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#addItemModal"
                    data-checklist-id="<?php echo $checklist['id']; ?>"
                    data-checklist-name="<?php echo htmlspecialchars($checklist['name']); ?>">
              <i class="fas fa-plus"></i> Add Item
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Exhibition Info</h5>
      </div>
      <div class="card-body">
        <h6><?php echo htmlspecialchars($exhibition['title']); ?></h6>
        <p class="small text-muted mb-2">
          <span class="badge" style="background-color: <?php echo $exhibition['status_info']['color'] ?? '#999'; ?>">
            <?php echo $exhibition['status_info']['label'] ?? $exhibition['status']; ?>
          </span>
        </p>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Overall Progress</h5>
      </div>
      <div class="card-body">
        <?php
          $totalItems = 0;
          $completedItems = 0;
          foreach ($checklists as $cl) {
            $totalItems += count($cl['items'] ?? []);
            $completedItems += count(array_filter($cl['items'] ?? [], fn($i) => !empty($i['is_completed'])));
          }
          $overallProgress = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
        ?>
        <div class="text-center mb-3">
          <div class="display-4"><?php echo $overallProgress; ?>%</div>
          <small class="text-muted"><?php echo $completedItems; ?> of <?php echo $totalItems; ?> items complete</small>
        </div>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar bg-<?php echo $overallProgress == 100 ? 'success' : 'primary'; ?>"
               style="width: <?php echo $overallProgress; ?>%"></div>
        </div>
      </div>
    </div>

    <?php
      $overdueItems = [];
      foreach ($checklists as $cl) {
        foreach ($cl['items'] ?? [] as $item) {
          if (!empty($item['due_date']) && empty($item['is_completed'])) {
            $dueDate = new DateTime($item['due_date']);
            $today = new DateTime();
            if ($dueDate < $today) {
              $item['checklist_name'] = $cl['name'];
              $overdueItems[] = $item;
            }
          }
        }
      }
    ?>

    <?php if (!empty($overdueItems)): ?>
      <div class="card mb-3 border-danger">
        <div class="card-header bg-danger text-white">
          <h5 class="mb-0"><i class="fas fa-triangle-exclamation me-2"></i> Overdue Items</h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($overdueItems, 0, 5) as $item): ?>
            <li class="list-group-item">
              <strong class="small"><?php echo htmlspecialchars($item['name']); ?></strong>
              <br>
              <small class="text-danger">Due: <?php echo $item['due_date']; ?></small>
              <br>
              <small class="text-muted"><?php echo htmlspecialchars($item['checklist_name']); ?></small>
            </li>
          <?php endforeach; ?>
          <?php if (count($overdueItems) > 5): ?>
            <li class="list-group-item text-center">
              <small class="text-muted">+<?php echo count($overdueItems) - 5; ?> more overdue</small>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Checklist Types</h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled small mb-0">
          <li class="mb-1"><strong>Planning</strong> - Pre-exhibition tasks</li>
          <li class="mb-1"><strong>Installation</strong> - Setup tasks</li>
          <li class="mb-1"><strong>Opening</strong> - Launch preparation</li>
          <li class="mb-1"><strong>Operation</strong> - During exhibition</li>
          <li><strong>Closing</strong> - Deinstallation tasks</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Checklist Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?php echo url_for(['module' => 'exhibition', 'action' => 'addChecklistItem', 'id' => $exhibition['id']]); ?>">
        <input type="hidden" name="checklist_id" id="addItemChecklistId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Checklist</label>
            <input type="text" id="addItemChecklistName" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Task Name <span class="text-danger">*</span></label>
            <input type="text" name="task_name" class="form-control" required placeholder="e.g., Order display cases">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Assigned To</label>
              <input type="text" name="assigned_to" class="form-control" placeholder="Person responsible">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Additional details..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Populate add item modal
document.getElementById('addItemModal').addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  document.getElementById('addItemChecklistId').value = button.dataset.checklistId;
  document.getElementById('addItemChecklistName').value = button.dataset.checklistName;
});

// Complete item
function completeItem(itemId, isCompleted) {
  if (isCompleted) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo url_for(['module' => 'exhibition', 'action' => 'completeItem', 'id' => $exhibition['id']]); ?>';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'item_id';
    input.value = itemId;
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>
