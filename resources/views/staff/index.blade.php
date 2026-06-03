@extends('layouts.app')

@section('title', 'Staff Overview - Mweela Cash Capital')
@section('page-title', 'Staff Overview')

@section('content')

<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Staff Overview</h2>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New Staff
    </a>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-value">{{ $totalStaff }}</div>
        <div class="stat-label">Total Staff</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--success);">{{ $activeStaff }}</div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--danger);">{{ $inactiveStaff }}</div>
        <div class="stat-label">Inactive</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, ID..." class="form-control" style="max-width:280px;">
    <select name="status" class="form-control" style="max-width:160px;">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
        <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>Suspended</option>
    </select>
    <select name="branch" class="form-control" style="max-width:180px;">
        <option value="">All Branches</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" {{ request('branch')==$branch->id?'selected':'' }}>{{ $branch->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    <a href="{{ route('staff.index') }}" class="btn btn-outline">Clear</a>
</form>

{{-- Table --}}
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Employee ID</th>
                <th>Designation</th>
                <th>Branch</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone_number }}</td>
                <td>{{ $user->employee_id ?? '-' }}</td>
                <td>{{ $user->designation }}</td>
                <td>{{ $user->branch?->name ?? '-' }}</td>
                <td>
                    <span class="badge badge-{{ $user->status==='active'?'success':($user->status==='inactive'?'secondary':'danger') }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('staff.performance', $user) }}" class="btn btn-sm btn-outline">
                        <i class="fas fa-chart-line"></i> Performance
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:24px; color:var(--text-secondary);">No staff found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $staff->links() }}

@endsection
