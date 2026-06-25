<!DOCTYPE html>
<?php
// track.php - Halaman publik untuk pelanggan tracking pesanan
// Tidak perlu login
?>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cek Status Pesanan — PrintTrack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <style>
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #8b5cf6;
      --accent: #06b6d4;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --bg-base: #0f0f1a;
      --bg-surface: #161627;
      --bg-card: #1e1e35;
      --bg-card-hover: #252542;
      --bg-input: #12121f;
      --border: rgba(99,102,241,0.15);
      --border-hover: rgba(99,102,241,0.4);
      --text-primary: #f1f5f9;
      --text-secondary: #94a3b8;
      --text-muted: #475569;
      --radius: 12px;
      --radius-sm: 8px;
      --radius-lg: 16px;
      --shadow: 0 4px 24px rgba(0,0,0,0.4);
      --transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 14px; }
    body {
      font-family: 'Inter', -apple-system, sans-serif;
      background: var(--bg-base);
      color: var(--text-primary);
      min-height: 100vh;
      line-height: 1.6;
    }

    /* ---- Header ---- */
    .top-bar {
      background: var(--bg-surface);
      border-bottom: 1px solid var(--border);
      padding: 0 24px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .top-bar-logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .top-bar-logo .logo-icon {
      width: 32px; height: 32px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
    }
    .top-bar-logo .logo-name { font-size: 15px; font-weight: 700; }
    .top-bar-logo .logo-name span { color: #a5b4fc; font-weight: 400; font-size: 12px; display: block; }
    .top-bar-right { font-size: 12px; color: var(--text-muted); }

    /* ---- Main ---- */
    .main {
      max-width: 800px;
      margin: 0 auto;
      padding: 40px 20px 60px;
    }

    /* ---- Hero ---- */
    .hero {
      text-align: center;
      margin-bottom: 40px;
    }
    .hero h1 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 8px;
      background: linear-gradient(135deg, #a5b4fc, var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero p {
      color: var(--text-muted);
      font-size: 14px;
    }

    /* ---- Search Box ---- */
    .search-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 28px;
      margin-bottom: 32px;
      box-shadow: var(--shadow);
    }
    .search-label {
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-secondary);
      margin-bottom: 10px;
      display: block;
    }
    .search-row {
      display: flex;
      gap: 10px;
    }
    .search-input {
      flex: 1;
      background: var(--bg-input);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text-primary);
      padding: 12px 16px;
      font-size: 15px;
      font-family: inherit;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      transition: var(--transition);
    }
    .search-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .search-input::placeholder { text-transform: none; font-weight: 400; letter-spacing: 0; color: var(--text-muted); }
    .btn-track {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(99,102,241,0.3);
    }
    .btn-track:hover { box-shadow: 0 6px 20px rgba(99,102,241,0.5); transform: translateY(-1px); }
    .btn-track:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
    .btn-track svg { width: 16px; height: 16px; }

    /* ---- Result ---- */
    #result { display: none; }
    #result.visible { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

    /* ---- Error ---- */
    .error-box {
      background: rgba(239,68,68,0.1);
      border: 1px solid rgba(239,68,68,0.3);
      border-radius: var(--radius);
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 14px;
      color: #f87171;
    }
    .error-box svg { width: 24px; height: 24px; flex-shrink: 0; }

    /* ---- Info Card ---- */
    .info-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 20px;
      box-shadow: var(--shadow);
    }
    .info-card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 18px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .order-number {
      font-family: monospace;
      font-size: 20px;
      font-weight: 800;
      color: var(--accent);
      letter-spacing: 1px;
    }
    .order-title {
      font-size: 16px;
      font-weight: 600;
      margin-top: 4px;
    }
    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 14px;
      margin-top: 16px;
    }
    .info-item label {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      display: block;
      margin-bottom: 4px;
    }
    .info-item span {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-primary);
    }

    /* ---- Badge ---- */
    .badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 4px 12px; border-radius: 20px;
      font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.4px;
    }
    .badge-pending    { background: rgba(148,163,184,0.15); color: #94a3b8; border: 1px solid rgba(148,163,184,0.2); }
    .badge-in_progress{ background: rgba(245,158,11,0.15);  color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }
    .badge-completed  { background: rgba(16,185,129,0.15);  color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .badge-cancelled  { background: rgba(239,68,68,0.15);   color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
    .badge-confirmed  { background: rgba(59,130,246,0.15);  color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }
    .badge-quality_check{ background: rgba(6,182,212,0.15); color: #22d3ee; border: 1px solid rgba(6,182,212,0.2); }
    .badge-prepared   { background: rgba(139,92,246,0.15);  color: #c4b5fd; border: 1px solid rgba(139,92,246,0.2); }
    .badge-shipping   { background: rgba(6,182,212,0.15);   color: #22d3ee; border: 1px solid rgba(6,182,212,0.2); }
    .badge-arrived    { background: rgba(16,185,129,0.15);  color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
    .badge-received   { background: rgba(16,185,129,0.3);   color: #6ee7b7; border: 1px solid rgba(16,185,129,0.5); }

    /* ---- Timeline ---- */
    .section-title {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .section-title::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .timeline {
      position: relative;
      padding-left: 28px;
    }
    .timeline::before {
      content: '';
      position: absolute;
      left: 10px;
      top: 10px;
      bottom: 10px;
      width: 2px;
      background: var(--border);
    }
    .timeline-item {
      position: relative;
      margin-bottom: 24px;
      opacity: 0.4;
      transition: var(--transition);
    }
    .timeline-item.done { opacity: 1; }
    .timeline-item.active { opacity: 1; }
    .timeline-dot {
      position: absolute;
      left: -24px;
      top: 4px;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: var(--bg-card);
      border: 2px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1;
    }
    .timeline-dot svg { width: 10px; height: 10px; }
    .timeline-item.done .timeline-dot {
      background: rgba(16,185,129,0.2);
      border-color: var(--success);
    }
    .timeline-item.done .timeline-dot svg { stroke: var(--success); }
    .timeline-item.active .timeline-dot {
      background: rgba(99,102,241,0.2);
      border-color: var(--primary);
      box-shadow: 0 0 10px rgba(99,102,241,0.4);
      animation: pulseDot 1.5s infinite;
    }
    .timeline-item.active .timeline-dot svg { stroke: var(--primary); }
    @keyframes pulseDot {
      0%,100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.4); }
      50% { box-shadow: 0 0 0 6px rgba(99,102,241,0); }
    }
    .timeline-label {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 2px;
    }
    .timeline-desc {
      font-size: 12px;
      color: var(--text-muted);
    }
    .timeline-time {
      font-size: 11px;
      color: var(--accent);
      margin-top: 4px;
    }

    /* ---- Delivery Card ---- */
    .delivery-card {
      background: var(--bg-card);
      border: 1px solid rgba(6,182,212,0.2);
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: var(--shadow);
    }
    .delivery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 14px;
      margin-top: 16px;
    }

    /* ---- No Delivery ---- */
    .no-delivery {
      background: var(--bg-surface);
      border: 1px dashed var(--border);
      border-radius: var(--radius);
      padding: 28px;
      text-align: center;
      color: var(--text-muted);
    }
    .no-delivery svg { width: 36px; height: 36px; margin-bottom: 10px; }
    .no-delivery p { font-size: 13px; }

    /* ---- Footer ---- */
    .page-footer {
      text-align: center;
      margin-top: 48px;
      font-size: 12px;
      color: var(--text-muted);
    }

    /* ---- Loading ---- */
    .loading { display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 13px; padding: 12px 0; }
    .spinner {
      width: 18px; height: 18px;
      border: 2px solid var(--border);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 600px) {
      .search-row { flex-direction: column; }
      .hero h1 { font-size: 22px; }
    }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
  <div class="top-bar-logo">
    <div class="logo-icon">🖨️</div>
    <div class="logo-name">
      PrintTrack
      <span>Inventory & Monitoring Percetakan</span>
    </div>
  </div>
  <div class="top-bar-right">
    <a href="login.php" style="color:var(--primary);text-decoration:none;font-weight:600">Login Staff →</a>
  </div>
</div>

<!-- Main Content -->
<div class="main">

  <!-- Hero -->
  <div class="hero">
    <h1>Cek Status Pesanan</h1>
    <p>Masukkan nomor order yang kamu terima dari kami untuk melihat status terkini pesanan.</p>
  </div>

  <!-- Search Card -->
  <div class="search-card">
    <label class="search-label">Nomor Order</label>
    <div class="search-row">
      <input
        type="text"
        id="order-input"
        class="search-input"
        placeholder="Contoh: ORD-2506-0001"
        maxlength="30"
        autocomplete="off"
        onkeydown="if(event.key==='Enter') trackOrder()"
      />
      <button class="btn-track" id="btn-track" onclick="trackOrder()">
        <i data-feather="search"></i>
        Cek Pesanan
      </button>
    </div>
  </div>

  <!-- Result Area -->
  <div id="result"></div>

  <div class="page-footer">
    &copy; <?= date('Y') ?> PrintTrack — Sistem Inventory & Monitoring Percetakan
  </div>
</div>

<script>
feather.replace();

// ============================================================
// REALTIME POLLING — setiap 4 detik
// ============================================================
let pollingInterval  = null;
let currentOrderNum  = null;
let lastOrderStatus  = null;
let lastDelivStatus  = null;

function startPolling(orderNum) {
  stopPolling();
  currentOrderNum = orderNum;
  pollingInterval = setInterval(pollStatus, 4000);
}

function stopPolling() {
  if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; }
}

async function pollStatus() {
  if (!currentOrderNum) return;
  try {
    const res  = await fetch(`api/deliveries.php?action=track&order_number=${encodeURIComponent(currentOrderNum)}`);
    const data = await res.json();
    if (!data.success) return;

    const d              = data.data;
    const newOrder       = d.order_status;
    const newDeliv       = d.delivery_status || null;
    const statusChanged  = (newOrder !== lastOrderStatus || newDeliv !== lastDelivStatus);

    if (statusChanged) {
      lastOrderStatus = newOrder;
      lastDelivStatus = newDeliv;
      renderResult(d);
      showStatusToast(newOrder, newDeliv);
    }

    // Hentikan polling kalau sudah status final
    const isFinal = newOrder === 'cancelled' ||
                   (newOrder === 'completed' && newDeliv === 'received');
    if (isFinal) { stopPolling(); setPollingBadge(false); }
    else { setPollingBadge(true); }

  } catch (e) { setPollingBadge(false, 'Offline'); }
}

function setPollingBadge(live, text) {
  const badge = document.getElementById('polling-badge');
  const dot   = document.getElementById('polling-dot');
  if (!badge) return;
  badge.textContent   = text || (live ? ' Live' : ' Offline');
  badge.style.color   = live ? '#34d399' : '#94a3b8';
  badge.style.borderColor = live ? 'rgba(16,185,129,0.3)' : 'rgba(148,163,184,0.2)';
  badge.style.background  = live ? 'rgba(16,185,129,0.1)' : 'rgba(148,163,184,0.08)';
  if (dot) {
    dot.style.background = live ? '#34d399' : '#94a3b8';
    dot.style.animationName = live ? 'pulseDot' : 'none';
  }
}

function showStatusToast(orderStatus, delivStatus) {
  const old = document.getElementById('status-toast');
  if (old) old.remove();
  const label = delivStatus
    ? `Pengiriman: <strong>${delivLabel(delivStatus)}</strong>`
    : `Status order: <strong>${orderStatusLabel(orderStatus)}</strong>`;
  const t = document.createElement('div');
  t.id = 'status-toast';
  t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:999;
    background:#1e1e35;border:1px solid rgba(16,185,129,0.4);border-radius:10px;
    padding:14px 18px;display:flex;align-items:center;gap:10px;
    font-size:13px;color:#f1f5f9;box-shadow:0 4px 24px rgba(0,0,0,0.5);
    animation:slideInToast .3s ease`;
  t.innerHTML = `<span style="font-size:18px">🔔</span><span>${label}</span>`;
  document.body.appendChild(t);
  setTimeout(() => {
    t.style.animation = 'slideOutToast .3s ease forwards';
    setTimeout(() => t.remove(), 300);
  }, 4000);
}

// Hentikan polling saat tab tersembunyi, lanjutkan saat kembali
window.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    stopPolling();
  } else if (currentOrderNum && lastOrderStatus) {
    const isFinal = lastOrderStatus === 'cancelled' ||
                   (lastOrderStatus === 'completed' && lastDelivStatus === 'received');
    if (!isFinal) startPolling(currentOrderNum);
  }
});
window.addEventListener('beforeunload', stopPolling);

// ============================================================
// TRACK ORDER
// ============================================================
async function trackOrder() {
  const input    = document.getElementById('order-input');
  const btn      = document.getElementById('btn-track');
  const result   = document.getElementById('result');
  const orderNum = input.value.trim().toUpperCase();

  if (!orderNum) { input.focus(); return; }

  // Reset polling sebelumnya
  stopPolling();
  lastOrderStatus = null;
  lastDelivStatus = null;

  btn.disabled = true;
  result.className = '';
  result.innerHTML = '<div class="loading"><div class="spinner"></div> Mencari pesanan...</div>';
  result.classList.add('visible');

  try {
    const res  = await fetch(`api/deliveries.php?action=track&order_number=${encodeURIComponent(orderNum)}`);
    const data = await res.json();

    if (!data.success) {
      result.innerHTML = `
        <div class="error-box">
          <i data-feather="alert-circle"></i>
          <div>
            <div style="font-weight:700;margin-bottom:4px">Pesanan tidak ditemukan</div>
            <div style="font-size:12px">Nomor order "<strong>${orderNum}</strong>" tidak ada dalam sistem.</div>
          </div>
        </div>`;
      feather.replace();
      btn.disabled = false;
      return;
    }

    const d = data.data;
    lastOrderStatus = d.order_status;
    lastDelivStatus = d.delivery_status || null;
    renderResult(d);

    // Mulai polling kalau belum final
    const isFinal = d.order_status === 'cancelled' ||
                   (d.order_status === 'completed' && d.delivery_status === 'received');
    if (!isFinal) startPolling(orderNum);

  } catch (e) {
    result.innerHTML = `
      <div class="error-box">
        <i data-feather="wifi-off"></i>
        <div>
          <div style="font-weight:700;margin-bottom:4px">Koneksi bermasalah</div>
          <div style="font-size:12px">Gagal terhubung ke server. Coba lagi beberapa saat.</div>
        </div>
      </div>`;
    feather.replace();
  }

  btn.disabled = false;
}

function renderResult(d) {
  const result = document.getElementById('result');

  // ── Polling badge ──────────────────────────────────────────
  const isFinal = d.order_status === 'cancelled' ||
                 (d.order_status === 'completed' && d.delivery_status === 'received');
  const pollingBadge = !isFinal ? `
    <div style="display:flex;align-items:center;justify-content:flex-end;margin-bottom:12px">
      <div id="polling-badge" style="display:inline-flex;align-items:center;gap:6px;
        padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;
        background:rgba(16,185,129,0.1);color:#34d399;border:1px solid rgba(16,185,129,0.3)">
        <span id="polling-dot" style="width:7px;height:7px;border-radius:50%;
          background:#34d399;flex-shrink:0;animation:pulseDot 1.5s infinite"></span>
        Live
      </div>
    </div>` : '';

  // ── Order Info ─────────────────────────────────────────────
  const infoHtml = `
    <div class="info-card">
      <div class="info-card-header">
        <div>
          <div class="order-number">${d.order_number}</div>
          <div class="order-title">${d.title}</div>
        </div>
        <span class="badge badge-${d.order_status}">${orderStatusLabel(d.order_status)}</span>
      </div>
      <div class="info-grid">
        <div class="info-item"><label>Pelanggan</label><span>${d.customer_name}</span></div>
        <div class="info-item"><label>Jumlah</label><span>${d.quantity ? parseInt(d.quantity).toLocaleString('id-ID') + ' pcs' : '—'}</span></div>
        <div class="info-item"><label>Jatuh Tempo</label><span>${formatDate(d.due_date)}</span></div>
        <div class="info-item"><label>Total</label><span style="color:#34d399">${formatCurrency(d.grand_total)}</span></div>
        ${d.order_notes ? `<div class="info-item" style="grid-column:1/-1"><label>Catatan</label><span>${d.order_notes}</span></div>` : ''}
      </div>
    </div>`;

  // ── Order Status Timeline ──────────────────────────────────
  const orderSteps = [
    { key:'pending',       label:'Pesanan Diterima',    desc:'Pesanan kamu sudah masuk ke sistem kami.',    icon:'file-text'   },
    { key:'confirmed',     label:'Dikonfirmasi',        desc:'Pesanan telah dikonfirmasi dan dijadwalkan.', icon:'check-square'},
    { key:'in_progress',   label:'Sedang Diproses',     desc:'Pesanan sedang dalam tahap produksi.',        icon:'settings'    },
    { key:'quality_check', label:'Pengecekan Kualitas', desc:'Pesanan melewati quality control.',           icon:'shield'      },
    { key:'completed',     label:'Selesai Diproduksi',  desc:'Produksi selesai, siap untuk pengiriman.',   icon:'package'     },
  ];
  const orderIdx = ['pending','confirmed','in_progress','quality_check','completed'].indexOf(d.order_status);
  const timelineHtml = orderSteps.map((s, i) => {
    const cls = i < orderIdx ? 'done' : i === orderIdx ? 'active' : '';
    return `<div class="timeline-item ${cls}">
      <div class="timeline-dot"><i data-feather="${s.icon}"></i></div>
      <div class="timeline-label">${s.label}</div>
      <div class="timeline-desc">${s.desc}</div>
    </div>`;
  }).join('');

  const orderCard = `
    <div class="info-card">
      <div class="section-title">Status Pesanan</div>
      <div class="timeline">${timelineHtml}</div>
    </div>`;

  // ── Delivery Info ──────────────────────────────────────────
  let deliveryHtml = '';
  if (d.order_status === 'completed' || d.delivery_id) {
    if (d.delivery_id) {
      const delivSteps = [
        { key:'prepared', label:'Disiapkan',        desc:'Paket sedang disiapkan untuk pengiriman.',  icon:'package'      },
        { key:'shipping', label:'Dalam Pengiriman', desc:'Paket sedang dalam perjalanan ke tujuan.',  icon:'truck'        },
        { key:'arrived',  label:'Tiba di Tujuan',   desc:'Paket telah sampai di lokasi tujuan.',      icon:'map-pin'      },
        { key:'received', label:'Diterima',         desc:'Paket telah diterima oleh penerima.',       icon:'check-circle' },
      ];
      const delivIdx = ['prepared','shipping','arrived','received'].indexOf(d.delivery_status);
      const delivTimeline = delivSteps.map((s, i) => {
        const cls = i < delivIdx ? 'done' : i === delivIdx ? 'active' : '';
        return `<div class="timeline-item ${cls}">
          <div class="timeline-dot"><i data-feather="${s.icon}"></i></div>
          <div class="timeline-label">${s.label}</div>
          <div class="timeline-desc">${s.desc}</div>
          ${i === delivIdx && s.key === 'arrived' && d.actual_arrival ? `<div class="timeline-time">⏰ ${formatDateTime(d.actual_arrival)}</div>` : ''}
        </div>`;
      }).join('');

      deliveryHtml = `
        <div class="delivery-card">
          <div class="section-title">Status Pengiriman</div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <span class="badge badge-${d.delivery_status}">${delivLabel(d.delivery_status)}</span>
            ${d.estimated_arrival ? `<div style="font-size:12px;color:var(--text-muted)">Estimasi tiba: <strong style="color:var(--text-secondary)">${formatDate(d.estimated_arrival)}</strong></div>` : ''}
          </div>
          <div class="timeline">${delivTimeline}</div>
          <div class="delivery-grid" style="border-top:1px solid var(--border);padding-top:16px;margin-top:8px">
            ${d.recipient_name    ? `<div class="info-item"><label>Penerima</label><span>${d.recipient_name}</span></div>` : ''}
            ${d.recipient_phone   ? `<div class="info-item"><label>Telepon</label><span>${d.recipient_phone}</span></div>` : ''}
            ${d.destination_city  ? `<div class="info-item"><label>Kota Tujuan</label><span>${d.destination_city}</span></div>` : ''}
            ${d.destination_address ? `<div class="info-item" style="grid-column:1/-1"><label>Alamat</label><span>${d.destination_address}</span></div>` : ''}
            ${d.delivery_notes    ? `<div class="info-item" style="grid-column:1/-1"><label>Catatan</label><span>${d.delivery_notes}</span></div>` : ''}
          </div>
          ${d.proof_image ? `
          <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:8px">
            <div class="info-item" style="margin-bottom:10px"><label>📷 Foto Bukti Penerimaan</label></div>
            <img src="uploads/proof/${d.proof_image}"
              style="max-width:260px;border-radius:10px;border:2px solid var(--success);cursor:zoom-in"
              onclick="this.style.maxWidth=this.style.maxWidth==='100%'?'260px':'100%'" />
          </div>` : ''}
        </div>`;
    } else {
      deliveryHtml = `
        <div class="no-delivery">
          <i data-feather="truck"></i>
          <p>Pesanan kamu sudah selesai diproduksi.<br>Informasi pengiriman akan tersedia segera.</p>
        </div>`;
    }
  }

  result.innerHTML = pollingBadge + infoHtml + orderCard + deliveryHtml;
  result.classList.add('visible');
  feather.replace();
}

// ---- Helpers ----
function orderStatusLabel(s) {
  const m = { pending:'Pending', confirmed:'Dikonfirmasi', in_progress:'Diproses', quality_check:'QC', completed:'Selesai', cancelled:'Dibatalkan' };
  return m[s] || s;
}
function delivLabel(s) {
  const m = { prepared:'Disiapkan', shipping:'Dalam Pengiriman', arrived:'Tiba di Tujuan', received:'Diterima' };
  return m[s] || s;
}
function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
}
function formatDateTime(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}
function formatCurrency(n) {
  const num = parseFloat(n) || 0;
  return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits:0, maximumFractionDigits:0 });
}

// Animasi toast
const s = document.createElement('style');
s.textContent = `
  @keyframes slideInToast  { from{opacity:0;transform:translateX(60px)} to{opacity:1;transform:translateX(0)} }
  @keyframes slideOutToast { to{opacity:0;transform:translateX(60px)} }
`;
document.head.appendChild(s);
</script>
</body>
</html>
