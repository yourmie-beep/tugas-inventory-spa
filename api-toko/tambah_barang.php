<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = get_pdo_connection();
    
    // Get POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['nama']) || empty($data['harga']) || empty($data['stok'])) {
        throw new Exception('Data tidak lengkap.');
    }

    $sql = "INSERT INTO barang (nama, harga, stok, deskripsi) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nama'],
        $data['harga'],
        $data['stok'],
        $data['deskripsi'] ?? ''
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Barang berhasil ditambahkan!',
        'id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
