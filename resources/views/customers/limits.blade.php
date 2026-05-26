{{-- resources/views/customers/limits.blade.php --}}
@extends('layouts.app')

@section('title', 'Limit Management - GetCash Capital')
@section('page-title', 'Limit Management')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Limit Management</span>
        <button class="btn btn-outline" style="font-size: 12px;">
            <i class="fas fa-cog"></i> Bulk Update
        </button>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('customers.limits') }}" style="margin-bottom:20px;">
        <div class="filter-row">
            <div style="flex:1 1 200px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or phone">
                </div>
            </div>
            <select name="tier" class="filter-select" style="flex:1 1 160px;">
                <option value="">All Tiers</option>
                <option value="platinum" {{ request('tier') === 'platinum' ? 'selected' : '' }}>Platinum (500K+)</option>
                <option value="gold"     {{ request('tier') === 'gold'     ? 'selected' : '' }}>Gold (200K–499K)</option>
                <option value="silver"   {{ request('tier') === 'silver'   ? 'selected' : '' }}>Silver (50K–199K)</option>
                <option value="bronze"   {{ request('tier') === 'bronze'   ? 'selected' : '' }}>Bronze (&lt;50K)</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Search</span></button>
            <a href="{{ route('customers.limits') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
        </div>
    </form>

    <div class="grid-4" style="margin-bottom: 20px;">
        <div class="card" style="background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%); border: none;">
            <div class="metric-value" style="font-size: 24px; color: #1565C0;">KSH {{ number_format($totalLimits, 0) }}</div>
            <div class="metric-label">Total Limits Assigned</div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); border: none;">
            <div class="metric-value" style="font-size: 24px; color: #2E7D32;">{{ number_format($withLimits) }}</div>
            <div class="metric-label">Customers With Limits</div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); border: none;">
            <div class="metric-value" style="font-size: 24px; color: #E65100;">{{ number_format($withoutLimits) }}</div>
            <div class="metric-label">Pending Limit Assignment</div>
        </div>
        <div class="card" style="background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%); border: none;">
            <div class="metric-value" style="font-size: 24px; color: #6A1B9A;">KSH {{ number_format($avgLimit, 0) }}</div>
            <div class="metric-label">Average Limit</div>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Current Limit</th>
                <th>Utilized</th>
                <th>Available</th>
                <th>Utilization %</th>
                <th>Tier</th>
                <th>Savings Multiplier</th>
                <th>Last Review</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers ?? [] as $index => $customer)
            @php
                $utilized = $customer->activeLoans->sum('principal_amount') ?? 0;
                $available = max(0, $customer->credit_limit - $utilized);
                $utilizationPercent = $customer->credit_limit > 0 ? round(($utilized / $customer->credit_limit) * 100, 1) : 0;
                
                $tier = match(true) {
                    $customer->credit_limit >= 500000 => ['Platinum', '#1565C0', 'platinum'],
                    $customer->credit_limit >= 200000 => ['Gold', '#F9A825', 'gold'],
                    $customer->credit_limit >= 50000 => ['Silver', '#757575', 'silver'],
                    default => ['Bronze', '#8D6E63', 'bronze']
                };
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="font-weight: 600;">{{ $customer->full_name }}</td>
                <td>{{ $customer->phone_number }}</td>
                <td style="font-weight: 600; color: var(--primary);">KSH {{ number_format($customer->credit_limit, 0) }}</td>
                <td style="color: var(--danger);">KSH {{ number_format($utilized, 0) }}</td>
                <td style="color: var(--success);">KSH {{ number_format($available, 0) }}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 60px; height: 8px; background: #E8ECF1; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ min(100, $utilizationPercent) }}%; height: 100%; background: {{ $utilizationPercent > 80 ? '#F44336' : ($utilizationPercent > 50 ? '#FF9800' : '#4CAF50') }}; border-radius: 4px;"></div>
                        </div>
                        <span style="font-size: 12px; font-weight: 600;">{{ $utilizationPercent }}%</span>
                    </div>
                </td>
                <td>
                    <span class="status" style="background: {{ $tier[1] }}20; color: {{ $tier[1] }};">
                        {{ $tier[0] }}
                    </span>
                </td>
                <td style="font-size: 12px;">{{ number_format($customer->savings_balance, 0) }} x {{ $customer->credit_limit > 0 ? round($customer->credit_limit / max(1, $customer->savings_balance), 1) : 0 }}</td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $customer->updated_at->format('d-M-Y') }}
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;"
                                onclick="openAdjustModal({{ $customer->id }}, '{{ addslashes($customer->full_name) }}', {{ $customer->credit_limit }})">
                            <i class="fas fa-edit"></i> Adjust
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-sliders-h" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                    <p>No limit data available</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Adjust Limit Modal --}}
<div id="adjustModal" class="modal-overlay" onclick="if(event.target===this)closeModal('adjustModal')">
    <div class="modal-box">
        <h3 style="font-size:16px; font-weight:600; margin-bottom:6px;">Adjust Credit Limit</h3>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
            Updating limit for <strong id="adjustCustomerName"></strong>
        </p>
        <form id="adjustForm" method="POST">
            @csrf @method('PATCH')
            <div style="margin-bottom:15px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">New Credit Limit (KSH) <span style="color:var(--danger)">*</span></label>
                <input type="number" name="credit_limit" id="adjustLimitInput" min="0" step="1000"
                       class="filter-select" style="width:100%;" required>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Reason for Change</label>
                <textarea name="reason" rows="2" placeholder="Optional notes…"
                          style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Limit</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openAdjustModal(id, name, currentLimit) {
    document.getElementById('adjustCustomerName').textContent = name;
    document.getElementById('adjustLimitInput').value = currentLimit;
    document.getElementById('adjustForm').action = `/customers/${id}/adjust-limit`;
    document.getElementById('adjustModal').classList.add('show');
}
function closeAdjustModal() {
    document.getElementById('adjustModal').classList.remove('show');
}
</script>
@endsection