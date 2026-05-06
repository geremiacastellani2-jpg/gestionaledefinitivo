<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

if (!$checkin || !$checkout || $checkin >= $checkout) {
    echo json_encode(['error' => 'Date non valide', 'camere' => []]);
    exit;
}

$escludiId = !empty($_GET['escludi']) ? (int)$_GET['escludi'] : null;
$camere = getCamereDisponibili($checkin, $checkout, $escludiId);

$risultato = array_map(function($c) {
    return [
        'id' => (int)$c['id'],
        'numero' => $c['numero'],
        'tipo' => $c['tipo'],
        'piano' => (int)$c['piano'],
        'descrizione' => $c['descrizione'] ?: '',
    ];
}, $camere);

echo json_encode(['camere' => $risultato]);
