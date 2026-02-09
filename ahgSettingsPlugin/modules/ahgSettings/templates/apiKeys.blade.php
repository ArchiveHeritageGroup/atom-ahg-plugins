@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('API Keys') }}</h1>

    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-key me-2"></i>{{ __('API Key Management') }}</h5>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createKeyModal">
          <i class="bi bi-plus-lg me-1"></i>{{ __('Create New Key') }}
        </button>
      </div>
      <div class="card-body">

        @if ($sf_user->hasFlash('new_api_key'))
          <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle me-2"></i>{{ __('Save Your API Key') }}</h5>
            <p class="mb-2">{{ __('This is your new API key. Copy it now - it will not be shown again!') }}</p>
            <div class="input-group">
              <input type="text" class="form-control font-monospace" id="newApiKey" value="{{ $sf_user->getFlash('new_api_key') }}" readonly>
              <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                <i class="bi bi-clipboard"></i> {{ __('Copy') }}
              </button>
            </div>
          </div>
        @endif

        <p class="text-muted mb-3">
          {{ __('API keys allow external applications to access the REST API. Each key is associated with a user account and inherits their permissions.') }}
        </p>

        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Key Prefix') }}</th>
                <th>{{ __('Scopes') }}</th>
                <th>{{ __('Rate Limit') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Last Used') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @if (empty($apiKeys))
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-key fs-1 d-block mb-2"></i>
                    {{ __('No API keys configured.') }}
                  </td>
                </tr>
              @else
                @foreach ($apiKeys as $key)
                  <tr>
                    <td><strong>{{ esc_specialchars($key->name) }}</strong></td>
                    <td>{{ esc_specialchars($key->username ?? 'Unknown') }}</td>
                    <td><code>{{ esc_specialchars($key->api_key_prefix) }}...</code></td>
                    <td>
                      @php
                      $raw = $key->scopes;
                      if (is_string($raw)) {
                          $raw = html_entity_decode($raw, ENT_QUOTES, "UTF-8");
                          $scopes = json_decode($raw, true);
                      } else {
                          $scopes = [];
                      }
                      @endphp
                      @if (!empty($scopes) && is_array($scopes))
                        @foreach ($scopes as $scope)
                          @php
                          $badgeClass = match($scope) {
                              "read" => "bg-success",
                              "write" => "bg-primary",
                              "delete" => "bg-danger",
                              default => "bg-secondary"
                          };
                          @endphp
                          <span class="badge {{ $badgeClass }}">{{ htmlspecialchars($scope) }}</span>
                        @endforeach
                      @else
                        <span class="text-muted">None</span>
                      @endif
                    </td>
                    <td>{{ number_format($key->rate_limit) }}/hr</td>
                    <td>
                      @if ($key->is_active)
                        <span class="badge bg-success">{{ __('Active') }}</span>
                      @else
                        <span class="badge bg-danger">{{ __('Inactive') }}</span>
                      @endif
                    </td>
                    <td>
                      @if ($key->last_used_at)
                        {{ date('Y-m-d H:i', strtotime($key->last_used_at)) }}
                      @else
                        <span class="text-muted">{{ __('Never') }}</span>
                      @endif
                    </td>
                    <td>
                      <form method="post" class="d-inline">
                        {!! $form->renderHiddenFields() !!}
                        <input type="hidden" name="action_type" value="toggle">
                        <input type="hidden" name="key_id" value="{{ $key->id }}">
                        <button type="submit" class="btn btn-sm btn-{{ $key->is_active ? 'warning' : 'success' }}">
                          <i class="bi bi-{{ $key->is_active ? 'pause-fill' : 'play-fill' }} me-1"></i>{{ $key->is_active ? __('Deactivate') : __('Activate') }}
                        </button>
                      </form>
                      <form method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this API key?') }}');">
                        {!! $form->renderHiddenFields() !!}
                        <input type="hidden" name="action_type" value="delete">
                        <input type="hidden" name="key_id" value="{{ $key->id }}">
                        <button type="submit" class="btn btn-sm btn-danger">
                          <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                        </button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-book me-2"></i>{{ __('API Usage') }}</h5>
      </div>
      <div class="card-body">
        <h6>{{ __('Scopes & Permissions') }}</h6>
        <div class="table-responsive mb-4">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>{{ __('Scope') }}</th>
                <th>{{ __('Permissions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="badge bg-success">read</span></td>
                <td>{{ __('View descriptions, authorities, repositories, taxonomies. Search records.') }}</td>
              </tr>
              <tr>
                <td><span class="badge bg-primary">write</span></td>
                <td>{{ __('Create new records and update existing records.') }}</td>
              </tr>
              <tr>
                <td><span class="badge bg-danger">delete</span></td>
                <td>{{ __('Delete records permanently.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h6>{{ __('Authentication') }}</h6>
        <p>{{ __('Include your API key in the request header:') }}</p>
        <pre class="bg-light p-3 rounded"><code>X-API-Key: your-api-key-here</code></pre>

        <h6 class="mt-4">{{ __('Example Request') }}</h6>
        <pre class="bg-light p-3 rounded"><code>curl -H "X-API-Key: your-api-key" https://{{ $_SERVER['HTTP_HOST'] }}/api/v2/descriptions</code></pre>

        <h6 class="mt-4">{{ __('Available Endpoints') }}</h6>
        <ul>
          <li><code>GET /api/v2/descriptions</code> - {{ __('List descriptions') }}</li>
          <li><code>GET /api/v2/descriptions/:slug</code> - {{ __('Get single description') }}</li>
          <li><code>GET /api/v2/authorities</code> - {{ __('List authority records') }}</li>
          <li><code>GET /api/v2/repositories</code> - {{ __('List repositories') }}</li>
          <li><code>GET /api/v2/taxonomies</code> - {{ __('List taxonomies') }}</li>
          <li><code>POST /api/v2/search</code> - {{ __('Search records') }}</li>
        </ul>
      </div>
    </div>

    <!-- Create Key Modal -->
    <div class="modal fade" id="createKeyModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post">
            {!! $form->renderHiddenFields() !!}
            <input type="hidden" name="action_type" value="create">

            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>{{ __('Create API Key') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">{{ __('Key Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="key_name" class="form-control" required placeholder="{{ __('e.g., Integration App') }}">
                <div class="form-text">{{ __('A descriptive name to identify this key.') }}</div>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('User') }} <span class="text-danger">*</span></label>
                <select name="user_id" class="form-select" required>
                  <option value="">{{ __('Select a user...') }}</option>
                  @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ esc_specialchars($user->username) }} ({{ esc_specialchars($user->email) }})</option>
                  @endforeach
                </select>
                <div class="form-text">{{ __('The API key will inherit this user\'s permissions.') }}</div>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('Scopes') }}</label>
                <div class="form-check">
                  <input type="checkbox" name="scopes[]" value="read" class="form-check-input" id="scopeRead" checked>
                  <label class="form-check-label" for="scopeRead">{{ __('Read') }} - {{ __('View records') }}</label>
                </div>
                <div class="form-check">
                  <input type="checkbox" name="scopes[]" value="write" class="form-check-input" id="scopeWrite">
                  <label class="form-check-label" for="scopeWrite">{{ __('Write') }} - {{ __('Create and update records') }}</label>
                </div>
                <div class="form-check">
                  <input type="checkbox" name="scopes[]" value="delete" class="form-check-input" id="scopeDelete">
                  <label class="form-check-label" for="scopeDelete">{{ __('Delete') }} - {{ __('Delete records') }}</label>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('Rate Limit (requests/hour)') }}</label>
                <input type="number" name="rate_limit" class="form-control" value="1000" min="100" max="100000">
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('Expires At') }}</label>
                <input type="datetime-local" name="expires_at" class="form-control">
                <div class="form-text">{{ __('Leave blank for no expiration.') }}</div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Create Key') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script {!! $csp_nonce !!}>
    function copyApiKey() {
      var input = document.getElementById('newApiKey');
      input.select();
      input.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(input.value);
      alert('API key copied to clipboard!');
    }
    </script>

  </div>
</div>
@endsection
