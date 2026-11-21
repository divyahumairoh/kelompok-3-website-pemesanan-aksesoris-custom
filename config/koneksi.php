<?php
// config/koneksi.php — Koneksi MySQL (PDO) untuk LYDI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_name = 'lydi_db_baru';
$db_user = 'root';
$db_pass = ''; // Sesuaikan dengan XAMPP/Laragon lokal Anda

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database LYDI gagal: ' . htmlspecialchars($e->getMessage()));
}

function formatRupiah(int|float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}


function baseUrl(string $path = ''): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $root = dirname($scriptName);

    if (preg_match('#/(pages|process)$#', $root)) {
        $root = dirname($root);
    }

    $root = rtrim($root, '/');

    return $root . '/' . ltrim($path, '/');
}

?>
