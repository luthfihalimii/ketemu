// KETEMU PENS — progressive enhancement only.
// The verification and pickup-code forms stay deliberately plain HTML so they
// work on any device security staff or students happen to use.

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('details').forEach((menu) => {
        menu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                menu.open = false;
                menu.querySelector('summary')?.focus();
            }
        });
    });
    // Feedback stays visible until explicitly dismissed.
    document.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.classList.remove('hidden');
        button.addEventListener('click', () => button.closest('[data-flash]')?.remove());
    });

    document.querySelectorAll('[data-copy-code]').forEach((button) => {
        if (!navigator.clipboard || !window.isSecureContext) return;
        button.classList.remove('hidden');
        button.addEventListener('click', async () => {
            const status = document.getElementById('copy-code-status');
            try {
                await navigator.clipboard.writeText(document.getElementById(button.dataset.copyCode).textContent.trim());
                status.textContent = 'Kode berhasil disalin. Jangan bagikan kepada orang lain.';
            } catch {
                status.textContent = 'Kode tidak dapat disalin otomatis. Pilih teks kode dan salin secara manual.';
            }
        });
    });

    document.querySelectorAll('[data-photo-upload]').forEach((input) => {
        const preview = document.getElementById(`${input.id}-preview`);
        const feedback = document.getElementById(`${input.id}-feedback`);
        let objectUrl;
        input.addEventListener('change', () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            preview.hidden = true;
            preview.removeAttribute('src');
            input.setCustomValidity('');
            feedback.textContent = '';
            const file = input.files[0];
            if (!file) return;
            const error = !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
                ? 'Pilih foto JPG, PNG, atau WEBP.' : file.size > 5 * 1024 * 1024 ? 'Ukuran foto maksimal 5 MB.' : '';
            if (error) {
                input.setCustomValidity(error);
                feedback.textContent = error;
                input.reportValidity();
                return;
            }
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.onload = () => {
                if (preview.naturalWidth > 4000 || preview.naturalHeight > 4000) {
                    input.setCustomValidity('Dimensi foto maksimal 4000 × 4000 piksel.');
                    feedback.textContent = input.validationMessage;
                    input.reportValidity();
                } else {
                    preview.hidden = false;
                }
                URL.revokeObjectURL(objectUrl);
            };
            preview.onerror = () => {
                input.setCustomValidity('Foto tidak dapat dibaca. Pilih foto lain.');
                feedback.textContent = input.validationMessage;
                URL.revokeObjectURL(objectUrl);
            };
        });
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

    // QR scan untuk satpam: BarcodeDetector bawaan browser (Chrome/Edge Android).
    // Tanpa dependency tambahan; kalau tidak didukung, tombol tetap tersembunyi
    // dan satpam mengetik manual seperti biasa.
    document.querySelectorAll('[data-qr-scan]').forEach((button) => {
        const status = document.querySelector('[data-qr-scan-status]');
        const video = document.querySelector('[data-qr-scan-video]');
        const target = document.getElementById(button.dataset.qrScan);
        if (!target || !video || !('BarcodeDetector' in window)) return;
        let detector;
        try {
            detector = new window.BarcodeDetector({ formats: ['qr_code'] });
        } catch {
            return;
        }
        button.classList.remove('hidden');
        let stream;
        let scanning = false;
        const stop = () => {
            scanning = false;
            if (stream) stream.getTracks().forEach((track) => track.stop());
            video.hidden = true;
            button.textContent = 'Scan QR mahasiswa';
        };
        button.addEventListener('click', async () => {
            if (scanning) {
                stop();
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            } catch {
                status.textContent = 'Kamera tidak dapat diakses. Ketik kode manual.';
                return;
            }
            video.srcObject = stream;
            await video.play();
            video.hidden = false;
            scanning = true;
            button.textContent = 'Berhenti scan';
            status.textContent = 'Arahkan kamera ke QR mahasiswa…';
            const tick = async () => {
                if (!scanning) return;
                try {
                    const codes = await detector.detect(video);
                    const value = codes[0]?.rawValue?.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8) ?? '';
                    if (value.length === 8) {
                        target.value = value.length > 4 ? `${value.slice(0, 4)}-${value.slice(4)}` : value;
                        status.textContent = 'QR terbaca. Periksa kode lalu tekan Verifikasi.';
                        stop();
                        return;
                    }
                } catch {
                    // Abaikan frame gagal, lanjut scan.
                }
                requestAnimationFrame(() => setTimeout(tick, 300));
            };
            tick();
        });
    });
});
