
<?php
// pages/register.php — Halaman Pendaftaran Pelanggan Baru
require_once __DIR__ . '/../includes/header.php';
$error = $_GET['error'] ?? '';
?>

<section class="container" style="max-width:480px; padding:48px 20px;">
  <div class="card">
    <h2 style="margin-bottom:6px;">Daftar Akun LYDI</h2>

    <p style="font-size:0.85rem; color:var(--ink-secondary); margin-bottom:20px;">
      Simpan riwayat desain dan pantau status produksi aksesoris custom Anda.
    </p>

    <?php if ($error): ?>
      <div style="padding:10px; border-radius:8px; background:#FEF2F2; color:#991B1B; font-size:0.85rem; margin-bottom:14px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="<?= baseUrl('process/auth_process.php') ?>" method="POST" style="display:flex; flex-direction:column; gap:14px;">

      <input type="hidden" name="action" value="register">

      <!-- Nama Lengkap -->
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Nama Lengkap</label>
        <input type="text" name="nama" required placeholder="Nama Anda">
      </div>

      <!-- Email -->
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Email</label>
        <input type="email" name="email" required placeholder="nama@email.com">
      </div>

      <!-- Nomor WhatsApp -->
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Nomor WhatsApp</label>
        <input type="text" name="no_hp" placeholder="0812-xxxx-xxxx">
      </div>

      <!-- Kata Sandi dengan fitur Lihat/Sembunyikan -->
      <div>
        <label style="font-size:0.82rem; font-weight:600;">Kata Sandi</label>

        <div style="position:relative;">
          <input
            type="password"
            name="password"
            id="password"
            required
            minlength="6"
            placeholder="Minimal 6 karakter"
            style="width:100%; padding-right:90px;"
          >

          <button
            type="button"
            id="togglePassword"
            onclick="togglePasswordVisibility()"
            aria-label="Lihat kata sandi"
            style="
              position:absolute;
              right:10px;
              top:50%;
              transform:translateY(-50%);
              background:transparent;
              border:none;
              color:#A65F73;
              font-size:12px;
              font-weight:600;
              cursor:pointer;
              padding:6px;
            "
          >
            Lihat
          </button>
        </div>
      </div>

      <!-- Tombol Daftar -->
      <button type="submit" class="btn btn-primary" style="width:100%;">
        Buat Akun
      </button>

    </form>
  </div>
</section>

<!-- JavaScript untuk melihat dan menyembunyikan kata sandi -->
<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.textContent = 'Sembunyikan';
        toggleButton.setAttribute('aria-label', 'Sembunyikan kata sandi');
    } else {
        passwordInput.type = 'password';
        toggleButton.textContent = 'Lihat';
        toggleButton.setAttribute('aria-label', 'Lihat kata sandi');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
