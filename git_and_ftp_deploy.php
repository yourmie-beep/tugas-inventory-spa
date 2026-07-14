<?php
header('Content-Type: text/plain');

echo "=== GIT DEPLOYMENT ===\n";
echo "Running git status...\n";
echo shell_exec('git status 2>&1') . "\n";

echo "Adding files...\n";
echo shell_exec('git add . 2>&1') . "\n";

echo "Committing files...\n";
$commit_msg = "Feat: Implement Smart QR Gateway & GPS location tracking";
echo shell_exec('git commit -m "' . addslashes($commit_msg) . '" 2>&1') . "\n";

echo "Pushing to remote...\n";
echo shell_exec('git push 2>&1') . "\n";

echo "\n=== FTP DEPLOYMENT (INFINITYFREE) ===\n";
$ftp_server = "ftpupload.net";
$ftp_user = "if0_41845541";
$ftp_pass = "echf4wFpBumXVu";

$conn_id = ftp_connect($ftp_server);
if (!$conn_id) {
    die("Could not connect to $ftp_server\n");
}
echo "Connected to $ftp_server\n";

$login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);
if (!$login_result) {
    die("FTP login failed for $ftp_user\n");
}
echo "Logged in successfully to FTP\n";

// Set passive mode
ftp_pasv($conn_id, true);

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

foreach ($files as $local => $remote) {
    echo "Uploading $local to $remote...\n";
    if (ftp_put($conn_id, $remote, $local, FTP_BINARY)) {
        echo "Successfully uploaded $local\n";
    } else {
        echo "Error uploading $local\n";
    }
}

ftp_close($conn_id);
echo "\nDeployment finished!\n";
?>
