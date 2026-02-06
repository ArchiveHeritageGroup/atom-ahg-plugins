<?php decorate_with('layout_2col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Webhooks'); ?></h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
  <?php include_component('settings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-broadcast me-2"></i><?php echo __('Webhook Management'); ?></h5>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createWebhookModal">
      <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Webhook'); ?>
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

    <?php if ($sf_user->hasFlash('new_webhook_secret')): ?>
      <div class="alert alert-warning">
        <h5><i class="bi bi-exclamation-triangle me-2"></i><?php echo __('Save Your Webhook Secret'); ?></h5>
        <p class="mb-2"><?php echo __('This is your webhook secret for HMAC signature verification. Copy it now - it will not be shown again!'); ?></p>
        <div class="input-group">
          <input type="text" class="form-control font-monospace" id="newWebhookSecret" value="<?php echo $sf_user->getFlash('new_webhook_secret'); ?>" readonly>
          <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
            <i class="bi bi-clipboard"></i> <?php echo __('Copy'); ?>
          </button>
        </div>
      </div>
    <?php endif; ?>

    <p class="text-muted mb-3">
      <?php echo __('Webhooks notify external applications when records are created, updated, or deleted. Each webhook receives an HMAC signature for verification.'); ?>
    </p>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('URL'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('Events'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Deliveries'); ?></th>
            <th><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($webhooks)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-broadcast fs-1 d-block mb-2"></i>
                <?php echo __('No webhooks configured.'); ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($webhooks as $webhook): ?>
              <tr>
                <td>
                  <strong><?php echo esc_specialchars($webhook->name); ?></strong>
                  <?php if ($webhook->failure_count > 0): ?>
                    <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo $webhook->failure_count; ?> failures</small>
                  <?php endif; ?>
                </td>
                <td>
                  <code class="small"><?php echo esc_specialchars(strlen($webhook->url) > 40 ? substr($webhook->url, 0, 40) . '...' : $webhook->url); ?></code>
                </td>
                <td><?php echo esc_specialchars($webhook->username ?? 'Unknown'); ?></td>
                <td>
                  <?php foreach ($webhook->events as $event): ?>
                    <?php
                    $badgeClass = match($event) {
                        'item.created' => 'bg-success',
                        'item.updated' => 'bg-primary',
                        'item.deleted' => 'bg-danger',
                        'item.published' => 'bg-info',
                        'item.unpublished' => 'bg-secondary',
                        default => 'bg-secondary'
                    };
                    $label = $eventLabels[$event] ?? $event;
                    ?>
                    <span class="badge <?php echo $badgeClass; ?> mb-1"><?php echo $label; ?></span>
                  <?php endforeach; ?>
                </td>
                <td>
                  <?php if ($webhook->is_active): ?>
                    <span class="badge bg-success"><?php echo __('Active'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-danger"><?php echo __('Inactive'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="text-success" title="<?php echo __('Successful'); ?>"><?php echo $webhook->stats['success']; ?></span> /
                  <span class="text-danger" title="<?php echo __('Failed'); ?>"><?php echo $webhook->stats['failed']; ?></span> /
                  <span class="text-warning" title="<?php echo __('Pending'); ?>"><?php echo $webhook->stats['pending']; ?></span>
                  <small class="text-muted d-block"><?php echo __('success/fail/pending'); ?></small>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                      <?php echo __('Actions'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <form method="post" class="d-inline">
                          <?php echo $form->renderHiddenFields(); ?>
                          <input type="hidden" name="action_type" value="toggle">
                          <input type="hidden" name="webhook_id" value="<?php echo $webhook->id; ?>">
                          <button type="submit" class="dropdown-item">
                            <i class="bi bi-<?php echo $webhook->is_active ? 'pause-fill' : 'play-fill'; ?> me-2"></i>
                            <?php echo $webhook->is_active ? __('Deactivate') : __('Activate'); ?>
                          </button>
                        </form>
                      </li>
                      <li>
                        <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Regenerate secret? The old secret will stop working immediately.'); ?>');">
                          <?php echo $form->renderHiddenFields(); ?>
                          <input type="hidden" name="action_type" value="regenerate">
                          <input type="hidden" name="webhook_id" value="<?php echo $webhook->id; ?>">
                          <button type="submit" class="dropdown-item">
                            <i class="bi bi-key me-2"></i><?php echo __('Regenerate Secret'); ?>
                          </button>
                        </form>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deliveryModal<?php echo $webhook->id; ?>">
                          <i class="bi bi-list-ul me-2"></i><?php echo __('View Deliveries'); ?>
                        </a>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this webhook?'); ?>');">
                          <?php echo $form->renderHiddenFields(); ?>
                          <input type="hidden" name="action_type" value="delete">
                          <input type="hidden" name="webhook_id" value="<?php echo $webhook->id; ?>">
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-trash me-2"></i><?php echo __('Delete'); ?>
                          </button>
                        </form>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Documentation Card -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="bi bi-book me-2"></i><?php echo __('Webhook Documentation'); ?></h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <h6><?php echo __('Supported Events'); ?></h6>
        <table class="table table-sm table-bordered mb-4">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Event'); ?></th>
              <th><?php echo __('Triggered When'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>item.created</code></td><td><?php echo __('A new record is created'); ?></td></tr>
            <tr><td><code>item.updated</code></td><td><?php echo __('An existing record is modified'); ?></td></tr>
            <tr><td><code>item.deleted</code></td><td><?php echo __('A record is deleted'); ?></td></tr>
            <tr><td><code>item.published</code></td><td><?php echo __('A record is published'); ?></td></tr>
            <tr><td><code>item.unpublished</code></td><td><?php echo __('A record is unpublished'); ?></td></tr>
          </tbody>
        </table>
      </div>
      <div class="col-md-6">
        <h6><?php echo __('Entity Types'); ?></h6>
        <table class="table table-sm table-bordered mb-4">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Records'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>informationobject</code></td><td><?php echo __('Archival descriptions'); ?></td></tr>
            <tr><td><code>actor</code></td><td><?php echo __('Authority records'); ?></td></tr>
            <tr><td><code>repository</code></td><td><?php echo __('Repositories'); ?></td></tr>
            <tr><td><code>accession</code></td><td><?php echo __('Accessions'); ?></td></tr>
            <tr><td><code>term</code></td><td><?php echo __('Taxonomy terms'); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <h6><?php echo __('Signature Verification'); ?></h6>
    <p><?php echo __('Each webhook request includes an X-Webhook-Signature header with an HMAC SHA-256 signature. Verify this to ensure the request is authentic:'); ?></p>
    <pre class="bg-light p-3 rounded"><code>$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}</code></pre>

    <h6 class="mt-4"><?php echo __('Payload Format'); ?></h6>
    <pre class="bg-light p-3 rounded"><code>{
  "event": "item.created",
  "entity_type": "informationobject",
  "entity_id": 12345,
  "timestamp": "2024-01-15T10:30:00+00:00",
  "data": {
    "slug": "my-record",
    "title": "Record Title"
  }
}</code></pre>
  </div>
</div>

<!-- Create Webhook Modal -->
<div class="modal fade" id="createWebhookModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <?php echo $form->renderHiddenFields(); ?>
        <input type="hidden" name="action_type" value="create">

        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i><?php echo __('Create Webhook'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Webhook Name'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="webhook_name" class="form-control" required placeholder="<?php echo __('e.g., Sync to CMS'); ?>">
                <div class="form-text"><?php echo __('A descriptive name to identify this webhook.'); ?></div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Webhook URL'); ?> <span class="text-danger">*</span></label>
                <input type="url" name="webhook_url" class="form-control" required placeholder="https://example.com/webhook">
                <div class="form-text"><?php echo __('HTTPS URL that will receive POST requests.'); ?></div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Owner'); ?> <span class="text-danger">*</span></label>
                <select name="user_id" class="form-select" required>
                  <option value=""><?php echo __('Select a user...'); ?></option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user->id; ?>"><?php echo esc_specialchars($user->username); ?> (<?php echo esc_specialchars($user->email); ?>)</option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text"><?php echo __('The user who owns this webhook.'); ?></div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Events'); ?></label>
                <?php foreach ($supportedEvents as $event): ?>
                  <div class="form-check">
                    <input type="checkbox" name="events[]" value="<?php echo $event; ?>" class="form-check-input" id="event_<?php echo str_replace('.', '_', $event); ?>"
                      <?php echo in_array($event, ['item.created', 'item.updated', 'item.deleted']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="event_<?php echo str_replace('.', '_', $event); ?>">
                      <?php echo $eventLabels[$event] ?? $event; ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="mb-3">
                <label class="form-label"><?php echo __('Entity Types'); ?></label>
                <?php foreach ($supportedEntityTypes as $entityType): ?>
                  <div class="form-check">
                    <input type="checkbox" name="entity_types[]" value="<?php echo $entityType; ?>" class="form-check-input" id="entity_<?php echo $entityType; ?>"
                      <?php echo $entityType === 'informationobject' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="entity_<?php echo $entityType; ?>">
                      <?php echo $entityTypeLabels[$entityType] ?? $entityType; ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
          <button type="submit" class="btn btn-primary"><?php echo __('Create Webhook'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delivery Modals -->
<?php foreach ($webhooks as $webhook): ?>
<div class="modal fade" id="deliveryModal<?php echo $webhook->id; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i><?php echo __('Recent Deliveries'); ?> - <?php echo esc_specialchars($webhook->name); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php
        $deliveries = \Illuminate\Database\Capsule\Manager::table('ahg_webhook_delivery')
            ->where('webhook_id', $webhook->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        ?>
        <?php if ($deliveries->isEmpty()): ?>
          <p class="text-muted text-center py-4"><?php echo __('No deliveries yet.'); ?></p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th><?php echo __('Time'); ?></th>
                  <th><?php echo __('Event'); ?></th>
                  <th><?php echo __('Entity'); ?></th>
                  <th><?php echo __('Status'); ?></th>
                  <th><?php echo __('Response'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($deliveries as $delivery): ?>
                  <tr>
                    <td><small><?php echo date('Y-m-d H:i:s', strtotime($delivery->created_at)); ?></small></td>
                    <td><code><?php echo $delivery->event_type; ?></code></td>
                    <td><?php echo $delivery->entity_type; ?> #<?php echo $delivery->entity_id; ?></td>
                    <td>
                      <?php
                      $statusClass = match($delivery->status) {
                          'success' => 'bg-success',
                          'failed' => 'bg-danger',
                          'pending' => 'bg-warning',
                          'retrying' => 'bg-info',
                          default => 'bg-secondary'
                      };
                      ?>
                      <span class="badge <?php echo $statusClass; ?>"><?php echo $delivery->status; ?></span>
                      <?php if ($delivery->attempt_count > 1): ?>
                        <small class="text-muted">(<?php echo $delivery->attempt_count; ?> attempts)</small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($delivery->response_code): ?>
                        <code><?php echo $delivery->response_code; ?></code>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function copySecret() {
  var input = document.getElementById('newWebhookSecret');
  input.select();
  input.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(input.value);
  alert('Webhook secret copied to clipboard!');
}
</script>

<?php end_slot(); ?>
