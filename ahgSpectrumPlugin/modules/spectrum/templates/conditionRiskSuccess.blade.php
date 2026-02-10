<h1 class="h3 mb-4">{{ __('Risk Assessment') }}</h1>
<a href="/admin/condition" class="btn btn-secondary mb-3">{{ __('Back') }}</a>
<div class="card">
    <div class="card-header bg-danger text-white"><h5 class="mb-0">{{ __('High Risk Items') }}</h5></div>
    <div class="card-body">
        @if(!empty($riskItems))
            <table class="table">
                <thead><tr><th>Object</th><th>Condition</th><th>Last Check</th></tr></thead>
                <tbody>
                @foreach($riskItems as $item)
                    <tr>
                        <td><a href="/{{ $item->slug ?? '' }}">{{ $item->title ?? 'Untitled' }}</a></td>
                        <td><span class="badge bg-danger">{{ ucfirst($item->overall_condition ?? '') }}</span></td>
                        <td>{{ $item->check_date ?? '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-4"><i class="fas fa-check-circle fa-3x text-success mb-2"></i><p>{{ __('No high-risk items') }}</p></div>
        @endif
    </div>
</div>
