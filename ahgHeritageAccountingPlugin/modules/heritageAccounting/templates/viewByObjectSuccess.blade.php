@extends('layouts.page')

@section('title', __('Heritage Asset'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-3"><i class="fas fa-landmark me-2"></i>{{ __('Heritage Asset') }}</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
            <h4>{{ __('No Heritage Asset Data') }}</h4>
            <p class="text-muted mb-4">{{ __('No heritage asset accounting data has been recorded for this item yet.') }}</p>
            <a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'add', 'io_id' => $io->id]) }}" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i>{{ __('Create Heritage Asset Record') }}
            </a>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $slug]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('Back to Record') }}
        </a>
    </div>
</div>
@endsection
