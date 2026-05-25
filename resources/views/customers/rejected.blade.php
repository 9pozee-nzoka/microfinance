{{-- resources/views/customers/rejected.blade.php --}}
@extends('layouts.app')

@section('title', 'Rejected Customers - GetCash Capital')
@section('page-title', 'Rejected Customers')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#E8F5E9; border:1px solid #A5D6A7; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#2E7D32; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Rejected Customers</span>
        <span class="badge badge-danger">{{ $customers->total() ?? 0 }} Rejected</span>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('customers.rejected') }}" style="margin-bottom:15px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <div class="search-box" style="width: 260px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone or ID">
            </div>
            <input type="text" name="reason" value="{{ request('reason') }}" placeholder="Filter by rejection reason"
                   class="filter-select" style="width:220px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <a href="{{ route('customers.rejected') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
        </div>
    </form>

    <div style="background: #FFEBEE; border: 1px solid #FFCDD2; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-triangle" style="color: #C62828;"></i>
        <span style="font-size: 13px; color: #C62828;">
            <strong>{{ $customers->total() ?? 0 }}</strong> rejected customers. Review before permanent deletion.
        </span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer No</th>
                <th>Full Name</th>
                <th>Phone Number</th>
                <th>ID Number</th>
                <th>Branch</th>
                <th>Relationship Officer</th>
                <th>Rejection Date</th>
                <th>Rejection Reason</th>
                <th>Rejected By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers ?? [] as $index => $customer)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 12px;">{{ $customer->customer_number }}</td>
                <td style="font-weight: 600;">{{ $customer->full_name }}</td>
                <td>{{ $customer->phone_number }}</td>
                <td>{{ $customer->id_number }}</td>
                <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                <td>{{ $customer->relationshipOfficer->name ?? 'N/A' }}</td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $customer->updated_at->format('d-M-Y') }}
                </td>
                <td>
                    <span class="status status-rejected">{{ $customer->rejection_reason ?? 'Not specified' }}</span>
                </td>
                <td style="font-size: 12px;">{{ $customer->kyc_verified_by ? \App\Models\User::find($customer->kyc_verified_by)?->name ?? 'System' : 'System' }}</td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <form method="POST" action="{{ route('customers.reactivate', $customer) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 12px; color: var(--success); border-color: var(--success);">
                                <i class="fas fa-redo"></i> Re-activate
                            </button>
                        </form>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline;"
                              onsubmit="return confirm('Permanently delete {{ addslashes($customer->full_name) }}? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 12px; color: var(--danger); border-color: var(--danger);">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-user-times" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                    <p>No rejected customers</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection