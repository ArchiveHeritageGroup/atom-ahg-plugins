@extends('layouts.page')

@section('content')
<div class="row">
  <div class="col-md-3">
    @include('_menu', ['nodes' => $menuNodes])
  </div>
  <div class="col-md-9">
    <h1>{{ __('Auto-Update') }}</h1>
    <p class="text-muted">
      {{ __('Keep this server current by pulling the latest framework and plugin code from GitHub on a schedule. Updates run via cron (or the CLI) — under this server\'s hardened php-fpm the web app cannot run git itself, so use the commands below to schedule or trigger a run.') }}
    </p>

    @php
      $cfg = $autoUpdateConfig ?? [];
      $state = $autoUpdateState ?? [];
      $enabled = ($cfg['AUTO_UPDATE_ENABLED'] ?? '1') === '1';
      $freq = $cfg['AUTO_UPDATE_FREQUENCY'] ?? 'daily';
      $email = $cfg['AUTO_UPDATE_NOTIFY_EMAIL'] ?? '';
      $result = $state['RESULT'] ?? null;
      $badge = ['ok' => 'success', 'warning' => 'warning', 'rolled-back' => 'danger'][$result] ?? 'secondary';
    @endphp

    {{-- Last run status --}}
    <div class="card mb-4">
      <div class="card-header"><strong>{{ __('Last run') }}</strong></div>
      <div class="card-body">
        @if (empty($state['LAST_RUN']))
          <p class="text-muted mb-0">{{ __('Auto-update has not run yet on this server.') }}</p>
        @else
          <dl class="row mb-0">
            <dt class="col-sm-3">{{ __('When') }}</dt>
            <dd class="col-sm-9">{{ $state['LAST_RUN'] }} <span class="text-muted">({{ $state['MODE'] ?? '' }})</span></dd>
            <dt class="col-sm-3">{{ __('Result') }}</dt>
            <dd class="col-sm-9"><span class="badge bg-{{ $badge }}">{{ $result ?? 'unknown' }}</span></dd>
            <dt class="col-sm-3">{{ __('Detail') }}</dt>
            <dd class="col-sm-9">{{ $state['MESSAGE'] ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('Framework') }}</dt>
            <dd class="col-sm-9"><code>{{ substr($state['FRAMEWORK_BEFORE'] ?? '', 0, 8) ?: '—' }} → {{ substr($state['FRAMEWORK_AFTER'] ?? '', 0, 8) ?: '—' }}</code></dd>
            <dt class="col-sm-3">{{ __('Plugins') }}</dt>
            <dd class="col-sm-9"><code>{{ substr($state['PLUGINS_BEFORE'] ?? '', 0, 8) ?: '—' }} → {{ substr($state['PLUGINS_AFTER'] ?? '', 0, 8) ?: '—' }}</code></dd>
          </dl>
        @endif
      </div>
    </div>

    {{-- Configuration --}}
    <form method="post" action="{{ url_for(['module' => 'ahgSettings', 'action' => 'autoUpdate']) }}">
      <div class="card mb-4">
        <div class="card-header"><strong>{{ __('Schedule &amp; notifications') }}</strong></div>
        <div class="card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="auto_update_enabled" name="auto_update_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
            <label class="form-check-label" for="auto_update_enabled">{{ __('Enable scheduled auto-update') }}</label>
            <div class="form-text">{{ __('When off, the cron job exits without pulling (no need to remove the cron entry).') }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="auto_update_frequency">{{ __('Frequency') }}</label>
            <select class="form-select" id="auto_update_frequency" name="auto_update_frequency" style="max-width: 16rem;">
              <option value="daily" {{ $freq === 'daily' ? 'selected' : '' }}>{{ __('Daily (02:00)') }}</option>
              <option value="weekly" {{ $freq === 'weekly' ? 'selected' : '' }}>{{ __('Weekly (Sunday 02:00)') }}</option>
            </select>
            <div class="form-text">{{ __('Used when you (re)install the cron entry with the command below.') }}</div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="auto_update_notify_email">{{ __('Notification email (optional)') }}</label>
            <input type="email" class="form-control" id="auto_update_notify_email" name="auto_update_notify_email" value="{{ $email }}" placeholder="ops@example.org" style="max-width: 24rem;">
            <div class="form-text">{{ __('Emailed on a successful update, a warning (skipped dirty repo), or a rollback. Requires a working mail command.') }}</div>
          </div>
        </div>
        <div class="card-footer">
          <input class="btn atom-btn-outline-success" type="submit" value="{{ __('Save') }}">
        </div>
      </div>
    </form>

    {{-- Cron + CLI --}}
    <div class="card mb-4">
      <div class="card-header"><strong>{{ __('Install the schedule &amp; run manually') }}</strong></div>
      <div class="card-body">
        <p>{{ __('Install the cron entry (run once as root). Re-run after changing the frequency above:') }}</p>
        <pre class="bg-light p-2 border rounded"><code>sudo bash {{ $frameworkPath }}/bin/auto-update --install-cron</code></pre>

        <p class="mb-1">{{ __('That installs:') }}</p>
        <pre class="bg-light p-2 border rounded"><code>{{ $cronLine }}</code></pre>

        <p class="mt-3">{{ __('Run on demand from a shell:') }}</p>
        <pre class="bg-light p-2 border rounded"><code>cd {{ $frameworkPath }}
bash bin/auto-update --check    # report available updates (no changes)
bash bin/auto-update            # pull, clear cache, validate (rolls back on failure)</code></pre>

        <p class="mt-3">{{ __('Remove the schedule:') }}</p>
        <pre class="bg-light p-2 border rounded"><code>sudo bash {{ $frameworkPath }}/bin/auto-update --uninstall-cron</code></pre>

        <p class="text-muted small mb-0">
          {{ __('Safety: a dirty git working tree (uncommitted local edits) is never overwritten — that repo is skipped with a warning. If a pull breaks the app, the repos are automatically reset to their previous commit.') }}
        </p>
      </div>
    </div>

    @unless ($confExists ?? false)
      <div class="alert alert-info">
        {{ __('Tip: save the settings above once to write the config file the cron job reads:') }} <code>{{ $confFile }}</code>
      </div>
    @endunless

    <p class="text-muted small">{{ __('Log file:') }} <code>{{ $logFile }}</code></p>
  </div>
</div>
@endsection
