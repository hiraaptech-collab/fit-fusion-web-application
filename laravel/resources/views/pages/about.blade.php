@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | FitFusion – AI-Powered Premium Fitness & Wellness</title>
    <meta name="description" content="Learn about FitFusion's mission, vision, and why we're the premium choice for AI-powered fitness, nutrition, and wellness tools.">
    <meta name="keywords" content="FitFusion, About FitFusion, Fitness Mission, Wellness, Premium Fitness, AI Fitness Platform">
    <meta name="author" content="FitFusion">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://fitfusion.com/about.html">
    <!-- Open Graph -->
    <meta property="og:title" content="About Us | FitFusion – AI-Powered Premium Fitness & Wellness">
    <meta property="og:description" content="Learn about FitFusion's mission, vision, and why we're the premium choice for AI-powered fitness, nutrition, and wellness tools.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fitfusion.com/about.html">
    <meta property="og:site_name" content="FitFusion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="About Us | FitFusion – AI-Powered Premium Fitness & Wellness">
    <meta name="twitter:description" content="Learn about FitFusion's mission, vision, and why we're the premium choice for AI-powered fitness, nutrition, and wellness tools.">
    <!-- Structured Data -->
    @verbatim
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "FitFusion",
        "url": "https://fitfusion.com",
        "logo": "https://fitfusion.com/assets/logo.png",
        "description": "FitFusion is a premium AI-powered fitness and wellness platform dedicated to making expert guidance accessible to everyone.",
        "foundingDate": "2016",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Pakistan Homes Society, Block 9-A, Gulistan-e-Johar",
            "addressLocality": "Karachi",
            "addressCountry": "PK"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+92-310-3701543",
            "contactType": "customer service",
            "email": "hiraaptech@gmail.com"
        },
        "sameAs": [
            "https://facebook.com",
            "https://instagram.com",
            "https://twitter.com",
            "https://youtube.com"
        ]
    }
    </script>
@endverbatim
@endsection

@section('content')
<!-- NAVBAR (Fetched from JSON) -->
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>

<!-- PAGE HERO -->
<section class="page-hero" id="about-hero" aria-label="About FitFusion hero">
    <div class="container">
        <span class="section-tag" data-aos="fade-up"><i class="bi bi-info-circle-fill"></i> About FitFusion</span>
        <h1 class="page-hero-title" data-aos="fade-up" data-aos-delay="100">
            Your Partner In <span>Health &amp; Wellness</span>
        </h1>
        <p class="page-hero-text" data-aos="fade-up" data-aos-delay="200">
            We blend expert fitness guidance, science-backed nutrition, and premium wellness tools
            to help you transform your life — one healthy habit at a time.
        </p>
    </div>
</section>

<!-- ABOUT INTRO -->
<section class="about-intro-section" id="about-intro" aria-label="About FitFusion introduction">
    <div class="container">
        <div class="about-grid">
            <div class="about-image" data-aos="fade-right">
                <img decoding="async" src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=80" alt="FitFusion training studio" loading="lazy">
                <div class="experience-badge">
                    <span class="badge-number">10+</span>
                    <span class="badge-text">Years of Expertise</span>
                </div>
            </div>
            <div class="about-content" data-aos="fade-left">
                <span class="section-tag"><i class="bi bi-award-fill"></i> Who We Are</span>
                <h2 class="section-title">
                    Building Healthier Lives With <span>FitFusion</span>
                </h2>
                <p class="section-text">
                    FitFusion is a premium fitness and wellness platform dedicated to making expert guidance
                    accessible to everyone. From precise BMI analysis and personalized diet plans to
                    comprehensive workout guides, we give you the tools, knowledge, and motivation to reach your goals.
                </p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Science-backed fitness and nutrition advice</li>
                    <li><i class="bi bi-check-circle-fill"></i> Plans tailored for every goal and fitness level</li>
                    <li><i class="bi bi-check-circle-fill"></i> A supportive community of thousands of members</li>
                    <li><i class="bi bi-check-circle-fill"></i> Free tools including BMI calculator and guides</li>
                </ul>
                <div class="hero-buttons">
                    <a href="dietplans.html" class="btn-primary-custom">
                        <i class="bi bi-egg-fried"></i> Explore Diet Plans
                    </a>
                    <a href="workouts.html" class="btn-outline-custom">
                        <i class="bi bi-dumbbell-fill"></i> View Workouts
                    </a>
                </div>
            </div>
        </div>
        <div class="provided-visuals" aria-label="Fitness inspiration"><figure class="provided-visual" data-aos="fade-up"><img src="assets/images/provided/2bf3a7df3395fd09caa676744a2baa7c.jpg" alt="Warmly lit strength training space" width="736" height="736" loading="lazy" decoding="async"><figcaption>Strength training space</figcaption></figure><figure class="provided-visual" data-aos="fade-up"><img src="assets/images/provided/ae02c845582cf40793765410bc4fe251.jpg" alt="Bright fitness studio with daylight from skylights" width="735" height="900" loading="lazy" decoding="async"><figcaption>Light-filled fitness studio</figcaption></figure></div>
    </div>
