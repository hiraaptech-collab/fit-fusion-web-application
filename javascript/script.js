/* ===========================================
   FitFusion – Shared JavaScript
   Navbar & Footer from JSON, animations, utilities
   ============================================ */

async function loadNavbar() {
    try {
        const response = await fetch('data/navbar.json');
        const data = await response.json();
        renderNavbar(data);
    } catch (error) {
        console.error('Error loading navbar:', error);
        renderFallbackNavbar();
    }
}

function renderNavbar(data) {
    const navbarContainer = document.getElementById('my-navbar');
    if (!navbarContainer) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    let navLinks = '';
    data.navLinks.forEach(link => {
        const isActive = link.url === currentPage ? 'active' : '';
        navLinks += `
            <li class="nav-item">
                <a class="nav-link ${isActive}" href="${link.url}">
                    <i class="${link.icon}"></i>${link.name}
                </a>
            </li>`;
    });

    const mobileLinks = data.navLinks.map(link => {
        const isActive = link.url === currentPage ? 'active' : '';
        return `<a class="mobile-link ${isActive}" href="${link.url}"><i class="${link.icon}"></i> ${link.name}</a>`;
    }).join('');

    const logoHtml = data.logoImage
        ? `<img src="${data.logoImage}" alt="FitFusion" style="height:40px">`
        : `<div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>`;

    navbarContainer.innerHTML = `
        <nav class="fusion-navbar" id="mainNav">
            <div class="navbar-container">
                <a class="navbar-brand" href="index.html">
                    ${logoHtml}
                    <span class="brand-name">${data.brandName}</span>
                </a>
                <ul class="navbar-nav">
                    ${navLinks}
                </ul>
                <a href="${data.ctaButton.url}" class="navbar-cta">
                    <i class="${data.ctaButton.icon}"></i> ${data.ctaButton.text}
                </a>
                <button class="navbar-toggler" type="button" aria-label="Toggle navigation" id="navToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </nav>
        <div class="navbar-mobile" id="navbarMobile">
            <button class="mobile-close" type="button" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
            ${mobileLinks}
            <a href="${data.ctaButton.url}" class="mobile-cta"><i class="${data.ctaButton.icon}"></i> ${data.ctaButton.text}</a>
        </div>`;

    addScrollEffect();
    setupMobileMenu();
}

function renderFallbackNavbar() {
    const navbarContainer = document.getElementById('my-navbar');
    if (!navbarContainer) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const pages = [
        { name: 'Home', url: 'index.html', icon: 'bi bi-house-fill' },
        { name: 'Diet Plans', url: 'dietplans.html', icon: 'bi bi-egg-fried' },
        { name: 'Workouts', url: 'workouts.html', icon: 'bi bi-dumbbell-fill' },
        { name: 'BMI Calculator', url: 'BMI.html', icon: 'bi bi-calculator-fill' },
        { name: 'Fusion-AI', url: 'fusion-ai.html', icon: 'bi bi-robot' },
        { name: 'About', url: 'about.html', icon: 'bi bi-info-circle-fill' },
        { name: 'Contact', url: 'contact.html', icon: 'bi bi-envelope-fill' }
    ];

    let navLinks = pages.map(p => {
        const isActive = p.url === currentPage ? 'active' : '';
        return `<li class="nav-item"><a class="nav-link ${isActive}" href="${p.url}"><i class="${p.icon}"></i>${p.name}</a></li>`;
    }).join('');

    let mobileLinks = pages.map(p => {
        const isActive = p.url === currentPage ? 'active' : '';
        return `<a class="mobile-link ${isActive}" href="${p.url}"><i class="${p.icon}"></i> ${p.name}</a>`;
    }).join('');

    navbarContainer.innerHTML = `
        <nav class="fusion-navbar" id="mainNav">
            <div class="navbar-container">
                <a class="navbar-brand" href="index.html">
                    <div class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></div>
                    <span class="brand-name">Fit<span>Fusion</span></span>
                </a>
                <ul class="navbar-nav">${navLinks}</ul>
                <a href="BMI.html" class="navbar-cta"><i class="bi bi-arrow-right-circle-fill"></i> Get Started</a>
                <button class="navbar-toggler" type="button" aria-label="Toggle navigation" id="navToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </nav>
        <div class="navbar-mobile" id="navbarMobile">
            <button class="mobile-close" type="button" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
            ${mobileLinks}
            <a href="BMI.html" class="mobile-cta"><i class="bi bi-arrow-right-circle-fill"></i> Get Started</a>
        </div>`;

    addScrollEffect();
    setupMobileMenu();
}

