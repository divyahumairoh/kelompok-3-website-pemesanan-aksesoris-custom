<?php
require_once __DIR__ . '/admin_auth.php';

$menu = [
    'dashboard' => ['🏠', 'Dashboard', 'dashboard.php'],
    'produk'    => ['📦', 'Produk',    'produk.php'],
    'pesanan'   => ['🛒', 'Pesanan',   'pesanan.php'],
    'pelanggan' => ['👥', 'Pelanggan', 'pelanggan.php'],
    'laporan'   => ['📈', 'Laporan',   'laporan.php'],
];
$menu_aktif  = $menu_aktif  ?? '';
$judul_admin = $judul_admin ?? 'Admin';
$sub_admin   = $sub_admin   ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($judul_admin) ?> | Lydi Admin</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tema.css">
</head>
<body>
<header class="admin-nav">
  <a class="brand" href="<?= BASE_URL ?>/admin/dashboard.php">lydi. <small>Admin</small></a>
  <nav class="admin-menu">
    <?php foreach ($menu as $kunci => [$ikon, $nama, $file]) { ?>
      <a href="<?= BASE_URL ?>/admin/<?= $file ?>" class="<?= $menu_aktif == $kunci ? 'aktif' : '' ?>"><?= $ikon ?> <?= $nama ?></a>
    <?php } ?>
  </nav>
  <details class="user-menu">
    <summary>
      <span class="avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></span>
      <?= htmlspecialchars($_SESSION['nama']) ?> ▾
    </summary>
    <div class="dropdown">
      <a href="<?= BASE_URL ?>/index.php">Lihat Toko</a>
      <a href="<?= BASE_URL ?>/process/logout.php">Logout</a>
    </div>
  </details>
</header>

<main class="admin-main">
  <div class="judul-admin">
    <h1><?= htmlspecialchars($judul_admin) ?> ✦</h1>
    <?php if ($sub_admin) { ?><p><?= htmlspecialchars($sub_admin) ?></p><?php } ?>
  </div>