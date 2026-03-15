<?php
$titoloPagina = 'Gestione Prenotazioni';
require_once __DIR__ . '/../includes/header.php';

$azione = $_GET['azione'] ?? 'lista';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtroStato = $_GET['stato'] ?? '';

// Parametri pre-compilati dalla griglia calendario
$preCameraId = $_GET['camera_id'] ?? '';
$preCheckin = $_GET['checkin'] ?? '';

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione_post = $_POST['azione'] ?? '';

    if ($azione_post === 'salva') {
        // Validazione dati cliente obbligatori
        $clienteNome = trim($_POST['cliente_nome']);
        $clienteCognome = trim($_POST['cliente_cognome']);
        $clienteEmail = trim($_POST['cliente_email']);
        $clienteTelefono = trim($_POST['cliente_telefono']);

        if (!$clienteNome || !$clienteCognome || !$clienteEmail || !$clienteTelefono) {
            setFlash('error', 'Nome, cognome, email e telefono del cliente sono obbligatori.');
            redirect('prenotazioni.php?azione=' . ($azione === 'modifica' ? "modifica&id=" . ($_POST['id'] ?? '') : 'nuova'));
        }

        $datiCliente = [
            'id' => $_POST['cliente_id'] ?? '',
            'nome' => $clienteNome,
            'cognome' => $clienteCognome,
            'email' => $clienteEmail,
            'telefono' => $clienteTelefono,
            'documento_tipo' => $_POST['documento_tipo'] ?? 'carta_identita',
            'documento_numero' => trim($_POST['documento_numero'] ?? ''),
            'note' => '',
        ];
        $clienteId = salvaCliente($datiCliente);

        $cameraId = (int)$_POST['camera_id'];
        $checkin = $_POST['data_checkin'];
        $checkout = $_POST['data_checkout'];
        $prenotazioneId = !empty($_POST['id']) ? (int)$_POST['id'] : null;

        // Verifica disponibilita prima di salvare
        if (!cameraDisponibile($cameraId, $checkin, $checkout, $prenotazioneId)) {
            setFlash('error', 'ATTENZIONE: La camera non e\' disponibile per le date selezionate. Verificare il calendario.');
            redirect('prenotazioni.php?azione=' . ($prenotazioneId ? "modifica&id=$prenotazioneId" : 'nuova'));
        }

        $datiPrenotazione = [
            'id' => $_POST['id'] ?? '',
            'camera_id' => $cameraId,
            'cliente_id' => $clienteId,
            'data_checkin' => $checkin,
            'data_checkout' => $checkout,
            'stato' => $_POST['stato'] ?? 'confermata',
            'pagamento' => $_POST['pagamento'] ?? 'cliente',
            'num_ospiti' => (int)$_POST['num_ospiti'],
            'note' => trim($_POST['note']),
        ];

        if (salvaPrenotazione($datiPrenotazione)) {
            setFlash('success', 'Prenotazione salvata con successo.');
        } else {
            setFlash('error', 'Errore nel salvare la prenotazione.');
        }
        redirect('prenotazioni.php');
    }

    if ($azione_post === 'cambia_stato') {
        $id = (int)$_POST['id'];
        $nuovoStato = $_POST['nuovo_stato'];
        cambiaStatoPrenotazione($id, $nuovoStato);
        $label = match($nuovoStato) {
            'checkin' => 'Check-in effettuato',
            'checkout' => 'Check-out effettuato',
            'cancellata' => 'Prenotazione cancellata',
            default => 'Stato aggiornato'
        };
        setFlash('success', $label . '.');
        redirect('prenotazioni.php');
    }

    if ($azione_post === 'elimina') {
        eliminaPrenotazione((int)$_POST['id']);
        setFlash('success', 'Prenotazione eliminata.');
        redirect('prenotazioni.php');
    }
}
?>

<h1>Gestione Prenotazioni</h1>

