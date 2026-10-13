/* ===========================================
   FitFusion – Diet Plans Page
   ============================================ */

async function loadDietPlans() {
    const container = document.getElementById('dietPlansContainer');
    if (!container) return;

    try {
        const response = await fetch('/api/diets', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = fitfusionEscapeData(await response.json());
        renderDietPlans(data.diets, container);
    } catch (error) {
        console.error('Error loading diet plans:', error);
        container.innerHTML = '<p class="text-center text-muted">Unable to load diet plans. Please try again later.</p>';
    }
}

function renderDietPlans(diets, container) {
    container.innerHTML = diets.map((diet, index) => `
        <div class="diet-card" id="${diet.id}" data-aos="fade-up" data-aos-delay="${(index % 3) * 100}">
            <div class="diet-card-inner">
                <div class="diet-card-image">
                    <img decoding="async" src="${diet.image}" alt="${diet.title}" loading="lazy">
                    <span class="calorie-badge"><i class="${diet.icon} me-1"></i>${diet.calories}</span>
                </div>
                <div class="diet-card-content">
                    <div class="diet-icon"><i class="${diet.icon}"></i></div>
                    <h4>${diet.title}</h4>
                    <div class="meals-grid">
                        <div class="meal-item">
                            <span class="meal-label"><i class="bi bi-sunrise-fill"></i> Breakfast</span>
                            <p class="meal-text">${diet.breakfast}</p>
                        </div>
                        <div class="meal-item">
                            <span class="meal-label"><i class="bi bi-sun-fill"></i> Lunch</span>
                            <p class="meal-text">${diet.lunch}</p>
                        </div>
                        <div class="meal-item">
                            <span class="meal-label"><i class="bi bi-moon-stars-fill"></i> Dinner</span>
                            <p class="meal-text">${diet.dinner}</p>
                        </div>
                        <div class="meal-item">
                            <span class="meal-label"><i class="bi bi-cup-straw"></i> Snacks</span>
                            <p class="meal-text">${diet.snacks}</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="diet-info-box">
                                <h6><i class="bi bi-check-circle-fill"></i> Benefits</h6>
                                <ul>${diet.benefits.map(b => `<li>${b}</li>`).join('')}</ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="diet-info-box">
                                <h6><i class="bi bi-lightbulb-fill"></i> Tips</h6>
                                <ul>${diet.tips.map(t => `<li>${t}</li>`).join('')}</ul>
                            </div>
                        </div>
                    </div>
                    <div class="diet-note">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        <strong>Important:</strong> ${diet.notes}
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    if (typeof AOS !== 'undefined') AOS.refresh();
}

document.addEventListener('DOMContentLoaded', loadDietPlans);

