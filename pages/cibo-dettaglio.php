<?php
$titoloPagina = 'Dettaglio Prodotto';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_cibi.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('error', 'Prodotto non specificato.');
    redirect(BASE_URL . 'pages/cibi.php');
}

// --- POST: Modifica / Elimina ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salva'])) {
        salvaCibo([
            'id' => $id,
            'operatore' => trim($_POST['operatore']),
            'nome' => trim($_POST['nome']),
            'descrizione' => trim($_POST['descrizione']),
            'peso' => trim($_POST['peso']),
            'cella' => trim($_POST['cella']),
        ]);
        setFlash('success', 'Prodotto aggiornato.');
        redirect(BASE_URL . 'pages/cibo-dettaglio.php?id=' . $id);
    }
    if (isset($_POST['elimina'])) {
        eliminaCibo($id);
        setFlash('success', 'Prodotto eliminato.');
        redirect(BASE_URL . 'pages/cibi.php');
    }
}

$cibo = getCibo($id);
if (!$cibo) {
    setFlash('error', 'Prodotto non trovato.');
    redirect(BASE_URL . 'pages/cibi.php');
}

$modificaMode = isset($_GET['modifica']);
?>

<div class="toolbar">
    <h1><?= e($cibo['nome']) ?></h1>
    <div style="display:flex; gap:0.5rem;">
        <?php if (!$modificaMode): ?>
            <a href="?id=<?= $id ?>&modifica=1" class="btn btn-warning">Modifica</a>
            <a href="<?= BASE_URL ?>pages/cibi.php?azione=etichetta&id=<?= $id ?>" class="btn btn-info">Stampa etichetta</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>pages/cibi.php" class="btn btn-secondary">Torna alla lista</a>
    </div>
</div>

<?php if ($modificaMode): ?>

<form method="POST" class="form">
    <div class="form-row">
        <div class="form-group">
            <label>Operatore *</label>
            <input type="text" name="operatore" required value="<?= e($cibo['operatore']) ?>">
        </div>
        <div class="form-group">
            <label>Nome cibo *</label>
            <input type="text" name="nome" required value="<?= e($cibo['nome']) ?>">
        </div>
    </div>

    <div class="form-group">
        <label>Descrizione</label>
        <textarea name="descrizione" rows="3"><?= e($cibo['descrizione'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Peso *</label>
            <input type="text" name="peso" required value="<?= e($cibo['peso']) ?>">
        </div>
        <div class="form-group">
            <label>Cella *</label>
            <input type="text" name="cella" required value="<?= e($cibo['cella']) ?>">
        </div>
    </div>

    <div style="display:flex; gap:0.5rem; margin-top:1rem;">
        <button type="submit" name="salva" class="btn btn-primary">Salva modifiche</button>
        <a href="?id=<?= $id ?>" class="btn btn-secondary">Annulla</a>
    </div>
</form>

<form method="POST" style="margin-top:1.5rem;" onsubmit="return confirm('Sei sicuro di voler eliminare questo prodotto?')">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button type="submit" name="elimina" class="btn btn-danger">Elimina prodotto</button>
</form>

<?php else: ?>

<div class="form" style="max-width:600px;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; width:140px; border-bottom:1px solid #f1f5f9;">Operatore</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><?= e($cibo['operatore']) ?></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; border-bottom:1px solid #f1f5f9;">Nome</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><strong><?= e($cibo['nome']) ?></strong></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; border-bottom:1px solid #f1f5f9;">Descrizione</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><?= e($cibo['descrizione'] ?: '-') ?></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; border-bottom:1px solid #f1f5f9;">Peso</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><?= e($cibo['peso']) ?></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; border-bottom:1px solid #f1f5f9;">Cella</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><span class="badge badge-confermata"><?= e($cibo['cella']) ?></span></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569; border-bottom:1px solid #f1f5f9;">Inserito il</td>
            <td style="padding:0.6rem 1rem; border-bottom:1px solid #f1f5f9;"><?= date('d/m/Y H:i', strtotime($cibo['created_at'])) ?></td>
        </tr>
        <tr>
            <td style="padding:0.6rem 1rem; font-weight:600; color:#475569;">Ultimo aggiornamento</td>
            <td style="padding:0.6rem 1rem;"><?= date('d/m/Y H:i', strtotime($cibo['updated_at'])) ?></td>
        </tr>
    </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
