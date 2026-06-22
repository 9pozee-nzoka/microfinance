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

        {{-- Notifications --}}
        <div class="topbar-dropdown-wrap" id="notificationsDropdownWrap">
            <button class="icon-btn has-badge" type="button" id="notificationsBtn" title="Notifications">
                <i class="fas fa-bell"></i>
                @if($notificationsCount > 0)
                    <span class="icon-badge">{{ $notificationsCount > 99 ? '99+' : $notificationsCount }}</span>
                @endif
            </button>

            <div class="dropdown-panel" id="notificationsPanel">
                <div class="dropdown-header">
                    <span>Notifications</span>
                    @if($notificationsCount > 0)
                        <span class="badge badge-primary">{{ $notificationsCount }}</span>
                    @endif
                </div>

                @php $hasNotifications = $notificationsCount > 0; @endphp

                @if(! $hasNotifications)
                    <div class="dropdown-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No new notifications</p>
                    </div>
                @endif

                @if($notifications['pending_customers']->count())
                    <div class="dropdown-group">
                        <div class="dropdown-group-title">New Registrations</div>
                        @foreach($notifications['pending_customers'] as $customer)
                            <a href="{{ route('customers.new') }}" class="dropdown-item">
                                <div class="dropdown-icon bg-info"><i class="fas fa-user-plus"></i></div>
                                <div class="dropdown-content">
                                    <div class="dropdown-title">{{ $customer->full_name }}</div>
                                    <div class="dropdown-meta">New customer registration · {{ $customer->created_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($notifications['pending_loans']->count())
                    <div class="dropdown-group">
                        <div class="dropdown-group-title">Loans Pending Approval</div>
                        @foreach($notifications['pending_loans'] as $loan)
                            <a href="{{ route('loans.approve') }}" class="dropdown-item">
                                <div class="dropdown-icon bg-warning"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="dropdown-content">
                                    <div class="dropdown-title">Loan {{ $loan->loan_number }}</div>
                                    <div class="dropdown-meta">{{ $loan->customer?->full_name ?? 'Customer' }} · {{ $loan->created_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($notifications['due_today']->count())
                    <div class="dropdown-group">
                        <div class="dropdown-group-title">Due Today</div>
                        @foreach($notifications['due_today'] as $loan)
                            <a href="{{ route('loans.show', $loan) }}" class="dropdown-item">
                                <div class="dropdown-icon bg-danger"><i class="fas fa-calendar-day"></i></div>
                                <div class="dropdown-content">
                                    <div class="dropdown-title">Loan {{ $loan->loan_number }}</div>
                                    <div class="dropdown-meta">{{ $loan->customer?->full_name ?? 'Customer' }} · due today</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($notifications['due_tomorrow']->count())
                    <div class="dropdown-group">
                        <div class="dropdown-group-title">Due Tomorrow</div>
                        @foreach($notifications['due_tomorrow'] as $loan)
                            <a href="{{ route('loans.show', $loan) }}" class="dropdown-item">
                                <div class="dropdown-icon bg-success"><i class="fas fa-calendar-check"></i></div>
                                <div class="dropdown-content">
                                    <div class="dropdown-title">Loan {{ $loan->loan_number }}</div>
                                    <div class="dropdown-meta">{{ $loan->customer?->full_name ?? 'Customer' }} · due tomorrow</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($hasNotifications)
                    <div class="dropdown-footer">
                        <a href="{{ route('customers.new') }}"><i class="fas fa-users"></i> Customers</a>
                        <a href="{{ route('loans.approve') }}"><i class="fas fa-file-invoice-dollar"></i> Loans</a>
                        <a href="{{ route('collection.index') }}"><i class="fas fa-hand-holding-usd"></i> Collections</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Settings --}}
        <div class="topbar-dropdown-wrap" id="settingsDropdownWrap">
            <button class="icon-btn" type="button" id="settingsBtn" title="Settings">
                <i class="fas fa-cog"></i>
            </button>

            <div class="dropdown-panel dropdown-panel-sm" id="settingsPanel">
                <div class="dropdown-header">Settings</div>

                <div class="dropdown-group">
                    <div class="dropdown-group-title">Theme</div>
                    <div class="theme-options">
                        <button type="button" class="theme-option" data-theme="light">
                            <i class="fas fa-sun"></i> Light
                        </button>
                        <button type="button" class="theme-option" data-theme="dark">
                            <i class="fas fa-moon"></i> Dark
                        </button>
                        <button type="button" class="theme-option" data-theme="system">
                            <i class="fas fa-desktop"></i> System
                        </button>
                    </div>
                </div>

                <a href="{{ route('profile.change-password') }}" class="dropdown-item">
                    <i class="fas fa-lock"></i> Change Password
                </a>

                <form method="POST" action="{{ route('logout') }}" class="dropdown-item-form">
                    @csrf
                    <button type="submit" class="dropdown-item dropdown-item-danger">
                        <i class="fas fa-power-off"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
