@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('AHG Central Integration') }}</h1>

    @if (isset($testResult))
      <div class="alert alert-{{ $testResult['success'] ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
        <strong>{{ $testResult['success'] ? __('Success!') : __('Error:') }}</strong>
        {{ $testResult['message'] }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-cloud me-2"></i>{{ __('About AHG Central') }}</h5>
      </div>
      <div class="card-body">
        <p class="mb-2">
          {{ __('AHG Central is a cloud service provided by The Archive and Heritage Group that enhances your AtoM instance with:') }}
        </p>
        <ul class="mb-3">
          <li><strong>{{ __('Shared NER Training') }}</strong> - {{ __('Contribute and benefit from a community-trained Named Entity Recognition model') }}</li>
          <li><strong>{{ __('Future AI Services') }}</strong> - {{ __('Access to upcoming cloud-based AI features') }}</li>
          <li><strong>{{ __('Usage Analytics') }}</strong> - {{ __('Optional aggregate statistics to improve the platform') }}</li>
        </ul>
        <p class="text-muted small mb-0">
          <i class="fas fa-info-circle me-1"></i>
          {{ __('Note: This is separate from local AI services configured in the AI Services settings. Local AI services run on your own infrastructure while AHG Central is a cloud service.') }}
        </p>
      </div>
    </div>

    <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'ahgIntegration']) }}">
      {!! $form->renderGlobalErrors() !!}
      {!! $form->renderHiddenFields() !!}

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-cog me-2"></i>{{ __('Connection Settings') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="ahg_central_enabled" class="form-label">{{ __('Enable AHG Central Integration') }}</label>
            <div>
              {!! $form['ahg_central_enabled']->render(['class' => 'form-check-input']) !!}
            </div>
            <div class="form-text">{{ $settings['ahg_central_enabled']['help'] }}</div>
          </div>

          <div class="mb-3">
            <label for="ahg_central_api_url" class="form-label">{{ __('AHG Central API URL') }}</label>
            {!! $form['ahg_central_api_url']->render(['class' => 'form-control']) !!}
            <div class="form-text">{{ $settings['ahg_central_api_url']['help'] }}</div>
          </div>

          <div class="mb-3">
            <label for="ahg_central_api_key" class="form-label">{{ __('API Key') }}</label>
            <div class="input-group">
              {!! $form['ahg_central_api_key']->render(['class' => 'form-control', 'id' => 'ahg_central_api_key']) !!}
              <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                <i class="fas fa-eye" id="toggleIcon"></i>
              </button>
            </div>
            <div class="form-text">{{ $settings['ahg_central_api_key']['help'] }}</div>
          </div>

          <div class="mb-3">
            <label for="ahg_central_site_id" class="form-label">{{ __('Site ID') }}</label>
            {!! $form['ahg_central_site_id']->render(['class' => 'form-control']) !!}
            <div class="form-text">{{ $settings['ahg_central_site_id']['help'] }}</div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-plug me-2"></i>{{ __('Test Connection') }}</h5>
        </div>
        <div class="card-body">
          <p class="mb-3">{{ __('Test the connection to AHG Central before saving your settings.') }}</p>
          <button type="submit" name="test_connection" value="1" class="btn btn-info">
            <i class="fas fa-plug me-1"></i> {{ __('Test Connection') }}
          </button>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>{{ __('Environment Variables (Legacy)') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-3">
            {{ __('Previously, AHG Central was configured via environment variables. Database settings (above) take precedence over environment variables.') }}
          </p>
          <table class="table table-sm">
            <thead>
              <tr>
                <th>{{ __('Variable') }}</th>
                <th>{{ __('Current Value') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @php
              $envVars = [
                  'NER_TRAINING_API_URL' => getenv('NER_TRAINING_API_URL'),
                  'NER_API_KEY' => getenv('NER_API_KEY') ? '********' : '',
                  'NER_SITE_ID' => getenv('NER_SITE_ID'),
              ];
              @endphp
              @foreach ($envVars as $name => $value)
              <tr>
                <td><code>{{ $name }}</code></td>
                <td>{!! $value ?: '<em class="text-muted">' . __('Not set') . '</em>' !!}</td>
                <td>
                  @if ($value)
                    <span class="badge bg-warning">{{ __('Will be overridden by database settings') }}</span>
                  @else
                    <span class="badge bg-secondary">{{ __('Not set') }}</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i> {{ __('Save Settings') }}
        </button>
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-secondary">
          {{ __('Cancel') }}
        </a>
      </div>
    </form>

    <script {!! $csp_nonce !!}>
    function togglePasswordVisibility() {
      const input = document.getElementById('ahg_central_api_key');
      const icon = document.getElementById('toggleIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
    </script>

  </div>
</div>
@endsection
