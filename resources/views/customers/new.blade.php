{{-- resources/views/customers/new.blade.php --}}
@extends('layouts.app')

@section('title', 'Newly Registered - GetCash Capital')
@section('page-title', 'Newly Registered')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div style="background:#E8F5E9; border:1px solid #A5D6A7; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#2E7D32; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FFEBEE; border:1px solid #FFCDD2; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#C62828; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Newly Registered Customers</span>
        <span class="badge badge-warning">{{ $customers->total() ?? 0 }} Pending</span>
    </div>

    {{-- Filter bar --}}
    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 15px;">
        <form method="GET" action="{{ route('customers.new') }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
            <div class="search-box" style="width: 260px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone or ID" id="newCustomerSearch">
            </div>
            <select name="branch" class="filter-select" style="width: 180px;">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            <a href="{{ route('customers.new') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
        </form>
    </div>

    <div style="background: #FFF3E0; border: 1px solid #FFE0B2; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-info-circle" style="color: #E65100;"></i>
        <span style="font-size: 13px; color: #E65100;">
            <strong>{{ $customers->total() ?? 0 }}</strong> customers pending KYC verification and approval
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
                <th>Registration Date</th>
                <th>KYC Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers ?? [] as $index => $customer)
            <tr style="background: {{ $customer->kyc_verified_at ? 'transparent' : '#FFF8E1' }};">
                <td>{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 12px;">{{ $customer->customer_number }}</td>
                <td style="font-weight: 600;">{{ $customer->full_name }}</td>
                <td>{{ $customer->phone_number }}</td>
                <td>{{ $customer->id_number }}</td>
                <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                <td>{{ $customer->relationshipOfficer->name ?? 'N/A' }}</td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $customer->created_at->format('d-M-Y') }}<br>
                    {{ $customer->created_at->format('h:i A') }}
                </td>
                <td>
                    @if($customer->kyc_verified_at)
                        <span class="status status-active"><i class="fas fa-check-circle" style="margin-right: 4px;"></i>Verified</span>
                    @else
                        <span class="status status-pending"><i class="fas fa-clock" style="margin-right: 4px;"></i>Pending</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <form method="POST" action="{{ route('customers.verify-kyc', $customer) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;"
                                    {{ $customer->kyc_verified_at ? 'disabled' : '' }}>
                                <i class="fas fa-check"></i> Verify KYC
                            </button>
                        </form>
                        @if($customer->kyc_verified_at)
                        <form method="POST" action="{{ route('customers.activate', $customer) }}" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-outline" style="padding: 5px 12px; font-size: 12px; color: var(--success); border-color: var(--success);">
                                <i class="fas fa-user-check"></i> Activate
                            </button>
                        </form>
                        @endif
                        <button type="button" class="btn btn-outline" style="padding: 5px 12px; font-size: 12px; color: var(--danger); border-color: var(--danger);"
                                onclick="openRejectModal({{ $customer->id }}, '{{ addslashes($customer->full_name) }}')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-user-plus" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                    <p>No newly registered customers</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:460px; max-width:95%;">
        <h3 style="font-size:16px; font-weight:600; margin-bottom:6px;">Reject Customer</h3>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
            Rejecting <strong id="rejectCustomerName"></strong>. Please provide a reason.
        </p>
        <form id="rejectForm" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" id="rejectCustomerId">
            <div style="margin-bottom:16px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Rejection Reason <span style="color:var(--danger)">*</span></label>
                <textarea name="reason" rows="3" required placeholder="e.g. Incomplete KYC documents, fraud suspicion…"
                          style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn" style="background:var(--danger); color:white;">
                    <i class="fas fa-times"></i> Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openRejectModal(id, name) {
    document.getElementById('rejectCustomerId').value = id;
    document.getElementById('rejectCustomerName').textContent = name;
    document.getElementById('rejectForm').action = `/customers/${id}/reject`;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endsection