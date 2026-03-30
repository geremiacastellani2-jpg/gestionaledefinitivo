<?php
$titoloPagina = 'Gestione Cibi';
require_once __DIR__ . '/../includes/header.php';

$azione = $_GET['azione'] ?? 'lista';

// --- POST: Salva / Elimina ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salva'])) {
        $id = salvaCibo([
            'id' => $_POST['id'] ?? '',
            'operatore' => trim($_POST['operatore']),
            'nome' => trim($_POST['nome']),
            'descrizione' => trim($_POST['descrizione']),
            'peso' => trim($_POST['peso']),
            'cella' => trim($_POST['cella']),
        ]);
        if (empty($_POST['id'])) {
            setFlash('success', 'Cibo aggiunto. Puoi stampare l\'etichetta.');
            redirect(BASE_URL . 'pages/cibi.php?azione=etichetta&id=' . $id);
        } else {
            setFlash('success', 'Cibo aggiornato.');
            redirect(BASE_URL . 'pages/cibi.php');
        }
    }
    if (isset($_POST['elimina'])) {
        eliminaCibo((int)$_POST['id']);
        setFlash('success', 'Cibo eliminato.');
        redirect(BASE_URL . 'pages/cibi.php');
    }
}

// --- VISTE ---
if ($azione === 'lista'):
    $cibi = getCibi();
?>

<div class="toolbar">
    <h1>Gestione Cibi</h1>
    <a href="?azione=nuovo" class="btn btn-primary">+ Aggiungi cibo</a>
</div>

<?php if (empty($cibi)): ?>
    <div class="alert alert-info">Nessun cibo registrato.</div>
<?php else: ?>
<table class="table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Operatore</th>
            <th>Descrizione</th>
            <th>Peso</th>
            <th>Cella</th>
            <th>Data inserimento</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($cibi as $cibo): ?>
        <tr>
            <td><strong><?= e($cibo['nome']) ?></strong></td>
            <td><?= e($cibo['operatore']) ?></td>
            <td><?= e($cibo['descrizione'] ?: '-') ?></td>
            <td><?= e($cibo['peso']) ?></td>
            <td><span class="badge badge-confermata"><?= e($cibo['cella']) ?></span></td>
            <td><?= date('d/m/Y', strtotime($cibo['created_at'])) ?></td>
            <td class="azioni-cell">
                <a href="?azione=modifica&id=<?= $cibo['id'] ?>" class="btn btn-sm btn-warning">Modifica</a>
                <a href="?azione=etichetta&id=<?= $cibo['id'] ?>" class="btn btn-sm btn-info">Etichetta</a>
                <a href="<?= BASE_URL ?>pages/cibo-dettaglio.php?id=<?= $cibo['id'] ?>" class="btn btn-sm btn-secondary">Dettaglio</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminare questo cibo?')">
                    <input type="hidden" name="id" value="<?= $cibo['id'] ?>">
                    <button type="submit" name="elimina" class="btn btn-sm btn-danger">Elimina</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ($azione === 'nuovo' || $azione === 'modifica'):
    $cibo = null;
    if ($azione === 'modifica' && isset($_GET['id'])) {
        $cibo = getCibo((int)$_GET['id']);
        if (!$cibo) {
            setFlash('error', 'Cibo non trovato.');
            redirect(BASE_URL . 'pages/cibi.php');
        }
    }
?>

<h1><?= $cibo ? 'Modifica' : 'Nuovo' ?> Cibo</h1>

