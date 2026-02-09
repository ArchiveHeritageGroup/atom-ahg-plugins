@extends('layouts.page')

@section('content')
@php
$title = __('Import AHG Settings');
@endphp

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-upload me-2"></i>{{ $title }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('Upload a previously exported AHG settings JSON file to restore settings.') }}
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="settings_file" class="form-label">{{ __('Settings File') }}</label>
                            <input type="file" class="form-control" id="settings_file" name="settings_file" accept=".json" required>
                            <div class="form-text">{{ __('Select a .json file exported from AHG Settings') }}</div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ __('Warning: This will overwrite existing settings with the same keys.') }}
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>{{ __('Import Settings') }}
                            </button>
                            <a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'index']) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>{{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
