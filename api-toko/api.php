<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = get_pdo_connection();
    
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        deskripsi TEXT
    )");

    // Check if empty, insert dummy data
    $stmt = $pdo->query("SELECT COUNT(*) FROM barang");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO barang (nama, harga, stok, deskripsi) VALUES 
            ('Laptop Pro X1', 15000000, 10, 'High performance laptop for pros.'),
            ('Smartphone Z Plus', 8000000, 25, 'Flagship smartphone with amazing camera.'),
            ('Wireless Mouse', 250000, 50, 'Ergonomic wireless mouse.'),
            ('Mechanical Keyboard', 1200000, 15, 'RGB mechanical keyboard with blue switches.'),
            ('Monitor 4K', 4500000, 8, 'Crystal clear 4K resolution monitor.')
        ");
    }

    $stmt = $pdo->query("SELECT * FROM barang ORDER BY id DESC");
    $barang = $stmt->fetchAll();

    global $is_localhost;
    $server_env = $is_localhost ? 'Laragon (Local)' : 'InfinityFree';

    echo json_encode([
        'status' => 'success',
        'server' => $server_env,
        'data' => $barang
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
