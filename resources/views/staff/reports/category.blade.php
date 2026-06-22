@extends('layouts.app')

@section('title', $category['name'] . ' - My Reports')
@section('page-title', 'My Reports')

@section('styles')
<style>
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table th { background: #26C6DA; color: #fff; text-align: left; padding: 12px 14px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .report-table td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--text-primary); }
    .report-table tr:hover { background: var(--bg); }
    .report-name { font-weight: 600; text-transform: uppercase; }
    .view-btn { background: #26C6DA; color: #fff; border: none; border-radius: 4px; padding: 6px 14px; font-size: 12px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .view-btn:hover { background: #00ACC1; color: #fff; }
</style>
@endsection

@section('content')
<div class="page-actions">
    <a href="{{ route('staff.reports.categories') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </a>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom: 16px; justify-content: space-between;">
        <span class="card-title">{{ $category['name'] }}</span>
        <a href="{{ route('staff.reports.categories') }}" class="btn btn-outline" style="font-size:12px;">Back to Listing</a>
    </div>

    <div class="table-wrap">
        <table class="report-table">
            <thead>
                <tr><th>Name</th><th>Report Category</th><th>Action</th></tr>
            </thead>
            <tbody>
                @foreach($category['reports'] as $report)
                <tr>
                    <td class="report-name">{{ $report['name'] }}</td>
                    <td>{{ $category['name'] }}</td>
                    <td>
                        <a href="{{ route('staff.reports.show', ['category' => $category['slug'], 'report' => $report['slug']]) }}" class="view-btn">
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
