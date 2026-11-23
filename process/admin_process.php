<?php
// process/admin_process.php — Aksi Admin: Produk, Manik, Charm, Pesanan, Pelanggan & Ekspor Laporan
require_once __DIR__ . '/../config/koneksi.php';

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ' . baseUrl('pages/login.php'));
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// 1. Ekspor Laporan CSV (GET)
if ($action === 'export_csv') {
    $dari   = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';
    $sql    = "SELECT kode_invoice, created_at, nama_penerima, email_penerima, no_hp, metode_pembayaran, status_pembayaran, status_pesanan, subtotal, ongkir, total_bayar FROM orders WHERE 1=1";
    $params = [];
    if ($dari) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $dari;
    }
    if ($sampai) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $sampai;
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-lydi-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['Invoice', 'Tanggal', 'Pelanggan', 'Email', 'No HP', 'Metode', 'Status Pembayaran', 'Status Pesanan', 'Subtotal', 'Ongkir', 'Total Bayar']);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

// 2. Kelola Pesanan (Tambah, Perbarui Detail & Status, Hapus)
if ($action === 'add_order') {
    $userId    = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $nama      = trim($_POST['nama_penerima'] ?? '');
    $email     = strtolower(trim($_POST['email_penerima'] ?? ''));
    $noHp      = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat_lengkap'] ?? '');
    $metode    = $_POST['metode_pembayaran'] ?? 'qris';
    $statusPay = $_POST['status_pembayaran'] ?? 'Menunggu Pembayaran';
    $statusOrd = $_POST['status_pesanan'] ?? 'Pending';
    $ongkir    = max(0, (int)($_POST['ongkir'] ?? 12000));

    $accId     = trim($_POST['accessory_id'] ?? 'gelang');
    $ukuran    = trim($_POST['ukuran'] ?? 'S (15.5 cm)');
    $matId     = trim($_POST['material_id'] ?? 'rose_gold_chain');
    $beadId    = !empty($_POST['bead_id']) ? (int)$_POST['bead_id'] : 0;
    $jmlManik  = max(0, (int)($_POST['jumlah_manik'] ?? 8));
    $ukiran    = trim($_POST['ukiran_nama'] ?? '');
    $inisial   = strtoupper(trim($_POST['inisial'] ?? ''));
    $charmId   = !empty($_POST['charm_id']) ? (int)$_POST['charm_id'] : null;
    $qty       = max(1, (int)($_POST['qty'] ?? 1));

    if ($nama && $email && $noHp && $alamat) {
        $accStmt = $pdo->prepare("SELECT harga_dasar FROM accessories WHERE id = ?");
        $accStmt->execute([$accId]);
        $hargaDasar = (int)($accStmt->fetchColumn() ?: 35000);

        $matStmt = $pdo->prepare("SELECT harga_tambahan FROM materials WHERE id = ?");
        $matStmt->execute([$matId]);
        $hargaBahan = (int)($matStmt->fetchColumn() ?: 0);

        $hargaManik = 0;
        if ($beadId && $jmlManik > 0) {
            $bStmt = $pdo->prepare("SELECT harga_per_butir FROM beads WHERE id = ?");
            $bStmt->execute([$beadId]);
            $hargaPerButir = (int)($bStmt->fetchColumn() ?: 5000);
            $hargaManik = $hargaPerButir * $jmlManik;
        }

        $hargaCharm = 0;
        if ($charmId) {
            $cStmt = $pdo->prepare("SELECT harga FROM charms WHERE id = ?");
            $cStmt->execute([$charmId]);
            $hargaCharm = (int)($cStmt->fetchColumn() ?: 0);
        }

        $hargaUkiran  = $ukiran !== '' ? 15000 : 0;
        $hargaInisial = strlen($inisial) * 5000;
        $hargaSatuan  = $hargaDasar + $hargaBahan + $hargaManik + $hargaUkiran + $hargaInisial + $hargaCharm;
        $subtotal     = $hargaSatuan * $qty;
        $totalBayar   = $subtotal + $ongkir;
        $invoice      = 'LYDI-' . date('Y') . '-' . rand(1000, 9999);

        $ins = $pdo->prepare("INSERT INTO orders (kode_invoice, user_id, nama_penerima, email_penerima, no_hp, alamat_lengkap, metode_pembayaran, status_pembayaran, status_pesanan, subtotal, ongkir, total_bayar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$invoice, $userId, $nama, $email, $noHp, $alamat, $metode, $statusPay, $statusOrd, $subtotal, $ongkir, $totalBayar]);
        $newOrderId = (int)$pdo->lastInsertId();

        $itemIns = $pdo->prepare("INSERT INTO order_items (order_id, accessory_id, ukuran, material_id, jumlah_manik, ukiran_nama, inisial, charm_id, harga_satuan, qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $itemIns->execute([$newOrderId, $accId, $ukuran, $matId, $jmlManik, $ukiran, $inisial, $charmId, $hargaSatuan, $qty]);
        $newItemId = (int)$pdo->lastInsertId();

        if ($beadId && $jmlManik > 0) {
            $bItemIns = $pdo->prepare("INSERT INTO order_item_beads (order_item_id, bead_id, urutan) VALUES (?, ?, ?)");
            for ($i = 1; $i <= $jmlManik; $i++) {
                $bItemIns->execute([$newItemId, $beadId, $i]);
            }
        }
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pesanan'));
    exit;
}

if ($action === 'update_order') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $status    = $_POST['status_pesanan'] ?? 'Diproses';
    $statusPay = $_POST['status_pembayaran'] ?? 'Dibayar';

    $curStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $curStmt->execute([$orderId]);
    $cur = $curStmt->fetch();

    if ($cur) {
        $nama   = trim($_POST['nama_penerima'] ?? $cur['nama_penerima']);
        $email  = strtolower(trim($_POST['email_penerima'] ?? $cur['email_penerima']));
        $noHp   = trim($_POST['no_hp'] ?? $cur['no_hp']);
        $alamat = trim($_POST['alamat_lengkap'] ?? $cur['alamat_lengkap']);
        $metode = $_POST['metode_pembayaran'] ?? $cur['metode_pembayaran'];
        $ongkir = isset($_POST['ongkir']) ? max(0, (int)$_POST['ongkir']) : (int)$cur['ongkir'];
        $total  = (int)$cur['subtotal'] + $ongkir;

        $stmt = $pdo->prepare("UPDATE orders SET nama_penerima = ?, email_penerima = ?, no_hp = ?, alamat_lengkap = ?, metode_pembayaran = ?, status_pesanan = ?, status_pembayaran = ?, ongkir = ?, total_bayar = ? WHERE id = ?");
        $stmt->execute([$nama, $email, $noHp, $alamat, $metode, $status, $statusPay, $ongkir, $total, $orderId]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pesanan'));
    exit;
}

if ($action === 'delete_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId) {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pesanan'));
    exit;
}

// 3. Kelola Produk Utama (Accessories)
if ($action === 'update_accessory') {
    $accId      = trim($_POST['accessory_id'] ?? '');
    $nama       = trim($_POST['nama'] ?? '');
    $hargaDasar = max(5000, (int)($_POST['harga_dasar'] ?? 35000));
    $maksManik  = max(4, (int)($_POST['maks_manik'] ?? 20));
    if ($accId && $nama) {
        $stmt = $pdo->prepare("UPDATE accessories SET nama = ?, harga_dasar = ?, maks_manik = ? WHERE id = ?");
        $stmt->execute([$nama, $hargaDasar, $maksManik, $accId]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=produk'));
    exit;
}

// 4. Kelola Manik (Tambah, Edit Harga/Stok, Hapus)
if ($action === 'add_bead') {
    $nama     = trim($_POST['nama'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Batu Alam';
    $hex      = $_POST['warna_hex'] ?? '#EBBAC5';
    $harga    = max(500, (int)($_POST['harga_per_butir'] ?? 4500));
    $stok     = max(0, (int)($_POST['stok'] ?? 100));
    if ($nama) {
        $stmt = $pdo->prepare("INSERT INTO beads (nama, kategori, warna_hex, harga_per_butir, stok) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $kategori, $hex, $harga, $stok]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=produk'));
    exit;
}

if ($action === 'update_bead') {
    $beadId = (int)($_POST['bead_id'] ?? 0);
    $harga  = max(500, (int)($_POST['harga_per_butir'] ?? 4500));
    $stok   = max(0, (int)($_POST['stok'] ?? 0));
    $stmt = $pdo->prepare("UPDATE beads SET harga_per_butir = ?, stok = ? WHERE id = ?");
    $stmt->execute([$harga, $stok, $beadId]);
    header('Location: ' . baseUrl('pages/admin.php?tab=produk'));
    exit;
}

if ($action === 'delete_bead') {
    $beadId = (int)($_POST['bead_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM beads WHERE id = ?");
    $stmt->execute([$beadId]);
    header('Location: ' . baseUrl('pages/admin.php?tab=produk'));
    exit;
}

// 5. Kelola Charm
if ($action === 'add_charm') {
    $nama   = trim($_POST['nama'] ?? '');
    $simbol = $_POST['simbol'] ?? 'bow';
    $bahan  = trim($_POST['bahan'] ?? 'Rose Gold 18K Plated');
    $harga  = max(1000, (int)($_POST['harga'] ?? 16000));
    $stok   = max(0, (int)($_POST['stok'] ?? 50));
    if ($nama) {
        $stmt = $pdo->prepare("INSERT INTO charms (nama, simbol, bahan, harga, stok) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $simbol, $bahan, $harga, $stok]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=produk'));
    exit;
}

// 6. Kelola Pelanggan (Tambah, Ubah Data & Sandi, Hapus)
if ($action === 'add_customer') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $noHp  = trim($_POST['no_hp'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $role  = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';

    if ($nama && $email && strlen($pass) >= 6) {
        $cek = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
        $cek->execute([$email]);
        if (!$cek->fetch()) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (nama, email, password_hash, no_hp, role) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$nama, $email, $hash, $noHp, $role]);
        }
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pelanggan'));
    exit;
}

if ($action === 'update_customer') {
    $custId  = (int)($_POST['user_id'] ?? 0);
    $nama    = trim($_POST['nama'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $noHp    = trim($_POST['no_hp'] ?? '');
    $role    = ($_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
    $newPass = trim((string)($_POST['password_baru'] ?? ''));

    if ($custId && $nama) {
        if ($custId === (int)$_SESSION['user']['id']) {
            $role = 'admin';
        }
        if ($email) {
            $cek = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id <> ?");
            $cek->execute([$email, $custId]);
            if ($cek->fetch()) {
                $email = '';
            }
        }
        if ($email) {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, no_hp = ?, role = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $noHp, $role, $custId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, no_hp = ?, role = ? WHERE id = ?");
            $stmt->execute([$nama, $noHp, $role, $custId]);
        }
        if (strlen($newPass) >= 6) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $pStmt->execute([$hash, $custId]);
        }
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pelanggan'));
    exit;
}

if ($action === 'delete_customer') {
    $custId = (int)($_POST['user_id'] ?? 0);
    if ($custId && $custId !== (int)$_SESSION['user']['id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$custId]);
    }
    header('Location: ' . baseUrl('pages/admin.php?tab=pelanggan'));
    exit;
}

header('Location: ' . baseUrl('pages/admin.php'));
exit;
?>