<?php if ($azione === 'lista'): ?>
    <div class="toolbar">
        <a href="?azione=nuova" class="btn btn-success">+ Nuova Prenotazione</a>
        <a href="calendario.php" class="btn btn-info">Griglia Camere</a>
        <div class="filtri">
            <a href="?stato=" class="btn btn-sm <?= !$filtroStato ? 'btn-primary' : 'btn-secondary' ?>">Tutte</a>
            <a href="?stato=confermata" class="btn btn-sm <?= $filtroStato === 'confermata' ? 'btn-primary' : 'btn-secondary' ?>">Confermate</a>
            <a href="?stato=checkin" class="btn btn-sm <?= $filtroStato === 'checkin' ? 'btn-primary' : 'btn-secondary' ?>">In corso</a>
            <a href="?stato=checkout" class="btn btn-sm <?= $filtroStato === 'checkout' ? 'btn-primary' : 'btn-secondary' ?>">Completate</a>
            <a href="?stato=cancellata" class="btn btn-sm <?= $filtroStato === 'cancellata' ? 'btn-primary' : 'btn-secondary' ?>">Cancellate</a>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Camera</th>
                <th>Cliente</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Paga</th>
                <th>Totale</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (getPrenotazioni($filtroStato) as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= e($p['camera_numero']) ?> (<?= e(ucfirst($p['camera_tipo'])) ?>)</td>
                <td><?= e($p['cliente_cognome'] . ' ' . $p['cliente_nome']) ?></td>
                <td><?= e($p['cliente_email'] ?? '-') ?></td>
                <td><?= e($p['cliente_telefono'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($p['data_checkin'])) ?></td>
                <td><?= date('d/m/Y', strtotime($p['data_checkout'])) ?></td>
                <td><span class="badge badge-<?= $p['pagamento'] ?>"><?= $p['pagamento'] === 'sposi' ? 'Sposi' : 'Cliente' ?></span></td>
                <td>&euro; <?= number_format($p['prezzo_totale'], 2, ',', '.') ?></td>
                <td><span class="badge badge-<?= $p['stato'] ?>"><?= e(ucfirst($p['stato'])) ?></span></td>
                <td class="azioni-cell">
                    <?php if ($p['stato'] === 'confermata'): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="azione" value="cambia_stato">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="nuovo_stato" value="checkin">
                            <button type="submit" class="btn btn-sm btn-success">Check-in</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($p['stato'] === 'checkin'): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="azione" value="cambia_stato">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="nuovo_stato" value="checkout">
                            <button type="submit" class="btn btn-sm btn-info">Check-out</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($p['stato'] === 'confermata'): ?>
                        <a href="?azione=modifica&id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Modifica</a>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="azione" value="cambia_stato">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="nuovo_stato" value="cancellata">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Cancellare questa prenotazione?')">Cancella</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($azione === 'nuova' || $azione === 'modifica'):
    $prenotazione = $azione === 'modifica' ? getPrenotazione($id) : null;
    $cliente = $prenotazione ? getCliente($prenotazione['cliente_id']) : null;
    $camereDisponibili = getCamere();

    // Valori pre-compilati (dalla griglia o dalla prenotazione esistente)
    $valCameraId = $prenotazione['camera_id'] ?? $preCameraId;
    $valCheckin = $prenotazione['data_checkin'] ?? $preCheckin;
    $valCheckout = $prenotazione['data_checkout'] ?? '';
?>
    <h2><?= $prenotazione ? 'Modifica Prenotazione #' . $prenotazione['id'] : 'Nuova Prenotazione' ?></h2>

    <?php if (!$prenotazione): ?>
    <div class="alert alert-info">
        La disponibilita della camera viene verificata automaticamente al salvataggio. Puoi controllare la <a href="calendario.php">griglia camere</a> per una visione d'insieme.
    </div>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="azione" value="salva">
        <?php if ($prenotazione): ?>
            <input type="hidden" name="id" value="<?= $prenotazione['id'] ?>">
            <input type="hidden" name="cliente_id" value="<?= $prenotazione['cliente_id'] ?>">
            <input type="hidden" name="stato" value="<?= e($prenotazione['stato']) ?>">
        <?php endif; ?>

        <fieldset>
            <legend>Dati Cliente (tutti obbligatori)</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_nome">Nome *</label>
                    <input type="text" id="cliente_nome" name="cliente_nome" value="<?= e($cliente['nome'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="cliente_cognome">Cognome *</label>
                    <input type="text" id="cliente_cognome" name="cliente_cognome" value="<?= e($cliente['cognome'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_email">Email *</label>
                    <input type="email" id="cliente_email" name="cliente_email" value="<?= e($cliente['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="cliente_telefono">Telefono *</label>
                    <input type="text" id="cliente_telefono" name="cliente_telefono" value="<?= e($cliente['telefono'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="documento_tipo">Tipo Documento</label>
                    <select id="documento_tipo" name="documento_tipo">
                        <?php foreach (['carta_identita' => "Carta d'Identita", 'passaporto' => 'Passaporto', 'patente' => 'Patente'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($cliente['documento_tipo'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="documento_numero">Numero Documento</label>
                    <input type="text" id="documento_numero" name="documento_numero" value="<?= e($cliente['documento_numero'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Dati Prenotazione</legend>
            <div class="form-group">
                <label for="camera_id">Camera *</label>
                <select id="camera_id" name="camera_id" required>
                    <option value="">-- Seleziona Camera --</option>
                    <?php foreach ($camereDisponibili as $cam): ?>
                        <option value="<?= $cam['id'] ?>" <?= $valCameraId == $cam['id'] ? 'selected' : '' ?>>
                            #<?= e($cam['numero']) ?> - <?= ucfirst($cam['tipo']) ?> (Piano <?= $cam['piano'] ?>) - &euro;<?= number_format($cam['prezzo_notte'], 2, ',', '.') ?>/notte
                            <?= $cam['stato'] !== 'disponibile' ? ' [' . strtoupper($cam['stato']) . ']' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="data_checkin">Data Check-in *</label>
                    <input type="date" id="data_checkin" name="data_checkin" value="<?= e($valCheckin) ?>" required>
                </div>
                <div class="form-group">
                    <label for="data_checkout">Data Check-out *</label>
                    <input type="date" id="data_checkout" name="data_checkout" value="<?= e($valCheckout) ?>" required>
                </div>
            </div>

            <div id="disponibilita-feedback"></div>

            <div class="form-row">
                <div class="form-group">
                    <label for="num_ospiti">Numero Ospiti *</label>
                    <input type="number" id="num_ospiti" name="num_ospiti" value="<?= $prenotazione['num_ospiti'] ?? 1 ?>" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label for="pagamento">Chi paga? *</label>
                    <select id="pagamento" name="pagamento" required>
                        <option value="cliente" <?= ($prenotazione['pagamento'] ?? 'cliente') === 'cliente' ? 'selected' : '' ?>>Il Cliente</option>
                        <option value="sposi" <?= ($prenotazione['pagamento'] ?? '') === 'sposi' ? 'selected' : '' ?>>Gli Sposi</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="note">Note</label>
                <textarea id="note" name="note" rows="3"><?= e($prenotazione['note'] ?? '') ?></textarea>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-primary">Salva Prenotazione</button>
        <a href="prenotazioni.php" class="btn btn-secondary">Annulla</a>
        <a href="calendario.php" class="btn btn-info">Torna alla Griglia</a>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
