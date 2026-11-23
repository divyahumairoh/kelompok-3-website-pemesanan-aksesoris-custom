<?php
// pages/kustomisasi.php — Studio Kustomisasi Produk Tanpa Scroll (Murni PHP + HTML + JS)
require_once __DIR__ . '/../includes/header.php';

$accessories = $pdo->query("SELECT * FROM accessories")->fetchAll();
$materials   = $pdo->query("SELECT * FROM materials")->fetchAll();
$beads       = $pdo->query("SELECT * FROM beads")->fetchAll();
$charms      = $pdo->query("SELECT * FROM charms")->fetchAll();
$selectedJenis = $_GET['jenis'] ?? ($accessories[0]['id'] ?? 'gelang');
?>
<section class="container studio-grid" id="lydi-studio-app">
  <!-- Kolom Kiri: Live Arch Preview + Harga Real-Time -->
  <div style="display:flex; flex-direction:column; gap:14px; min-height:0;">
    <div class="card" style="flex:1; display:flex; flex-direction:column; min-height:0;">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:8px;">
        <h3 style="font-size:1.25rem;">Visualisasi Live</h3>
        <div style="display:flex; align-items:center; gap:8px;">
          <span id="bead-capacity-badge" class="mono" style="font-size:0.75rem; color:var(--accent-rose); font-weight:600;">0/17 manik</span>
          <button
            type="button"
            id="btn-reset-isian"
            class="btn btn-secondary"
            style="padding:4px 10px; font-size:0.72rem;"
            title="Hapus manik, ukiran, inisial, dan charm tanpa mengubah produk"
          >Reset</button>
        </div>
      </div>
      <div class="preview-box">
        <svg id="live-svg-preview" viewBox="0 0 320 290" width="100%" height="100%"></svg>
      </div>
    </div>

    <div class="card" style="flex-shrink:0;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div id="rincian-biaya-list" style="font-size:0.75rem; color:var(--ink-secondary);"></div>
        <strong id="total-harga-display" class="mono" style="font-size:1.3rem;">Rp 35.000</strong>
      </div>
      <form action="<?= baseUrl('pages/checkout.php') ?>" method="POST">
        <div id="hidden-design-inputs"></div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Pesan &amp; Lanjut Pembayaran</button>
      </form>
    </div>
  </div>

  <!-- Kolom Kanan: Tab Langkah Kustomisasi (Zero Scroll) -->
  <div class="card" style="display:flex; flex-direction:column; min-height:0;">
    <div class="step-tabs">
      <button type="button" class="step-tab-btn active" data-step-tab="1">01. Produk &amp; Ukuran</button>
      <button type="button" class="step-tab-btn" data-step-tab="2">02. Bahan &amp; Warna</button>
      <button type="button" class="step-tab-btn" data-step-tab="3">03. Susun Manik</button>
      <button type="button" class="step-tab-btn" data-step-tab="4">04. Ukiran &amp; Charm</button>
    </div>

    <!-- Step 1 -->
    <div class="step-pane active" data-step-pane="1">
      <h3>Pilih Jenis Aksesoris</h3>
      <div class="option-grid">
        <?php foreach ($accessories as $idx => $acc): ?>
          <button
            type="button"
            class="option-card <?= $acc['id'] === $selectedJenis ? 'active' : '' ?>"
            data-acc="<?= htmlspecialchars($acc['id']) ?>"
            data-nama="<?= htmlspecialchars($acc['nama']) ?>"
            data-harga="<?= (int)$acc['harga_dasar'] ?>"
            style="display:flex; align-items:center; gap:10px;"
          >
            <img src="<?= baseUrl('assets/img/' . $acc['id'] . '.jpg') ?>" alt="<?= htmlspecialchars($acc['nama']) ?>" style="width:46px; height:46px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid #E5CFD5;">
            <div>
              <div style="font-weight:600;"><?= htmlspecialchars($acc['nama']) ?></div>
              <div class="mono" style="font-size:0.78rem; color:var(--ink-secondary);"><?= formatRupiah($acc['harga_dasar']) ?></div>
            </div>
          </button>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <label style="font-size:0.82rem; font-weight:600;">Pilih Ukuran</label>
          <span id="size-max-note" class="mono" style="font-size:0.74rem; color:var(--accent-rose); font-weight:600;">Maks 17 manik</span>
        </div>
        <select id="size-select" style="margin-top:6px;">
          <option value="XS (14 cm)" data-max-beads="14">XS (14 cm) — Maks 14 manik</option>
          <option value="S (15.5 cm)" data-max-beads="17" selected>S (15.5 cm) — Maks 17 manik</option>
          <option value="M (17 cm)" data-max-beads="20">M (17 cm) — Maks 20 manik</option>
          <option value="L (18.5 cm)" data-max-beads="24">L (18.5 cm) — Maks 24 manik</option>
        </select>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="step-pane" data-step-pane="2">
      <h3>Pilih Warna &amp; Bahan Dasar</h3>
      <div class="option-grid">
        <?php foreach ($materials as $idx => $mat): ?>
          <button
            type="button"
            class="option-card <?= $idx === 0 ? 'active' : '' ?>"
            data-mat="<?= htmlspecialchars($mat['id']) ?>"
            data-nama="<?= htmlspecialchars($mat['nama']) ?>"
            data-hex="<?= htmlspecialchars($mat['warna_hex']) ?>"
            data-harga="<?= (int)$mat['harga_tambahan'] ?>"
          >
            <span class="bead-dot" style="width:16px; height:16px; background:<?= htmlspecialchars($mat['warna_hex']) ?>;"></span>
            <div style="font-weight:600; font-size:0.85rem; margin-top:4px;"><?= htmlspecialchars($mat['nama']) ?></div>
            <div class="mono" style="font-size:0.75rem; color:var(--ink-secondary);">+<?= formatRupiah($mat['harga_tambahan']) ?></div>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="step-pane" data-step-pane="3">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
        <h3>Susun Manik-Manik (Harga Per Butir)</h3>
        <span id="step3-capacity-text" class="mono" style="font-size:0.78rem; color:var(--accent-rose); font-weight:700;">0 / 17 manik</span>
      </div>
      <div style="display:flex; flex-direction:column; gap:8px; margin-top:10px;">
        <?php foreach ($beads as $bead): ?>
          <div class="bead-row">
            <div style="display:flex; align-items:center; gap:10px;">
              <span class="bead-dot" style="background:<?= htmlspecialchars($bead['warna_hex']) ?>;"></span>
              <div>
                <div style="font-weight:600; font-size:0.86rem;"><?= htmlspecialchars($bead['nama']) ?></div>
                <div class="mono" style="font-size:0.76rem; color:var(--ink-secondary);"><?= formatRupiah($bead['harga_per_butir']) ?> / butir</div>
              </div>
            </div>
            <div style="display:flex; gap:6px;">
              <button type="button" class="btn btn-secondary" data-rem-bead-id="<?= (int)$bead['id'] ?>">-</button>
              <button
                type="button"
                class="btn btn-primary"
                data-add-bead-id="<?= (int)$bead['id'] ?>"
                data-bead-nama="<?= htmlspecialchars($bead['nama']) ?>"
                data-bead-harga="<?= (int)$bead['harga_per_butir'] ?>"
                data-bead-hex="<?= htmlspecialchars($bead['warna_hex']) ?>"
              >+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Step 4 -->
    <div class="step-pane" data-step-pane="4">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        <div>
          <label style="font-size:0.78rem;">Ukiran Nama (+Rp 15.000)</label>
          <input type="text" id="engraving-input" maxlength="14" placeholder="Contoh: CLARISSA">
        </div>
        <div>
          <label style="font-size:0.78rem;">Inisial (+Rp 5.000/huruf)</label>
          <input type="text" id="initials-input" maxlength="3" placeholder="Contoh: CP">
        </div>
      </div>
      <div style="margin-top:14px;">
        <label style="font-size:0.78rem; font-weight:600;">Charm Vintage Coquette</label>
        <div style="display:flex; align-items:center; gap:12px; margin-top:6px;">
          <select id="charm-select" style="flex:1;">
            <option value="" data-nama="" data-harga="0">Tanpa Charm</option>
            <?php foreach ($charms as $ch): ?>
              <option
                value="<?= (int)$ch['id'] ?>"
                data-nama="<?= htmlspecialchars($ch['nama']) ?>"
                data-harga="<?= (int)$ch['harga'] ?>"
                data-gambar="<?= !empty($ch['gambar']) ? htmlspecialchars(baseUrl('assets/img/charms/' . $ch['gambar'])) : '' ?>"
              ><?= htmlspecialchars($ch['nama']) ?> (+<?= formatRupiah($ch['harga']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <!-- Gambar charm yang dipilih -->
          <div id="charm-thumb" style="width:56px; height:62px; flex-shrink:0; border:1px solid #E5CFD5; border-radius:10px; background:#FAF0F2; display:flex; align-items:center; justify-content:center;"></div>
        </div>
      </div>
      <div style="margin-top:18px; padding:14px; border-radius:12px; border:1px solid #E5CFD5; background:#FAF0F2; display:flex; align-items:center; gap:12px;">
        <svg viewBox="0 0 80 56" width="38" height="26" fill="none" aria-hidden="true" style="flex-shrink:0;">
          <path d="M38 24 C23 9, 6 12, 8 24 C10 35, 25 34, 38 25 Z" fill="#F4D2DC" stroke="#A86473" stroke-width="1.4" />
          <path d="M42 24 C57 9, 74 12, 72 24 C70 35, 55 34, 42 25 Z" fill="#F4D2DC" stroke="#A86473" stroke-width="1.4" />
          <ellipse cx="40" cy="24.5" rx="5.5" ry="4.5" fill="#FFFDFB" stroke="#C19E7B" stroke-width="1.3" />
        </svg>
        <p style="font-size:0.8rem; color:var(--ink-secondary);">
          Setiap sentuhan ukiran nama, huruf inisial, dan charm pita langsung memperbarui visualisasi di panel kiri secara real-time sebelum checkout.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Gambar charm + renderer gelang -->
<script src="<?= baseUrl('assets/js/lydi-preview.js') ?>"></script>
<script>
(function () {
  var svg      = document.getElementById('live-svg-preview');
  var sizeSel  = document.getElementById('size-select');
  var charmSel = document.getElementById('charm-select');
  var thumb    = document.getElementById('charm-thumb');
  var engr     = document.getElementById('engraving-input');
  var inis     = document.getElementById('initials-input');
  var resetBtn = document.getElementById('btn-reset-isian');
  var beads    = []; // urutan manik: [{id, hex}]

  function maxBeads() {
    var o = sizeSel.selectedOptions[0];
    return parseInt(o && o.dataset.maxBeads, 10) || 17;
  }
  function matHex() {
    var a = document.querySelector('[data-mat].active') || document.querySelector('[data-mat]');
    return a ? a.dataset.hex : '#B8CDB5';
  }
  function jenisAktif() {
    var a = document.querySelector('[data-acc].active');
    return a ? (a.dataset.acc + ' ' + a.dataset.nama) : '<?= htmlspecialchars($selectedJenis) ?>';
  }
  function charmImg() {
    var o = charmSel.selectedOptions[0];
    return o ? (o.dataset.gambar || '') : '';
  }
  function charmNama() {
    var o = charmSel.selectedOptions[0];
    return o ? (o.dataset.nama || '') : '';
  }

  function draw() {
    if (beads.length > maxBeads()) beads = beads.slice(0, maxBeads());
    LydiPreview.render(svg, {
      jenis: jenisAktif(),
      beads: beads.map(function (b) { return b.hex; }),
      matHex: matHex(),
      engraving: engr.value.trim(),
      initials: inis.value,
      charmNama: charmNama(),
      charmImg: charmImg()
    });
    thumb.innerHTML = LydiPreview.charmSVG(charmNama(), charmImg()) ||
      '<span style="font-size:0.65rem; color:#A86473; text-align:center;">Tanpa<br>charm</span>';
    if (thumb.firstElementChild && thumb.firstElementChild.tagName === 'svg') {
      thumb.firstElementChild.setAttribute('width', '46');
      thumb.firstElementChild.setAttribute('height', '52');
    }
  }
  // setTimeout 0: jalankan setelah script lain selesai mengubah tampilan
  function later() { setTimeout(draw, 0); }

  function resetIsian() {
    // Kosongkan seluruh manik pada produk aktif (produk & ukuran tetap dipertahankan)
    for (var i = beads.length - 1; i >= 0; i--) {
      var remBtn = document.querySelector('[data-rem-bead-id="' + beads[i].id + '"]');
      if (remBtn) remBtn.click();
    }
    beads = [];

    // Kosongkan ukiran nama & inisial
    if (engr) {
      engr.value = '';
      engr.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (inis) {
      inis.value = '';
      inis.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Kembalikan charm ke "Tanpa Charm"
    if (charmSel) {
      charmSel.value = '';
      charmSel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    later();
  }

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-bead-id]');
    var rem = e.target.closest('[data-rem-bead-id]');
    if (add) {
      if (beads.length < maxBeads()) {
        beads.push({ id: add.dataset.addBeadId, hex: add.dataset.beadHex });
      }
    } else if (rem) {
      for (var i = beads.length - 1; i >= 0; i--) {
        if (beads[i].id === rem.dataset.remBeadId) { beads.splice(i, 1); break; }
      }
    }
    later();
  });

  if (resetBtn) {
    resetBtn.addEventListener('click', resetIsian);
  }

  [sizeSel, charmSel].forEach(function (el) { el.addEventListener('change', later); });
  [engr, inis].forEach(function (el) { el.addEventListener('input', later); });

  // Kalau script lama menimpa gambar, gambar ulang
  new MutationObserver(function () {
    if (!svg.querySelector('#lydi-disc')) draw();
  }).observe(svg, { childList: true });

  draw();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>