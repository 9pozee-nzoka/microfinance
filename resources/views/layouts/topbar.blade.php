<div class="top-bar">
    <div class="top-bar-left">
        {{-- Hamburger — visible only on mobile via CSS --}}
        <button class="hamburger" id="hamburgerBtn" type="button" aria-label="Open menu">
            <i class="fas fa-bars"></i>
        </button>

        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
    </div>

    <div class="top-actions">
        {{-- Global search — hidden on mobile, shown on tablet+ --}}
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="globalSearch" name="globalSearch"
                   placeholder="Search by phone…" autocomplete="off">
        </div>

        <button class="icon-btn" type="button" title="Notifications">
            <i class="fas fa-bell"></i>
        </button>

        <button class="icon-btn" type="button" title="Settings">
            <i class="fas fa-cog"></i>
        </button>
    </div>
</div>
