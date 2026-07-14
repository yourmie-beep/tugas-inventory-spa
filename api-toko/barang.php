<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $pdo = get_pdo_connection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check if searching by QR Code
        $kode_qr = isset($_GET['kode_qr']) ? trim($_GET['kode_qr']) : '';
        if ($kode_qr !== '') {
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE kode_qr = ?");
            $stmt->execute([$kode_qr]);
            $item = $stmt->fetch();
            
            echo json_encode([
                'status' => 'success',
                'data' => $item ? [$item] : [],
                'message' => $item ? 'Barang ditemukan' : 'Barang tidak ditemukan'
            ]);
            exit;
        }

        // Search query
        $search = isset($_GET['cari']) ? trim($_GET['cari']) : '';
        
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 5; // 5 items per page
        $offset = ($page - 1) * $limit;

        // Build Query
        $where_clause = "";
        $params = [];
        if ($search !== '') {
            $where_clause = "WHERE nama LIKE ? OR deskripsi LIKE ?";
            $params = ["%$search%", "%$search%"];
        }

        // Count total items
        $count_sql = "SELECT COUNT(*) FROM barang $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_items = (int)$count_stmt->fetchColumn();
        $total_pages = ceil($total_items / $limit);
        if ($total_pages < 1) $total_pages = 1;

        // Fetch data for the current page
        $data_sql = "SELECT * FROM barang $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $data_stmt = $pdo->prepare($data_sql);
        $data_stmt->execute($params);
        $barang = $data_stmt->fetchAll();

        global $is_localhost;
        $server_env = $is_localhost ? 'Laragon (Local)' : 'InfinityFree';

        echo json_encode([
            'status' => 'success',
            'server' => $server_env,
            'data' => $barang,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'total_items' => $total_items
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure user is authenticated
        verify_access_token();
        
        $id = $_POST['id'] ?? null;
        $nama = $_POST['nama'] ?? '';
        $harga = $_POST['harga'] ?? '';
        $stok = $_POST['stok'] ?? '';
        $deskripsi = $_POST['deskripsi'] ?? '';
        $kode_qr = isset($_POST['kode_qr']) && $_POST['kode_qr'] !== '' ? $_POST['kode_qr'] : null;
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? $_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? $_POST['longitude'] : null;

        if (empty($nama) || empty($harga) || $stok === '') {
            throw new Exception('Data tidak lengkap.');
        }

        // Handle file upload
        $gambar_path = null;
        if ($id) {
            $stmt_old = $pdo->prepare("SELECT gambar FROM barang WHERE id = ?");
            $stmt_old->execute([$id]);
            $gambar_path = $stmt_old->fetchColumn();
        }

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
                // Delete old file if updating
                if ($id && $gambar_path && file_exists(__DIR__ . '/' . $gambar_path)) {
                    @unlink(__DIR__ . '/' . $gambar_path);
                }
                $gambar_path = 'uploads/' . $file_name;
            } else {
                throw new Exception('Gagal mengupload gambar.');
            }
        }

        if ($id) {
            // Update
            $sql = "UPDATE barang SET nama = ?, harga = ?, stok = ?, deskripsi = ?, gambar = ?, kode_qr = ?, latitude = ?, longitude = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama,
                $harga,
                $stok,
                $deskripsi,
                $gambar_path,
                $kode_qr,
                $latitude,
                $longitude,
                $id
            ]);
            echo json_encode([
                'status' => 'success',
                'message' => 'Barang berhasil diupdate!'
            ]);
        } else {
            // Insert
            $sql = "INSERT INTO barang (nama, harga, stok, deskripsi, gambar, kode_qr, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nama,
                $harga,
                $stok,
                $deskripsi,
                $gambar_path,
                $kode_qr,
                $latitude,
                $longitude
            ]);
            echo json_encode([
                'status' => 'success',
                'message' => 'Barang berhasil ditambahkan!',
                'id' => $pdo->lastInsertId()
            ]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
