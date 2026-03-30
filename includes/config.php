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

// Password accesso gestionale (bcrypt di 'Villa2024!')
define('GESTIONALE_PASSWORD_HASH', '$2y$12$Vg.UrLri38c7VeGMvGaH4udQkntBvKjX2T6V1MYv8rtFzYj6JI52q');

// Timezone
date_default_timezone_set('Europe/Rome');

// Error reporting (disabilitare in produzione)
error_reporting(E_ALL);
ini_set('display_errors', 1);
