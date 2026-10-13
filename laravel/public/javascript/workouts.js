/* ===========================================
   FitFusion – Workouts Page
   ============================================ */

async function loadWorkouts() {
    const container = document.getElementById('workoutsContainer');
    if (!container) return;

    try {
        const response = await fetch('/api/workouts', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = fitfusionEscapeData(await response.json());
        renderWorkouts(data.categories, container);
        setupWorkoutFilters(data.categories);
    } catch (error) {
        console.error('Error loading workouts:', error);
        container.innerHTML = '<p class="text-center text-muted">Unable to load workouts. Please try again later.</p>';
    }
}

function renderWorkouts(categories, container) {
    container.innerHTML = categories.map((cat, catIndex) => `
        <div class="workout-category-section" id="${cat.id}">
            <div class="workout-category-header">
                <div class="cat-icon"><i class="${cat.icon}"></i></div>
                <div>
                    <h3>${cat.title}</h3>
                    <p>Professional exercises designed for optimal results</p>
                </div>
            </div>
            <div class="workout-grid">
                ${cat.exercises.map((ex, i) => `
                    <div class="workout-card">
                        <div class="card-image">
                            <img decoding="async" src="${ex.image}" alt="${ex.name}" loading="lazy">
                            <span class="difficulty-badge difficulty-${ex.difficulty.toLowerCase()}">${ex.difficulty}</span>
                        </div>
                        <div class="card-body">
                            <h5>${ex.name}</h5>
                            <div class="workout-meta">
                                <span class="meta-item"><i class="bi bi-layers-fill"></i> ${ex.sets} Sets</span>
                                <span class="meta-item"><i class="bi bi-arrow-repeat"></i> ${ex.reps} Reps</span>
                                <span class="meta-item"><i class="bi bi-fire"></i> ${ex.calories} cal</span>
                            </div>
                            <span class="muscle-tag"><i class="bi bi-bullseye"></i> ${ex.muscle}</span>
                            <div class="tip-text">
                                <i class="bi bi-lightbulb-fill"></i> ${ex.tips}
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');

    if (typeof AOS !== 'undefined') AOS.refresh();
}

function setupWorkoutFilters(categories) {
    const filterNav = document.getElementById('workoutFilterNav');
    if (!filterNav) return;

    filterNav.innerHTML = `
        <button class="workout-filter-btn active" data-target="all">All Workouts</button>
        ${categories.map(c => `<button class="workout-filter-btn" data-target="${c.id}">${c.title}</button>`).join('')}
    `;

    filterNav.querySelectorAll('.workout-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            filterNav.querySelectorAll('.workout-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const target = btn.dataset.target;

            document.querySelectorAll('.workout-category-section').forEach(section => {
                const isVisible = target === 'all' || section.id === target;
                section.style.display = isVisible ? 'block' : 'none';
            });

            if (target !== 'all') {
                const firstVisible = document.querySelector(`.workout-category-section[id="${target}"]`);
                if (firstVisible) {
                    firstVisible.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', loadWorkouts);

