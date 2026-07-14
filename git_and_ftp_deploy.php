<?php
header('Content-Type: text/plain');

echo "=== GIT DEPLOYMENT ===\n";
echo "Adding files...\n";
echo shell_exec('git add . 2>&1') . "\n";

echo "Committing files...\n";
$commit_msg = "Feat: Implement Smart QR Gateway & GPS location tracking";
echo shell_exec('git commit -m "' . addslashes($commit_msg) . '" 2>&1') . "\n";

echo "Pushing to remote...\n";
echo shell_exec('git push 2>&1') . "\n";

echo "\n=== FTP DEPLOYMENT (INFINITYFREE VIA RAW TCP SOCKETS) ===\n";
$ftp_server = "ftpupload.net";
$ftp_user = "if0_41845541";
$ftp_pass = "echf4wFpBumXVu";

function read_response($socket) {
    $res = "";
    while ($line = fgets($socket, 512)) {
        $res .= $line;
        if (preg_match('/^[0-9]{3} /', $line)) {
            break;
        }
    }
    return $res;
}

function socket_ftp_upload($host, $user, $pass, $local_file, $remote_file) {
    echo "Uploading $local_file -> $remote_file...\n";
    $socket = @fsockopen($host, 21, $errno, $errstr, 15);
    if (!$socket) {
        echo "Error: fsockopen failed: $errstr ($errno)\n";
        return false;
    }
    
    $res = read_response($socket);
    
    fwrite($socket, "USER $user\r\n");
    $res = read_response($socket);
    
    fwrite($socket, "PASS $pass\r\n");
    $res = read_response($socket);
    if (strpos($res, '230') === false) {
        echo "  Login failed: $res\n";
        fclose($socket);
        return false;
    }
    
    fwrite($socket, "TYPE I\r\n");
    read_response($socket);
    
    fwrite($socket, "PASV\r\n");
    $res = read_response($socket);
    if (!preg_match('/227.*\((\d+),(\d+),(\d+),(\d+),(\d+),(\d+)\)/', $res, $matches)) {
        echo "  Failed to enter passive mode: $res\n";
        fclose($socket);
        return false;
    }
    
    $data_host = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
    $data_port = ($matches[5] << 8) + $matches[6];
    
    $data_socket = @fsockopen($data_host, $data_port, $errno, $errstr, 15);
    if (!$data_socket) {
        echo "  Failed to open data socket: $errstr ($errno)\n";
        fclose($socket);
        return false;
    }
    
    fwrite($socket, "STOR $remote_file\r\n");
    $res = read_response($socket);
    if (strpos($res, '150') === false && strpos($res, '125') === false) {
        echo "  STOR command failed: $res\n";
        fclose($data_socket);
        fclose($socket);
        return false;
    }
    
    $content = file_get_contents($local_file);
    fwrite($data_socket, $content);
    fclose($data_socket);
    
    $res = read_response($socket);
    
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    if (strpos($res, '226') !== false) {
        echo "  Successfully uploaded!\n";
        return true;
    } else {
        echo "  Upload failed on completion: $res\n";
        return false;
    }
}

// Files to upload
$files = [
    'api-toko/config.php' => 'htdocs/api-toko/config.php',
    'api-toko/get_barang.php' => 'htdocs/api-toko/get_barang.php',
    'api-toko/tambah_barang.php' => 'htdocs/api-toko/tambah_barang.php',
    'api-toko/edit_barang.php' => 'htdocs/api-toko/edit_barang.php',
    'api-toko/barang.php' => 'htdocs/api-toko/barang.php',
    'app-toko/index.html' => 'htdocs/app-toko/index.html',
    'app-toko/style.css' => 'htdocs/app-toko/style.css',
    'app-toko/app.js' => 'htdocs/app-toko/app.js'
];

$success_count = 0;
foreach ($files as $local => $remote) {
    if (socket_ftp_upload($ftp_server, $ftp_user, $ftp_pass, $local, $remote)) {
        $success_count++;
    }
}

echo "\nFTP Upload finished. Successfully uploaded $success_count of " . count($files) . " files.\n";
?>
