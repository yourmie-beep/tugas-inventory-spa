<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->query("SELECT nama, harga, stok FROM barang ORDER BY id DESC");
    $barang = $stmt->fetchAll();

    $labels = [];
    $values = [];
    foreach ($barang as $item) {
        $labels[] = $item['nama'];
        $values[] = floatval($item['harga']) * intval($item['stok']);
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'values' => $values
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
