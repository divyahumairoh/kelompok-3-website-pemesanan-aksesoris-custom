<?php
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function label_status($status) {
    $label = [
        'diterima'   => 'Pesanan Diterima',
        'dibayar'    => 'Pembayaran Dikonfirmasi',
        'dibuat'     => 'Sedang Dibuat',
        'dikemas'    => 'Dikemas',
        'dikirim'    => 'Dikirim',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
    return $label[$status] ?? $status;
}

function gambar_produk($file) {
    $path = __DIR__ . '/../assets/img/produk/' . $file;
    if ($file && file_exists($path)) {
        return '<img src="' . BASE_URL . '/assets/img/produk/' . htmlspecialchars($file) . '" alt="">';
    }
    return '<span class="placeholder">🎀</span>';
}
?>