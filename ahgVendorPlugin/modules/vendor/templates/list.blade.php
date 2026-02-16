@extends('layouts.page')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-building me-2"></i>Vendors</h1>
        <div>
            <a href="@url('ahg_vend_index')" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
            </a>
            <a href="@url('ahg_vend_add')" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Vendor
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="@url('ahg_vend_list')" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, code, email..." value="{{ e($filters['search'] ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach ($vendorStatuses as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="vendor_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach ($vendorTypes as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['vendor_type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Service</label>
                    <select name="service_type_id" class="form-select">
                        <option value="">All Services</option>
                        @foreach ($serviceTypes as $service)
                            <option value="{{ $service->id }}" {{ ($filters['service_type_id'] ?? '') == $service->id ? 'selected' : '' }}>{{ e($service->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Insurance</label>
                    <select name="has_insurance" class="form-select">
                        <option value="">Any</option>
                        <option value="1" {{ ($filters['has_insurance'] ?? '') === '1' ? 'selected' : '' }}>Has Valid Insurance</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    <div class="card">
        <div class="card-header">
            <span class="badge bg-secondary me-2">{{ $vendors->count() }}</span> Vendors
        </div>
        <div class="card-body p-0">
            @if ($vendors->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>City</th>
                            <th>Insurance</th>
                            <th>Transactions</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendors as $vendor)
                        <tr>
                            <td><code>{{ e($vendor->vendor_code) }}</code></td>
                            <td>
                                <a href="@url('ahg_vend_view', ['slug' => $vendor->slug])">
                                    <strong>{{ e($vendor->name) }}</strong>
                                </a>
                            </td>
                            <td>{{ ucfirst($vendor->vendor_type) }}</td>
                            <td>
                                @if ($vendor->email)
                                    <a href="mailto:{{ $vendor->email }}">{{ e($vendor->email) }}</a>
                                @elseif ($vendor->phone)
                                    {{ e($vendor->phone) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ e($vendor->city ?? '-') }}</td>
                            <td>
                                @if ($vendor->has_insurance && $vendor->insurance_expiry_date)
                                    @php $expired = strtotime($vendor->insurance_expiry_date) < time(); @endphp
                                    <span class="badge bg-{{ $expired ? 'danger' : 'success' }}">
                                        {{ $expired ? 'Expired' : 'Valid' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary" title="Total transactions">{{ $vendor->transaction_count ?? 0 }}</span>
                                @if (($vendor->active_transactions ?? 0) > 0)
                                <span class="badge bg-primary" title="Active transactions">{{ $vendor->active_transactions }} active</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger', 'pending_approval' => 'warning'];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$vendor->status] ?? 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $vendor->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="@url('ahg_vend_view', ['slug' => $vendor->slug])" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="@url('ahg_vend_edit', ['slug' => $vendor->slug])" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="@url('ahg_vend_transaction_add', ['vendor' => $vendor->slug])" class="btn btn-outline-success" title="New Transaction">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete" onclick="deleteVendor('{{ $vendor->slug }}', '{{ e($vendor->name) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="fas fa-building fa-3x mb-3"></i>
                <p>No vendors found matching your criteria</p>
                <a href="@url('ahg_vend_add')" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Add First Vendor
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Delete Form (hidden) --}}
<form id="deleteVendorForm" method="post" action="" style="display: none;">
    <input type="hidden" name="_method" value="POST">
</form>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete vendor <strong id="deleteVendorName"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone. All associated data will be permanently removed.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Delete Vendor
                </button>
            </div>
        </div>
    </div>
</div>

<script {!! $csp_nonce !!}>
let deleteSlug = '';

function deleteVendor(slug, name) {
    deleteSlug = slug;
    document.getElementById('deleteVendorName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const form = document.getElementById('deleteVendorForm');
    form.action = '/index.php/ahg/vend/delete/' + deleteSlug;
    form.submit();
});
</script>
@endsection
