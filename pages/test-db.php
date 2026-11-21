<?php
$conn = new mysqli("localhost", "root", "", "lydi_db_baru");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "KONEKSI DATABASE BERHASIL!";
?>
