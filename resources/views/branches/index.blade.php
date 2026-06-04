@extends('layouts.app')

@section('title', 'Branches - Mweela Cash Capital')
@section('page-title', 'Branch Management')

@section('content')

<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Branches</h2>
    <a href="{{ route('branches.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Branch
    </a>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-value">{{ $totalBranches }}</div>
        <div class="stat-label">Total Branches</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--success);">{{ $activeBranches }}</div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--danger);">{{ $inactiveBranches }}</div>
        <div class="stat-label">Inactive</div>
    </div>
</div>

{{-- Branches Table --}}
<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                <tr>
                    <td><code>{{ $branch->code }}</code></td>
                    <td><strong>{{ $branch->name }}</strong></td>
                    <td>{{ $branch->location }}</td>
                    <td>{{ $branch->phone ?? '-' }}</td>
                    <td>{{ $branch->email ?? '-' }}</td>
                    <td>
                        <span class="badge badge-{{ $branch->status==='active'?'success':'secondary' }}">
                            {{ ucfirst($branch->status) }}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('branches.destroy', $branch) }}" style="display:inline;" onsubmit="return confirm('Delete branch {{ $branch->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger);">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                        <i class="fas fa-building" style="font-size:36px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                        No branches found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($branches->hasPages())
    <div style="padding:14px 4px 4px; border-top:1px solid var(--border); margin-top:8px;">
        {{ $branches->links() }}
    </div>
    @endif
</div>

@endsection
