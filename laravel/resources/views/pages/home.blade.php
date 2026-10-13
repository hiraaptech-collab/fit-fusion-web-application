@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitFusion – AI-Powered Premium Fitness & Wellness Platform</title>
    <meta name="description" content="FitFusion is your premium AI-powered fitness partner. Calculate BMI, explore diet plans, workout guides, chat with AI fitness coach, and transform your lifestyle.">
    <meta name="keywords" content="FitFusion, AI Fitness, BMI Calculator, Diet Plans, Workout Guides, Wellness, Premium Fitness Platform, AI Fitness Coach">
    <meta name="author" content="FitFusion">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://fitfusion.com/index.html">
    <!-- Open Graph -->
    <meta property="og:title" content="FitFusion – AI-Powered Premium Fitness & Wellness">
    <meta property="og:description" content="Transform your life with AI-powered fitness tools, expert diet plans, and comprehensive workout guides.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fitfusion.com/index.html">
    <meta property="og:site_name" content="FitFusion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FitFusion – AI-Powered Premium Fitness & Wellness">
    <meta name="twitter:description" content="Transform your life with AI-powered fitness tools, expert diet plans, and comprehensive workout guides.">
    <!-- Structured Data -->
    @verbatim
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "FitFusion",
        "url": "https://fitfusion.com",
        "description": "AI-Powered Premium Fitness & Wellness Platform",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://fitfusion.com/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
@endverbatim
@endsection

@section('content')
<!-- NAVBAR (Fetched from JSON) -->
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>

<!-- HERO SECTION -->
<section class="hero-section" id="home">
    <div class="hero-particles" aria-hidden="true"></div>
    <div class="container">
        <div class="row align-items-center g-5 pt-5">
            <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-award-fill"></i>
                        AI-Powered Fitness Platform
                    </span>
                    <h1 class="hero-title">
                        Transform Your
                        <span class="highlight">Body &amp; Mind</span>
                        With <span class="highlight">FitFusion</span>
                    </h1>
                    <p class="hero-description">
                        Experience the ultimate fitness journey with AI-powered coaching,
                        expert-crafted workout plans, personalized diet strategies, and
                        cutting-edge wellness tools designed to help you achieve your goals faster.
                    </p>
                    <div class="hero-buttons">
                        <a href="BMI.html" class="hero-btn primary-btn" aria-label="Check your BMI">
                            <i class="bi bi-calculator-fill"></i>
                            Check Your BMI
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <a href="fusion-ai.html" class="hero-btn outline-btn" aria-label="Chat with Fusion AI">
                            <i class="bi bi-robot"></i>
                            Try Fusion-AI
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="stat-number">10K+</span>
                            <span class="stat-label">Active Members</span>
                        </div>
                        <div class="hero-stat">
                            <span class="stat-number">95%</span>
                            <span class="stat-label">Success Rate</span>
                        </div>
                        <div class="hero-stat">
                            <span class="stat-number">4.9</span>
                            <span class="stat-label">Average Rating</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="300">
                <div class="hero-image-wrapper">
                    <img decoding="async" src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=900&q=80" alt="FitFusion personal training session" loading="eager">
                    <div class="hero-image-overlay"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS SECTION -->
<section class="stats-section" id="stats" aria-label="Platform statistics">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <span class="stat-number counter" data-target="10000" data-suffix="+">0</span>
                <span class="stat-label">Happy Users</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                <span class="stat-number counter" data-target="95" data-suffix="%">0</span>
                <span class="stat-label">Health Awareness</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon"><i class="bi bi-egg-fried"></i></div>
                <span class="stat-number counter" data-target="25" data-suffix="+">0</span>
                <span class="stat-label">Diet Plans</span>
            </div>
            <div class="stat-box" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon"><i class="bi bi-activity"></i></div>
                <span class="stat-number counter" data-target="100" data-suffix="+">0</span>
                <span class="stat-label">Workout Exercises</span>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="features-section" id="features">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Why Choose FitFusion</span>
            <h2 class="section-title">
                Your Premium Partner For
                <span>Health &amp; Wellness</span>
            </h2>
            <p class="section-text mx-auto">
                Discover world-class fitness tools, AI-powered coaching, expert guidance,
                and personalized wellness solutions designed to help you achieve lasting results.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
                <div class="feature-icon"><i class="bi bi-calculator-fill"></i></div>
                <h4>BMI Calculator</h4>
                <p>Quickly assess your health status with our advanced BMI analysis tool.</p>
                <a href="BMI.html" class="feature-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon"><i class="bi bi-robot"></i></div>
                <h4>Fusion-AI Coach</h4>
                <p>Get personalized fitness advice from our AI-powered fitness assistant.</p>
                <a href="fusion-ai.html" class="feature-link">Try Now <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon"><i class="bi bi-egg-fried"></i></div>
                <h4>Diet Plans</h4>
                <p>Explore expert-crafted meal plans for every goal and lifestyle.</p>
                <a href="dietplans.html" class="feature-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon"><i class="bi bi-dumbbell-fill"></i></div>
                <h4>Workout Guides</h4>
                <p>Access comprehensive workout routines for all fitness levels.</p>
                <a href="workouts.html" class="feature-link">Learn More <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- SERVICES PREVIEW SECTION -->
