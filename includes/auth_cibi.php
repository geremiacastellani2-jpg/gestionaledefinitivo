<?php
// Auth utenti cibi - da includere dopo header.php nelle pagine cibi
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

if (!$cibiAutenticato):
?>
<div style="max-width:380px; margin:60px auto; text-align:center;">
    <h2 style="margin-bottom:0.5rem; color:#1e293b;">Accesso Cibi</h2>
    <p style="color:#64748b; margin-bottom:1.5rem; font-size:0.9rem;">Inserisci le credenziali per accedere alla gestione cibi.</p>
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
</div>
<?php require_once __DIR__ . '/footer.php'; exit; ?>
<?php endif; ?>
