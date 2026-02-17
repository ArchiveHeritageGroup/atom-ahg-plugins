@extends('layouts.page')

@section('content')
<h1><i class="fas fa-envelope text-primary me-2"></i>{{ __('Email Settings') }}</h1>

<!-- TEMPLATE:BLADE_EMAIL -->
<form method="post">
  <div class="row">
    <div class="col-md-6">
      <!-- SMTP Settings -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-server me-2"></i>{{ __('SMTP Configuration') }}
        </div>
        <div class="card-body">
          @foreach ($smtpSettings as $setting)
            <div class="mb-3">
              <label class="form-label">
                {{ __(ucwords(str_replace('_', ' ', str_replace('smtp_', '', $setting->setting_key)))) }}
              </label>
              @if ($setting->setting_type === 'boolean')
                <select name="settings[{{ $setting->setting_key }}]" class="form-select">
                  <option value="0" {{ $setting->setting_value == '0' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                  <option value="1" {{ $setting->setting_value == '1' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                </select>
              @elseif ($setting->setting_type === 'password')
                <input type="password" name="settings[{{ $setting->setting_key }}]"
                       class="form-control" value="{{ e($setting->setting_value ?? '') }}"
                       placeholder="••••••••">
              @elseif ($setting->setting_type === 'number')
                <input type="number" name="settings[{{ $setting->setting_key }}]"
                       class="form-control" value="{{ e($setting->setting_value ?? '') }}">
              @else
                <input type="{{ $setting->setting_type === 'email' ? 'email' : 'text' }}"
                       name="settings[{{ $setting->setting_key }}]"
                       class="form-control" value="{{ e($setting->setting_value ?? '') }}">
              @endif
              @if ($setting->description)
                <small class="text-muted">{{ e($setting->description) }}</small>
              @endif
            </div>
          @endforeach
        </div>
      </div>

      <!-- Test Email -->
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-paper-plane me-2"></i>{{ __('Test Email') }}
        </div>
        <div class="card-body">
          <p class="small text-muted">{{ __('Save settings first, then send a test email to verify configuration.') }}</p>
          <div class="input-group">
            <input type="email" name="test_email" class="form-control" placeholder="test@example.com" id="testEmailInput">
            <button type="button" class="btn btn-outline-primary" id="btnSendTest">
              <i class="fas fa-paper-plane me-1"></i>{{ __('Send Test') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Notification Recipients -->
      <div class="card mb-4">
        <div class="card-header bg-info text-white">
          <i class="fas fa-bell me-2"></i>{{ __('Notification Recipients') }}
        </div>
        <div class="card-body">
          @foreach ($notificationSettings as $setting)
            <div class="mb-3">
              <label class="form-label">
                {{ __(ucwords(str_replace('_', ' ', str_replace('notify_', '', $setting->setting_key)))) }}
              </label>
              <input type="email" name="settings[{{ $setting->setting_key }}]"
                     class="form-control" value="{{ e($setting->setting_value ?? '') }}"
                     placeholder="admin@example.com">
              @if ($setting->description)
                <small class="text-muted">{{ e($setting->description) }}</small>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <!-- Email Templates -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <i class="fas fa-file-alt me-2"></i>{{ __('Email Templates') }}
        </div>
        <div class="card-body">
          <div class="alert alert-info small">
            <strong>{{ __('Available placeholders:') }}</strong><br>
            <code>{name}</code> - {{ __('Recipient name') }}<br>
            <code>{email}</code> - {{ __('Recipient email') }}<br>
            <code>{institution}</code> - {{ __('Institution name') }}<br>
            <code>{login_url}</code> - {{ __('Login page URL') }}<br>
            <code>{reset_url}</code> - {{ __('Password reset URL') }}<br>
            <code>{review_url}</code> - {{ __('Review page URL') }}<br>
            <code>{date}</code>, <code>{time}</code>, <code>{room}</code> - {{ __('Booking details') }}<br>
            <code>{reason}</code> - {{ __('Rejection reason') }}<br>
            <code>{message}</code>, <code>{file}</code>, <code>{line}</code>, <code>{trace}</code> - {{ __('Error alert details') }}<br>
            <code>{hostname}</code>, <code>{url}</code>, <code>{timestamp}</code> - {{ __('Error context') }}
          </div>

          @php
          $templateGroups = [
              'researcher_pending' => 'New Registration (to Researcher)',
              'researcher_approved' => 'Approval (to Researcher)',
              'researcher_rejected' => 'Rejection (to Researcher)',
              'password_reset' => 'Password Reset (to User)',
              'booking_confirmed' => 'Booking Confirmed (to Researcher)',
              'admin_new_researcher' => 'New Registration (to Admin)',
              'error_alert' => 'System Error Alert (to Admin)',
          ];
          $index = 0;
          @endphp

          <div class="accordion" id="templateAccordion">
            @foreach ($templateGroups as $templateKey => $templateLabel)
              @php
                $subjectKey = 'email_' . $templateKey . '_subject';
                $bodyKey = 'email_' . $templateKey . '_body';
                $subjectSetting = null;
                $bodySetting = null;
                foreach ($templateSettings as $ts) {
                    if ($ts->setting_key === $subjectKey) $subjectSetting = $ts;
                    if ($ts->setting_key === $bodyKey) $bodySetting = $ts;
                }
                if (!$subjectSetting || !$bodySetting) continue;
                $index++;
              @endphp
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $index }}">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                          data-bs-target="#collapse{{ $index }}" aria-expanded="false">
                    {{ __($templateLabel) }}
                  </button>
                </h2>
                <div id="collapse{{ $index }}" class="accordion-collapse collapse"
                     aria-labelledby="heading{{ $index }}" data-bs-parent="#templateAccordion">
                  <div class="accordion-body">
                    <div class="mb-3">
                      <label class="form-label">{{ __('Subject') }}</label>
                      <input type="text" name="settings[{{ $subjectKey }}]"
                             class="form-control" value="{{ e($subjectSetting->setting_value ?? '') }}">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">{{ __('Body') }}</label>
                      <textarea name="settings[{{ $bodyKey }}]" class="form-control"
                                rows="6">{{ e($bodySetting->setting_value ?? '') }}</textarea>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Settings -->
  <div class="card mb-4">
    <div class="card-header bg-dark text-white">
      <i class="fas fa-bell me-2"></i>{{ __('Notification Settings') }}
    </div>
    <ul class="list-group list-group-flush">
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-layer-group me-2 text-muted"></i>{{ __('Spectrum Email Notifications') }}
          <br><small class="text-muted">{{ __('Task assignments and state transitions') }}</small>
        </div>
        <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'section', 'section' => 'spectrum']) }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-book-reader me-2 text-muted"></i>{{ __('Research Notifications') }}
          <br><small class="text-muted">{{ __('Researcher registration, approval, booking emails') }}</small>
        </div>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="research_email_notifications" name="notif_toggles[research_email_notifications]" value="1" {{ (isset($notifToggles) && (($notifToggles['research_email_notifications'] ?? 'true') === 'true' || ($notifToggles['research_email_notifications'] ?? '1') === '1')) || !isset($notifToggles) ? 'checked' : '' }}>
          <label class="form-check-label" for="research_email_notifications">{{ __('Enabled') }}</label>
        </div>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-shield-alt me-2 text-muted"></i>{{ __('Access Request Notifications') }}
          <br><small class="text-muted">{{ __('Approver notifications, request status emails') }}</small>
        </div>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="access_request_email_notifications" name="notif_toggles[access_request_email_notifications]" value="1" {{ (isset($notifToggles) && (($notifToggles['access_request_email_notifications'] ?? 'true') === 'true' || ($notifToggles['access_request_email_notifications'] ?? '1') === '1')) || !isset($notifToggles) ? 'checked' : '' }}>
          <label class="form-check-label" for="access_request_email_notifications">{{ __('Enabled') }}</label>
        </div>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-project-diagram me-2 text-muted"></i>{{ __('Workflow Notifications') }}
          <br><small class="text-muted">{{ __('Task assignment, approval, rejection emails') }}</small>
        </div>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="workflow_email_notifications" name="notif_toggles[workflow_email_notifications]" value="1" {{ (isset($notifToggles) && (($notifToggles['workflow_email_notifications'] ?? 'true') === 'true' || ($notifToggles['workflow_email_notifications'] ?? '1') === '1')) || !isset($notifToggles) ? 'checked' : '' }}>
          <label class="form-check-label" for="workflow_email_notifications">{{ __('Enabled') }}</label>
        </div>
      </li>
    </ul>
  </div>

  <hr>

  <div class="d-flex justify-content-between">
    <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Settings') }}
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i>{{ __('Save Settings') }}
    </button>
  </div>
</form>

<script {!! $csp_nonce !!}>
document.getElementById('btnSendTest').addEventListener('click', function() {
    var email = document.getElementById('testEmailInput').value;
    if (email) {
        window.location.href = '{{ url_for(['module' => 'ahgSettings', 'action' => 'emailTest']) }}?email=' + encodeURIComponent(email);
    } else {
        alert('Please enter an email address');
    }
});
</script>
@endsection
