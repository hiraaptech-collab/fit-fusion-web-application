/* ===========================================
   FitFusion – Contact Form Validation
   ============================================ */

function setupContactForm() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const fields = {
        fullName: { el: document.getElementById('fullName'), error: document.getElementById('fullNameError'), validate: (v) => v.trim().length >= 2 || 'Name must be at least 2 characters.' },
        email: { el: document.getElementById('email'), error: document.getElementById('emailError'), validate: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Please enter a valid email address.' },
        phone: { el: document.getElementById('phone'), error: document.getElementById('phoneError'), validate: (v) => /^[\d\s+\-()]{7,20}$/.test(v) || 'Please enter a valid phone number.' },
        subject: { el: document.getElementById('subject'), error: document.getElementById('subjectError'), validate: (v) => v.trim().length >= 3 || 'Subject must be at least 3 characters.' },
        message: { el: document.getElementById('message'), error: document.getElementById('messageError'), validate: (v) => v.trim().length >= 10 || 'Message must be at least 10 characters.' }
    };

    function validateField(key) {
        const field = fields[key];
        const result = field.validate(field.el.value);
        if (result === true) {
            field.el.classList.remove('is-invalid');
            field.el.classList.add('is-valid');
            field.error.textContent = '';
            return true;
        }
        field.el.classList.remove('is-valid');
        field.el.classList.add('is-invalid');
        field.error.textContent = result;
        return false;
    }

    Object.keys(fields).forEach(key => {
        fields[key].el.addEventListener('blur', () => validateField(key));
        fields[key].el.addEventListener('input', () => {
            if (fields[key].el.classList.contains('is-invalid')) validateField(key);
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let isValid = true;
        Object.keys(fields).forEach(key => {
            if (!validateField(key)) isValid = false;
        });

        if (isValid) {
            const button = form.querySelector('button[type=submit]');
            if (button.disabled) return;
            button.disabled = true;
            let failure = document.getElementById('contactFailure');
            if (!failure) { failure = document.createElement('p'); failure.id = 'contactFailure'; failure.setAttribute('role','alert'); failure.style.color = '#854129'; form.prepend(failure); }
            failure.textContent = '';
            try {
                await fitfusionSubmit('/api/contact', Object.fromEntries(new FormData(form)));
                const successMsg = document.getElementById('formSuccess');
                successMsg.style.display = 'block'; form.reset();
                Object.values(fields).forEach(field => field.el.classList.remove('is-valid', 'is-invalid'));
                setTimeout(() => { successMsg.style.display = 'none'; }, 5000);
            } catch (error) { failure.textContent = error.message; }
            finally { button.disabled = false; }
        }
    });
}

document.addEventListener('DOMContentLoaded', setupContactForm);


