{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - Mweela Cash Capital')
@section('page-title', 'Dashboard')

@section('content')
{{-- Filter Bar --}}
<form method="GET" action="{{ route('dashboard') }}" class="filter-bar">
    <select class="filter-select" id="officerFilter" name="officer" {{ $isPureOfficer ? 'disabled' : '' }}>
        <option value="">Relationship Officer</option>
        @foreach($officers as $officer)
            <option value="{{ $officer->id }}" {{ (string) $selectedOfficer === (string) $officer->id ? 'selected' : '' }}>{{ $officer->name }}</option>
        @endforeach
    </select>
    @if($isPureOfficer)
        <input type="hidden" name="officer" value="{{ $selectedOfficer }}">
    @endif

    <select class="filter-select" id="branchFilter" name="branch">
        <option value="">Branch</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" {{ (string) $selectedBranch === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Search
    </button>

    @if($selectedOfficer || $selectedBranch)
        <a href="{{ route('dashboard') }}" class="btn btn-outline">
            <i class="fas fa-times"></i> Clear
        </a>
    @endif
</form>

{{-- Actionable Loan Cards --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Loans Due Today --}}
    <div class="card" style="cursor: pointer; border-left: 4px solid var(--warning);" onclick="toggleCard('dueTodayList')">
        <div class="card-header">
            <span class="card-title">Loans Due Today</span>
            <span class="badge badge-warning">{{ $loansDueTodayList->count() }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--warning);">{{ $loansDueTodayList->count() }}</div>
        <div class="metric-label">KSH {{ number_format($loansDueTodayAmount, 0) }} due today</div>
        <div style="margin-top: 10px; font-size: 12px; color: var(--text-secondary);">
            <i class="fas fa-chevron-down" id="dueTodayList-icon"></i> Click to view &amp; collect
        </div>
    </div>

    {{-- Prepay Loans (Due Tomorrow) --}}
    <div class="card" style="cursor: pointer; border-left: 4px solid var(--success);" onclick="toggleCard('prepayLoansList')">
        <div class="card-header">
            <span class="card-title">Prepay Loans</span>
            <span class="badge badge-success">{{ $loansDueTomorrowList->count() }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--success);">{{ $loansDueTomorrowList->count() }}</div>
        <div class="metric-label">Loans due tomorrow that can be paid today</div>
        <div style="margin-top: 10px; font-size: 12px; color: var(--text-secondary);">
            <i class="fas fa-chevron-down" id="prepayLoansList-icon"></i> Click to view &amp; collect
        </div>
    </div>
</div>

{{-- Loans Due Today List --}}
<div id="dueTodayList" style="display: none; margin-bottom: 20px;">
    <div class="card">
        <div class="card-header" style="margin-bottom: 12px;">
            <span class="card-title">Loans Due Today</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Loan No.</th>
                        <th>Phone</th>
                        <th>Amount Due</th>
                        <th>Outstanding</th>
                        @if($canFilter)
                            <th>Officer</th>
                            <th>Branch</th>
                        @endif
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loansDueTodayList as $loan)
                    <tr>
                        <td>{{ $loan->customer?->full_name ?? 'N/A' }}</td>
                        <td>{{ $loan->loan_number }}</td>
                        <td>{{ $loan->customer?->phone_number ?? 'N/A' }}</td>
                        <td>KSH {{ number_format($loan->weekly_installment, 0) }}</td>
                        <td>KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                        @if($canFilter)
                            <td>{{ $loan->relationshipOfficer?->name ?? 'N/A' }}</td>
                            <td>{{ $loan->branch?->name ?? 'N/A' }}</td>
                        @endif
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                <button type="button" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="event.stopPropagation(); openRecordPaymentModal({{ $loan->id }}, {{ $loan->customer_id }}, '{{ addslashes($loan->customer?->full_name ?? '') }}', {{ $loan->weekly_installment }}, '{{ $loan->customer?->phone_number ?? '' }}')">
                                    <i class="fas fa-money-bill-wave"></i> <span class="btn-text">Record Payment</span>
                                </button>
                                <button type="button" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="event.stopPropagation(); openStkModal({{ $loan->id }}, '{{ addslashes($loan->customer?->full_name ?? '') }}', '{{ $loan->customer?->phone_number ?? '' }}', {{ $loan->weekly_installment }}, {{ $loan->outstanding_balance }}, '{{ route('mpesa.stk.push', $loan) }}')">
                                    <i class="fas fa-mobile-alt"></i> <span class="btn-text">Request Payment</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canFilter ? 8 : 6 }}" style="text-align: center;">
                            <div class="empty-state">No loans due today</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Prepay Loans List --}}
