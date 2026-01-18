<?php use_helper('Date'); ?>

<h1 class="h3 mb-4">
  <i class="fas fa-gavel me-2"></i>
  <?php echo __('Rights'); ?> - <?php echo $resource->title ?? $resource->slug; ?>
</h1>

<?php if ($sf_user->isAuthenticated()): ?>
<div class="mb-3">
  <a href="<?php echo url_for(['module' => 'rights', 'action' => 'edit', 'slug' => $resource->slug]); ?>" 
     class="btn btn-primary">
    <i class="fas fa-plus me-1"></i><?php echo __('Add Rights'); ?>
  </a>
  <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'embargoEdit', 'object_id' => $resource->id]); ?>" 
     class="btn btn-outline-warning">
    <i class="fas fa-clock me-1"></i><?php echo __('Add Embargo'); ?>
  </a>
</div>
<?php endif; ?>

<!-- Access Status Summary -->
<div class="card mb-4 border-<?php echo $accessCheck['accessible'] ? 'success' : 'warning'; ?>">
  <div class="card-header bg-<?php echo $accessCheck['accessible'] ? 'success' : 'warning'; ?> text-<?php echo $accessCheck['accessible'] ? 'white' : 'dark'; ?>">
    <h5 class="mb-0">
      <i class="fas fa-<?php echo $accessCheck['accessible'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
      <?php echo __('Access Status'); ?>
    </h5>
  </div>
  <div class="card-body">
    <?php if ($accessCheck['accessible']): ?>
      <p class="text-success mb-0"><strong><?php echo __('This item is accessible.'); ?></strong></p>
    <?php else: ?>
      <p class="text-warning mb-2"><strong><?php echo __('Access to this item may be restricted.'); ?></strong></p>
      <?php if (!empty($accessCheck['restrictions'])): ?>
        <ul class="mb-0">
          <?php foreach ($accessCheck['restrictions'] as $restriction): ?>
          <li>
            <?php echo ucfirst($restriction['type']); ?>
            <?php if (isset($restriction['reason'])): ?>
              - <?php echo ucfirst(str_replace('_', ' ', $restriction['reason'])); ?>
            <?php endif; ?>
            <?php if (isset($restriction['until'])): ?>
              (until <?php echo $restriction['until']; ?>)
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($accessCheck['rights_statement']): ?>
    <div class="mt-3">
      <strong><?php echo __('Rights Statement'); ?>:</strong>
      <a href="<?php echo $accessCheck['rights_statement']['uri']; ?>" target="_blank" class="ms-2">
        <?php echo $accessCheck['rights_statement']['name']; ?>
        <i class="fas fa-external-link-alt ms-1"></i>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($accessCheck['cc_license']): ?>
    <div class="mt-2">
      <strong><?php echo __('License'); ?>:</strong>
      <a href="<?php echo $accessCheck['cc_license']['uri']; ?>" target="_blank" class="ms-2">
        <img src="<?php echo $accessCheck['cc_license']['badge_url']; ?>" alt="<?php echo $accessCheck['cc_license']['name']; ?>" height="31">
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Active Embargo -->
<?php if ($embargo): ?>
<div class="card mb-4 border-danger">
  <div class="card-header bg-danger text-white">
    <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Embargo'); ?></h5>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('Type'); ?></dt>
      <dd class="col-sm-9"><?php echo ucfirst(str_replace('_', ' ', $embargo->embargo_type)); ?></dd>

      <dt class="col-sm-3"><?php echo __('Reason'); ?></dt>
      <dd class="col-sm-9"><?php echo ucfirst(str_replace('_', ' ', $embargo->reason)); ?></dd>

      <dt class="col-sm-3"><?php echo __('Start Date'); ?></dt>
      <dd class="col-sm-9"><?php echo format_date($embargo->start_date, 'f'); ?></dd>

      <dt class="col-sm-3"><?php echo __('End Date'); ?></dt>
      <dd class="col-sm-9">
        <?php if ($embargo->end_date): ?>
          <?php echo format_date($embargo->end_date, 'f'); ?>
          <?php 
          $daysLeft = floor((strtotime($embargo->end_date) - time()) / 86400);
          if ($daysLeft > 0): 
          ?>
            <span class="badge bg-warning text-dark ms-2"><?php echo $daysLeft; ?> days remaining</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="text-danger"><?php echo __('Indefinite'); ?></span>
        <?php endif; ?>
      </dd>

      <?php if ($embargo->reason_note): ?>
      <dt class="col-sm-3"><?php echo __('Note'); ?></dt>
      <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($embargo->reason_note)); ?></dd>
      <?php endif; ?>
    </dl>
  </div>
</div>
<?php endif; ?>

