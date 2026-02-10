@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Webhooks') }}</h1>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-broadcast me-2"></i>{{ __('Webhook Management') }}</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createWebhookModal">
          <i class="bi bi-plus-lg me-1"></i>{{ __('Create Webhook') }}
        </button>
      </div>
      <div class="card-body">

        @if ($sf_user->hasFlash('new_webhook_secret'))
          <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle me-2"></i>{{ __('Save Your Webhook Secret') }}</h5>
            <p class="mb-2">{{ __('This is your webhook secret for HMAC signature verification. Copy it now - it will not be shown again!') }}</p>
            <div class="input-group">
              <input type="text" class="form-control font-monospace" id="newWebhookSecret" value="{{ $sf_user->getFlash('new_webhook_secret') }}" readonly>
              <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
                <i class="bi bi-clipboard"></i> {{ __('Copy') }}
              </button>
            </div>
          </div>
        @endif

        <p class="text-muted mb-3">
          {{ __('Webhooks notify external applications when records are created, updated, or deleted. Each webhook receives an HMAC signature for verification.') }}
        </p>

        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('URL') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Events') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Deliveries') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @if (empty($webhooks))
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-broadcast fs-1 d-block mb-2"></i>
                    {{ __('No webhooks configured.') }}
                  </td>
                </tr>
              @else
                @foreach ($webhooks as $webhook)
                  <tr>
                    <td>
                      <strong>{{ $webhook->name }}</strong>
                      @if ($webhook->failure_count > 0)
                        <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> {{ $webhook->failure_count }} failures</small>
                      @endif
                    </td>
                    <td>
                      <code class="small">{{ strlen($webhook->url) > 40 ? substr($webhook->url, 0, 40) . '...' : $webhook->url }}</code>
                    </td>
                    <td>{{ $webhook->username ?? 'Unknown' }}</td>
                    <td>
                      @foreach ($webhook->events as $event)
                        @php
                        $badgeClass = match($event) {
                            'item.created' => 'bg-success',
                            'item.updated' => 'bg-primary',
                            'item.deleted' => 'bg-danger',
                            'item.published' => 'bg-info',
                            'item.unpublished' => 'bg-secondary',
                            default => 'bg-secondary'
                        };
                        $label = $eventLabels[$event] ?? $event;
                        @endphp
                        <span class="badge {{ $badgeClass }} mb-1">{{ $label }}</span>
                      @endforeach
                    </td>
                    <td>
                      @if ($webhook->is_active)
                        <span class="badge bg-success">{{ __('Active') }}</span>
                      @else
                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                      @endif
                    </td>
                    <td>
                      <span class="text-success" title="{{ __('Successful') }}">{{ $webhook->stats['success'] }}</span> /
                      <span class="text-danger" title="{{ __('Failed') }}">{{ $webhook->stats['failed'] }}</span> /
                      <span class="text-warning" title="{{ __('Pending') }}">{{ $webhook->stats['pending'] }}</span>
                      <small class="text-muted d-block">{{ __('success/fail/pending') }}</small>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                          {{ __('Actions') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li>
                            <form method="post" class="d-inline">
                              {!! $form->renderHiddenFields() !!}
                              <input type="hidden" name="action_type" value="toggle">
                              <input type="hidden" name="webhook_id" value="{{ $webhook->id }}">
                              <button type="submit" class="dropdown-item">
                                <i class="bi bi-{{ $webhook->is_active ? 'pause-fill' : 'play-fill' }} me-2"></i>
                                {{ $webhook->is_active ? __('Deactivate') : __('Activate') }}
                              </button>
                            </form>
                          </li>
                          <li>
                            <form method="post" class="d-inline" onsubmit="return confirm('{{ __('Regenerate secret? The old secret will stop working immediately.') }}');">
                              {!! $form->renderHiddenFields() !!}
                              <input type="hidden" name="action_type" value="regenerate">
                              <input type="hidden" name="webhook_id" value="{{ $webhook->id }}">
                              <button type="submit" class="dropdown-item">
                                <i class="bi bi-key me-2"></i>{{ __('Regenerate Secret') }}
                              </button>
                            </form>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#deliveryModal{{ $webhook->id }}">
                              <i class="bi bi-list-ul me-2"></i>{{ __('View Deliveries') }}
                            </a>
                          </li>
                          <li><hr class="dropdown-divider"></li>
                          <li>
                            <form method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this webhook?') }}');">
                              {!! $form->renderHiddenFields() !!}
                              <input type="hidden" name="action_type" value="delete">
                              <input type="hidden" name="webhook_id" value="{{ $webhook->id }}">
                              <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-trash me-2"></i>{{ __('Delete') }}
                              </button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Documentation Card -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-book me-2"></i>{{ __('Webhook Documentation') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6>{{ __('Supported Events') }}</h6>
            <table class="table table-sm table-bordered mb-4">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Event') }}</th>
                  <th>{{ __('Triggered When') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr><td><code>item.created</code></td><td>{{ __('A new record is created') }}</td></tr>
                <tr><td><code>item.updated</code></td><td>{{ __('An existing record is modified') }}</td></tr>
                <tr><td><code>item.deleted</code></td><td>{{ __('A record is deleted') }}</td></tr>
                <tr><td><code>item.published</code></td><td>{{ __('A record is published') }}</td></tr>
                <tr><td><code>item.unpublished</code></td><td>{{ __('A record is unpublished') }}</td></tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6">
            <h6>{{ __('Entity Types') }}</h6>
            <table class="table table-sm table-bordered mb-4">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Type') }}</th>
                  <th>{{ __('Records') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr><td><code>informationobject</code></td><td>{{ __('Archival descriptions') }}</td></tr>
                <tr><td><code>actor</code></td><td>{{ __('Authority records') }}</td></tr>
                <tr><td><code>repository</code></td><td>{{ __('Repositories') }}</td></tr>
                <tr><td><code>accession</code></td><td>{{ __('Accessions') }}</td></tr>
                <tr><td><code>term</code></td><td>{{ __('Taxonomy terms') }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <h6>{{ __('Signature Verification') }}</h6>
        <p>{{ __('Each webhook request includes an X-Webhook-Signature header with an HMAC SHA-256 signature. Verify this to ensure the request is authentic:') }}</p>
        <pre class="bg-light p-3 rounded"><code>$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}</code></pre>

        <h6 class="mt-4">{{ __('Payload Format') }}</h6>
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
            {!! $form->renderHiddenFields() !!}
            <input type="hidden" name="action_type" value="create">

            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>{{ __('Create Webhook') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ __('Webhook Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="webhook_name" class="form-control" required placeholder="{{ __('e.g., Sync to CMS') }}">
                    <div class="form-text">{{ __('A descriptive name to identify this webhook.') }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">{{ __('Webhook URL') }} <span class="text-danger">*</span></label>
                    <input type="url" name="webhook_url" class="form-control" required placeholder="https://example.com/webhook">
                    <div class="form-text">{{ __('HTTPS URL that will receive POST requests.') }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">{{ __('Owner') }} <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select" required>
                      <option value="">{{ __('Select a user...') }}</option>
                      @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->email }})</option>
                      @endforeach
                    </select>
                    <div class="form-text">{{ __('The user who owns this webhook.') }}</div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">{{ __('Events') }}</label>
                    @foreach ($supportedEvents as $event)
                      <div class="form-check">
                        <input type="checkbox" name="events[]" value="{{ $event }}" class="form-check-input" id="event_{{ str_replace('.', '_', $event) }}"
                          {{ in_array($event, ['item.created', 'item.updated', 'item.deleted']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="event_{{ str_replace('.', '_', $event) }}">
                          {{ $eventLabels[$event] ?? $event }}
                        </label>
                      </div>
                    @endforeach
                  </div>

                  <div class="mb-3">
                    <label class="form-label">{{ __('Entity Types') }}</label>
                    @foreach ($supportedEntityTypes as $entityType)
                      <div class="form-check">
                        <input type="checkbox" name="entity_types[]" value="{{ $entityType }}" class="form-check-input" id="entity_{{ $entityType }}"
                          {{ $entityType === 'informationobject' ? 'checked' : '' }}>
                        <label class="form-check-label" for="entity_{{ $entityType }}">
                          {{ $entityTypeLabels[$entityType] ?? $entityType }}
                        </label>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Create Webhook') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delivery Modals -->
    @foreach ($webhooks as $webhook)
    <div class="modal fade" id="deliveryModal{{ $webhook->id }}" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i>{{ __('Recent Deliveries') }} - {{ $webhook->name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            @php
            $deliveries = \Illuminate\Database\Capsule\Manager::table('ahg_webhook_delivery')
                ->where('webhook_id', $webhook->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
            @endphp
            @if ($deliveries->isEmpty())
              <p class="text-muted text-center py-4">{{ __('No deliveries yet.') }}</p>
            @else
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>{{ __('Time') }}</th>
                      <th>{{ __('Event') }}</th>
                      <th>{{ __('Entity') }}</th>
                      <th>{{ __('Status') }}</th>
                      <th>{{ __('Response') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($deliveries as $delivery)
                      <tr>
                        <td><small>{{ date('Y-m-d H:i:s', strtotime($delivery->created_at)) }}</small></td>
                        <td><code>{{ $delivery->event_type }}</code></td>
                        <td>{{ $delivery->entity_type }} #{{ $delivery->entity_id }}</td>
                        <td>
                          @php
                          $statusClass = match($delivery->status) {
                              'success' => 'bg-success',
                              'failed' => 'bg-danger',
                              'pending' => 'bg-warning',
                              'retrying' => 'bg-info',
                              default => 'bg-secondary'
                          };
                          @endphp
                          <span class="badge {{ $statusClass }}">{{ $delivery->status }}</span>
                          @if ($delivery->attempt_count > 1)
                            <small class="text-muted">({{ $delivery->attempt_count }} attempts)</small>
                          @endif
                        </td>
                        <td>
                          @if ($delivery->response_code)
                            <code>{{ $delivery->response_code }}</code>
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
          </div>
        </div>
      </div>
    </div>
    @endforeach

    <script {!! $csp_nonce !!}>
    function copySecret() {
      var input = document.getElementById('newWebhookSecret');
      input.select();
      input.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(input.value);
      alert('Webhook secret copied to clipboard!');
    }
    </script>

  </div>
</div>
@endsection
