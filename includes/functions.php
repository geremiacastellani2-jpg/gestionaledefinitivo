<?php
require_once __DIR__ . '/db.php';

// --- CAMERE ---

function getCamere(): array {
    $db = getDB();
    return $db->query('SELECT * FROM camere ORDER BY numero')->fetchAll();
}

function getCamera(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM camere WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function salvaCamere(array $data): bool {
    $db = getDB();
    if (!empty($data['id'])) {
        $stmt = $db->prepare('UPDATE camere SET numero=?, tipo=?, piano=?, prezzo_notte=?, stato=?, descrizione=? WHERE id=?');
        return $stmt->execute([
            $data['numero'], $data['tipo'], $data['piano'],
            $data['prezzo_notte'], $data['stato'], $data['descrizione'], $data['id']
        ]);
    } else {
        $stmt = $db->prepare('INSERT INTO camere (numero, tipo, piano, prezzo_notte, stato, descrizione) VALUES (?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['numero'], $data['tipo'], $data['piano'],
            $data['prezzo_notte'], $data['stato'], $data['descrizione']
        ]);
    }
}

function eliminaCamera(int $id): bool {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM camere WHERE id = ?');
    return $stmt->execute([$id]);
}

function contaCamere(): array {
    $db = getDB();
    $totale = $db->query('SELECT COUNT(*) FROM camere')->fetchColumn();
    $disponibili = $db->query("SELECT COUNT(*) FROM camere WHERE stato = 'disponibile'")->fetchColumn();
    $occupate = $db->query("SELECT COUNT(*) FROM camere WHERE stato = 'occupata'")->fetchColumn();
    $manutenzione = $db->query("SELECT COUNT(*) FROM camere WHERE stato = 'manutenzione'")->fetchColumn();
    return compact('totale', 'disponibili', 'occupate', 'manutenzione');
}

// --- CLIENTI ---

function getClienti(): array {
    $db = getDB();
    return $db->query('SELECT * FROM clienti ORDER BY cognome, nome')->fetchAll();
}

function getCliente(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM clienti WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function salvaCliente(array $data): int {
    $db = getDB();
    if (!empty($data['id'])) {
        $stmt = $db->prepare('UPDATE clienti SET nome=?, cognome=?, email=?, telefono=?, documento_tipo=?, documento_numero=?, note=? WHERE id=?');
        $stmt->execute([
            $data['nome'], $data['cognome'], $data['email'], $data['telefono'],
            $data['documento_tipo'], $data['documento_numero'], $data['note'], $data['id']
        ]);
        return (int)$data['id'];
    } else {
        $stmt = $db->prepare('INSERT INTO clienti (nome, cognome, email, telefono, documento_tipo, documento_numero, note) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['nome'], $data['cognome'], $data['email'], $data['telefono'],
            $data['documento_tipo'], $data['documento_numero'], $data['note']
        ]);
        return (int)$db->lastInsertId();
    }
}

// --- PRENOTAZIONI ---

