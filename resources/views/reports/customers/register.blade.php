@extends('layouts.app')
@section('title', 'Customer Register - Reports')
@section('page-title', 'Customer Register')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
</div>

{{-- Status Stats --}}
<div class="grid-4" style="margin-bottom:20px;">
    @php
        $statusDef = ['active' => ['var(--success)','Active'], 'pending' => ['var(--warning)','Pending'], 'rejected' => ['var(--danger)','Rejected'], 'dormant' => ['#9E9E9E','Dormant']];
    @endphp
    @foreach($statusDef as $s => [$color, $label])
    <div class="card" style="border-left:4px solid {{ $color }}; text-align:center; padding:16px;">
        <div style="font-size:26px; font-weight:700; color:{{ $color }};">{{ number_format($stats[$s]->cnt ?? 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:3px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.customers.register') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    @foreach(['active','pending','rejected','dormant','suspended'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Branch</label>
                <select name="branch" class="filter-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Employment</label>
                <select name="employment_type" class="filter-select">
                    <option value="">All</option>
                    @foreach(['salaried','self_employed','business','farmer','other'] as $e)
                        <option value="{{ $e }}" {{ request('employment_type') === $e ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$e)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Joined From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select">
            </div>
            <div>
                <label class="form-label">Joined To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-select">
            </div>
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.customers.register') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
                <button type="submit" name="export" value="1" class="btn btn-outline" style="height:38px; padding:0 14px; color:var(--success); border-color:var(--success);">
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Customers — {{ $customers->total() }} records</span>
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Customer No.</th><th>Full Name</th><th>Phone</th><th>ID No.</th><th>Branch</th><th>Officer</th><th>Employment</th><th>Savings</th><th>Credit Score</th><th>Status</th><th>Joined</th></tr>
            </thead>
            <tbody>
                @forelse($customers as $i => $c)
                @php
                    $sc = match($c->status) { 'active' => 'status-active', 'pending' => 'status-pending', 'rejected' => 'status-rejected', default => 'status-partially-approved' };
                @endphp
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($customers->currentPage()-1)*$customers->perPage()+$i+1 }}</td>
                    <td style="font-family:monospace; font-size:12px; font-weight:600; color:var(--primary);">{{ $c->customer_number }}</td>
                    <td style="font-weight:600;">{{ $c->full_name }}</td>
                    <td style="font-size:12px;">{{ $c->phone_number }}</td>
                    <td style="font-size:12px;">{{ $c->id_number }}</td>
                    <td style="font-size:12px;">{{ $c->branch->name ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $c->relationshipOfficer->name ?? '—' }}</td>
                    <td style="font-size:12px;">{{ ucfirst(str_replace('_',' ',$c->employment_type ?? '—')) }}</td>
                    <td style="color:var(--success); font-weight:600;">KSH {{ number_format($c->savings_balance, 0) }}</td>
                    <td style="font-weight:600;">{{ $c->credit_score }}</td>
                    <td><span class="status {{ $sc }}">{{ ucfirst($c->status) }}</span></td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $c->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="12" style="text-align:center; padding:50px; color:var(--text-secondary);">No customers found</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $customers->firstItem() }}–{{ $customers->lastItem() }} of {{ $customers->total() }}</span>
        {{ $customers->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
