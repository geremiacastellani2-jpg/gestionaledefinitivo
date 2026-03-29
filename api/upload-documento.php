<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['foto'])) {
    echo json_encode(['error' => 'Nessun file ricevuto']);
    exit;
}

$file = $_FILES['foto'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$ammessi = ['jpg', 'jpeg', 'png', 'pdf', 'webp', 'heic'];

if (!in_array($ext, $ammessi)) {
    echo json_encode(['error' => 'Formato non supportato. Usa: ' . implode(', ', $ammessi)]);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['error' => 'File troppo grande (max 10MB)']);
    exit;
}

$nomeFile = 'doc_' . bin2hex(random_bytes(12)) . '.' . $ext;
$percorso = __DIR__ . '/../uploads/documenti/' . $nomeFile;

if (move_uploaded_file($file['tmp_name'], $percorso)) {
    echo json_encode(['ok' => true, 'file' => $nomeFile]);
} else {
    echo json_encode(['error' => 'Errore nel caricamento']);
}
