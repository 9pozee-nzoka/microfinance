@extends('layouts.app')

@section('title', 'Loan Products - Mweela Cash Capital')
@section('page-title', 'Loan Products')

@section('content')

<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Loan Products</h2>
    <a href="{{ route('loan-products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<form method="GET" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or code..." class="form-control" style="max-width:280px;">
    <select name="status" class="form-control" style="max-width:160px;">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
        <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
    </select>
    <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    <a href="{{ route('loan-products.index') }}" class="btn btn-outline">Clear</a>
</form>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Interest</th>
                <th>Term (weeks)</th>
                <th>Amount Range</th>
                <th>Loans</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td><strong>{{ $product->code }}</strong></td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->interest_rate }}% {{ ucfirst($product->interest_method) }}<br><small class="text-muted">{{ $product->rates->count() }} rate row(s)</small></td>
                <td>{{ $product->min_term_weeks }} - {{ $product->max_term_weeks }}</td>
                <td>KSH {{ number_format($product->min_amount,0) }} - {{ number_format($product->max_amount,0) }}</td>
                <td>{{ $product->loans_count }}</td>
                <td>
                    <span class="badge badge-{{ $product->status==='active'?'success':'secondary' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </td>
                <td style="text-align:right;">
                    <a href="{{ route('loan-products.edit', $product) }}" class="btn btn-sm btn-outline">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding:24px; color:var(--text-secondary);">No loan products found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $products->links() }}

@endsection
