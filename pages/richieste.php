<?php
$titoloPagina = 'Richieste Sposi';
require_once __DIR__ . '/../includes/header.php';

// Cambio stato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    if ($_POST['azione'] === 'cambia_stato') {
        cambiaStatoRichiesta((int)$_POST['id'], $_POST['stato']);
        setFlash('success', 'Stato richiesta aggiornato.');
        redirect(BASE_URL . 'pages/richieste.php');
    }
}

$filtro = $_GET['stato'] ?? '';
$richieste = getRichiesteSposi($filtro);
$linkPubblico = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'richiesta-sposi.php';
?>

<div class="toolbar">
    <h1>Richieste Sposi</h1>
    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
        <a href="<?= e($linkPubblico) ?>" class="btn btn-info btn-sm" target="_blank">Apri pagina pubblica</a>
        <button class="btn btn-sm btn-secondary" onclick="copiaLink()">Copia link</button>
    </div>
</div>

<div class="filtri" style="margin-bottom:1rem;">
    <a href="?stato=" class="btn btn-sm <?= !$filtro ? 'btn-primary' : 'btn-secondary' ?>">Tutte</a>
    <a href="?stato=nuova" class="btn btn-sm <?= $filtro === 'nuova' ? 'btn-primary' : 'btn-secondary' ?>">Nuove</a>
    <a href="?stato=gestita" class="btn btn-sm <?= $filtro === 'gestita' ? 'btn-primary' : 'btn-secondary' ?>">Gestite</a>
    <a href="?stato=confermata" class="btn btn-sm <?= $filtro === 'confermata' ? 'btn-primary' : 'btn-secondary' ?>">Confermate</a>
    <a href="?stato=annullata" class="btn btn-sm <?= $filtro === 'annullata' ? 'btn-primary' : 'btn-secondary' ?>">Annullate</a>
</div>

<?php if (empty($richieste)): ?>
    <div class="alert alert-info">Nessuna richiesta trovata.</div>
<?php else: ?>

<?php foreach ($richieste as $r):
    $camere = json_decode($r['camere'], true);
    $notti = (new DateTime($r['data_checkin']))->diff(new DateTime($r['data_checkout']))->days;
    $badgeStato = match($r['stato']) {
        'nuova' => 'checkin',
        'confermata' => 'disponibile',
        'annullata' => 'cancellata',
        default => 'confermata'
    };
?>
<div style="background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.08); padding:1.2rem; margin-bottom:1rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.8rem;">
        <div>
            <strong style="font-size:1.1rem;"><?= e($r['nome_sposi']) ?></strong>
            <span class="badge badge-<?= $badgeStato ?>" style="margin-left:0.5rem;"><?= e(ucfirst($r['stato'])) ?></span>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:center;">
            <span style="font-size:1.1rem; font-weight:700; color:#7c3aed;">&euro;<?= number_format($r['prezzo_totale'], 2, ',', '.') ?></span>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="azione" value="cambia_stato">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <select name="stato" onchange="this.form.submit()" style="font-size:0.8rem; padding:0.25rem 0.4rem; border-radius:6px; border:1px solid #e2e8f0;">
                    <option value="nuova" <?= $r['stato'] === 'nuova' ? 'selected' : '' ?>>Nuova</option>
                    <option value="gestita" <?= $r['stato'] === 'gestita' ? 'selected' : '' ?>>Gestita</option>
                    <option value="confermata" <?= $r['stato'] === 'confermata' ? 'selected' : '' ?>>Confermata</option>
                    <option value="annullata" <?= $r['stato'] === 'annullata' ? 'selected' : '' ?>>Annullata</option>
                </select>
            </form>
        </div>
    </div>

    <div style="display:flex; gap:1.5rem; flex-wrap:wrap; font-size:0.88rem; color:#64748b; margin-bottom:0.8rem;">
        <span><?= e($r['email']) ?></span>
        <span><?= e($r['telefono']) ?></span>
        <span>Richiesta: <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
        <span>Date: <?= date('d/m/Y', strtotime($r['data_checkin'])) ?> &rarr; <?= date('d/m/Y', strtotime($r['data_checkout'])) ?> (<?= $notti ?> notti)</span>
    </div>

    <?php if ($r['note']): ?>
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:0.5rem 0.75rem; font-size:0.85rem; color:#92400e; margin-bottom:0.8rem;">
            <strong>Note:</strong> <?= e($r['note']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($camere)): ?>
    <table class="table" style="margin-top:0.5rem;">
        <thead>
            <tr>
                <th>Camera</th>
                <th>Date</th>
                <th>Ospiti</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Documento</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($camere as $cam): ?>
            <tr>
                <td>
                    <?php if (!empty($cam['camera_label'])): ?>
                        <?= e($cam['camera_label']) ?>
                    <?php else: ?>
                        <span class="badge badge-confermata"><?= $cam['num_ospiti'] ?? '-' ?> pers.</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($cam['data_checkin'])): ?>
                        <?= date('d/m', strtotime($cam['data_checkin'])) ?> &rarr; <?= date('d/m', strtotime($cam['data_checkout'])) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= $cam['num_ospiti'] ?? '-' ?></td>
                <td><?= e($cam['nome'] ?? '-') ?></td>
                <td><?= e($cam['cognome'] ?? '-') ?></td>
                <td><small><?= e($cam['email'] ?? '-') ?></small></td>
                <td><small><?= e($cam['telefono'] ?? '-') ?></small></td>
                <td>
                    <?php if (!empty($cam['documento_numero'])): ?>
                        <?= e($cam['documento_numero']) ?>
                    <?php endif; ?>
                    <?php if (!empty($cam['documento_foto'])): ?>
                        <a href="<?= BASE_URL ?>uploads/documenti/<?= e($cam['documento_foto']) ?>" target="_blank" class="btn btn-sm btn-info" style="padding:0.15rem 0.4rem; font-size:0.75rem;">Foto</a>
                    <?php endif; ?>
                    <?php if (empty($cam['documento_numero']) && empty($cam['documento_foto'])): ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<script>
function copiaLink() {
    var link = <?= json_encode($linkPubblico) ?>;
    navigator.clipboard.writeText(link).then(function() {
        alert('Link copiato!');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
