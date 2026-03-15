<?php
// Configurazione Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestionale_hotel');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurazione Applicazione
define('APP_NAME', 'Gestionale Hotel');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/');

// Timezone
date_default_timezone_set('Europe/Rome');

// Error reporting (disabilitare in produzione)
error_reporting(E_ALL);
ini_set('display_errors', 1);
