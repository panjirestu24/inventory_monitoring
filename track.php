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
  <link rel="icon" type="image/png" href="logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary:#6366f1;--secondary:#8b5cf6;--accent:#06b6d4;
      --success:#10b981;--warning:#f59e0b;--danger:#ef4444;
      --bg-base:#080818;--bg-card:rgba(19,19,42,0.95);
      --bg-input:rgba(8,8,24,0.8);
      --border:rgba(99,102,241,0.15);
      --text-primary:#f1f5f9;--text-secondary:#94a3b8;--text-muted:#475569;
      --radius:14px;--radius-sm:10px;--radius-lg:20px;
      --shadow:0 8px 40px rgba(0,0,0,0.6);
      --shadow-glow:0 0 40px rgba(99,102,241,0.15);
      --transition:all 0.25s cubic-bezier(0.4,0,0.2,1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{font-size:14px}
    body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg-base);color:var(--text-primary);min-height:100vh;line-height:1.6;overflow-x:hidden}
    /* Aurora background */
    body::before{content:'';position:fixed;inset:0;z-index:0;
      background:
        radial-gradient(ellipse 80% 60% at 10% 0%,rgba(99,102,241,0.18) 0%,transparent 60%),
        radial-gradient(ellipse 60% 50% at 90% 10%,rgba(139,92,246,0.14) 0%,transparent 55%),
        radial-gradient(ellipse 70% 55% at 50% 100%,rgba(6,182,212,0.10) 0%,transparent 60%),
        radial-gradient(ellipse 50% 40% at 80% 70%,rgba(16,185,129,0.07) 0%,transparent 50%);
      animation:auroraShift 18s ease-in-out infinite alternate;pointer-events:none}
    @keyframes auroraShift{0%{opacity:.7;transform:scale(1) rotate(0deg)}50%{opacity:1;transform:scale(1.05) rotate(1deg)}100%{opacity:.8;transform:scale(1) rotate(-1deg)}}
    body>*{position:relative;z-index:1}

    /* Top Bar */
    .top-bar{position:sticky;top:0;z-index:100;background:rgba(8,8,24,0.65);backdrop-filter:blur(20px) saturate(180%);-webkit-backdrop-filter:blur(20px) saturate(180%);border-bottom:1px solid rgba(99,102,241,0.12);padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 0 rgba(99,102,241,0.08),0 4px 24px rgba(0,0,0,0.3)}
    .top-bar-logo{display:flex;align-items:center;gap:12px}
    .top-bar-logo .logo-icon{width:40px;height:40px;background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(139,92,246,0.15));border:1px solid rgba(99,102,241,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 16px rgba(99,102,241,0.2);overflow:hidden}
    .top-bar-logo .logo-name{font-size:16px;font-weight:800;letter-spacing:-.3px;background:linear-gradient(135deg,#c7d2fe,#a5b4fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .top-bar-logo .logo-name span{color:var(--text-muted);-webkit-text-fill-color:var(--text-muted);font-weight:400;font-size:11px;display:block;letter-spacing:0}
    .top-bar-right a{display:inline-flex;align-items:center;gap:6px;color:var(--primary);text-decoration:none;font-size:12px;font-weight:600;padding:7px 14px;border-radius:8px;border:1px solid rgba(99,102,241,0.25);background:rgba(99,102,241,0.08);transition:var(--transition)}
    .top-bar-right a:hover{background:rgba(99,102,241,0.18);border-color:rgba(99,102,241,0.4);box-shadow:0 0 12px rgba(99,102,241,0.2)}

    /* Layout */
    .main{max-width:820px;margin:0 auto;padding:56px 20px 80px}

    /* Hero */
    .hero{text-align:center;margin-bottom:48px}
    .hero-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.2);font-size:11px;font-weight:600;color:#a5b4fc;text-transform:uppercase;letter-spacing:.8px;margin-bottom:18px}
    .hero-badge-dot{width:6px;height:6px;border-radius:50%;background:#6366f1;box-shadow:0 0 6px #6366f1}
    .hero-icon-wrap{width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,rgba(99,102,241,0.2),rgba(6,182,212,0.15));border:1px solid rgba(99,102,241,0.25);border-radius:22px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(99,102,241,0.25),inset 0 1px 0 rgba(255,255,255,0.06)}
    .hero-icon-wrap svg{width:36px;height:36px;stroke:#a5b4fc}
    .hero h1{font-size:36px;font-weight:900;letter-spacing:-1px;margin-bottom:12px;background:linear-gradient(135deg,#e0e7ff 0%,#a5b4fc 40%,#22d3ee 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.2}
    .hero p{color:var(--text-secondary);font-size:15px;max-width:440px;margin:0 auto;line-height:1.7}
    /* Search Card */
    .search-card{position:relative;background:rgba(19,19,42,0.8);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(99,102,241,0.2);border-radius:var(--radius-lg);padding:32px;margin-bottom:36px;box-shadow:var(--shadow),var(--shadow-glow);overflow:hidden}
    .search-card::after{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:radial-gradient(circle,rgba(99,102,241,0.08) 0%,transparent 70%);pointer-events:none}
    .search-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-secondary);margin-bottom:12px;display:flex;align-items:center;gap:8px}
    .search-label i{color:var(--primary);font-size:13px}
    .search-row{display:flex;gap:10px}
    .search-input{flex:1;background:rgba(8,8,24,0.7);border:1px solid rgba(99,102,241,0.2);border-radius:var(--radius-sm);color:var(--text-primary);padding:14px 18px;font-size:15px;font-family:'Inter',monospace;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;transition:var(--transition)}
    .search-input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,0.15),0 0 20px rgba(99,102,241,0.1);background:rgba(8,8,24,0.9)}
    .search-input::placeholder{text-transform:none;font-weight:400;letter-spacing:0;color:var(--text-muted);font-size:13px}
    .btn-track{background:linear-gradient(135deg,var(--primary) 0%,var(--secondary) 100%);color:#fff;border:none;padding:14px 26px;border-radius:var(--radius-sm);font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;transition:var(--transition);white-space:nowrap;box-shadow:0 4px 20px rgba(99,102,241,0.4),inset 0 1px 0 rgba(255,255,255,0.1);letter-spacing:.2px}
    .btn-track:hover{box-shadow:0 6px 30px rgba(99,102,241,0.6);transform:translateY(-2px);filter:brightness(1.08)}
    .btn-track:active{transform:translateY(0)}
    .btn-track:disabled{opacity:.5;cursor:not-allowed;transform:none}
    .btn-track svg{width:16px;height:16px}

    /* Result */
    #result{display:none}
    #result.visible{display:block;animation:fadeInUp .4s ease}
    @keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

    /* Live badge */
    .live-badge-wrap{display:flex;align-items:center;justify-content:flex-end;margin-bottom:14px}
    #polling-badge{display:inline-flex;align-items:center;gap:7px;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;border:1px solid rgba(16,185,129,0.3);background:rgba(16,185,129,0.1);color:#34d399;box-shadow:0 0 12px rgba(16,185,129,0.1)}
    #polling-dot{width:7px;height:7px;border-radius:50%;background:#34d399;flex-shrink:0;animation:pulseLive 1.5s infinite}
    @keyframes pulseLive{0%,100%{box-shadow:0 0 0 0 rgba(52,211,153,0.5)}50%{box-shadow:0 0 0 5px rgba(52,211,153,0)}}

    /* Error */
    .error-box{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:var(--radius);padding:22px 24px;display:flex;align-items:flex-start;gap:16px;color:#f87171;box-shadow:0 0 24px rgba(239,68,68,0.08)}
    .error-box svg{width:22px;height:22px;flex-shrink:0;margin-top:2px}

    /* Info Card */
    .info-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:20px;box-shadow:var(--shadow);overflow:hidden}
    .info-card-top-bar{height:4px;background:linear-gradient(90deg,#6366f1,#8b5cf6,#06b6d4,#10b981)}
    .info-card-body{padding:24px}
    .info-card-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
    .order-number{font-family:'Inter',monospace;font-size:22px;font-weight:900;color:var(--accent);letter-spacing:1.5px;text-shadow:0 0 20px rgba(6,182,212,0.3)}
    .order-title{font-size:15px;font-weight:600;color:var(--text-secondary);margin-top:4px}
    .info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}
    .info-item{background:rgba(8,8,24,0.4);border:1px solid rgba(99,102,241,0.08);border-radius:var(--radius-sm);padding:12px 14px}
    .info-item label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);display:block;margin-bottom:5px}
    .info-item span{font-size:13px;font-weight:600;color:var(--text-primary)}

    /* Badges */
    .badge{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .badge-pending{background:rgba(148,163,184,0.12);color:#94a3b8;border:1px solid rgba(148,163,184,0.25)}
    .badge-in_progress{background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.3);box-shadow:0 0 10px rgba(245,158,11,0.1)}
    .badge-completed{background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.3);box-shadow:0 0 10px rgba(16,185,129,0.12)}
    .badge-cancelled{background:rgba(239,68,68,0.12);color:#f87171;border:1px solid rgba(239,68,68,0.3)}
    .badge-confirmed{background:rgba(59,130,246,0.12);color:#60a5fa;border:1px solid rgba(59,130,246,0.3)}
    .badge-quality_check{background:rgba(6,182,212,0.12);color:#22d3ee;border:1px solid rgba(6,182,212,0.3)}
    .badge-prepared{background:rgba(139,92,246,0.12);color:#c4b5fd;border:1px solid rgba(139,92,246,0.3)}
    .badge-shipping{background:rgba(6,182,212,0.12);color:#22d3ee;border:1px solid rgba(6,182,212,0.3)}
    .badge-arrived{background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.3)}
    .badge-received{background:rgba(16,185,129,0.25);color:#6ee7b7;border:1px solid rgba(16,185,129,0.5);box-shadow:0 0 14px rgba(16,185,129,0.2)}
    /* Section Title */
    .section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:24px;display:flex;align-items:center;gap:10px}
    .section-title i{color:var(--primary);font-size:14px}
    .section-title::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,rgba(99,102,241,0.2),transparent)}

    /* Horizontal Stepper */
    .stepper{display:flex;align-items:flex-start;gap:0;overflow-x:auto;padding-bottom:4px}
    .stepper::-webkit-scrollbar{height:3px}
    .stepper::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
    .step-item{display:flex;flex-direction:column;align-items:center;flex:1;min-width:80px;position:relative}
    .step-item:not(:last-child)::after{content:'';position:absolute;top:17px;left:calc(50% + 18px);right:calc(-50% + 18px);height:2px;background:rgba(99,102,241,0.15);transition:var(--transition)}
    .step-item.done:not(:last-child)::after{background:linear-gradient(90deg,#10b981,rgba(16,185,129,0.4))}
    .step-dot{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(99,102,241,0.15);background:rgba(8,8,24,0.8);color:var(--text-muted);font-size:14px;transition:var(--transition);flex-shrink:0;position:relative;z-index:1}
    .step-item.done .step-dot{background:rgba(16,185,129,0.15);border-color:var(--success);color:var(--success);box-shadow:0 0 12px rgba(16,185,129,0.25)}
    .step-item.active .step-dot{background:rgba(99,102,241,0.18);border-color:var(--primary);color:#a5b4fc;animation:stepPulse 1.8s infinite}
    @keyframes stepPulse{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,0.4)}50%{box-shadow:0 0 0 8px rgba(99,102,241,0)}}
    .step-label{font-size:10px;font-weight:600;text-align:center;margin-top:8px;color:var(--text-muted);line-height:1.3;padding:0 4px;transition:var(--transition)}
    .step-item.done .step-label{color:var(--success)}
    .step-item.active .step-label{color:#a5b4fc}

    /* Delivery Card */
    .delivery-card{background:var(--bg-card);border:1px solid rgba(6,182,212,0.15);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .delivery-card-top-bar{height:4px;background:linear-gradient(90deg,#06b6d4,#10b981,#6366f1)}
    .delivery-card-body{padding:24px}

    /* Vertical Timeline */
    .timeline{position:relative;padding-left:32px}
    .timeline::before{content:'';position:absolute;left:14px;top:16px;bottom:16px;width:2px;background:linear-gradient(180deg,var(--border),rgba(99,102,241,0.05))}
    .timeline-item{position:relative;margin-bottom:22px;opacity:.35;transition:var(--transition)}
    .timeline-item:last-child{margin-bottom:0}
    .timeline-item.done,.timeline-item.active{opacity:1}
    .timeline-dot{position:absolute;left:-26px;top:3px;width:24px;height:24px;border-radius:50%;background:rgba(8,8,24,0.9);border:2px solid rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;z-index:1}
    .timeline-dot svg{width:11px;height:11px}
    .timeline-item.done .timeline-dot{background:rgba(16,185,129,0.15);border-color:var(--success);box-shadow:0 0 10px rgba(16,185,129,0.2)}
    .timeline-item.done .timeline-dot svg{stroke:var(--success)}
    .timeline-item.active .timeline-dot{background:rgba(99,102,241,0.15);border-color:var(--primary);box-shadow:0 0 12px rgba(99,102,241,0.35);animation:pulseDot 1.6s infinite}
    .timeline-item.active .timeline-dot svg{stroke:var(--primary)}
    @keyframes pulseDot{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,0.4)}50%{box-shadow:0 0 0 6px rgba(99,102,241,0)}}
    .timeline-label{font-size:13px;font-weight:700;margin-bottom:3px;color:var(--text-primary)}
    .timeline-item.active .timeline-label{color:#a5b4fc}
    .timeline-desc{font-size:12px;color:var(--text-muted)}
    .timeline-time{font-size:11px;color:var(--accent);margin-top:5px;display:flex;align-items:center;gap:4px}

    /* Delivery Grid */
    .delivery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}

    /* No Delivery */
    .no-delivery{background:rgba(8,8,24,0.5);border:1px dashed rgba(99,102,241,0.15);border-radius:var(--radius);padding:40px 28px;text-align:center;color:var(--text-muted)}
    .no-delivery .nd-icon{width:56px;height:56px;margin:0 auto 14px;background:rgba(99,102,241,0.08);border-radius:16px;display:flex;align-items:center;justify-content:center}
    .no-delivery svg{width:28px;height:28px;stroke:var(--text-muted)}
    .no-delivery p{font-size:13px;line-height:1.7}

    /* Loading */
    .loading{display:flex;align-items:center;gap:12px;color:var(--text-muted);font-size:13px;padding:20px 0}
    .spinner{width:20px;height:20px;border:2px solid rgba(99,102,241,0.15);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0}
    @keyframes spin{to{transform:rotate(360deg)}}

    /* Footer */
    .page-footer{text-align:center;margin-top:60px;padding-top:24px;border-top:1px solid rgba(99,102,241,0.08);font-size:12px;color:var(--text-muted)}
    .page-footer a{color:var(--primary);text-decoration:none}

    /* Proof image */
    .proof-wrap{margin-top:20px;padding-top:20px;border-top:1px solid var(--border)}
    .proof-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);margin-bottom:12px;display:flex;align-items:center;gap:6px}
    .proof-wrap img{max-width:260px;border-radius:12px;border:2px solid rgba(16,185,129,0.4);cursor:zoom-in;box-shadow:0 0 20px rgba(16,185,129,0.1);transition:var(--transition)}
    .proof-wrap img:hover{border-color:var(--success);box-shadow:0 0 30px rgba(16,185,129,0.2)}

    @media(max-width:600px){
      .main{padding:36px 16px 60px}.hero h1{font-size:26px}
      .search-card{padding:22px 18px}.search-row{flex-direction:column}
      .btn-track{justify-content:center}
      .step-label{font-size:9px}.step-dot{width:30px;height:30px;font-size:12px}
      .step-item:not(:last-child)::after{top:15px}
      .info-grid{grid-template-columns:1fr 1fr}.order-number{font-size:18px}
    }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
  <div class="top-bar-logo">
    <div class="logo-icon">
      <img src="logo.png" alt="Logo" style="width:32px;height:32px;object-fit:contain;border-radius:6px">
    </div>
    <div class="logo-name">
      PrintTrack
      <span>Inventory &amp; Monitoring Percetakan</span>
    </div>
  </div>
  <div class="top-bar-right">
    <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login Staff</a>
  </div>
</div>

<!-- Main -->
<div class="main">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-badge"><span class="hero-badge-dot"></span> Real-time Tracking</div>
    <div class="hero-icon-wrap"><i data-feather="package"></i></div>
    <h1>Cek Status Pesanan</h1>
    <p>Masukkan nomor order yang kamu terima dari kami untuk melihat status terkini pesananmu secara real-time.</p>
  </div>

  <!-- Search Card -->
  <div class="search-card">
    <label class="search-label"><i class="bi bi-search"></i> Nomor Order</label>
    <div class="search-row">
      <input type="text" id="order-input" class="search-input"
        placeholder="Contoh: ORD-2506-0001" maxlength="30" autocomplete="off"
        onkeydown="if(event.key==='Enter') trackOrder()" />
      <button class="btn-track" id="btn-track" onclick="trackOrder()">
        <i data-feather="search"></i> Cek Pesanan
      </button>
    </div>
  </div>

  <!-- Result -->
  <div id="result"></div>

  <!-- Footer -->
  <div class="page-footer">
    &copy; <?= date('Y') ?> <a href="#">PrintTrack</a> &mdash; Sistem Inventory &amp; Monitoring Percetakan
  </div>

</div>

<script>
feather.replace();

// ── Realtime Polling ──────────────────────────────────────
let pollingInterval = null, currentOrderNum = null;
let lastOrderStatus = null, lastDelivStatus = null;

function startPolling(orderNum) {
  stopPolling();
  currentOrderNum = orderNum;
  pollingInterval = setInterval(pollStatus, 3000);
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
    const d = data.data;
    const newOrder = d.order_status, newDeliv = d.delivery_status || null;
    if (newOrder !== lastOrderStatus || newDeliv !== lastDelivStatus) {
      lastOrderStatus = newOrder; lastDelivStatus = newDeliv;
      renderResult(d); showStatusToast(newOrder, newDeliv);
    }
    const isFinal = newOrder === 'cancelled' || (newOrder === 'completed' && newDeliv === 'received');
    if (isFinal) { stopPolling(); setPollingBadge(false); } else { setPollingBadge(true); }
  } catch(e) { setPollingBadge(false, 'Offline'); }
}

function setPollingBadge(live, text) {
  const badge = document.getElementById('polling-badge');
  const dot   = document.getElementById('polling-dot');
  if (!badge) return;
  // Cari atau buat span teks
  let label = badge.querySelector('.badge-label');
  if (!label) {
    label = document.createElement('span');
    label.className = 'badge-label';
    badge.appendChild(label);
  }
  label.textContent = text || (live ? 'Live' : 'Offline');
  badge.style.color       = live ? '#34d399' : '#94a3b8';
  badge.style.borderColor = live ? 'rgba(16,185,129,0.3)' : 'rgba(148,163,184,0.2)';
  badge.style.background  = live ? 'rgba(16,185,129,0.1)' : 'rgba(148,163,184,0.08)';
  if (dot) { dot.style.background = live ? '#34d399' : '#94a3b8'; dot.style.animationName = live ? 'pulseLive' : 'none'; }
}

function showStatusToast(orderStatus, delivStatus) {
  const old = document.getElementById('status-toast');
  if (old) old.remove();
  const label = delivStatus
    ? `Pengiriman: <strong>${delivLabel(delivStatus)}</strong>`
    : `Status: <strong>${orderStatusLabel(orderStatus)}</strong>`;
  const t = document.createElement('div');
  t.id = 'status-toast';
  t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:999;
    background:rgba(19,19,42,0.95);backdrop-filter:blur(16px);
    border:1px solid rgba(16,185,129,0.4);border-radius:12px;
    padding:14px 18px;display:flex;align-items:center;gap:10px;
    font-size:13px;color:#f1f5f9;box-shadow:0 8px 32px rgba(0,0,0,0.5);
    animation:slideInToast .3s ease`;
  t.innerHTML = `<i class="bi bi-bell-fill" style="font-size:16px;color:#34d399;flex-shrink:0"></i><span>${label}</span>`;
  document.body.appendChild(t);
  setTimeout(() => { t.style.animation = 'slideOutToast .3s ease forwards'; setTimeout(() => t.remove(), 300); }, 4000);
}

window.addEventListener('visibilitychange', () => {
  if (document.hidden) { stopPolling(); }
  else if (currentOrderNum && lastOrderStatus) {
    const isFinal = lastOrderStatus === 'cancelled' || (lastOrderStatus === 'completed' && lastDelivStatus === 'received');
    if (!isFinal) startPolling(currentOrderNum);
  }
});
window.addEventListener('beforeunload', stopPolling);

// ── Track Order ───────────────────────────────────────────
async function trackOrder() {
  const input = document.getElementById('order-input');
  const btn   = document.getElementById('btn-track');
  const result= document.getElementById('result');
  const orderNum = input.value.trim().toUpperCase();
  if (!orderNum) { input.focus(); return; }

  stopPolling(); lastOrderStatus = null; lastDelivStatus = null;
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
      feather.replace(); btn.disabled = false; return;
    }
    const d = data.data;
    lastOrderStatus = d.order_status; lastDelivStatus = d.delivery_status || null;
    renderResult(d);
    const isFinal = d.order_status === 'cancelled' || (d.order_status === 'completed' && d.delivery_status === 'received');
    if (!isFinal) startPolling(orderNum);
  } catch(e) {
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

// ── Render Result ─────────────────────────────────────────
function renderResult(d) {
  const result = document.getElementById('result');
  const isFinal = d.order_status === 'cancelled' || (d.order_status === 'completed' && d.delivery_status === 'received');

  const pollingBadge = !isFinal ? `
    <div class="live-badge-wrap">
      <div id="polling-badge">
        <span id="polling-dot"></span>
        <span class="badge-label">Live</span>
      </div>
    </div>` : '';

  // Info card
  const infoHtml = `
    <div class="info-card">
      <div class="info-card-top-bar"></div>
      <div class="info-card-body">
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
      </div>
    </div>`;

  // Horizontal Stepper
  const steps = [
    { key:'pending',       label:'Diterima',    icon:'bi-file-earmark-text' },
    { key:'confirmed',     label:'Konfirmasi',  icon:'bi-check2-square'     },
    { key:'in_progress',   label:'Diproses',    icon:'bi-gear'              },
    { key:'quality_check', label:'QC',          icon:'bi-shield-check'      },
    { key:'completed',     label:'Selesai',     icon:'bi-box-seam'          },
  ];
  const orderIdx = steps.map(s=>s.key).indexOf(d.order_status);
  const stepper  = steps.map((s,i) => {
    const cls = i < orderIdx ? 'done' : i === orderIdx ? 'active' : '';
    return `<div class="step-item ${cls}">
      <div class="step-dot">${cls==='done' ? '<i class="bi bi-check-lg" style="font-size:14px"></i>' : `<i class="${s.icon}" style="font-size:14px"></i>`}</div>
      <div class="step-label">${s.label}</div>
    </div>`;
  }).join('');

  const orderCard = `
    <div class="info-card">
      <div class="info-card-top-bar" style="background:linear-gradient(90deg,#6366f1,#8b5cf6,#06b6d4)"></div>
      <div class="info-card-body">
        <div class="section-title"><i class="bi bi-activity"></i> Status Pesanan</div>
        <div class="stepper">${stepper}</div>
      </div>
    </div>`;

  // Delivery
  let delivHtml = '';
  if (d.order_status === 'completed' || d.delivery_id) {
    if (d.delivery_id) {
      const dSteps = [
        {key:'prepared',label:'Disiapkan',       desc:'Paket sedang disiapkan.',           icon:'package'},
        {key:'shipping',label:'Dikirim',          desc:'Paket dalam perjalanan.',           icon:'truck'},
        {key:'arrived', label:'Tiba di Tujuan',   desc:'Paket sampai di lokasi tujuan.',    icon:'map-pin'},
        {key:'received',label:'Diterima',         desc:'Paket diterima oleh penerima.',     icon:'check-circle'},
      ];
      const dIdx = ['prepared','shipping','arrived','received'].indexOf(d.delivery_status);
      const dTimeline = dSteps.map((s,i) => {
        const cls = i < dIdx ? 'done' : i === dIdx ? 'active' : '';
        return `<div class="timeline-item ${cls}">
          <div class="timeline-dot"><i data-feather="${s.icon}"></i></div>
          <div class="timeline-label">${s.label}</div>
          <div class="timeline-desc">${s.desc}</div>
          ${i===dIdx && s.key==='arrived' && d.actual_arrival ? `<div class="timeline-time"><i class="bi bi-clock-fill"></i> ${formatDateTime(d.actual_arrival)}</div>` : ''}
        </div>`;
      }).join('');

      const proofHtml = d.proof_image ? `
        <div class="proof-wrap">
          <div class="proof-label"><i class="bi bi-camera-fill"></i> Foto Bukti Penerimaan</div>
          <img src="uploads/proof/${d.proof_image}" onclick="this.style.maxWidth=this.style.maxWidth==='100%'?'260px':'100%'" />
        </div>` : '';

      delivHtml = `
        <div class="delivery-card">
          <div class="delivery-card-top-bar"></div>
          <div class="delivery-card-body">
            <div class="section-title"><i class="bi bi-truck"></i> Status Pengiriman</div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
              <span class="badge badge-${d.delivery_status}">${delivLabel(d.delivery_status)}</span>
              ${d.estimated_arrival ? `<div style="font-size:12px;color:var(--text-muted)"><i class="bi bi-calendar3" style="margin-right:4px"></i>Est. tiba: <strong style="color:var(--text-secondary)">${formatDate(d.estimated_arrival)}</strong></div>` : ''}
            </div>
            <div class="timeline">${dTimeline}</div>
            <div class="delivery-grid">
              ${d.recipient_name    ? `<div class="info-item"><label>Penerima</label><span>${d.recipient_name}</span></div>` : ''}
              ${d.recipient_phone   ? `<div class="info-item"><label>Telepon</label><span>${d.recipient_phone}</span></div>` : ''}
              ${d.destination_city  ? `<div class="info-item"><label>Kota Tujuan</label><span>${d.destination_city}</span></div>` : ''}
              ${d.destination_address ? `<div class="info-item" style="grid-column:1/-1"><label>Alamat</label><span>${d.destination_address}</span></div>` : ''}
              ${d.delivery_notes    ? `<div class="info-item" style="grid-column:1/-1"><label>Catatan</label><span>${d.delivery_notes}</span></div>` : ''}
            </div>
            ${proofHtml}
          </div>
        </div>`;
    } else {
      delivHtml = `
        <div class="no-delivery">
          <div class="nd-icon"><i data-feather="truck"></i></div>
          <p>Pesanan sudah selesai diproduksi.<br>Informasi pengiriman akan tersedia segera.</p>
        </div>`;
    }
  }

  result.innerHTML = pollingBadge + infoHtml + orderCard + delivHtml;
  result.classList.add('visible');
  feather.replace();
}

// ── Helpers ───────────────────────────────────────────────
function orderStatusLabel(s){const m={pending:'Pending',confirmed:'Dikonfirmasi',in_progress:'Diproses',quality_check:'QC',completed:'Selesai',cancelled:'Dibatalkan'};return m[s]||s}
function delivLabel(s){const m={prepared:'Disiapkan',shipping:'Dalam Pengiriman',arrived:'Tiba di Tujuan',received:'Diterima'};return m[s]||s}
function formatDate(d){if(!d)return'—';return new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})}
function formatDateTime(d){if(!d)return'—';return new Date(d).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}
function formatCurrency(n){const num=parseFloat(n)||0;return'Rp '+num.toLocaleString('id-ID',{minimumFractionDigits:0,maximumFractionDigits:0})}

// Toast animations
const _s=document.createElement('style');
_s.textContent=`
  @keyframes slideInToast{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:translateX(0)}}
  @keyframes slideOutToast{to{opacity:0;transform:translateX(60px)}}
`;
document.head.appendChild(_s);
</script>
</body>
</html>
