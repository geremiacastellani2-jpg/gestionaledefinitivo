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
<table class="table">
    <thead>
        <tr>
            <th>Data richiesta</th>
            <th>Sposi</th>
            <th>Contatti</th>
            <th>Date</th>
            <th>Camere</th>
            <th>Totale</th>
            <th>Stato</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($richieste as $r):
        $camere = json_decode($r['camere'], true);
        $notti = (new DateTime($r['data_checkin']))->diff(new DateTime($r['data_checkout']))->days;
    ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
            <td><strong><?= e($r['nome_sposi']) ?></strong></td>
            <td>
                <small><?= e($r['email']) ?></small><br>
                <small><?= e($r['telefono']) ?></small>
            </td>
            <td>
                <?= date('d/m/Y', strtotime($r['data_checkin'])) ?> &rarr; <?= date('d/m/Y', strtotime($r['data_checkout'])) ?>
                <br><small><?= $notti ?> nott<?= $notti === 1 ? 'e' : 'i' ?></small>
            </td>
            <td>
                <?php foreach ($camere as $i => $cam): ?>
                    <span class="badge badge-confermata"><?= $cam['num_ospiti'] ?> pers.</span>
                <?php endforeach; ?>
                <br><small><?= count($camere) ?> camer<?= count($camere) === 1 ? 'a' : 'e' ?></small>
            </td>
            <td><strong>&euro;<?= number_format($r['prezzo_totale'], 2, ',', '.') ?></strong></td>
            <td>
                <span class="badge badge-<?= $r['stato'] === 'nuova' ? 'checkin' : ($r['stato'] === 'confermata' ? 'disponibile' : ($r['stato'] === 'annullata' ? 'cancellata' : 'confermata')) ?>">
                    <?= e(ucfirst($r['stato'])) ?>
                </span>
            </td>
            <td class="azioni-cell">
                <?php if ($r['note']): ?>
                    <span title="<?= e($r['note']) ?>" style="cursor:help;">&#128172;</span>
                <?php endif; ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="azione" value="cambia_stato">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <select name="stato" onchange="this.form.submit()" style="font-size:0.8rem; padding:0.2rem;">
                        <option value="nuova" <?= $r['stato'] === 'nuova' ? 'selected' : '' ?>>Nuova</option>
                        <option value="gestita" <?= $r['stato'] === 'gestita' ? 'selected' : '' ?>>Gestita</option>
                        <option value="confermata" <?= $r['stato'] === 'confermata' ? 'selected' : '' ?>>Confermata</option>
                        <option value="annullata" <?= $r['stato'] === 'annullata' ? 'selected' : '' ?>>Annullata</option>
                    </select>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
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
