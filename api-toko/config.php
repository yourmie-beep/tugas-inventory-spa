<?php
/**
 * Database Configuration for StockPro
 * Handles automatic switching between Local (Laragon) and Production (InfinityFree)
 */

$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1');
$is_production = ($_SERVER['HTTP_HOST'] === 'stockpro.42web.io');

if ($is_localhost) {
    // Laragon Settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'toko_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else if ($is_production) {
    // InfinityFree Settings (Update these with your actual credentials from vPanel)
    define('DB_HOST', 'sqlxxx.epizy.com'); // Replace with your MySQL Host
    define('DB_NAME', 'epiz_xxx_toko_db'); // Replace with your Database Name
    define('DB_USER', 'epiz_xxx');         // Replace with your Database Username
    define('DB_PASS', 'your_password');    // Replace with your Database Password
}

define('DB_CHARSET', 'utf8mb4');

function get_pdo_connection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // On local, we might need to create the DB first if it doesn't exist
        // But on InfinityFree, the DB is already created via vPanel
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If connection fails because DB doesn't exist (likely on local), try connecting without dbname
        if ($e->getCode() == 1049) {
            $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $temp_pdo = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            $temp_pdo->exec("USE " . DB_NAME);
            return $temp_pdo;
        }
        throw $e;
    }
}
?>
