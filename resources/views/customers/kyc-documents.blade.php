@extends('layouts.app')

@section('title', 'KYC Documents Directory - Mweela Cash Capital')
@section('page-title', 'Customer KYC Documents')

@section('styles')
<style>
    .kyc-doc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    .kyc-doc-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #E0E0E0;
        overflow: hidden;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .kyc-doc-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .kyc-doc-header {
        padding: 14px 16px;
        background: #FAFAFA;
        border-bottom: 1px solid #EEEEEE;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .kyc-doc-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .kyc-doc-name {
        font-size: 14px; font-weight: 600; color: var(--text-primary);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .kyc-doc-meta {
        font-size: 11px; color: var(--text-secondary);
    }
    .kyc-doc-body {
        padding: 12px 16px;
    }
    .kyc-doc-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #F5F5F5;
    }
    .kyc-doc-item:last-child {
        border-bottom: none;
    }
    .kyc-doc-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .kyc-doc-label i {
        width: 18px;
        text-align: center;
    }
    .kyc-doc-actions {
        display: flex;
        gap: 6px;
    }
    .kyc-doc-btn {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        border: 1px solid #E0E0E0;
        background: white;
        color: var(--text-secondary);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s;
    }
    .kyc-doc-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .kyc-doc-btn.view-btn {
        border-color: var(--primary);
        color: var(--primary);
    }
    .kyc-doc-btn.view-btn:hover {
        background: var(--primary);
        color: white;
    }
    .kyc-doc-btn.missing {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }
    .kyc-doc-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    .kyc-status-verified {
        background: #E8F5E9;
        color: #2E7D32;
    }
    .kyc-status-pending {
        background: #FFF3E0;
        color: #E65100;
    }
    .kyc-doc-viewer-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .kyc-doc-viewer-overlay.active {
        display: flex;
    }
    .kyc-doc-viewer-content {
        background: white;
        border-radius: 12px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .kyc-doc-viewer-header {
        padding: 14px 20px;
        border-bottom: 1px solid #E0E0E0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .kyc-doc-viewer-header h3 {
        font-size: 16px;
        margin: 0;
    }
    .kyc-doc-viewer-body {
        flex: 1;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f5f5;
        min-height: 300px;
    }
    .kyc-doc-viewer-body img {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
    }
    .kyc-doc-viewer-body iframe {
        width: 100%;
        height: 80vh;
        border: none;
    }
    .kyc-doc-viewer-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: var(--text-secondary);
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .kyc-doc-viewer-close:hover {
        background: #f5f5f5;
        color: var(--danger);
    }
    .kyc-doc-viewer-download {
        padding: 8px 16px;
        background: var(--primary);
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    .kyc-doc-viewer-download:hover {
        opacity: 0.9;
    }
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .stats-bar .stat-box {
        background: white;
        border-radius: 10px;
        padding: 14px 16px;
        border: 1px solid #E0E0E0;
        text-align: center;
    }
    .stats-bar .stat-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--primary);
    }
    .stats-bar .stat-label {
        font-size: 11px;
        color: var(--text-secondary);
        margin-top: 4px;
    }
</style>
@endsection

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="flash-error">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

{{-- Stats --}}
<div class="stats-bar">
    <div class="stat-box">
        <div class="stat-value">{{ $totalWithKyc }}</div>
        <div class="stat-label">Customers with KYC</div>
    </div>
    <div class="stat-box">
        <div class="stat-value" style="color: var(--success);">{{ $verifiedKyc }}</div>
        <div class="stat-label">KYC Verified</div>
    </div>
    <div class="stat-box">
        <div class="stat-value" style="color: var(--warning);">{{ $pendingKyc }}</div>
        <div class="stat-label">Pending Verification</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('customers.kyc-documents') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
            <label class="form-label" style="font-size: 12px;">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, phone, ID, customer number...">
        </div>
        <div class="form-group" style="min-width: 140px; margin-bottom: 0;">
            <label class="form-label" style="font-size: 12px;">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="form-group" style="min-width: 160px; margin-bottom: 0;">
            <label class="form-label" style="font-size: 12px;">Branch</label>
            <select name="branch" class="form-control">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="min-width: 160px; margin-bottom: 0;">
            <label class="form-label" style="font-size: 12px;">Document Type</label>
            <select name="doc_type" class="form-control">
                <option value="">All Documents</option>
                <option value="id_front" {{ request('doc_type') === 'id_front' ? 'selected' : '' }}>ID Front</option>
                <option value="id_back" {{ request('doc_type') === 'id_back' ? 'selected' : '' }}>ID Back</option>
                <option value="passport_photo" {{ request('doc_type') === 'passport_photo' ? 'selected' : '' }}>Passport Photo</option>
                <option value="kra_pin" {{ request('doc_type') === 'kra_pin' ? 'selected' : '' }}>KRA PIN</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ route('customers.kyc-documents') }}" class="btn btn-outline" style="padding: 8px 14px; font-size: 13px;">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

