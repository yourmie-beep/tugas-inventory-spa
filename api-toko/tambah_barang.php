<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure user is authenticated (Soal 4 requirement)
verify_access_token();

try {
    $pdo = get_pdo_connection();
    
    // Get POST data from $_POST instead of JSON string
    $nama = $_POST['nama'] ?? '';
    $harga = $_POST['harga'] ?? '';
    $stok = $_POST['stok'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';

    if (empty($nama) || empty($harga) || $stok === '') {
        throw new Exception('Data tidak lengkap.');
    }

    // Handle file upload
    $gambar_path = null;
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
        } else {
            throw new Exception('Gagal mengupload gambar.');
        }
    }

    $sql = "INSERT INTO barang (nama, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nama,
        $harga,
        $stok,
        $deskripsi,
        $gambar_path
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Barang berhasil ditambahkan!',
        'id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    http_response_code(500); // Changed from 400 to 500 to prevent InfinityFree from blocking the JSON response
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
