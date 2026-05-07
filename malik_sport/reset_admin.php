<?php
require_once 'config/koneksi.php';

$password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$password, 'admin@maliksport.com']);

echo "✅ Password admin direset!<br>";
echo "Email: admin@maliksport.com<br>";
echo "Password: admin123<br>";
echo '<a href="login.php">Login Sekarang</a>';
?>