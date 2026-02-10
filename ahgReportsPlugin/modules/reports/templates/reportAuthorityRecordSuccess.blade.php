@extends('layouts.page')

@section('title')
  <h1>{!! __('Browse Authority Record/Actor Report') !!}</h1>
  <div style="margin-bottom: 1rem;">
    <a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" class="c-btn">
      <i class="fa fa-arrow-left"></i> {{ __("Back to Reports") }}
    </a>
  </div>
@endsection

@section('sidebar')
  <section class="sidebar-widget">
    <h4>{{ __('Filter options') }}</h4>
    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord']), ['method' => 'get']) !!}
      {!! $form->renderHiddenFields() !!}

      <div class="form-item">
        <label>{{ __('Date start') }}</label>
        {!! $form['dateStart']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Date end') }}</label>
        {!! $form['dateEnd']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Date of') }}</label>
        {!! $form['dateOf']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Entity type') }}</label>
        {!! $form['entityType']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Sort by') }}</label>
        {!! $form['sort']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Results per page') }}</label>
        {!! $form['limit']->render() !!}
      </div>

      <section>
        <input class="c-btn c-btn-submit" type="submit" value="{{ __('Search') }}"/>
      </section>

      <div style="margin-top: 1rem;">
        <button type="button" onclick="exportTableToCSV()" class="c-btn" style="width:100%;">
          <i class="fa fa-download"></i> {{ __('Export CSV') }}
        </button>
      </div>
    </form>
  </section>

  @if (isset($statistics))
  @php $stats = $statistics; @endphp
  <section class="sidebar-widget">
    <h4>{{ __('Statistics') }}</h4>
    <ul class="list-unstyled">
      <li><strong>{{ __('Total') }}:</strong> {{ $stats['total'] ?? 0 }}</li>
      @if (!empty($stats['by_type']))
        @foreach ($stats['by_type'] as $type => $count)
          <li><strong>{{ $type }}:</strong> {{ $count }}</li>
        @endforeach
      @endif
    </ul>
  </section>
  @endif
@endsection

@section('content')
@php
$rawResults = isset($results) ? $results : [];
$rawTotal = isset($total) ? $total : 0;
$rawCurrentPage = isset($currentPage) ? $currentPage : 1;
$rawLastPage = isset($lastPage) ? $lastPage : 1;
$rawHasNext = isset($hasNext) ? $hasNext : false;
$rawHasPrevious = isset($hasPrevious) ? $hasPrevious : false;
@endphp

<section>
  @if (!empty($rawResults))
    <div class="alert alert-info">
      {{ __('Showing %1% of %2% results (Page %3% of %4%)', [
        '%1%' => count($rawResults),
        '%2%' => $rawTotal,
        '%3%' => $rawCurrentPage,
        '%4%' => $rawLastPage
      ]) }}
    </div>

    <table id="reportTable" class="table table-striped sticky-enabled tablesorter">
      <thead>
        <tr>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Type') }}</th>
          <th>{{ __('Dates') }}</th>
          <th>{{ __('Created') }}</th>
          <th>{{ __('Updated') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rawResults as $result)
          <tr>
            <td>
              <a href="{{ url_for(['module' => 'actor', 'slug' => $result['slug'] ?? '']) }}">
                {{ $result['authorized_form_of_name'] ?? 'N/A' }}
              </a>
            </td>
            <td>{{ $result['entity_type_name'] ?? 'N/A' }}</td>
            <td>{{ $result['dates_of_existence'] ?? '' }}</td>
            <td>{{ isset($result['created_at']) ? date('Y-m-d', strtotime($result['created_at'])) : 'N/A' }}</td>
            <td>{{ isset($result['updated_at']) ? date('Y-m-d', strtotime($result['updated_at'])) : 'N/A' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @if ($rawLastPage > 1)
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center">
        @if ($rawHasPrevious)
          <li class="page-item">
            <a class="page-link" href="{{ url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $rawCurrentPage - 1] + sfOutputEscaper::unescape($sf_request->getGetParameters())) }}">
              {{ __('Previous') }}
            </a>
          </li>
        @endif

        @for ($p = max(1, $rawCurrentPage - 2); $p <= min($rawLastPage, $rawCurrentPage + 2); $p++)
          <li class="page-item {{ $p == $rawCurrentPage ? 'active' : '' }}">
            <a class="page-link" href="{{ url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $p] + sfOutputEscaper::unescape($sf_request->getGetParameters())) }}">
              {{ $p }}
            </a>
          </li>
        @endfor

        @if ($rawHasNext)
          <li class="page-item">
            <a class="page-link" href="{{ url_for(['module' => 'reports', 'action' => 'reportAuthorityRecord', 'page' => $rawCurrentPage + 1] + sfOutputEscaper::unescape($sf_request->getGetParameters())) }}">
              {{ __('Next') }}
            </a>
          </li>
        @endif
      </ul>
    </nav>
    @endif

  @else
    <div class="alert alert-warning">
      {{ __('No results found. Adjust your search criteria.') }}
    </div>
  @endif
</section>

<script {!! csp_nonce_attr() !!}>
function exportTableToCSV() {
  var table = document.getElementById('reportTable');
  if (!table) {
    alert('No data to export');
    return;
  }
  var csv = [];
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    var row = [], cols = rows[i].querySelectorAll('td, th');
    for (var j = 0; j < cols.length; j++) {
      var text = cols[j].innerText.replace(/"/g, '""');
      row.push('"' + text + '"');
    }
    csv.push(row.join(','));
  }
  var csvContent = csv.join('\n');
  var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'authority_report_{{ date('Y-m-d') }}.csv';
  link.click();
}
</script>
@endsection
