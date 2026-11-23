<?php
// pages/detail_invoice.php — Rincian Lengkap Pesanan & Invoice LYDI
require_once __DIR__ . '/../includes/header.php';

$inv = trim($_GET['inv'] ?? '');
$order = null;
$errorMsg = '';

function diAmbil(array $row, array $keys, string $default = ''): string {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && (string)$row[$k] !== '') {
            return (string)$row[$k];
        }
    }
    return $default;
}

function diAngka(array $row, array $keys, int $default = 0): int {
    foreach ($keys as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '' && is_numeric($row[$k])) {
            return (int)$row[$k];
        }
    }
    return $default;
}

if ($inv === '') {
    $errorMsg = 'Kode invoice belum disertakan.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE kode_invoice = ? LIMIT 1");
    $stmt->execute([$inv]);
    $order = $stmt->fetch() ?: null;

    if (!$order) {
        $errorMsg = 'Data invoice dengan kode "' . $inv . '" tidak ditemukan.';
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
  <section class="container" style="max-width:860px; display:flex; flex-direction:column; gap:18px;">

    <!-- Tombol Navigasi & Cetak -->
    <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= baseUrl('pages/track_order.php' . ($inv !== '' ? '?inv=' . urlencode($inv) : '')) ?>" class="btn btn-secondary" style="text-decoration:none;">
          ← Kembali ke Lacak Pesanan
        </a>
        <?php if ($order): ?>
          <a href="<?= baseUrl('pages/invoice.php?inv=' . urlencode($order['kode_invoice'])) ?>" class="btn btn-secondary" style="text-decoration:none;">
            Format Ringkas Invoice
          </a>
        <?php endif; ?>
      </div>
      <?php if ($order): ?>
        <button type="button" onclick="window.print()" class="btn btn-primary">Cetak Detail Invoice</button>
      <?php endif; ?>
    </div>

    <?php if ($errorMsg !== ''): ?>
      <div class="card" style="text-align:center; padding:32px;">
        <h2 style="font-size:1.4rem; margin:0 0 8px;">Detail Invoice Tidak Tersedia</h2>
        <p style="color:#b3363f; font-size:0.88rem; margin:0 0 18px;"><?= htmlspecialchars($errorMsg) ?></p>
        <a href="<?= baseUrl('pages/track_order.php') ?>" class="btn btn-primary" style="text-decoration:none;">Cari Invoice</a>
      </div>
    <?php else: ?>
      <?php
      $kodeInv        = diAmbil($order, ['kode_invoice'], $inv);
      $tglDibuat      = diAmbil($order, ['created_at', 'tanggal_pesan'], '-');
      $statusPesanan  = diAmbil($order, ['status_pesanan'], 'Pending');
      $statusBayar    = diAmbil($order, ['status_pembayaran'], '-');
      $metodeBayar    = diAmbil($order, ['metode_pembayaran'], '-');
      $namaPenerima   = diAmbil($order, ['nama_penerima', 'nama_pemesan'], '-');
      $emailPenerima  = diAmbil($order, ['email_penerima', 'email'], '-');
      $noHp           = diAmbil($order, ['no_hp', 'telepon', 'no_telp'], '-');
      $alamatKirim    = diAmbil($order, ['alamat_pengiriman', 'alamat', 'alamat_penerima'], '-');
      $catatanOrder   = diAmbil($order, ['catatan', 'catatan_custom', 'ukiran', 'teks_ukiran'], '');

      $namaProduk     = diAmbil($order, ['nama_produk', 'produk_nama', 'jenis_produk', 'aksesoris_nama'], 'Aksesoris Kustom');
      $ukuran         = diAmbil($order, ['ukuran_cm', 'ukuran', 'size'], '-');
      $namaBahan      = diAmbil($order, ['nama_bahan', 'bahan_nama', 'bahan', 'material_nama'], '-');
      $ringkasanItem  = diAmbil($order, ['detail_item', 'ringkasan_item', 'deskripsi_item', 'manik_json', 'custom_json'], '');

      $hargaDasar     = diAngka($order, ['harga_dasar', 'subtotal', 'total_item'], 0);
      $ongkir         = diAngka($order, ['ongkir', 'biaya_kirim', 'biaya_pengiriman'], 0);
      $diskon         = diAngka($order, ['diskon', 'potongan'], 0);
      $totalBayar     = diAngka($order, ['total_bayar', 'total_harga', 'grand_total'], 0);
      if ($hargaDasar === 0 && $totalBayar > 0) {
          $hargaDasar = max(0, $totalBayar - $ongkir + $diskon);
      }
      ?>

      <div class="card" style="padding:28px;">
        <!-- Kop Invoice -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; padding-bottom:18px; border-bottom:1px solid #E5CFD5;">
          <div>
            <p style="font-family:'Cormorant Garamond',serif; font-style:italic; font-weight:600; color:var(--accent-rose); font-size:1rem; margin:0 0 2px;">
              Atelier LYDI · Rincian Pesanan Resmi
            </p>
            <h2 style="font-size:1.75rem; margin:0;">Detail Invoice</h2>
            <div style="font-size:0.8rem; color:var(--ink-secondary); margin-top:4px;">
              Tanggal Pesanan: <span class="mono"><?= htmlspecialchars($tglDibuat) ?></span>
            </div>
          </div>

          <div style="text-align:right;">
            <div style="font-size:0.75rem; color:var(--ink-secondary);">Kode Invoice</div>
            <div class="mono" style="font-size:1.25rem; font-weight:700; color:var(--accent-rose);"><?= htmlspecialchars($kodeInv) ?></div>
            <div style="margin-top:6px; display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap;">
              <span style="padding:3px 10px; border-radius:999px; font-size:0.74rem; font-weight:600; background:#FAF0F2; color:var(--accent-rose); border:1px solid #E5CFD5;">
                <?= htmlspecialchars($statusPesanan) ?>
              </span>
              <span style="padding:3px 10px; border-radius:999px; font-size:0.74rem; font-weight:600; background:#FFFDFB; color:var(--ink-secondary); border:1px solid #E5CFD5;">
                Bayar: <?= htmlspecialchars($statusBayar) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Informasi Penerima & Pembayaran -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; margin:18px 0;">
          <div style="padding:14px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB;">
            <div style="font-size:0.74rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:var(--accent-rose); margin-bottom:8px;">
              Data Penerima &amp; Pengiriman
            </div>
            <div style="font-size:0.88rem;"><strong><?= htmlspecialchars($namaPenerima) ?></strong></div>
            <div style="font-size:0.8rem; color:var(--ink-secondary); margin-top:3px;"><?= htmlspecialchars($emailPenerima) ?></div>
            <div class="mono" style="font-size:0.8rem; color:var(--ink-secondary); margin-top:2px;"><?= htmlspecialchars($noHp) ?></div>
            <div style="font-size:0.8rem; color:var(--ink-secondary); margin-top:6px; line-height:1.45;">
              <?= nl2br(htmlspecialchars($alamatKirim)) ?>
            </div>
          </div>

          <div style="padding:14px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB;">
            <div style="font-size:0.74rem; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:var(--accent-rose); margin-bottom:8px;">
              Status &amp; Metode Pembayaran
            </div>
            <div style="font-size:0.84rem; margin-bottom:4px;">Metode: <strong><?= htmlspecialchars($metodeBayar) ?></strong></div>
            <div style="font-size:0.84rem; margin-bottom:4px;">Status Bayar: <strong><?= htmlspecialchars($statusBayar) ?></strong></div>
            <div style="font-size:0.84rem;">Status Produksi: <strong><?= htmlspecialchars($statusPesanan) ?></strong></div>
          </div>
        </div>

        <!-- Tabel Rincian Item -->
        <div style="overflow-x:auto; margin-top:8px;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Produk / Aksesoris</th>
                <th>Ukuran</th>
                <th>Bahan</th>
                <th style="text-align:right;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($namaProduk) ?></strong>
                  <?php if ($ringkasanItem !== ''): ?>
                    <div style="font-size:0.76rem; color:var(--ink-secondary); margin-top:4px; word-break:break-word;">
                      <?= htmlspecialchars($ringkasanItem) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="mono"><?= htmlspecialchars($ukuran) ?></td>
                <td><?= htmlspecialchars($namaBahan) ?></td>
                <td class="mono" style="text-align:right;"><strong><?= formatRupiah($hargaDasar) ?></strong></td>
              </tr>
            </tbody>
          </table>
        </div>

        <?php if ($catatanOrder !== ''): ?>
          <div style="margin-top:14px; padding:12px 14px; border-radius:12px; background:#FAF0F2; border:1px solid #E5CFD5; font-size:0.82rem;">
            <strong>Catatan / Ukiran Kustom:</strong> <?= htmlspecialchars($catatanOrder) ?>
          </div>
        <?php endif; ?>

        <!-- Ringkasan Pembayaran -->
        <div style="margin-top:18px; padding-top:14px; border-top:1px solid #E5CFD5; display:flex; flex-direction:column; align-items:flex-end; gap:6px; font-size:0.86rem;">
          <div style="display:flex; justify-content:space-between; width:100%; max-width:300px;">
            <span style="color:var(--ink-secondary);">Subtotal Item</span>
            <span class="mono"><?= formatRupiah($hargaDasar) ?></span>
          </div>
          <?php if ($ongkir > 0): ?>
            <div style="display:flex; justify-content:space-between; width:100%; max-width:300px;">
              <span style="color:var(--ink-secondary);">Ongkos Kirim</span>
              <span class="mono"><?= formatRupiah($ongkir) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($diskon > 0): ?>
            <div style="display:flex; justify-content:space-between; width:100%; max-width:300px;">
              <span style="color:var(--ink-secondary);">Diskon</span>
              <span class="mono">-<?= formatRupiah($diskon) ?></span>
            </div>
          <?php endif; ?>
          <div style="display:flex; justify-content:space-between; width:100%; max-width:300px; padding-top:8px; margin-top:4px; border-top:1px dashed #E5CFD5; font-size:1.05rem;">
            <strong>Total Bayar</strong>
            <strong class="mono" style="color:var(--accent-rose);"><?= formatRupiah($totalBayar) ?></strong>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>