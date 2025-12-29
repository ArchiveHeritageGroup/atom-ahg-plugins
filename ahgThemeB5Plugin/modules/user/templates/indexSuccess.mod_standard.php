<?php
// Get security clearance for this user
use AtomExtensions\Services\SecurityClearanceService;

$userClearance = null;
$clearanceLevel = 0;
$clearanceName = 'None';
$clearanceColor = 'secondary';

try {
    if (class_exists('AtomExtensions\Services\SecurityClearanceService')) {
        $clearanceLevel = SecurityClearanceService::getUserClearance($resource->id);
        $clearanceInfo = SecurityClearanceService::getUserClearance($resource->id);
        
        if ($clearanceInfo) {
            $userClearance = $clearanceInfo;
            $clearanceName = $clearanceInfo->classificationName ?? 'Unknown';
            
            // Color coding based on level
            if ($clearanceLevel >= 4) {
                $clearanceColor = 'danger';
            } elseif ($clearanceLevel >= 2) {
                $clearanceColor = 'warning';
            } elseif ($clearanceLevel >= 1) {
                $clearanceColor = 'info';
            } else {
                $clearanceColor = 'success';
            }
        }
    }
} catch (Exception $e) {
    // Silently fail if service not available
}

// Check if current user is admin
$isAdmin = $sf_user->isAdministrator();
$canManageClearance = $isAdmin || $sf_user->hasCredential('manage_security');
?>

<h1><?php echo __('User %1%', ['%1%' => render_title($resource)]); ?></h1>

<?php if (!$resource->active) { ?>
  <div class="alert alert-danger" role="alert">
    <?php echo __('This user is inactive'); ?>
  </div>
<?php } ?>

<?php echo get_component('user', 'aclMenu'); ?>

