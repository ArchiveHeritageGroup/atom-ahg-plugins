<?php
// Shared add/edit form - used for both add and edit actions
$isEdit = isset($exhibition) && !empty($exhibition);
$data = $isEdit ? $exhibition : ($formData ?? []);
?>

<div class="row">
  <div class="col-md-8">
    <h1><?php echo $isEdit ? 'Edit Exhibition' : 'New Exhibition'; ?></h1>

    <form method="post" action="">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Basic Information</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required
                   value="<?php echo htmlspecialchars($data['title'] ?? ''); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Subtitle</label>
            <input type="text" name="subtitle" class="form-control"
                   value="<?php echo htmlspecialchars($data['subtitle'] ?? ''); ?>">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Exhibition Type</label>
              <select name="exhibition_type" class="form-select">
                <?php foreach ($types as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo ($data['exhibition_type'] ?? 'temporary') == $key ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Project Code</label>
              <input type="text" name="project_code" class="form-control"
                     value="<?php echo htmlspecialchars($data['project_code'] ?? ''); ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Theme</label>
            <input type="text" name="theme" class="form-control"
                   value="<?php echo htmlspecialchars($data['theme'] ?? ''); ?>"
                   placeholder="e.g., African Art, Modern Sculpture, Industrial Heritage">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Dates</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Opening Date</label>
              <input type="date" name="opening_date" class="form-control"
                     value="<?php echo $data['opening_date'] ?? ''; ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Closing Date</label>
              <input type="date" name="closing_date" class="form-control"
                     value="<?php echo $data['closing_date'] ?? ''; ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Venue & Team</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($venues)): ?>
            <div class="mb-3">
              <label class="form-label">Venue</label>
              <select name="venue_id" class="form-select">
                <option value="">-- Select venue --</option>
                <?php foreach ($venues as $venue): ?>
                  <option value="<?php echo $venue->id; ?>" <?php echo ($data['venue_id'] ?? '') == $venue->id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($venue->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Venue Name</label>
            <input type="text" name="venue_name" class="form-control"
                   value="<?php echo htmlspecialchars($data['venue_name'] ?? ''); ?>"
                   placeholder="Enter venue name if not in list above">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Curator</label>
              <input type="text" name="curator_name" class="form-control"
                     value="<?php echo htmlspecialchars($data['curator_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Organized By</label>
              <input type="text" name="organized_by" class="form-control"
                     value="<?php echo htmlspecialchars($data['organized_by'] ?? ''); ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Budget</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Budget Amount</label>
              <input type="number" name="budget_amount" class="form-control" step="0.01"
                     value="<?php echo $data['budget_amount'] ?? ''; ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Currency</label>
              <select name="budget_currency" class="form-select">
                <option value="ZAR" <?php echo ($data['budget_currency'] ?? 'ZAR') == 'ZAR' ? 'selected' : ''; ?>>ZAR</option>
                <option value="USD" <?php echo ($data['budget_currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD</option>
                <option value="EUR" <?php echo ($data['budget_currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                <option value="GBP" <?php echo ($data['budget_currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP</option>
              </select>
            </div>
          </div>

          <?php if ($isEdit): ?>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Expected Visitors</label>
                <input type="number" name="expected_visitors" class="form-control"
                       value="<?php echo $data['expected_visitors'] ?? ''; ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Admission Fee</label>
                <input type="number" name="admission_fee" class="form-control" step="0.01"
                       value="<?php echo $data['admission_fee'] ?? ''; ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">&nbsp;</label>
                <div class="form-check mt-2">
                  <input type="checkbox" name="is_free_admission" class="form-check-input" value="1"
                         <?php echo !empty($data['is_free_admission']) ? 'checked' : ''; ?>>
                  <label class="form-check-label">Free Admission</label>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Notes</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Internal Notes</label>
            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo $isEdit ? url_for(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibition['id']]) : url_for(['module' => 'exhibition', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
          Cancel
        </a>
        <button type="submit" class="btn btn-primary">
          <?php echo $isEdit ? 'Save Changes' : 'Create Exhibition'; ?>
        </button>
      </div>
    </form>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Help</h5>
      </div>
      <div class="card-body">
        <h6>Exhibition Types</h6>
        <ul class="small">
          <li><strong>Permanent</strong> - Long-term display, rarely changes</li>
          <li><strong>Temporary</strong> - Fixed duration, typically 3-12 months</li>
          <li><strong>Traveling</strong> - Moves between venues</li>
          <li><strong>Online</strong> - Virtual/digital exhibition</li>
          <li><strong>Pop-up</strong> - Short-term, often < 1 month</li>
        </ul>

        <h6 class="mt-3">After Creating</h6>
        <p class="small text-muted">
          After creating the exhibition, you can:
        </p>
        <ul class="small text-muted">
          <li>Add sections/galleries</li>
          <li>Add objects from the collection</li>
          <li>Create storylines and narratives</li>
          <li>Schedule events</li>
          <li>Generate checklists</li>
        </ul>
      </div>
    </div>
  </div>
</div>
