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
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=2&data=' . urlencode($urlProdotto);
?>

<div class="toolbar">
    <h1>Etichetta: <?= e($cibo['nome']) ?></h1>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
        <button class="btn btn-primary" onclick="scaricaImmagine()">Scarica immagine</button>
        <button class="btn btn-secondary" onclick="window.print()">Stampa</button>
        <a href="<?= BASE_URL ?>pages/cibi.php" class="btn btn-secondary">Torna alla lista</a>
    </div>
</div>

<!-- Canvas nascosto per generare l'immagine -->
<canvas id="etichettaCanvas" style="display:none;"></canvas>

<div class="etichetta-preview" id="etichettaPreview">
    <div class="etichetta-card">
        <div class="etichetta-qr">
            <img src="<?= e($qrUrl) ?>" alt="QR Code" id="qrImg" crossorigin="anonymous" width="80" height="80">
        </div>
        <div class="etichetta-info">
            <div class="etichetta-nome"><?= e($cibo['nome']) ?></div>
            <div class="etichetta-dettagli">
                <span><strong>Peso:</strong> <?= e($cibo['peso']) ?></span>
                <span><strong>Cella:</strong> <?= e($cibo['cella']) ?></span>
            </div>
            <div class="etichetta-dettagli">
                <span><strong>Operatore:</strong> <?= e($cibo['operatore']) ?></span>
                <span><strong>Data:</strong> <?= date('d/m/Y', strtotime($cibo['created_at'])) ?></span>
            </div>
            <?php if ($cibo['descrizione']): ?>
                <div class="etichetta-desc"><?= e($cibo['descrizione']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .etichetta-preview {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
    }
    .etichetta-card {
        background: #fff;
        border: 2px solid #1e293b;
        border-radius: 8px;
        padding: 0.6rem 0.8rem;
        display: flex;
        gap: 0.7rem;
        align-items: center;
        max-width: 280px;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .etichetta-qr { flex-shrink: 0; }
    .etichetta-qr img { border-radius: 4px; border: 1px solid #e2e8f0; }
    .etichetta-info { flex: 1; }
    .etichetta-nome {
        font-size: 0.85rem; font-weight: 800; color: #1e293b;
        margin-bottom: 0.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.2rem;
    }
    .etichetta-dettagli {
        display: flex; gap: 0.5rem; font-size: 0.6rem; color: #475569; margin-bottom: 0.15rem;
    }
    .etichetta-desc {
        font-size: 0.55rem; color: #64748b; margin-top: 0.25rem; font-style: italic;
    }

    @media (max-width: 600px) {
        .etichetta-card { flex-direction: column; text-align: center; }
        .etichetta-dettagli { justify-content: center; }
    }

    @media print {
        body * { visibility: hidden; }
        .etichetta-preview, .etichetta-preview * { visibility: visible; }
        .etichetta-preview { position: absolute; left: 0; top: 0; margin: 0; }
        .etichetta-card {
            border: 2px solid #000; box-shadow: none;
            flex-direction: row !important; text-align: left !important;
            max-width: 280px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .etichetta-dettagli { justify-content: flex-start !important; }
    }
</style>

<script>
var ETICHETTA = {
    nome: <?= json_encode($cibo['nome']) ?>,
    peso: <?= json_encode($cibo['peso']) ?>,
    cella: <?= json_encode($cibo['cella']) ?>,
    operatore: <?= json_encode($cibo['operatore']) ?>,
    data: <?= json_encode(date('d/m/Y', strtotime($cibo['created_at']))) ?>,
    qrUrl: <?= json_encode($qrUrl) ?>
};

function scaricaImmagine() {
    var scale = 2;
    var W = 800 * scale;
    var H = 400 * scale;
    var canvas = document.getElementById('etichettaCanvas');
    canvas.width = W;
    canvas.height = H;
    var ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);

    var w = 800, h = 400;

    // Sfondo bianco
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, w, h);

    // Bordo
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 3;
    ctx.strokeRect(4, 4, w - 8, h - 8);

    var qrImg = new Image();
    qrImg.crossOrigin = 'anonymous';
    qrImg.onload = function() {
        // QR a sinistra
        var qrSize = 240;
        var qrX = 40;
        var qrY = (h - qrSize) / 2;
        ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

        // Info a destra
        var textX = qrX + qrSize + 40;
        var y = 60;

        // Nome
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 32px sans-serif';
        ctx.textAlign = 'left';
        var lines = wrapText(ctx, ETICHETTA.nome, w - textX - 30);
        lines.forEach(function(line) {
            ctx.fillText(line, textX, y);
            y += 38;
        });

        // Linea
        y += 6;
        ctx.strokeStyle = '#ccc';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(textX, y);
        ctx.lineTo(w - 30, y);
        ctx.stroke();
        y += 24;

        // Dettagli
        ctx.font = 'bold 22px sans-serif';
        ctx.fillStyle = '#475569';
        ctx.fillText('Peso: ', textX, y);
        ctx.fillStyle = '#000';
        ctx.font = '22px sans-serif';
        ctx.fillText(ETICHETTA.peso, textX + ctx.measureText('Peso: ').width, y);

        var cellaX = textX + 200;
        ctx.font = 'bold 22px sans-serif';
        ctx.fillStyle = '#475569';
        ctx.fillText('Cella: ', cellaX, y);
        ctx.fillStyle = '#000';
        ctx.font = '22px sans-serif';
        ctx.fillText(ETICHETTA.cella, cellaX + ctx.measureText('Cella: ').width, y);
        y += 34;

        ctx.font = 'bold 22px sans-serif';
        ctx.fillStyle = '#475569';
        ctx.fillText('Operatore: ', textX, y);
        ctx.fillStyle = '#000';
        ctx.font = '22px sans-serif';
        ctx.fillText(ETICHETTA.operatore, textX + ctx.measureText('Operatore: ').width, y);
        y += 34;

        ctx.font = 'bold 22px sans-serif';
        ctx.fillStyle = '#475569';
        ctx.fillText('Data: ', textX, y);
        ctx.fillStyle = '#000';
        ctx.font = '22px sans-serif';
        ctx.fillText(ETICHETTA.data, textX + ctx.measureText('Data: ').width, y);

        // Scarica/Condividi
        canvas.toBlob(function(blob) {
            var url = URL.createObjectURL(blob);
            if (navigator.share && /Mobi|Android/i.test(navigator.userAgent)) {
                var file = new File([blob], 'etichetta-' + <?= json_encode($cibo['id']) ?> + '.png', {type: 'image/png'});
                navigator.share({ title: 'Etichetta: ' + ETICHETTA.nome, files: [file] }).catch(function() { downloadBlob(url); });
            } else {
                downloadBlob(url);
            }
        }, 'image/png');
    };
    qrImg.onerror = function() { alert('Errore nel caricamento del QR code. Riprova.'); };
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
        if (ctx.measureText(test).width > maxWidth && line) { lines.push(line); line = words[i]; }
        else { line = test; }
    }
    if (line) lines.push(line);
    return lines;
}
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
