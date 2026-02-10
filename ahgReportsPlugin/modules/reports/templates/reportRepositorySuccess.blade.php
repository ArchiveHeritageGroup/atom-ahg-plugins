@extends('layouts.page')

@section('title')
  <h1>{{ __('Browse Repository Report') }}</h1>
  <div style="margin-bottom: 1rem;">
    <a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" class="c-btn">
      <i class="fa fa-arrow-left"></i> {{ __("Back to Reports") }}
    </a>
  </div>
@endsection

@section('sidebar')

  <section class="sidebar-widget">

    <h4>{{ __('Filter options') }}</h4>

    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportRepository']), ['method' => 'get']) !!}

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
      <label><input type="checkbox" onclick="toggleColumn(0)" checked> Identifier</label>
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> Name</label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> Desc Status</label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> Desc Detail</label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> Desc ID</label>
      <label><input type="checkbox" onclick="toggleColumn(5)" checked> Geocultural</label>
      <label><input type="checkbox" onclick="toggleColumn(6)" checked> Collecting</label>
      <label><input type="checkbox" onclick="toggleColumn(7)" checked> Buildings</label>
      <label><input type="checkbox" onclick="toggleColumn(8)" checked> Holdings</label>
      <label><input type="checkbox" onclick="toggleColumn(9)" checked> Finding Aids</label>
      <label><input type="checkbox" onclick="toggleColumn(10)" checked> Opening Times</label>
      <label><input type="checkbox" onclick="toggleColumn(11)" checked> Access</label>
      <label><input type="checkbox" onclick="toggleColumn(12)" checked> Disabled Access</label>
      <label><input type="checkbox" onclick="toggleColumn(13)" checked> Research</label>
      <label><input type="checkbox" onclick="toggleColumn(14)" checked> Reproduction</label>
      <label><input type="checkbox" onclick="toggleColumn(15)" checked> Public Facilities</label>
      <label><input type="checkbox" onclick="toggleColumn(16)" checked> Institution ID</label>
      <label><input type="checkbox" onclick="toggleColumn(17)" checked> Rules</label>
      <label><input type="checkbox" onclick="toggleColumn(18)" checked> Sources</label>
      <label><input type="checkbox" onclick="toggleColumn(19)" checked> Revision</label>
      <label><input type="checkbox" onclick="toggleColumn(20)" checked> Created</label>
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
      downloadLink.download = 'repository_report_' + new Date().getTime() + '.csv';
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
            <th>Identifier</th>
            <th>Name</th>
            <th>Description Status</th>
            <th>Description Detail</th>
            <th>Description Identifier</th>
            <th>Geocultural Context</th>
            <th>Collecting Policies</th>
            <th>Buildings</th>
            <th>Holdings</th>
            <th>Finding Aids</th>
            <th>Opening Times</th>
            <th>Access Conditions</th>
            <th>Disabled Access</th>
            <th>Research Services</th>
            <th>Reproduction Services</th>
            <th>Public Facilities</th>
            <th>Description Institution Identifier</th>
            <th>Description Rules</th>
            <th>Description Sources</th>
            <th>Description Revision History</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $termService = new \AtomExtensions\Services\TermService('en');
          foreach ($results as $item) {
          ?>
            <tr>
              <td>@if(isset($item->identifier))<a href="{{ url_for(['module' => 'repository', 'slug' => $item->id]) }}">{{ $item->identifier }}</a>@else - @endif</td>
              <td>{!! $item->name ?? '-' !!}</td>
              <td>{!! isset($item->descStatusId) ? $termService->getTermName($item->descStatusId) : '-' !!}</td>
              <td>{!! isset($item->descDetailId) ? $termService->getTermName($item->descDetailId) : '-' !!}</td>
              <td>{!! $item->descIdentifier ?? '-' !!}</td>
              <td>{!! $item->geoculturalContext ?? '-' !!}</td>
              <td>{!! $item->collectingPolicies ?? '-' !!}</td>
              <td>{!! $item->buildings ?? '-' !!}</td>
              <td>{!! $item->holdings ?? '-' !!}</td>
              <td>{!! $item->findingAids ?? '-' !!}</td>
              <td>{!! $item->openingTimes ?? '-' !!}</td>
              <td>{!! $item->accessConditions ?? '-' !!}</td>
              <td>{!! $item->disabledAccess ?? '-' !!}</td>
              <td>{!! $item->researchServices ?? '-' !!}</td>
              <td>{!! $item->reproductionServices ?? '-' !!}</td>
              <td>{!! $item->publicFacilities ?? '-' !!}</td>
              <td>{!! $item->descInstitutionIdentifier ?? '-' !!}</td>
              <td>{!! $item->descRules ?? '-' !!}</td>
              <td>{!! $item->descSources ?? '-' !!}</td>
              <td>{!! $item->descRevisionHistory ?? '-' !!}</td>
              <td>{!! $item->createdAt ?? '-' !!}</td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>

  @else
    <div class="alert alert-warning">
      {{ __('No results found.') }}
    </div>
  @endif

@endsection
