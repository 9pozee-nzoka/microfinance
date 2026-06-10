<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn about Mweela Cash Capital - a trusted microfinance institution in Mutomo, Kitui County, Kenya. We provide quick business loans, personal loans, and SME financing.">
    <meta name="keywords" content="about Mweela Cash Capital, microfinance Kenya, Mutomo loans, Kitui County, SME financing, business loans Kenya">
    <meta name="author" content="Mweela Cash Capital">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://mweelacredit.co.ke/about">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://mweelacredit.co.ke/about">
    <meta property="og:title" content="About Us - Mweela Cash Capital">
    <meta property="og:description" content="Trusted microfinance institution in Mutomo, Kitui County. Providing quick business loans, personal loans, and SME financing across Kenya.">
    <meta property="og:image" content="https://mweelacredit.co.ke/images/og-image.jpg">
    <meta property="og:site_name" content="Mweela Cash Capital">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us - Mweela Cash Capital">
    <meta name="twitter:description" content="Trusted microfinance institution in Mutomo, Kitui County. Providing quick business loans, personal loans, and SME financing across Kenya.">
    <meta name="twitter:image" content="https://mweelacredit.co.ke/images/og-image.jpg">

    <title>About Us - Mweela Cash Capital</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#00BCD4">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('partials.public-styles')

    <style>
        .story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .story-text h3 { font-size: 28px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px; }
        .story-text p { color: var(--text-secondary); margin-bottom: 16px; font-size: 16px; line-height: 1.7; }
        .story-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .stat-card { background: white; padding: 28px; border-radius: var(--radius-md); text-align: center; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .stat-card .number { font-size: 36px; font-weight: 800; color: var(--primary); }
        .stat-card .label { font-size: 14px; color: var(--text-secondary); margin-top: 6px; }

        .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; }
        .value-card { background: white; padding: 32px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); transition: var(--transition); border: 1px solid transparent; }
        .value-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--primary-light); }
        .value-card i { font-size: 32px; color: var(--primary); margin-bottom: 16px; width: 56px; height: 56px; background: var(--primary-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; }
        .value-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .value-card p { font-size: 15px; color: var(--text-secondary); line-height: 1.6; }

        .location-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
        .location-info h3 { font-size: 24px; font-weight: 700; margin-bottom: 24px; }
        .location-info p { color: var(--text-secondary); margin-bottom: 16px; display: flex; align-items: center; gap: 12px; font-size: 15px; }
        .location-info i { color: var(--primary); width: 20px; font-size: 16px; }
        .location-map { background: linear-gradient(135deg, #E8ECF1 0%, #F5F7FA 100%); border-radius: var(--radius-md); height: 320px; display: flex; align-items: center; justify-content: center; flex-direction: column; color: var(--text-secondary); box-shadow: inset 0 2px 8px rgba(0,0,0,0.04); }
        .location-map i { font-size: 48px; color: var(--primary); margin-bottom: 12px; opacity: 0.5; }

        @media (max-width: 768px) {
            .story-grid, .location-grid { grid-template-columns: 1fr; gap: 40px; }
            .story-stats { grid-template-columns: 1fr 1fr; }
            .story-text h3 { font-size: 22px; }
        }
    </style>

    @php
    $aboutJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'AboutPage',
                '@id' => 'https://mweelacredit.co.ke/about#aboutpage',
                'url' => 'https://mweelacredit.co.ke/about',
                'name' => 'About Mweela Cash Capital',
                'isPartOf' => ['@id' => 'https://mweelacredit.co.ke/#website'],
                'about' => ['@id' => 'https://mweelacredit.co.ke/#organization']
            ],
            [
                '@type' => 'Organization',
                '@id' => 'https://mweelacredit.co.ke/#organization',
                'name' => 'Mweela Cash Capital',
                'url' => 'https://mweelacredit.co.ke',
                'logo' => 'https://mweelacredit.co.ke/images/logo.png',
                'description' => 'Trusted microfinance institution providing quick business loans, personal loans, and SME financing in Kenya.',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Mutomo Town',
                    'addressLocality' => 'Mutomo',
                    'addressRegion' => 'Kitui County',
                    'addressCountry' => 'KE'
                ],
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+254-700-000-001',
                    'contactType' => 'customer service',
                    'areaServed' => 'KE',
                    'availableLanguage' => ['English', 'Swahili']
                ]
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $aboutJsonLd !!}</script>
</head>
<body>
    @include('partials.public-nav', ['active' => 'about'])

    <section class="hero">
        <div class="container hero-content">
            <h1>About Mweela Cash Capital</h1>
            <p>Your trusted microfinance partner, empowering individuals and businesses across Kenya with accessible financial solutions.</p>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="story-grid">
                <div class="story-text">
                    <span class="section-label">Our Story</span>
                    <h3>Finance for Everyone</h3>
                    <p>Mweela Cash Capital was founded with a simple mission: to make financial services accessible to everyone in Kenya, especially those underserved by traditional banks. Based in Mutomo, Kitui County, we understand the unique challenges faced by local entrepreneurs and families.</p>
                    <p>We believe that access to credit should not be a privilege. Whether you are a small business owner looking to expand, a farmer needing seasonal capital, or a family facing unexpected expenses, Mweela Cash Capital is here to help.</p>
                    <p>Our team combines deep local knowledge with modern financial practices to deliver fast, fair, and transparent loan products tailored to your needs.</p>
                </div>
                <div class="story-stats">
                    <div class="stat-card">
                        <div class="number">500+</div>
                        <div class="label">Loans Disbursed</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">KSH 50M+</div>
                        <div class="label">Capital Deployed</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">98%</div>
                        <div class="label">Customer Satisfaction</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">24hrs</div>
                        <div class="label">Avg. Approval Time</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Our Values</span>
                <h2>What We Stand For</h2>
                <p>The principles that guide everything we do at Mweela Cash Capital.</p>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Integrity</h3>
                    <p>We operate with complete transparency. No hidden fees, no surprises. Every term is clearly explained before you sign.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Speed</h3>
                    <p>We know time is money. Our streamlined application process ensures you get a decision quickly, often within 24 hours.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-users"></i>
                    <h3>Customer First</h3>
                    <p>Your success is our success. We work with you to structure repayments that fit your cash flow, not ours.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-seedling"></i>
                    <h3>Growth</h3>
                    <p>We are committed to helping you grow. From your first loan to your tenth, we are with you every step of the way.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Visit Us</span>
                <h2>Our Location</h2>
                <p>Visit our branch in Mutomo, Kitui County. We are here to serve you.</p>
            </div>
            <div class="location-grid">
                <div class="location-info">
                    <h3>Mutomo Branch</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Mutomo Town, Kitui County, Kenya</p>
                    <p><i class="fas fa-clock"></i> Monday – Friday: 8:00 AM – 5:00 PM</p>
                    <p><i class="fas fa-clock"></i> Saturday: 9:00 AM – 1:00 PM</p>
                    <p><i class="fas fa-phone"></i> +254 700 000 001</p>
                    <p><i class="fas fa-envelope"></i> info@mweelacredit.co.ke</p>
                </div>
                <div class="location-map">
                    <i class="fas fa-map"></i>
                    <p>Google Maps integration available</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Ready to Grow With Us?</h2>
            <p>Explore our loan products and find the right financing solution for your needs.</p>
            <div class="hero-buttons" style="justify-content:center;">
                <a href="/our-loans" class="btn btn-primary">View Loan Products</a>
                <a href="/contact" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>
</html>