<section class="services-preview-section" id="services">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Our Services</span>
            <h2 class="section-title">
                Comprehensive <span>Wellness Solutions</span>
            </h2>
            <p class="section-text mx-auto">
                From AI-powered coaching to personalized diet plans, we provide everything
                you need for a healthier lifestyle.
            </p>
        </div>
        <div class="services-grid">
            <div class="service-card" data-aos="fade-up" data-aos-delay="0">
                <div class="service-icon"><i class="bi bi-robot"></i></div>
                <div class="provided-service-image"><img src="assets/images/provided/228899d7da34d9a56e66c1920d8a7704.jpg" alt="Athlete using a strength training machine" width="1080" height="1350" loading="lazy" decoding="async"></div>
                <h4>AI Fitness Coach</h4>
                <p>Chat with our AI assistant for personalized fitness guidance and workout plans.</p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Personalized Advice</li>
                    <li><i class="bi bi-check-circle-fill"></i> Workout Planning</li>
                    <li><i class="bi bi-check-circle-fill"></i> Multi-Language</li>
                </ul>
            </div>
            <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                <div class="service-icon"><i class="bi bi-egg-fried"></i></div>
                <div class="provided-service-image"><img src="assets/images/provided/05e83f496517b0d1d53f1112db5417bd.jpg" alt="Balanced meal beside training equipment" width="736" height="1308" loading="lazy" decoding="async"></div>
                <h4>Diet Planning</h4>
                <p>Personalized nutrition plans tailored to your goals and preferences.</p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Weight Loss</li>
                    <li><i class="bi bi-check-circle-fill"></i> Muscle Gain</li>
                    <li><i class="bi bi-check-circle-fill"></i> Healthy Maintenance</li>
                </ul>
            </div>
            <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                <div class="service-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                <div class="provided-service-image"><img src="assets/images/provided/04b7b236c0e1cf9e766800647aee1dd3.jpg" alt="Athlete performing a battle rope workout" width="736" height="1104" loading="lazy" decoding="async"></div>
                <h4>Workout Programs</h4>
                <p>Expert-designed workout routines for all fitness levels and goals.</p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Strength Training</li>
                    <li><i class="bi bi-check-circle-fill"></i> Cardio &amp; HIIT</li>
                    <li><i class="bi bi-check-circle-fill"></i> Home Workouts</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- BMI PREVIEW SECTION -->
<section class="preview-section" id="bmi-preview">
    <div class="container">
        <div class="preview-grid">
            <div class="preview-image" data-aos="fade-right">
                <img decoding="async" src="https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=900&q=80" alt="BMI health assessment" loading="lazy">
                <div class="preview-image-overlay"></div>
                <span class="preview-image-badge"><i class="bi bi-calculator-fill"></i> Instant BMI Analysis</span>
            </div>
            <div class="preview-content" data-aos="fade-left">
                <span class="section-tag">Health Assessment</span>
                <h2 class="section-title">Check Your BMI &amp; <span>Know Your Health</span></h2>
                <p class="section-text">
                    Understanding your Body Mass Index is the first step toward better health.
                    Our advanced calculator provides instant, accurate results with personalized insights.
                </p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Instant BMI Calculation</li>
                    <li><i class="bi bi-check-circle-fill"></i> Detailed Health Analysis</li>
                    <li><i class="bi bi-check-circle-fill"></i> Personalized Recommendations</li>
                </ul>
                <a href="BMI.html" class="btn-primary-custom">
                    Calculate Now <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- WORKOUT PREVIEW SECTION -->
