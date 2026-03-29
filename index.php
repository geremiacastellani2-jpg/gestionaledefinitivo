<?php
$titoloPagina = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$camere = contaCamere();
$prenotazioni = contaPrenotazioni();
?>

<h1>Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $camere['totale'] ?></div>
        <div class="stat-label">Camere Totali</div>
    </div>
    <div class="stat-card stat-disponibili">
        <div class="stat-number"><?= $camere['disponibili'] ?></div>
        <div class="stat-label">Disponibili</div>
    </div>
    <div class="stat-card stat-occupate">
        <div class="stat-number"><?= $camere['occupate'] ?></div>
        <div class="stat-label">Occupate</div>
    </div>
    <div class="stat-card stat-manutenzione">
        <div class="stat-number"><?= $camere['manutenzione'] ?></div>
        <div class="stat-label">Manutenzione</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $prenotazioni['attive'] ?></div>
        <div class="stat-label">Prenotazioni Attive</div>
    </div>
    <div class="stat-card stat-checkin">
        <div class="stat-number"><?= $prenotazioni['checkin_oggi'] ?></div>
        <div class="stat-label">Check-in Oggi</div>
    </div>
    <div class="stat-card stat-checkout">
        <div class="stat-number"><?= $prenotazioni['checkout_oggi'] ?></div>
        <div class="stat-label">Check-out Oggi</div>
    </div>
</div>

<div class="quick-actions">
    <h2>Azioni Rapide</h2>
    <a href="<?= BASE_URL ?>pages/camere.php?azione=nuova" class="btn btn-primary">+ Nuova Camera</a>
    <a href="<?= BASE_URL ?>pages/prenotazioni.php?azione=nuova" class="btn btn-success">+ Nuova Prenotazione</a>
    <a href="<?= BASE_URL ?>pages/calendario.php" class="btn btn-info">Calendario</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
