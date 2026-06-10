{{-- Shared styles for all public pages --}}
<style>
    :root {
        --primary: #00BCD4;
        --primary-dark: #0097A7;
        --primary-light: #E0F7FA;
        --success: #4CAF50;
        --warning: #FF9800;
        --danger: #F44336;
        --bg: #F5F7FA;
        --card-bg: #FFFFFF;
        --text-primary: #1A2332;
        --text-secondary: #5D6D7E;
        --border: #E8ECF1;
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
        --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
        --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--bg);
        color: var(--text-primary);
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    .container { max-width: 1140px; margin: 0 auto; padding: 0 24px; }

    /* ==================== NAVIGATION ==================== */
    nav {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: var(--transition);
    }
    nav.scrolled { box-shadow: var(--shadow-sm); }
    .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 68px;
    }
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
        font-weight: 800;
        color: var(--primary);
        text-decoration: none;
        letter-spacing: -0.5px;
    }
    .nav-brand-icon {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 18px; font-weight: 700;
    }
    .nav-links {
        display: flex;
        align-items: center;
        gap: 6px;
        list-style: none;
    }
    .nav-links a {
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: var(--radius-sm);
        transition: var(--transition);
    }
    .nav-links a:hover, .nav-links a.active {
        color: var(--primary);
        background: var(--primary-light);
    }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .nav-btn {
        padding: 9px 20px;
        border-radius: var(--radius-sm);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: var(--transition);
        white-space: nowrap;
    }
    .nav-btn-outline {
        color: var(--text-secondary);
        border: 1.5px solid var(--border);
        background: transparent;
    }
    .nav-btn-outline:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-light);
    }
    .nav-btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        box-shadow: 0 4px 14px rgba(0,188,212,0.35);
    }
    .nav-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(0,188,212,0.45);
    }

    /* Hamburger */
    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        padding: 8px;
        cursor: pointer;
        background: none;
        border: none;
        z-index: 1001;
    }
    .hamburger span {
        display: block;
        width: 24px;
        height: 2.5px;
        background: var(--text-primary);
        border-radius: 2px;
        transition: var(--transition);
    }
    .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
    .hamburger.active span:nth-child(2) { opacity: 0; }
    .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

    /* Mobile Menu */
    .mobile-menu {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(255,255,255,0.98);
        backdrop-filter: blur(20px);
        z-index: 999;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .mobile-menu.active {
        opacity: 1;
        pointer-events: all;
    }
    .mobile-menu a {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-primary);
        text-decoration: none;
        padding: 12px 24px;
        border-radius: var(--radius-sm);
        transition: var(--transition);
    }
    .mobile-menu a:hover, .mobile-menu a.active { color: var(--primary); background: var(--primary-light); }
    .mobile-menu .nav-actions-mobile {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 20px;
        width: 200px;
    }
    .mobile-menu .nav-actions-mobile .nav-btn {
        text-align: center;
        padding: 14px 24px;
        font-size: 15px;
    }

    /* ==================== HERO ==================== */
    .hero {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 100px 0 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .hero::before {
        content: '';
        position: absolute;
        top: -50%; right: -20%;
        width: 600px; height: 600px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    .hero::after {
        content: '';
        position: absolute;
        bottom: -30%; left: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        pointer-events: none;
    }
    .hero-content { position: relative; z-index: 1; }
    .hero h1, .hero h2 {
        font-size: 48px;
        font-weight: 800;
        margin-bottom: 20px;
        line-height: 1.15;
        letter-spacing: -1px;
    }
    .hero p {
        font-size: 19px;
        opacity: 0.92;
        max-width: 560px;
        margin: 0 auto 36px;
        font-weight: 400;
        line-height: 1.6;
    }
    .hero-badges {
        display: flex;
        gap: 16px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 36px;
    }
    .hero-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.15);
        padding: 8px 18px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 500;
        backdrop-filter: blur(10px);
    }
    .hero-badge i { font-size: 14px; }

    /* ==================== BUTTONS ==================== */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 32px;
        background: white;
        color: var(--primary);
        text-decoration: none;
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        box-shadow: 0 4px 14px rgba(0,188,212,0.4);
    }
    .btn-primary:hover { box-shadow: 0 8px 24px rgba(0,188,212,0.5); }
    .btn-outline {
        background: transparent;
        border: 2px solid rgba(255,255,255,0.5);
        color: white;
        box-shadow: none;
    }
    .btn-outline:hover {
        background: white;
        color: var(--primary);
        border-color: white;
        box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    }
    .btn-dark {
        background: var(--text-primary);
        color: white;
        box-shadow: 0 4px 14px rgba(26,35,50,0.3);
    }
    .btn-dark:hover { box-shadow: 0 8px 24px rgba(26,35,50,0.4); }
    .btn-sm { padding: 10px 20px; font-size: 13px; }

    /* ==================== SECTIONS ==================== */
    .section { padding: 90px 0; }
    .section-alt { background: var(--card-bg); }
    .section-header { text-align: center; margin-bottom: 56px; }
    .section-header h2 {
        font-size: 36px;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 14px;
        letter-spacing: -0.5px;
    }
    .section-header p {
        color: var(--text-secondary);
        font-size: 17px;
        max-width: 520px;
        margin: 0 auto;
        line-height: 1.6;
    }
    .section-label {
        display: inline-block;
        background: var(--primary-light);
        color: var(--primary-dark);
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 16px;
    }

    /* ==================== CARDS ==================== */
    .card {
        background: var(--card-bg);
        border-radius: var(--radius-md);
        padding: 32px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
        border: 1px solid transparent;
    }
    .card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-light);
    }
    .card-icon {
        width: 56px; height: 56px;
        background: linear-gradient(135deg, var(--primary-light) 0%, #B2EBF2 100%);
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 20px;
    }
    .card-icon i { font-size: 24px; color: var(--primary-dark); }
    .card h3 { font-size: 19px; font-weight: 700; margin-bottom: 10px; }
    .card p { font-size: 15px; color: var(--text-secondary); line-height: 1.6; }

    /* ==================== GRIDS ==================== */
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }

    /* ==================== CTA SECTION ==================== */
    .cta-section {
        background: linear-gradient(135deg, #1A2332 0%, #0F1923 100%);
        color: white;
        padding: 90px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .cta-section::before {
        content: '';
        position: absolute;
        top: -50%; left: 50%;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(0,188,212,0.1) 0%, transparent 70%);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .cta-section h2 {
        color: white;
        margin-bottom: 16px;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .cta-section p { opacity: 0.8; margin-bottom: 32px; font-size: 17px; max-width: 500px; margin-left: auto; margin-right: auto; }

    /* ==================== FOOTER ==================== */
    footer {
        background: #0A0F17;
        color: #7F8C8D;
        padding: 50px 0 30px;
        font-size: 14px;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }
    .footer-brand { font-size: 20px; font-weight: 800; color: white; margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
    .footer-brand-icon {
        width: 32px; height: 32px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 16px; font-weight: 700;
    }
    .footer-desc { color: #95A5A6; font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
    .footer-social { display: flex; gap: 12px; }
    .footer-social a {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        display: flex; align-items: center; justify-content: center;
        color: #95A5A6;
        text-decoration: none;
        transition: var(--transition);
    }
    .footer-social a:hover { background: var(--primary); color: white; }
    .footer-col h4 { color: white; font-size: 14px; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 1px; }
    .footer-col a { display: block; color: #95A5A6; text-decoration: none; padding: 6px 0; font-size: 14px; transition: var(--transition); }
    .footer-col a:hover { color: var(--primary); }
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,0.06);
        padding-top: 24px;
        text-align: center;
        color: #5D6D7E;
        font-size: 13px;
    }

    /* ==================== ANIMATIONS ==================== */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeInUp 0.6s ease forwards;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    /* ==================== MOBILE RESPONSIVE ==================== */
    @media (max-width: 768px) {
        .container { padding: 0 20px; }

        /* Nav */
        .nav-links, .nav-actions { display: none; }
        .hamburger { display: flex; }
        .mobile-menu { display: flex; }
        .nav-inner { height: 60px; }

        /* Hero */
        .hero { padding: 70px 0 60px; }
        .hero h1, .hero h2 { font-size: 30px; letter-spacing: -0.5px; }
        .hero p { font-size: 16px; max-width: 100%; padding: 0 10px; }
        .hero-badges { gap: 10px; }
        .hero-badge { font-size: 12px; padding: 6px 14px; }

        /* Buttons */
        .btn { padding: 12px 24px; font-size: 14px; width: 100%; }
        .btn-outline { margin-left: 0; margin-top: 12px; }

        /* Sections */
        .section { padding: 60px 0; }
        .section-header h2 { font-size: 26px; }
        .section-header p { font-size: 15px; }

        /* Grids */
        .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; gap: 20px; }

        /* Cards */
        .card { padding: 24px; }

        /* CTA */
        .cta-section { padding: 60px 0; }
        .cta-section h2 { font-size: 26px; }

        /* Footer */
        .footer-grid { grid-template-columns: 1fr; gap: 32px; text-align: center; }
        .footer-social { justify-content: center; }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        .grid-3 { grid-template-columns: repeat(2, 1fr); }
        .grid-4 { grid-template-columns: repeat(2, 1fr); }
        .footer-grid { grid-template-columns: 1fr 1fr; }
    }
</style>
