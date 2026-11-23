<?php
// pages/invoice.php — Invoice Pesanan LYDI
require_once __DIR__ . '/../includes/header.php';

$inv = trim($_GET['inv'] ?? '');
$order = null;
$errorMsg = '';

function invAmbil(array $row, array $keys, string $default = '-'): string {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && (string)$row[$k] !== '') {
            return (string)$row[$k];
        }
    }
    return $default;
}

if ($inv === '') {
    $errorMsg = 'Kode invoice tidak ditemukan pada parameter URL.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE kode_invoice = ? LIMIT 1");
    $stmt->execute([$inv]);
    $order = $stmt->fetch() ?: null;

    if (!$order) {
        $errorMsg = 'Invoice dengan kode "' . $inv . '" tidak ditemukan.';
    }
}
?>

<style>
@media print {
  .no-print { display: none !important; }
  body { background: #fff !important; }
  .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<div style="flex:1; overflow-y:auto; padding:28px 0;">
  <section class="container" style="max-width:740px; display:flex; flex-direction:column; gap:16px;">

    <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <a href="<?= baseUrl('pages/track_order.php' . ($inv !== '' ? '?inv=' . urlencode($inv) : '')) ?>" class="btn btn-secondary" style="text-decoration:none;">
        ← Kembali
      </a>
      <?php if ($order): ?>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <a href="<?= baseUrl('pages/detail_invoice.php?inv=' . urlencode($order['kode_invoice'])) ?>" class="btn btn-secondary" style="text-decoration:none;">
            Lihat Detail Invoice
          </a>
          <button type="button" onclick="window.print()" class="btn btn-primary">Cetak Invoice</button>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($errorMsg !== ''): ?>
      <div class="card" style="text-align:center; padding:28px;">
        <h2 style="font-size:1.35rem; margin:0 0 8px;">Invoice Tidak Ditemukan</h2>
        <p style="color:#b3363f; font-size:0.86rem; margin:0 0 16px;"><?= htmlspecialchars($errorMsg) ?></p>
        <a href="<?= baseUrl('pages/track_order.php') ?>" class="btn btn-primary" style="text-decoration:none;">Kembali ke Lacak Pesanan</a>
      </div>
    <?php else: ?>
      <?php
      $kodeInv       = invAmbil($order, ['kode_invoice'], $inv);
      $tglOrder      = invAmbil($order, ['created_at', 'tanggal_pesan'], '-');
      $namaPenerima  = invAmbil($order, ['nama_penerima', 'nama_pemesan'], '-');
      $noHp          = invAmbil($order, ['no_hp', 'telepon', 'no_telp'], '-');
      $namaProduk    = invAmbil($order, ['nama_produk', 'produk_nama', 'jenis_produk', 'aksesoris_nama'], 'Aksesoris Kustom');
      $ukuran        = invAmbil($order, ['ukuran_cm', 'ukuran', 'size'], '-');
      $namaBahan     = invAmbil($order, ['nama_bahan', 'bahan_nama', 'bahan', 'material_nama'], '-');
      $statusBayar   = invAmbil($order, ['status_pembayaran'], '-');
      $statusPesanan = invAmbil($order, ['status_pesanan'], 'Pending');
      $totalBayar    = (int)($order['total_bayar'] ?? 0);
      ?>

      <div class="card" style="padding:24px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; padding-bottom:14px; border-bottom:1px solid #E5CFD5;">
          <div>
            <span style="font-family:'Cormorant Garamond',serif; font-style:italic; font-weight:600; color:var(--accent-rose);">Atelier LYDI</span>
            <h2 style="font-size:1.55rem; margin:2px 0 0;">Invoice Pesanan</h2>
          </div>
          <div style="text-align:right;">
            <div class="mono" style="font-weight:700; font-size:1.1rem; color:var(--accent-rose);"><?= htmlspecialchars($kodeInv) ?></div>
            <div class="mono" style="font-size:0.78rem; color:var(--ink-secondary);"><?= htmlspecialchars($tglOrder) ?></div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:14px 0; font-size:0.84rem;">
          <div>
            <div style="color:var(--ink-secondary); font-size:0.75rem;">Penerima</div>
            <strong><?= htmlspecialchars($namaPenerima) ?></strong>
            <div class="mono" style="font-size:0.78rem; color:var(--ink-secondary);"><?= htmlspecialchars($noHp) ?></div>
          </div>
          <div style="text-align:right;">
            <div style="color:var(--ink-secondary); font-size:0.75rem;">Status</div>
            <div><strong><?= htmlspecialchars($statusPesanan) ?></strong> · Bayar: <strong><?= htmlspecialchars($statusBayar) ?></strong></div>
          </div>
        </div>

        <div style="padding:14px; border-radius:12px; background:#FFFDFB; border:1px solid #E5CFD5; margin-top:10px; font-size:0.86rem;">
          <div><strong><?= htmlspecialchars($namaProduk) ?></strong></div>
          <div style="color:var(--ink-secondary); font-size:0.8rem; margin-top:3px;">
            Ukuran: <span class="mono"><?= htmlspecialchars($ukuran) ?></span> · Bahan: <?= htmlspecialchars($namaBahan) ?>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:14px; border-top:1px solid #E5CFD5;">
          <span style="font-size:0.9rem;">Total Tagihan</span>
          <strong class="mono" style="font-size:1.2rem; color:var(--accent-rose);"><?= formatRupiah($totalBayar) ?></strong>
        </div>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>