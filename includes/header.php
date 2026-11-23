
<?php
// includes/header.php
require_once __DIR__ . '/../config/koneksi.php';

$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LYDI — Studio Aksesoris Custom</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,500;1,600&family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS utama -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>

<body>

<header class="lydi-header">
    <div class="container header-inner">

        <a href="<?= baseUrl('index.php') ?>" class="brand-logo">
            LYDI
        </a>

        <nav class="nav-links">
            <a href="<?= baseUrl('index.php') ?>">Beranda</a>

            <a href="<?= baseUrl('pages/kustomisasi.php') ?>">
                Studio Kustom
            </a>
            <a href="<?= baseUrl('pages/track_order.php') ?>">
                Lacak Pesanan
            </a>
            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <a href="<?= baseUrl('pages/admin.php') ?>">Admin</a>
            <?php endif; ?>

            <?php if ($currentUser): ?>
                <a href="<?= baseUrl('process/auth_process.php?action=logout') ?>">
                    Keluar (<?= htmlspecialchars($currentUser['nama']) ?>)
                </a>
            <?php else: ?>
                <a href="<?= baseUrl('pages/login.php') ?>">Masuk</a>
            <?php endif; ?>
        </nav>

        <a href="<?= baseUrl('pages/checkout.php') ?>" class="btn btn-primary">
            Checkout
        </a>

    </div>
</header>

<main class="main-content">