<section class="preview-section" id="workout-preview">
    <div class="container">
        <div class="preview-grid reversed">
            <div class="preview-image" data-aos="fade-left">
                <img decoding="async" src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=900&q=80" alt="Expert workout guides" loading="lazy">
                <div class="preview-image-overlay"></div>
                <span class="preview-image-badge"><i class="bi bi-dumbbell-fill"></i> All Fitness Levels</span>
            </div>
            <div class="preview-content" data-aos="fade-right">
                <span class="section-tag">Fitness Training</span>
                <h2 class="section-title">Expert <span>Workout Guides</span> For Every Goal</h2>
                <p class="section-text">
                    Whether you're a beginner or advanced athlete, our comprehensive workout
                    library has everything you need to build strength, lose weight, or improve fitness.
                </p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> Step-by-Step Instructions</li>
                    <li><i class="bi bi-check-circle-fill"></i> Image Guides</li>
                    <li><i class="bi bi-check-circle-fill"></i> All Fitness Levels</li>
                </ul>
                <a href="workouts.html" class="btn-primary-custom">
                    Explore Workouts <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- DIET PREVIEW SECTION -->
<section class="preview-section" id="diet-preview">
    <div class="container">
        <div class="preview-grid">
            <div class="preview-image" data-aos="fade-right">
                <img decoding="async" src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=900&q=80" alt="Healthy diet plans" loading="lazy">
                <div class="preview-image-overlay"></div>
                <span class="preview-image-badge"><i class="bi bi-egg-fried"></i> 25+ Expert Diet Plans</span>
            </div>
            <div class="preview-content" data-aos="fade-left">
                <span class="section-tag">Nutrition Guide</span>
                <h2 class="section-title">Eat Healthy, <span>Live Better</span></h2>
                <p class="section-text">
                    Discover scientifically-designed diet plans that nourish your body,
                    support your fitness goals, and transform your relationship with food.
                </p>
                <ul class="benefit-list">
                    <li><i class="bi bi-check-circle-fill"></i> 25+ Diet Plans</li>
                    <li><i class="bi bi-check-circle-fill"></i> Detailed Meal Guides</li>
                    <li><i class="bi bi-check-circle-fill"></i> Nutrition Information</li>
                </ul>
                <a href="dietplans.html" class="btn-primary-custom">
                    View Diet Plans <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- TRANSFORMATION GALLERY -->
