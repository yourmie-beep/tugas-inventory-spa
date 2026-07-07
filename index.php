<?php
// Redirect to the application folder
header("Location: app-toko/");
exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0; url=app-toko/">
    <title>Redirecting...</title>
    <script>
        window.location.href = "app-toko/";
    </script>
</head>
<body>
    If you are not redirected, <a href="app-toko/">click here to go to StockPro</a>.
</body>
</html>