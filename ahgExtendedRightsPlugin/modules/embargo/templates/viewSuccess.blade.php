@extends('layouts.page')

@section('sidebar')
  <div class="sidebar-widget">
    <h3>{{ __('Actions') }}</h3>
    <ul class="nav nav-pills nav-stacked">
      @if ($embargo['status'] === 'active')
        <li>
          <a href="#" data-bs-toggle="modal" data-bs-target="#liftEmbargoModal" class="text-success">
            <i class="fas fa-unlock"></i> {{ __('Lift Embargo') }}
          </a>
        </li>
      @endif
      <li>
        <a href="{{ url_for(['module' => 'embargo', 'action' => 'edit', 'id' => $embargo['id']]) }}">
          <i class="fas fa-edit"></i> {{ __('Edit') }}
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-widget">
    <h3>{{ __('Status') }}</h3>
    @php
    $statusColors = ['active' => 'danger', 'expired' => 'secondary', 'lifted' => 'success', 'pending' => 'warning'];
    $status = $embargo['status'];
    @endphp
    <span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} fs-6">
      {{ ucfirst($status) }}
    </span>
  </div>
@endsection

@section('title')
  <h1>{{ __('Embargo Details') }}</h1>
@endsection

@section('content')
<div class="card mb-4">
  <div class="card-header">
    <h4 class="mb-0">{{ __('Embargo Information') }}</h4>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <dl>
          <dt>{{ __('Object') }}</dt>
          <dd><a href="{{ url_for(['module' => 'informationobject', 'action' => 'browse', 'id' => $embargo['object_id']]) }}">#{{ $embargo['object_id'] }}</a></dd>

          <dt>{{ __('Type') }}</dt>
          <dd>{{ ucfirst(str_replace('_', ' ', $embargo['embargo_type'])) }}</dd>

          <dt>{{ __('Start Date') }}</dt>
          <dd>{{ $embargo['start_date'] }}</dd>

          <dt>{{ __('End Date') }}</dt>
          <dd>
            @if ($embargo['is_perpetual'])
              <span class="text-danger">{{ __('Perpetual') }}</span>
            @elseif ($embargo['end_date'])
              {{ $embargo['end_date'] }}
            @else
              -
            @endif
          </dd>
        </dl>
      </div>
      <div class="col-md-6">
        <dl>
          @if (!empty($embargo['translations'][0]['reason']))
            <dt>{{ __('Reason') }}</dt>
            <dd>{{ $embargo['translations'][0]['reason'] }}</dd>
          @endif

          @if (!empty($embargo['translations'][0]['public_message']))
            <dt>{{ __('Public Message') }}</dt>
            <dd>{{ $embargo['translations'][0]['public_message'] }}</dd>
          @endif

          @if (!empty($embargo['translations'][0]['notes']))
            <dt>{{ __('Internal Notes') }}</dt>
            <dd>{!! nl2br(e($embargo['translations'][0]['notes'])) !!}</dd>
          @endif
        </dl>
      </div>
    </div>

    @if ($embargo['status'] === 'lifted')
      <div class="alert alert-success">
        <strong>{{ __('This embargo was lifted') }}</strong>
        @if ($embargo['lifted_at'])
          {{ __('on %1%', ['%1%' => date('Y-m-d H:i', strtotime($embargo['lifted_at']))]) }}
        @endif
        @if ($embargo['lift_reason'])
          <br>{{ __('Reason: %1%', ['%1%' => $embargo['lift_reason']]) }}
        @endif
      </div>
    @endif
  </div>
</div>