async function loadFooter() {
    try {
        const response = await fetch('data/footer.json');
        const data = await response.json();
        renderFooter(data);
        setupNewsletterForms();
    } catch (error) {
        console.error('Error loading footer:', error);
        renderFallbackFooter();
        setupNewsletterForms();
    }
}

function renderFooter(data) {
    const footerContainer = document.getElementById('my-footer');
    if (!footerContainer) return;

    const quickLinks = data.quickLinks.map(l =>
        `<li><a href="${l.url}"><i class="bi bi-chevron-right"></i> ${l.name}</a></li>`
    ).join('');

    const services = data.services.map(s =>
        `<li><a href="${s.url}"><i class="bi bi-chevron-right"></i> ${s.name}</a></li>`
    ).join('');

    const socialIcons = data.socialIcons.map(s =>
        `<a href="${s.url}" target="_blank" rel="noopener" aria-label="${s.name}"><i class="${s.icon}"></i></a>`
    ).join('');

    const contactInfo = data.contactInfo.map(c =>
        `<div class="footer-contact-item"><i class="${c.icon}"></i> <span>${c.text}</span></div>`
    ).join('');

    footerContainer.innerHTML = `
        <footer class="footer" id="footer">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4 col-md-12">
                        <div class="footer-logo">Fit<span>Fusion</span></div>
                        <p class="footer-tagline">${data.tagline}</p>
                        <div class="footer-social">${socialIcons}</div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <h6>${data.quickLinksTitle}</h6>
                        <ul class="footer-links">${quickLinks}</ul>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <h6>${data.servicesTitle}</h6>
                        <ul class="footer-links">${services}</ul>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <h6>${data.contactTitle}</h6>
                        ${contactInfo}
                        <div class="footer-newsletter">
                            <p>${data.newsletterText}</p>
                            <form class="newsletter-form">
                                <input type="email" placeholder="Your email address" required aria-label="Email address">
                                <button type="submit" aria-label="Subscribe"><i class="bi bi-send-fill"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>${data.copyrightText}</p>
                </div>
            </div>
        </footer>`;
}

function renderFallbackFooter() {
    const footerContainer = document.getElementById('my-footer');
    if (!footerContainer) return;
    footerContainer.innerHTML = `
        <footer class="footer">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <div class="footer-logo">Fit<span>Fusion</span></div>
                        <p class="footer-tagline">Your premium fitness and wellness partner.</p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; 2026 FitFusion | Developed by <span>SYEDA HIRA</span></p>
                </div>
            </div>
        </footer>`;
}

function addScrollEffect() {
    const navbar = document.getElementById('mainNav');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 60) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

function setupMobileMenu() {
    const toggle = document.getElementById('navToggle');
    const mobileMenu = document.getElementById('navbarMobile');
    if (!toggle || !mobileMenu) return;
    toggle.setAttribute('aria-controls', 'navbarMobile');
    toggle.setAttribute('aria-expanded', 'false');
    mobileMenu.setAttribute('aria-hidden', 'true');
    const closeBtn = mobileMenu.querySelector('.mobile-close');
    function setOpen(open, restoreFocus = true) {
        mobileMenu.classList.toggle('active', open);
        document.body.classList.toggle('nav-menu-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        mobileMenu.setAttribute('aria-hidden', String(!open));
        if (open) closeBtn?.focus();
        else if (restoreFocus) toggle.focus();
    }
    toggle.addEventListener('click', () => setOpen(!mobileMenu.classList.contains('active')));
    closeBtn?.addEventListener('click', () => setOpen(false));
    mobileMenu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false, false)));
    document.addEventListener('keydown', event => {
        if (!mobileMenu.classList.contains('active')) return;
        if (event.key === 'Escape') setOpen(false);
        if (event.key === 'Tab') {
            const items = [...mobileMenu.querySelectorAll('button, a[href]')];
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.matchMedia('(min-width: 1200px)').addEventListener('change', event => {
        if (event.matches) setOpen(false, false);
    });
}

function addBackToTop() {
    let backBtn = document.getElementById('backToTop');
    if (!backBtn) {
        backBtn = document.createElement('button');
        backBtn.id = 'backToTop';
        backBtn.className = 'back-to-top';
        backBtn.setAttribute('aria-label', 'Back to top');
        backBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        document.body.appendChild(backBtn);
    }

    window.addEventListener('scroll', () => {
        backBtn.classList.toggle('visible', window.scrollY > 400);
    });

    backBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const counter = entry.target;
            const target = parseFloat(counter.getAttribute('data-target'));
            const suffix = counter.getAttribute('data-suffix') || '';
            const isDecimal = target % 1 !== 0;
            const duration = 2000;
            const steps = 60;
            let step = 0;

            const format = (value) => {
                const number = isDecimal ? value.toFixed(1) : Math.round(value).toLocaleString('en-US');
                return number + suffix;
            };

            const interval = setInterval(() => {
                step++;
                const progress = Math.min(step / steps, 1);
                counter.textContent = step >= steps ? format(target) : format(target * progress);
                if (step >= steps) clearInterval(interval);
            }, duration / steps);

            observer.unobserve(counter);
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
}

function setupNewsletterForms() {
    document.querySelectorAll('.newsletter-form').forEach(form => {
        if (form.dataset.newsletterBound) return;
        form.dataset.newsletterBound = 'true';
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = form.querySelector('input[type="email"]');
            if (input && input.value) {
                alert('Thank you for subscribing! Weekly fitness tips are on the way.');
                input.value = '';
            }
        });
    });
}

