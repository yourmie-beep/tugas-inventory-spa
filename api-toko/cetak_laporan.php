<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Token authentication
$token = 'StockProSecretToken2026';
$provided_token = '';

if (isset($_GET['token'])) {
    $provided_token = $_GET['token'];
} else {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $provided_token = str_replace('Bearer ', '', $headers['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $provided_token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
}

if ($provided_token !== $token) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized: Invalid token.'
    ]);
    exit;
}

try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->query("SELECT * FROM barang ORDER BY id DESC");
    $barang = $stmt->fetchAll();
    
    $total_harga_aset = 0;
    $total_stok = 0;
    foreach ($barang as $item) {
        $total_harga_aset += (floatval($item['harga']) * intval($item['stok']));
        $total_stok += intval($item['stok']);
    }

    echo json_encode([
        'status' => 'success',
        'data' => $barang,
        'agregat' => [
            'total_harga_aset' => $total_harga_aset,
            'total_stok' => $total_stok,
            'total_items' => count($barang)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
