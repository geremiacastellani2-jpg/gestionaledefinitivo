<?php
session_start();
require_once __DIR__ . '/functions.php';

// --- Autenticazione password gestionale ---
$authErrore = '';
if (isset($_POST['gestionale_password'])) {
    if (password_verify($_POST['gestionale_password'], GESTIONALE_PASSWORD_HASH)) {
        $_SESSION['gestionale_auth'] = true;
        $_SESSION['gestionale_auth_time'] = time();
    } else {
        $authErrore = 'Password non valida.';
    }
}
if (isset($_GET['logout_gestionale'])) {
    unset($_SESSION['gestionale_auth'], $_SESSION['gestionale_auth_time']);
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$autenticato = !empty($_SESSION['gestionale_auth']);

$flash = getFlash();
$paginaCorrente = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titoloPagina ?? APP_NAME) ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= BASE_URL ?>index.php"><?= APP_NAME ?></a>
    </div>
    <?php if ($autenticato): ?>
    <ul class="navbar-menu">
        <li><a href="<?= BASE_URL ?>index.php" class="<?= $paginaCorrente === 'index' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>pages/calendario.php" class="<?= $paginaCorrente === 'calendario' ? 'active' : '' ?>">Griglia Camere</a></li>
        <li><a href="<?= BASE_URL ?>pages/prenotazioni.php" class="<?= $paginaCorrente === 'prenotazioni' ? 'active' : '' ?>">Prenotazioni</a></li>
        <li><a href="<?= BASE_URL ?>pages/camere.php" class="<?= $paginaCorrente === 'camere' ? 'active' : '' ?>">Anagrafica Camere</a></li>
        <li><a href="<?= BASE_URL ?>pages/clienti.php" class="<?= $paginaCorrente === 'clienti' ? 'active' : '' ?>">Clienti</a></li>
        <li><a href="<?= BASE_URL ?>pages/richieste.php" class="<?= $paginaCorrente === 'richieste' ? 'active' : '' ?>">Richieste Sposi</a></li>
        <li><a href="<?= BASE_URL ?>pages/cibi.php" class="<?= $paginaCorrente === 'cibi' || $paginaCorrente === 'cibo-dettaglio' ? 'active' : '' ?>">Cibi</a></li>
        <li><a href="?logout_gestionale=1" style="color:#ef4444;">Esci</a></li>
    </ul>
    <?php endif; ?>
</nav>
<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipo']) ?>">
        <?= e($flash['messaggio']) ?>
    </div>
<?php endif; ?>
<?php if (!$autenticato): ?>
<div style="max-width:360px; margin:80px auto; text-align:center;">
    <h2 style="margin-bottom:1.5rem; color:#1e293b;">Accesso Gestionale</h2>
    <?php if ($authErrore): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;"><?= e($authErrore) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="password" name="gestionale_password" placeholder="Password" required autofocus
            style="width:100%; padding:0.75rem; font-size:1.1rem; border:2px solid #cbd5e1; border-radius:8px; margin-bottom:1rem; box-sizing:border-box;">
        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem; font-size:1.1rem;">Accedi</button>
    </form>
</div>
<?php require_once __DIR__ . '/footer.php'; exit; ?>
<?php endif; ?>