<section id="content">

  <section id="userDetails">

    <?php echo render_b5_section_heading(
        __('User details'),
        \AtomExtensions\Services\AclService::check($resource, 'update'),
        [$resource, 'module' => 'user', 'action' => 'edit'],
        ['class' => 'rounded-top']
    ); ?>

    <?php echo render_show(__('User name'), render_value_inline($resource->username.($sf_user->user === $resource ? ' ('.__('you').')' : ''))); ?>

    <?php echo render_show(__('Email'), $resource->email); ?>

    <?php if (!$sf_user->isAdministrator()) { ?>
      <?php echo render_show(__('Password'), link_to(__('Reset password'), [$resource, 'module' => 'user', 'action' => 'passwordEdit'])); ?>
    <?php } ?>

    <?php if (0 < count($groups = $resource->getAclGroups())) { ?>
      <?php echo render_show(__('User groups'), $groups); ?>
    <?php } ?>

    <?php if (
        sfConfig::get('app_multi_repository')
        && 0 < count($repositories = $resource->getRepositories())
    ) { ?>
      <?php
          $repos = [];
          foreach ($repositories as $item) {
              $repos[] = render_title($item);
          }
          echo render_show(__('Repository affiliation'), $repos);
      ?>
    <?php } ?>

    <?php if ($sf_context->getConfiguration()->isPluginEnabled('arRestApiPlugin')) { ?>
      <?php echo render_show(
          __('REST API key'),
          isset($restApiKey) ? '<code>'.$restApiKey.'</code>' : __('Not generated yet.')
      ); ?>
    <?php } ?>

    <?php if ($sf_context->getConfiguration()->isPluginEnabled('arOaiPlugin')) { ?>
      <?php echo render_show(
          __('OAI-PMH API key'),
          isset($oaiApiKey) ? '<code>'.$oaiApiKey.'</code>' : __('Not generated yet.')
      ); ?>
    <?php } ?>

  </section>

  <!-- Security Clearance Section -->
  <section id="securityClearance" class="mt-4">
    
    <div class="section border rounded">
      <div class="d-flex justify-content-between align-items-center section-heading rounded-top bg-light p-3">
        <h4 class="mb-0">
          <i class="fas fa-shield-alt me-2"></i><?php echo __('Security Clearance'); ?>
        </h4>
        <?php if ($canManageClearance) { ?>
          <a href="<?php echo url_for('@security_clearances'); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-cog me-1"></i><?php echo __('Manage Clearances'); ?>
          </a>
        <?php } ?>
      </div>
      
      <div class="p-3">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <strong><?php echo __('Current Clearance Level'); ?>:</strong>
              <span class="badge bg-<?php echo $clearanceColor; ?> ms-2 fs-6">
                <i class="fas fa-<?php echo $clearanceLevel >= 4 ? 'lock' : ($clearanceLevel >= 2 ? 'user-shield' : 'unlock'); ?> me-1"></i>
                <?php echo $clearanceName; ?>
              </span>
            </div>
            
            <?php if ($userClearance) { ?>
              <div class="mb-2">
                <strong><?php echo __('Granted'); ?>:</strong>
                <?php echo isset($userClearance->granted_at) ? date('Y-m-d', strtotime($userClearance->granted_at)) : 'N/A'; ?>
              </div>
              
              <?php if (isset($userClearance->expires_at) && $userClearance->expires_at) { ?>
                <div class="mb-2">
                  <strong><?php echo __('Expires'); ?>:</strong>
                  <?php 
                  $expiresAt = strtotime($userClearance->expires_at);
                  $isExpired = $expiresAt < time();
                  $isExpiringSoon = $expiresAt < strtotime('+30 days');
                  ?>
                  <span class="<?php echo $isExpired ? 'text-danger' : ($isExpiringSoon ? 'text-warning' : ''); ?>">
                    <?php echo date('Y-m-d', $expiresAt); ?>
                    <?php if ($isExpired) { ?>
                      <span class="badge bg-danger ms-1"><?php echo __('EXPIRED'); ?></span>
                    <?php } elseif ($isExpiringSoon) { ?>
                      <span class="badge bg-warning text-dark ms-1"><?php echo __('Expiring Soon'); ?></span>
                    <?php } ?>
                  </span>
                </div>
              <?php } ?>
              
              <?php if (isset($userClearance->notes) && $userClearance->notes) { ?>
                <div class="mb-2">
                  <strong><?php echo __('Notes'); ?>:</strong>
                  <span class="text-muted"><?php echo htmlspecialchars($userClearance->notes); ?></span>
                </div>
              <?php } ?>
            <?php } else { ?>
              <p class="text-muted mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo __('No security clearance assigned. This user can only access public records.'); ?>
              </p>
            <?php } ?>
          </div>
          
          <div class="col-md-6">
            <div class="card bg-light">
              <div class="card-body">
                <h6 class="card-title">
                  <i class="fas fa-info-circle me-1"></i><?php echo __('Classification Levels'); ?>
                </h6>
                <ul class="list-unstyled small mb-0">
                  <li><span class="badge bg-success">Public</span> - <?php echo __('Open access materials'); ?></li>
                  <li><span class="badge bg-info">Restricted</span> - <?php echo __('Limited distribution'); ?></li>
                  <li><span class="badge bg-warning text-dark">Confidential</span> - <?php echo __('Sensitive information'); ?></li>
                  <li><span class="badge bg-danger">Secret</span> - <?php echo __('Highly sensitive'); ?></li>
                  <li><span class="badge bg-dark">Top Secret</span> - <?php echo __('Maximum protection'); ?></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        
        <?php if ($canManageClearance && $sf_user->user->id !== $resource->id) { ?>
          <hr>
          <div class="d-flex gap-2">
            <?php if ($userClearance) { ?>
              <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#grantClearanceModal">
                <i class="fas fa-edit me-1"></i><?php echo __('Change Clearance'); ?>
              </button>
              <a href="<?php echo url_for('@security_clearance_revoke?id=' . $resource->id); ?>" 
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('<?php echo __('Are you sure you want to revoke this user\'s security clearance?'); ?>');">
                <i class="fas fa-user-slash me-1"></i><?php echo __('Revoke Clearance'); ?>
              </a>
            <?php } else { ?>
              <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#grantClearanceModal">
                <i class="fas fa-user-shield me-1"></i><?php echo __('Grant Clearance'); ?>
              </button>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
    
  </section>

  <?php if (sfConfig::get('app_audit_log_enabled', false)) { ?>
    <section id="editingHistorySection" class="mt-4">
      <div id="editing-history-wrapper">
        <div class="accordion accordion-flush border rounded" id="editingHistory">
          <div class="accordion-item rounded">
            <h2 class="accordion-header" id="history-heading">
              <button class="accordion-button collapsed text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#history-collapse" aria-expanded="false" aria-controls="history-collapse">
                <?php echo __('Editing history'); ?>
                <span id="editingHistoryActivityIndicator">
                  <i class="fas fa-spinner fa-spin ms-2" aria-hidden="true"></i>
                  <span class="visually-hidden"><?php echo __('Loading ...'); ?></span>
                </span>
              </button>
            </h2>
            <div id="history-collapse" class="accordion-collapse collapse" aria-labelledby="history-heading">
              <div class="accordion-body">
                <div class="table-responsive mb-3">
                  <table class="table table-bordered mb-0">
                    <thead>
                      <tr>
                        <th><?php echo __('Title'); ?></th>
                        <th><?php echo __('Date'); ?></th>
                        <th><?php echo __('Type'); ?></th>
                      </tr>
                    </thead>
                    <tbody id="editingHistoryRows">
                    </tbody>
                  </table>
                </div>

                <div class="text-end">
                  <input class="btn atom-btn-white" type="button" id='previousButton' value='<?php echo __('Previous'); ?>'>
                  <input class="btn atom-btn-white ms-2" type="button" id='nextButton' value='<?php echo __('Next'); ?>'>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  <?php } ?>

</section>

<?php echo get_partial('showActions', ['resource' => $resource]); ?>

<!-- Grant/Change Clearance Modal -->
<?php if ($canManageClearance && $sf_user->user->id !== $resource->id) { ?>
<div class="modal fade" id="grantClearanceModal" tabindex="-1" aria-labelledby="grantClearanceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?php echo url_for('@security_clearance_grant'); ?>" method="post">
        <input type="hidden" name="user_id" value="<?php echo $resource->id; ?>">
        
        <div class="modal-header">
          <h5 class="modal-title" id="grantClearanceModalLabel">
            <i class="fas fa-user-shield me-2"></i>
            <?php echo $userClearance ? __('Change Security Clearance') : __('Grant Security Clearance'); ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body">
          <p class="text-muted">
            <?php echo __('Assigning clearance to: %1%', ['%1%' => '<strong>' . htmlspecialchars($resource->username) . '</strong>']); ?>
          </p>
          
          <div class="mb-3">
            <label for="classification_id" class="form-label"><?php echo __('Classification Level'); ?> <span class="text-danger">*</span></label>
            <select name="classification_id" id="classification_id" class="form-select" required>
              <option value=""><?php echo __('Select level...'); ?></option>
              <?php
              // Get classification levels from database
              try {
                  $classifications = Illuminate\Support\Facades\DB::table('security_classification')
                      ->where('active', 1)
                      ->orderBy('level', 'asc')
                      ->get();
                  foreach ($classifications as $class) {
                      $selected = ($userClearance && $userClearance->classification_id == $class->id) ? 'selected' : '';
                      echo '<option value="' . $class->id . '" ' . $selected . '>' . htmlspecialchars($class->name) . ' (Level ' . $class->level . ')</option>';
                  }
              } catch (Exception $e) {
                  // Fallback options
                  echo '<option value="1">Public (Level 0)</option>';
                  echo '<option value="2">Restricted (Level 1)</option>';
                  echo '<option value="3">Confidential (Level 2)</option>';
                  echo '<option value="4">Secret (Level 3)</option>';
                  echo '<option value="5">Top Secret (Level 4)</option>';
              }
              ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="expires_at" class="form-label"><?php echo __('Expiration Date'); ?></label>
            <input type="date" name="expires_at" id="expires_at" class="form-control" 
                   value="<?php echo ($userClearance && isset($userClearance->expires_at)) ? date('Y-m-d', strtotime($userClearance->expires_at)) : ''; ?>"
                   min="<?php echo date('Y-m-d'); ?>">
            <div class="form-text"><?php echo __('Leave blank for no expiration'); ?></div>
          </div>
          
          <div class="mb-3">
            <label for="notes" class="form-label"><?php echo __('Notes'); ?></label>
            <textarea name="notes" id="notes" class="form-control" rows="3" 
                      placeholder="<?php echo __('Reason for granting clearance, special conditions, etc.'); ?>"><?php echo ($userClearance && isset($userClearance->notes)) ? htmlspecialchars($userClearance->notes) : ''; ?></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <?php echo __('Cancel'); ?>
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>
            <?php echo $userClearance ? __('Update Clearance') : __('Grant Clearance'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php } ?>
