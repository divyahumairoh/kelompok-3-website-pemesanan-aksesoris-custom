<?php
$menu_aktif  = 'dashboard';
$judul_admin = 'Dashboard';
$sub_admin   = 'Selamat datang di panel admin Lydi! ✨';
include '../includes/admin_header.php';

function hitung($koneksi, $sql) {
    $hasil = mysqli_query($koneksi, $sql);
    return (int) mysqli_fetch_row($hasil)[0];
}
$total_pesanan   = hitung($koneksi, "SELECT COUNT(*) FROM pesanan");
$total_produk    = hitung($koneksi, "SELECT COUNT(*) FROM produk WHERE aktif = 1");
$total_pelanggan = hitung($koneksi, "SELECT COUNT(*) FROM users WHERE role = 'pelanggan'");

$terbaru = mysqli_query($koneksi,
  "SELECT kode_pesanan, nama_penerima, status
   FROM pesanan ORDER BY created_at DESC, id DESC LIMIT 3");

// Grafik: bulanan atau harian (12 titik terakhir yang ada datanya)
$mode   = (($_GET['grafik'] ?? '') === 'harian') ? 'harian' : 'bulanan';
$format = ($mode === 'harian') ? '%Y-%m-%d' : '%Y-%m';
$hasil  = mysqli_query($koneksi,
  "SELECT DATE_FORMAT(created_at, '$format') AS periode, SUM(total) AS jumlah
   FROM pesanan WHERE status <> 'dibatalkan'
   GROUP BY periode ORDER BY periode DESC LIMIT 12");
$label = []; $data = [];
while ($r = mysqli_fetch_assoc($hasil)) {
    array_unshift($label, $r['periode']);
    array_unshift($data, (int) $r['jumlah']);
}
?>
<div class="stat-grid">
  <div class="stat ungu"><div class="ikon">🛒</div><div><small>Total Pesanan</small><strong><?= $total_pesanan ?></strong></div></div>
  <div class="stat pink"><div class="ikon">📦</div><div><small>Total Produk</small><strong><?= $total_produk ?></strong></div></div>
  <div class="stat biru"><div class="ikon">👥</div><div><small>Total Pelanggan</small><strong><?= $total_pelanggan ?></strong></div></div>
</div>

<div class="dua-kolom">
  <div class="panel">
    <div class="panel-kepala">
      <h2>📊 Grafik Penjualan</h2>
      <div class="toggle">
        <a href="?grafik=bulanan" class="<?= $mode == 'bulanan' ? 'aktif' : '' ?>">Bulanan</a>
        <a href="?grafik=harian" class="<?= $mode == 'harian' ? 'aktif' : '' ?>">Harian</a>
      </div>
    </div>
    <canvas id="grafik" height="130"></canvas>
  </div>

  <div class="panel">
    <div class="panel-kepala">
      <h2>🕘 Pesanan Terbaru</h2>
      <a class="link-semua" href="<?= BASE_URL ?>/admin/pesanan.php">Lihat Semua →</a>
    </div>
    <?php while ($p = mysqli_fetch_assoc($terbaru)) { ?>
      <div class="baris-pesanan">
        <div>
          <strong>#<?= htmlspecialchars($p['kode_pesanan']) ?></strong><br>
          <small><?= htmlspecialchars($p['nama_penerima']) ?></small>
        </div>
        <span class="badge s-<?= $p['status'] ?>"><?= label_status($p['status']) ?></span>
      </div>
    <?php } ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('grafik'), {
  type: 'line',
  data: {
    labels: <?= json_encode($label) ?>,
    datasets: [{
      data: <?= json_encode($data) ?>,
      borderColor: '#f472a8',
      backgroundColor: 'rgba(244,114,168,.15)',
      pointBackgroundColor: '#fff',
      pointBorderColor: '#f472a8',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { ticks: { callback: v => v.toLocaleString('id-ID') } } }
  }
});
</script>
<?php include '../includes/admin_footer.php'; ?>