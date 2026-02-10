@extends('layouts.page')

@php
$users = $userData['users'] ?? [];
$total = $userData['total'] ?? 0;
$page = $userData['page'] ?? 1;
$pages = $userData['pages'] ?? 1;
@endphp

@section('title')
<h1 class="h3">
    <i class="fas fa-users me-2"></i>User Management
</h1>
@endsection

@section('sidebar')
@include('heritage/adminSidebar', ['active' => 'users'])
@endsection

@section('content')
<!-- Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" placeholder="Search by username or email..."
                       value="{{ $sf_request->getParameter('search', '') }}">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="trust_level">
                    <option value="">All Trust Levels</option>
                    @foreach ($trustLevels as $level)
                    <option value="{{ $level->code }}"
                            {{ $sf_request->getParameter('trust_level') === $level->code ? 'selected' : '' }}>
                        {{ $level->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Users</h5>
        <span class="badge bg-secondary">{{ number_format($total) }} total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Trust Level</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->username ?? 'N/A' }}</strong>
                        </td>
                        <td>{{ $user->email ?? '' }}</td>
                        <td>
                            @if ($user->trust_name)
                            <span class="badge bg-info">{{ $user->trust_name }}</span>
                            @else
                            <span class="badge bg-secondary">None</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->active)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ date('Y-m-d', strtotime($user->created_at)) }}</small>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#trustModal"
                                    data-user-id="{{ $user->id }}"
                                    data-username="{{ $user->username }}">
                                <i class="fas fa-shield-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                    @if (empty($users))
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @if ($pages > 1)
    <div class="card-footer bg-transparent">
        <nav>
            <ul class="pagination mb-0 justify-content-center">
                @for ($i = 1; $i <= min($pages, 10); $i++)
                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="?page={{ $i }}&search={{ urlencode($sf_request->getParameter('search', '')) }}&trust_level={{ urlencode($sf_request->getParameter('trust_level', '')) }}">{{ $i }}</a>
                </li>
                @endfor
            </ul>
        </nav>
    </div>
    @endif
</div>

<!-- Trust Level Modal -->
<div class="modal fade" id="trustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Trust Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    <p>Assigning trust level to: <strong id="modal_username"></strong></p>
                    <div class="mb-3">
                        <label for="trust_level_id" class="form-label">Trust Level</label>
                        <select class="form-select" name="trust_level_id" id="trust_level_id" required>
                            @foreach ($trustLevels as $level)
                            <option value="{{ $level->id }}">
                                {{ $level->name }} (Level {{ $level->level }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="expires_at" class="form-label">Expires At (optional)</label>
                        <input type="date" class="form-control" name="expires_at" id="expires_at">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Trust Level</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script {!! $csp_nonce !!}>
document.getElementById('trustModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    document.getElementById('modal_user_id').value = button.getAttribute('data-user-id');
    document.getElementById('modal_username').textContent = button.getAttribute('data-username');
});
</script>
@endsection
