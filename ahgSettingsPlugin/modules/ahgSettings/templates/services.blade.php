@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>
      <i class="bi bi-activity me-2"></i>
      {{ __('Services Monitor') }}
    </h1>

    @php
      $services = $sf_data->getRaw('services');
      $overallStatus = $sf_data->getRaw('overallStatus');
      $notificationSettings = $sf_data->getRaw('notificationSettings');
      $serviceHistory = $sf_data->getRaw('serviceHistory');
    @endphp

    <style {!! $csp_nonce !!}>
      .service-card {
        transition: all 0.3s ease;
      }
      .service-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      }
      .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
      }
      .status-ok { background-color: #198754; }
      .status-warning { background-color: #ffc107; }
      .status-error { background-color: #dc3545; animation: pulse 1.5s infinite; }
      .status-unknown { background-color: #6c757d; }

      @@keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
      }

      .response-time {
        font-size: 0.75rem;
        color: #6c757d;
      }

      .overall-status {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
      }
      .overall-ok { background-color: #d1e7dd; border: 1px solid #badbcc; }
      .overall-warning { background-color: #fff3cd; border: 1px solid #ffecb5; }
      .overall-error { background-color: #f8d7da; border: 1px solid #f5c2c7; }

      .category-title {
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
      }

      .history-item {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
      }
      .history-item:last-child {
        border-bottom: none;
      }
    </style>

    <!-- Overall Status -->
    <div class="overall-status overall-{{ $overallStatus }}">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="status-indicator status-{{ $overallStatus }}"></span>
          <strong>
            @if ($overallStatus === 'ok')
              {{ __('All Systems Operational') }}
            @elseif ($overallStatus === 'warning')
              {{ __('Some Services Have Warnings') }}
            @else
              {{ __('Service Issues Detected') }}
            @endif
          </strong>
        </div>
        <div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-btn">
            <i class="bi bi-arrow-clockwise me-1"></i>
            {{ __('Refresh') }}
          </button>
          <small class="text-muted ms-2">
            {{ __('Last checked:') }} <span id="last-check">{{ date('H:i:s') }}</span>
          </small>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Services Grid -->
      <div class="col-lg-8">

        @php
        $categories = [
            'core' => __('Core Services'),
            'optional' => __('Optional Services'),
            'plugin' => __('Plugin Services'),
            'system' => __('System'),
        ];
        @endphp

        @foreach ($categories as $catKey => $catTitle)
          @php
            $catServices = array_filter($services, fn($s) => ($s['category'] ?? 'core') === $catKey);
          @endphp
          @if (!empty($catServices))
            <h6 class="category-title">{{ $catTitle }}</h6>
            <div class="row mb-4">
              @foreach ($catServices as $key => $service)
                <div class="col-md-6 mb-3">
                  <div class="card service-card h-100" data-service="{{ $key }}">
                    <div class="card-body">
                      <div class="d-flex align-items-start">
                        <div class="me-3">
                          <i class="bi {{ $service['icon'] }} fs-4 text-{{ $service['status'] === 'ok' ? 'success' : ($service['status'] === 'warning' ? 'warning' : ($service['status'] === 'error' ? 'danger' : 'secondary')) }}"></i>
                        </div>
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center mb-1">
                            <span class="status-indicator status-{{ $service['status'] }}"></span>
                            <strong>{{ $service['name'] }}</strong>
                          </div>
                          <p class="mb-1 small text-muted">{{ $service['message'] }}</p>
                          @if ($service['response_time'] !== null)
                            <span class="response-time">
                              <i class="bi bi-stopwatch me-1"></i>
                              {{ $service['response_time'] }}ms
                            </span>
                          @endif
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        @endforeach

      </div>

      <!-- Settings & History Sidebar -->
      <div class="col-lg-4">

        <!-- Notification Settings -->
        <div class="card mb-4">
          <div class="card-header">
            <i class="bi bi-bell me-2"></i>
            {{ __('Notification Settings') }}
          </div>
          <div class="card-body">
            <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'services']) }}">

              <div class="mb-3">
                <label class="form-label">{{ __('Enable notifications') }}</label>
                <div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notification_enabled" id="notify_no" value="0" {{ ($notificationSettings['enabled'] ?? '0') !== '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="notify_no">{{ __('No') }}</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notification_enabled" id="notify_yes" value="1" {{ ($notificationSettings['enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="notify_yes">{{ __('Yes') }}</label>
                  </div>
                </div>
                <small class="form-text text-muted">{{ __('Send email when service status changes') }}</small>
              </div>

              <div class="mb-3">
                <label class="form-label" for="notification_email">{{ __('Notification email') }}</label>
                <input type="email" class="form-control" id="notification_email" name="notification_email"
                       value="{{ htmlspecialchars($notificationSettings['email'] ?? '') }}"
                       placeholder="admin@example.com">
              </div>

              <div class="mb-3">
                <label class="form-label" for="check_interval">{{ __('Check interval') }}</label>
                <select class="form-select" id="check_interval" name="check_interval" style="min-width: 200px; padding: 10px 15px; font-size: 1rem;">
                  @php
                  $intervals = [
                      '1' => __('1 minute'),
                      '5' => __('5 minutes'),
                      '15' => __('15 minutes'),
                      '30' => __('30 minutes'),
                      '60' => __('1 hour'),
                  ];
                  $currentInterval = $notificationSettings['check_interval'] ?? '5';
                  @endphp
                  @foreach ($intervals as $val => $label)
                    <option value="{{ $val }}" {{ $currentInterval === $val ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
                <small class="form-text text-muted">{{ __('How often to check services (for cron job)') }}</small>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('Notify on warnings') }}</label>
                <div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notify_on_warning" id="warn_no" value="0" {{ ($notificationSettings['notify_on_warning'] ?? '1') !== '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="warn_no">{{ __('No') }}</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notify_on_warning" id="warn_yes" value="1" {{ ($notificationSettings['notify_on_warning'] ?? '1') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="warn_yes">{{ __('Yes') }}</label>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">{{ __('Notify on recovery') }}</label>
                <div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notify_on_recovery" id="recover_no" value="0" {{ ($notificationSettings['notify_on_recovery'] ?? '1') !== '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="recover_no">{{ __('No') }}</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="notify_on_recovery" id="recover_yes" value="1" {{ ($notificationSettings['notify_on_recovery'] ?? '1') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="recover_yes">{{ __('Yes') }}</label>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" name="save_settings" value="1" class="btn btn-primary">
                  <i class="bi bi-check me-1"></i>
                  {{ __('Save Settings') }}
                </button>
                <button type="submit" name="test_notification" value="1" class="btn btn-outline-secondary">
                  <i class="bi bi-send me-1"></i>
                  {{ __('Send Test') }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Recent Events -->
        <div class="card">
          <div class="card-header">
            <i class="bi bi-clock-history me-2"></i>
            {{ __('Recent Events') }}
          </div>
          <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            @if (empty($serviceHistory))
              <p class="text-muted mb-0">{{ __('No events recorded yet.') }}</p>
            @else
              @foreach ($serviceHistory as $event)
                <div class="history-item">
                  <div class="d-flex align-items-center">
                    @php
                    $eventIcon = match ($event->event_type ?? '') {
                        'down' => '<span class="badge bg-danger">DOWN</span>',
                        'warning' => '<span class="badge bg-warning text-dark">WARN</span>',
                        'recovered' => '<span class="badge bg-success">OK</span>',
                        default => '<span class="badge bg-secondary">INFO</span>',
                    };
                    @endphp
                    {!! $eventIcon !!}
                    <span class="ms-2 small">{{ htmlspecialchars($event->service_name ?? '') }}</span>
                  </div>
                  <small class="text-muted d-block">
                    {{ htmlspecialchars($event->message ?? '') }}
                  </small>
                  <small class="text-muted">
                    {{ $event->created_at ?? '' }}
                  </small>
                </div>
              @endforeach
            @endif
          </div>
        </div>

      </div>
    </div>

    <!-- Cron Setup Info -->
    <div class="card mt-4">
      <div class="card-header">
        <i class="bi bi-terminal me-2"></i>
        {{ __('Automated Monitoring Setup') }}
      </div>
      <div class="card-body">
        <p class="mb-2">{{ __('To enable automated service monitoring with notifications, add this cron job:') }}</p>
        <pre class="bg-dark text-light p-3 rounded"><code>*/{{ $notificationSettings['check_interval'] ?? 5 }} * * * * curl -s "{{ url_for(['module' => 'ahgSettings', 'action' => 'services', 'check' => '1'], true) }}" > /dev/null</code></pre>
        <small class="text-muted">
          {{ __('This will check all services every %minutes% minutes and send notifications if configured.', ['%minutes%' => $notificationSettings['check_interval'] ?? 5]) }}
        </small>
      </div>
    </div>

    <script {!! $csp_nonce !!}>
    document.addEventListener('DOMContentLoaded', function() {
      const refreshBtn = document.getElementById('refresh-btn');

      refreshBtn.addEventListener('click', function() {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> {{ __('Checking...') }}';

        fetch('{{ url_for(['module' => 'ahgSettings', 'action' => 'services']) }}?check=1', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
          // Update last check time
          document.getElementById('last-check').textContent = data.timestamp.split(' ')[1];

          // Update service cards
          Object.entries(data.services).forEach(([key, service]) => {
            const card = document.querySelector(`[data-service="${key}"]`);
            if (card) {
              const indicator = card.querySelector('.status-indicator');
              indicator.className = `status-indicator status-${service.status}`;

              const message = card.querySelector('.text-muted');
              if (message) message.textContent = service.message;

              const responseTime = card.querySelector('.response-time');
              if (responseTime && service.response_time !== null) {
                responseTime.innerHTML = `<i class="bi bi-stopwatch me-1"></i>${service.response_time}ms`;
              }
            }
          });

          refreshBtn.disabled = false;
          refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> {{ __('Refresh') }}';
        })
        .catch(error => {
          console.error('Error:', error);
          refreshBtn.disabled = false;
          refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> {{ __('Refresh') }}';
        });
      });
    });
    </script>

  </div>
</div>
@endsection
