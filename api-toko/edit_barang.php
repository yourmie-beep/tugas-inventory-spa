<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Get parameters from either $_POST or JSON payload
    $id = $_POST['id'] ?? null;
    $nama = $_POST['nama'] ?? '';
    $harga = $_POST['harga'] ?? '';
    $stok = $_POST['stok'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    if (empty($id)) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if ($data) {
            $id = $data['id'] ?? null;
            $nama = $data['nama'] ?? '';
            $harga = $data['harga'] ?? '';
            $stok = $data['stok'] ?? '';
            $deskripsi = $data['deskripsi'] ?? '';
        }
    }

    if (empty($id) || empty($nama) || empty($harga) || $stok === '') {
        throw new Exception('Data tidak lengkap.');
    }

    // Get existing image path
    $stmt_old = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
    $stmt_old->execute([$id]);
    $old_gambar = $stmt_old->fetchColumn();
    $gambar_path = $old_gambar;

    // Handle file upload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Format file tidak didukung. Harap upload gambar (jpg, jpeg, png, gif, webp).');
        }

        $file_name = uniqid('img_', true) . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
            $gambar_path = 'uploads/' . $file_name;
            
            // Delete old file if it exists
            if ($old_gambar && file_exists(__DIR__ . '/' . $old_gambar)) {
                @unlink(__DIR__ . '/' . $old_gambar);
            }
        } else {
            throw new Exception('Gagal mengupload gambar.');
        }
    }

    $sql = "UPDATE barang SET nama = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nama,
        $harga,
        $stok,
        $deskripsi,
        $gambar_path,
        $id
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
