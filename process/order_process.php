<?php
// process/order_process.php — Simpan Pesanan Custom & Relasi Manik ke MySQL (Tanpa JSON)
require_once __DIR__ . '/../config/koneksi.php';

$design = $_SESSION['pending_design'] ?? null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$design) {
    header('Location: ' . baseUrl('pages/kustomisasi.php'));
    exit;
}

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
    header('Location: ' . baseUrl('pages/checkout.php?auth_required=1'));
    exit;
}

$nama    = trim($_POST['nama_penerima'] ?? '');
$email   = trim($_POST['email_penerima'] ?? '');
$hp      = trim($_POST['no_hp'] ?? '');
$alamat  = trim($_POST['alamat_lengkap'] ?? '');
$metode  = $_POST['metode_pembayaran'] ?? 'qris';
$userId  = (int)$userId;

$subtotal = (int)($design['total_harga'] ?? 35000);
$ongkir   = $subtotal >= 250000 ? 0 : 12000;
$total    = $subtotal + $ongkir;
$invoice  = 'LYDI-' . date('Y') . '-' . random_int(1000, 9999);
$payStat  = $metode === 'cod' ? 'COD Terkonfirmasi' : 'Dibayar';
$ordStat  = 'Diproses';

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO orders (kode_invoice, user_id, nama_penerima, email_penerima, no_hp, alamat_lengkap, metode_pembayaran, status_pembayaran, status_pesanan, subtotal, ongkir, total_bayar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$invoice, $userId, $nama, $email, $hp, $alamat, $metode, $payStat, $ordStat, $subtotal, $ongkir, $total]);
    $orderId = $pdo->lastInsertId();

    $beadIds = is_array($design['bead_ids']) ? $design['bead_ids'] : [];
    $charmId = !empty($design['charm_id']) ? (int)$design['charm_id'] : null;

    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, accessory_id, ukuran, material_id, jumlah_manik, ukiran_nama, inisial, charm_id, harga_satuan, qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $itemStmt->execute([
        $orderId,
        $design['accessory_id'] ?? 'gelang',
        $design['ukuran'] ?? 'S (15.5 cm)',
        $design['material_id'] ?? 'rose_gold_chain',
        count($beadIds),
        $design['ukiran_nama'] ?? '',
        $design['inisial'] ?? '',
        $charmId,
        $subtotal,
    ]);
    $orderItemId = $pdo->lastInsertId();

    // Simpan setiap butir manik ke tabel relasional order_item_beads (tanpa JSON)
    $beadStmt = $pdo->prepare("INSERT INTO order_item_beads (order_item_id, bead_id, urutan) VALUES (?, ?, ?)");
    foreach ($beadIds as $urutan => $bId) {
        $beadStmt->execute([$orderItemId, (int)$bId, $urutan + 1]);
    }

    $pdo->commit();
    unset($_SESSION['pending_design']);
    header('Location: ' . baseUrl('pages/checkout.php?invoice=' . urlencode($invoice)));
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    die('Gagal memproses pesanan: ' . htmlspecialchars($e->getMessage()));
}
?>
