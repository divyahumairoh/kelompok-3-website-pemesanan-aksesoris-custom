<?php
// pages/admin.php — Panel Admin Lengkap LYDI: Dashboard Admin, Kelola Produk, Kelola Pesanan, Kelola Pelanggan, Laporan Admin
require_once __DIR__ . '/../includes/header.php';

if (!$currentUser || $currentUser['role'] !== 'admin') {
    ?>
    <section class="container" style="max-width:520px; padding:56px 20px;">
      <div class="card" style="text-align:center; border-radius:48px 48px 16px 16px; padding:32px;">
        <svg viewBox="0 0 80 56" width="44" height="30" fill="none" style="margin:0 auto 10px;" aria-hidden="true">
          <path d="M38 24 C23 9, 6 12, 8 24 C10 35, 25 34, 38 25 Z" fill="#F4D2DC" stroke="#A86473" stroke-width="1.4" />
          <path d="M42 24 C57 9, 74 12, 72 24 C70 35, 55 34, 42 25 Z" fill="#F4D2DC" stroke="#A86473" stroke-width="1.4" />
          <ellipse cx="40" cy="24.5" rx="5.5" ry="4.5" fill="#FFFDFB" stroke="#C19E7B" stroke-width="1.3" />
        </svg>
        <h2>Halaman Khusus Admin LYDI</h2>
        <p style="margin:10px 0 18px; font-size:0.86rem; color:var(--ink-secondary);">
          Silakan masuk menggunakan akun Admin untuk mengakses Dashboard, Kelola Produk, Kelola Pesanan, Kelola Pelanggan, dan Laporan.
        </p>
        <a href="<?= baseUrl('pages/login.php') ?>" class="btn btn-primary">Masuk sebagai Admin</a>
      </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$tab = $_GET['tab'] ?? 'dashboard';

// ===== BLOK 1: KELOLA CHARM (tambah / ubah / hapus + gambar) =====
$charmPesan = $_SESSION['charm_pesan'] ?? '';
$charmGalat = $_SESSION['charm_galat'] ?? '';
unset($_SESSION['charm_pesan'], $_SESSION['charm_galat']);

$dirCharm = __DIR__ . '/../assets/img/charms/';
if (!is_dir($dirCharm)) {
    @mkdir($dirCharm, 0755, true);
}
if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_charm'])) {
    $_SESSION['csrf_charm'] = bin2hex(random_bytes(16));
}
$csrfCharm = $_SESSION['csrf_charm'] ?? '';

// Simpan gambar: hanya PNG/JPG/WEBP, maks 1 MB. Mengembalikan nama file, atau null.
function charmSimpanGambar(array $f, string $dir, string &$galat): ?string {
    if ($f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK)      { $galat = 'Upload gambar gagal.'; return null; }
    if ($f['size'] > 1024 * 1024)           { $galat = 'Ukuran gambar maksimal 1 MB.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($f['tmp_name'])) { $galat = 'Format gambar harus PNG, JPG, atau WEBP.'; return null; }
    $nama = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $nama)) { $galat = 'Gagal menyimpan gambar.'; return null; }
    return $nama;
}

