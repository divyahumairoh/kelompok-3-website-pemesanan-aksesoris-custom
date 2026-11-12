<?php
$judul = 'Beranda';
$halaman = 'home';
include 'includes/header.php';

$kategori = mysqli_query($koneksi, "SELECT * FROM kategori");
?>
<section class="hero">
  <div class="container">
    <h1>Make it yours!</h1>
    <p>Create your own accessories, made just for you ♡</p>
    <a class="btn" href="<?= BASE_URL ?>/pages/kustomisasi.php">Start Customizing →</a>
  </div>
</section>

<div class="container">
  <h2 class="judul-seksi">Explore Our Collection</h2>
  <div class="grid">
    <?php while ($k = mysqli_fetch_assoc($kategori)) { ?>
      <a class="kartu" href="<?= BASE_URL ?>/pages/katalog.php?kategori=<?= $k['id'] ?>">
        <div class="gambar"></div>
        <strong><?= htmlspecialchars($k['nama']) ?></strong>
      </a>
    <?php } ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>