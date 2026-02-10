@extends('layouts.page')

@section('title', __('Accounting Standards'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-balance-scale me-2"></i>{{ __('Accounting Standards') }}</h1>
        <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardAdd']) }}" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>{{ __('Add Standard') }}
        </a>
    </div>

    @if($sf_user->hasFlash('success'))
    <div class="alert alert-success">{!! $sf_user->getFlash('success') !!}</div>
    @endif
    @if($sf_user->hasFlash('error'))
    <div class="alert alert-danger">{!! $sf_user->getFlash('error') !!}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">{{ __('Order') }}</th>
                        <th width="100">{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Country/Region') }}</th>
                        <th width="80">{{ __('Capital.') }}</th>
                        <th width="80">{{ __('Status') }}</th>
                        <th width="150"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($standards as $s)
                    <tr class="{{ $s->is_active ? '' : 'table-secondary' }}">
                        <td>{{ $s->sort_order }}</td>
                        <td><code>{{ $s->code }}</code></td>
                        <td>
                            <strong>{{ $s->name }}</strong>
                            @if($s->description)
                            <br><small class="text-muted">{!! truncate_text($s->description, 80) !!}</small>
                            @endif
                        </td>
                        <td>{{ $s->country }}</td>
                        <td class="text-center">
                            @if($s->capitalisation_required)
                            <span class="badge bg-warning text-dark">{{ __('Required') }}</span>
                            @else
                            <span class="badge bg-secondary">{{ __('Optional') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($s->is_active)
                            <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                            <span class="badge bg-danger">{{ __('Disabled') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'ruleList', 'standard_id' => $s->id]) }}"
                               class="btn btn-sm btn-outline-info" title="{{ __('Rules') }}">
                                <i class="fas fa-clipboard-check"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardEdit', 'id' => $s->id]) }}"
                               class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardToggle', 'id' => $s->id]) }}"
                               class="btn btn-sm btn-outline-{{ $s->is_active ? 'warning' : 'success' }}"
                               title="{{ $s->is_active ? __('Disable') : __('Enable') }}">
                                <i class="fas fa-{{ $s->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                            </a>
                            <a href="{{ url_for(['module' => 'heritageAdmin', 'action' => 'standardDelete', 'id' => $s->id]) }}"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('{{ __('Delete this standard?') }}')"
                               title="{{ __('Delete') }}">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>{{ __('About Accounting Standards') }}</h5>
        </div>
        <div class="card-body">
            <p>{{ __('Heritage accounting standards define how cultural and heritage assets should be recognized, measured, and disclosed in financial statements.') }}</p>
            <ul class="mb-0">
                <li><strong>GRAP 103</strong> - South Africa (most comprehensive heritage-specific standard)</li>
                <li><strong>FRS 102</strong> - United Kingdom heritage assets section</li>
                <li><strong>GASB 34</strong> - US Government entities</li>
                <li><strong>FASB 958</strong> - US Non-profit organizations</li>
                <li><strong>PSAS 3150</strong> - Canada public sector</li>
                <li><strong>IPSAS 45</strong> - International (Africa, Asia, etc.)</li>
            </ul>
        </div>
    </div>

<hr>
<div class="d-flex justify-content-start">
    <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'settings']) }}" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Settings') }}
    </a>
</div>
</div>
@endsection