<!-- Exceptions -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0">{{ __('Exceptions') }}</h4>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExceptionModal">
      <i class="fas fa-plus"></i> {{ __('Add Exception') }}
    </button>
  </div>
  <div class="card-body">
    @if (!empty($embargo['exceptions']))
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Details') }}</th>
            <th>{{ __('Valid Period') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($embargo['exceptions'] as $exception)
            <tr>
              <td>{{ ucfirst($exception['exception_type']) }}</td>
              <td>
                @if ($exception['exception_type'] === 'ip_range')
                  {{ $exception['ip_range_start'] }} - {{ $exception['ip_range_end'] }}
                @elseif ($exception['exception_id'])
                  #{{ $exception['exception_id'] }}
                @endif
              </td>
              <td>
                @if ($exception['valid_from'] || $exception['valid_until'])
                  {{ $exception['valid_from'] ?? '...' }} - {{ $exception['valid_until'] ?? '...' }}
                @else
                  {{ __('Always') }}
                @endif
              </td>
              <td>
                <form method="post" action="{{ url_for(['module' => 'embargo', 'action' => 'removeException', 'id' => $exception['id'], 'embargo_id' => $embargo['id']]) }}" style="display:inline;">
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure?') }}');">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="text-muted">{{ __('No exceptions defined.') }}</p>
    @endif
  </div>
</div>

<!-- Audit Log -->
<div class="card">
  <div class="card-header">
    <h4 class="mb-0">{{ __('Audit Log') }}</h4>
  </div>
  <div class="card-body">
    @if (!empty($embargo['audit_log']))
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Action') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('IP Address') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($embargo['audit_log'] as $log)
            <tr>
              <td>{{ date('Y-m-d H:i', strtotime($log['created_at'])) }}</td>
              <td>{{ ucfirst(str_replace('_', ' ', $log['action'])) }}</td>
              <td>{{ $log['user_id'] ? '#' . $log['user_id'] : '-' }}</td>
              <td>{{ $log['ip_address'] ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="text-muted">{{ __('No audit log entries.') }}</p>
    @endif
  </div>
</div>

<!-- Lift Embargo Modal -->
<div class="modal fade" id="liftEmbargoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="{{ url_for(['module' => 'embargo', 'action' => 'lift', 'id' => $embargo['id']]) }}">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Lift Embargo') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>{{ __('Are you sure you want to lift this embargo? This action will be logged.') }}</p>
          <div class="mb-3">
            <label for="lift_reason" class="form-label">{{ __('Reason for lifting') }}</label>
            <textarea name="lift_reason" id="lift_reason" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-success">{{ __('Lift Embargo') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Exception Modal -->
<div class="modal fade" id="addExceptionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="{{ url_for(['module' => 'embargo', 'action' => 'addException', 'embargo_id' => $embargo['id']]) }}">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Add Exception') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="exception_type" class="form-label">{{ __('Exception Type') }}</label>
            <select name="exception_type" id="exception_type" class="form-select" required>
              <option value="user">{{ __('User') }}</option>
              <option value="group">{{ __('Group') }}</option>
              <option value="ip_range">{{ __('IP Range') }}</option>
              <option value="repository">{{ __('Repository') }}</option>
            </select>
          </div>

          <div class="mb-3" id="exception_id_field">
            <label for="exception_id" class="form-label">{{ __('User/Group/Repository ID') }}</label>
            <input type="number" name="exception_id" id="exception_id" class="form-control">
          </div>

          <div id="ip_range_fields" style="display:none;">
            <div class="mb-3">
              <label for="ip_range_start" class="form-label">{{ __('IP Range Start') }}</label>
              <input type="text" name="ip_range_start" id="ip_range_start" class="form-control" placeholder="192.168.1.1">
            </div>
            <div class="mb-3">
              <label for="ip_range_end" class="form-label">{{ __('IP Range End') }}</label>
              <input type="text" name="ip_range_end" id="ip_range_end" class="form-control" placeholder="192.168.1.255">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="valid_from" class="form-label">{{ __('Valid From') }}</label>
              <input type="date" name="valid_from" id="valid_from" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label for="valid_until" class="form-label">{{ __('Valid Until') }}</label>
              <input type="date" name="valid_until" id="valid_until" class="form-control">
            </div>
          </div>

          <div class="mb-3">
            <label for="exception_notes" class="form-label">{{ __('Notes') }}</label>
            <textarea name="notes" id="exception_notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-primary">{{ __('Add Exception') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script {!! $csp_nonce !!}>
document.getElementById('exception_type').addEventListener('change', function() {
  var isIpRange = this.value === 'ip_range';
  document.getElementById('exception_id_field').style.display = isIpRange ? 'none' : 'block';
  document.getElementById('ip_range_fields').style.display = isIpRange ? 'block' : 'none';
});
</script>
@endsection
