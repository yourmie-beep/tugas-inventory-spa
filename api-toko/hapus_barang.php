<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Support both DELETE and POST for max compatibility, also handle JSON or URL Encoded body
$data = json_decode(file_get_contents("php://input"));
$id = $_GET['id'] ?? ($_POST['id'] ?? ($data->id ?? null));

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Barang is required']);
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Get existing image path to delete the file
    $stmt_old = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
    $stmt_old->execute([$id]);
    $old_gambar = $stmt_old->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM barang WHERE id = :id");
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            if ($old_gambar && file_exists(__DIR__ . '/' . $old_gambar)) {
                @unlink(__DIR__ . '/' . $old_gambar);
            }
            echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Barang tidak ditemukan']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus barang']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