function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

function initAOS() {
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 900, once: true, offset: 80, easing: 'ease-in-out' });
    }
}

function injectWhatsAppButton() {
    if (document.querySelector('.whatsapp-float')) return;

    const waButton = document.createElement('a');
    waButton.className = 'whatsapp-float';
    waButton.setAttribute('aria-label', 'Chat on WhatsApp');
    waButton.target = '_blank';
    waButton.rel = 'noopener';
    waButton.innerHTML = `
        <svg viewBox="0 0 32 32" fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
            <path d="M16.004 3C8.832 3 3 8.832 3 16.004c0 2.292.602 4.533 1.745 6.506L3 29l6.645-1.705A12.94 12.94 0 0 0 16.004 29C23.176 29 29 23.168 29 16.004S23.176 3 16.004 3zm0 23.66c-2.123 0-4.204-.573-6.011-1.654l-.431-.256-3.938 1.011 1.05-3.84-.281-.447a10.62 10.62 0 0 1-1.633-5.659C4.76 9.217 9.803 4.34 16.004 4.34c5.771 0 10.26 4.489 10.26 10.26 0 5.772-4.489 10.26-10.26 10.26zm5.38-7.685c-.294-.147-1.742-.86-2.011-.958-.27-.098-.466-.147-.663.147-.197.294-.763.958-.935 1.154-.172.196-.344.221-.638.073-.294-.147-1.242-.458-2.365-1.46-.874-.78-1.465-1.744-1.637-2.038-.172-.294-.018-.453.13-.599.132-.132.294-.344.441-.516.147-.172.196-.294.294-.491.098-.196.049-.368-.024-.515-.074-.147-.663-1.596-.908-2.185-.239-.574-.482-.496-.663-.505l-.563-.01c-.197 0-.516.074-.786.368-.27.294-1.03 1.007-1.03 2.456 0 1.45 1.055 2.85 1.202 3.047.147.196 2.077 3.17 5.031 4.446.703.304 1.252.485 1.68.621.706.224 1.348.192 1.856.117.566-.085 1.742-.712 1.987-1.4.245-.687.245-1.276.172-1.4-.074-.122-.27-.196-.564-.343z"/>
        </svg>`;

    const setWhatsAppLink = (number) => {
        waButton.href = `https://wa.me/${number}`;
    };

    setWhatsAppLink('0000000000');

    const h = window.location.hostname + ':' + window.location.port;
    const apiBase = ((window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') && window.location.port !== '3000') || window.location.protocol === 'file:' ? 'http://localhost:3000' : '';

    fetch(`${apiBase}/api/config`)
        .then(res => res.json())
        .then(config => {
            if (config && config.whatsappNumber) {
                setWhatsAppLink(config.whatsappNumber);
            }
        })
        .catch(() => {
            setWhatsAppLink('0000000000');
        });

    document.body.appendChild(waButton);
}

function createHeroParticles() {
    const container = document.querySelector('.hero-particles');
    if (!container) return;

    const particleCount = Math.floor(Math.random() * 6) + 15;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        const size = Math.floor(Math.random() * 4) + 2;
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        const delay = Math.random() * 8;
        const duration = Math.random() * 6 + 6;

        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${left}%`;
        particle.style.top = `${top}%`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;

        container.appendChild(particle);
    }
}

function disableBackdropScroll() {
    return null;
}

document.addEventListener('DOMContentLoaded', () => {
    loadNavbar();
    loadFooter();
    injectWhatsAppButton();
    createHeroParticles();

    setTimeout(() => {
        animateCounters();
        setupNewsletterForms();
        setupSmoothScroll();
        initAOS();
        addBackToTop();
    }, 300);
});