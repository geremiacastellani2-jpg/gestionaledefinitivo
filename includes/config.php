<?php
// Configurazione Database
define('DB_HOST', '31.11.39.107');
define('DB_NAME', 'Sql1711789_2');
define('DB_USER', 'Sql1711789');
define('DB_PASS', 'Vobred-sibpec-monfy7');
define('DB_CHARSET', 'utf8mb4');

// Configurazione Applicazione
define('APP_NAME', 'Gestionale Hotel');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/gestionale/');

// Password accesso gestionale (bcrypt di 'admin')
define('GESTIONALE_PASSWORD_HASH', '$2y$12$dvwNUYytQgE04bnmz4Z/oO0Dq/m6nLT6TQ.Hhv1xQhYB8kLeqYZ3S');

// Timezone
date_default_timezone_set('Europe/Rome');

// Error reporting (disabilitare in produzione)
error_reporting(E_ALL);
ini_set('display_errors', 1);
