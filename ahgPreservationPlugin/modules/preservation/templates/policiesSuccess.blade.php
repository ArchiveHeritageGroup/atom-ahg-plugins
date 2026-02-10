@extends('layouts.page')

@section('title')
<h1><i class="bi bi-gear text-primary me-2"></i>{{ __('Preservation Policies') }}</h1>
@endsection

@section('content')

<div class="d-flex justify-content-between mb-4">
    <p class="text-muted mb-0">Automated preservation policies and schedules.</p>
    <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Dashboard') }}
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Policy') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Schedule') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Last Run') }}</th>
                    <th>{{ __('Next Run') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($policies as $policy)
                <tr>
                    <td>
                        <strong>{{ $policy->name }}</strong>
                        @if($policy->description)
                        <br><small class="text-muted">{{ $policy->description }}</small>
                        @endif
                    </td>
                    <td><span class="badge bg-secondary">{{ ucfirst($policy->policy_type) }}</span></td>
                    <td><code>{{ $policy->schedule_cron ?? 'Manual' }}</code></td>
                    <td>
                        @if($policy->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $policy->last_run_at ? date('Y-m-d H:i', strtotime($policy->last_run_at)) : '-' }}</td>
                    <td>{{ $policy->next_run_at ? date('Y-m-d H:i', strtotime($policy->next_run_at)) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i>{{ __('CLI Commands') }}
    </div>
    <div class="card-body">
        <p>Run fixity checks from command line:</p>
        <pre class="bg-dark text-light p-3 rounded">
# Check 100 objects not verified in 7+ days
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php

# Check all objects with verbose output
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php --all --verbose

# Custom limits
php plugins/ahgPreservationPlugin/bin/run-fixity-check.php --limit=500 --min-age=30</pre>

        <p class="mt-3">Add to crontab for scheduled runs:</p>
        <pre class="bg-dark text-light p-3 rounded">
# Daily fixity check at 2am
0 2 * * * cd {{ sfConfig::get('sf_root_dir') }} && php plugins/ahgPreservationPlugin/bin/run-fixity-check.php >> /var/log/fixity.log 2>&1</pre>
    </div>
</div>

@endsection
