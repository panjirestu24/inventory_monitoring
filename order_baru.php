<?php
require_once 'auth_check.php';
require_once 'helpers.php';
$userRole = getUserRole();
$userInfo = getUserInfo();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Input Order Baru — PrintTrack</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <link rel="stylesheet" href="css/app.css?v=<?= time() ?>" />
  <style>
    /* ---- Print styles ---- */
    @media print {
      .sidebar, .header, .no-print { display: none !important; }
      .main-wrapper { margin-left: 0 !important; }
      body { background: white !important; color: #000 !important; }
      .nota-wrap.card {
        background: #fff !important; border: 1px solid #ccc !important;
        box-shadow: none !important; color: #000 !important;
      }
      .nota-wrap * { color: #000 !important; border-color: #ccc !important; background: transparent !important; }
      .form-nota-grid > div:first-child { display: none !important; }
      .form-nota-grid { display: block !important; }
      #nota-wrapper { width: 100% !important; max-width: 82mm !important; margin: 0 auto !important; }
    }
    .print-only { display: none; }

    /* ---- Two column layout ---- */
    .form-nota-grid {
      display: grid;
      grid-template-columns: 1fr 420px;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .form-nota-grid { grid-template-columns: 1fr; }
    }

    /* ---- Section header ---- */
    .section-title {
      font-size: 12px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.5px; color: var(--text-muted);
      margin-bottom: 14px; padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 8px;
    }
    .section-title svg { width: 14px; height: 14px; }
  </style>
</head>
<body>
<div class="app-layout">

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">🖨️</div>
      <div class="logo-text">
        PrintTrack
        <span>Inventory & Monitoring</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-group">
        <div class="nav-group-label">Utama</div>
        <div class="nav-item" onclick="location.href='index.php#dashboard'">
          <i data-feather="grid"></i> Dashboard
        </div>
        <div class="nav-item" onclick="location.href='index.php#monitoring'">
          <i data-feather="activity"></i> Monitoring Realtime
        </div>
      </div>
      <div class="nav-group">
        <div class="nav-group-label">Inventory</div>
        <div class="nav-item" onclick="location.href='index.php#items'">
          <i data-feather="package"></i> Bahan Baku
        </div>
        <div class="nav-item" onclick="location.href='index.php#stock-mutation'">
          <i data-feather="repeat"></i> Mutasi Stok
        </div>
      </div>
      <div class="nav-group">
        <div class="nav-group-label">Produksi</div>
        <div class="nav-item active">
          <i data-feather="plus-circle"></i> Input Order Baru
        </div>
        <div class="nav-item" onclick="location.href='index.php#orders'">
          <i data-feather="file-text"></i> Order Cetak
        </div>
        <div class="nav-item" onclick="location.href='index.php#deliveries'">
          <i data-feather="truck"></i> Pengiriman
        </div>
        <div class="nav-item" onclick="location.href='index.php#machines'">
          <i data-feather="cpu"></i> Mesin
        </div>
        <div class="nav-item" onclick="location.href='index.php#customers'">
          <i data-feather="users"></i> Pelanggan
        </div>
      </div>
      <div class="nav-group">
        <div class="nav-group-label">Laporan</div>
        <div class="nav-item" onclick="location.href='index.php#reports'">
          <i data-feather="bar-chart-2"></i> Laporan
        </div>
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($userInfo['name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($userInfo['name']) ?></div>
          <div class="user-role"><?= ucfirst($userRole) ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary btn-sm" style="margin-top:12px;width:100%;text-align:center;text-decoration:none">
        <i data-feather="log-out"></i> Logout
      </a>
    </div>
  </aside>

  <!-- ========== MAIN ========== -->
  <div class="main-wrapper">
    <header class="header no-print">
      <div class="header-title">
        Input Order Baru <span>Isi data pelanggan & pesanan, nota langsung tercetak</span>
      </div>
      <div class="header-actions">
        <a href="track.php" target="_blank" class="btn btn-secondary btn-sm">
          <i data-feather="external-link"></i> Halaman Tracking
        </a>
        <a href="index.php" class="btn btn-secondary btn-sm">
          <i data-feather="arrow-left"></i> Kembali
        </a>
      </div>
    </header>

    <main class="page-content">

      <div class="form-nota-grid">
        <!-- ====== KOLOM KIRI: FORM ====== -->
        <div class="no-print">

          <!-- Data Pelanggan -->
          <div class="card" style="margin-bottom:20px">
            <div class="section-title"><i data-feather="user"></i> Data Pelanggan</div>

            <!-- Autocomplete search -->
            <div class="form-group" style="position:relative">
              <label class="form-label">Cari Pelanggan Lama (opsional)</label>
          <div class="search-box" style="margin-bottom:0">
            <i data-feather="search"></i>
            <input type="text" id="cust-search" placeholder="Ketik nama atau no. HP..."
              oninput="searchCustomer(this.value)" autocomplete="off" />
          </div>
          <div id="cust-dropdown" style="
            display:none;position:absolute;top:100%;left:0;right:0;z-index:200;
            background:var(--bg-card);border:1px solid var(--border);
            border-radius:var(--radius-sm);max-height:200px;overflow-y:auto;
            box-shadow:var(--shadow);margin-top:4px
          "></div>
        </div>
        <div class="divider"></div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nama Pelanggan *</label>
            <input class="form-control" id="cust-name" required placeholder="Budi Santoso" />
          </div>
          <div class="form-group">
            <label class="form-label">No. HP *</label>
            <input class="form-control" id="cust-phone" required placeholder="0812-3456-7890" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Kota</label>
            <input class="form-control" id="cust-city" placeholder="Jakarta" />
          </div>
          <div class="form-group">
            <label class="form-label">Alamat</label>
            <input class="form-control" id="cust-address" placeholder="Jl. Merdeka No. 1..." />
          </div>
        </div>
      </div>

      <!-- Detail Pesanan -->
      <div class="card" style="margin-bottom:20px">
        <div class="section-title"><i data-feather="file-text"></i> Detail Pesanan</div>
        <div class="form-group">
          <label class="form-label">Jenis / Judul Pekerjaan *</label>
          <input class="form-control" id="order-title" required
            placeholder="Contoh: Cetak Spanduk 3x1m Full Color" />
        </div>
        <div class="form-group">
          <label class="form-label">Spesifikasi / Keterangan</label>
          <textarea class="form-control" id="order-desc" rows="2"
            placeholder="Bahan, finishing, warna, dll..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Jumlah *</label>
            <input type="number" class="form-control" id="order-qty" min="1" value="1"
              oninput="hitungTotal()" />
          </div>
          <div class="form-group">
            <label class="form-label">Harga Satuan (Rp) *</label>
            <input type="number" class="form-control" id="order-price" min="0" value="0"
              oninput="hitungTotal()" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Diskon (Rp)</label>
            <input type="number" class="form-control" id="order-discount" min="0" value="0"
              oninput="hitungTotal()" />
          </div>
          <div class="form-group">
            <label class="form-label">PPN (%)</label>
            <input type="number" class="form-control" id="order-tax" min="0" max="100" value="11"
              oninput="hitungTotal()" />
          </div>
        </div>

        <!-- Kalkulasi -->
        <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px">
          <div class="flex justify-between mb-4" style="font-size:13px">
            <span style="color:var(--text-muted)">Subtotal</span>
            <span id="c-subtotal" style="font-weight:600">Rp 0</span>
          </div>
          <div class="flex justify-between mb-4" style="font-size:13px">
            <span style="color:var(--text-muted)">Diskon</span>
            <span id="c-discount" style="color:var(--danger)">- Rp 0</span>
          </div>
          <div class="flex justify-between mb-4" style="font-size:13px">
            <span style="color:var(--text-muted)">PPN</span>
            <span id="c-tax">+ Rp 0</span>
          </div>
          <div class="divider"></div>
          <div class="flex justify-between">
            <span style="font-weight:700;font-size:15px">TOTAL</span>
            <span id="c-total" style="font-weight:800;font-size:20px;color:var(--accent)">Rp 0</span>
          </div>
        </div>
      </div>

      <!-- Produksi -->
      <div class="card" style="margin-bottom:20px">
        <div class="section-title"><i data-feather="cpu"></i> Produksi</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Mesin</label>
            <select class="form-control" id="order-machine">
              <option value="">-- Pilih Mesin --</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Operator</label>
            <select class="form-control" id="order-operator">
              <option value="">-- Pilih Operator --</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Prioritas</label>
            <select class="form-control" id="order-priority">
              <option value="low">Rendah</option>
              <option value="normal" selected>Normal</option>
              <option value="high">Tinggi</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Jatuh Tempo *</label>
            <input type="date" class="form-control" id="order-due" required />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Catatan Tambahan</label>
          <textarea class="form-control" id="order-notes" rows="2"
            placeholder="Instruksi khusus, catatan finishing..."></textarea>
        </div>
      </div>

      <!-- Tombol -->
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button class="btn btn-primary" style="flex:1;justify-content:center;padding:12px"
          onclick="simpanOrder()" id="btn-simpan">
          <i data-feather="save"></i> Simpan & Tampilkan Nota
        </button>
        <button class="btn btn-secondary" onclick="resetForm()" style="padding:12px 20px">
          <i data-feather="refresh-cw"></i> Reset
        </button>
      </div>

    </div><!-- /kolom kiri -->

    <!-- ====== KOLOM KANAN: NOTA ====== -->
    <div id="nota-wrapper">
      <!-- Placeholder sebelum order disimpan -->
      <div id="nota-placeholder" class="card" style="text-align:center;padding:48px 24px">
        <i data-feather="printer" style="width:48px;height:48px;stroke:var(--text-muted);margin-bottom:16px"></i>
        <div style="font-size:15px;font-weight:600;color:var(--text-secondary);margin-bottom:8px">Nota Belum Tersedia</div>
        <div style="font-size:13px;color:var(--text-muted)">Isi form & klik "Simpan" untuk generate nota otomatis</div>
      </div>

      <!-- Nota (hidden until order saved) -->
      <div id="nota-content" style="display:none">
        <div class="nota-wrap card" id="nota-print" style="font-family:'Inter',sans-serif;padding:28px;line-height:1.6">

          <!-- Header nota -->
          <div style="text-align:center;border-bottom:2px solid var(--border);padding-bottom:16px;margin-bottom:16px">
            <div style="font-size:20px;font-weight:800;color:var(--primary)">🖨️ PrintTrack</div>
            <div style="font-size:11px;color:var(--text-muted)">Sistem Inventory & Monitoring Percetakan</div>
          </div>

          <div style="text-align:center;margin-bottom:18px">
            <div style="font-size:13px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px">NOTA PESANAN</div>
            <div id="nota-order-num" style="font-size:26px;font-weight:800;font-family:monospace;color:var(--accent);margin-top:4px"></div>
            <div id="nota-tanggal" style="font-size:12px;color:var(--text-muted);margin-top:2px"></div>
          </div>

          <!-- Data pelanggan -->
          <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Data Pelanggan</div>
            <table style="width:100%;font-size:13px;border-collapse:collapse">
              <tr>
                <td style="color:var(--text-muted);width:90px;padding:3px 0">Nama</td>
                <td style="font-weight:600;color:var(--text-primary)">: <span id="nota-cust-name"></span></td>
              </tr>
              <tr>
                <td style="color:var(--text-muted);padding:3px 0">No. HP</td>
                <td style="font-weight:600;color:var(--text-primary)">: <span id="nota-cust-phone"></span></td>
              </tr>
              <tr id="nota-cust-city-row">
                <td style="color:var(--text-muted);padding:3px 0">Kota</td>
                <td style="color:var(--text-secondary)">: <span id="nota-cust-city"></span></td>
              </tr>
            </table>
          </div>

          <!-- Detail pesanan -->
          <div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px">Detail Pesanan</div>
            <table style="width:100%;font-size:13px;border-collapse:collapse">
              <tr>
                <td style="color:var(--text-muted);width:90px;padding:3px 0">Pesanan</td>
                <td style="font-weight:600;color:var(--text-primary)">: <span id="nota-title"></span></td>
              </tr>
              <tr id="nota-desc-row">
                <td style="color:var(--text-muted);padding:3px 0;vertical-align:top">Spesifikasi</td>
                <td style="color:var(--text-secondary)">: <span id="nota-desc"></span></td>
              </tr>
              <tr>
                <td style="color:var(--text-muted);padding:3px 0">Jumlah</td>
                <td style="color:var(--text-secondary)">: <span id="nota-qty"></span></td>
              </tr>
              <tr>
                <td style="color:var(--text-muted);padding:3px 0">Harga/pcs</td>
                <td style="color:var(--text-secondary)">: <span id="nota-price"></span></td>
              </tr>
            </table>
          </div>

          <!-- Rincian harga -->
          <div style="border-top:1px dashed var(--border);padding-top:12px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
              <span style="color:var(--text-muted)">Subtotal</span>
              <span id="nota-subtotal"></span>
            </div>
            <div id="nota-discount-row" style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
              <span style="color:var(--text-muted)">Diskon</span>
              <span id="nota-discount" style="color:var(--danger)"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:10px">
              <span style="color:var(--text-muted)">PPN (<span id="nota-tax-pct"></span>%)</span>
              <span id="nota-tax-amt"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;border-top:2px solid var(--border);padding-top:10px">
              <span>TOTAL</span>
              <span id="nota-total" style="color:var(--success)"></span>
            </div>
          </div>

          <!-- Jatuh tempo & QR info -->
          <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px;font-size:12px">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="color:var(--text-muted)">Jatuh Tempo</span>
              <span id="nota-due" style="font-weight:600"></span>
            </div>
            <div id="nota-machine-row" style="display:flex;justify-content:space-between;margin-bottom:4px">
              <span style="color:var(--text-muted)">Mesin</span>
              <span id="nota-machine"></span>
            </div>
            <div style="display:flex;justify-content:space-between">
              <span style="color:var(--text-muted)">Prioritas</span>
              <span id="nota-priority"></span>
            </div>
          </div>

          <!-- Tracking info -->
          <div style="border:1px dashed rgba(99,102,241,0.4);border-radius:var(--radius-sm);padding:12px;text-align:center">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px">Cek status pesanan Anda di:</div>
            <div style="font-size:12px;font-weight:600;color:var(--accent)">
              localhost/inventory_monitoring/track.php
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Masukkan nomor order:</div>
            <div id="nota-order-num-2" style="font-size:16px;font-weight:800;font-family:monospace;color:var(--primary);margin-top:2px"></div>
          </div>

          <!-- Tanda tangan -->
          <div style="display:flex;justify-content:space-between;margin-top:24px;font-size:12px">
            <div style="text-align:center">
              <div style="color:var(--text-muted);margin-bottom:40px">Kasir</div>
              <div style="border-top:1px solid var(--border);padding-top:6px;min-width:100px">
                (<?= htmlspecialchars($userInfo['name']) ?>)
              </div>
            </div>
            <div style="text-align:center">
              <div style="color:var(--text-muted);margin-bottom:40px">Pelanggan</div>
              <div style="border-top:1px solid var(--border);padding-top:6px;min-width:100px">
                (________________)
              </div>
            </div>
          </div>

        </div><!-- /nota-print -->

        <!-- Tombol aksi nota -->
        <div style="display:flex;gap:10px;margin-top:14px" class="no-print">
          <button class="btn btn-primary" style="flex:1;justify-content:center" onclick="window.print()">
            <i data-feather="printer"></i> Cetak Nota
          </button>
          <button class="btn btn-secondary" onclick="orderBaru()" style="flex:1;justify-content:center">
            <i data-feather="plus"></i> Order Baru
          </button>
        </div>
      </div><!-- /nota-content -->
    </div><!-- /nota-wrapper -->

  </div><!-- /form-nota-grid -->

    </main>
  </div><!-- /.main-wrapper -->
</div><!-- /.app-layout -->

<div class="toast-container" id="toast-container"></div>

<script>
// ============================================================
// ORDER BARU — standalone script
// ============================================================
const API = 'api/';
let searchTimeout = null;
let selectedCustomerId = null;

// ---- Init ----
document.addEventListener('DOMContentLoaded', async () => {
  feather.replace();

  // Set default due date = 7 hari dari sekarang
  const due = new Date();
  due.setDate(due.getDate() + 7);
  document.getElementById('order-due').value = due.toISOString().slice(0,10);

  // Load mesin & operator
  const [machs, ops] = await Promise.all([
    fetch('api/machines.php?action=list').then(r => r.json()),
    fetch('api/dashboard.php?action=operators').then(r => r.json()),
  ]);

  const machSel = document.getElementById('order-machine');
  (machs?.data || []).forEach(m => {
    machSel.innerHTML += `<option value="${m.id}">${m.name}</option>`;
  });

  const opSel = document.getElementById('order-operator');
  (ops?.data || []).forEach(o => {
    opSel.innerHTML += `<option value="${o.id}">${o.name}</option>`;
  });

  hitungTotal();

  // Tutup dropdown saat klik di luar
  document.addEventListener('click', e => {
    if (!e.target.closest('#cust-search') && !e.target.closest('#cust-dropdown')) {
      document.getElementById('cust-dropdown').style.display = 'none';
    }
  });
});

// ---- Autocomplete pelanggan ----
function searchCustomer(q) {
  clearTimeout(searchTimeout);
  if (q.length < 2) {
    document.getElementById('cust-dropdown').style.display = 'none';
    return;
  }
  searchTimeout = setTimeout(async () => {
    const r    = await fetch(`api/customers.php?action=search&q=${encodeURIComponent(q)}`);
    const data = await r.json();
    renderDropdown(data.data || []);
  }, 300);
}

function renderDropdown(customers) {
  const dd = document.getElementById('cust-dropdown');
  if (!customers.length) { dd.style.display = 'none'; return; }
  dd.innerHTML = customers.map(c => `
    <div onclick="pilihPelanggan(${c.id},'${escapeJs(c.name)}','${escapeJs(c.phone)}','${escapeJs(c.city||'')}','${escapeJs(c.address||'')}')"
      style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);
             display:flex;justify-content:space-between;align-items:center;transition:background 0.15s"
      onmouseover="this.style.background='var(--bg-card-hover)'"
      onmouseout="this.style.background=''">
      <div>
        <div style="font-weight:600;color:var(--text-primary)">${c.name}</div>
        <div style="font-size:11px;color:var(--text-muted)">${c.phone}${c.city ? ' · ' + c.city : ''}</div>
      </div>
      <div style="font-size:11px;color:var(--accent)">${c.total_orders} order</div>
    </div>
  `).join('');
  dd.style.display = 'block';
}

function pilihPelanggan(id, name, phone, city, address) {
  selectedCustomerId = id;
  document.getElementById('cust-name').value    = name;
  document.getElementById('cust-phone').value   = phone;
  document.getElementById('cust-city').value    = city;
  document.getElementById('cust-address').value = address;
  document.getElementById('cust-search').value  = name;
  document.getElementById('cust-dropdown').style.display = 'none';
  showToast('Pelanggan dipilih: ' + name, 'success');
}

function escapeJs(s) {
  return String(s).replace(/'/g, "\\'").replace(/\n/g, '');
}

// ---- Hitung total ----
function hitungTotal() {
  const qty      = parseFloat(document.getElementById('order-qty').value)      || 0;
  const price    = parseFloat(document.getElementById('order-price').value)    || 0;
  const discount = parseFloat(document.getElementById('order-discount').value) || 0;
  const tax      = parseFloat(document.getElementById('order-tax').value)      || 0;
  const sub      = qty * price;
  const taxAmt   = (sub - discount) * (tax / 100);
  const total    = sub - discount + taxAmt;
  document.getElementById('c-subtotal').textContent  = formatRp(sub);
  document.getElementById('c-discount').textContent  = '- ' + formatRp(discount);
  document.getElementById('c-tax').textContent       = '+ ' + formatRp(taxAmt);
  document.getElementById('c-total').textContent     = formatRp(total);
}

function formatRp(n) {
  return 'Rp ' + (parseFloat(n)||0).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0});
}

function formatTgl(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'});
}

// ---- Simpan order ----
async function simpanOrder() {
  const name  = document.getElementById('cust-name').value.trim();
  const phone = document.getElementById('cust-phone').value.trim();
  const title = document.getElementById('order-title').value.trim();
  const qty   = parseFloat(document.getElementById('order-qty').value)   || 0;
  const price = parseFloat(document.getElementById('order-price').value) || 0;
  const due   = document.getElementById('order-due').value;

  if (!name)  { showToast('Nama pelanggan wajib diisi', 'warning'); return; }
  if (!phone) { showToast('No. HP pelanggan wajib diisi', 'warning'); return; }
  if (!title) { showToast('Judul pesanan wajib diisi', 'warning'); return; }
  if (qty < 1){ showToast('Jumlah minimal 1', 'warning'); return; }
  if (price < 1){ showToast('Harga satuan wajib diisi', 'warning'); return; }
  if (!due)   { showToast('Jatuh tempo wajib diisi', 'warning'); return; }

  const btn = document.getElementById('btn-simpan');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Menyimpan...';
  feather.replace();

  const payload = {
    customer_name:    name,
    customer_phone:   phone,
    customer_city:    document.getElementById('cust-city').value,
    customer_address: document.getElementById('cust-address').value,
    title,
    description:  document.getElementById('order-desc').value,
    quantity:     qty,
    unit_price:   price,
    discount:     parseFloat(document.getElementById('order-discount').value) || 0,
    tax:          parseFloat(document.getElementById('order-tax').value) || 11,
    machine_id:   document.getElementById('order-machine').value || null,
    operator_id:  document.getElementById('order-operator').value || null,
    priority:     document.getElementById('order-priority').value,
    due_date:     due,
    notes:        document.getElementById('order-notes').value,
  };

  try {
    const res  = await fetch('api/orders.php?action=create_with_customer', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      showToast('Order ' + data.order_number + ' berhasil disimpan!', 'success');
      tampilkanNota(data.data, data.order_number);
    } else {
      showToast(data.message || 'Gagal menyimpan order', 'error');
    }
  } catch(e) {
    showToast('Koneksi bermasalah', 'error');
    console.error(e);
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="save"></i> Simpan & Tampilkan Nota';
  feather.replace();
}

// ---- Tampilkan Nota ----
function tampilkanNota(o, orderNum) {
  const qty      = parseFloat(o.quantity)    || 0;
  const price    = parseFloat(o.unit_price)  || 0;
  const discount = parseFloat(o.discount)    || 0;
  const tax      = parseFloat(o.tax)         || 0;
  const sub      = qty * price;
  const taxAmt   = (sub - discount) * (tax / 100);
  const total    = sub - discount + taxAmt;

  document.getElementById('nota-order-num').textContent  = orderNum;
  document.getElementById('nota-order-num-2').textContent = orderNum;
  document.getElementById('nota-tanggal').textContent    = 'Tanggal: ' + formatTgl(o.created_at || new Date().toISOString());
  document.getElementById('nota-cust-name').textContent  = o.customer_name;
  document.getElementById('nota-cust-phone').textContent = o.customer_phone || '—';
  document.getElementById('nota-cust-city').textContent  = o.customer_city  || '—';
  document.getElementById('nota-title').textContent      = o.title;
  document.getElementById('nota-desc').textContent       = o.description || '—';
  document.getElementById('nota-qty').textContent        = qty + ' pcs';
  document.getElementById('nota-price').textContent      = formatRp(price);
  document.getElementById('nota-subtotal').textContent   = formatRp(sub);
  document.getElementById('nota-discount').textContent   = '- ' + formatRp(discount);
  document.getElementById('nota-tax-pct').textContent    = tax;
  document.getElementById('nota-tax-amt').textContent    = '+ ' + formatRp(taxAmt);
  document.getElementById('nota-total').textContent      = formatRp(total);
  document.getElementById('nota-due').textContent        = formatTgl(o.due_date);
  document.getElementById('nota-machine').textContent    = o.machine_name || '—';
  document.getElementById('nota-priority').textContent   = {'low':'Rendah','normal':'Normal','high':'Tinggi','urgent':'URGENT'}[o.priority] || o.priority;

  // Sembunyikan baris kosong
  if (!o.description) document.getElementById('nota-desc-row').style.display = 'none';
  if (!o.customer_city) document.getElementById('nota-cust-city-row').style.display = 'none';
  if (discount <= 0) document.getElementById('nota-discount-row').style.display = 'none';
  if (!o.machine_name) document.getElementById('nota-machine-row').style.display = 'none';

  document.getElementById('nota-placeholder').style.display = 'none';
  document.getElementById('nota-content').style.display     = 'block';

  // Scroll ke nota di mobile
  if (window.innerWidth < 900) {
    document.getElementById('nota-content').scrollIntoView({behavior:'smooth', block:'start'});
  }
}

function orderBaru() {
  resetForm();
  document.getElementById('nota-content').style.display     = 'none';
  document.getElementById('nota-placeholder').style.display = 'block';
  window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
  document.getElementById('cust-name').value    = '';
  document.getElementById('cust-phone').value   = '';
  document.getElementById('cust-city').value    = '';
  document.getElementById('cust-address').value = '';
  document.getElementById('cust-search').value  = '';
  document.getElementById('order-title').value  = '';
  document.getElementById('order-desc').value   = '';
  document.getElementById('order-qty').value    = '1';
  document.getElementById('order-price').value  = '0';
  document.getElementById('order-discount').value = '0';
  document.getElementById('order-tax').value    = '11';
  document.getElementById('order-notes').value  = '';
  document.getElementById('order-machine').value  = '';
  document.getElementById('order-operator').value = '';
  document.getElementById('order-priority').value = 'normal';
  selectedCustomerId = null;
  hitungTotal();
}

// Toast (standalone — tidak pakai app.js toast agar tidak conflict)
function showToast(message, type = 'info') {
  const colors = {success:'var(--success)',error:'var(--danger)',warning:'var(--warning)',info:'var(--accent)'};
  const icons  = {success:'check-circle',error:'x-circle',warning:'alert-triangle',info:'info'};
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<i data-feather="${icons[type]}" style="width:16px;height:16px;stroke:${colors[type]};flex-shrink:0"></i><span>${message}</span>`;
  document.getElementById('toast-container').appendChild(el);
  feather.replace();
  setTimeout(() => { el.style.animation = 'slideOut 0.3s ease forwards'; setTimeout(() => el.remove(), 300); }, 4000);
}
</script>
</body>
</html>
