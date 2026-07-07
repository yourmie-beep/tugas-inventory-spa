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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Use POST.'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Username dan password wajib diisi.'
    ]);
    exit;
}

try {
    $pdo = get_pdo_connection();
    
    // Fetch user from DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Generate secure dynamic token
        $token = bin2hex(random_bytes(16));
        
        // Update user token in DB
        $update_stmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
        $update_stmt->execute([$token, $user['id']]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Login berhasil! Selamat datang, ' . htmlspecialchars($username) . '.',
            'data' => [
                'token' => $token,
                'username' => $user['username'],
                'expires' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Username atau password salah.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