<div id="prepayLoansList" style="display: none; margin-bottom: 20px;">
    <div class="card">
        <div class="card-header" style="margin-bottom: 12px;">
            <span class="card-title">Prepay Loans (Due Tomorrow)</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Loan No.</th>
                        <th>Phone</th>
                        <th>Tomorrow's Installment</th>
                        <th>Outstanding</th>
                        @if($canFilter)
                            <th>Officer</th>
                            <th>Branch</th>
                        @endif
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loansDueTomorrowList as $loan)
                    <tr>
                        <td>{{ $loan->customer?->full_name ?? 'N/A' }}</td>
                        <td>{{ $loan->loan_number }}</td>
                        <td>{{ $loan->customer?->phone_number ?? 'N/A' }}</td>
                        <td>KSH {{ number_format($loan->weekly_installment, 0) }}</td>
                        <td>KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                        @if($canFilter)
                            <td>{{ $loan->relationshipOfficer?->name ?? 'N/A' }}</td>
                            <td>{{ $loan->branch?->name ?? 'N/A' }}</td>
                        @endif
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap;">
                                <button type="button" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="event.stopPropagation(); openRecordPaymentModal({{ $loan->id }}, {{ $loan->customer_id }}, '{{ addslashes($loan->customer?->full_name ?? '') }}', {{ $loan->weekly_installment }}, '{{ $loan->customer?->phone_number ?? '' }}')">
                                    <i class="fas fa-money-bill-wave"></i> <span class="btn-text">Record Payment</span>
                                </button>
                                <button type="button" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;"
                                    onclick="event.stopPropagation(); openStkModal({{ $loan->id }}, '{{ addslashes($loan->customer?->full_name ?? '') }}', '{{ $loan->customer?->phone_number ?? '' }}', {{ $loan->weekly_installment }}, {{ $loan->outstanding_balance }}, '{{ route('mpesa.stk.push', $loan) }}')">
                                    <i class="fas fa-mobile-alt"></i> <span class="btn-text">Request Payment</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canFilter ? 8 : 6 }}" style="text-align: center;">
                            <div class="empty-state">No loans due tomorrow</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pending Actions --}}
<div class="grid-2" style="margin-bottom: 20px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Loans Pending Approvals</span>
            <span class="badge badge-warning">{{ $pendingApprovals }}</span>
        </div>
        <div class="metric-value" style="font-size: 36px;">{{ $pendingApprovals }}</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <span class="card-title">Loans Pending Disbursement</span>
            <span class="badge badge-primary">{{ $pendingDisbursement }}</span>
        </div>
        <div class="metric-value" style="font-size: 36px;">{{ $pendingDisbursement }}</div>
    </div>
</div>

