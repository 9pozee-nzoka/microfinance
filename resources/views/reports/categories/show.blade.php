@extends('layouts.app')

@section('title', $category['name'] . ' - Mweela Cash Capital')
@section('page-title', 'Reports')

@section('styles')
<style>
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table th {
        background: #26C6DA;
        color: #fff;
        text-align: left;
        padding: 12px 14px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .report-table td {
        padding: 14px;
        border-bottom: 1px solid var(--border);
        font-size: 13px;
        color: var(--text-primary);
    }
    .report-table tr:hover { background: var(--bg); }
    .report-name { font-weight: 600; text-transform: uppercase; color: var(--text-primary); }
    .report-category-cell { color: var(--text-secondary); text-transform: uppercase; }
    .report-creator { color: var(--text-secondary); }
    .report-date { color: var(--text-secondary); }
    .view-btn {
        background: #26C6DA;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .view-btn:hover { background: #00ACC1; color: #fff; }
</style>
@endsection

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">Report Category Details</span>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom: 16px; justify-content: space-between;">
        <span class="card-title">{{ $category['name'] }}</span>
        <a href="{{ route('reports.categories.index') }}" class="btn btn-outline" style="font-size:12px;">Back to Listing</a>
    </div>

    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Report Category</th>
                    <th>Created By</th>
                    <th>Date Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($category['reports'] as $report)
                <tr>
                    <td class="report-name">{{ $report['name'] }}</td>
                    <td class="report-category-cell">{{ $category['name'] }}</td>
                    <td class="report-creator">System</td>
                    <td class="report-date">{{ now()->format('d-m-Y') }}</td>
                    <td>
                        <a href="{{ route($report['route']) }}" class="view-btn">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
