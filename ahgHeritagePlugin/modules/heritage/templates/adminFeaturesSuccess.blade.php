@extends('layouts.page')

@section('title')
<h1 class="h3">
    <i class="fas fa-toggle-on me-2"></i>Feature Toggles
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'features'])
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        <h5 class="mb-0">Platform Features</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Feature</th>
                        <th>Code</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($features as $feature)
                    <tr>
                        <td>
                            <strong>{{ $feature->feature_name ?? $feature->feature_code }}</strong>
                            @if (!empty($feature->config_json))
                            <br><small class="text-muted">Has configuration</small>
                            @endif
                        </td>
                        <td><code>{{ $feature->feature_code }}</code></td>
                        <td class="text-center">
                            @if ($feature->is_enabled)
                            <span class="badge bg-success">Enabled</span>
                            @else
                            <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="feature_code" value="{{ $feature->feature_code }}">
                                <input type="hidden" name="toggle_action" value="toggle">
                                <button type="submit" class="btn btn-sm btn-outline-{{ $feature->is_enabled ? 'secondary' : 'success' }}">
                                    {{ $feature->is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if (empty($features))
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No features configured.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    Feature toggles control platform functionality. Disabled features will not be available to users.
</div>
@endsection
