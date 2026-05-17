<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$inviata = false;
$errore = '';
$nomeInviato = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomeSposi = trim($_POST['nome_sposi'] ?? '');
    $emailSposi = trim($_POST['email_sposi'] ?? '');
    $telefonoSposi = trim($_POST['telefono_sposi'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $camereJson = $_POST['camere_json'] ?? '[]';

    if (!$nomeSposi) {
        $errore = 'Inserisci il nome degli sposi.';
    } else {
        $camere = json_decode($camereJson, true);
        if (empty($camere)) {
            $errore = 'Aggiungi almeno una camera.';
        } else {
            $valido = true;
            foreach ($camere as $cam) {
                if (empty($cam['nome']) || empty($cam['cognome'])) {
                    $valido = false;
                    break;
                }
                if (empty($cam['data_checkin']) || empty($cam['data_checkout']) || empty($cam['camera_id'])) {
                    $valido = false;
                    break;
                }
            }
            if (!$valido) {
                $errore = 'Compila tutti i campi obbligatori per ogni camera (date, camera, nome e cognome).';
            } else {
                // Verifica disponibilita in tempo reale e crea prenotazioni
                $db = getDB();
                $tuttoOk = true;
                $cameraOccupata = '';

                // Controlla prima tutte le disponibilita
                foreach ($camere as $cam) {
                    if (!cameraDisponibile((int)$cam['camera_id'], $cam['data_checkin'], $cam['data_checkout'])) {
                        $tuttoOk = false;
                        $cameraOccupata = $cam['camera_label'] ?? 'Camera #' . $cam['camera_id'];
                        break;
                    }
                }

                if (!$tuttoOk) {
                    $errore = 'La camera "' . $cameraOccupata . '" non e\' piu\' disponibile per le date selezionate. Riprova scegliendo un\'altra camera.';
                } else {
                    // Tutto disponibile: crea clienti e prenotazioni
                    try {
                        $db->beginTransaction();

                        foreach ($camere as $cam) {
                            // Crea cliente
                            $clienteId = salvaCliente([
                                'nome' => $cam['nome'],
                                'cognome' => $cam['cognome'],
                                'email' => $cam['email'] ?? '',
                                'telefono' => $cam['telefono'] ?? '',
                                'documento_tipo' => $cam['documento_tipo'] ?? 'carta_identita',
                                'documento_numero' => $cam['documento_numero'] ?? '',
                                'note' => !empty($cam['documento_foto'])
                                    ? 'Foto documento: ' . $cam['documento_foto'] . ' | Prenotazione sposi: ' . $nomeSposi
                                    : 'Prenotazione sposi: ' . $nomeSposi,
                            ]);

                            // Calcola prezzo con tariffe sposi
                            $checkin = new DateTime($cam['data_checkin']);
                            $checkout = new DateTime($cam['data_checkout']);
                            $notti = $checkin->diff($checkout)->days;
                            $prezzoTotale = getPrezzoPerOspiti((int)$cam['num_ospiti']) * $notti;

                            // Chi paga
                            $pagamento = ($cam['pagamento'] ?? 'sposi') === 'cliente' ? 'cliente' : 'sposi';

                            // Note con ospiti extra
                            $notePren = 'Sposi: ' . $nomeSposi;
                            if (!empty($cam['altri_ospiti'])) {
                                $nomiExtra = [];
                                foreach ($cam['altri_ospiti'] as $ao) {
                                    $nomiExtra[] = $ao['nome'] . ' ' . $ao['cognome'];
                                }
                                $notePren .= ' | Altri ospiti: ' . implode(', ', $nomiExtra);
                            }
                            if ($note) $notePren .= ' | ' . $note;

                            // Crea prenotazione
                            $stmt = $db->prepare('INSERT INTO prenotazioni (camera_id, cliente_id, data_checkin, data_checkout, stato, pagamento, num_ospiti, prezzo_totale, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute([
                                (int)$cam['camera_id'],
                                $clienteId,
                                $cam['data_checkin'],
                                $cam['data_checkout'],
                                'confermata',
                                $pagamento,
                                (int)$cam['num_ospiti'],
                                $prezzoTotale,
                                $notePren,
                            ]);
                        }

                        $db->commit();
                        $inviata = true;
                        $nomeInviato = $nomeSposi;
                    } catch (Exception $ex) {
                        $db->rollBack();
                        $errore = 'Errore nel salvataggio. Riprova.';
                    }
                }
            }
        }
    }
}

