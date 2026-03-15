// Gestionale Hotel - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert dopo 5 secondi
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });

    // Calcolo prezzo stimato in tempo reale nel form prenotazioni
    const checkinInput = document.getElementById('data_checkin');
    const checkoutInput = document.getElementById('data_checkout');
    const cameraSelect = document.getElementById('camera_id');

    if (checkinInput && checkoutInput && cameraSelect) {
        function calcolaStima() {
            const checkin = new Date(checkinInput.value);
            const checkout = new Date(checkoutInput.value);
            if (checkin && checkout && checkout > checkin) {
                const notti = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
                const opzioneCamera = cameraSelect.options[cameraSelect.selectedIndex];
                if (opzioneCamera && opzioneCamera.value) {
                    const match = opzioneCamera.text.match(/€([\d.,]+)/);
                    if (match) {
                        const prezzo = parseFloat(match[1].replace('.', '').replace(',', '.'));
                        const totale = prezzo * notti;

                        let stimaEl = document.getElementById('stima-prezzo');
                        if (!stimaEl) {
                            stimaEl = document.createElement('div');
                            stimaEl.id = 'stima-prezzo';
                            stimaEl.style.cssText = 'margin-top:0.5rem;padding:0.5rem 1rem;background:#eff6ff;border-radius:8px;color:#1e40af;font-weight:500;';
                            checkoutInput.parentNode.appendChild(stimaEl);
                        }
                        stimaEl.textContent = notti + ' nott' + (notti === 1 ? 'e' : 'i') + ' - Totale stimato: € ' + totale.toFixed(2).replace('.', ',');
                    }
                }
            }
        }

        checkinInput.addEventListener('change', calcolaStima);
        checkoutInput.addEventListener('change', calcolaStima);
        cameraSelect.addEventListener('change', calcolaStima);
    }

    // Validazione date: checkout deve essere dopo checkin
    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', function() {
            checkoutInput.min = checkinInput.value;
            if (checkoutInput.value && checkoutInput.value <= checkinInput.value) {
                checkoutInput.value = '';
            }
        });
    }
});
