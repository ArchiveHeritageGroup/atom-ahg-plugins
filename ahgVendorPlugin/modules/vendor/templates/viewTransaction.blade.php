@extends('layouts.page')

@section('content')
@php
$statusColors = [
    'pending' => 'warning',
    'approved' => 'info',
    'in_progress' => 'primary',
    'completed' => 'success',
    'cancelled' => 'secondary',
    'on_hold' => 'dark'
];
@endphp

<div class="container-fluid px-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="@url('ahg_vend_index')">Vendor Management</a></li>
            <li class="breadcrumb-item"><a href="@url('ahg_vend_transactions')">Transactions</a></li>
            <li class="breadcrumb-item active">{{ e($transaction->transaction_number) }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fas fa-file-invoice me-2"></i>{{ e($transaction->transaction_number) }}
            <span class="badge bg-{{ $statusColors[$transaction->status] ?? 'secondary' }} ms-2">
                {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}
            </span>
        </h1>
        <div>
            <a href="@url('ahg_vend_transaction_edit', ['id' => $transaction->id])" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Edit
            </a>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal">
                <i class="fas fa-sync me-1"></i>Update Status
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            {{-- Transaction Details --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Transaction Details</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">Transaction #</th><td><code>{{ e($transaction->transaction_number) }}</code></td></tr>
                                <tr><th>Vendor</th><td><a href="@url('ahg_vend_view', ['slug' => $transaction->vendor_slug])">{{ e($transaction->vendor_name) }}</a></td></tr>
                                <tr><th>Service Type</th><td>{{ e($transaction->service_name ?? '-') }}</td></tr>
                                <tr><th>Priority</th><td><span class="badge bg-{{ ['low'=>'success','normal'=>'primary','high'=>'warning','urgent'=>'danger'][$transaction->priority ?? 'normal'] ?? 'secondary' }}">{{ ucfirst($transaction->priority ?? 'normal') }}</span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr><th width="40%">Request Date</th><td>{{ $transaction->request_date ? date('d M Y', strtotime($transaction->request_date)) : '-' }}</td></tr>
                                <tr><th>Due Date</th><td>{{ $transaction->due_date ? date('d M Y', strtotime($transaction->due_date)) : '-' }}</td></tr>
                                <tr><th>Completion Date</th><td>{{ $transaction->completion_date ? date('d M Y', strtotime($transaction->completion_date)) : '-' }}</td></tr>
                                <tr><th>Reference</th><td>{{ e($transaction->reference_number ?? '-') }}</td></tr>
                            </table>
                        </div>
                    </div>
                    @if ($transaction->description)
                    <div class="mt-3"><strong>Description:</strong><p class="mb-0">{!! nl2br(e($transaction->description)) !!}</p></div>
                    @endif
                </div>
            </div>

            {{-- GLAM/DAM Items --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-archive me-2"></i>GLAM/DAM Items</span>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i>Link Item
                    </button>
                </div>
                <div class="card-body p-0">
                    @php $itemCount = $items ? (is_array($items) ? count($items) : $items->count()) : 0; @endphp
                    @if ($itemCount > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Identifier</th><th>Notes</th><th>Qty</th><th>Cost</th><th>Status</th><th width="80">Actions</th></tr>
                            </thead>
                            <tbody>
                                @php $total = 0; @endphp
                                @foreach ($items as $item)
                                @php $total += ($item->unit_cost ?? 0) * ($item->quantity ?? 1); @endphp
                                <tr>
                                    <td>
                                        @if (!empty($item->io_slug))
                                        <a href="{{ url_for(['module' => 'informationobject', 'slug' => $item->io_slug]) }}" target="_blank"><i class="fas fa-external-link-alt fa-xs me-1"></i>{{ e($item->io_title ?? 'Untitled') }}</a>
                                        @else
                                        {{ e($item->description ?? '-') }}
                                        @endif
                                    </td>
                                    <td><code>{{ e($item->identifier ?? '-') }}</code></td>
                                    <td><small>{{ e($item->notes ?? '-') }}</small></td>
                                    <td>{{ $item->quantity ?? 1 }}</td>
                                    <td>{{ $item->unit_cost ? 'R' . number_format($item->unit_cost * ($item->quantity ?? 1), 2) : '-' }}</td>
                                    <td><span class="badge bg-{{ ['pending'=>'warning','in_progress'=>'info','completed'=>'success'][$item->status ?? 'pending'] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $item->status ?? 'pending')) }}</span></td>
                                    <td>
                                        <a href="@url('ahg_vend_transaction_item_remove', ['transaction_id' => $transaction->id, 'item_id' => $item->id])" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?');"><i class="fas fa-unlink"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light"><tr><th colspan="4" class="text-end">Total:</th><th><strong>R{{ number_format($total, 2) }}</strong></th><th colspan="2"></th></tr></tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4 text-muted"><i class="fas fa-archive fa-2x mb-2"></i><p class="mb-0">No items linked yet</p></div>
                    @endif
                </div>
            </div>

            @if ($transaction->notes)
            <div class="card mb-4"><div class="card-header"><i class="fas fa-sticky-note me-2"></i>Notes</div><div class="card-body">{!! nl2br(e($transaction->notes)) !!}</div></div>
            @endif
        </div>

        <div class="col-md-4">
            {{-- Costs --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-dollar-sign me-2"></i>Cost Summary</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Estimated</th><td class="text-end">{{ $transaction->estimated_cost ? 'R' . number_format($transaction->estimated_cost, 2) : '-' }}</td></tr>
                        <tr><th>Actual</th><td class="text-end"><strong>{{ $transaction->actual_cost ? 'R' . number_format($transaction->actual_cost, 2) : '-' }}</strong></td></tr>
                        <tr class="table-light"><th>Items Total</th><td class="text-end"><strong>R{{ number_format($total ?? 0, 2) }}</strong></td></tr>
                    </table>
                </div>
            </div>

            {{-- Invoice --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><th>Invoice #</th><td>{{ e($transaction->invoice_number ?? '-') }}</td></tr>
                        <tr><th>Invoice Date</th><td>{{ $transaction->invoice_date ? date('d M Y', strtotime($transaction->invoice_date)) : '-' }}</td></tr>
                        <tr><th>Payment</th><td><span class="badge bg-{{ ['pending'=>'warning','paid'=>'success','partial'=>'info','overdue'=>'danger'][$transaction->payment_status ?? 'pending'] ?? 'secondary' }}">{{ ucfirst($transaction->payment_status ?? 'pending') }}</span></td></tr>
                    </table>
                </div>
            </div>

            {{-- Record Info --}}
            <div class="card">
                <div class="card-header"><i class="fas fa-info me-2"></i>Record Info</div>
                <div class="card-body"><small class="text-muted">Created: {{ date('d M Y H:i', strtotime($transaction->created_at)) }}<br>Updated: {{ date('d M Y H:i', strtotime($transaction->updated_at)) }}</small></div>
            </div>
        </div>
    </div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="@url('ahg_vend_transaction_status', ['id' => $transaction->id])">
                <div class="modal-header"><h5 class="modal-title">Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" class="form-select" required>
                            @foreach ($statusOptions as $code => $label)
                            <option value="{{ $code }}" {{ $transaction->status === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes</label><textarea name="status_notes" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>

@include('_addItemModal', ['transaction' => $transaction])
@endsection
