@extends('layouts.app')

@section('title', 'Report Categories - Mweela Cash Capital')
@section('page-title', 'Report Categories')

@section('styles')
<style>
    .category-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 24px;
        display: flex;
        align-items: flex-start;
        gap: 18px;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        text-decoration: none;
        color: inherit;
    }
    .category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border-color: var(--primary);
        color: inherit;
    }
    .category-icon {
        width: 56px; height: 56px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; flex-shrink: 0;
    }
    .category-name { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px; }
    .category-count { font-size: 12px; color: var(--text-secondary); }
    .category-arrow { margin-left: auto; font-size: 14px; color: var(--text-secondary); }
</style>
@endsection

@section('content')
<div class="page-actions">
    <span style="font-size:12px; color:var(--text-secondary);">Select a category to view available reports</span>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom: 16px;">
        <span class="card-title">Report Category Details</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
        @foreach($categories as $category)
        <a href="{{ route('reports.categories.show', $category['slug']) }}" class="category-card">
            <div class="category-icon" style="background: {{ $category['bg'] }}; color: {{ $category['color'] }};">
                <i class="fas {{ $category['icon'] }}"></i>
            </div>
            <div>
                <div class="category-name">{{ $category['name'] }}</div>
                <div class="category-count">{{ count($category['reports']) }} report{{ count($category['reports']) === 1 ? '' : 's' }}</div>
            </div>
            <div class="category-arrow">
                <span class="btn btn-sm btn-outline" style="padding: 6px 12px; font-size: 12px;">View Reports</span>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
