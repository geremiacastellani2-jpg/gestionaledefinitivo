<?php
$titoloPagina = 'Gestione Clienti';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Clienti</h1>

<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Cognome</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Telefono</th>
            <th>Documento</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (getClienti() as $cliente): ?>
        <tr>
            <td><?= $cliente['id'] ?></td>
            <td><?= e($cliente['cognome']) ?></td>
            <td><?= e($cliente['nome']) ?></td>
            <td><?= e($cliente['email'] ?: '-') ?></td>
            <td><?= e($cliente['telefono'] ?: '-') ?></td>
            <td><?= e(ucfirst(str_replace('_', ' ', $cliente['documento_tipo']))) ?> <?= e($cliente['documento_numero'] ?: '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
