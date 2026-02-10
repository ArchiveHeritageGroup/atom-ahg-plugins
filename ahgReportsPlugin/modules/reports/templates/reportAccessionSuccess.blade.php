@extends('layouts.page')

@section('title')
  <h1>{{ __('Browse Accession Report') }}</h1>
@endsection

@section('sidebar')

  <section class="sidebar-widget">

    <div style="margin-bottom: 1rem;">
      <a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" class="c-btn" style="width:100%;">
        <i class="fa fa-arrow-left"></i> {{ __('Back to Reports') }}
      </a>
    </div>

    <h4>{{ __('Filter options') }}</h4>

    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportAccession']), ['method' => 'get']) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="form-item">
        <label>{{ __('Culture') }}</label>
        {!! $form['culture']->render() !!}
      </div>

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

@endsection

@section('content')

  @if (isset($results) && count($results) > 0)

    <div class="alert alert-info">
      {{ __('Found %1% results', ['%1%' => $total]) }}
    </div>

    <div style="margin-bottom: 1rem; font-size: 0.85rem;">
      <strong>{{ __('Show/Hide Columns') }}:</strong><br/>
      <label><input type="checkbox" onclick="toggleColumn(0)" checked> {{ __('Identifier') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> {{ __('Title') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> {{ __('Accession Date') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> {{ __('Acquisition Type') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> {{ __('Resource Type') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(5)" checked> {{ __('Processing Status') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(6)" checked> {{ __('Culture') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(7)" checked> {{ __('Created') }}</label>
    </div>

    <script {!! $csp_nonce !!}>
    function toggleColumn(colNum) {
      var table = document.getElementById('reportTable');
      var rows = table.getElementsByTagName('tr');

      for (var i = 0; i < rows.length; i++) {
        var cell = rows[i].cells[colNum];
        if (cell) {
          if (cell.style.display === 'none') {
            cell.style.display = '';
          } else {
            cell.style.display = 'none';
          }
        }
      }
    }

    function exportTableToCSV() {
      var table = document.getElementById('reportTable');
      var csv = [];
      var rows = table.querySelectorAll('tr');

      for (var i = 0; i < rows.length; i++) {
        var row = [];
        var cols = rows[i].querySelectorAll('td, th');

        for (var j = 0; j < cols.length; j++) {
          if (cols[j].style.display !== 'none') {
            var text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
          }
        }
        csv.push(row.join(','));
      }

      var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
      var downloadLink = document.createElement('a');
      downloadLink.download = 'accession_report_' + new Date().getTime() + '.csv';
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = 'none';
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
    </script>

    <div class="table-responsive" style="max-height: 600px; overflow: auto;">
      <table id="reportTable" class="table table-bordered table-striped table-sm">
        <thead>
          <tr>
            <th>{{ __('Identifier') }}</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Accession Date') }}</th>
            <th>{{ __('Acquisition Type') }}</th>
            <th>{{ __('Resource Type') }}</th>
            <th>{{ __('Processing Status') }}</th>
            <th>{{ __('Culture') }}</th>
            <th>{{ __('Created') }}</th>
          </tr>
        </thead>
        <tbody>
          @php
          $termService = new \AtomExtensions\Services\TermService('en');
          @endphp
          @foreach ($results as $item)
            <tr>
              <td>@if(isset($item->identifier))<a href="{{ url_for(['module' => 'accession', 'slug' => $item->id]) }}">{{ $item->identifier }}</a>@else - @endif</td>
              <td>{{ $item->title ?? '-' }}</td>
              <td>{{ $item->accessionDate ?? '-' }}</td>
              <td>{{ isset($item->acquisitionTypeId) ? $termService->getTermName($item->acquisitionTypeId) : '-' }}</td>
              <td>{{ isset($item->resourceTypeId) ? $termService->getTermName($item->resourceTypeId) : '-' }}</td>
              <td>{{ isset($item->processingStatusId) ? $termService->getTermName($item->processingStatusId) : '-' }}</td>
              <td>{{ $item->culture ?? '-' }}</td>
              <td>{{ $item->createdAt ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  @else
    <div class="alert alert-warning">
      {{ __('No results found.') }}
    </div>
  @endif

@endsection
