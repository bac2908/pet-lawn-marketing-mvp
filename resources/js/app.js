import './bootstrap';

// Make server-side success and validation messages easy to find after a redirect.
document.querySelector('[data-form-feedback]')?.focus();

const leadForm = document.querySelector('[data-lead-form]');

if (leadForm) {
    const submitButton = leadForm.querySelector('button[type="submit"]');

    leadForm.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
    });

    // Re-enable the button when returning through the browser's back/forward cache.
    window.addEventListener('pageshow', () => {
        submitButton.disabled = false;
        submitButton.removeAttribute('aria-busy');
    });
}
