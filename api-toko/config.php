<?php
/**
 * Database Configuration for StockPro
 * Handles automatic switching between Local (Laragon) and Production (InfinityFree)
 */

$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

if ($is_localhost) {
    // Laragon Settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'toko_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // InfinityFree Settings
    define('DB_HOST', 'sql200.infinityfree.com'); 
    define('DB_NAME', 'if0_41845541_toko_db'); 
    define('DB_USER', 'if0_41845541');         
    define('DB_PASS', 'echf4wFpBumXVu');    
}

define('DB_CHARSET', 'utf8mb4');

function ensure_tables_and_migrations($pdo) {
    // Ensure users table exists (Soal 4 requirement)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        token VARCHAR(255) DEFAULT NULL
    )");
    
    // Seed users if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $admin_hash = password_hash('password', PASSWORD_DEFAULT);
        $kasir_hash = password_hash('password', PASSWORD_DEFAULT);
        
        $insert_stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?), (?, ?)");
        $insert_stmt->execute(['admin', $admin_hash, 'kasir', $kasir_hash]);
    }

    // Ensure barang table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        deskripsi TEXT,
        gambar VARCHAR(255) DEFAULT NULL,
        kode_qr VARCHAR(255) DEFAULT NULL,
        latitude VARCHAR(100) DEFAULT NULL,
        longitude VARCHAR(100) DEFAULT NULL
    )");

    // Migrations: add columns if table already existed in a previous session
    $migrations = [
        "ALTER TABLE barang ADD COLUMN gambar VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE barang ADD COLUMN kode_qr VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE barang ADD COLUMN latitude VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE barang ADD COLUMN longitude VARCHAR(100) DEFAULT NULL"
    ];
    foreach ($migrations as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            // Column may already exist, ignore error
        }
    }
}

function get_pdo_connection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        ensure_tables_and_migrations($pdo);
        return $pdo;
    } catch (PDOException $e) {
        // If connection fails because DB doesn't exist (likely on local), try connecting without dbname
        if ($e->getCode() == 1049) {
            $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $temp_pdo = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            $temp_pdo->exec("USE " . DB_NAME);
            ensure_tables_and_migrations($temp_pdo);
            return $temp_pdo;
        }
        throw $e;
    }
}

/**
 * Verifies access token from Authorization header or URL parameter.
 * Rejects request with 'Akses Ditolak!' (Soal 4 requirement) if invalid.
 */
function verify_access_token() {
    $provided_token = '';
    
    // Check token in GET parameters (for cetak_laporan.php link)
    if (isset($_GET['token'])) {
        $provided_token = $_GET['token'];
    } else {
        // Check Authorization header
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $provided_token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $provided_token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        }
    }
    
    if (empty($provided_token)) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses Ditolak!'
        ]);
        exit;
    }
    
    // Static token fallback for print logic
    if ($provided_token === 'StockProSecretToken2026') {
        return true;
    }
    
    // Verify token against database users
    try {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE token = ? AND token IS NOT NULL AND token != ''");
        $stmt->execute([$provided_token]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
    } catch (Exception $e) {
        // Fallback or log error
    }
    
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses Ditolak!'
    ]);
    exit;
}
?>
