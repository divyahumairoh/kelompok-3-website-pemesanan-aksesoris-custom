<?php
// pages/checkout.php — Halaman Checkout & Pembayaran Terintegrasi (Tanpa JSON)
require_once __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accessory_id'])) {
    $_SESSION['pending_design'] = [
        'accessory_id' => $_POST['accessory_id'],
        'ukuran'       => $_POST['ukuran'] ?? 'S (15.5 cm)',
        'material_id'  => $_POST['material_id'] ?? 'rose_gold_chain',
        'bead_ids'     => $_POST['bead_ids'] ?? [],
        'ukiran_nama'  => $_POST['ukiran_nama'] ?? '',
        'inisial'      => $_POST['inisial'] ?? '',
        'charm_id'     => $_POST['charm_id'] ?? '',
        'total_harga'  => (int)($_POST['total_harga'] ?? 35000),
    ];
}
$design = $_SESSION['pending_design'] ?? null;
$invoice = $_GET['invoice'] ?? '';
$authError = $_GET['error'] ?? '';
$authRequired = !empty($_GET['auth_required']);
?>
<section class="container" style="max-width:780px; padding:40px 20px;">
  <?php if ($invoice): ?>
    <div class="card">
      <h2>Pesanan Berhasil Dibuat</h2>
      <p style="margin-top:8px; color:var(--ink-secondary);">
        Nomor Invoice: <strong class="mono"><?= htmlspecialchars($invoice) ?></strong>
      </p>
      <p style="margin-top:12px; font-size:0.9rem;">
        Terima kasih telah merancang aksesoris custom di LYDI. Akun dan pesanan Anda telah tersimpan ke tabel relasional MySQL dan langsung terdeteksi di panel Admin.
      </p>
      <a href="<?= baseUrl('pages/kustomisasi.php') ?>" class="btn btn-primary" style="margin-top:18px;">Kembali ke Studio</a>
    </div>
  <?php elseif (!$design): ?>
    <div class="card" style="text-align:center;">
      <h2>Belum Ada Desain untuk Dibayar</h2>
      <p style="margin:10px 0 18px; color:var(--ink-secondary);">Rancang aksesoris custom Anda terlebih dahulu di Studio Kustom.</p>
      <a href="<?= baseUrl('pages/kustomisasi.php') ?>" class="btn btn-primary">Buka Studio Kustom</a>
    </div>
  <?php elseif (!$currentUser): ?>
    <div class="card" style="display:flex; flex-direction:column; gap:18px;">
      <div>
        <p style="font-family:'Cormorant Garamond',serif; font-style:italic; font-weight:600; color:var(--accent-rose); font-size:0.95rem;">
          Wajib Daftar Akun Pelanggan Sebelum Memesan
        </p>
        <h2 style="margin-top:2px;">Daftarkan Diri untuk Melanjutkan Pemesanan</h2>
        <p style="font-size:0.86rem; color:var(--ink-secondary); margin-top:4px;">
          Pelanggan wajib mendaftar atau masuk akun terlebih dahulu agar identitas akun dan detail pesanan Anda terdeteksi otomatis di Admin LYDI.
        </p>
      </div>

      <div style="padding:14px; border-radius:10px; background:var(--bg-muted); font-size:0.86rem;">
        <div><strong>Produk Dipilih:</strong> <?= htmlspecialchars(strtoupper($design['accessory_id'])) ?> · Ukuran <?= htmlspecialchars($design['ukuran']) ?></div>
        <div><strong>Jumlah Manik:</strong> <?= count($design['bead_ids']) ?> butir · <strong>Total Produk:</strong> <span class="mono"><?= formatRupiah($design['total_harga']) ?></span></div>
      </div>

      <?php if ($authRequired || $authError): ?>
        <div style="padding:10px 12px; border-radius:8px; background:#FEF2F2; color:#991B1B; font-size:0.84rem;">
          <?= htmlspecialchars($authError ?: 'Silakan daftar atau masuk ke akun pelanggan terlebih dahulu sebelum membuat pesanan.') ?>
        </div>
      <?php endif; ?>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
        <form action="<?= baseUrl('process/auth_process.php') ?>" method="POST" style="padding:16px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB; display:flex; flex-direction:column; gap:10px;">
          <input type="hidden" name="action" value="register">
          <input type="hidden" name="redirect" value="checkout">
          <h3 style="font-size:1.15rem; margin-bottom:2px;">Daftar Pelanggan Baru</h3>
          <input type="text" name="nama" required placeholder="Nama Lengkap">
          <input type="text" name="no_hp" required placeholder="Nomor WhatsApp Aktif">
          <input type="email" name="email" required placeholder="Email (contoh@email.com)">
          <input type="password" name="password" required minlength="6" placeholder="Kata Sandi (min. 6 karakter)">
          <button type="submit" class="btn btn-primary" style="width:100%;">Daftar &amp; Lanjut Memesan</button>
        </form>

        <form action="<?= baseUrl('process/auth_process.php') ?>" method="POST" style="padding:16px; border:1px solid #E5CFD5; border-radius:12px; background:#FCF8F4; display:flex; flex-direction:column; gap:10px;">
          <input type="hidden" name="action" value="login">
          <input type="hidden" name="redirect" value="checkout">
          <h3 style="font-size:1.15rem; margin-bottom:2px;">Sudah Punya Akun? Masuk</h3>
          <input type="email" name="email" required placeholder="Email Terdaftar">
          <input type="password" name="password" required placeholder="Kata Sandi">
          <button type="submit" class="btn btn-secondary" style="width:100%; margin-top:auto;">Masuk &amp; Lanjut Memesan</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div style="padding:10px 14px; border-radius:10px; background:#EDF2EC; border:1px solid #CFE0CF; color:#465C49; font-size:0.84rem; margin-bottom:14px;">
        Akun Terhubung: <strong><?= htmlspecialchars($currentUser['nama']) ?></strong> (<?= htmlspecialchars($currentUser['email']) ?>)
      </div>
      <h2 style="margin-bottom:16px;">Konfirmasi Pengiriman &amp; Pembayaran</h2>
      <div style="padding:14px; border-radius:10px; background:var(--bg-muted); margin-bottom:18px; font-size:0.88rem;">
        <div><strong>Produk:</strong> <?= htmlspecialchars(strtoupper($design['accessory_id'])) ?> · Ukuran <?= htmlspecialchars($design['ukuran']) ?></div>
        <div><strong>Jumlah Manik:</strong> <?= count($design['bead_ids']) ?> butir</div>
        <div><strong>Ukiran / Inisial:</strong> <?= htmlspecialchars(($design['ukiran_nama'] ?: '-') . ' / ' . ($design['inisial'] ?: '-')) ?></div>
        <div style="margin-top:6px;"><strong>Total Produk:</strong> <span class="mono"><?= formatRupiah($design['total_harga']) ?></span> (+ Ongkir Rp 12.000)</div>
      </div>

      <form action="<?= baseUrl('process/order_process.php') ?>" method="POST" style="display:flex; flex-direction:column; gap:14px;">
        <input type="text" name="nama_penerima" required placeholder="Nama Lengkap Penerima" value="<?= htmlspecialchars($currentUser['nama'] ?? '') ?>">
        <input type="email" name="email_penerima" required placeholder="Email Penerima" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>">
        <input type="text" name="no_hp" required placeholder="Nomor WhatsApp Aktif" value="<?= htmlspecialchars($currentUser['no_hp'] ?? '') ?>">
        <textarea name="alamat_lengkap" rows="3" required placeholder="Alamat Pengiriman Lengkap"></textarea>
        <div>
          <label style="font-size:0.82rem; font-weight:600;">Metode Pembayaran Aman</label>
          <select name="metode_pembayaran" style="margin-top:6px;">
            <option value="qris">QRIS Instan</option>
            <option value="bca_va">BCA Virtual Account</option>
            <option value="mandiri_va">Mandiri Virtual Account</option>
            <option value="gopay">GoPay / OVO / DANA</option>
            <option value="cod">Bayar di Tempat (COD)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Bayar &amp; Buat Pesanan</button>
      </form>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
