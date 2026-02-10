@extends('layouts.page')

@section('title')
  <h1>{{ __('Browse Archival Description') }}</h1>
  <div style="margin-bottom: 1rem;">
    <a href="{{ url_for(['module' => 'reports', 'action' => 'reportSelect']) }}" class="c-btn">
      <i class="fa fa-arrow-left"></i> {{ __("Back to Reports") }}
    </a>
  </div>
@endsection

@section('sidebar')

  <section class="sidebar-widget">

    <h4>{{ __('Filter options') }}</h4>

    {!! $form->renderFormTag(url_for(['module' => 'reports', 'action' => 'reportInformationObject']), ['method' => 'get']) !!}

      {!! $form->renderHiddenFields() !!}

      <div class="form-item">
        <label>{{ __('Level of description') }}</label>
        {!! $form['levelOfDescription']->render() !!}
      </div>

      <div class="form-item">
        <label>{{ __('Publication status') }}</label>
        {!! $form['publicationStatus']->render() !!}
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
          {{ __('Export to CSV') }}
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
      <label><input type="checkbox" onclick="toggleColumn(1)" checked> Title</label>
      <label><input type="checkbox" onclick="toggleColumn(2)" checked> Alt Title</label>
      <label><input type="checkbox" onclick="toggleColumn(3)" checked> Extent</label>
      <label><input type="checkbox" onclick="toggleColumn(4)" checked> Archival History</label>
      <label><input type="checkbox" onclick="toggleColumn(5)" checked> Acquisition</label>
      <label><input type="checkbox" onclick="toggleColumn(6)" checked> Scope</label>
      <label><input type="checkbox" onclick="toggleColumn(7)" checked> Appraisal</label>
      <label><input type="checkbox" onclick="toggleColumn(8)" checked> Accruals</label>
      <label><input type="checkbox" onclick="toggleColumn(9)" checked> Arrangement</label>
      <label><input type="checkbox" onclick="toggleColumn(10)" checked> Access</label>
      <label><input type="checkbox" onclick="toggleColumn(11)" checked> Reproduction</label>
      <label><input type="checkbox" onclick="toggleColumn(12)" checked> Physical</label>
      <label><input type="checkbox" onclick="toggleColumn(13)" checked> Finding Aids</label>
      <label><input type="checkbox" onclick="toggleColumn(14)" checked> Originals</label>
      <label><input type="checkbox" onclick="toggleColumn(15)" checked> Copies</label>
      <label><input type="checkbox" onclick="toggleColumn(16)" checked> Related</label>
      <label><input type="checkbox" onclick="toggleColumn(17)" checked> Institution</label>
      <label><input type="checkbox" onclick="toggleColumn(18)" checked> Rules</label>
      <label><input type="checkbox" onclick="toggleColumn(19)" checked> Sources</label>
      <label><input type="checkbox" onclick="toggleColumn(20)" checked> Revision</label>
      <label><input type="checkbox" onclick="toggleColumn(21)" checked> Culture</label>
      <label><input type="checkbox" onclick="toggleColumn(22)" checked> Repository</label>
      <label><input type="checkbox" onclick="toggleColumn(23)" checked> Created</label>
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
      downloadLink.download = 'report_' + new Date().getTime() + '.csv';
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
            <th>Title</th>
            <th>Alternate Title</th>
            <th>Extent And Medium</th>
            <th>Archival History</th>
            <th>Acquisition</th>
            <th>Scope And Content</th>
            <th>Appraisal</th>
            <th>Accruals</th>
            <th>Arrangement</th>
            <th>Access Conditions</th>
            <th>Reproduction Conditions</th>
            <th>Physical Characteristics</th>
            <th>Finding Aids</th>
            <th>Location Of Originals</th>
            <th>Location Of Copies</th>
            <th>Related Units</th>
            <th>Institution Responsible</th>
            <th>Rules</th>
            <th>Sources</th>
            <th>Revision History</th>
            <th>Culture</th>
            <th>Repository</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($results as $item)
            <tr>
              <td>@if(isset($item->identifier))<a href="{{ url_for(['module' => 'informationobject', 'slug' => $item->id]) }}">{{ $item->identifier }}</a>@else - @endif</td>
              <td>{!! $item->title ?? '-' !!}</td>
              <td>{!! $item->alternateTitle ?? '-' !!}</td>
              <td>{!! $item->extentAndMedium ?? '-' !!}</td>
              <td>{!! $item->archivalHistory ?? '-' !!}</td>
              <td>{!! $item->acquisition ?? '-' !!}</td>
              <td>{!! $item->scopeAndContent ?? '-' !!}</td>
              <td>{!! $item->appraisal ?? '-' !!}</td>
              <td>{!! $item->accruals ?? '-' !!}</td>
              <td>{!! $item->arrangement ?? '-' !!}</td>
              <td>{!! $item->accessConditions ?? '-' !!}</td>
              <td>{!! $item->reproductionConditions ?? '-' !!}</td>
              <td>{!! $item->physicalCharacteristics ?? '-' !!}</td>
              <td>{!! $item->findingAids ?? '-' !!}</td>
              <td>{!! $item->locationOfOriginals ?? '-' !!}</td>
              <td>{!! $item->locationOfCopies ?? '-' !!}</td>
              <td>{!! $item->relatedUnitsOfDescription ?? '-' !!}</td>
              <td>{!! $item->institutionResponsibleIdentifier ?? '-' !!}</td>
              <td>{!! $item->rules ?? '-' !!}</td>
              <td>{!! $item->sources ?? '-' !!}</td>
              <td>{!! $item->revisionHistory ?? '-' !!}</td>
              <td>{!! $item->culture ?? '-' !!}</td>
              <td>
                <?php
                  if (isset($item->repositoryId)) {
                    $ioRepo = new \AtomExtensions\Repositories\InformationObjectRepository();
                    $repo = $ioRepo->getRepository($item->id);
                    echo $repo ? $repo->name : '-';
                  } else {
                    echo '-';
                  }
                ?>
              </td>
              <td>{!! $item->createdAt ?? '-' !!}</td>
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
