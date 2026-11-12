<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "lydi_db";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
mysqli_set_charset($koneksi, "utf8mb4");

// Dipakai untuk link CSS/gambar/halaman. Nama folder proyek kalian berdua sama, jadi aman.
define('BASE_URL', '/kelompok-3-website-pemesanan-aksesoris-custom');
?>