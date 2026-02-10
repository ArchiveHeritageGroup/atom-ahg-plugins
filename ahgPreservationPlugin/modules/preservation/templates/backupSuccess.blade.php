@extends('layouts.page')

@section('title')
<h1><i class="bi bi-cloud-arrow-up text-info me-2"></i>{{ __('Backup & Replication') }}</h1>
@endsection

@section('content')

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Dashboard') }}
        </a>
    </div>
</div>

<!-- Verification Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Verified') }}</h6>
                        <h2 class="mb-0">{{ number_format($verificationStats['passed'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-patch-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Failed') }}</h6>
                        <h2 class="mb-0">{{ number_format($verificationStats['failed'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-x-octagon fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Warnings') }}</h6>
                        <h2 class="mb-0">{{ number_format($verificationStats['warning'] ?? 0) }}</h2>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ __('Replication Targets') }}</h6>
                        <h2 class="mb-0">{{ count($replicationTargets ?? []) }}</h2>
                    </div>
                    <i class="bi bi-hdd-network fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Replication Targets -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-hdd-network me-2"></i>{{ __('Replication Targets') }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Path') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($replicationTargets))
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                {{ __('No replication targets configured') }}
                            </td>
                        </tr>
                        @else
                            @foreach($replicationTargets as $target)
                            <tr>
                                <td>{{ $target->name }}</td>
                                <td><span class="badge bg-secondary">{{ $target->target_type }}</span></td>
                                <td>
                                    @if($target->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ substr($target->target_path, 0, 30) }}</small></td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Replications -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-arrow-repeat me-2"></i>{{ __('Recent Replications') }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Target') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Files') }}</th>
                            <th>{{ __('Started') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($recentReplications))
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">
                                {{ __('No replication logs yet') }}
                            </td>
                        </tr>
                        @else
                            @foreach($recentReplications as $rep)
                            <tr>
                                <td>{{ $rep->target_name ?? 'Unknown' }}</td>
                                <td>
                                    @if($rep->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($rep->status === 'running')
                                        <span class="badge bg-info">Running</span>
                                    @elseif($rep->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($rep->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ number_format($rep->files_synced ?? 0) }}</td>
                                <td><small class="text-muted">{{ date('Y-m-d H:i', strtotime($rep->started_at)) }}</small></td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- CLI Commands -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-terminal me-2"></i>{{ __('CLI Commands') }}
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Backup Verification</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># Show verification status
php symfony preservation:verify-backup --status

# Verify all backups in directory
php symfony preservation:verify-backup --backup-dir=/var/backups/atom

# Verify specific backup file
php symfony preservation:verify-backup --path=/backup.tar.gz</code></pre>
            </div>
            <div class="col-md-6">
                <h6>Replication</h6>
                <pre class="bg-dark text-light p-3 rounded"><code># List replication targets
php symfony preservation:replicate --list

# Sync all active targets
php symfony preservation:replicate

# Sync specific target
php symfony preservation:replicate --target=offsite

# Preview sync (dry run)
php symfony preservation:replicate --dry-run</code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Recent Verifications Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i>{{ __('Recent Backup Verifications') }}
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Backup') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Size') }}</th>
                    <th>{{ __('Verified') }}</th>
                    <th>{{ __('By') }}</th>
                </tr>
            </thead>
            <tbody>
                @if(empty($recentVerifications))
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        {{ __('No backup verifications performed yet') }}
                    </td>
                </tr>
                @else
                    @foreach($recentVerifications as $v)
                    <tr>
                        <td>
                            <small>{{ basename($v->backup_path) }}</small>
                        </td>
                        <td><span class="badge bg-secondary">{{ $v->backup_type ?? 'full' }}</span></td>
                        <td>
                            @if($v->status === 'passed')
                                <span class="badge bg-success">Passed</span>
                            @elseif($v->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @elseif($v->status === 'warning')
                                <span class="badge bg-warning">Warning</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($v->status) }}</span>
                            @endif
                        </td>
                        <td><small>{{ number_format($v->file_size ?? 0) }} bytes</small></td>
                        <td><small class="text-muted">{{ date('Y-m-d H:i', strtotime($v->verified_at)) }}</small></td>
                        <td><small>{{ $v->verified_by ?? 'system' }}</small></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
