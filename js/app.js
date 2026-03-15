// Gestionale Hotel - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alert dopo 5 secondi
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });

    // --- Form Prenotazione ---
    var checkinInput = document.getElementById('data_checkin');
    var checkoutInput = document.getElementById('data_checkout');
    var cameraSelect = document.getElementById('camera_id');
    var feedbackDiv = document.getElementById('disponibilita-feedback');

    if (checkinInput && checkoutInput && cameraSelect) {
        function calcolaStima() {
            var checkin = new Date(checkinInput.value);
            var checkout = new Date(checkoutInput.value);
            if (checkin && checkout && checkout > checkin) {
                var notti = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
                var opzioneCamera = cameraSelect.options[cameraSelect.selectedIndex];
                if (opzioneCamera && opzioneCamera.value) {
                    var match = opzioneCamera.text.match(/€([\d.,]+)/);
                    if (match) {
                        var prezzo = parseFloat(match[1].replace('.', '').replace(',', '.'));
                        var totale = prezzo * notti;

                        var stimaEl = document.getElementById('stima-prezzo');
                        if (!stimaEl) {
                            stimaEl = document.createElement('div');
                            stimaEl.id = 'stima-prezzo';
                            stimaEl.style.cssText = 'margin-top:0.5rem;padding:0.75rem 1rem;background:#eff6ff;border-radius:8px;color:#1e40af;font-weight:500;border:1px solid #bfdbfe;';
                            if (feedbackDiv) {
                                feedbackDiv.appendChild(stimaEl);
                            } else {
                                checkoutInput.parentNode.appendChild(stimaEl);
                            }
                        }
                        stimaEl.textContent = notti + ' nott' + (notti === 1 ? 'e' : 'i') + ' - Totale stimato: \u20AC ' + totale.toFixed(2).replace('.', ',');
                    }
                }
            }
        }

        checkinInput.addEventListener('change', calcolaStima);
        checkoutInput.addEventListener('change', calcolaStima);
        cameraSelect.addEventListener('change', calcolaStima);

        // Calcola subito se ci sono valori pre-compilati
        if (checkinInput.value && checkoutInput.value && cameraSelect.value) {
            calcolaStima();
        }
    }

    // Validazione date: checkout deve essere dopo checkin
    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', function() {
            checkoutInput.min = checkinInput.value;
            if (checkoutInput.value && checkoutInput.value <= checkinInput.value) {
                checkoutInput.value = '';
            }
        });
        // Imposta il min iniziale se checkin ha gia un valore
        if (checkinInput.value) {
            checkoutInput.min = checkinInput.value;
        }
    }

    // --- Tooltip migliorato per griglia calendario ---
    var celleGriglia = document.querySelectorAll('.calendario-tabella td.giorno-col[title]');
    celleGriglia.forEach(function(cella) {
        var title = cella.getAttribute('title');
        if (title && title.indexOf('\n') !== -1) {
            cella.removeAttribute('title');
            cella.addEventListener('mouseenter', function(e) {
                var tooltipEl = document.createElement('div');
                tooltipEl.className = 'tooltip-custom';
                tooltipEl.innerHTML = title.replace(/\n/g, '<br>');
                document.body.appendChild(tooltipEl);
                var rect = cella.getBoundingClientRect();
                tooltipEl.style.left = (rect.left + window.scrollX) + 'px';
                tooltipEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                cella._tooltip = tooltipEl;
            });
            cella.addEventListener('mouseleave', function() {
                if (cella._tooltip) {
                    cella._tooltip.remove();
                    cella._tooltip = null;
                }
            });
        }
    });
});