<section class="transformation-section" id="transformations">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Success Stories</span>
            <h2 class="section-title">Real <span>Transformations</span></h2>
            <p class="section-text mx-auto">
                See how our members transformed their lives with FitFusion's
                comprehensive fitness and wellness programs.
            </p>
        </div>
        <div class="transformation-grid">
            <div class="transformation-card" data-aos="fade-up">
                <div class="card-image">
                    <img decoding="async" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=600&q=80" alt="Sarah Johnson lost 25 lbs with FitFusion" loading="lazy">
                    <span class="overlay-badge">-25 lbs</span>
                </div>
                <div class="card-body">
                    <h5>Sarah Johnson</h5>
                    <div class="meta"><i class="bi bi-arrow-right"></i> Weight Loss Journey</div>
                    <p>"FitFusion completely transformed my life. I lost 25 pounds and gained confidence."</p>
                </div>
            </div>
            <div class="transformation-card" data-aos="fade-up" data-aos-delay="100">
                <div class="card-image">
                    <img decoding="async" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80" alt="Mike Rodriguez gained 15 lbs muscle with FitFusion" loading="lazy">
                    <span class="overlay-badge">+15 lbs muscle</span>
                </div>
                <div class="card-body">
                    <h5>Mike Rodriguez</h5>
                    <div class="meta"><i class="bi bi-arrow-right"></i> Muscle Gain Journey</div>
                    <p>"The workout guides and diet plans helped me build 15 pounds of muscle naturally."</p>
                </div>
            </div>
            <div class="transformation-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-image">
                    <img decoding="async" src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=600&q=80" alt="Emily Chen wellness transformation with FitFusion" loading="lazy">
                    <span class="overlay-badge">Overall Wellness</span>
                </div>
                <div class="card-body">
                    <h5>Emily Chen</h5>
                    <div class="meta"><i class="bi bi-arrow-right"></i> Wellness Transformation</div>
                    <p>"FitFusion helped me achieve my fitness goals and maintain a healthy lifestyle."</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS SECTION -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">What Our <span>Members Say</span></h2>
            <p class="section-text mx-auto">
                Real stories from real people who transformed their lives with FitFusion.
            </p>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card" data-aos="fade-up">
                <div class="stars" aria-label="5 out of 5 stars">
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                </div>
                <p class="quote">"The BMI calculator and diet plans are incredible tools. I've never felt healthier!"</p>
                <div class="author">
                    <img decoding="async" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&q=80" alt="David Smith">
                    <div>
                        <div class="author-name">David Smith</div>
                        <div class="author-role">Weight Loss Success</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stars" aria-label="5 out of 5 stars">
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                </div>
                <p class="quote">"The workout guides are comprehensive and easy to follow. Perfect for beginners!"</p>
                <div class="author">
                    <img decoding="async" src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&q=80" alt="Jessica Martinez">
                    <div>
                        <div class="author-name">Jessica Martinez</div>
                        <div class="author-role">Fitness Journey</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stars" aria-label="5 out of 5 stars">
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                    <i class="bi bi-star-fill"></i>
                </div>
                <p class="quote">"FitFusion helped me stay motivated and achieve my health goals faster than I expected."</p>
                <div class="author">
                    <img decoding="async" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=100&q=80" alt="Robert Thompson">
                    <div>
                        <div class="author-name">Robert Thompson</div>
                        <div class="author-role">Wellness Journey</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" id="faq">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag">FAQs</span>
            <h2 class="section-title">Frequently Asked <span>Questions</span></h2>
            <p class="section-text mx-auto">
                Find answers to common questions about FitFusion's services and programs.
            </p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10" data-aos="fade-up">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                                <i class="bi bi-question-circle-fill me-2"></i>
                                What is FitFusion?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                FitFusion is a premium AI-powered fitness and wellness platform offering BMI calculation, diet plans, workout guides, AI fitness coaching, and expert health guidance to help you achieve your fitness goals.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item mt-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false">
                                <i class="bi bi-question-circle-fill me-2"></i>
                                What is Fusion-AI?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Fusion-AI is our AI-powered fitness assistant that can help you with workout plans, nutrition advice, exercise techniques, BMI interpretation, and general fitness guidance. It supports multiple languages including English, Urdu, and Hindi.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item mt-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false">
                                <i class="bi bi-question-circle-fill me-2"></i>
                                Are the diet plans personalized?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! Our diet plans are designed for various goals including weight loss, muscle gain, fat loss, and healthy maintenance. Each plan includes detailed meal guidance, calories, benefits, and practical tips.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item mt-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false">
                                <i class="bi bi-question-circle-fill me-2"></i>
                                Are the workout guides suitable for beginners?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolutely! We offer workout guides for all fitness levels, from beginner to advanced. Each exercise includes detailed instructions, safety tips, and modifications for different skill levels.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item mt-3">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false">
                                <i class="bi bi-question-circle-fill me-2"></i>
                                Is FitFusion free to use?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! All our core features including BMI calculator, diet plans, workout guides, and health resources are completely free. Fusion-AI delivers AI-powered fitness coaching through our integration.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- NEWSLETTER SECTION -->
<section class="newsletter-section" id="newsletter">
    <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
            <span class="section-tag">Stay Updated</span>
            <h3 class="section-title">Get Weekly <span>Fitness Insights</span></h3>
            <p class="section-text mx-auto">
                Subscribe for weekly fitness tips, AI workout insights, and exclusive offers.
            </p>
        </div>
        <form class="newsletter-form" id="newsletterForm" aria-label="Newsletter subscription" data-aos="fade-up">
            <input type="email" placeholder="Enter your email" required aria-label="Email address">
            <button class="btn-subscribe" type="submit">
                SUBSCRIBE <i class="bi bi-arrow-right"></i>
            </button>
        </form>
    </div>
</section>

<!-- FINAL CTA SECTION -->
<section class="final-cta-section" id="get-started">
    <div class="container">
        <div data-aos="fade-up">
            <h2>Ready to Start Your Fitness Journey?</h2>
            <p>
                Join thousands of members who have transformed their health with FitFusion.
                Take the first step today — it's free.
            </p>
            <div class="final-cta-buttons">
                <a href="fusion-ai.html" class="btn-cta-dark"><i class="bi bi-robot"></i> Try Fusion-AI</a>
                <a href="BMI.html" class="btn-cta-light"><i class="bi bi-calculator-fill"></i> Check Your BMI</a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER (Fetched from JSON) -->
<div id="my-footer" role="contentinfo"></div>

<!-- BACK TO TOP BUTTON -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-arrow-up"></i>
</button>
@endsection
