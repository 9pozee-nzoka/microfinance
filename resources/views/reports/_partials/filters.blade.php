{{--
    Reusable filter bar partial.
    Props: $action (route), $showDate (bool), $showBranch (bool), $showProduct (bool),
           $showStatus (bool), $showSearch (bool), $showExport (bool),
           $branches (collection), $products (collection), $extraSlot (bool)
--}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ $action }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">

            @if($showDate ?? true)
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select" style="width:150px;">
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-select" style="width:150px;">
            </div>
            @endif

            @if(($showBranch ?? false) && !empty($branches))
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Branch</label>
                <select name="branch" class="filter-select" style="width:160px;">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(($showProduct ?? false) && !empty($products))
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Product</label>
                <select name="product" class="filter-select" style="width:180px;">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showSearch ?? false)
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Search</label>
                <div class="search-box" style="width:200px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Loan No…">
                </div>
            </div>
            @endif

            {{ $slot ?? '' }}

            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="{{ $action }}" class="btn btn-outline" style="height:38px; padding:0 14px;" title="Reset">
                    <i class="fas fa-undo"></i>
                </a>
                @if($showExport ?? false)
                <button type="submit" name="export" value="1" class="btn btn-outline" style="height:38px; padding:0 14px; color:var(--success); border-color:var(--success);" title="Export CSV">
                    <i class="fas fa-download"></i> CSV
                </button>
                @endif
            </div>
        </div>
    </form>
</div>
