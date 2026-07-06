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
    
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS barang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        deskripsi TEXT,
        gambar VARCHAR(255) DEFAULT NULL
    )");

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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
