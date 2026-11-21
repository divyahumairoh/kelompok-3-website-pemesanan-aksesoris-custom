<?php
// admin/charm.php — Admin: tambah / ubah / hapus charm
// CATATAN: ganti 2 baris di bawah dengan layout & pengecekan login admin milikmu
require_once __DIR__ . '/../includes/header.php';   // harus menyediakan $pdo, baseUrl(), formatRupiah()
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(403); exit('Akses ditolak'); }

$dirUpload = __DIR__ . '/../assets/img/charms/';
if (!is_dir($dirUpload)) mkdir($dirUpload, 0755, true);

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$pesan = '';
$galat = '';

// Simpan gambar unggahan: hanya PNG/JPG/WEBP, maks 1 MB. Mengembalikan nama file atau null.
function simpanGambar(array $f, string $dir, string &$galat): ?string {
    if ($f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) { $galat = 'Upload gambar gagal.'; return null; }
    if ($f['size'] > 1024 * 1024)     { $galat = 'Ukuran gambar maksimal 1 MB.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($f['tmp_name'])) { $galat = 'Format harus PNG, JPG, atau WEBP.'; return null; }
    $nama = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $nama)) { $galat = 'Gagal menyimpan gambar.'; return null; }
    return $nama;
}
function hapusFile(?string $nama, string $dir): void {
    if ($nama && preg_match('/^[a-f0-9]{16}\.(png|jpg|webp)$/', $nama) && is_file($dir . $nama)) unlink($dir . $nama);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $galat = 'Sesi tidak valid, muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $harga = (int)($_POST['harga'] ?? 0);

        if ($aksi === 'hapus' && $id) {
            $lama = $pdo->prepare("SELECT gambar FROM charms WHERE id = ?");
            $lama->execute([$id]);
            hapusFile($lama->fetchColumn() ?: null, $dirUpload);
            $pdo->prepare("DELETE FROM charms WHERE id = ?")->execute([$id]);
            $pesan = 'Charm dihapus.';
        } elseif (in_array($aksi, ['tambah', 'ubah'], true)) {
            if ($nama === '' || mb_strlen($nama) > 60) $galat = 'Nama charm wajib diisi (maks 60 karakter).';
            elseif ($harga < 0) $galat = 'Harga tidak boleh negatif.';
            else {
                $gambar = simpanGambar($_FILES['gambar'] ?? ['error' => UPLOAD_ERR_NO_FILE], $dirUpload, $galat);
                if ($galat === '') {
                    if ($aksi === 'tambah') {
                        $pdo->prepare("INSERT INTO charms (nama, harga, gambar) VALUES (?, ?, ?)")->execute([$nama, $harga, $gambar]);
                        $pesan = 'Charm ditambahkan.';
                    } else {
                        if ($gambar) {
                            $lama = $pdo->prepare("SELECT gambar FROM charms WHERE id = ?");
                            $lama->execute([$id]);
                            hapusFile($lama->fetchColumn() ?: null, $dirUpload);
                            $pdo->prepare("UPDATE charms SET nama = ?, harga = ?, gambar = ? WHERE id = ?")->execute([$nama, $harga, $gambar, $id]);
                        } else {
                            $pdo->prepare("UPDATE charms SET nama = ?, harga = ? WHERE id = ?")->execute([$nama, $harga, $id]);
                        }
                        $pesan = 'Charm diperbarui.';
                    }
                }
            }
        }
    }
}

$charms = $pdo->query("SELECT * FROM charms ORDER BY id DESC")->fetchAll();
?>
<section class="container">
  <h2>Kelola Charm</h2>
  <?php if ($pesan): ?><p style="color:#3b7a4a;"><?= htmlspecialchars($pesan) ?></p><?php endif; ?>
  <?php if ($galat): ?><p style="color:#b3363f;"><?= htmlspecialchars($galat) ?></p><?php endif; ?>

  <div class="card" style="margin-bottom:16px;">
    <h3>Tambah Charm Baru</h3>
    <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:2fr 1fr 2fr auto; gap:10px; align-items:end;">
      <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
      <input type="hidden" name="aksi" value="tambah">
      <div><label>Nama charm</label><input type="text" name="nama" maxlength="60" required placeholder="Contoh: Daun Emas Vintage"></div>
      <div><label>Harga (Rp)</label><input type="number" name="harga" min="0" step="500" required></div>
      <div><label>Gambar (PNG/JPG/WEBP, maks 1 MB)</label><input type="file" name="gambar" accept="image/png,image/jpeg,image/webp"></div>
      <button type="submit" class="btn btn-primary">Tambah</button>
    </form>
    <p style="font-size:0.78rem; color:var(--ink-secondary); margin-top:8px;">
      Tips gambar: latar transparan (PNG), persegi, sekitar 200×200 px. Tanpa gambar, charm tampil sebagai liontin mutiara polos.
    </p>
  </div>

  <div class="card">
    <h3>Daftar Charm</h3>
    <?php foreach ($charms as $ch): ?>
      <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:56px 2fr 1fr 2fr auto auto; gap:10px; align-items:center; padding:8px 0; border-bottom:1px solid #E5CFD5;">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
        <input type="hidden" name="id" value="<?= (int)$ch['id'] ?>">
        <div style="width:48px; height:48px; border:1px solid #E5CFD5; border-radius:8px; background:#FAF0F2; display:flex; align-items:center; justify-content:center; overflow:hidden;">
          <?php if (!empty($ch['gambar'])): ?>
            <img src="<?= htmlspecialchars(baseUrl('assets/img/charms/' . $ch['gambar'])) ?>" alt="" style="max-width:100%; max-height:100%;">
          <?php else: ?><span style="font-size:0.6rem;">bawaan</span><?php endif; ?>
        </div>
        <input type="text" name="nama" value="<?= htmlspecialchars($ch['nama']) ?>" maxlength="60" required>
        <input type="number" name="harga" value="<?= (int)$ch['harga'] ?>" min="0" step="500" required>
        <input type="file" name="gambar" accept="image/png,image/jpeg,image/webp">
        <button type="submit" name="aksi" value="ubah" class="btn btn-primary">Simpan</button>
        <button type="submit" name="aksi" value="hapus" class="btn btn-secondary" onclick="return confirm('Hapus charm ini?');">Hapus</button>
      </form>
    <?php endforeach; ?>
    <?php if (!$charms): ?><p>Belum ada charm.</p><?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
