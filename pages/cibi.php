<?php
$titoloPagina = 'Gestione Cibi';
require_once __DIR__ . '/../includes/header_cibi.php';

$azione = $_GET['azione'] ?? 'lista';

// --- POST: Salva / Elimina ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crea_utente']) && $cibiIsAdmin) {
        $nuovoUser = trim($_POST['nuovo_username'] ?? '');
        $nuovaPass = $_POST['nuovo_password'] ?? '';
        if ($nuovoUser && strlen($nuovaPass) >= 4) {
            if (creaUtenteCibi($nuovoUser, $nuovaPass)) {
                setFlash('success', "Utente '$nuovoUser' creato.");
            } else {
                setFlash('error', "Username '$nuovoUser' già esistente.");
            }
        } else {
            setFlash('error', 'Username e password (min 4 caratteri) obbligatori.');
        }
        redirect(BASE_URL . 'pages/cibi.php?azione=utenti');
    }
    if (isset($_POST['elimina_utente']) && $cibiIsAdmin) {
        eliminaUtenteCibi((int)$_POST['utente_id']);
        setFlash('success', 'Utente eliminato.');
        redirect(BASE_URL . 'pages/cibi.php?azione=utenti');
    }
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
            <img src="<?= e($qrUrl) ?>" alt="QR Code" id="qrImg" crossorigin="anonymous" width="100" height="100">
        </div>
        <div class="etichetta-info">
            <div class="etichetta-nome"><?= e($cibo['nome']) ?></div>
            <div class="etichetta-data"><?= date('d/m/Y', strtotime($cibo['created_at'])) ?></div>
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
        border: none;
        padding: 0.8rem 1rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        max-width: 320px;
        width: 100%;
    }
    .etichetta-qr { flex-shrink: 0; }
    .etichetta-qr img { border-radius: 4px; border: 1px solid #e2e8f0; }
    .etichetta-info { flex: 1; text-align: center; }
    .etichetta-nome {
        font-size: 1.5rem; font-weight: 800; color: #000;
        line-height: 1.1; margin-bottom: 0.3rem;
    }
    .etichetta-data {
        font-size: 1.1rem; font-weight: 700; color: #1e293b;
    }

    @media (max-width: 600px) {
        .etichetta-card { flex-direction: column; text-align: center; }
    }

    @media print {
        body * { visibility: hidden; }
        .etichetta-preview, .etichetta-preview * { visibility: visible; }
        .etichetta-preview { position: absolute; left: 0; top: 0; margin: 0; }
        .etichetta-card {
            border: none; box-shadow: none;
            flex-direction: row !important; text-align: left !important;
            max-width: 320px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
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
    var w = 800, h = 400;
    var canvas = document.getElementById('etichettaCanvas');
    canvas.width = w * scale;
    canvas.height = h * scale;
    var ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);

    // Sfondo bianco
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, w, h);

    var qrImg = new Image();
    qrImg.crossOrigin = 'anonymous';
    qrImg.onload = function() {
        // QR a sinistra centrato verticalmente
        var qrSize = 300;
        var qrX = 30;
        var qrY = (h - qrSize) / 2;
        ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

        // Info a destra centrate verticalmente
        var textX = qrX + qrSize + 30;
        var textAreaW = w - textX - 30;
        var centerX = textX + textAreaW / 2;
        ctx.textAlign = 'center';

        // Calcola altezza totale testo per centrare
        ctx.font = 'bold 72px sans-serif';
        var nomeLines = wrapText(ctx, ETICHETTA.nome, textAreaW);
        var nomeHeight = nomeLines.length * 80;
        var dataHeight = 50;
        var totalH = nomeHeight + 30 + dataHeight;
        var startY = (h - totalH) / 2 + 60;

        // Nome grande
        ctx.fillStyle = '#000';
        ctx.font = 'bold 72px sans-serif';
        var y = startY;
        nomeLines.forEach(function(line) {
            ctx.fillText(line, centerX, y);
            y += 80;
        });

        // Data grande
        y += 30;
        ctx.font = 'bold 48px sans-serif';
        ctx.fillStyle = '#1e293b';
        ctx.fillText(ETICHETTA.data, centerX, y);

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

<?php elseif ($azione === 'utenti' && $cibiIsAdmin):
    $utenti = getUtentiCibi();
?>

<div class="toolbar">
    <h1>Gestione Utenti Cibi</h1>
    <a href="<?= BASE_URL ?>pages/cibi.php" class="btn btn-secondary">Torna ai Cibi</a>
</div>

<div class="card" style="max-width:500px; margin-bottom:2rem;">
    <h3 style="margin-bottom:1rem;">Nuovo utente</h3>
    <form method="post">
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <input type="text" name="nuovo_username" placeholder="Username" required
                style="flex:1; min-width:120px; padding:0.5rem; border:2px solid #cbd5e1; border-radius:6px;">
            <input type="password" name="nuovo_password" placeholder="Password (min 4 car.)" required minlength="4"
                style="flex:1; min-width:150px; padding:0.5rem; border:2px solid #cbd5e1; border-radius:6px;">
            <button type="submit" name="crea_utente" value="1" class="btn btn-primary">Crea</button>
        </div>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Ruolo</th>
            <th>Creato il</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($utenti as $u): ?>
        <tr>
            <td><strong><?= e($u['username']) ?></strong></td>
            <td><?= $u['is_admin'] ? '<span class="badge badge-confermata">Admin</span>' : 'Utente' ?></td>
            <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if (!$u['is_admin']): ?>
                <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare utente <?= e($u['username']) ?>?')">
                    <input type="hidden" name="utente_id" value="<?= $u['id'] ?>">
                    <button type="submit" name="elimina_utente" value="1" class="btn btn-sm btn-danger">Elimina</button>
                </form>
                <?php else: ?>
                    <span style="color:#94a3b8; font-size:0.85rem;">Non eliminabile</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
