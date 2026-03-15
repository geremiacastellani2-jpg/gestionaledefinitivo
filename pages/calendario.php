<?php
$titoloPagina = 'Calendario Disponibilità';
require_once __DIR__ . '/../includes/header.php';

$meseCorrente = $_GET['mese'] ?? date('Y-m');
$dataRiferimento = new DateTime($meseCorrente . '-01');
$anno = (int)$dataRiferimento->format('Y');
$mese = (int)$dataRiferimento->format('m');

$mesePrecedente = (clone $dataRiferimento)->modify('-1 month')->format('Y-m');
$meseSuccessivo = (clone $dataRiferimento)->modify('+1 month')->format('Y-m');

$giorniMese = (int)$dataRiferimento->format('t');
$primoGiornoSettimana = (int)(new DateTime("$anno-$mese-01"))->format('N'); // 1=Lun, 7=Dom

$nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

$prenotazioni = getPrenotazioniCalendario($meseCorrente);
$camere = getCamere();

// Prepara mappa prenotazioni per camera/giorno
$mappaPrenotazioni = [];
foreach ($prenotazioni as $p) {
    $inizio = max(strtotime($p['data_checkin']), strtotime("$anno-$mese-01"));
    $fine = min(strtotime($p['data_checkout']), strtotime("$anno-$mese-$giorniMese"));
    for ($d = $inizio; $d <= $fine; $d += 86400) {
        $giorno = (int)date('j', $d);
        $mappaPrenotazioni[$p['camera_id']][$giorno] = $p;
    }
}
?>

<h1>Calendario - <?= $nomiMesi[$mese] ?> <?= $anno ?></h1>

<div class="calendario-nav">
    <a href="?mese=<?= $mesePrecedente ?>" class="btn btn-secondary">&laquo; Mese Precedente</a>
    <a href="?mese=<?= date('Y-m') ?>" class="btn btn-primary">Oggi</a>
    <a href="?mese=<?= $meseSuccessivo ?>" class="btn btn-secondary">Mese Successivo &raquo;</a>
</div>

<div class="legenda">
    <span class="legenda-item"><span class="legenda-colore legenda-disponibile"></span> Disponibile</span>
    <span class="legenda-item"><span class="legenda-colore legenda-confermata"></span> Prenotata</span>
    <span class="legenda-item"><span class="legenda-colore legenda-checkin"></span> Occupata (Check-in)</span>
    <span class="legenda-item"><span class="legenda-colore legenda-manutenzione"></span> Manutenzione</span>
</div>

<div class="calendario-griglia-wrapper">
<table class="table calendario-tabella">
    <thead>
        <tr>
            <th class="camera-col">Camera</th>
            <?php for ($g = 1; $g <= $giorniMese; $g++): ?>
                <th class="giorno-col <?= date('N', strtotime("$anno-$mese-$g")) >= 6 ? 'weekend' : '' ?>">
                    <?= $g ?>
                </th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($camere as $camera): ?>
        <tr>
            <td class="camera-col">
                <strong>#<?= e($camera['numero']) ?></strong>
                <small><?= ucfirst($camera['tipo']) ?></small>
            </td>
            <?php for ($g = 1; $g <= $giorniMese; $g++):
                $prenotazione = $mappaPrenotazioni[$camera['id']][$g] ?? null;
                $classe = 'cella-disponibile';
                $titolo = 'Disponibile';

                if ($camera['stato'] === 'manutenzione') {
                    $classe = 'cella-manutenzione';
                    $titolo = 'In manutenzione';
                } elseif ($prenotazione) {
                    $classe = 'cella-' . $prenotazione['stato'];
                    $titolo = $prenotazione['cliente_cognome'] . ' ' . $prenotazione['cliente_nome'];
                }
            ?>
                <td class="giorno-col <?= $classe ?>" title="<?= e($titolo) ?>"></td>
            <?php endfor; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
