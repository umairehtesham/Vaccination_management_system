<?php
// Generate password hash for admin
$password = "admin123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Copy this hashed password:</h2>";
echo "<p style='background: #f0f0f0; padding: 10px; word-break: break-all;'>";
echo $hashed_password;
echo "</p>";

echo "<hr>";
echo "<h3>Now run this SQL in phpMyAdmin:</h3>";
echo "<pre style='background: #f0f0f0; padding: 15px;'>";
echo "INSERT INTO users (username, password, role, email, phone) VALUES\n";
echo "('admin', '$hashed_password', 'admin', 'admin@vms.com', '1234567890');";
echo "</pre>";
?>