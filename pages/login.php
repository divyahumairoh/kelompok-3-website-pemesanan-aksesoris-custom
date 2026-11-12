<?php
session_start();
require_once '../config/koneksi.php';

// Kalau sudah login, arahkan sesuai peran
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($koneksi, "SELECT id, nama, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        header('Location: ' . ($user['role'] === 'admin' ? '../admin/dashboard.php' : '../index.php'));
        exit;
    }
    $error = 'Email atau password salah.';
}

$judul = 'Login';
include '../includes/header.php';
?>
<div class="container">
  <div class="form-auth">
    <h1>Masuk ke lydi.</h1>
    <?php if ($error) { ?><p class="pesan-error"><?= $error ?></p><?php } ?>
    <form method="post">
      <label>Email</label>
      <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <label>Password</label>
      <input type="password" name="password" required>
      <button class="btn btn-pink" type="submit">Masuk</button>
    </form>
  </div>
</div>
<?php include '../includes/footer.php'; ?>