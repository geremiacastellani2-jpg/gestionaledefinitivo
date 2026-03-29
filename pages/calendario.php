<?php
$titoloPagina = 'Gestione Camere';
require_once __DIR__ . '/../includes/header.php';

$meseCorrente = $_GET['mese'] ?? date('Y-m');
$dataRiferimento = new DateTime($meseCorrente . '-01');
$anno = (int)$dataRiferimento->format('Y');
$mese = (int)$dataRiferimento->format('m');

$mesePrecedente = (clone $dataRiferimento)->modify('-1 month')->format('Y-m');
$meseSuccessivo = (clone $dataRiferimento)->modify('+1 month')->format('Y-m');

$giorniMese = (int)$dataRiferimento->format('t');

$nomiMesi = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
$nomiGiorni = ['', 'Lun','Mar','Mer','Gio','Ven','Sab','Dom'];

$prenotazioni = getPrenotazioniCalendario($meseCorrente);
$camere = getCamere();

// Mappa prenotazioni per camera/giorno
$mappaPrenotazioni = [];
foreach ($prenotazioni as $p) {
    $inizio = max(strtotime($p['data_checkin']), strtotime("$anno-$mese-01"));
    $fine = min(strtotime($p['data_checkout']), strtotime("$anno-$mese-$giorniMese"));
    for ($d = $inizio; $d < $fine; $d += 86400) {
        $giorno = (int)date('j', $d);
        $mappaPrenotazioni[$p['camera_id']][$giorno] = $p;
    }
}

$oggi = date('Y-m-d');
?>

<h1>Gestione Camere - <?= $nomiMesi[$mese] ?> <?= $anno ?></h1>

<div class="calendario-nav">
    <a href="?mese=<?= $mesePrecedente ?>" class="btn btn-secondary">&laquo; Precedente</a>
    <a href="?mese=<?= date('Y-m') ?>" class="btn btn-primary">Mese Corrente</a>
    <a href="?mese=<?= $meseSuccessivo ?>" class="btn btn-secondary">Successivo &raquo;</a>
    <a href="<?= BASE_URL ?>pages/prenotazioni.php?azione=nuova" class="btn btn-success">+ Nuova Prenotazione</a>
</div>

<div class="legenda">
    <span class="legenda-item"><span class="legenda-colore legenda-disponibile"></span> Disponibile</span>
    <span class="legenda-item"><span class="legenda-colore legenda-confermata"></span> Prenotata (confermata)</span>
    <span class="legenda-item"><span class="legenda-colore legenda-checkin"></span> Occupata (check-in)</span>
    <span class="legenda-item"><span class="legenda-colore legenda-manutenzione"></span> Manutenzione</span>
    <span class="legenda-item"><span class="legenda-colore legenda-sposi"></span> Pagano gli Sposi</span>
    <span class="legenda-item"><span class="legenda-colore legenda-cliente-paga"></span> Paga il Cliente</span>
</div>

<div class="calendario-griglia-wrapper">
<table class="table calendario-tabella">
    <thead>
        <tr>
            <th class="camera-col-header">Camera</th>
            <?php for ($g = 1; $g <= $giorniMese; $g++):
                $dataGiorno = sprintf('%04d-%02d-%02d', $anno, $mese, $g);
                $giornoSettimana = (int)date('N', strtotime($dataGiorno));
                $isWeekend = $giornoSettimana >= 6;
                $isOggi = $dataGiorno === $oggi;
            ?>
                <th class="giorno-col-header <?= $isWeekend ? 'weekend' : '' ?> <?= $isOggi ? 'oggi' : '' ?>">
                    <span class="giorno-nome"><?= $nomiGiorni[$giornoSettimana] ?></span>
                    <span class="giorno-num"><?= $g ?></span>
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
                <small class="camera-prezzo">&euro;<?= number_format($camera['prezzo_notte'], 0, ',', '.') ?></small>
            </td>
            <?php for ($g = 1; $g <= $giorniMese; $g++):
                $pren = $mappaPrenotazioni[$camera['id']][$g] ?? null;
                $dataGiorno = sprintf('%04d-%02d-%02d', $anno, $mese, $g);
                $classe = 'cella-disponibile';
                $tooltip = '';
                $link = '';

                if ($camera['stato'] === 'manutenzione') {
                    $classe = 'cella-manutenzione';
                    $tooltip = 'In manutenzione';
                } elseif ($pren) {
                    $classe = 'cella-' . $pren['stato'];
                    if ($pren['pagamento'] === 'sposi') {
                        $classe .= ' cella-sposi';
                    }
                    $tooltip = $pren['cliente_cognome'] . ' ' . $pren['cliente_nome'];
                    $tooltip .= "\nEmail: " . ($pren['cliente_email'] ?: '-');
                    $tooltip .= "\nTel: " . ($pren['cliente_telefono'] ?: '-');
                    $tooltip .= "\nDal " . date('d/m', strtotime($pren['data_checkin'])) . ' al ' . date('d/m', strtotime($pren['data_checkout']));
                    $tooltip .= "\nPaga: " . ($pren['pagamento'] === 'sposi' ? 'SPOSI' : 'CLIENTE');
                    $tooltip .= "\nStato: " . ucfirst($pren['stato']);
                    $link = BASE_URL . 'pages/prenotazioni.php?azione=modifica&id=' . $pren['id'];
                } else {
                    $tooltip = 'Disponibile - clicca per prenotare';
                    $link = BASE_URL . 'pages/prenotazioni.php?azione=nuova&camera_id=' . $camera['id'] . '&checkin=' . $dataGiorno;
                }
            ?>
                <td class="giorno-col <?= $classe ?>"
                    title="<?= e($tooltip) ?>"
                    <?php if ($link): ?>onclick="window.location='<?= $link ?>'"<?php endif; ?>
                    style="cursor:pointer">
                    <?php if ($pren && $pren['pagamento'] === 'sposi'): ?>
                        <span class="paga-icona">S</span>
                    <?php endif; ?>
                </td>
            <?php endfor; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
