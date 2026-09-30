// KETEMU PENS — progressive enhancement only.
// The verification and pickup-code forms stay deliberately plain HTML so they
// work on any device security staff or students happen to use.

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash messages after a short delay.
    document.querySelectorAll('[data-flash]').forEach((flash) => {
        setTimeout(() => {
            flash.style.transition = 'opacity 300ms ease, transform 300ms ease';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-4px)';
            setTimeout(() => flash.remove(), 320);
        }, 6000);
    });

    // Give confirmation prompts explicit Indonesian copy.
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('submit', (event) => {
            if (!window.confirm(element.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Pickup code inputs: uppercase and group as the user types.
    document.querySelectorAll('[data-pickup-code]').forEach((input) => {
        input.addEventListener('input', () => {
            const cleaned = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
            input.value = cleaned.length > 4 ? `${cleaned.slice(0, 4)}-${cleaned.slice(4)}` : cleaned;
        });
    });
});