{{-- Document Cards --}}
<div class="kyc-doc-grid">
    @forelse($customers as $customer)
    @php
        $initials = collect(explode(' ', $customer->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
        $colors = ['#00BCD4','#4CAF50','#FF9800','#9C27B0','#F44336','#3F51B5','#009688','#795548'];
        $avatarBg = $colors[abs(crc32($customer->customer_number)) % count($colors)];
    @endphp
    <div class="kyc-doc-card">
        <div class="kyc-doc-header">
            <div class="kyc-doc-avatar" style="background: {{ $avatarBg }};">{{ $initials }}</div>
            <div style="flex: 1; min-width: 0;">
                <div class="kyc-doc-name">{{ $customer->full_name }}</div>
                <div class="kyc-doc-meta">
                    {{ $customer->customer_number }} · {{ $customer->phone_number }}
                </div>
            </div>
            @if($customer->kyc_verified_at)
                <span class="kyc-doc-status kyc-status-verified"><i class="fas fa-check"></i> Verified</span>
            @else
                <span class="kyc-doc-status kyc-status-pending"><i class="fas fa-clock"></i> Pending</span>
            @endif
        </div>
        <div class="kyc-doc-body">
            @php
                $idFrontExists = $customer->id_front_path && Storage::disk('public')->exists($customer->id_front_path);
                $idBackExists = $customer->id_back_path && Storage::disk('public')->exists($customer->id_back_path);
                $photoExists = $customer->passport_photo_path && Storage::disk('public')->exists($customer->passport_photo_path);
                $kraExists = $customer->kra_pin_path && Storage::disk('public')->exists($customer->kra_pin_path);
            @endphp
            {{-- ID Front --}}
            <div class="kyc-doc-item">
                <div class="kyc-doc-label">
                    <i class="fas fa-id-card" style="color: #2196F3;"></i>
                    ID Front
                </div>
                <div class="kyc-doc-actions">
                    @if($idFrontExists)
                        <button type="button" class="kyc-doc-btn view-btn" onclick="openKycViewer('{{ Storage::url($customer->id_front_path) }}', 'ID Front - {{ $customer->full_name }}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="{{ Storage::url($customer->id_front_path) }}" download class="kyc-doc-btn">
                            <i class="fas fa-download"></i>
                        </a>
                    @elseif($customer->id_front_path)
                        <span class="kyc-doc-btn missing" title="File record exists but file is missing on server"><i class="fas fa-unlink"></i> Not Found</span>
                    @else
                        <span class="kyc-doc-btn missing">Missing</span>
                    @endif
                </div>
            </div>
            {{-- ID Back --}}
            <div class="kyc-doc-item">
                <div class="kyc-doc-label">
                    <i class="fas fa-id-card" style="color: #4CAF50;"></i>
                    ID Back
                </div>
                <div class="kyc-doc-actions">
                    @if($idBackExists)
                        <button type="button" class="kyc-doc-btn view-btn" onclick="openKycViewer('{{ Storage::url($customer->id_back_path) }}', 'ID Back - {{ $customer->full_name }}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="{{ Storage::url($customer->id_back_path) }}" download class="kyc-doc-btn">
                            <i class="fas fa-download"></i>
                        </a>
                    @elseif($customer->id_back_path)
                        <span class="kyc-doc-btn missing" title="File record exists but file is missing on server"><i class="fas fa-unlink"></i> Not Found</span>
                    @else
                        <span class="kyc-doc-btn missing">Missing</span>
                    @endif
                </div>
            </div>
            {{-- Passport Photo --}}
            <div class="kyc-doc-item">
                <div class="kyc-doc-label">
                    <i class="fas fa-camera" style="color: #FF9800;"></i>
                    Passport Photo
                </div>
                <div class="kyc-doc-actions">
                    @if($photoExists)
                        <button type="button" class="kyc-doc-btn view-btn" onclick="openKycViewer('{{ Storage::url($customer->passport_photo_path) }}', 'Passport Photo - {{ $customer->full_name }}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="{{ Storage::url($customer->passport_photo_path) }}" download class="kyc-doc-btn">
                            <i class="fas fa-download"></i>
                        </a>
                    @elseif($customer->passport_photo_path)
                        <span class="kyc-doc-btn missing" title="File record exists but file is missing on server"><i class="fas fa-unlink"></i> Not Found</span>
                    @else
                        <span class="kyc-doc-btn missing">Missing</span>
                    @endif
                </div>
            </div>
            {{-- KRA PIN --}}
            <div class="kyc-doc-item">
                <div class="kyc-doc-label">
                    <i class="fas fa-file-alt" style="color: #9C27B0;"></i>
                    KRA PIN
                </div>
                <div class="kyc-doc-actions">
                    @if($kraExists)
                        <button type="button" class="kyc-doc-btn view-btn" onclick="openKycViewer('{{ Storage::url($customer->kra_pin_path) }}', 'KRA PIN - {{ $customer->full_name }}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="{{ Storage::url($customer->kra_pin_path) }}" download class="kyc-doc-btn">
                            <i class="fas fa-download"></i>
                        </a>
                    @elseif($customer->kra_pin_path)
                        <span class="kyc-doc-btn missing" title="File record exists but file is missing on server"><i class="fas fa-unlink"></i> Not Found</span>
                    @else
                        <span class="kyc-doc-btn missing">Missing</span>
                    @endif
                </div>
            </div>
        </div>
        <div style="padding: 10px 16px; background: #FAFAFA; border-top: 1px solid #EEEEEE; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 11px; color: var(--text-secondary);">
                <i class="fas fa-building"></i> {{ $customer->branch->name ?? '—' }}
            </span>
            <a href="{{ route('customers.profile', $customer) }}" class="kyc-doc-btn view-btn" style="font-size: 11px;">
                <i class="fas fa-user"></i> Profile
            </a>
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1;">
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 48px; color: #BDBDBD;"></i>
            <p>No KYC documents found</p>
            <small>No customers have uploaded KYC documents matching your filters.</small>
        </div>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
<div style="margin-top: 24px;">
    {{ $customers->links() }}
</div>

{{-- Document Viewer Modal --}}
<div class="kyc-doc-viewer-overlay" id="kycViewerOverlay" onclick="closeKycViewer(event)">
    <div class="kyc-doc-viewer-content" onclick="event.stopPropagation()">
        <div class="kyc-doc-viewer-header">
            <h3 id="kycViewerTitle">Document</h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="#" id="kycViewerDownload" class="kyc-doc-viewer-download" download>
                    <i class="fas fa-download"></i> Download
                </a>
                <button type="button" class="kyc-doc-viewer-close" onclick="closeKycViewer()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="kyc-doc-viewer-body" id="kycViewerBody">
            {{-- Content loaded dynamically --}}
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openKycViewer(url, title) {
    const overlay = document.getElementById('kycViewerOverlay');
    const body = document.getElementById('kycViewerBody');
    const titleEl = document.getElementById('kycViewerTitle');
    const downloadEl = document.getElementById('kycViewerDownload');

    titleEl.textContent = title;
    downloadEl.href = url;

    // Determine if image or PDF
    const isPdf = url.toLowerCase().endsWith('.pdf');
    if (isPdf) {
        body.innerHTML = '<iframe src="' + url + '"></iframe>';
    } else {
        body.innerHTML = '<img src="' + url + '" alt="' + title + '">';
    }

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeKycViewer(event) {
    if (event && event.target !== document.getElementById('kycViewerOverlay')) {
        return;
    }
    const overlay = document.getElementById('kycViewerOverlay');
    overlay.classList.remove('active');
    document.getElementById('kycViewerBody').innerHTML = '';
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeKycViewer();
    }
});
</script>
@endsection