function getPrenotazioni(string $filtroStato = ''): array {
    $db = getDB();
    $sql = 'SELECT p.*, c.numero AS camera_numero, c.tipo AS camera_tipo,
            cl.nome AS cliente_nome, cl.cognome AS cliente_cognome, cl.email AS cliente_email, cl.telefono AS cliente_telefono
            FROM prenotazioni p
            JOIN camere c ON p.camera_id = c.id
            JOIN clienti cl ON p.cliente_id = cl.id';
    if ($filtroStato) {
        $sql .= ' WHERE p.stato = ?';
        $stmt = $db->prepare($sql . ' ORDER BY p.data_checkin DESC');
        $stmt->execute([$filtroStato]);
    } else {
        $stmt = $db->prepare($sql . ' ORDER BY p.data_checkin DESC');
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getPrenotazione(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT p.*, c.numero AS camera_numero, c.tipo AS camera_tipo, c.prezzo_notte,
            cl.nome AS cliente_nome, cl.cognome AS cliente_cognome, cl.email AS cliente_email, cl.telefono AS cliente_telefono
            FROM prenotazioni p
            JOIN camere c ON p.camera_id = c.id
            JOIN clienti cl ON p.cliente_id = cl.id
            WHERE p.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function salvaPrenotazione(array $data): bool {
    $db = getDB();
    $checkin = new DateTime($data['data_checkin']);
    $checkout = new DateTime($data['data_checkout']);
    $notti = $checkin->diff($checkout)->days;

    $camera = getCamera((int)$data['camera_id']);
    $prezzoTotale = $camera ? $camera['prezzo_notte'] * $notti : 0;

    if (!empty($data['id'])) {
        $stmt = $db->prepare('UPDATE prenotazioni SET camera_id=?, cliente_id=?, data_checkin=?, data_checkout=?, stato=?, pagamento=?, num_ospiti=?, prezzo_totale=?, note=? WHERE id=?');
        return $stmt->execute([
            $data['camera_id'], $data['cliente_id'], $data['data_checkin'], $data['data_checkout'],
            $data['stato'], $data['pagamento'], $data['num_ospiti'], $prezzoTotale, $data['note'], $data['id']
        ]);
    } else {
        $stmt = $db->prepare('INSERT INTO prenotazioni (camera_id, cliente_id, data_checkin, data_checkout, stato, pagamento, num_ospiti, prezzo_totale, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            $data['camera_id'], $data['cliente_id'], $data['data_checkin'], $data['data_checkout'],
            $data['stato'] ?? 'confermata', $data['pagamento'] ?? 'cliente', $data['num_ospiti'], $prezzoTotale, $data['note']
        ]);
    }
}

function cambiaStatoPrenotazione(int $id, string $nuovoStato): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE prenotazioni SET stato = ? WHERE id = ?');
    $result = $stmt->execute([$nuovoStato, $id]);

    // Aggiorna stato camera
    $prenotazione = getPrenotazione($id);
    if ($prenotazione) {
        $statoCamera = match($nuovoStato) {
            'checkin' => 'occupata',
            'checkout', 'cancellata' => 'disponibile',
            default => null
        };
        if ($statoCamera) {
            $stmtCam = $db->prepare('UPDATE camere SET stato = ? WHERE id = ?');
            $stmtCam->execute([$statoCamera, $prenotazione['camera_id']]);
        }
    }
    return $result;
}

function eliminaPrenotazione(int $id): bool {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM prenotazioni WHERE id = ?');
    return $stmt->execute([$id]);
}

function contaPrenotazioni(): array {
    $db = getDB();
    $oggi = date('Y-m-d');
    $totaleAttive = $db->prepare("SELECT COUNT(*) FROM prenotazioni WHERE stato IN ('confermata','checkin')");
    $totaleAttive->execute();

    $checkinOggi = $db->prepare("SELECT COUNT(*) FROM prenotazioni WHERE data_checkin = ? AND stato = 'confermata'");
    $checkinOggi->execute([$oggi]);

    $checkoutOggi = $db->prepare("SELECT COUNT(*) FROM prenotazioni WHERE data_checkout = ? AND stato = 'checkin'");
    $checkoutOggi->execute([$oggi]);

    return [
        'attive' => $totaleAttive->fetchColumn(),
        'checkin_oggi' => $checkinOggi->fetchColumn(),
        'checkout_oggi' => $checkoutOggi->fetchColumn(),
    ];
}

function getPrenotazioniCalendario(string $meseAnno): array {
    $db = getDB();
    $inizioMese = $meseAnno . '-01';
    $fineMese = date('Y-m-t', strtotime($inizioMese));

    $stmt = $db->prepare("SELECT p.*, c.numero AS camera_numero, cl.nome AS cliente_nome, cl.cognome AS cliente_cognome, cl.email AS cliente_email, cl.telefono AS cliente_telefono
        FROM prenotazioni p
        JOIN camere c ON p.camera_id = c.id
        JOIN clienti cl ON p.cliente_id = cl.id
        WHERE p.data_checkin <= ? AND p.data_checkout >= ? AND p.stato != 'cancellata'
        ORDER BY c.numero");
    $stmt->execute([$fineMese, $inizioMese]);
    return $stmt->fetchAll();
}

function cameraDisponibile(int $cameraId, string $checkin, string $checkout, ?int $escludiPrenotazioneId = null): bool {
    $db = getDB();
    $sql = "SELECT COUNT(*) FROM prenotazioni
            WHERE camera_id = ? AND stato != 'cancellata'
            AND data_checkin < ? AND data_checkout > ?";
    $params = [$cameraId, $checkout, $checkin];

    if ($escludiPrenotazioneId) {
        $sql .= ' AND id != ?';
        $params[] = $escludiPrenotazioneId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() == 0;
}

// --- UTILITA ---

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function setFlash(string $tipo, string $messaggio): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'messaggio' => $messaggio];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
