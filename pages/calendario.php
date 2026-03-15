<?php
$titoloPagina = 'Gestione Camere';
require_once __DIR__ . '/../includes/header.php';

// Data di inizio periodo (default: oggi)
$inizioParam = $_GET['da'] ?? date('Y-m-d');
$dataInizio = new DateTime($inizioParam);

// Allinea al lunedi della settimana corrente
$giornoSettimanaInizio = (int)$dataInizio->format('N');
if ($giornoSettimanaInizio > 1) {
    $dataInizio->modify('-' . ($giornoSettimanaInizio - 1) . ' days');
}

$dataFine = (clone $dataInizio)->modify('+13 days'); // 14 giorni (2 settimane)

$periodoPrec = (clone $dataInizio)->modify('-14 days')->format('Y-m-d');
$periodoSucc = (clone $dataInizio)->modify('+14 days')->format('Y-m-d');

$nomiGiorni = ['', 'Lun','Mar','Mer','Gio','Ven','Sab','Dom'];
$nomiMesi = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];

$prenotazioni = getPrenotazioniPeriodo($dataInizio->format('Y-m-d'), $dataFine->format('Y-m-d'));
$camere = getCamere();

// Genera lista dei 14 giorni
$giorniPeriodo = [];
$cursore = clone $dataInizio;
for ($i = 0; $i < 14; $i++) {
    $giorniPeriodo[] = clone $cursore;
    $cursore->modify('+1 day');
}

// Mappa prenotazioni per camera/data
$mappaPrenotazioni = [];
foreach ($prenotazioni as $p) {
    $inizio = max(strtotime($p['data_checkin']), strtotime($dataInizio->format('Y-m-d')));
    $fine = min(strtotime($p['data_checkout']), strtotime($dataFine->format('Y-m-d')) + 86400);
    for ($d = $inizio; $d < $fine; $d += 86400) {
        $chiave = date('Y-m-d', $d);
        $mappaPrenotazioni[$p['camera_id']][$chiave] = $p;
    }
}

$oggi = date('Y-m-d');

// Titolo periodo
$meseInizio = (int)$dataInizio->format('m');
$meseFine = (int)$dataFine->format('m');
$annoInizio = $dataInizio->format('Y');
if ($meseInizio === $meseFine) {
    $titoloPeriodo = $dataInizio->format('d') . ' - ' . $dataFine->format('d') . ' ' . $nomiMesi[$meseFine] . ' ' . $annoInizio;
} else {
    $titoloPeriodo = $dataInizio->format('d') . ' ' . $nomiMesi[$meseInizio] . ' - ' . $dataFine->format('d') . ' ' . $nomiMesi[$meseFine] . ' ' . $annoInizio;
}
?>

<h1>Gestione Camere - <?= $titoloPeriodo ?></h1>

<div class="calendario-nav">
    <a href="?da=<?= $periodoPrec ?>" class="btn btn-secondary">&laquo; 2 Settimane Prec.</a>
    <a href="?da=<?= date('Y-m-d') ?>" class="btn btn-primary">Oggi</a>
    <a href="?da=<?= $periodoSucc ?>" class="btn btn-secondary">2 Settimane Succ. &raquo;</a>
    <a href="prenotazioni.php?azione=nuova" class="btn btn-success">+ Nuova Prenotazione</a>
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
            <?php foreach ($giorniPeriodo as $giorno):
                $dataGiorno = $giorno->format('Y-m-d');
                $giornoSett = (int)$giorno->format('N');
                $isWeekend = $giornoSett >= 6;
                $isOggi = $dataGiorno === $oggi;
                $isLunedi = $giornoSett === 1;
            ?>
                <th class="giorno-col-header <?= $isWeekend ? 'weekend' : '' ?> <?= $isOggi ? 'oggi' : '' ?> <?= $isLunedi ? 'bordo-settimana' : '' ?>">
                    <span class="giorno-nome"><?= $nomiGiorni[$giornoSett] ?></span>
                    <span class="giorno-num"><?= $giorno->format('d') ?></span>
                    <span class="giorno-mese"><?= $nomiMesi[(int)$giorno->format('m')] ?></span>
                </th>
            <?php endforeach; ?>
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
            <?php foreach ($giorniPeriodo as $giorno):
                $dataGiorno = $giorno->format('Y-m-d');
                $giornoSett = (int)$giorno->format('N');
                $isLunedi = $giornoSett === 1;
                $pren = $mappaPrenotazioni[$camera['id']][$dataGiorno] ?? null;
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
                    $link = 'prenotazioni.php?azione=modifica&id=' . $pren['id'];
                } else {
                    $tooltip = 'Disponibile - clicca per prenotare';
                    $link = 'prenotazioni.php?azione=nuova&camera_id=' . $camera['id'] . '&checkin=' . $dataGiorno;
                }
            ?>
                <td class="giorno-col <?= $classe ?> <?= $isLunedi ? 'bordo-settimana' : '' ?>"
                    title="<?= e($tooltip) ?>"
                    <?php if ($link): ?>onclick="window.location='<?= $link ?>'"<?php endif; ?>
                    style="cursor:pointer">
                    <?php if ($pren && $pren['pagamento'] === 'sposi'): ?>
                        <span class="paga-icona">S</span>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
