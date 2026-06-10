{{-- Shared navigation for all public pages --}}
{{-- Usage: @include('partials.public-nav', ['active' => 'home']) --}}
@php $active = $active ?? ''; @endphp
<nav id="mainNav">
    <div class="container nav-inner">
        <a href="/" class="nav-brand">
            <span class="nav-brand-icon">M</span>
            Mweela Cash
        </a>

        <ul class="nav-links">
            <li><a href="/" class="{{ $active === 'home' ? 'active' : '' }}">Home</a></li>
            <li><a href="/about" class="{{ $active === 'about' ? 'active' : '' }}">About</a></li>
            <li><a href="/our-loans" class="{{ $active === 'loans' ? 'active' : '' }}">Loans</a></li>
            <li><a href="/contact" class="{{ $active === 'contact' ? 'active' : '' }}">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <a href="/portal/login" class="nav-btn nav-btn-outline">Customer Login</a>
            <a href="/login" class="nav-btn nav-btn-primary">Staff Login</a>
        </div>

        <button class="hamburger" id="hamburgerBtn" aria-label="Menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
    <a href="/" class="{{ $active === 'home' ? 'active' : '' }}">Home</a>
    <a href="/about" class="{{ $active === 'about' ? 'active' : '' }}">About</a>
    <a href="/our-loans" class="{{ $active === 'loans' ? 'active' : '' }}">Loans</a>
    <a href="/contact" class="{{ $active === 'contact' ? 'active' : '' }}">Contact</a>
    <div class="nav-actions-mobile">
        <a href="/portal/login" class="nav-btn nav-btn-outline">Customer Login</a>
        <a href="/login" class="nav-btn nav-btn-primary">Staff Login</a>
    </div>
</div>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const btn = document.getElementById('hamburgerBtn');
        menu.classList.toggle('active');
        btn.classList.toggle('active');
        document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
    }

    // Close menu on link click
    document.querySelectorAll('.mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            toggleMobileMenu();
        });
    });

    // Nav scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 10) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
</script>