<!-- TK Labels -->
<?php if (count($tkLabels) > 0): ?>
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-tags me-2"></i><?php echo __('Traditional Knowledge Labels'); ?></h5>
  </div>
  <div class="card-body">
    <div class="row">
      <?php foreach ($tkLabels as $label): ?>
      <div class="col-md-6 mb-3">
        <div class="d-flex align-items-start">
          <span class="badge me-3" style="background-color: <?php echo esc_entities($label->color); ?>; width: 60px; padding: 10px;">
            <?php echo esc_entities($label->code); ?>
          </span>
          <div>
            <strong><?php echo esc_entities($label->name); ?></strong>
            <?php if ($label->verified): ?>
              <i class="fas fa-check-circle text-success ms-1" title="Verified"></i>
            <?php endif; ?>
            <br>
            <small class="text-muted"><?php echo esc_entities($label->description); ?></small>
            <?php if ($label->community_name): ?>
              <br><small>Community: <?php echo esc_entities($label->community_name); ?></small>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="mt-2 mb-0">
      <small>
        <i class="fas fa-info-circle me-1"></i>
        <?php echo __('Learn more about Traditional Knowledge Labels at'); ?> 
        <a href="https://localcontexts.org" target="_blank">Local Contexts</a>
      </small>
    </p>
  </div>
</div>
<?php endif; ?>

<!-- Rights Records -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i><?php echo __('Rights Records'); ?></h5>
  </div>
  <div class="card-body">
    <?php if (count($rights) > 0): ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th><?php echo __('Basis'); ?></th>
            <th><?php echo __('Rights Statement / License'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Dates'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rights as $right): ?>
          <tr>
            <td>
              <span class="badge bg-<?php 
                echo match($right->basis) {
                  'copyright' => 'primary',
                  'license' => 'success',
                  'statute' => 'warning',
                  'donor' => 'info',
                  'policy' => 'secondary',
                  default => 'light'
                };
              ?>">
                <?php echo ucfirst($right->basis); ?>
              </span>
            </td>
            <td>
              <?php if ($right->rights_statement_name): ?>
                <a href="<?php echo $right->rights_statement_uri; ?>" target="_blank">
                  <?php echo $right->rights_statement_name; ?>
                </a>
              <?php elseif ($right->cc_license_name): ?>
                <a href="<?php echo $right->cc_license_uri; ?>" target="_blank">
                  <img src="<?php echo $right->cc_badge_url; ?>" alt="<?php echo $right->cc_license_name; ?>" height="20">
                </a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <?php if ($right->copyright_status): ?>
                <?php echo ucfirst(str_replace('_', ' ', $right->copyright_status)); ?>
              <?php endif; ?>
              <?php if ($right->copyright_holder): ?>
                <br><small>Holder: <?php echo $right->copyright_holder; ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($right->start_date || $right->end_date): ?>
                <?php echo $right->start_date ?: '...'; ?> - <?php echo $right->end_date ?: '...'; ?>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
            <td>
              <?php if ($sf_user->isAuthenticated()): ?>
              <div class="btn-group btn-group-sm">
                <a href="<?php echo url_for(['module' => 'rights', 'action' => 'edit', 'slug' => $resource->slug, 'id' => $right->id]); ?>" 
                   class="btn btn-outline-secondary" title="Edit">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="<?php echo url_for(['module' => 'rights', 'action' => 'delete', 'slug' => $resource->slug, 'id' => $right->id]); ?>" 
                   class="btn btn-outline-danger" title="Delete"
                   onclick="return confirm('Delete this rights record?');">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="text-muted mb-0"><?php echo __('No rights records have been added yet.'); ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- Orphan Work -->
<?php if ($orphanWork): ?>
<div class="card mb-4">
  <div class="card-header bg-info text-white">
    <h5 class="mb-0"><i class="fas fa-search me-2"></i><?php echo __('Orphan Work Due Diligence'); ?></h5>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3"><?php echo __('Status'); ?></dt>
      <dd class="col-sm-9">
        <span class="badge bg-<?php 
          echo match($orphanWork->status) {
            'in_progress' => 'warning',
            'completed' => 'success',
            'rights_holder_found' => 'info',
            'abandoned' => 'secondary',
            default => 'light'
          };
        ?>"><?php echo ucfirst(str_replace('_', ' ', $orphanWork->status)); ?></span>
      </dd>

      <dt class="col-sm-3"><?php echo __('Work Type'); ?></dt>
      <dd class="col-sm-9"><?php echo ucfirst(str_replace('_', ' ', $orphanWork->work_type)); ?></dd>

      <dt class="col-sm-3"><?php echo __('Search Started'); ?></dt>
      <dd class="col-sm-9"><?php echo $orphanWork->search_started_date; ?></dd>

      <?php if ($orphanWork->search_completed_date): ?>
      <dt class="col-sm-3"><?php echo __('Search Completed'); ?></dt>
      <dd class="col-sm-9"><?php echo $orphanWork->search_completed_date; ?></dd>
      <?php endif; ?>
    </dl>
    <a href="<?php echo url_for(['module' => 'rightsAdmin', 'action' => 'orphanWorkEdit', 'id' => $orphanWork->id]); ?>" 
       class="btn btn-sm btn-outline-info mt-2">
      <?php echo __('View Search Details'); ?>
    </a>
  </div>
</div>
<?php endif; ?>
