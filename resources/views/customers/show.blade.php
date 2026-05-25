@extends('layouts.app')

@section('title', 'Manage Customers - GetCash Capital')
@section('page-title', 'Manage Customers')

@section('styles')
<style>
    .avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .score-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
    }
    .stat-card {
        background: #fff; border-radius: 12px; padding: 18px 20px;
        border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        display: flex; align-items: center; gap: 16px;
        transition: transform 0.15s, box-shadow 0.15s; cursor: default;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.09); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .data-table tbody tr { transition: background 0.12s; }
    .data-table tbody tr:hover { background: #F0F7FF !important; }
    .action-btn {
        width: 30px; height: 30px; border-radius: 7px;
        border: 1px solid var(--border); background: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; cursor: pointer; transition: all 0.15s;
        text-decoration: none; color: var(--text-secondary);
    }
    .action-btn:hover { border-color: var(--primary); color: var(--primary); background: #E3F2FD; }
    .action-btn.success:hover { border-color: var(--success); color: var(--success); background: #E8F5E9; }
    .action-btn.danger:hover { border-color: var(--danger); color: var(--danger); background: #FFEBEE; }
    .filter-input {
        padding: 8px 14px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; background: #fff; outline: none;
        transition: border-color 0.15s; height: 38px;
    }
    .filter-input:focus { border-color: var(--primary); }
    .empty-state { text-align: center; padding: 70px 20px; color: var(--text-secondary); }
    .empty-state i { font-size: 56px; opacity: 0.18; display: block; margin-bottom: 18px; }
    .empty-state p { font-size: 15px; margin-bottom: 6px; }
    .empty-state small { font-size: 12px; opacity: 0.7; }
</style>
@endsection

@section('content')

{{-- ── Summary Stats ── --}}
<div class="grid-4" style="margin-bottom: 24px;">

    <div class="stat-card" style="border-left: 4px solid var(--primary);">
        <div class="stat-icon" style="background: #E3F2FD; color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div style="font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1;">{{ number_format($totalCustomers) }}</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 3px;">Total Customers</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--success);">
        <div class="stat-icon" style="background: #E8F5E9; color: var(--success);">
            <i class="fas fa-user-check"></i>
        </div>
        <div>
            <div style="font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1;">{{ number_format($activeCustomers) }}</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 3px;">Active</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--warning);">
        <div class="stat-icon" style="background: #FFF3E0; color: var(--warning);">
            <i class="fas fa-user-clock"></i>
        </div>
        <div>
            <div style="font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1;">{{ number_format($pendingCustomers) }}</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 3px;">Pending Approval</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #9E9E9E;">
        <div class="stat-icon" style="background: #F5F5F5; color: #757575;">
            <i class="fas fa-user-slash"></i>
        </div>
        <div>
            <div style="font-size: 26px; font-weight: 700; color: var(--text-primary); line-height: 1;">{{ number_format($dormantCustomers) }}</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 3px;">Dormant / Suspended</div>
        </div>
    </div>

</div>

{{-- ── Main Card ── --}}
<div class="card">

    {{-- Header --}}
    <div class="card-header" style="margin-bottom: 18px;">
        <div>
            <div style="font-size: 15px; font-weight: 600; color: var(--text-primary);">Customer Directory</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                {{ $customers->total() }} {{ Str::plural('record', $customers->total()) }} found
            </div>
        </div>
        <button class="btn btn-primary" onclick="window.location='{{ route('customers.create') }}'">
            <i class="fas fa-user-plus"></i> Add Customer
        </button>
    </div>

    {{-- ── Filters ── --}}
    <form method="GET" action="{{ route('customers.index') }}">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 16px; background: #FAFBFC; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px;">

            <div style="position: relative; flex: 1; min-width: 200px; max-width: 280px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name, phone, ID, customer no…"
                       class="filter-input" style="width: 100%; padding-left: 36px;">
            </div>

            <select name="status" class="filter-input" style="min-width: 150px;">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'rejected' => 'Rejected', 'dormant' => 'Dormant'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="branch" class="filter-input" style="min-width: 160px;">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>

            <select name="employment_type" class="filter-input" style="min-width: 160px;">
                <option value="">All Employment</option>
                @foreach(['salaried' => 'Salaried', 'self_employed' => 'Self Employed', 'business' => 'Business', 'farmer' => 'Farmer', 'other' => 'Other'] as $val => $label)
                    <option value="{{ $val }}" {{ request('employment_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 18px;">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline" style="height: 38px; padding: 0 14px;" title="Reset">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>

    {{-- ── Table ── --}}
    <div style="overflow-x: auto;">
        <table class="data-table" style="min-width: 1000px;">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAll" style="cursor:pointer; accent-color: var(--primary);">
                    </th>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Branch / Officer</th>
                    <th>Employment</th>
                    <th>Credit Score</th>
                    <th>Savings</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $index => $customer)
                @php
                    $initials   = collect(explode(' ', $customer->full_name))
                                    ->map(fn($w) => strtoupper($w[0] ?? ''))
                                    ->take(2)->implode('');
                    $colors     = ['#00BCD4','#4CAF50','#FF9800','#9C27B0','#F44336','#3F51B5','#009688','#795548'];
                    $avatarBg   = $colors[abs(crc32($customer->customer_number)) % count($colors)];

                    $score      = $customer->credit_score ?? 0;
                    $scoreColor = match(true) {
                        $score >= 800 => ['#4CAF50', '#E8F5E9'],
                        $score >= 650 => ['#8BC34A', '#F1F8E9'],
                        $score >= 500 => ['#FF9800', '#FFF3E0'],
                        $score >= 350 => ['#FF5722', '#FBE9E7'],
                        default       => ['#F44336', '#FFEBEE'],
                    };

                    $statusMap = [
                        'active'    => ['status-active',             'Active'],
                        'pending'   => ['status-pending',            'Pending'],
                        'suspended' => ['status-partially-approved', 'Suspended'],
                        'rejected'  => ['status-rejected',           'Rejected'],
                        'dormant'   => ['status-partially-approved', 'Dormant'],
                    ];
                    [$statusClass, $statusLabel] = $statusMap[$customer->status]
                        ?? ['status-pending', ucfirst($customer->status)];

                    $empIcons = [
                        'salaried'      => 'fa-briefcase',
                        'self_employed' => 'fa-store',
                        'business'      => 'fa-building',
                        'farmer'        => 'fa-seedling',
                        'other'         => 'fa-ellipsis-h',
                    ];
                    $empIcon = $empIcons[$customer->employment_type ?? 'other'] ?? 'fa-ellipsis-h';
                @endphp
                <tr>
                    <td>
                        <input type="checkbox" class="row-check"
                               style="cursor:pointer; accent-color: var(--primary);">
                    </td>

                    <td style="color: var(--text-secondary); font-size: 12px;">
                        {{ ($customers->currentPage() - 1) * $customers->perPage() + $index + 1 }}
                    </td>

                    {{-- Identity --}}
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar" style="background: {{ $avatarBg }};">{{ $initials }}</div>
                            <div>
                                <div style="font-weight: 600; font-size: 13px;">{{ $customer->full_name }}</div>
                                <div style="font-size: 11px; color: var(--text-secondary); font-family: monospace; margin-top: 1px;">
                                    {{ $customer->customer_number }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Contact --}}
                    <td>
                        <div style="font-size: 13px;">{{ $customer->phone_number }}</div>
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                            ID: {{ $customer->id_number }}
                        </div>
                    </td>

                    {{-- Branch / Officer --}}
                    <td>
                        <div style="font-size: 13px; font-weight: 500;">{{ $customer->branch->name ?? '—' }}</div>
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                            <i class="fas fa-user-tie" style="font-size: 10px;"></i>
                            {{ $customer->relationshipOfficer->name ?? '—' }}
                        </div>
                    </td>

                    {{-- Employment --}}
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-secondary);">
                            <i class="fas {{ $empIcon }}" style="font-size: 13px; color: var(--primary);"></i>
                            {{ ucfirst(str_replace('_', ' ', $customer->employment_type ?? 'N/A')) }}
                        </div>
                        @if($customer->employer_name || $customer->business_name)
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $customer->employer_name ?? $customer->business_name }}
                        </div>
                        @endif
                    </td>

                    {{-- Credit Score --}}
                    <td>
                        <div class="score-pill" style="background: {{ $scoreColor[1] }}; color: {{ $scoreColor[0] }};">
                            <i class="fas fa-circle" style="font-size: 7px;"></i>
                            {{ $score }}
                        </div>
                        <div style="margin-top: 5px; width: 70px; height: 4px; background: #E8ECF1; border-radius: 2px; overflow: hidden;">
                            <div style="width: {{ min(100, ($score / 1000) * 100) }}%; height: 100%; background: {{ $scoreColor[0] }}; border-radius: 2px;"></div>
                        </div>
                    </td>

                    {{-- Savings --}}
                    <td>
                        <div style="font-weight: 600; font-size: 13px; color: var(--success);">
                            KSH {{ number_format($customer->savings_balance, 0) }}
                        </div>
                        @if($customer->share_capital > 0)
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                            Share: KSH {{ number_format($customer->share_capital, 0) }}
                        </div>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                        @if($customer->kyc_verified_at)
                        <div style="margin-top: 4px; font-size: 10px; color: var(--success);">
                            <i class="fas fa-shield-alt"></i> KYC Verified
                        </div>
                        @else
                        <div style="margin-top: 4px; font-size: 10px; color: var(--warning);">
                            <i class="fas fa-exclamation-circle"></i> KYC Pending
                        </div>
                        @endif
                    </td>

                    {{-- Joined --}}
                    <td style="font-size: 12px; color: var(--text-secondary);">
                        {{ $customer->created_at->format('d M Y') }}
                        <div style="font-size: 11px; margin-top: 1px;">{{ $customer->created_at->diffForHumans() }}</div>
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <a href="{{ route('customers.profile', $customer) }}" class="action-btn" title="View Profile">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" class="action-btn" title="Edit Customer">
                                <i class="fas fa-pen"></i>
                            </a>
                            @if($customer->status === 'pending')
                            <form method="POST" action="{{ route('customers.activate', $customer) }}" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="action-btn success" title="Activate">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                            @if(in_array($customer->status, ['active', 'dormant']))
                            <a href="{{ route('loans.create', ['customer_id' => $customer->id]) }}" class="action-btn" title="Apply for Loan" style="color: #9C27B0;">
                                <i class="fas fa-hand-holding-usd"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No customers found</p>
                            <small>Try adjusting your search or filters, or add a new customer</small>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Pagination ── --}}
    @if($customers->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 4px 4px; border-top: 1px solid var(--border); margin-top: 8px;">
        <span style="font-size: 12px; color: var(--text-secondary);">
            Showing <strong>{{ $customers->firstItem() }}</strong> –
            <strong>{{ $customers->lastItem() }}</strong>
            of <strong>{{ $customers->total() }}</strong> customers
        </span>
        {{ $customers->links() }}
    </div>
    @endif

</div>

{{-- ── Bulk action bar ── --}}
<div id="bulkBar" style="display:none; position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:#2C3E50; color:#fff; border-radius:12px; padding:12px 24px; align-items:center; gap:16px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:500; font-size:13px;">
    <span id="bulkCount">0 selected</span>
    <div style="width:1px; height:20px; background:rgba(255,255,255,0.2);"></div>
    <button class="btn" style="background:rgba(255,255,255,0.1); color:#fff; padding:6px 14px; font-size:12px;">
        <i class="fas fa-envelope"></i> Send SMS
    </button>
    <button class="btn" style="background:rgba(255,255,255,0.1); color:#fff; padding:6px 14px; font-size:12px;">
        <i class="fas fa-download"></i> Export
    </button>
    <button onclick="clearSelection()" style="background:none; border:none; color:rgba(255,255,255,0.6); cursor:pointer; font-size:18px; line-height:1;">&times;</button>
</div>

@endsection

@section('scripts')
<script>
    const selectAll = document.getElementById('selectAll');
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.row-check:checked').length;
        bulkBar.style.display = checked > 0 ? 'flex' : 'none';
        bulkCount.textContent = checked + ' selected';
    }

    selectAll?.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    function clearSelection() {
        document.querySelectorAll('.row-check, #selectAll').forEach(cb => cb.checked = false);
        bulkBar.style.display = 'none';
    }

    function openAddModal() {
        alert('Add Customer form coming soon.');
    }
</script>
@endsection
