
<?php
// process/auth_process.php
// Proses Login, Register & Logout LYDI

require_once __DIR__ . '/../config/koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* =====================================
   LOGOUT
===================================== */
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();

    header('Location: ' . baseUrl('index.php'));
    exit;
}

/* =====================================
   LOGIN
===================================== */
if ($action === 'login') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $loginValid = false;

    if ($user) {

        $storedPassword = $user['password_hash'];

        // Cek password yang sudah di-hash
        if (password_verify($password, $storedPassword)) {
            $loginValid = true;
        }

        // Cek password lama yang masih berupa teks biasa
        elseif (hash_equals($storedPassword, $password)) {
            $loginValid = true;

            // Otomatis ubah password menjadi hash
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $update = $pdo->prepare(
                "UPDATE users SET password_hash = ? WHERE id = ?"
            );

            $update->execute([$newHash, $user['id']]);
        }
    }

    if ($loginValid) {

        // Perbarui ID sesi setelah login
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'    => $user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'no_hp' => $user['no_hp'] ?? '',
            'role'  => $user['role']
        ];

        // Jika berasal dari checkout
        if ($redirect === 'checkout') {
            header('Location: ' . baseUrl('pages/checkout.php'));
            exit;
        }

        // Arahkan berdasarkan role
        if ($user['role'] === 'admin') {
            header('Location: ' . baseUrl('pages/admin.php'));
        } else {
            header('Location: ' . baseUrl('pages/kustomisasi.php'));
        }

        exit;
    }

    // Login gagal
    $error = urlencode('Email atau kata sandi salah.');

    if ($redirect === 'checkout') {
        header(
            'Location: ' . baseUrl(
                'pages/checkout.php?auth_tab=login&error=' . $error
            )
        );
    } else {
        header(
            'Location: ' . baseUrl(
                'pages/login.php?error=' . $error
            )
        );
    }

    exit;
}

/* =====================================
   REGISTER
===================================== */
if ($action === 'register') {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    // Validasi data
    if (
        $nama === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 6
    ) {

        $error = urlencode(
            'Data tidak valid atau kata sandi kurang dari 6 karakter.'
        );

        $target = $redirect === 'checkout'
            ? 'pages/checkout.php?auth_tab=register&error=' . $error
            : 'pages/register.php?error=' . $error;

        header('Location: ' . baseUrl($target));
        exit;
    }

    // Periksa email yang sudah terdaftar
    $check = $pdo->prepare(
        "SELECT id FROM users WHERE email = ?"
    );
    $check->execute([$email]);

    if ($check->fetch()) {

        $error = urlencode('Email sudah terdaftar. Silakan masuk.');

        $target = $redirect === 'checkout'
            ? 'pages/checkout.php?auth_tab=register&error=' . $error
            : 'pages/register.php?error=' . $error;

        header('Location: ' . baseUrl($target));
        exit;
    }

    // Enkripsi password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Simpan pengguna baru sebagai customer
    $stmt = $pdo->prepare(
        "INSERT INTO users
        (nama, email, password_hash, no_hp, role)
        VALUES (?, ?, ?, ?, 'customer')"
    );

    $stmt->execute([
        $nama,
        $email,
        $hash,
        $no_hp
    ]);

    // Buat sesi pengguna
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => $pdo->lastInsertId(),
        'nama'  => $nama,
        'email' => $email,
        'no_hp' => $no_hp,
        'role'  => 'customer'
    ];

    $target = $redirect === 'checkout'
        ? 'pages/checkout.php'
        : 'pages/kustomisasi.php';

    header('Location: ' . baseUrl($target));
    exit;
}

// Jika action tidak dikenal
header('Location: ' . baseUrl('index.php'));
exit;

?>
