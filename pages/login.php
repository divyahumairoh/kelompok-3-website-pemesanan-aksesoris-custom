<?php
// pages/login.php — Halaman Login Pelanggan & Admin
require_once __DIR__ . '/../includes/header.php';
$error = $_GET['error'] ?? '';
?>
<section class="container" style="max-width:460px; padding:48px 20px;">
  <div class="card">
    <h2 style="margin-bottom:6px;">Masuk ke Akun LYDI</h2>
    <p style="font-size:0.85rem; color:var(--ink-secondary); margin-bottom:20px;">
      Masuk sebagai pelanggan untuk melacak pesanan atau sebagai admin studio.
    </p>
    <?php if ($error): ?>
      <div style="padding:10px; border-radius:8px; background:#FEF2F2; color:#991B1B; font-size:0.85rem; margin-bottom:14px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>
    <form action="<?= baseUrl('process/auth_process.php') ?>" method="POST" style="display:flex; flex-direction:column; gap:14px;">
      <input type="hidden" name="action" value="login">
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Email</label>
        <input type="email" name="email" required placeholder="nama@email.com">
      </div>
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Kata Sandi</label>
        <input type="password" name="password" required placeholder="Masukkan kata sandi">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Masuk Sekarang</button>
    </form>
    <p style="font-size:0.82rem; color:var(--ink-secondary); margin-top:16px; text-align:center;">
      Belum punya akun? <a href="<?= baseUrl('pages/register.php') ?>" style="color:var(--accent-sage); font-weight:600;">Daftar di sini</a>
    </p>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