$apiBase = BASE_URL . 'api/';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richiesta Prenotazione Camere</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-width: 750px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
        }
        .card-header { text-align: center; margin-bottom: 2rem; }
        .card-header h1 { font-size: 1.6rem; color: #1e293b; margin-bottom: 0.5rem; }
        .card-header p { color: #64748b; font-size: 0.95rem; }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 600; color: #475569; font-size: 0.88rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.55rem 0.8rem; border: 2px solid #e2e8f0;
            border-radius: 10px; font-size: 0.95rem; transition: border-color 0.2s; font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
        }
        .form-row { display: flex; gap: 0.75rem; }
        .form-row .form-group { flex: 1; }

        /* Sezione sposi */
        .sezione-sposi {
            background: #faf5ff; border: 2px solid #e9d5ff; border-radius: 14px;
            padding: 1.2rem; margin-bottom: 1.5rem;
        }
        .sezione-sposi h3 { font-size: 1rem; color: #6d28d9; margin-bottom: 0.8rem; }

        /* Camera block */
        .camera-block {
            background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 14px;
            padding: 1.2rem; margin-bottom: 1rem; position: relative;
            transition: border-color 0.2s;
        }
        .camera-block:hover { border-color: #c4b5fd; }
        .camera-block-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e2e8f0;
        }
        .camera-block-header h4 { font-size: 1rem; color: #334155; }
        .camera-block-header .prezzo-camera { font-weight: 700; color: #7c3aed; font-size: 0.95rem; }

        .btn-rimuovi-camera {
            background: #fee2e2; color: #dc2626; border: none; border-radius: 8px;
            padding: 0.4rem 0.7rem; cursor: pointer; font-size: 0.85rem; font-weight: 600;
        }
        .btn-rimuovi-camera:hover { background: #fecaca; }

        /* Date + camera select */
        .camera-date-row { display: flex; gap: 0.75rem; margin-bottom: 0.8rem; }
        .camera-date-row .form-group { flex: 1; }

        .camera-select-row { margin-bottom: 0.8rem; }
        .camera-select-row select { width: 100%; padding: 0.55rem 0.8rem; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; }

        .ospiti-row { margin-bottom: 0.8rem; }

        /* Guest info */
        .guest-info { background: #fff; border-radius: 10px; padding: 1rem; border: 1px solid #e2e8f0; }
        .guest-info h5 { font-size: 0.9rem; color: #475569; margin-bottom: 0.6rem; }

        .documento-upload {
            display: flex; align-items: center; gap: 0.75rem; margin-top: 0.3rem;
        }
        .documento-upload input[type="file"] {
            border: none; padding: 0; font-size: 0.85rem;
        }
        .upload-status {
            font-size: 0.8rem; font-weight: 600; padding: 0.2rem 0.5rem;
            border-radius: 6px;
        }
        .upload-ok { background: #dcfce7; color: #166534; }
        .upload-err { background: #fef2f2; color: #991b1b; }
        .upload-loading { background: #eff6ff; color: #1e40af; }

        /* No room msg */
        .no-camere {
            text-align: center; padding: 1rem; color: #dc2626; font-weight: 500;
            background: #fef2f2; border-radius: 10px; font-size: 0.9rem;
        }
        .loading-camere {
            text-align: center; padding: 1rem; color: #1e40af;
            background: #eff6ff; border-radius: 10px; font-size: 0.9rem;
        }

        /* Buttons */
        .btn-aggiungi {
            background: #ede9fe; color: #7c3aed; border: 2px dashed #c4b5fd;
            border-radius: 10px; padding: 0.7rem; width: 100%; cursor: pointer;
            font-size: 0.9rem; font-weight: 600; transition: background 0.2s;
            margin-bottom: 1.5rem;
        }
        .btn-aggiungi:hover { background: #ddd6fe; }

        .prezzi-ref {
            display: grid; grid-template-columns: repeat(2,1fr); gap: 0.4rem;
            margin-bottom: 1rem; font-size: 0.85rem; color: #64748b;
        }
        .prezzi-ref span { background: #f1f5f9; padding: 0.35rem 0.6rem; border-radius: 6px; text-align: center; }
        .prezzi-ref strong { color: #7c3aed; }

        .riepilogo {
            background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
            border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1.5rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .riepilogo .totale-label { font-size: 0.95rem; opacity: 0.9; }
        .riepilogo .totale-prezzo { font-size: 1.5rem; font-weight: 800; }
        .riepilogo .totale-dettaglio { font-size: 0.8rem; opacity: 0.75; }

        .btn-invia {
            background: linear-gradient(135deg, #7c3aed, #6d28d9); color: #fff;
            border: none; border-radius: 12px; padding: 0.85rem; width: 100%;
            font-size: 1.05rem; font-weight: 700; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-invia:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,58,237,0.35); }
        .btn-invia:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        .errore { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1.2rem; font-weight: 500; }
        .successo { text-align: center; padding: 2rem 0; }
        .successo .icona { font-size: 3.5rem; margin-bottom: 1rem; }
        .successo h2 { color: #166534; margin-bottom: 0.5rem; }
        .successo p { color: #64748b; }

        @media (max-width: 600px) {
            .card { padding: 1.5rem; }
            .form-row, .camera-date-row { flex-direction: column; gap: 0; }
            .prezzi-ref { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="card">
<?php if ($inviata): ?>
    <div class="successo">
        <div class="icona">&#10004;</div>
        <h2>Prenotazione confermata!</h2>
        <p>Grazie <?= e($nomeInviato) ?>! La prenotazione e' stata registrata con successo.<br>
        Le camere sono state riservate per le date selezionate.</p>
    </div>
<?php else: ?>
    <div class="card-header">
        <h1>Richiesta Prenotazione Camere</h1>
        <p>Compila il modulo per richiedere la prenotazione delle camere per il soggiorno.</p>
    </div>

    <?php if ($errore): ?>
        <div class="errore"><?= e($errore) ?></div>
    <?php endif; ?>

    <form method="POST" id="formRichiesta">
        <input type="hidden" name="camere_json" id="camereJson" value="[]">

        <div class="sezione-sposi">
            <h3>Dati degli Sposi</h3>
            <div class="form-group">
                <label>Nome e Cognome Sposi *</label>
                <input type="text" name="nome_sposi" id="nomeSposi" required placeholder="Mario Rossi e Giulia Bianchi" value="<?= e($_POST['nome_sposi'] ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email Sposi</label>
                    <input type="email" name="email_sposi" id="emailSposi" placeholder="email@esempio.com" value="<?= e($_POST['email_sposi'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Telefono Sposi</label>
                    <input type="tel" name="telefono_sposi" id="telefonoSposi" placeholder="+39 333 1234567" value="<?= e($_POST['telefono_sposi'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="prezzi-ref">
            <span>1 persona: <strong>&euro;80</strong>/notte</span>
            <span>2 persone: <strong>&euro;110</strong>/notte</span>
            <span>3 persone: <strong>&euro;130</strong>/notte</span>
            <span>4 persone: <strong>&euro;150</strong>/notte</span>
        </div>

        <div id="listaCamere"></div>

        <button type="button" class="btn-aggiungi" id="btnAggiungi" onclick="aggiungiCamera()">+ Aggiungi camera</button>

        <div class="riepilogo" id="riepilogo" style="display:none;">
            <div>
                <div class="totale-label">Totale stimato</div>
                <div class="totale-dettaglio" id="dettaglioTotale"></div>
            </div>
            <div class="totale-prezzo" id="totalePrezzo">&euro;0</div>
        </div>

        <div class="form-group">
            <label>Note aggiuntive</label>
            <textarea name="note" rows="3" placeholder="Richieste particolari, orari di arrivo..."><?= e($_POST['note'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-invia" id="btnInvia">Invia richiesta</button>
    </form>

    <script>
    const API_BASE = <?= json_encode($apiBase) ?>;
    const UPLOAD_URL = API_BASE + 'upload-documento.php';
    const PREZZI = {1: 80, 2: 110, 3: 130, 4: 150};
    const CAPACITA_TIPO = {singola: 1, doppia: 2, tripla: 3, quadrupla: 4, suite: 4};
    let cameraCounter = 0;
    let camereData = [];
    let camereCaricate = {}; // idx -> array di camere dal server

    function aggiungiCamera() {
        cameraCounter++;
        const idx = cameraCounter;
        const block = document.createElement('div');
        block.className = 'camera-block';
        block.id = 'camera-block-' + idx;
        block.dataset.idx = idx;

        block.innerHTML = `
            <div class="camera-block-header">
                <h4>Camera <span class="cam-num">${document.querySelectorAll('.camera-block').length + 1}</span></h4>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <span class="prezzo-camera" id="prezzo-${idx}"></span>
                    <button type="button" class="btn-rimuovi-camera" onclick="rimuoviCamera(${idx})">Rimuovi</button>
                </div>
            </div>

            <div class="camera-date-row">
                <div class="form-group">
                    <label>Check-in *</label>
                    <input type="date" id="checkin-${idx}" onchange="caricaCamerePerBlocco(${idx})" min="${getTomorrow()}">
                </div>
                <div class="form-group">
                    <label>Check-out *</label>
                    <input type="date" id="checkout-${idx}" onchange="caricaCamerePerBlocco(${idx})" min="${getTomorrow()}">
                </div>
            </div>

            <div class="form-row ospiti-row">
                <div class="form-group">
                    <label>Numero ospiti *</label>
                    <select id="ospiti-${idx}" onchange="filtraCamerePerOspiti(${idx}); aggiornaOspiti(${idx})">
                        <option value="1">1 persona - &euro;80/notte</option>
                        <option value="2" selected>2 persone - &euro;110/notte</option>
                        <option value="3">3 persone - &euro;130/notte</option>
                        <option value="4">4 persone - &euro;150/notte</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Chi paga? *</label>
                    <select id="pagamento-${idx}">
                        <option value="sposi">Pagano gli Sposi</option>
                        <option value="cliente">Paga l'Ospite</option>
                    </select>
                </div>
            </div>

            <div class="camera-select-row">
                <div class="form-group">
                    <label>Camera disponibile *</label>
                    <select id="select-camera-${idx}" onchange="aggiornaPrezzo(${idx})" disabled>
                        <option value="">-- Seleziona prima le date e il numero ospiti --</option>
                    </select>
                </div>
                <div id="camera-status-${idx}"></div>
            </div>

            <div class="guest-info">
                <h5>Ospite 1 (riferimento)</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" id="nome-${idx}" placeholder="Nome">
                    </div>
                    <div class="form-group">
                        <label>Cognome *</label>
                        <input type="text" id="cognome-${idx}" placeholder="Cognome">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email-${idx}" placeholder="email@esempio.com">
                    </div>
                    <div class="form-group">
                        <label>Telefono</label>
                        <input type="tel" id="telefono-${idx}" placeholder="+39 333 1234567">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipo documento</label>
                        <select id="documento-tipo-${idx}">
                            <option value="carta_identita">Carta d'Identita</option>
                            <option value="passaporto">Passaporto</option>
                            <option value="patente">Patente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Numero documento</label>
                        <input type="text" id="documento-${idx}" placeholder="Facoltativo">
                    </div>
                </div>
                <div class="form-group">
                    <label>Foto documento</label>
                    <div class="documento-upload">
                        <input type="file" id="foto-${idx}" accept="image/*,.pdf" onchange="uploadFoto(${idx})">
                    </div>
                    <span id="foto-status-${idx}"></span>
                    <input type="hidden" id="foto-file-${idx}" value="">
                </div>
            </div>
            <div id="extra-ospiti-${idx}"></div>
        `;

        document.getElementById('listaCamere').appendChild(block);
        aggiornaOspiti(idx);
        rinumeraCamere();
        calcolaTotale();
    }

    function aggiornaOspiti(idx) {
        var numOspiti = parseInt(document.getElementById('ospiti-' + idx).value) || 1;
        var container = document.getElementById('extra-ospiti-' + idx);
        container.innerHTML = '';
        for (var i = 2; i <= numOspiti; i++) {
            container.innerHTML += '<div class="guest-info" style="margin-top:0.5rem;">' +
                '<h5>Ospite ' + i + '</h5>' +
                '<div class="form-row">' +
                    '<div class="form-group"><label>Nome *</label>' +
                    '<input type="text" id="ospite-nome-' + idx + '-' + i + '" placeholder="Nome"></div>' +
                    '<div class="form-group"><label>Cognome *</label>' +
                    '<input type="text" id="ospite-cognome-' + idx + '-' + i + '" placeholder="Cognome"></div>' +
                '</div></div>';
        }
    }

    function rimuoviCamera(idx) {
        const block = document.getElementById('camera-block-' + idx);
        if (block) block.remove();
        rinumeraCamere();
        calcolaTotale();
    }

    function rinumeraCamere() {
        document.querySelectorAll('.camera-block').forEach(function(b, i) {
            b.querySelector('.cam-num').textContent = i + 1;
        });
    }

    function getTomorrow() {
        const d = new Date();
        d.setDate(d.getDate() + 1);
        return d.toISOString().split('T')[0];
    }

    async function caricaCamerePerBlocco(idx) {
        const checkin = document.getElementById('checkin-' + idx).value;
        const checkout = document.getElementById('checkout-' + idx).value;
        const sel = document.getElementById('select-camera-' + idx);
        const status = document.getElementById('camera-status-' + idx);

        if (!checkin || !checkout || checkin >= checkout) {
            sel.innerHTML = '<option value="">-- Seleziona date valide --</option>';
            sel.disabled = true;
            status.innerHTML = '';
            camereCaricate[idx] = [];
            aggiornaPrezzo(idx);
            return;
        }

        sel.disabled = true;
        status.innerHTML = '<div class="loading-camere">Caricamento camere disponibili...</div>';

        try {
            const resp = await fetch(API_BASE + 'camere-disponibili.php?checkin=' + checkin + '&checkout=' + checkout);
            const data = await resp.json();
            status.innerHTML = '';
            camereCaricate[idx] = data.camere || [];
            filtraCamerePerOspiti(idx);
        } catch(e) {
            status.innerHTML = '<div class="no-camere">Errore nel caricamento. Riprova.</div>';
            camereCaricate[idx] = [];
        }

        aggiornaPrezzo(idx);
    }

    function filtraCamerePerOspiti(idx) {
        const sel = document.getElementById('select-camera-' + idx);
        const status = document.getElementById('camera-status-' + idx);
        const ospiti = parseInt(document.getElementById('ospiti-' + idx).value) || 2;
        const tutteCamere = camereCaricate[idx] || [];

        if (tutteCamere.length === 0) {
            if (document.getElementById('checkin-' + idx).value && document.getElementById('checkout-' + idx).value) {
                sel.innerHTML = '<option value="">Nessuna camera disponibile</option>';
                sel.disabled = true;
                status.innerHTML = '<div class="no-camere">Nessuna camera disponibile per queste date.</div>';
            }
            aggiornaPrezzo(idx);
            return;
        }

        // Filtra per capacita e per camere gia selezionate
        const selezionate = getAltreSelezionate(idx);
        const disponibili = tutteCamere.filter(function(c) {
            if (selezionate.includes(c.id)) return false;
            const capacita = CAPACITA_TIPO[c.tipo] || 2;
            return capacita >= ospiti;
        });

        status.innerHTML = '';
        if (disponibili.length === 0) {
            sel.innerHTML = '<option value="">Nessuna camera per ' + ospiti + ' ospiti</option>';
            sel.disabled = true;
            status.innerHTML = '<div class="no-camere">Nessuna camera disponibile per ' + ospiti + ' persone in queste date.</div>';
        } else {
            sel.innerHTML = '<option value="">-- Scegli camera --</option>';
            disponibili.forEach(function(cam) {
                const opt = document.createElement('option');
                opt.value = cam.id;
                opt.textContent = '#' + cam.numero + ' - ' + cam.tipo.charAt(0).toUpperCase() + cam.tipo.slice(1) + ' (Piano ' + cam.piano + ')';
                if (cam.descrizione) opt.textContent += ' - ' + cam.descrizione;
                sel.appendChild(opt);
            });
            sel.disabled = false;
        }
        aggiornaPrezzo(idx);
    }

    function getAltreSelezionate(excludeIdx) {
        const ids = [];
        document.querySelectorAll('.camera-block').forEach(function(b) {
            const bidx = parseInt(b.dataset.idx);
            if (bidx === excludeIdx) return;
            const sel = document.getElementById('select-camera-' + bidx);
            if (sel && sel.value) ids.push(parseInt(sel.value));
        });
        return ids;
    }

    function aggiornaPrezzo(idx) {
        const checkin = document.getElementById('checkin-' + idx).value;
        const checkout = document.getElementById('checkout-' + idx).value;
        const ospiti = parseInt(document.getElementById('ospiti-' + idx).value) || 2;
        const prezzoEl = document.getElementById('prezzo-' + idx);

        if (checkin && checkout && checkout > checkin) {
            const notti = Math.round((new Date(checkout) - new Date(checkin)) / 86400000);
            const prezzoNotte = PREZZI[ospiti] || 0;
            prezzoEl.textContent = '\u20AC' + (prezzoNotte * notti) + ' (' + notti + 'n x \u20AC' + prezzoNotte + ')';
        } else {
            prezzoEl.textContent = '';
        }
        calcolaTotale();
    }

    function calcolaTotale() {
        const blocks = document.querySelectorAll('.camera-block');
        let totale = 0;
        let numCamere = 0;

        blocks.forEach(function(b) {
            const idx = parseInt(b.dataset.idx);
            const checkin = document.getElementById('checkin-' + idx).value;
            const checkout = document.getElementById('checkout-' + idx).value;
            const ospiti = parseInt(document.getElementById('ospiti-' + idx).value) || 2;

            if (checkin && checkout && checkout > checkin) {
                const notti = Math.round((new Date(checkout) - new Date(checkin)) / 86400000);
                totale += (PREZZI[ospiti] || 0) * notti;
                numCamere++;
            }
        });

        const riepilogo = document.getElementById('riepilogo');
        if (numCamere > 0) {
            riepilogo.style.display = 'flex';
            document.getElementById('totalePrezzo').textContent = '\u20AC' + totale;
            document.getElementById('dettaglioTotale').textContent = numCamere + ' camer' + (numCamere === 1 ? 'a' : 'e');
        } else {
            riepilogo.style.display = 'none';
        }
    }

    async function uploadFoto(idx) {
        const input = document.getElementById('foto-' + idx);
        const status = document.getElementById('foto-status-' + idx);
        const hidden = document.getElementById('foto-file-' + idx);

        if (!input.files[0]) return;

        status.innerHTML = '<span class="upload-status upload-loading">Caricamento...</span>';

        const fd = new FormData();
        fd.append('foto', input.files[0]);

        try {
            const resp = await fetch(UPLOAD_URL, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.ok) {
                hidden.value = data.file;
                status.innerHTML = '<span class="upload-status upload-ok">Caricato</span>';
            } else {
                status.innerHTML = '<span class="upload-status upload-err">' + (data.error || 'Errore') + '</span>';
                hidden.value = '';
            }
        } catch(e) {
            status.innerHTML = '<span class="upload-status upload-err">Errore rete</span>';
            hidden.value = '';
        }
    }

    // Raccolta dati prima dell'invio
    document.getElementById('formRichiesta').addEventListener('submit', function(e) {
        const blocks = document.querySelectorAll('.camera-block');
        if (blocks.length === 0) {
            e.preventDefault();
            alert('Aggiungi almeno una camera.');
            return;
        }

        const camere = [];
        let valido = true;

        blocks.forEach(function(b) {
            const idx = parseInt(b.dataset.idx);
            const checkin = document.getElementById('checkin-' + idx).value;
            const checkout = document.getElementById('checkout-' + idx).value;
            const cameraId = document.getElementById('select-camera-' + idx).value;
            const ospiti = document.getElementById('ospiti-' + idx).value;
            const pagamento = document.getElementById('pagamento-' + idx).value;
            const nome = document.getElementById('nome-' + idx).value.trim();
            const cognome = document.getElementById('cognome-' + idx).value.trim();
            const email = document.getElementById('email-' + idx).value.trim();
            const telefono = document.getElementById('telefono-' + idx).value.trim();
            const documentoTipo = document.getElementById('documento-tipo-' + idx).value;
            const documento = document.getElementById('documento-' + idx).value.trim();
            const fotoFile = document.getElementById('foto-file-' + idx).value;

            if (!checkin || !checkout || !cameraId || !nome || !cognome) {
                valido = false;
                return;
            }

            // Raccolta ospiti extra
            var numOspiti = parseInt(ospiti);
            var altriOspiti = [];
            for (var i = 2; i <= numOspiti; i++) {
                var oNome = document.getElementById('ospite-nome-' + idx + '-' + i);
                var oCognome = document.getElementById('ospite-cognome-' + idx + '-' + i);
                if (oNome && oCognome) {
                    var n = oNome.value.trim();
                    var c = oCognome.value.trim();
                    if (!n || !c) { valido = false; return; }
                    altriOspiti.push({nome: n, cognome: c});
                }
            }

            const selEl = document.getElementById('select-camera-' + idx);
            const cameraLabel = selEl.options[selEl.selectedIndex].textContent;

            camere.push({
                data_checkin: checkin,
                data_checkout: checkout,
                camera_id: parseInt(cameraId),
                camera_label: cameraLabel,
                num_ospiti: numOspiti,
                pagamento: pagamento,
                nome: nome,
                cognome: cognome,
                email: email,
                telefono: telefono,
                documento_tipo: documentoTipo,
                documento_numero: documento,
                documento_foto: fotoFile,
                altri_ospiti: altriOspiti
            });
        });

        if (!valido) {
            e.preventDefault();
            alert('Compila tutti i campi obbligatori per ogni camera (date, camera, nome e cognome).');
            return;
        }

        document.getElementById('camereJson').value = JSON.stringify(camere);
    });

    // Prima camera di default
    aggiungiCamera();
    </script>
<?php endif; ?>
</div>
</body>
</html>
