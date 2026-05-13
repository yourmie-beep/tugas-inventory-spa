<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Get PUT data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['id']) || empty($data['nama']) || empty($data['harga']) || empty($data['stok'])) {
        throw new Exception('Data tidak lengkap.');
    }

    $sql = "UPDATE barang SET nama = ?, harga = ?, stok = ?, deskripsi = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['nama'],
        $data['harga'],
        $data['stok'],
        $data['deskripsi'] ?? '',
        $data['id']
    ]);

    // Check row count or if execute was successful
    if ($stmt->errorCode() == '00000') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Barang berhasil diupdate!'
        ]);
    } else {
        throw new Exception('Gagal mengupdate barang.');
    }

} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
