@extends('layouts.page')

@section('title')
  <h1>{{ __('Browse Updates Report') }}</h1>
@endsection

@section('sidebar')

  <section class="sidebar-widget">

    <div style="margin-bottom: 1rem;">
      <a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" class="c-btn" style="width:100%;">
        <i class="fa fa-arrow-left"></i> {{ __('Back to Reports') }}
      </a>
    </div>

    <h4>{{ __('Filter options') }}</h4>

    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportUpdates']), ['method' => 'get']) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="form-item">
        <label>{{ __('Entity Type') }}</label>
        {!! $form['className']->render() !!}
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
      <label><input type="checkbox" onclick="toggleColumn(0)" checked> {{ __('Type') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> {{ __('Name') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> {{ __('Identifier') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> {{ __('Created') }}</label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> {{ __('Updated') }}</label>
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
      downloadLink.download = 'updates_report_' + new Date().getTime() + '.csv';
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
            <th>{{ __('Type') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Identifier') }}</th>
            <th>{{ __('Created') }}</th>
            <th>{{ __('Updated') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($results as $item)
            <tr>
              <td>{{ str_replace('Qubit', '', $item->className ?? '') }}</td>
              <td>{{ $item->name ?? '-' }}</td>
              <td>{{ $item->identifier ?? '-' }}</td>
              <td>{{ $item->createdAt ?? '-' }}</td>
              <td>{{ $item->updatedAt ?? '-' }}</td>
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
