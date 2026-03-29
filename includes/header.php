<?php
session_start();
require_once __DIR__ . '/functions.php';
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
    <ul class="navbar-menu">
        <li><a href="<?= BASE_URL ?>index.php" class="<?= $paginaCorrente === 'index' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>pages/calendario.php" class="<?= $paginaCorrente === 'calendario' ? 'active' : '' ?>">Griglia Camere</a></li>
        <li><a href="<?= BASE_URL ?>pages/prenotazioni.php" class="<?= $paginaCorrente === 'prenotazioni' ? 'active' : '' ?>">Prenotazioni</a></li>
        <li><a href="<?= BASE_URL ?>pages/camere.php" class="<?= $paginaCorrente === 'camere' ? 'active' : '' ?>">Anagrafica Camere</a></li>
        <li><a href="<?= BASE_URL ?>pages/clienti.php" class="<?= $paginaCorrente === 'clienti' ? 'active' : '' ?>">Clienti</a></li>
        <li><a href="<?= BASE_URL ?>pages/richieste.php" class="<?= $paginaCorrente === 'richieste' ? 'active' : '' ?>">Richieste Sposi</a></li>
        <li><a href="<?= BASE_URL ?>pages/cibi.php" class="<?= $paginaCorrente === 'cibi' || $paginaCorrente === 'cibo-dettaglio' ? 'active' : '' ?>">Cibi</a></li>
    </ul>
</nav>
<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipo']) ?>">
        <?= e($flash['messaggio']) ?>
    </div>
<?php endif; ?>
