<?php
// pages/track_order.php — Halaman Lacak & Riwayat Pesanan LYDI
require_once __DIR__ . '/../includes/header.php';

$cariInv   = trim($_GET['inv'] ?? '');
$hasilCari = null;
$galatCari = '';

// Helper aman untuk mengambil nilai kolom yang mungkin memiliki beberapa nama alias
function ambilKolomOrder(array $row, array $keys, string $default = '-'): string {
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== '') {
            return (string)$row[$k];
        }
    }
    return $default;
}

// 1. Jika pengguna mencari berdasarkan Kode Invoice
if ($cariInv !== '') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE kode_invoice = ? LIMIT 1");
    $stmt->execute([$cariInv]);
    $hasilCari = $stmt->fetch() ?: null;

    if (!$hasilCari) {
        $galatCari = 'Kode invoice "' . $cariInv . '" tidak ditemukan. Periksa kembali kode pesanan Anda.';
    }
}

// 2. Jika pengguna sudah login, ambil semua pesanan miliknya otomatis
$riwayatSaya = [];
if (!empty($currentUser)) {
    $stmtUser = $pdo->prepare("
        SELECT * FROM orders
        WHERE user_id = ? OR LOWER(email_penerima) = LOWER(?)
        ORDER BY created_at DESC
    ");
    $stmtUser->execute([(int)$currentUser['id'], $currentUser['email'] ?? '']);
    $riwayatSaya = $stmtUser->fetchAll();
}

// Helper untuk tahapan progres status pesanan
function tahapStatus(string $status): int {
    return match ($status) {
        'Pending'  => 1,
        'Diproses' => 2,
        'Dikirim'  => 3,
        'Selesai'  => 4,
        default    => 0, // Dibatalkan
    };
}

// Helper format ringkasan item pesanan agar tidak error jika kolom berbeda
function ringkasanItemOrder(array $ord): string {
    $produk = ambilKolomOrder($ord, ['nama_produk', 'produk_nama', 'jenis_produk', 'aksesoris_nama', 'item_nama'], 'Aksesoris Kustom');
    $ukuran = ambilKolomOrder($ord, ['ukuran_cm', 'ukuran', 'size'], '');
    $bahan  = ambilKolomOrder($ord, ['nama_bahan', 'bahan_nama', 'bahan', 'material_nama'], '');

    $detail = [];
    if ($ukuran !== '') {
        $detail[] = (str_contains(strtolower($ukuran), 'cm') || str_contains(strtolower($ukuran), 'size'))
            ? $ukuran
            : $ukuran . ' cm';
    }
    if ($bahan !== '') {
        $detail[] = $bahan;
    }

    return $detail ? $produk . ' (' . implode(' · ', $detail) . ')' : $produk;
}
?>

<div style="flex:1; overflow-y:auto; padding:28px 0;">
  <section class="container" style="max-width:920px; display:flex; flex-direction:column; gap:20px;">

    <!-- Header & Pencarian Kode Invoice -->
    <div class="card">
      <p style="font-family:'Cormorant Garamond',serif; font-style:italic; font-weight:600; color:var(--accent-rose); font-size:0.95rem; margin-bottom:2px;">
        Atelier LYDI · Pelacakan Pesanan
      </p>
      <h2 style="font-size:1.7rem; margin:0 0 6px;">Lacak Pesanan Anda</h2>
      <p style="font-size:0.84rem; color:var(--ink-secondary); margin:0 0 16px;">
        Masukkan kode invoice untuk melihat status pembuatan dan pengiriman aksesoris kustom Anda.
      </p>

      <form method="GET" action="<?= baseUrl('pages/track_order.php') ?>" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input
          type="text"
          name="inv"
          value="<?= htmlspecialchars($cariInv) ?>"
          placeholder="Contoh: LYDI-2026-1162"
          required
          style="flex:1; min-width:220px;"
        >
        <button type="submit" class="btn btn-primary">Lacak Sekarang</button>
        <?php if ($cariInv !== ''): ?>
          <a href="<?= baseUrl('pages/track_order.php') ?>" class="btn btn-secondary" style="text-decoration:none;">Reset</a>
        <?php endif; ?>
      </form>

      <?php if ($galatCari): ?>
        <p style="color:#b3363f; font-size:0.84rem; margin:12px 0 0;"><?= htmlspecialchars($galatCari) ?></p>
      <?php endif; ?>
    </div>

    <!-- Hasil Pencarian Spesifik Invoice -->
    <?php if ($hasilCari): ?>
      <?php
      $statusPesanan = ambilKolomOrder($hasilCari, ['status_pesanan'], 'Pending');
      $statusBayar   = ambilKolomOrder($hasilCari, ['status_pembayaran'], '-');
      $namaPenerima  = ambilKolomOrder($hasilCari, ['nama_penerima', 'nama_pemesan'], '-');
      $tglOrder      = ambilKolomOrder($hasilCari, ['created_at'], '');
      $totalBayar    = (int)($hasilCari['total_bayar'] ?? 0);
      $step          = tahapStatus($statusPesanan);
      ?>
      <div class="card" style="border:1.5px solid var(--accent-rose); background:#FFFDFB;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
          <div>
            <span style="font-size:0.75rem; color:var(--ink-secondary);">Hasil Pelacakan Invoice</span>
            <h3 class="mono" style="font-size:1.25rem; margin:2px 0;"><?= htmlspecialchars($hasilCari['kode_invoice']) ?></h3>
            <div style="font-size:0.8rem; color:var(--ink-secondary);">
              Penerima: <strong><?= htmlspecialchars($namaPenerima) ?></strong>
              <?php if ($tglOrder !== ''): ?>
                · Tanggal: <span class="mono"><?= htmlspecialchars(substr($tglOrder, 0, 10)) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div style="text-align:right;">
            <span style="display:inline-block; padding:5px 12px; border-radius:999px; font-size:0.78rem; font-weight:600; background:#FAF0F2; color:var(--accent-rose); border:1px solid #E5CFD5;">
              <?= htmlspecialchars($statusPesanan) ?>
            </span>
            <div class="mono" style="font-size:1.05rem; font-weight:700; margin-top:6px;">
              <?= formatRupiah($totalBayar) ?>
            </div>
          </div>
        </div>

        <!-- Bar Progres 4 Tahap -->
        <?php if ($step > 0): ?>
          <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin:16px 0;">
            <?php
            $labels = [1 => '01. Pending', 2 => '02. Diproses', 3 => '03. Dikirim', 4 => '04. Selesai'];
            foreach ($labels as $num => $lbl):
              $aktif = $step >= $num;
            ?>
              <div style="padding:8px 10px; border-radius:10px; text-align:center; font-size:0.78rem; font-weight:600;
                          background:<?= $aktif ? 'var(--accent-rose)' : '#FAF0F2' ?>;
                          color:<?= $aktif ? '#FFFDFB' : 'var(--ink-secondary)' ?>;
                          border:1px solid #E5CFD5;">
                <?= $lbl ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="padding:10px 14px; border-radius:10px; background:#FDF2F2; color:#9E3F46; font-size:0.82rem; margin:12px 0;">
            Pesanan ini berstatus <strong>Dibatalkan</strong>.
          </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; padding-top:12px; border-top:1px solid #E5CFD5; font-size:0.84rem;">
          <div>
            <strong><?= htmlspecialchars(ringkasanItemOrder($hasilCari)) ?></strong>
            — Pembayaran: <strong><?= htmlspecialchars($statusBayar) ?></strong>
          </div>
          <a href="<?= baseUrl('pages/invoice.php?inv=' . urlencode($hasilCari['kode_invoice'])) ?>" class="btn btn-secondary" style="text-decoration:none; padding:6px 14px;">
            Lihat Detail Invoice
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Riwayat Pesanan Otomatis untuk Pengguna Login -->
    <?php if (!empty($currentUser)): ?>
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
          <div>
            <h3 style="font-size:1.3rem; margin:0;">Riwayat Pesanan Saya (<?= htmlspecialchars($currentUser['nama']) ?>)</h3>
            <p style="font-size:0.8rem; color:var(--ink-secondary); margin:3px 0 0;">Daftar seluruh aksesoris yang pernah Anda pesan.</p>
          </div>
          <span style="font-size:0.8rem; color:var(--ink-secondary);"><?= count($riwayatSaya) ?> pesanan</span>
        </div>

        <?php if (empty($riwayatSaya)): ?>
          <div style="text-align:center; padding:28px 16px; border:1px dashed #E5CFD5; border-radius:14px; background:#FAF0F2;">
            <p style="font-size:0.88rem; color:var(--ink-secondary); margin:0 0 12px;">
              Anda belum memiliki pesanan di Atelier LYDI.
            </p>
            <a href="<?= baseUrl('pages/kustomisasi.php') ?>" class="btn btn-primary" style="text-decoration:none;">
              Mulai Rancang di Studio Kustom
            </a>
          </div>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($riwayatSaya as $ord): ?>
              <?php
              $statusPesanan = ambilKolomOrder($ord, ['status_pesanan'], 'Pending');
              $statusBayar   = ambilKolomOrder($ord, ['status_pembayaran'], '-');
              $tglOrder      = ambilKolomOrder($ord, ['created_at'], '');
              $totalBayar    = (int)($ord['total_bayar'] ?? 0);
              ?>
              <div style="padding:14px 16px; border:1px solid #E5CFD5; border-radius:14px; background:#FFFDFB; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap;">
                <div style="min-width:220px; flex:1;">
                  <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <strong class="mono" style="font-size:0.92rem;"><?= htmlspecialchars($ord['kode_invoice']) ?></strong>
                    <span style="padding:2px 10px; border-radius:999px; font-size:0.72rem; font-weight:600; background:#FAF0F2; color:var(--accent-rose); border:1px solid #E5CFD5;">
                      <?= htmlspecialchars($statusPesanan) ?>
                    </span>
                    <span style="font-size:0.74rem; color:var(--ink-secondary);">
                      · Bayar: <?= htmlspecialchars($statusBayar) ?>
                    </span>
                  </div>
                  <div style="font-size:0.86rem; margin-top:6px;">
                    <strong><?= htmlspecialchars(ringkasanItemOrder($ord)) ?></strong>
                  </div>
                  <?php if ($tglOrder !== ''): ?>
                    <div class="mono" style="font-size:0.75rem; color:var(--ink-secondary); margin-top:3px;">
                      Dipesan pada <?= htmlspecialchars(substr($tglOrder, 0, 16)) ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div style="display:flex; align-items:center; gap:12px;">
                  <div class="mono" style="font-weight:700; font-size:0.95rem; color:var(--accent-rose);">
                    <?= formatRupiah($totalBayar) ?>
                  </div>
                  <a href="<?= baseUrl('pages/track_order.php?inv=' . urlencode($ord['kode_invoice'])) ?>" class="btn btn-secondary" style="text-decoration:none; padding:6px 12px;">
                    Lacak
                  </a>
                  <a href="<?= baseUrl('pages/invoice.php?inv=' . urlencode($ord['kode_invoice'])) ?>" class="btn btn-secondary" style="text-decoration:none; padding:6px 12px;">
                    Invoice
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>