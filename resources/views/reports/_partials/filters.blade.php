{{--
    Reusable filter bar partial.
    Props: $action (route), $showDate (bool), $showBranch (bool), $showProduct (bool),
           $showStatus (bool), $showSearch (bool), $showOfficer (bool), $showRisk (bool),
           $showMethod (bool), $branches, $products, $officers, $slot (string),
           $dateLabel (string)
--}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ $action }}" id="reportFilterForm">
        <div class="filter-row scroll-x">

            @if($showDate ?? true)
            <div>
                <label class="form-label">{{ $dateLabelFrom ?? 'Start Date' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div>
                <label class="form-label">{{ $dateLabelTo ?? 'End Date' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            @endif

            @if(($showBranch ?? false) && !empty($branches))
            <div>
                <label class="form-label">Branch</label>
                <select name="branch" class="form-control">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(($showProduct ?? false) && !empty($products))
            <div>
                <label class="form-label">Product</label>
                <select name="product" class="form-control">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(($showOfficer ?? false) && !empty($officers))
            <div>
                <label class="form-label">Officer</label>
                <select name="officer" class="form-control">
                    <option value="">All Officers</option>
                    @foreach($officers as $o)
                        <option value="{{ $o->id }}" {{ request('officer') == $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showStatus ?? false)
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    @foreach($statusOptions ?? ['confirmed' => 'Confirmed', 'pending' => 'Pending', 'reversed' => 'Reversed'] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showRisk ?? false)
            <div>
                <label class="form-label">Risk</label>
                <select name="risk" class="form-control">
                    <option value="">All Risk</option>
                    @foreach(['low','medium','high','watch','default'] as $r)
                        <option value="{{ $r }}" {{ request('risk') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showMethod ?? false)
            <div>
                <label class="form-label">Payment Method</label>
                <select name="method" class="form-control">
                    <option value="">All Methods</option>
                    @foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque'] as $value => $label)
                        <option value="{{ $value }}" {{ request('method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showSearch ?? false)
            <div>
                <label class="form-label">Search</label>
                <div class="search-box" style="width:200px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Loan No…">
                </div>
            </div>
            @endif

            {!! $slot ?? '' !!}

            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="{{ $action }}" class="btn btn-outline" style="height:38px; padding:0 14px;" title="Reset">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </div>
    </form>

    {{-- Export buttons bar --}}
    <form method="GET" action="{{ $action }}" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border);">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <span style="font-size:12px; color:var(--text-secondary); font-weight:500;">Export:</span>
            @foreach(request()->except('export') as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <button type="submit" name="export" value="pdf" class="btn btn-outline" style="color:#DC2626; border-color:#DC2626; height:34px; padding:0 12px; font-size:12px;">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button type="submit" name="export" value="excel" class="btn btn-outline" style="color:#16A34A; border-color:#16A34A; height:34px; padding:0 12px; font-size:12px;">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="submit" name="export" value="csv" class="btn btn-outline" style="color:#2563EB; border-color:#2563EB; height:34px; padding:0 12px; font-size:12px;">
                <i class="fas fa-file-csv"></i> CSV
            </button>
            <button type="submit" name="export" value="word" class="btn btn-outline" style="color:#2563EB; border-color:#2563EB; height:34px; padding:0 12px; font-size:12px;">
                <i class="fas fa-file-word"></i> Word
            </button>
        </div>
    </form>
</div>
