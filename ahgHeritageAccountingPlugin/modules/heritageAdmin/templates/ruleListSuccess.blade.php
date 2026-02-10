@extends('layouts.page')

@section('title', __('Compliance Rules'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardList']) }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-clipboard-check me-2"></i>{{ __('Compliance Rules') }}</span>
        </div>
        <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleAdd', 'standard_id' => $standardId]) }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>{{ __('Add Rule') }}
        </a>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{!! $sf_user->getFlash('success') !!}</div>
    @endif

    <!-- Filter by Standard -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 me-2">{{ __('Filter by Standard:') }}</label>
                </div>
                <div class="col-md-4">
                    <select name="standard_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All Standards') }}</option>
                        @foreach($standards as $s)
                        <option value="{{ $s->id }}" {{ $standardId == $s->id ? 'selected' : '' }}>
                            {{ $s->code }} - {{ $s->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
                <thead class="table-light">
                    <tr>
                        <th width="80">{{ __('Standard') }}</th>
                        <th width="100">{{ __('Category') }}</th>
                        <th width="80">{{ __('Code') }}</th>
                        <th>{{ __('Rule Name') }}</th>
                        <th width="120">{{ __('Field') }}</th>
                        <th width="80">{{ __('Severity') }}</th>
                        <th width="60">{{ __('Status') }}</th>
                        <th width="120"></th>
                    </tr>
                </thead>
                <tbody>
                    @if($rules->isEmpty())
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No rules found') }}</td></tr>
                    @else
                    @php
                    $categoryColors = ['recognition' => 'primary', 'measurement' => 'success', 'disclosure' => 'info'];
                    $severityColors = ['error' => 'danger', 'warning' => 'warning', 'info' => 'secondary'];
                    @endphp
                    @foreach($rules as $r)
                    <tr class="{{ $r->is_active ? '' : 'table-secondary' }}">
                        <td><code>{{ $r->standard_code }}</code></td>
                        <td>
                            <span class="badge bg-{{ $categoryColors[$r->category] ?? 'secondary' }}">
                                {{ ucfirst($r->category) }}
                            </span>
                        </td>
                        <td><code>{{ $r->code }}</code></td>
                        <td>
                            <strong>{{ $r->name }}</strong>
                            @if($r->reference)
                            <br><small class="text-muted">{{ $r->reference }}</small>
                            @endif
                        </td>
                        <td><code class="text-muted">{{ $r->field_name }}</code></td>
                        <td>
                            <span class="badge bg-{{ $severityColors[$r->severity] ?? 'secondary' }}">
                                {{ ucfirst($r->severity) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($r->is_active)
                            <i class="fas fa-check-circle text-success"></i>
                            @else
                            <i class="fas fa-times-circle text-danger"></i>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleEdit', 'id' => $r->id]) }}"
                               class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleToggle', 'id' => $r->id]) }}"
                               class="btn btn-sm btn-outline-{{ $r->is_active ? 'warning' : 'success' }}">
                                <i class="fas fa-{{ $r->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleDelete', 'id' => $r->id]) }}"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('{{ __('Delete this rule?') }}')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('About Compliance Rules') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6>{{ __('Categories') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li><span class="badge bg-primary">Recognition</span> - Asset identification requirements</li>
                        <li><span class="badge bg-success">Measurement</span> - Valuation requirements</li>
                        <li><span class="badge bg-info">Disclosure</span> - Documentation requirements</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>{{ __('Severity Levels') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li><span class="badge bg-danger">Error</span> - Must fix for compliance</li>
                        <li><span class="badge bg-warning text-dark">Warning</span> - Should be addressed</li>
                        <li><span class="badge bg-secondary">Info</span> - Best practice recommendation</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>{{ __('Check Types') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li><code>required_field</code> - Field must have a value</li>
                        <li><code>value_check</code> - Numeric comparison (>0, >=1)</li>
                        <li><code>date_check</code> - Valid date required</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
