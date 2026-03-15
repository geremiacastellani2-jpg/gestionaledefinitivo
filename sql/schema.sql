-- Gestionale Hotel - Database Schema
-- Creare il database e usarlo prima di eseguire questo script:
-- CREATE DATABASE gestionale_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE gestionale_hotel;

CREATE TABLE IF NOT EXISTS camere (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL UNIQUE,
    tipo ENUM('singola', 'doppia', 'tripla', 'quadrupla', 'suite') NOT NULL DEFAULT 'doppia',
    piano INT NOT NULL DEFAULT 1,
    prezzo_notte DECIMAL(10,2) NOT NULL,
    stato ENUM('disponibile', 'occupata', 'manutenzione') NOT NULL DEFAULT 'disponibile',
    descrizione TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clienti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    documento_tipo ENUM('carta_identita', 'passaporto', 'patente') DEFAULT 'carta_identita',
    documento_numero VARCHAR(50),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prenotazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camera_id INT NOT NULL,
    cliente_id INT NOT NULL,
    data_checkin DATE NOT NULL,
    data_checkout DATE NOT NULL,
    stato ENUM('confermata', 'checkin', 'checkout', 'cancellata') NOT NULL DEFAULT 'confermata',
    pagamento ENUM('sposi', 'cliente') NOT NULL DEFAULT 'cliente',
    num_ospiti INT NOT NULL DEFAULT 1,
    prezzo_totale DECIMAL(10,2),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (camera_id) REFERENCES camere(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE RESTRICT,
    INDEX idx_date (data_checkin, data_checkout),
    INDEX idx_stato (stato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