{{-- Overdue & Risk Summary --}}
<div class="grid-3" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-header">
            <span class="card-title">Overdue Loans</span>
            <span class="badge badge-danger">{{ $overdueLoansCount }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--danger);">{{ $overdueLoansCount }}</div>
        <div class="metric-label">KSH {{ number_format($overdueAmount, 0) }} outstanding</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="card-header">
            <span class="card-title">Portfolio at Risk (PAR30)</span>
            <span class="badge badge-warning">{{ $parPercentage }}%</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--warning);">{{ $parPercentage }}%</div>
        <div class="metric-label">KSH {{ number_format($portfolioAtRisk ?? 0, 0) }} at risk</div>
    </div>
    <div class="card" style="border-left: 4px solid #6A1B9A;">
        <div class="card-header">
            <span class="card-title">Non-Performing Loans</span>
            <span class="badge" style="background:#6A1B9A; color:white;">{{ $nplCount }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: #6A1B9A;">{{ $nplCount }}</div>
        <div class="metric-label">KSH {{ number_format($nplAmount, 0) }} NPL amount</div>
    </div>
</div>

{{-- Portfolio & Performance --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Portfolio --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-primary">Portfolio</span>
        </div>
        <div class="grid-2" style="gap: 15px; grid-template-columns: repeat(2,1fr);">
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--primary);">{{ $totalCustomers }}</div>
                <div class="metric-label">Total Customers</div>
            </div>
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--primary);">{{ $activeCustomers }}</div>
                <div class="metric-label">Active Customers</div>
            </div>
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--success);">{{ $inactiveCustomers }}</div>
                <div class="metric-label">Inactive Customers</div>
            </div>
            <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 8px; padding: 15px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">OLB KSH</div>
                <div style="font-size: 24px; font-weight: 700;">{{ number_format($olb, 0) }}</div>
            </div>
        </div>
    </div>

    {{-- Performance --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-primary">Performance</span>
        </div>
        <div class="circle-card-inner">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#00BCD4" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="{{ 326.73 * (1 - min($fundedPercentage, 100) / 100) }}"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--primary);">{{ $fundedPercentage }}%</div>
                    <div class="circle-label">% Funded</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Disbursed Loans</div>
                    <div style="font-size: 20px; font-weight: 600;">{{ $disbursedLoans }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary);">Disbursed Amount</div>
                    <div style="font-size: 20px; font-weight: 600;">KSH {{ number_format($disbursedAmount, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Collection & Risk --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Collection --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-success">Collection</span>
        </div>
        <div class="circle-card-inner">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#28A745" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="{{ 326.73 * (1 - min($collectionRate, 100) / 100) }}"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--success);">{{ $collectionRate }}%</div>
                    <div class="circle-label">% Collection Rate</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Loans Due Today</div>
                    <div style="font-size: 16px; font-weight: 600;">KSH {{ number_format($loansDueTodayAmount, 0) }}</div>
                </div>
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Collections</div>
                    <div style="font-size: 16px; font-weight: 600; color: var(--success);">KSH {{ number_format($collectionsToday, 2) }}</div>
                </div>
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Loans Due Count</div>
                    <div style="font-size: 16px; font-weight: 600;">{{ $loansDueCount }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary);">Prepaid Loans</div>
                    <div style="font-size: 16px; font-weight: 600;">KSH {{ number_format($prepaidLoansAmount, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Risk --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-danger">Risk</span>
        </div>
        <div class="circle-card-inner">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#DC3545" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="{{ 326.73 * (1 - min($parPercentage, 100) / 100) }}"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--danger);">{{ $parPercentage }}%</div>
                    <div class="circle-label">% Portfolio at Risk</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Total Arrears</div>
                    <div style="font-size: 16px; font-weight: 600; color: var(--danger);">KSH {{ number_format($totalArrears, 0) }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary);">Arrears Collected Today</div>
                    <div style="font-size: 16px; font-weight: 600; color: var(--success);">KSH {{ number_format($arrearsCollectedToday, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- NPL Breakdown --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Non-Performing Loan Breakdown</span>
        <span class="form-hint">Includes defaulted and written-off loans</span>
    </div>
    <div class="grid-4">
        <div>
            <div class="metric-label">NPL Count</div>
            <div class="metric-value" style="font-size: 24px;">{{ $nplCount }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Principal</div>
            <div class="metric-value" style="font-size: 24px;">KSH {{ number_format($nplPrincipal, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Outstanding</div>
            <div class="metric-value" style="font-size: 24px;">KSH {{ number_format($nplAmount, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Ratio</div>
            <div class="metric-value" style="font-size: 24px;">{{ $totalPortfolio > 0 ? round(($nplAmount / $totalPortfolio) * 100, 1) : 0 }}%</div>
        </div>
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Today's Transactions</span>
        <a href="{{ route('transactions.processed') }}" class="btn btn-outline" style="font-size: 12px;">View All</a>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Names</th>
                <th>Transaction Type</th>
                <th>Transaction ID</th>
                <th>Amount Received</th>
                <th>Date Captured</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentTransactions as $index => $txn)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $txn->customer?->full_name ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}</span>
                </td>
                <td>{{ $txn->transaction_number }}</td>
                <td style="font-weight: 600;">{{ number_format($txn->amount, 0) }}</td>
                <td style="color: var(--text-secondary);">{{ $txn->created_at->format('d-M-y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">No transactions today</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Record Payment Modal --}}
<div id="recordPaymentModal" class="modal-overlay" onclick="if(event.target===this)closeModal('recordPaymentModal')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-money-bill-wave" style="color:var(--primary);"></i> Record Payment</div>
            <button type="button" class="modal-close" onclick="closeModal('recordPaymentModal')">&times;</button>
        </div>

        <div id="recordPaymentInfo" style="background:#F8FAFC; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:13px; border:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Customer</span>
                <strong id="rpCustomerName">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-secondary);">Suggested Amount</span>
                <strong style="color:var(--primary);" id="rpSuggestedAmount">-</strong>
            </div>
        </div>

        <form id="recordPaymentForm" method="POST" action="{{ route('transactions.store') }}">
            @csrf
            <input type="hidden" name="transaction_type" value="loan_repayment">
            <input type="hidden" name="customer_id" id="rpCustomerId">
            <input type="hidden" name="loan_id" id="rpLoanId">

            <div class="form-group">
                <label class="form-label">Payment Source <span style="color:var(--danger)">*</span></label>
                <select class="form-control" name="source" id="rpSource" onchange="togglePaymentFields()" required>
                    <option value="cash" selected>Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank">Bank</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Amount (KSH) <span style="color:var(--danger)">*</span></label>
                <input type="number" class="form-control" name="amount" id="rpAmount" min="1" step="0.01" required>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Date <span style="color:var(--danger)">*</span></label>
                <input type="date" class="form-control" name="payment_date" id="rpPaymentDate" value="{{ today()->format('Y-m-d') }}" required>
            </div>

            <div class="form-group" id="rpMpesaRefGroup" style="display:none;">
                <label class="form-label">M-Pesa Receipt <span style="color:var(--danger)">*</span></label>
                <input type="text" class="form-control" name="mpesa_receipt" id="rpMpesaReceipt">
            </div>

            <div class="form-group" id="rpBankRefGroup" style="display:none;">
                <label class="form-label">Bank Reference <span style="color:var(--danger)">*</span></label>
                <input type="text" class="form-control" name="bank_reference" id="rpBankReference">
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="rpNotes" rows="2"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('recordPaymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Request Payment (STK) Modal --}}
<div id="stkModal" class="modal-overlay" onclick="if(event.target===this)closeModal('stkModal')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-mobile-alt" style="color:var(--primary);"></i> Request M-Pesa Payment</div>
            <button type="button" class="modal-close" onclick="closeModal('stkModal')">&times;</button>
        </div>

        <div style="background:#E3F2FD; border:1px solid #90CAF9; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:12px; color:#1565C0;">
            <i class="fas fa-info-circle"></i>
            An STK push will be sent to the customer's phone. They will see a payment prompt and enter their M-Pesa PIN to complete the payment.
        </div>

        <div style="background:#F8FAFC; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:13px; border:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Customer</span>
                <strong id="stkCustomerName">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-secondary);">Outstanding Balance</span>
                <strong style="color:var(--primary);" id="stkOutstanding">-</strong>
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Phone Number <span style="color:var(--danger)">*</span></label>
            <input type="text" id="stkPhone" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px; font-family:monospace;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Amount (KSH) <span style="color:var(--danger)">*</span></label>
            <input type="number" id="stkAmount" min="1" step="0.01" style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:6px; font-size:13px;">
        </div>

        <div id="stkResult" style="display:none; margin-bottom:14px;"></div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="btn btn-outline" onclick="closeModal('stkModal')">Cancel</button>
            <button type="button" class="btn btn-primary" id="stkBtn" onclick="initiateStkPush()">
                <i class="fas fa-mobile-alt"></i> Send STK Push
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Toggle expandable cards
    function toggleCard(id) {
        const el = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        if (el.style.display === 'none') {
            el.style.display = 'block';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            el.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    // Modal helpers
    function openModal(id) {
        document.getElementById(id).classList.add('show');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    // Record Payment modal
    function openRecordPaymentModal(loanId, customerId, customerName, amount, phone) {
        document.getElementById('rpCustomerId').value = customerId;
        document.getElementById('rpLoanId').value = loanId;
        document.getElementById('rpCustomerName').textContent = customerName || 'N/A';
        document.getElementById('rpSuggestedAmount').textContent = amount ? 'KSH ' + Number(amount).toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 2}) : '-';
        document.getElementById('rpAmount').value = amount ? Number(amount).toFixed(2) : '';
        document.getElementById('rpSource').value = 'cash';
        document.getElementById('rpMpesaReceipt').value = '';
        document.getElementById('rpBankReference').value = '';
        document.getElementById('rpNotes').value = '';
        togglePaymentFields();
        openModal('recordPaymentModal');
    }

    function togglePaymentFields() {
        const source = document.getElementById('rpSource').value;
        document.getElementById('rpMpesaRefGroup').style.display = source === 'mpesa' ? 'block' : 'none';
        document.getElementById('rpBankRefGroup').style.display = source === 'bank' ? 'block' : 'none';
        document.getElementById('rpMpesaReceipt').required = source === 'mpesa';
        document.getElementById('rpBankReference').required = source === 'bank';
    }

    // STK Push modal
    let currentStkUrl = '';
    let stkPollInterval = null;

    function openStkModal(loanId, customerName, phone, amount, outstanding, url) {
        currentStkUrl = url;
        document.getElementById('stkCustomerName').textContent = customerName || 'N/A';
        document.getElementById('stkOutstanding').textContent = outstanding ? 'KSH ' + Number(outstanding).toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 2}) : '-';
        document.getElementById('stkPhone').value = phone || '';
        document.getElementById('stkAmount').value = amount ? Number(amount).toFixed(2) : '';
        document.getElementById('stkResult').style.display = 'none';
        document.getElementById('stkResult').innerHTML = '';
        const btn = document.getElementById('stkBtn');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Send STK Push';
        if (stkPollInterval) clearInterval(stkPollInterval);
        openModal('stkModal');
    }

    function initiateStkPush() {
        const phone = document.getElementById('stkPhone').value.trim();
        const amount = document.getElementById('stkAmount').value;
        const btn = document.getElementById('stkBtn');
        const result = document.getElementById('stkResult');

        if (!phone || !amount) { alert('Please enter phone and amount.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
        result.style.display = 'none';

        fetch(currentStkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ phone, amount }),
        })
        .then(r => r.json())
        .then(data => {
            result.style.display = 'block';
            if (data.success) {
                result.innerHTML = `<div style="background:#E3F2FD;border:1px solid #90CAF9;border-radius:8px;padding:12px;font-size:13px;color:#1565C0;">
                    <i class="fas fa-mobile-alt"></i> ${data.message}
                    <div style="margin-top:6px;font-size:11px;opacity:0.8;">Waiting for customer to complete payment…</div>
                </div>`;
                btn.innerHTML = '<i class="fas fa-clock"></i> Waiting…';
                if (data.mpesa_txn_id) {
                    stkPollInterval = setInterval(() => pollStkStatus(data.mpesa_txn_id, btn, result), 5000);
                }
            } else {
                result.innerHTML = `<div style="background:#FFEBEE;border:1px solid #FFCDD2;border-radius:8px;padding:12px;font-size:13px;color:#C62828;">
                    <i class="fas fa-exclamation-circle"></i> ${data.message}
                </div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Send STK Push';
            }
        })
        .catch((err) => {
            console.error(err);
            result.style.display = 'block';
            result.innerHTML = `<div style="background:#FFEBEE;border:1px solid #FFCDD2;border-radius:8px;padding:12px;font-size:13px;color:#C62828;">
                <i class="fas fa-exclamation-circle"></i> Network error. Please try again.
            </div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Send STK Push';
        });
    }

    function pollStkStatus(txnId, btn, result) {
        fetch(`/mpesa/transactions/${txnId}/status`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'completed') {
                clearInterval(stkPollInterval);
                result.innerHTML = `<div style="background:#E8F5E9;border:1px solid #A5D6A7;border-radius:8px;padding:12px;font-size:13px;color:#2E7D32;">
                    <i class="fas fa-check-circle"></i> Payment received! Receipt: <strong>${data.receipt}</strong>
                </div>`;
                btn.innerHTML = '<i class="fas fa-check"></i> Payment Received';
                setTimeout(() => location.reload(), 2000);
            } else if (data.status === 'failed') {
                clearInterval(stkPollInterval);
                result.innerHTML = `<div style="background:#FFEBEE;border:1px solid #FFCDD2;border-radius:8px;padding:12px;font-size:13px;color:#C62828;">
                    <i class="fas fa-times-circle"></i> Payment failed: ${data.result_desc}
                </div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-mobile-alt"></i> Send STK Push';
            }
        });
    }

    // Animate circle progress on load
    document.addEventListener('DOMContentLoaded', function() {
        const circles = document.querySelectorAll('.circle-fill');
        circles.forEach(circle => {
            const offset = circle.getAttribute('stroke-dashoffset');
            circle.style.strokeDashoffset = offset;
            setTimeout(() => {
                circle.style.transition = 'stroke-dashoffset 1s ease';
            }, 100);
        });
    });
</script>
@endsection