function charmHapusFile(?string $nama, string $dir): void {
    if ($nama && preg_match('/^[a-f0-9]{16}\.(png|jpg|webp)$/', $nama) && is_file($dir . $nama)) {
        unlink($dir . $nama);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['charm_action'])) {
    $pesanBaru = '';
    $galatBaru = '';

    if ($csrfCharm === '' || !hash_equals($csrfCharm, $_POST['csrf'] ?? '')) {
        $galatBaru = 'Sesi tidak valid, muat ulang halaman lalu coba lagi.';
    } else {
        $cAksi  = $_POST['charm_action'];
        $cId    = (int)($_POST['charm_id'] ?? 0);
        $cNama  = trim($_POST['nama'] ?? '');
        $cBahan = trim($_POST['bahan'] ?? '');
        $cHarga = (int)($_POST['harga'] ?? 0);
        $cStok  = (int)($_POST['stok'] ?? 0);

        if ($cAksi === 'hapus' && $cId) {
            $cQ = $pdo->prepare("SELECT gambar FROM charms WHERE id = ?");
            $cQ->execute([$cId]);
            $cLama = $cQ->fetchColumn();
            try {
                $cOk = $pdo->prepare("DELETE FROM charms WHERE id = ?")->execute([$cId]);
            } catch (PDOException $e) {
                $cOk = false;
            }
            if ($cOk) {
                charmHapusFile($cLama ?: null, $dirCharm);
                $pesanBaru = 'Charm dihapus.';
            } else {
                $galatBaru = 'Charm tidak bisa dihapus karena sudah dipakai pada pesanan.';
            }
        } elseif (in_array($cAksi, ['tambah', 'ubah'], true)) {
            if ($cNama === '' || mb_strlen($cNama) > 60) {
                $galatBaru = 'Nama charm wajib diisi (maks 60 karakter).';
            } elseif (mb_strlen($cBahan) > 60) {
                $galatBaru = 'Bahan maksimal 60 karakter.';
            } elseif ($cHarga < 1000 || $cStok < 0) {
                $galatBaru = 'Harga minimal Rp 1.000 dan stok tidak boleh negatif.';
            } else {
                $cGambar = charmSimpanGambar($_FILES['gambar'] ?? ['error' => UPLOAD_ERR_NO_FILE], $dirCharm, $galatBaru);
                if ($galatBaru === '') {
                    if ($cAksi === 'tambah') {
                        $pdo->prepare("INSERT INTO charms (nama, bahan, harga, stok, gambar) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$cNama, $cBahan, $cHarga, $cStok, $cGambar]);
                        $pesanBaru = 'Charm ditambahkan.';
                    } elseif ($cId) {
                        if ($cGambar) {
                            $cQ = $pdo->prepare("SELECT gambar FROM charms WHERE id = ?");
                            $cQ->execute([$cId]);
                            charmHapusFile($cQ->fetchColumn() ?: null, $dirCharm);
                            $pdo->prepare("UPDATE charms SET nama = ?, bahan = ?, harga = ?, stok = ?, gambar = ? WHERE id = ?")
                                ->execute([$cNama, $cBahan, $cHarga, $cStok, $cGambar, $cId]);
                        } else {
                            $pdo->prepare("UPDATE charms SET nama = ?, bahan = ?, harga = ?, stok = ? WHERE id = ?")
                                ->execute([$cNama, $cBahan, $cHarga, $cStok, $cId]);
                        }
                        $pesanBaru = 'Charm diperbarui.';
                    }
                }
            }
        }
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        if ($pesanBaru !== '') $_SESSION['charm_pesan'] = $pesanBaru;
        if ($galatBaru !== '') $_SESSION['charm_galat'] = $galatBaru;
        $redirectUrl = baseUrl('pages/admin.php?tab=produk');
        if ($cAksi === 'ubah' && $galatBaru !== '' && $cId > 0) {
            $redirectUrl .= '&edit_charm=' . $cId;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    $charmPesan = $pesanBaru;
    $charmGalat = $galatBaru;
}
// ===== akhir blok 1 =====

$orders      = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$accessories = $pdo->query("SELECT * FROM accessories ORDER BY harga_dasar ASC")->fetchAll();
$materials   = $pdo->query("SELECT * FROM materials ORDER BY nama ASC")->fetchAll();
$beads       = $pdo->query("SELECT * FROM beads ORDER BY id ASC")->fetchAll();
$charms      = $pdo->query("SELECT * FROM charms ORDER BY id ASC")->fetchAll();
$customers   = $pdo->query("
    SELECT u.id, u.nama, u.email, u.no_hp, u.role, u.created_at,
           COUNT(o.id) AS total_pesanan,
           COALESCE(SUM(CASE WHEN o.status_pesanan <> 'Dibatalkan' THEN o.total_bayar ELSE 0 END), 0) AS total_belanja
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id OR LOWER(o.email_penerima) = LOWER(u.email)
    GROUP BY u.id
    ORDER BY u.role DESC, u.created_at DESC
")->fetchAll();

$totalPendapatan = 0;
$antreanAktif = 0;
$selesaiCount = 0;
foreach ($orders as $ord) {
    if ($ord['status_pembayaran'] === 'Dibayar' || $ord['status_pesanan'] === 'Selesai') {
        $totalPendapatan += (int)$ord['total_bayar'];
    }
    if ($ord['status_pesanan'] === 'Pending' || $ord['status_pesanan'] === 'Diproses') {
        $antreanAktif++;
    }
    if ($ord['status_pesanan'] === 'Selesai') {
        $selesaiCount++;
    }
}
$totalStokManik = array_sum(array_map(fn($b) => (int)$b['stok'], $beads));
$pelangganCount = count(array_filter($customers, fn($c) => $c['role'] === 'customer'));

$dari   = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$reportOrders = array_filter($orders, function ($ord) use ($dari, $sampai) {
    $tgl = substr($ord['created_at'], 0, 10);
    if ($dari && $tgl < $dari) return false;
    if ($sampai && $tgl > $sampai) return false;
    return true;
});
?>
<div style="flex:1; overflow-y:auto; padding:24px 0;">
  <section class="container" style="display:flex; flex-direction:column; gap:20px;">
    <!-- Header & 5 Menu Admin -->
    <div class="card" style="display:flex; flex-direction:column; gap:14px;">
      <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:14px;">
        <div>
          <p style="font-family:'Cormorant Garamond',serif; font-style:italic; font-weight:600; color:var(--accent-rose); font-size:0.92rem;">
            Pusat Administrasi Atelier LYDI · Vintage Coquette
          </p>
          <h2 style="font-size:1.75rem; line-height:1.1;">Dashboard, Produk, Pesanan, Pelanggan &amp; Laporan</h2>
        </div>
        <svg viewBox="0 0 120 62" width="66" height="34" fill="none" style="opacity:0.8;" aria-hidden="true">
          <path d="M12 49 C35 38, 85 38, 108 49" stroke="#C19E7B" stroke-width="2" stroke-linecap="round" />
          <path d="M16 46 C26 24, 42 24, 46 39 C51 14, 69 14, 74 39 C78 24, 94 24, 104 46" stroke="#C19E7B" stroke-width="1.5" stroke-linejoin="round" />
          <ellipse cx="60" cy="27" rx="5.5" ry="7.5" fill="#F4D2DC" stroke="#A86473" stroke-width="1.2" />
          <circle cx="60" cy="14" r="3.4" fill="#FFFDFB" stroke="#C19E7B" stroke-width="1.1" />
        </svg>
      </div>

      <div class="step-tabs" style="grid-template-columns:repeat(5, minmax(0, 1fr)); margin-bottom:0;">
        <a href="<?= baseUrl('pages/admin.php?tab=dashboard') ?>" class="step-tab-btn <?= $tab === 'dashboard' ? 'active' : '' ?>" style="text-align:center; text-decoration:none;">Dashboard Admin</a>
        <a href="<?= baseUrl('pages/admin.php?tab=produk') ?>" class="step-tab-btn <?= $tab === 'produk' ? 'active' : '' ?>" style="text-align:center; text-decoration:none;">Kelola Produk (<?= count($accessories) + count($beads) + count($charms) ?>)</a>
        <a href="<?= baseUrl('pages/admin.php?tab=pesanan') ?>" class="step-tab-btn <?= $tab === 'pesanan' ? 'active' : '' ?>" style="text-align:center; text-decoration:none;">Kelola Pesanan (<?= count($orders) ?>)</a>
        <a href="<?= baseUrl('pages/admin.php?tab=pelanggan') ?>" class="step-tab-btn <?= $tab === 'pelanggan' ? 'active' : '' ?>" style="text-align:center; text-decoration:none;">Kelola Pelanggan (<?= count($customers) ?>)</a>
        <a href="<?= baseUrl('pages/admin.php?tab=laporan') ?>" class="step-tab-btn <?= $tab === 'laporan' ? 'active' : '' ?>" style="text-align:center; text-decoration:none;">Laporan Admin</a>
      </div>
    </div>

    <?php if ($tab === 'dashboard'): ?>
      <!-- 1. DASHBOARD ADMIN -->
      <div style="display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px;">
        <div class="card" style="padding:16px;">
          <div style="font-size:0.78rem; color:var(--ink-secondary);">Pendapatan Terverifikasi</div>
          <div class="mono" style="font-size:1.45rem; font-weight:700; color:#465C49; margin-top:4px;"><?= formatRupiah($totalPendapatan) ?></div>
          <div style="font-size:0.74rem; color:var(--ink-secondary); margin-top:2px;"><?= $selesaiCount ?> pesanan selesai</div>
        </div>
        <div class="card" style="padding:16px;">
          <div style="font-size:0.78rem; color:var(--ink-secondary);">Antrean Produksi</div>
          <div class="mono" style="font-size:1.45rem; font-weight:700; color:var(--accent-rose); margin-top:4px;"><?= $antreanAktif ?></div>
          <div style="font-size:0.74rem; color:var(--ink-secondary); margin-top:2px;">Dari <?= count($orders) ?> total pesanan</div>
        </div>
        <div class="card" style="padding:16px;">
          <div style="font-size:0.78rem; color:var(--ink-secondary);">Pelanggan Terdaftar</div>
          <div class="mono" style="font-size:1.45rem; font-weight:700; margin-top:4px;"><?= $pelangganCount ?></div>
          <div style="font-size:0.74rem; color:var(--ink-secondary); margin-top:2px;">Total <?= count($customers) ?> akun aktif</div>
        </div>
        <div class="card" style="padding:16px;">
          <div style="font-size:0.78rem; color:var(--ink-secondary);">Persediaan Manik</div>
          <div class="mono" style="font-size:1.45rem; font-weight:700; margin-top:4px;"><?= number_format($totalStokManik, 0, ',', '.') ?></div>
          <div style="font-size:0.74rem; color:var(--ink-secondary); margin-top:2px;"><?= count($beads) ?> manik · <?= count($charms) ?> charm</div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="card">
          <h3 style="font-size:1.25rem; margin-bottom:10px;">Pesanan Terbaru</h3>
          <?php foreach (array_slice($orders, 0, 5) as $ord): ?>
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #E5CFD5; font-size:0.84rem;">
              <div>
                <strong class="mono"><?= htmlspecialchars($ord['kode_invoice']) ?></strong> · <?= htmlspecialchars($ord['nama_penerima']) ?>
              </div>
              <div class="mono">
                <strong><?= formatRupiah($ord['total_bayar']) ?></strong> (<?= htmlspecialchars($ord['status_pesanan']) ?>)
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <h3 style="font-size:1.25rem; margin-bottom:10px;">Akses Cepat Pengelolaan</h3>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <a href="<?= baseUrl('pages/admin.php?tab=produk') ?>" class="btn btn-secondary">Kelola Produk</a>
            <a href="<?= baseUrl('pages/admin.php?tab=pesanan') ?>" class="btn btn-secondary">Kelola Pesanan</a>
            <a href="<?= baseUrl('pages/admin.php?tab=pelanggan') ?>" class="btn btn-secondary">Kelola Pelanggan</a>
            <a href="<?= baseUrl('pages/admin.php?tab=laporan') ?>" class="btn btn-primary">Buka Laporan Admin</a>
          </div>
        </div>
      </div>

    <?php elseif ($tab === 'produk'): ?>
      <!-- 2. KELOLA PRODUK: daftar ringkas, formulir hanya saat Edit dipilih -->
      <?php
      $editAccessory = null;
      if (isset($_GET['edit_accessory']) && ctype_digit((string)$_GET['edit_accessory'])) {
          foreach ($accessories as $item) {
              if ((int)$item['id'] === (int)$_GET['edit_accessory']) {
                  $editAccessory = $item;
                  break;
              }
          }
      }

      $editBead = null;
      if (isset($_GET['edit_bead']) && ctype_digit((string)$_GET['edit_bead'])) {
          foreach ($beads as $item) {
              if ((int)$item['id'] === (int)$_GET['edit_bead']) {
                  $editBead = $item;
                  break;
              }
          }
      }

      $editCharm = null;
      if (isset($_GET['edit_charm']) && ctype_digit((string)$_GET['edit_charm'])) {
          foreach ($charms as $item) {
              if ((int)$item['id'] === (int)$_GET['edit_charm']) {
                  $editCharm = $item;
                  break;
              }
          }
      }
      $urlProduk = baseUrl('pages/admin.php?tab=produk');
      ?>

      <?php if ($editAccessory): ?>
        <div class="card" id="form-edit-produk-utama">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
            <div>
              <h3 style="font-size:1.3rem; margin:0 0 4px;">Edit Produk Utama</h3>
              <span style="font-size:0.82rem; color:var(--ink-secondary);">Ubah nama, harga dasar, atau batas maksimal manik.</span>
            </div>
            <a href="<?= $urlProduk ?>" class="btn btn-secondary" style="text-decoration:none;">← Kembali ke Daftar</a>
          </div>
          <form action="<?= baseUrl('process/admin_process.php') ?>" method="POST" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:10px; align-items:end;">
            <input type="hidden" name="action" value="update_accessory">
            <input type="hidden" name="accessory_id" value="<?= (int)$editAccessory['id'] ?>">
            <div>
              <label style="font-size:0.75rem;">Nama Produk</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($editAccessory['nama']) ?>" required>
            </div>
            <div>
              <label style="font-size:0.75rem;">Harga Dasar (Rp)</label>
              <input type="number" name="harga_dasar" value="<?= (int)$editAccessory['harga_dasar'] ?>" min="5000" step="1000" required>
            </div>
            <div>
              <label style="font-size:0.75rem;">Maksimal Manik</label>
              <input type="number" name="maks_manik" value="<?= (int)$editAccessory['maks_manik'] ?>" min="4" max="40" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </form>
        </div>
      <?php elseif ($editBead): ?>
        <div class="card" id="form-edit-manik">
          <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
            <div>
              <h3 style="font-size:1.3rem; margin:0 0 4px;">Edit Stok &amp; Harga Manik</h3>
              <strong><?= htmlspecialchars($editBead['nama']) ?></strong>
            </div>
            <a href="<?= $urlProduk ?>" class="btn btn-secondary" style="text-decoration:none;">← Kembali ke Daftar</a>
          </div>
          <form action="<?= baseUrl('process/admin_process.php') ?>" method="POST" style="display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end;">
            <input type="hidden" name="action" value="update_bead">
            <input type="hidden" name="bead_id" value="<?= (int)$editBead['id'] ?>">
            <div>
              <label style="font-size:0.75rem;">Harga per Butir (Rp)</label>
              <input type="number" name="harga_per_butir" value="<?= (int)$editBead['harga_per_butir'] ?>" min="500" step="500" required>
            </div>
            <div>
              <label style="font-size:0.75rem;">Stok</label>
              <input type="number" name="stok" value="<?= (int)$editBead['stok'] ?>" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </form>
        </div>
      <?php endif; ?>

      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
          <h3 style="font-size:1.3rem; margin:0;">Kelola Produk Utama</h3>
          <span style="font-size:0.8rem; color:var(--ink-secondary);"><?= count($accessories) ?> produk</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px;">
          <?php foreach ($accessories as $acc): ?>
            <div style="padding:13px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
              <div style="min-width:160px; flex:1;">
                <strong><?= htmlspecialchars($acc['nama']) ?></strong>
                <div class="mono" style="font-size:0.8rem; color:var(--ink-secondary); margin-top:4px;">
                  <?= formatRupiah($acc['harga_dasar']) ?> · Maks. <?= (int)$acc['maks_manik'] ?> manik
                </div>
              </div>
              <a href="<?= $urlProduk ?>&edit_accessory=<?= (int)$acc['id'] ?>" class="btn btn-secondary" style="text-decoration:none;">Edit</a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
          <div>
            <h3 style="font-size:1.3rem; margin:0;">Kelola Manik Per Butir</h3>
            <p style="font-size:0.8rem; color:var(--ink-secondary); margin:4px 0 0;">Formulir edit hanya muncul setelah tombol Edit dipilih.</p>
          </div>
          <span style="font-size:0.8rem; color:var(--ink-secondary);"><?= count($beads) ?> jenis manik</span>
        </div>

        <form action="<?= baseUrl('process/admin_process.php') ?>" method="POST" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr auto; gap:8px; margin:12px 0 16px;">
          <input type="hidden" name="action" value="add_bead">
          <input type="text" name="nama" required placeholder="Nama manik baru">
          <select name="kategori">
            <option value="Batu Alam">Batu Alam</option>
            <option value="Logam">Logam</option>
            <option value="Mutiara & Kaca">Mutiara &amp; Kaca</option>
          </select>
          <input type="color" name="warna_hex" value="#EBBAC5" style="height:38px;">
          <input type="number" name="harga_per_butir" required min="500" step="500" value="1500" placeholder="Harga">
          <input type="number" name="stok" required min="0" value="50" placeholder="Stok">
          <button type="submit" class="btn btn-primary">Tambah</button>
        </form>

        <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px;">
          <?php foreach ($beads as $bd): ?>
            <div style="padding:12px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB; display:flex; justify-content:space-between; align-items:center; gap:10px;">
              <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                <span style="width:14px; height:14px; border-radius:50%; background:<?= htmlspecialchars($bd['warna_hex'] ?? '#EBBAC5') ?>; border:1px solid #C19E7B; flex-shrink:0;"></span>
                <div style="min-width:0;">
                  <strong style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($bd['nama']) ?></strong>
                  <div class="mono" style="font-size:0.78rem; color:var(--ink-secondary);"><?= formatRupiah($bd['harga_per_butir']) ?> · Stok <?= (int)$bd['stok'] ?></div>
                </div>
              </div>
              <a href="<?= $urlProduk ?>&edit_bead=<?= (int)$bd['id'] ?>" class="btn btn-secondary" style="text-decoration:none; padding:5px 10px;">Edit</a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- BLOK 2: KOLEKSI CHARM PERSONAL -->
      <div class="card" id="koleksi-charm">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
          <h3 style="font-size:1.3rem; margin:0;">Koleksi Charm Personal</h3>
          <span style="font-size:0.8rem; color:var(--ink-secondary);"><?= count($charms) ?> charm</span>
        </div>

        <?php if ($charmPesan): ?><p style="color:#3b7a4a; font-size:0.85rem; margin:6px 0;"><?= htmlspecialchars($charmPesan) ?></p><?php endif; ?>
        <?php if ($charmGalat): ?><p style="color:#b3363f; font-size:0.85rem; margin:6px 0;"><?= htmlspecialchars($charmGalat) ?></p><?php endif; ?>

        <?php if ($editCharm): ?>
          <!-- FORM EDIT: hanya muncul setelah tombol Edit ditekan -->
          <div style="padding:14px; border:1px solid #E5CFD5; border-radius:12px; background:#FAF0F2; margin:12px 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <strong>Edit Charm: <?= htmlspecialchars($editCharm['nama']) ?></strong>
              <a href="<?= $urlProduk ?>" class="btn btn-secondary" style="text-decoration:none;">← Batal</a>
            </div>
            <form action="<?= $urlProduk ?>&edit_charm=<?= (int)$editCharm['id'] ?>" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:2fr 1.5fr 1fr 1fr; gap:8px; align-items:end;">
              <input type="hidden" name="charm_action" value="ubah">
              <input type="hidden" name="charm_id" value="<?= (int)$editCharm['id'] ?>">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfCharm) ?>">
              <div><label style="font-size:0.75rem;">Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($editCharm['nama']) ?>" maxlength="60" required></div>
              <div><label style="font-size:0.75rem;">Bahan</label><input type="text" name="bahan" value="<?= htmlspecialchars($editCharm['bahan'] ?? '') ?>" maxlength="60"></div>
              <div><label style="font-size:0.75rem;">Harga (Rp)</label><input type="number" name="harga" value="<?= (int)$editCharm['harga'] ?>" min="1000" step="500" required></div>
              <div><label style="font-size:0.75rem;">Stok</label><input type="number" name="stok" value="<?= (int)$editCharm['stok'] ?>" min="0" required></div>
              <div style="grid-column:span 3;"><label style="font-size:0.75rem;">Ganti gambar (kosongkan jika tidak diganti)</label><input type="file" name="gambar" accept="image/png,image/jpeg,image/webp"></div>
              <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
          </div>
        <?php endif; ?>

        <!-- FORM TAMBAH -->
        <form action="<?= $urlProduk ?>" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:2fr 1.5fr 1fr 1fr; gap:8px; margin:12px 0 4px; align-items:end;">
          <input type="hidden" name="charm_action" value="tambah">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfCharm) ?>">
          <input type="text" name="nama" required maxlength="60" placeholder="Nama charm baru">
          <input type="text" name="bahan" value="Rose Gold 18K Plated" maxlength="60">
          <input type="number" name="harga" required min="1000" step="500" value="17000">
          <input type="number" name="stok" required min="0" value="80">
          <div style="grid-column:span 3;"><input type="file" name="gambar" accept="image/png,image/jpeg,image/webp"></div>
          <button type="submit" class="btn btn-primary">Tambah Charm</button>
        </form>
        <p style="font-size:0.76rem; color:var(--ink-secondary); margin:0 0 14px;">
          Gambar opsional: PNG/JPG/WEBP, maks 1 MB, paling bagus PNG latar transparan persegi (±200×200 px).
          Tanpa gambar, charm tampil sebagai liontin bawaan.
        </p>

        <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px;">
          <?php foreach ($charms as $ch): ?>
            <div style="padding:12px; border:1px solid #E5CFD5; border-radius:12px; background:#FFFDFB; display:flex; gap:10px; align-items:flex-start;">
              <div style="width:46px; height:46px; flex-shrink:0; border:1px solid #E5CFD5; border-radius:8px; background:#FAF0F2; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                <?php if (!empty($ch['gambar'])): ?>
                  <img src="<?= htmlspecialchars(baseUrl('assets/img/charms/' . $ch['gambar'])) ?>" alt="" style="max-width:100%; max-height:100%;">
                <?php else: ?>
                  <span style="font-size:0.58rem; color:var(--ink-secondary);">bawaan</span>
                <?php endif; ?>
              </div>
              <div style="flex:1; min-width:0;">
                <strong><?= htmlspecialchars($ch['nama']) ?></strong>
                <div style="font-size:0.74rem; color:var(--ink-secondary);"><?= htmlspecialchars($ch['bahan'] ?? '') ?></div>
                <div class="mono" style="font-size:0.8rem; color:var(--accent-rose); margin-top:5px;"><?= formatRupiah($ch['harga']) ?> · Stok <?= (int)$ch['stok'] ?></div>
                <div style="display:flex; gap:6px; margin-top:8px;">
                  <a href="<?= $urlProduk ?>&edit_charm=<?= (int)$ch['id'] ?>" class="btn btn-secondary" style="text-decoration:none; padding:5px 10px;">Edit</a>
                  <form action="<?= $urlProduk ?>" method="POST" onsubmit="return confirm('Yakin ingin menghapus charm ini?');">
                    <input type="hidden" name="charm_action" value="hapus">
                    <input type="hidden" name="charm_id" value="<?= (int)$ch['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfCharm) ?>">
                    <button type="submit" class="btn btn-secondary" style="padding:5px 10px; color:#9E3F46;">Hapus</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    <?php elseif ($tab === 'pesanan'): ?>
      <!-- 3. KELOLA PESANAN -->
      <div class="card">
        <h3 style="font-size:1.35rem; margin-bottom:12px;">Kelola Pesanan &amp; Produksi Custom</h3>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Penerima</th>
                <th>Produk &amp; Ukuran</th>
                <th>Total</th>
                <th>Bayar</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $ord): ?>
                <tr>
                  <td class="mono"><strong><?= htmlspecialchars($ord['kode_invoice']) ?></strong></td>
                  <td>
                    <div><strong><?= htmlspecialchars($ord['nama_penerima']) ?></strong></div>
                    <div style="font-size:0.74rem; color:var(--ink-secondary);"><?= htmlspecialchars($ord['no_hp']) ?></div>
                  </td>
                  <td>
                    <div><?= htmlspecialchars($ord['nama_produk']) ?> (<?= htmlspecialchars($ord['ukuran_cm']) ?> cm)</div>
                    <div style="font-size:0.74rem; color:var(--ink-secondary);"><?= htmlspecialchars($ord['nama_bahan']) ?></div>
                  </td>
                  <td class="mono"><strong><?= formatRupiah($ord['total_bayar']) ?></strong></td>
                  <td><?= htmlspecialchars($ord['status_pembayaran']) ?></td>
                  <td>
                    <form action="<?= baseUrl('process/admin_process.php') ?>" method="POST" style="display:flex; gap:6px; align-items:center;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                      <select name="status_pesanan" style="padding:5px 8px; font-size:0.78rem;">
                        <?php foreach (['Pending', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'] as $st): ?>
                          <option value="<?= $st ?>" <?= $ord['status_pesanan'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-secondary" style="padding:5px 10px;">Simpan</button>
                    </form>
                  </td>
                  <td>
                    <a href="<?= baseUrl('pages/invoice.php?inv=' . urlencode($ord['kode_invoice'])) ?>" class="btn btn-secondary" style="padding:5px 10px; text-decoration:none;">Invoice</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'pelanggan'): ?>
      <!-- 4. KELOLA PELANGGAN -->
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
          <div>
            <h3 style="font-size:1.35rem; margin:0;">Kelola Pelanggan</h3>
            <p style="font-size:0.8rem; color:var(--ink-secondary); margin:4px 0 0;">Pantau akun terdaftar, kontak penerima, dan riwayat belanja.</p>
          </div>
          <span style="font-size:0.8rem; color:var(--ink-secondary);"><?= $pelangganCount ?> pelanggan · <?= count($customers) ?> total akun</span>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Kontak</th>
                <th>Peran</th>
                <th>Pesanan</th>
                <th>Total Belanja</th>
                <th>Terdaftar</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customers as $cust): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($cust['nama']) ?></strong></td>
                  <td>
                    <div><?= htmlspecialchars($cust['email']) ?></div>
                    <div style="font-size:0.74rem; color:var(--ink-secondary);"><?= htmlspecialchars($cust['no_hp'] ?: '-') ?></div>
                  </td>
                  <td><?= htmlspecialchars($cust['role']) ?></td>
                  <td class="mono"><?= (int)$cust['total_pesanan'] ?></td>
                  <td class="mono"><strong><?= formatRupiah((int)$cust['total_belanja']) ?></strong></td>
                  <td class="mono" style="font-size:0.78rem; color:var(--ink-secondary);"><?= htmlspecialchars(substr($cust['created_at'], 0, 10)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'laporan'): ?>
      <!-- 5. LAPORAN ADMIN -->
      <div class="card">
        <h3 style="font-size:1.35rem; margin-bottom:10px;">Laporan Penjualan &amp; Stok Admin</h3>
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; align-items:end; margin-bottom:16px;">
          <input type="hidden" name="tab" value="laporan">
          <div>
            <label style="font-size:0.75rem;">Dari Tanggal</label>
            <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
          </div>
          <div>
            <label style="font-size:0.75rem;">Sampai Tanggal</label>
            <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Filter Laporan</button>
          <a href="<?= baseUrl('pages/admin.php?tab=laporan') ?>" class="btn btn-secondary" style="text-decoration:none;">Reset</a>
          <button type="button" onclick="window.print()" class="btn btn-secondary">Cetak Laporan</button>
        </form>

        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Pelanggan</th>
                <th>Item</th>
                <th>Pembayaran</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <?php $sumReport = 0; foreach ($reportOrders as $rOrd): $sumReport += (int)$rOrd['total_bayar']; ?>
                <tr>
                  <td class="mono"><?= htmlspecialchars(substr($rOrd['created_at'], 0, 10)) ?></td>
                  <td class="mono"><?= htmlspecialchars($rOrd['kode_invoice']) ?></td>
                  <td><?= htmlspecialchars($rOrd['nama_penerima']) ?></td>
                  <td><?= htmlspecialchars($rOrd['nama_produk']) ?></td>
                  <td><?= htmlspecialchars($rOrd['metode_pembayaran']) ?> (<?= htmlspecialchars($rOrd['status_pembayaran']) ?>)</td>
                  <td class="mono"><strong><?= formatRupiah($rOrd['total_bayar']) ?></strong></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top:14px; text-align:right; font-size:0.95rem;">
          Total Nilai Pesanan Terfilter: <strong class="mono" style="color:var(--accent-rose);"><?= formatRupiah($sumReport) ?></strong>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>
<script>
  // cegah form terkirim ulang saat halaman di-refresh
  if (window.history.replaceState) window.history.replaceState(null, '', window.location.href);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>