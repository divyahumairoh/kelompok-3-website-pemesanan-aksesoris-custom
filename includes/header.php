<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/koneksi.php';

$halaman = $halaman ?? '';
$jumlah_keranjang = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $judul ?? 'Lydi' ?> | Lydi Accessories</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
  <a class="logo" href="<?= BASE_URL ?>/index.php">lydi.</a>
  <nav class="menu">
    <a href="<?= BASE_URL ?>/index.php" class="<?= $halaman == 'home' ? 'aktif' : '' ?>">Home</a>
    <a href="<?= BASE_URL ?>/pages/katalog.php" class="<?= $halaman == 'shop' ? 'aktif' : '' ?>">Shop</a>
    <a href="<?= BASE_URL ?>/pages/kustomisasi.php" class="<?= $halaman == 'custom' ? 'aktif' : '' ?>">Custom</a>
    <a href="<?= BASE_URL ?>/pages/lacak.php" class="<?= $halaman == 'lacak' ? 'aktif' : '' ?>">Track Order</a>
  </nav>
  <div class="ikon">
    <a href="<?= BASE_URL ?>/pages/katalog.php">🔍</a>
    <a href="<?= BASE_URL ?>/pages/keranjang.php">🛒<?= $jumlah_keranjang > 0 ? " ($jumlah_keranjang)" : "" ?></a>
    <a href="<?= BASE_URL ?>/pages/<?= isset($_SESSION['user_id']) ? 'profil.php' : 'login.php' ?>">👤</a>
  </div>
</header>