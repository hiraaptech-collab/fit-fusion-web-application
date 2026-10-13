/* ===========================================
   FitFusion – BMI Calculator
   ============================================ */

const BMI_CATEGORIES = {
    underweight: {
        label: 'Underweight',
        range: 'Below 18.5',
        class: 'bmi-underweight',
        recommendation: 'Your BMI suggests you are underweight. Focus on a nutrient-dense diet with healthy fats, complex carbohydrates, and consistent strength training to build a healthy, sustainable frame. Consult a nutritionist for a personalized plan.'
    },
    normal: {
        label: 'Normal Weight',
        range: '18.5 – 24.9',
        class: 'bmi-normal',
        recommendation: 'Excellent! Your weight is in the healthy range. Maintain your progress with a balanced diet, regular exercise, and consistent sleep. Keep up the great habits to stay on track.'
    },
    overweight: {
        label: 'Overweight',
        range: '25 – 29.9',
        class: 'bmi-overweight',
        recommendation: 'Your BMI indicates you are in the overweight range. A moderate calorie deficit combined with regular cardio and strength training can help. Explore our diet plans and workout guides to get started on a sustainable path.'
    },
    obese: {
        label: 'Obese',
        range: '30 and above',
        class: 'bmi-obese',
        recommendation: 'Your BMI falls in the obese range. Prioritize your health by combining a structured, balanced diet with gradual increases in physical activity. We strongly recommend consulting a healthcare professional before starting any program.'
    }
};

function getBMICategory(bmi) {
    if (bmi < 18.5) return 'underweight';
    if (bmi < 25) return 'normal';
    if (bmi < 30) return 'overweight';
    return 'obese';
}

function getHealthyWeightRange(heightCm) {
    const heightM = heightCm / 100;
    const min = (18.5 * heightM * heightM).toFixed(1);
    const max = (24.9 * heightM * heightM).toFixed(1);
    return { min, max };
}

function setFieldError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (!input || !error) return;
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');
    error.textContent = message;
}

function clearFieldError(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (!input || !error) return;
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    error.textContent = '';
}

function calculateBMI() {
    const heightInput = document.getElementById('bmiHeight');
    const weightInput = document.getElementById('bmiWeight');
    if (!heightInput || !weightInput) return;

    const height = parseFloat(heightInput.value);
    const weight = parseFloat(weightInput.value);
    let valid = true;

    if (!heightInput.value.trim() || isNaN(height)) {
        setFieldError('bmiHeight', 'bmiHeightError', 'Please enter your height.');
        valid = false;
    } else if (height < 100 || height > 250) {
        setFieldError('bmiHeight', 'bmiHeightError', 'Please enter a height between 100 and 250 cm.');
        valid = false;
    } else {
        clearFieldError('bmiHeight', 'bmiHeightError');
    }

    if (!weightInput.value.trim() || isNaN(weight)) {
        setFieldError('bmiWeight', 'bmiWeightError', 'Please enter your weight.');
        valid = false;
    } else if (weight < 30 || weight > 300) {
        setFieldError('bmiWeight', 'bmiWeightError', 'Please enter a weight between 30 and 300 kg.');
        valid = false;
    } else {
        clearFieldError('bmiWeight', 'bmiWeightError');
    }

    if (!valid) {
        document.getElementById('bmiResult').style.display = 'none';
        return;
    }

    const heightM = height / 100;
    const bmi = weight / (heightM * heightM);
    const bmiRounded = bmi.toFixed(1);
    const categoryKey = getBMICategory(bmi);
    const category = BMI_CATEGORIES[categoryKey];
    const healthyRange = getHealthyWeightRange(height);

    displayBMIResult(bmiRounded, category, healthyRange);
    const savePanel = document.getElementById('bmiSavePanel');
    if (savePanel) { savePanel.hidden = false; document.getElementById('bmiSaveStatus').textContent = ''; }
}

function displayBMIResult(bmi, category, healthyRange) {
    const resultSection = document.getElementById('bmiResult');
    resultSection.style.display = 'block';
    const placeholder = document.getElementById('bmiPlaceholder');
    if (placeholder) placeholder.hidden = true;
    resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    document.getElementById('bmiValue').textContent = bmi;
    document.getElementById('bmiCategory').textContent = category.label;
    document.getElementById('bmiCategory').className = `bmi-category-label ${category.class}`;
    document.getElementById('bmiRange').textContent = category.range;
    document.getElementById('healthyRange').textContent = `${healthyRange.min} – ${healthyRange.max} kg`;
    document.getElementById('bmiRecommendation').innerHTML = `<i class="bi bi-lightbulb-fill"></i><p><strong>Recommendation:</strong> ${category.recommendation}</p>`;

    const progressBar = document.getElementById('bmiProgressBar');
    const progressPercent = Math.min((parseFloat(bmi) / 40) * 100, 100);
    progressBar.style.width = `${progressPercent}%`;
    progressBar.className = `progress-bar ${category.class}`;
    progressBar.setAttribute('aria-valuenow', bmi);
}

function setupBMICalculator() {
    const form = document.getElementById('bmiForm');
    if (!form) return;

    document.getElementById('bmiSave')?.addEventListener('click', async event => {
        const button = event.currentTarget, status = document.getElementById('bmiSaveStatus');
        if (!document.getElementById('bmiSaveConsent').checked) { status.textContent = 'Please allow saving first.'; return; }
        const height = Number(document.getElementById('bmiHeight').value), weight = Number(document.getElementById('bmiWeight').value);
        if (height < 100 || height > 250 || weight < 30 || weight > 300) { calculateBMI(); status.textContent = 'Enter valid height and weight before saving.'; return; }
        button.disabled = true;
        try { await fitfusionSubmit(button.dataset.endpoint, {recorded_on:button.dataset.date, height_cm:height, weight_kg:weight, consent:true}); status.textContent = 'Saved to your fitness progress.'; }
        catch (error) { status.textContent = error.message; }
        finally { button.disabled = false; }
    });
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        calculateBMI();
    });

    document.getElementById('resetBmi')?.addEventListener('click', () => {
        form.reset();
        const savePanel = document.getElementById('bmiSavePanel'); if (savePanel) savePanel.hidden = true;
        ['bmiHeight', 'bmiWeight'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('is-valid', 'is-invalid');
        });
        ['bmiHeightError', 'bmiWeightError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });
        const result = document.getElementById('bmiResult');
        if (result) result.style.display = 'none';
        const placeholder = document.getElementById('bmiPlaceholder');
        if (placeholder) placeholder.hidden = false;
    });
}

document.addEventListener('DOMContentLoaded', setupBMICalculator);

