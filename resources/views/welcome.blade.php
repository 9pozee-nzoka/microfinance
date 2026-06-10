<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mweela Cash Capital - Trusted microfinance loans in Kenya. Quick business loans, personal loans, and SME financing in Mutomo, Kitui. Apply today for affordable loans.">
    <meta name="keywords" content="microfinance, loans Kenya, business loans, Mweela Cash Capital, quick loans, SME loans, Mutomo, Kitui, personal loans, Kenya microfinance">
    <meta name="author" content="Mweela Cash Capital">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="https://mweelacredit.co.ke/">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://mweelacredit.co.ke/">
    <meta property="og:title" content="Mweela Cash Capital - Microfinance Loans in Kenya">
    <meta property="og:description" content="Trusted microfinance loans in Kenya. Quick business loans, personal loans, and SME financing tailored for you.">
    <meta property="og:image" content="https://mweelacredit.co.ke/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Mweela Cash Capital">
    <meta property="og:locale" content="en_KE">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://mweelacredit.co.ke/">
    <meta name="twitter:title" content="Mweela Cash Capital - Microfinance Loans in Kenya">
    <meta name="twitter:description" content="Trusted microfinance loans in Kenya. Quick business loans, personal loans, and SME financing tailored for you.">
    <meta name="twitter:image" content="https://mweelacredit.co.ke/images/og-image.jpg">

    <title>Mweela Cash Capital - Microfinance Loans in Kenya</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#00BCD4">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('partials.public-styles')

    {{-- Page-specific styles --}}
    <style>
        .hero-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .hero-buttons .btn { min-width: 180px; }

        .stats-bar {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 32px;
            margin-top: -40px;
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            text-align: center;
        }
        .stats-bar .stat-number { font-size: 32px; font-weight: 800; color: var(--primary); }
        .stats-bar .stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

        .trust-badges {
            display: flex;
            gap: 40px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 48px;
            opacity: 0.6;
        }
        .trust-badges span { font-size: 13px; font-weight: 600; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; }
        .trust-badges i { color: var(--primary); font-size: 18px; }

        .testimonials { background: linear-gradient(180deg, var(--bg) 0%, white 100%); }
        .testimonial-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 32px;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .testimonial-card::before {
            content: '"';
            font-size: 60px;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: 10px;
            left: 20px;
            font-family: Georgia, serif;
            line-height: 1;
        }
        .testimonial-card p { font-size: 15px; color: var(--text-secondary); line-height: 1.7; margin-bottom: 20px; position: relative; z-index: 1; }
        .testimonial-author { display: flex; align-items: center; gap: 12px; }
        .testimonial-author img {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex; align-items: center; justify-content: center;
            object-fit: cover;
        }
        .testimonial-author strong { font-size: 14px; color: var(--text-primary); }
        .testimonial-author span { font-size: 13px; color: var(--text-secondary); }

        @media (max-width: 768px) {
            .hero-buttons .btn { width: 100%; min-width: unset; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); gap: 20px; padding: 24px; margin-top: -30px; }
            .stats-bar .stat-number { font-size: 24px; }
            .trust-badges { gap: 20px; }
        }
    </style>

    {{-- JSON-LD Structured Data --}}
    @php
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => 'https://mweelacredit.co.ke/#organization',
                'name' => 'Mweela Cash Capital',
                'alternateName' => 'Mweela',
                'url' => 'https://mweelacredit.co.ke',
                'logo' => ['@type' => 'ImageObject', 'url' => 'https://mweelacredit.co.ke/images/logo.png'],
                'description' => 'Trusted microfinance institution providing quick business loans, personal loans, and SME financing in Kenya.',
                'contactPoint' => [
                    '@type' => 'ContactPoint',
                    'telephone' => '+254-700-000-001',
                    'contactType' => 'customer service',
                    'areaServed' => 'KE',
                    'availableLanguage' => ['English', 'Swahili']
                ]
            ],
            [
                '@type' => 'LocalBusiness',
                '@id' => 'https://mweelacredit.co.ke/#localbusiness',
                'name' => 'Mweela Cash Capital',
                'image' => 'https://mweelacredit.co.ke/images/og-image.jpg',
                'url' => 'https://mweelacredit.co.ke',
                'telephone' => '+254-700-000-001',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Mutomo Town',
                    'addressLocality' => 'Mutomo',
                    'addressRegion' => 'Kitui County',
                    'addressCountry' => 'KE'
                ],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => -1.85, 'longitude' => 38.1833],
                'openingHoursSpecification' => [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
                    'opens' => '08:00',
                    'closes' => '17:00'
                ],
                'priceRange' => '$$',
                'currenciesAccepted' => 'KES'
            ],
            [
                '@type' => 'WebSite',
                '@id' => 'https://mweelacredit.co.ke/#website',
                'url' => 'https://mweelacredit.co.ke',
                'name' => 'Mweela Cash Capital',
                'publisher' => ['@id' => 'https://mweelacredit.co.ke/#organization']
            ],
            [
                '@type' => 'WebPage',
                '@id' => 'https://mweelacredit.co.ke/#webpage',
                'url' => 'https://mweelacredit.co.ke/',
                'name' => 'Mweela Cash Capital - Microfinance Loans in Kenya',
                'isPartOf' => ['@id' => 'https://mweelacredit.co.ke/#website'],
                'about' => ['@id' => 'https://mweelacredit.co.ke/#organization']
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $jsonLd !!}</script>
</head>
<body>
    @include('partials.public-nav', ['active' => 'home'])

    {{-- Hero --}}
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-badges">
                <span class="hero-badge"><i class="fas fa-check-circle"></i> Licensed Microfinance</span>
                <span class="hero-badge"><i class="fas fa-bolt"></i> 24hr Approval</span>
                <span class="hero-badge"><i class="fas fa-shield-alt"></i> No Hidden Fees</span>
            </div>
            <h1>Quick Loans for Your Future</h1>
            <p>Get affordable microfinance loans in Kenya. Business loans, personal loans, and SME financing — tailored for you with transparent terms.</p>
            <div class="hero-buttons">
                <a href="/our-loans" class="btn">Explore Loans</a>
                <a href="/contact" class="btn btn-outline">Apply Now</a>
            </div>
            <div class="trust-badges">
                <span><i class="fas fa-users"></i> 500+ Happy Customers</span>
                <span><i class="fas fa-hand-holding-usd"></i> KSH 50M+ Disbursed</span>
                <span><i class="fas fa-star"></i> 4.9/5 Rating</span>
            </div>
        </div>
    </section>

    {{-- Stats Bar --}}
    <div class="container">
        <div class="stats-bar">
            <div>
                <div class="stat-number">500+</div>
                <div class="stat-label">Loans Disbursed</div>
            </div>
            <div>
                <div class="stat-number">KSH 50M+</div>
                <div class="stat-label">Capital Deployed</div>
            </div>
            <div>
                <div class="stat-number">24hrs</div>
                <div class="stat-label">Avg. Approval</div>
            </div>
            <div>
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction</div>
            </div>
        </div>
    </div>

    {{-- Features --}}
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Why Choose Us</span>
                <h2>Built for Your Success</h2>
                <p>We combine local understanding with modern financial solutions to help you grow.</p>
            </div>
            <div class="grid-3">
                <div class="card animate-in delay-1">
                    <div class="card-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Lightning Fast</h3>
                    <p>Get loan decisions within 24 hours. Our streamlined process means less waiting and more doing.</p>
                </div>
                <div class="card animate-in delay-2">
                    <div class="card-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <h3>Flexible Terms</h3>
                    <p>Repayment schedules designed around your cash flow. Weekly or monthly — you choose what works.</p>
                </div>
                <div class="card animate-in delay-3">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Transparent Pricing</h3>
                    <p>No hidden fees, no surprises. Every charge is clearly explained before you sign anything.</p>
                </div>
                <div class="card animate-in delay-1">
                    <div class="card-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>M-Pesa Ready</h3>
                    <p>Repay your loan conveniently via M-Pesa from anywhere in Kenya. Simple and secure.</p>
                </div>
                <div class="card animate-in delay-2">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h3>Local Support</h3>
                    <p>Our Mutomo team knows the community. We understand your challenges because we live here too.</p>
                </div>
                <div class="card animate-in delay-3">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <h3>Build Credit</h3>
                    <p>Establish a positive credit history with us. Good borrowers unlock larger loans and better rates.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Loan Products Preview --}}
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Our Products</span>
                <h2>Loans That Fit Your Life</h2>
                <p>Whether you are growing a business or handling personal expenses, we have you covered.</p>
            </div>
            <div class="grid-3">
                <div class="card" style="border-top: 4px solid var(--primary);">
                    <div class="card-icon" style="background: linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 100%);"><i class="fas fa-briefcase"></i></div>
                    <h3>Business Loans</h3>
                    <p>Flexible financing to grow your enterprise. From KSH 10K to 500K with competitive rates.</p>
                    <a href="/our-loans" style="color: var(--primary); font-weight: 600; font-size: 14px; text-decoration: none;">Learn more <i class="fas fa-arrow-right" style="font-size: 12px;"></i></a>
                </div>
                <div class="card" style="border-top: 4px solid var(--success);">
                    <div class="card-icon" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%);"><i class="fas fa-user" style="color: var(--success);"></i></div>
                    <h3>Personal Loans</h3>
                    <p>Quick personal financing for education, medical, or home needs. From KSH 5K to 200K.</p>
                    <a href="/our-loans" style="color: var(--success); font-weight: 600; font-size: 14px; text-decoration: none;">Learn more <i class="fas fa-arrow-right" style="font-size: 12px;"></i></a>
                </div>
                <div class="card" style="border-top: 4px solid var(--warning);">
                    <div class="card-icon" style="background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%);"><i class="fas fa-building" style="color: var(--warning);"></i></div>
                    <h3>SME Financing</h3>
                    <p>Specialized financing for SMEs looking to scale. From KSH 100K to 2M with structured plans.</p>
                    <a href="/our-loans" style="color: var(--warning); font-weight: 600; font-size: 14px; text-decoration: none;">Learn more <i class="fas fa-arrow-right" style="font-size: 12px;"></i></a>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="section testimonials">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Testimonials</span>
                <h2>What Our Clients Say</h2>
                <p>Real stories from real people who trusted us with their financial goals.</p>
            </div>
            <div class="grid-3">
                <div class="testimonial-card">
                    <p>Mweela Cash Capital helped me expand my shop in Mutomo. The process was fast, the staff was friendly, and I got my loan within 2 days. Highly recommended!</p>
                    <div class="testimonial-author">
                        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">JM</div>
                        <div><strong>James Mutua</strong><br><span>Shop Owner, Mutomo</span></div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p>I needed urgent funds for my daughter's school fees. Mweela came through when the banks said no. Their personal loan saved the day.</p>
                    <div class="testimonial-author">
                        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#4CAF50,#388E3C);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">MK</div>
                        <div><strong>Mary Kioko</strong><br><span>Teacher, Kitui</span></div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <p>As a farmer, cash flow is seasonal. Mweela's flexible repayment terms meant I could pay when my harvest came in. They truly understand our needs.</p>
                    <div class="testimonial-author">
                        <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#FF9800,#F57C00);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;">PK</div>
                        <div><strong>Peter Kyalo</strong><br><span>Farmer, Mutomo</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join hundreds of Kenyans who have already transformed their lives with Mweela Cash Capital.</p>
            <div class="hero-buttons" style="justify-content:center;">
                <a href="/contact" class="btn btn-primary">Apply for a Loan</a>
                <a href="/about" class="btn btn-outline">Learn About Us</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>
</html>
