<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$inviata = false;
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_sposi'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $checkin = $_POST['data_checkin'] ?? '';
    $checkout = $_POST['data_checkout'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $ospiti = $_POST['ospiti'] ?? [];

    if (!$nome || !$email || !$telefono || !$checkin || !$checkout || empty($ospiti)) {
        $errore = 'Compila tutti i campi obbligatori e aggiungi almeno una camera.';
    } elseif ($checkin >= $checkout) {
        $errore = 'La data di checkout deve essere successiva al check-in.';
    } else {
        $camere = [];
        foreach ($ospiti as $num) {
            $num = (int)$num;
            if ($num >= 1 && $num <= 4) {
                $camere[] = ['num_ospiti' => $num];
            }
        }
        if (empty($camere)) {
            $errore = 'Aggiungi almeno una camera valida.';
        } else {
            $ok = salvaRichiestaSposi([
                'nome_sposi' => $nome,
                'email' => $email,
                'telefono' => $telefono,
                'data_checkin' => $checkin,
                'data_checkout' => $checkout,
                'camere' => json_encode($camere),
                'note' => $note,
            ]);
            if ($ok) {
                $inviata = true;
            } else {
                $errore = 'Errore nel salvataggio. Riprova.';
            }
        }
    }
}
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-width: 600px;
            width: 100%;
            padding: 2.5rem;
        }
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .card-header h1 {
            font-size: 1.6rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        .card-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group { flex: 1; }
        .camere-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .camere-section h3 {
            font-size: 1rem;
            color: #334155;
            margin-bottom: 0.75rem;
        }
        .camera-riga {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 0.75rem;
            background: #fff;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        .camera-riga .camera-label {
            font-weight: 600;
            color: #475569;
            min-width: 80px;
            font-size: 0.9rem;
        }
        .camera-riga select {
            flex: 1;
            padding: 0.45rem 0.6rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fff;
        }
        .camera-riga select:focus {
            outline: none;
            border-color: #7c3aed;
        }
        .camera-riga .prezzo-camera {
            font-weight: 700;
            color: #7c3aed;
            min-width: 70px;
            text-align: right;
            font-size: 0.95rem;
        }
        .camera-riga .btn-rimuovi {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .camera-riga .btn-rimuovi:hover { background: #fecaca; }
        .btn-aggiungi {
            background: #ede9fe;
            color: #7c3aed;
            border: 2px dashed #c4b5fd;
            border-radius: 10px;
            padding: 0.6rem;
            width: 100%;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-aggiungi:hover { background: #ddd6fe; }
        .prezzi-ref {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.4rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #64748b;
        }
        .prezzi-ref span {
            background: #f1f5f9;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            text-align: center;
        }
        .prezzi-ref strong { color: #7c3aed; }
        .riepilogo {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
            border-radius: 12px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .riepilogo .totale-label { font-size: 0.95rem; opacity: 0.9; }
        .riepilogo .totale-prezzo { font-size: 1.5rem; font-weight: 800; }
        .riepilogo .totale-dettaglio { font-size: 0.8rem; opacity: 0.75; }
        .btn-invia {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            width: 100%;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-invia:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(124,58,237,0.35);
        }
        .errore {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.2rem;
            font-weight: 500;
        }
        .successo {
            text-align: center;
            padding: 2rem 0;
        }
        .successo .icona { font-size: 3.5rem; margin-bottom: 1rem; }
        .successo h2 { color: #166534; margin-bottom: 0.5rem; }
        .successo p { color: #64748b; }
        @media (max-width: 600px) {
            .card { padding: 1.5rem; }
            .form-row { flex-direction: column; gap: 0; }
            .prezzi-ref { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<div class="card">
<?php if ($inviata): ?>
    <div class="successo">
        <div class="icona">&#10004;</div>
        <h2>Richiesta inviata!</h2>
        <p>Grazie <?= e($nome) ?>! Abbiamo ricevuto la tua richiesta.<br>
        Ti contatteremo al piu presto per confermare la disponibilita.</p>
    </div>
<?php else: ?>
    <div class="card-header">
        <h1>Richiesta Prenotazione Camere</h1>
        <p>Compila il modulo per richiedere la prenotazione delle camere per il tuo soggiorno.</p>
    </div>

    <?php if ($errore): ?>
        <div class="errore"><?= e($errore) ?></div>
    <?php endif; ?>

    <form method="POST" id="formRichiesta">
        <div class="form-group">
            <label>Nome e Cognome *</label>
            <input type="text" name="nome_sposi" required value="<?= e($_POST['nome_sposi'] ?? '') ?>" placeholder="Mario Rossi e Giulia Bianchi">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="email@esempio.com">
            </div>
            <div class="form-group">
                <label>Telefono *</label>
                <input type="tel" name="telefono" required value="<?= e($_POST['telefono'] ?? '') ?>" placeholder="+39 333 1234567">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Check-in *</label>
                <input type="date" name="data_checkin" required value="<?= e($_POST['data_checkin'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Check-out *</label>
                <input type="date" name="data_checkout" required value="<?= e($_POST['data_checkout'] ?? '') ?>">
            </div>
        </div>

        <div class="camere-section">
            <h3>Camere richieste</h3>
            <div class="prezzi-ref">
                <span>1 persona: <strong>&euro;80</strong>/notte</span>
                <span>2 persone: <strong>&euro;110</strong>/notte</span>
                <span>3 persone: <strong>&euro;130</strong>/notte</span>
                <span>4 persone: <strong>&euro;150</strong>/notte</span>
            </div>
            <div id="listaCamere"></div>
            <button type="button" class="btn-aggiungi" onclick="aggiungiCamera()">+ Aggiungi camera</button>
        </div>

        <div class="riepilogo" id="riepilogo" style="display:none;">
            <div>
                <div class="totale-label">Totale stimato</div>
                <div class="totale-dettaglio" id="dettaglioNotti"></div>
            </div>
            <div class="totale-prezzo" id="totalePrezzo">&euro;0</div>
        </div>

        <div class="form-group">
            <label>Note aggiuntive</label>
            <textarea name="note" rows="3" placeholder="Richieste particolari, orari di arrivo..."><?= e($_POST['note'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-invia">Invia richiesta</button>
    </form>

    <script>
    const PREZZI = {1: 80, 2: 110, 3: 130, 4: 150};
    let contatore = 0;

    function aggiungiCamera() {
        contatore++;
        const div = document.createElement('div');
        div.className = 'camera-riga';
        div.id = 'camera-' + contatore;
        div.innerHTML = '<span class="camera-label">Camera ' + contatore + '</span>' +
            '<select name="ospiti[]" onchange="calcolaTotale()">' +
            '<option value="1">1 persona</option>' +
            '<option value="2" selected>2 persone</option>' +
            '<option value="3">3 persone</option>' +
            '<option value="4">4 persone</option>' +
            '</select>' +
            '<span class="prezzo-camera"></span>' +
            '<button type="button" class="btn-rimuovi" onclick="rimuoviCamera(this)">&times;</button>';
        document.getElementById('listaCamere').appendChild(div);
        rinumeraCamere();
        calcolaTotale();
    }

    function rimuoviCamera(btn) {
        btn.closest('.camera-riga').remove();
        rinumeraCamere();
        calcolaTotale();
    }

    function rinumeraCamere() {
        const righe = document.querySelectorAll('.camera-riga');
        righe.forEach(function(r, i) {
            r.querySelector('.camera-label').textContent = 'Camera ' + (i + 1);
        });
    }

    function calcolaTotale() {
        const checkin = document.querySelector('[name="data_checkin"]').value;
        const checkout = document.querySelector('[name="data_checkout"]').value;
        const selects = document.querySelectorAll('[name="ospiti[]"]');
        const riepilogo = document.getElementById('riepilogo');

        let notti = 0;
        if (checkin && checkout) {
            const d1 = new Date(checkin);
            const d2 = new Date(checkout);
            notti = Math.max(0, Math.round((d2 - d1) / 86400000));
        }

        let totale = 0;
        selects.forEach(function(sel) {
            const ospiti = parseInt(sel.value);
            const prezzoNotte = PREZZI[ospiti] || 0;
            const riga = sel.closest('.camera-riga');
            riga.querySelector('.prezzo-camera').textContent = '\u20AC' + prezzoNotte + '/n';
            totale += prezzoNotte * notti;
        });

        if (selects.length > 0 && notti > 0) {
            riepilogo.style.display = 'flex';
            document.getElementById('totalePrezzo').textContent = '\u20AC' + totale;
            document.getElementById('dettaglioNotti').textContent = selects.length + ' camer' + (selects.length === 1 ? 'a' : 'e') + ' x ' + notti + ' nott' + (notti === 1 ? 'e' : 'i');
        } else {
            riepilogo.style.display = 'none';
        }
    }

    // Aggiungi una camera di default
    aggiungiCamera();

    // Ricalcola quando cambiano le date
    document.querySelector('[name="data_checkin"]').addEventListener('change', calcolaTotale);
    document.querySelector('[name="data_checkout"]').addEventListener('change', calcolaTotale);
    </script>
<?php endif; ?>
</div>

</body>
</html>