</section>

<!-- MISSION & VISION -->
<section class="mission-section" id="mission" aria-label="Our mission and vision">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"><i class="bi bi-compass-fill"></i> Our Purpose</span>
            <h2 class="section-title">Mission &amp; <span>Vision</span></h2>
            <p class="section-text mx-auto">
                Everything we build is guided by a clear purpose: to make premium wellness accessible to everyone.
            </p>
        </div>
        <div class="mission-grid">
            <div class="mission-card" data-aos="fade-up">
                <div class="mission-icon"><i class="bi bi-bullseye"></i></div>
                <h4>Our Mission</h4>
                <p>
                    To empower people worldwide with reliable fitness tools, expert guidance, and
                    practical nutrition plans that make healthy living simple, sustainable, and enjoyable.
                </p>
            </div>
            <div class="mission-card" data-aos="fade-up" data-aos-delay="100">
                <div class="mission-icon"><i class="bi bi-eye-fill"></i></div>
                <h4>Our Vision</h4>
                <p>
                    A world where everyone has the knowledge and support to achieve lasting health —
                    where wellness is a lifestyle, not a luxury.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- STATS / HIGHLIGHTS -->
<section class="stats-section" id="about-stats" aria-label="FitFusion platform statistics">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box" data-aos="fade-up">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <span class="stat-number counter" data-target="10000" data-suffix="+">0</span>
                <span class="stat-label">Happy Users</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon"><i class="bi bi-egg-fried"></i></div>
                <span class="stat-number counter" data-target="25" data-suffix="+">0</span>
                <span class="stat-label">Diet Plans</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon"><i class="bi bi-activity"></i></div>
                <span class="stat-number counter" data-target="100" data-suffix="+">0</span>
                <span class="stat-label">Workout Exercises</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon"><i class="bi bi-award-fill"></i></div>
                <span class="stat-number counter" data-target="4.9" data-suffix="">0</span>
                <span class="stat-label">Average Rating</span>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE FITFUSION -->
<section class="about-values-section" id="why-choose" aria-label="Why choose FitFusion">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"><i class="bi bi-stars"></i> Why Choose FitFusion</span>
            <h2 class="section-title">The FitFusion <span>Advantage</span></h2>
            <p class="section-text mx-auto">
                We stand apart through quality, personalization, and an unwavering commitment to your results.
            </p>
        </div>
        <div class="about-values-grid">
            <div class="value-card" data-aos="fade-up">
                <div class="value-icon"><i class="bi bi-shield-check"></i></div>
                <h5>Science-Backed</h5>
                <p>Every plan and recommendation is grounded in trusted health and fitness research.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="100">
                <div class="value-icon"><i class="bi bi-person-gear"></i></div>
                <h5>Fully Personalized</h5>
                <p>Plans adapt to your goals, fitness level, lifestyle, and dietary preferences.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="200">
                <div class="value-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <h5>Easy To Follow</h5>
                <p>Clear instructions, meal guides, and step-by-step workouts anyone can follow.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="300">
                <div class="value-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                <h5>Total Wellness</h5>
                <p>We care about your whole health — body, mind, and lasting habits.</p>
            </div>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="preview-section" id="benefits" aria-label="Benefits of joining FitFusion">
    <div class="container">
        <div class="preview-grid">
            <div class="preview-content" data-aos="fade-right">
                <span class="section-tag"><i class="bi bi-heart-fill"></i> Your Benefits</span>
                <h2 class="section-title">What You Gain With <span>FitFusion</span></h2>
                <p class="section-text">
                    When you join FitFusion, you're not just following a plan — you're building a healthier,
                    stronger, more confident version of yourself.
                </p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Faster progress with structured, proven programs</li>
                    <li><i class="bi bi-check-circle-fill"></i> Balanced nutrition that fits your real life</li>
                    <li><i class="bi bi-check-circle-fill"></i> Confidence through clear, measurable results</li>
                    <li><i class="bi bi-check-circle-fill"></i> Lasting habits for long-term wellness</li>
                </ul>
            </div>
            <div class="preview-image" data-aos="fade-left">
                <img decoding="async" src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=900&q=80" alt="FitFusion members training" loading="lazy">
                <div class="preview-image-overlay"></div>
                <span class="preview-image-badge"><i class="bi bi-graph-up-arrow"></i> Proven Results</span>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta-section" id="about-cta" aria-label="Call to action">
    <div class="container">
        <div data-aos="fade-up">
            <h2>Become Part of the FitFusion Family</h2>
            <p>Start your transformation today — free BMI analysis, diet plans, and workouts await.</p>
            <div class="final-cta-buttons">
                <a href="BMI.html" class="btn-cta-dark"><i class="bi bi-calculator-fill"></i> Get Started</a>
                <a href="contact.html" class="btn-cta-light"><i class="bi bi-envelope-fill"></i> Contact Us</a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER (Fetched from JSON) -->
<div id="my-footer" role="contentinfo" aria-label="Site footer"></div>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-arrow-up"></i>
</button>
@endsection
