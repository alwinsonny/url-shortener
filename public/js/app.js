'use strict';

const expiryToggle = document.getElementById('expiry-toggle');
const expiryField  = document.getElementById('expiry-field');
const expiryInput  = document.getElementById('expires_at');

// Show/hide the datetime picker when the checkbox is checked
if (expiryToggle && expiryField && expiryInput) {
    expiryToggle.addEventListener('change', () => {
      if (expiryToggle.checked) {
        expiryField.classList.remove('hidden');
       // const d = new Date();
        const d   = new Date(Date.now() + 60 * 1000); // default to 1 min from current date and time
        const pad = (n) => String(n).padStart(2, '0');
        expiryInput.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
                        + `T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    } else {
        expiryField.classList.add('hidden');
        expiryInput.value = '';
}
    });
}

const form      = document.getElementById('shorten-form');
const urlInput  = document.getElementById('url');
const urlError  = document.getElementById('url-error');
const submitBtn = document.getElementById('submit-btn');
const expiresTs = document.getElementById('expires_ts');

if (form && urlInput && urlError) {
    urlInput.addEventListener('input', () => {
        if (urlInput.validity.valid || urlInput.value === '') {
            urlError.classList.add('hidden');
        }
    });

    form.addEventListener('submit', (e) => {
        if (!urlInput.validity.valid) {
            e.preventDefault();
            urlError.classList.remove('hidden');
            urlInput.focus();
            return;
        }

        // Convert the local datetime to a UTC Unix timestamp before submitting.
        if (expiryInput && expiryInput.value && expiresTs) {
            expiresTs.value = Math.floor(new Date(expiryInput.value).getTime() / 1000);
        }

        if (submitBtn) {
            submitBtn.disabled    = true;
            submitBtn.textContent = 'Shortening…';
        }
    });
}

// Copy button
const copyBtn = document.getElementById('copy-btn');

if (copyBtn) {
    copyBtn.addEventListener('click', async () => {
        const url = copyBtn.getAttribute('data-url') ?? '';

        try {
            if (navigator.clipboard && window.isSecureContext) {
                    // Modern API — used on HTTPS
                await navigator.clipboard.writeText(url);
            } else {
                    // Fallback — used on HTTP (localhost)
                const ta          = document.createElement('textarea');
                ta.value          = url;
                ta.style.position = 'fixed';
                ta.style.opacity  = '0';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch { /* silent */ }
                document.body.removeChild(ta);
            }

            copyBtn.textContent = 'Copied!';
        } catch {
            copyBtn.textContent = 'Failed';
        } finally {
            setTimeout(() => { copyBtn.textContent = 'Copy'; }, 2000);
        }
    });
}