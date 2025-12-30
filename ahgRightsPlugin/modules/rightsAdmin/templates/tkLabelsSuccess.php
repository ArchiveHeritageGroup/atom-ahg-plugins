<?php echo get_partial('header', ['title' => 'Traditional Knowledge Labels']); ?>

<div class="container-fluid">
  <div class="row">
    <?php include_partial('rightsAdmin/sidebar', ['active' => 'tkLabels']); ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tags me-2"></i><?php echo __('Traditional Knowledge Labels'); ?></h1>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
          <i class="fas fa-plus me-1"></i> Assign Label
        </button>
      </div>

      <!-- Available Labels -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Available TK Labels'); ?></h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            Traditional Knowledge Labels are part of the <a href="https://localcontexts.org" target="_blank">Local Contexts</a> initiative 
            to support Indigenous communities in the management of their cultural heritage and intellectual property.
          </p>
          
          <div class="row">
            <?php 
            $currentCategory = '';
            foreach ($tkLabels as $label): 
              if ($label->category !== $currentCategory):
                $currentCategory = $label->category;
            ?>
              <div class="col-12 mt-3 mb-2">
                <h6 class="text-uppercase text-muted">
                  <?php echo match($currentCategory) {
                    'tk' => 'Traditional Knowledge Labels',
                    'bc' => 'Biocultural Labels',
                    'attribution' => 'Attribution Labels',
                    default => $currentCategory
                  }; ?>
                </h6>
              </div>
            <?php endif; ?>
            
            <div class="col-md-6 col-lg-4 mb-3">
              <div class="d-flex align-items-start p-2 border rounded">
                <span class="badge me-3 text-white" style="background-color: <?php echo $label->color; ?>; min-width: 50px; padding: 8px;">
                  <?php echo $label->code; ?>
                </span>
                <div class="small">
                  <strong><?php echo $label->name; ?></strong>
                  <br>
                  <span class="text-muted"><?php echo $label->description; ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Current Assignments -->
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0"><?php echo __('Label Assignments'); ?></h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>Label</th>
                <th>Object</th>
                <th>Community</th>
                <th>Verified</th>
                <th>Assigned</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignments as $assign): ?>
              <tr>
                <td>
                  <span class="badge text-white" style="background-color: <?php echo $assign->color; ?>;">
                    <?php echo $assign->code; ?>
                  </span>
                  <?php echo $assign->label_name; ?>
                </td>
                <td>
                  <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $assign->slug]); ?>">
                    <?php echo $assign->object_title ?: 'ID: '.$assign->object_id; ?>
                  </a>
                </td>
                <td><?php echo $assign->community_name ?: '-'; ?></td>
                <td>
                  <?php if ($assign->verified): ?>
                    <i class="fas fa-check-circle text-success"></i>
                    <?php if ($assign->verified_by): ?>
                      <small class="text-muted"><?php echo $assign->verified_by; ?></small>
                    <?php endif; ?>
                  <?php else: ?>
                    <i class="fas fa-clock text-warning"></i> Pending
                  <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($assign->created_at)); ?></td>
                <td>
                  <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'removeTkLabel', 'object_id' => $assign->object_id, 'label_id' => $assign->tk_label_id]); ?>" 
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Remove this TK Label assignment?');">
                    <i class="fas fa-times"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (count($assignments) === 0): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No TK Labels have been assigned yet.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'assignTkLabel']); ?>" method="post">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo __('Assign TK Label'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Object ID'); ?> <span class="text-danger">*</span></label>
              <input type="number" name="object_id" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('TK Label'); ?> <span class="text-danger">*</span></label>
              <select name="tk_label_id" class="form-select" required>
                <option value=""><?php echo __('- Select Label -'); ?></option>
                <?php foreach ($tkLabels as $label): ?>
                <option value="<?php echo $label->id; ?>"><?php echo $label->code; ?> - <?php echo $label->name; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Community Name'); ?></label>
            <input type="text" name="community_name" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Community Contact'); ?></label>
            <textarea name="community_contact" class="form-control" rows="2" placeholder="Contact information for the community"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Custom Text'); ?></label>
            <textarea name="custom_text" class="form-control" rows="2" placeholder="Optional custom description from the community"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo __('Assign Label'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
