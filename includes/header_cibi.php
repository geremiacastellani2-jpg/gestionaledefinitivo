<?php
session_start();
require_once __DIR__ . '/functions.php';

$flash = getFlash();
$paginaCorrente = basename($_SERVER['PHP_SELF'], '.php');

// Auth utenti cibi
initUtentiCibi();

$cibiAuthErrore = '';
if (isset($_POST['cibi_login'])) {
    $utente = loginUtenteCibi(trim($_POST['cibi_username']), $_POST['cibi_password']);
    if ($utente) {
        $_SESSION['cibi_user_id'] = $utente['id'];
        $_SESSION['cibi_username'] = $utente['username'];
        $_SESSION['cibi_is_admin'] = (bool)$utente['is_admin'];
    } else {
        $cibiAuthErrore = 'Username o password non validi.';
    }
}
if (isset($_GET['logout_cibi'])) {
    unset($_SESSION['cibi_user_id'], $_SESSION['cibi_username'], $_SESSION['cibi_is_admin']);
    header('Location: ' . BASE_URL . 'pages/cibi.php');
    exit;
}

$cibiAutenticato = !empty($_SESSION['cibi_user_id']);
$cibiIsAdmin = !empty($_SESSION['cibi_is_admin']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titoloPagina ?? 'Gestione Cibi') ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= BASE_URL ?>pages/cibi.php">Gestione Cibi</a>
    </div>
    <?php if ($cibiAutenticato): ?>
    <ul class="navbar-menu">
        <li><a href="<?= BASE_URL ?>pages/cibi.php" class="<?= $paginaCorrente === 'cibi' ? 'active' : '' ?>">Cibi</a></li>
        <?php if ($cibiIsAdmin): ?>
        <li><a href="<?= BASE_URL ?>pages/cibi.php?azione=utenti" class="<?= isset($_GET['azione']) && $_GET['azione'] === 'utenti' ? 'active' : '' ?>">Gestione Utenti</a></li>
        <?php endif; ?>
        <li><a href="<?= BASE_URL ?>index.php" style="color:#64748b;">Gestionale</a></li>
        <li><a href="?logout_cibi=1" style="color:#ef4444;">Esci (<?= e($_SESSION['cibi_username']) ?>)</a></li>
    </ul>
    <?php endif; ?>
</nav>
<main class="container">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipo']) ?>">
        <?= e($flash['messaggio']) ?>
    </div>
<?php endif; ?>
<?php if (!$cibiAutenticato): ?>
<div style="max-width:380px; margin:80px auto; text-align:center;">
    <h2 style="margin-bottom:0.5rem; color:#1e293b;">Accesso Cibi</h2>
    <p style="color:#64748b; margin-bottom:1.5rem; font-size:0.9rem;">Inserisci le credenziali per accedere.</p>
    <?php if ($cibiAuthErrore): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;"><?= e($cibiAuthErrore) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <input type="text" name="cibi_username" placeholder="Username" required autofocus
            style="width:100%; padding:0.7rem; font-size:1rem; border:2px solid #cbd5e1; border-radius:8px; margin-bottom:0.7rem; box-sizing:border-box;">
        <input type="password" name="cibi_password" placeholder="Password" required
            style="width:100%; padding:0.7rem; font-size:1rem; border:2px solid #cbd5e1; border-radius:8px; margin-bottom:1rem; box-sizing:border-box;">
        <button type="submit" name="cibi_login" value="1" class="btn btn-primary" style="width:100%; padding:0.7rem; font-size:1rem;">Accedi</button>
    </form>
    <a href="<?= BASE_URL ?>index.php" style="display:inline-block; margin-top:1rem; color:#64748b; font-size:0.85rem;">Vai al Gestionale Camere</a>
</div>
<?php require_once __DIR__ . '/footer.php'; exit; ?>
<?php endif; ?>
