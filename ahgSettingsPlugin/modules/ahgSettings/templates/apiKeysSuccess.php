<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('API Keys'); ?></h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <?php include_component('settings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-key me-2"></i><?php echo __('API Key Management'); ?></h5>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createKeyModal">
      <i class="bi bi-plus-lg me-1"></i><?php echo __('Create New Key'); ?>
    </button>
  </div>
  <div class="card-body">

    <?php if ($sf_user->hasFlash('success')): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($sf_user->hasFlash('new_api_key')): ?>
      <div class="alert alert-warning">
        <h5><i class="bi bi-exclamation-triangle me-2"></i><?php echo __('Save Your API Key'); ?></h5>
        <p class="mb-2"><?php echo __('This is your new API key. Copy it now - it will not be shown again!'); ?></p>
        <div class="input-group">
          <input type="text" class="form-control font-monospace" id="newApiKey" value="<?php echo $sf_user->getFlash('new_api_key'); ?>" readonly>
          <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
            <i class="bi bi-clipboard"></i> <?php echo __('Copy'); ?>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <p class="text-muted mb-3">
      <?php echo __('API keys allow external applications to access the REST API. Each key is associated with a user account and inherits their permissions.'); ?>
    </p>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('Key Prefix'); ?></th>
            <th><?php echo __('Scopes'); ?></th>
            <th><?php echo __('Rate Limit'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Last Used'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($apiKeys)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-key fs-1 d-block mb-2"></i>
                <?php echo __('No API keys configured.'); ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($apiKeys as $key): ?>
              <tr>
                <td><strong><?php echo esc_specialchars($key->name); ?></strong></td>
                <td><?php echo esc_specialchars($key->username ?? 'Unknown'); ?></td>
                <td><code><?php echo esc_specialchars($key->api_key_prefix); ?>...</code></td>
                <td>
                  <?php
                  $raw = $key->scopes;
                  if (is_string($raw)) {
                      $raw = html_entity_decode($raw, ENT_QUOTES, "UTF-8");
                      $scopes = json_decode($raw, true);
                  } else {
                      $scopes = [];
                  }
                  if (!empty($scopes) && is_array($scopes)): ?>
                    <?php foreach ($scopes as $scope):
                      $badgeClass = match($scope) {
                          "read" => "bg-success",
                          "write" => "bg-primary",
                          "delete" => "bg-danger",
                          default => "bg-secondary"
                      };
                    ?>
                      <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($scope); ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span class="text-muted">None</span>
                  <?php endif; ?>
                </td>
                <td><?php echo number_format($key->rate_limit); ?>/hr</td>
                <td>
                  <?php if ($key->is_active): ?>
                    <span class="badge bg-success"><?php echo __('Active'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?php echo __('Inactive'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($key->last_used_at): ?>
                    <?php echo date('Y-m-d H:i', strtotime($key->last_used_at)); ?>
                  <?php else: ?>
                    <span class="text-muted"><?php echo __('Never'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <?php echo $form->renderHiddenFields(); ?>
                    <input type="hidden" name="action_type" value="toggle">
                    <input type="hidden" name="key_id" value="<?php echo $key->id; ?>">
                    <button type="submit" class="btn btn-sm btn-<?php echo $key->is_active ? 'warning' : 'success'; ?>">
                      <i class="bi bi-<?php echo $key->is_active ? 'pause-fill' : 'play-fill'; ?> me-1"></i><?php echo $key->is_active ? __('Deactivate') : __('Activate'); ?>
                    </button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this API key?'); ?>');">
                    <?php echo $form->renderHiddenFields(); ?>
                    <input type="hidden" name="action_type" value="delete">
                    <input type="hidden" name="key_id" value="<?php echo $key->id; ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-trash me-1"></i><?php echo __('Delete'); ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-book me-2"></i><?php echo __('API Usage'); ?></h5>
  </div>
  <div class="card-body">
    <h6><?php echo __('Scopes & Permissions'); ?></h6>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Scope'); ?></th>
            <th><?php echo __('Permissions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><span class="badge bg-success">read</span></td>
            <td><?php echo __('View descriptions, authorities, repositories, taxonomies. Search records.'); ?></td>
          </tr>
          <tr>
            <td><span class="badge bg-primary">write</span></td>
            <td><?php echo __('Create new records and update existing records.'); ?></td>
          </tr>
          <tr>
            <td><span class="badge bg-danger">delete</span></td>
            <td><?php echo __('Delete records permanently.'); ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <h6><?php echo __('Authentication'); ?></h6>
    <p><?php echo __('Include your API key in the request header:'); ?></p>
    <pre class="bg-light p-3 rounded"><code>X-API-Key: your-api-key-here</code></pre>
    
    <h6 class="mt-4"><?php echo __('Example Request'); ?></h6>
    <pre class="bg-light p-3 rounded"><code>curl -H "X-API-Key: your-api-key" https://<?php echo $_SERVER['HTTP_HOST']; ?>/api/v2/descriptions</code></pre>

    <h6 class="mt-4"><?php echo __('Available Endpoints'); ?></h6>
    <ul>
      <li><code>GET /api/v2/descriptions</code> - <?php echo __('List descriptions'); ?></li>
      <li><code>GET /api/v2/descriptions/:slug</code> - <?php echo __('Get single description'); ?></li>
      <li><code>GET /api/v2/authorities</code> - <?php echo __('List authority records'); ?></li>
      <li><code>GET /api/v2/repositories</code> - <?php echo __('List repositories'); ?></li>
      <li><code>GET /api/v2/taxonomies</code> - <?php echo __('List taxonomies'); ?></li>
      <li><code>POST /api/v2/search</code> - <?php echo __('Search records'); ?></li>
    </ul>
  </div>
</div>

<!-- Create Key Modal -->
<div class="modal fade" id="createKeyModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <?php echo $form->renderHiddenFields(); ?>
        <input type="hidden" name="action_type" value="create">
        
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i><?php echo __('Create API Key'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Key Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="key_name" class="form-control" required placeholder="<?php echo __('e.g., Integration App'); ?>">
            <div class="form-text"><?php echo __('A descriptive name to identify this key.'); ?></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('User'); ?> <span class="text-danger">*</span></label>
            <select name="user_id" class="form-select" required>
              <option value=""><?php echo __('Select a user...'); ?></option>
              <?php foreach ($users as $user): ?>
                <option value="<?php echo $user->id; ?>"><?php echo esc_specialchars($user->username); ?> (<?php echo esc_specialchars($user->email); ?>)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-text"><?php echo __('The API key will inherit this user\'s permissions.'); ?></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('Scopes'); ?></label>
            <div class="form-check">
              <input type="checkbox" name="scopes[]" value="read" class="form-check-input" id="scopeRead" checked>
              <label class="form-check-label" for="scopeRead"><?php echo __('Read'); ?> - <?php echo __('View records'); ?></label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="scopes[]" value="write" class="form-check-input" id="scopeWrite">
              <label class="form-check-label" for="scopeWrite"><?php echo __('Write'); ?> - <?php echo __('Create and update records'); ?></label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="scopes[]" value="delete" class="form-check-input" id="scopeDelete">
              <label class="form-check-label" for="scopeDelete"><?php echo __('Delete'); ?> - <?php echo __('Delete records'); ?></label>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('Rate Limit (requests/hour)'); ?></label>
            <input type="number" name="rate_limit" class="form-control" value="1000" min="100" max="100000">
          </div>
          
          <div class="mb-3">
            <label class="form-label"><?php echo __('Expires At'); ?></label>
            <input type="datetime-local" name="expires_at" class="form-control">
            <div class="form-text"><?php echo __('Leave blank for no expiration.'); ?></div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo __('Create Key'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function copyApiKey() {
  var input = document.getElementById('newApiKey');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value);
  alert('API key copied to clipboard!');
}
</script>

<?php end_slot(); ?>