<form method="POST" class="form">
    <?php if ($cibo): ?>
        <input type="hidden" name="id" value="<?= $cibo['id'] ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group">
            <label>Operatore *</label>
            <input type="text" name="operatore" required value="<?= e($cibo['operatore'] ?? '') ?>" placeholder="Nome operatore">
        </div>
        <div class="form-group">
            <label>Nome cibo *</label>
            <input type="text" name="nome" required value="<?= e($cibo['nome'] ?? '') ?>" placeholder="Es. Lasagna, Tiramisù...">
        </div>
    </div>

    <div class="form-group">
        <label>Descrizione</label>
        <textarea name="descrizione" rows="3" placeholder="Descrizione del prodotto, ingredienti..."><?= e($cibo['descrizione'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Peso *</label>
            <input type="text" name="peso" required value="<?= e($cibo['peso'] ?? '') ?>" placeholder="Es. 500g, 1.2kg">
        </div>
        <div class="form-group">
            <label>Cella *</label>
            <input type="text" name="cella" required value="<?= e($cibo['cella'] ?? '') ?>" placeholder="Es. A1, B3, Frigo-2">
        </div>
    </div>

    <div style="display:flex; gap:0.5rem; margin-top:1rem;">
        <button type="submit" name="salva" class="btn btn-primary"><?= $cibo ? 'Aggiorna' : 'Aggiungi' ?></button>
        <a href="<?= BASE_URL ?>pages/cibi.php" class="btn btn-secondary">Annulla</a>
    </div>
</form>

<?php elseif ($azione === 'etichetta'):
    $cibo = getCibo((int)$_GET['id']);
    if (!$cibo) {
        setFlash('error', 'Cibo non trovato.');
        redirect(BASE_URL . 'pages/cibi.php');
    }
    $urlProdotto = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'pages/cibo-dettaglio.php?id=' . $cibo['id'];
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=2&data=' . urlencode($urlProdotto);
?>

<div class="toolbar">
    <h1>Etichetta: <?= e($cibo['nome']) ?></h1>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <button class="btn btn-primary" onclick="scaricaImmagine()">Scarica immagine</button>
        <button class="btn btn-secondary" onclick="window.print()">Stampa (PC)</button>
        <a href="<?= BASE_URL ?>pages/cibi.php" class="btn btn-secondary">Torna alla lista</a>
    </div>
</div>

<div class="alert alert-info" style="font-size:0.85rem;">
    Ottimizzato per <strong>Godex G500</strong> (203 DPI) - Etichette <strong>50mm x 90mm</strong>.
    Da cellulare usa <strong>"Scarica immagine"</strong> per salvare l'etichetta.
</div>

<!-- Canvas nascosto per generare l'immagine -->
<canvas id="etichettaCanvas" style="display:none;"></canvas>

<!-- Anteprima visiva -->
<div class="etichetta-preview" id="etichettaPreview">
    <div class="etichetta-card" id="etichettaCard">
        <div class="etichetta-qr">
            <img src="<?= e($qrUrl) ?>" alt="QR Code" id="qrImg" crossorigin="anonymous">
        </div>
        <div class="etichetta-nome"><?= e($cibo['nome']) ?></div>
        <div class="etichetta-grid">
            <div class="etichetta-campo">
                <span class="etichetta-label">Peso</span>
                <span class="etichetta-valore"><?= e($cibo['peso']) ?></span>
            </div>
            <div class="etichetta-campo">
                <span class="etichetta-label">Cella</span>
                <span class="etichetta-valore"><?= e($cibo['cella']) ?></span>
            </div>
        </div>
        <div class="etichetta-grid">
            <div class="etichetta-campo">
                <span class="etichetta-label">Operatore</span>
                <span class="etichetta-valore etichetta-valore-sm"><?= e($cibo['operatore']) ?></span>
            </div>
            <div class="etichetta-campo">
                <span class="etichetta-label">Data</span>
                <span class="etichetta-valore etichetta-valore-sm"><?= date('d/m/Y', strtotime($cibo['created_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<style>
    .etichetta-preview {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }
    .etichetta-card {
        width: 100mm;
        height: 180mm;
        background: #fff;
        border: 2px solid #1e293b;
        border-radius: 4px;
        padding: 6mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .etichetta-qr { margin-bottom: 4mm; }
    .etichetta-qr img { width: 56mm; height: 56mm; }
    .etichetta-nome {
        font-size: 16pt; font-weight: 900; color: #000; text-align: center;
        width: 100%; border-bottom: 1.5px solid #000; padding-bottom: 2mm;
        margin-bottom: 3mm; line-height: 1.2; word-break: break-word;
    }
    .etichetta-grid { display: flex; width: 100%; gap: 3mm; margin-bottom: 2mm; }
    .etichetta-campo { flex: 1; text-align: center; }
    .etichetta-label {
        display: block; font-size: 6pt; font-weight: 700;
        text-transform: uppercase; color: #666; letter-spacing: 0.5px;
    }
    .etichetta-valore { display: block; font-size: 12pt; font-weight: 800; color: #000; line-height: 1.3; }
    .etichetta-valore-sm { font-size: 9pt; font-weight: 600; }

    @media (max-width: 600px) {
        .etichetta-card { width: 70mm; height: 126mm; padding: 4mm; }
        .etichetta-qr img { width: 38mm; height: 38mm; }
        .etichetta-nome { font-size: 12pt; }
    }

    /* === STAMPA PC - Godex G500, 203 DPI, 50mm x 90mm === */
    @media print {
        @page { size: 50mm 90mm; margin: 0; }
        html, body { margin: 0 !important; padding: 0 !important; width: 50mm; height: 90mm; background: #fff !important; }
        body > * { display: none !important; }
        body > main { display: block !important; }
        main > * { display: none !important; }
        .etichetta-preview { display: flex !important; }
        .navbar, .footer, .toolbar, .alert { display: none !important; }
        .container { max-width: none !important; margin: 0 !important; padding: 0 !important; }
        .etichetta-preview {
            position: fixed; top: 0; left: 0; width: 50mm; height: 90mm;
            margin: 0 !important; padding: 0 !important;
        }
        .etichetta-card {
            width: 50mm !important; height: 90mm !important; border: none !important;
            border-radius: 0 !important; box-shadow: none !important; padding: 2mm 3mm !important; margin: 0 !important;
            justify-content: center !important;
        }
        .etichetta-qr { margin-bottom: 1.5mm !important; }
        .etichetta-qr img { width: 26mm !important; height: 26mm !important; }
        .etichetta-nome { font-size: 10pt !important; padding-bottom: 1mm !important; margin-bottom: 1.5mm !important; border-bottom: 0.8px solid #000 !important; }
        .etichetta-grid { gap: 1.5mm !important; margin-bottom: 1mm !important; }
        .etichetta-label { font-size: 5pt !important; }
        .etichetta-valore { font-size: 8pt !important; }
        .etichetta-valore-sm { font-size: 6.5pt !important; }
    }
</style>

<script>
// Dati etichetta per il canvas
var ETICHETTA = {
    nome: <?= json_encode($cibo['nome']) ?>,
    peso: <?= json_encode($cibo['peso']) ?>,
    cella: <?= json_encode($cibo['cella']) ?>,
    operatore: <?= json_encode($cibo['operatore']) ?>,
    data: <?= json_encode(date('d/m/Y', strtotime($cibo['created_at']))) ?>,
    qrUrl: <?= json_encode($qrUrl) ?>
};

function scaricaImmagine() {
    // Godex G500: 203 DPI, etichetta 50mm x 90mm
    // 50mm = 1.9685in * 203 = 399px, 90mm = 3.5433in * 203 = 719px
    var W = 399;
    var H = 719;
    var canvas = document.getElementById('etichettaCanvas');
    canvas.width = W;
    canvas.height = H;
    var ctx = canvas.getContext('2d');

    // Sfondo bianco
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, W, H);

    // Carica QR e disegna
    var qrImg = new Image();
    qrImg.crossOrigin = 'anonymous';
    qrImg.onload = function() {
        // Calcola altezza totale del contenuto per centrarlo verticalmente
        ctx.font = 'bold 28px sans-serif';
        var nomeLines = wrapText(ctx, ETICHETTA.nome, W - 30);

        var qrSize = 224;
        var contentH = qrSize + 14                    // QR + gap
            + (nomeLines.length * 32)                 // nome
            + 4 + 2 + 16                              // linea separatrice
            + 14 + 22 + 24 + 32                       // peso/cella labels + values
            + 14 + 20 + 18;                           // operatore/data labels + values
        var startY = Math.max(20, (H - contentH) / 2);

        // QR centrato
        var qrX = (W - qrSize) / 2;
        ctx.drawImage(qrImg, qrX, startY, qrSize, qrSize);

        var y = startY + qrSize + 14;

        // Nome prodotto
        ctx.fillStyle = '#000';
        ctx.font = 'bold 28px sans-serif';
        ctx.textAlign = 'center';

        nomeLines.forEach(function(line) {
            ctx.fillText(line, W / 2, y);
            y += 32;
        });

        // Linea separatrice
        y += 4;
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(20, y);
        ctx.lineTo(W - 20, y);
        ctx.stroke();
        y += 16;

        // Peso e Cella
        var colL = W * 0.25;
        var colR = W * 0.75;

        ctx.fillStyle = '#888';
        ctx.font = 'bold 14px sans-serif';
        ctx.fillText('PESO', colL, y);
        ctx.fillText('CELLA', colR, y);
        y += 22;

        ctx.fillStyle = '#000';
        ctx.font = 'bold 24px sans-serif';
        ctx.fillText(ETICHETTA.peso, colL, y);
        ctx.fillText(ETICHETTA.cella, colR, y);
        y += 32;

        // Operatore e Data
        ctx.fillStyle = '#888';
        ctx.font = 'bold 14px sans-serif';
        ctx.fillText('OPERATORE', colL, y);
        ctx.fillText('DATA', colR, y);
        y += 20;

        ctx.fillStyle = '#000';
        ctx.font = '600 18px sans-serif';
        ctx.fillText(ETICHETTA.operatore, colL, y);
        ctx.fillText(ETICHETTA.data, colR, y);

        // Scarica
        canvas.toBlob(function(blob) {
            var url = URL.createObjectURL(blob);

            // Su mobile prova share API, altrimenti download
            if (navigator.share && /Mobi|Android/i.test(navigator.userAgent)) {
                var file = new File([blob], 'etichetta-' + <?= json_encode($cibo['id']) ?> + '.png', {type: 'image/png'});
                navigator.share({
                    title: 'Etichetta: ' + ETICHETTA.nome,
                    files: [file]
                }).catch(function() {
                    // Fallback download
                    downloadBlob(url);
                });
            } else {
                downloadBlob(url);
            }
        }, 'image/png');
    };
    qrImg.onerror = function() {
        alert('Errore nel caricamento del QR code. Riprova.');
    };
    qrImg.src = ETICHETTA.qrUrl;
}

function downloadBlob(url) {
    var a = document.createElement('a');
    a.href = url;
    a.download = 'etichetta-' + <?= json_encode($cibo['id']) ?> + '.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function() { URL.revokeObjectURL(url); }, 5000);
}

function wrapText(ctx, text, maxWidth) {
    var words = text.split(' ');
    var lines = [];
    var line = '';
    for (var i = 0; i < words.length; i++) {
        var test = line + (line ? ' ' : '') + words[i];
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = words[i];
        } else {
            line = test;
        }
    }
    if (line) lines.push(line);
    return lines;
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
