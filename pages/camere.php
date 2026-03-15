<?php
$titoloPagina = 'Gestione Camere';
require_once __DIR__ . '/../includes/header.php';

$azione = $_GET['azione'] ?? 'lista';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione_post = $_POST['azione'] ?? '';

    if ($azione_post === 'salva') {
        $dati = [
            'id' => $_POST['id'] ?? '',
            'numero' => trim($_POST['numero']),
            'tipo' => $_POST['tipo'],
            'piano' => (int)$_POST['piano'],
            'prezzo_notte' => (float)$_POST['prezzo_notte'],
            'stato' => $_POST['stato'],
            'descrizione' => trim($_POST['descrizione']),
        ];

        if (salvaCamere($dati)) {
            setFlash('success', 'Camera salvata con successo.');
        } else {
            setFlash('error', 'Errore nel salvare la camera.');
        }
        redirect('camere.php');
    }

    if ($azione_post === 'elimina') {
        $id = (int)$_POST['id'];
        try {
            eliminaCamera($id);
            setFlash('success', 'Camera eliminata.');
        } catch (PDOException $ex) {
            setFlash('error', 'Impossibile eliminare: la camera ha prenotazioni associate.');
        }
        redirect('camere.php');
    }
}
?>

<h1>Gestione Camere</h1>

<?php if ($azione === 'lista'): ?>
    <a href="?azione=nuova" class="btn btn-primary">+ Nuova Camera</a>

    <table class="table">
        <thead>
            <tr>
                <th>Numero</th>
                <th>Tipo</th>
                <th>Piano</th>
                <th>Prezzo/Notte</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (getCamere() as $camera): ?>
            <tr>
                <td><strong><?= e($camera['numero']) ?></strong></td>
                <td><?= e(ucfirst($camera['tipo'])) ?></td>
                <td><?= $camera['piano'] ?></td>
                <td>&euro; <?= number_format($camera['prezzo_notte'], 2, ',', '.') ?></td>
                <td><span class="badge badge-<?= $camera['stato'] ?>"><?= e(ucfirst($camera['stato'])) ?></span></td>
                <td>
                    <a href="?azione=modifica&id=<?= $camera['id'] ?>" class="btn btn-sm btn-warning">Modifica</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Eliminare questa camera?')">
                        <input type="hidden" name="azione" value="elimina">
                        <input type="hidden" name="id" value="<?= $camera['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Elimina</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($azione === 'nuova' || $azione === 'modifica'):
    $camera = $azione === 'modifica' ? getCamera($id) : null;
?>
    <h2><?= $camera ? 'Modifica Camera' : 'Nuova Camera' ?></h2>
    <form method="post" class="form">
        <input type="hidden" name="azione" value="salva">
        <?php if ($camera): ?>
            <input type="hidden" name="id" value="<?= $camera['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="numero">Numero Camera</label>
            <input type="text" id="numero" name="numero" value="<?= e($camera['numero'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" required>
                <?php foreach (['singola','doppia','tripla','quadrupla','suite'] as $tipo): ?>
                    <option value="<?= $tipo ?>" <?= ($camera['tipo'] ?? '') === $tipo ? 'selected' : '' ?>><?= ucfirst($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="piano">Piano</label>
            <input type="number" id="piano" name="piano" value="<?= $camera['piano'] ?? 1 ?>" min="0" max="20" required>
        </div>

        <div class="form-group">
            <label for="prezzo_notte">Prezzo per Notte (&euro;)</label>
            <input type="number" id="prezzo_notte" name="prezzo_notte" step="0.01" value="<?= $camera['prezzo_notte'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label for="stato">Stato</label>
            <select id="stato" name="stato" required>
                <?php foreach (['disponibile','occupata','manutenzione'] as $stato): ?>
                    <option value="<?= $stato ?>" <?= ($camera['stato'] ?? 'disponibile') === $stato ? 'selected' : '' ?>><?= ucfirst($stato) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="descrizione">Descrizione</label>
            <textarea id="descrizione" name="descrizione" rows="3"><?= e($camera['descrizione'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Salva</button>
        <a href="camere.php" class="btn btn-secondary">Annulla</a>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
