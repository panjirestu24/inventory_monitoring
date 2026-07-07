<!DOCTYPE html>
<?php // track.php - Halaman publik tracking pesanan ?>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cek Status Pesanan — Ranum Indocraft</title>
  <link rel="icon" type="image/png" href="logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --bg:#0a0a14;--bg-card:#111120;--bg-surface:#16162a;--bg-input:#0d0d1e;
      --border:#1e1e3a;--border-light:#252540;
      --primary:#6366f1;--accent:#06b6d4;--success:#10b981;--danger:#ef4444;--warning:#f59e0b;
      --text-1:#f1f5f9;--text-2:#94a3b8;--text-3:#475569;
      --r:12px;--r-sm:8px;--r-lg:16px;
      --shadow:0 4px 24px rgba(0,0,0,0.5);
      --t:all 0.2s ease;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{font-size:14px;color-scheme:dark}
    body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg);color:var(--text-1);min-height:100vh;line-height:1.6}

    /* Top Bar */
    .topbar{position:sticky;top:0;z-index:100;background:var(--bg-card);border-bottom:1px solid var(--border);
      padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between}
    .logo{display:flex;align-items:center;gap:10px}
    .logo-img{width:36px;height:36px;border-radius:8px;object-fit:contain;background:var(--bg-surface);border:1px solid var(--border)}
    .logo-name{font-size:15px;font-weight:800;color:var(--text-1)}
    .logo-sub{font-size:11px;color:var(--text-3);font-weight:400;display:block}
    .btn-login{display:inline-flex;align-items:center;gap:6px;color:var(--text-2);text-decoration:none;
      font-size:12px;font-weight:600;padding:7px 14px;border-radius:var(--r-sm);
      border:1px solid var(--border-light);background:var(--bg-surface);transition:var(--t)}
    .btn-login:hover{color:var(--text-1);border-color:var(--primary);background:rgba(99,102,241,0.08)}

    /* Main */
    .main{max-width:760px;margin:0 auto;padding:48px 20px 80px}

    /* Hero */
    .hero{margin-bottom:36px}
    .hero-tag{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;
      background:var(--bg-surface);border:1px solid var(--border-light);
      font-size:11px;font-weight:600;color:var(--text-2);margin-bottom:14px}
    .live-dot{width:6px;height:6px;border-radius:50%;background:var(--success);
      animation:pulse-live 1.5s infinite}
    @keyframes pulse-live{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,0.4)}50%{box-shadow:0 0 0 5px rgba(16,185,129,0)}}
    .hero h1{font-size:28px;font-weight:800;color:var(--text-1);margin-bottom:8px;letter-spacing:-.5px}
    .hero p{color:var(--text-2);font-size:14px;max-width:420px}

    /* Search */
    .search-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r-lg);
      padding:28px;margin-bottom:28px;box-shadow:var(--shadow)}
    .field-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;
      color:var(--text-3);margin-bottom:10px;display:block}
    .search-row{display:flex;gap:8px}
    .search-input{flex:1;background:var(--bg-input);border:1px solid var(--border-light);
      border-radius:var(--r-sm);color:var(--text-1);padding:12px 16px;font-size:15px;
      font-family:'Inter',monospace;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;transition:var(--t)}
    .search-input:focus{outline:none;border-color:var(--primary);background:var(--bg-input);
      box-shadow:0 0 0 3px rgba(99,102,241,0.12)}
    .search-input::placeholder{text-transform:none;font-weight:400;letter-spacing:0;color:var(--text-3);font-size:13px}
    .btn-cek{background:var(--primary);color:#fff;border:none;padding:12px 22px;border-radius:var(--r-sm);
      font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;
      transition:var(--t);white-space:nowrap}
    .btn-cek:hover{background:#5558e8;box-shadow:0 4px 16px rgba(99,102,241,0.35)}
    .btn-cek:active{transform:scale(.98)}
    .btn-cek:disabled{opacity:.5;cursor:not-allowed;transform:none}

    /* Result */
    #result{display:none}
    #result.visible{display:block;animation:fadeUp .35s ease}
    @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

    /* Live badge */
    .live-wrap{display:flex;justify-content:flex-end;margin-bottom:12px}
    #live-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;
      background:var(--bg-surface);border:1px solid var(--border-light);
      font-size:11px;font-weight:600;color:var(--success)}

    /* Card */
    .card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r-lg);
      margin-bottom:16px;box-shadow:var(--shadow);overflow:hidden}
    .card-accent{height:3px}
    .card-accent.order{background:var(--primary)}
    .card-accent.delivery{background:var(--accent)}
    .card-body{padding:22px}

    /* Order header */
    .order-head{display:flex;align-items:flex-start;justify-content:space-between;
      margin-bottom:18px;flex-wrap:wrap;gap:10px}
    .order-num{font-family:monospace;font-size:20px;font-weight:900;color:var(--accent);letter-spacing:1px}
    .order-name{font-size:13px;color:var(--text-2);margin-top:3px}

    /* Info grid */
    .info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:10px}
    .info-cell{background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--r-sm);padding:10px 12px}
    .info-cell label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
      color:var(--text-3);display:block;margin-bottom:4px}
    .info-cell span{font-size:13px;font-weight:600;color:var(--text-1)}

    /* Section label */
    .sec-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;
      color:var(--text-3);margin-bottom:18px;display:flex;align-items:center;gap:8px}
    .sec-label::after{content:'';flex:1;height:1px;background:var(--border)}

    /* Badge */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;
      font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
    .badge-pending   {background:rgba(148,163,184,.12);color:#94a3b8;border:1px solid rgba(148,163,184,.2)}
    .badge-confirmed {background:rgba(96,165,250,.12);color:#60a5fa;border:1px solid rgba(96,165,250,.25)}
    .badge-in_progress{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.25)}
    .badge-quality_check{background:rgba(6,182,212,.12);color:#22d3ee;border:1px solid rgba(6,182,212,.25)}
    .badge-completed {background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)}
    .badge-cancelled {background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.25)}
    .badge-prepared  {background:rgba(139,92,246,.12);color:#c4b5fd;border:1px solid rgba(139,92,246,.25)}
    .badge-shipping  {background:rgba(6,182,212,.12);color:#22d3ee;border:1px solid rgba(6,182,212,.25)}
    .badge-arrived   {background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25)}
    .badge-received  {background:rgba(16,185,129,.2);color:#6ee7b7;border:1px solid rgba(16,185,129,.4)}

    /* Horizontal stepper */
    .stepper{display:flex;align-items:flex-start}
    .step{display:flex;flex-direction:column;align-items:center;flex:1;min-width:72px;position:relative}
    .step:not(:last-child)::after{content:'';position:absolute;top:16px;left:calc(50% + 17px);
      right:calc(-50% + 17px);height:2px;background:var(--border-light);z-index:0}
    .step.done:not(:last-child)::after{background:var(--success)}
    .step-dot{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      border:2px solid var(--border-light);background:var(--bg-surface);color:var(--text-3);
      font-size:13px;position:relative;z-index:1;transition:var(--t)}
    .step.done  .step-dot{background:rgba(16,185,129,.12);border-color:var(--success);color:var(--success)}
    .step.active .step-dot{background:rgba(99,102,241,.12);border-color:var(--primary);color:#a5b4fc;
      animation:pulse-step 1.8s infinite}
    @keyframes pulse-step{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,.35)}50%{box-shadow:0 0 0 7px rgba(99,102,241,0)}}
    .step-lbl{font-size:10px;font-weight:600;text-align:center;margin-top:7px;color:var(--text-3);padding:0 2px}
    .step.done  .step-lbl{color:var(--success)}
    .step.active .step-lbl{color:#a5b4fc}

    /* Timeline */
    .timeline{position:relative;padding-left:30px}
    .timeline::before{content:'';position:absolute;left:11px;top:14px;bottom:14px;
      width:2px;background:var(--border)}
    .tl-item{position:relative;margin-bottom:20px;opacity:.35;transition:var(--t)}
    .tl-item:last-child{margin-bottom:0}
    .tl-item.done,.tl-item.active{opacity:1}
    .tl-dot{position:absolute;left:-24px;top:2px;width:22px;height:22px;border-radius:50%;
      background:var(--bg-surface);border:2px solid var(--border-light);
      display:flex;align-items:center;justify-content:center;z-index:1}
    .tl-dot svg{width:10px;height:10px;stroke:var(--text-3)}
    .tl-item.done  .tl-dot{background:rgba(16,185,129,.1);border-color:var(--success)}
    .tl-item.done  .tl-dot svg{stroke:var(--success)}
    .tl-item.active .tl-dot{background:rgba(99,102,241,.1);border-color:var(--primary);
      animation:pulse-dot 1.6s infinite}
    .tl-item.active .tl-dot svg{stroke:var(--primary)}
    @keyframes pulse-dot{0%,100%{box-shadow:0 0 0 0 rgba(99,102,241,.35)}50%{box-shadow:0 0 0 5px rgba(99,102,241,0)}}
    .tl-title{font-size:13px;font-weight:700;color:var(--text-1);margin-bottom:2px}
    .tl-item.active .tl-title{color:#a5b4fc}
    .tl-desc{font-size:12px;color:var(--text-3)}
    .tl-time{font-size:11px;color:var(--accent);margin-top:4px;display:flex;align-items:center;gap:4px}

    /* Delivery info grid */
    .del-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:10px;
      margin-top:18px;padding-top:18px;border-top:1px solid var(--border)}

    /* Proof */
    .proof-wrap{margin-top:18px;padding-top:18px;border-top:1px solid var(--border)}
    .proof-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
      color:var(--text-3);margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .proof-wrap img{max-width:240px;border-radius:10px;border:1px solid rgba(16,185,129,.3);
      cursor:zoom-in;transition:var(--t);display:block}
    .proof-wrap img:hover{border-color:var(--success);max-width:100%}

    /* No delivery */
    .no-delivery{background:var(--bg-card);border:1px dashed var(--border-light);
      border-radius:var(--r-lg);padding:36px 24px;text-align:center;color:var(--text-3);margin-bottom:16px}
    .no-delivery i{font-size:32px;display:block;margin-bottom:12px;opacity:.4}
    .no-delivery p{font-size:13px;line-height:1.7}

    /* Error */
    .err-box{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);
      border-radius:var(--r);padding:20px;display:flex;align-items:flex-start;gap:14px;color:#f87171}
    .err-box svg{width:20px;height:20px;flex-shrink:0;margin-top:2px}

    /* Loading */
    .loading{display:flex;align-items:center;gap:10px;color:var(--text-2);font-size:13px;padding:20px 0}
    .spin{width:18px;height:18px;border:2px solid var(--border-light);border-top-color:var(--primary);
      border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0}
    @keyframes spin{to{transform:rotate(360deg)}}

    /* Footer */
    .footer{text-align:center;margin-top:56px;padding-top:20px;border-top:1px solid var(--border);
      font-size:12px;color:var(--text-3)}
    .footer a{color:var(--primary);text-decoration:none}

    @media(max-width:580px){
      .main{padding:32px 16px 60px}
      .hero h1{font-size:22px}
      .search-row{flex-direction:column}
      .btn-cek{justify-content:center}
      .step-dot{width:28px;height:28px;font-size:11px}
      .step:not(:last-child)::after{top:13px}
      .step-lbl{font-size:9px}
    }
  </style>
</head>
<body>

<div class="topbar">
  <div class="logo">
    <img src="logo.png" alt="Logo" class="logo-img">
    <div class="logo-name">Ranum Indocraft<span class="logo-sub">Inventory & Monitoring Percetakan</span></div>
  </div>
  <a href="login.php" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Login Staff</a>
</div>

<div class="main">

  <div class="hero">
    <div class="hero-tag"><span class="live-dot"></span> Real-time Tracking</div>
    <h1>Cek Status Pesanan</h1>
    <p>Masukkan nomor order untuk melihat status terkini pesanan kamu secara real-time.</p>
  </div>

  <div class="search-card">
    <span class="field-label">Nomor Order</span>
    <div class="search-row">
      <input type="text" id="order-input" class="search-input"
        placeholder="Contoh: ORD-2506-0001" maxlength="30" autocomplete="off"
        onkeydown="if(event.key==='Enter') trackOrder()" />
      <button class="btn-cek" id="btn-track" onclick="trackOrder()">
        <i data-feather="search" style="width:15px;height:15px"></i> Cek Pesanan
      </button>
    </div>
  </div>

  <div id="result"></div>

  <div class="footer">
    &copy; <?= date('Y') ?> <a href="#">Ranum Indocraft</a> &mdash; Sistem Inventory &amp; Monitoring Percetakan
  </div>

</div>

<script>
feather.replace();

let pollingInterval=null, currentOrderNum=null, lastOrder=null, lastDeliv=null;

function startPolling(n){stopPolling();currentOrderNum=n;pollingInterval=setInterval(poll,3000)}
function stopPolling(){if(pollingInterval){clearInterval(pollingInterval);pollingInterval=null}}

async function poll(){
  if(!currentOrderNum)return;
  try{
    const r=await fetch(`api/deliveries.php?action=track&order_number=${encodeURIComponent(currentOrderNum)}`);
    const j=await r.json();
    if(!j.success)return;
    const d=j.data,no=d.order_status,nd=d.delivery_status||null;
    if(no!==lastOrder||nd!==lastDeliv){lastOrder=no;lastDeliv=nd;renderResult(d);toast(no,nd)}
    const fin=no==='cancelled'||(no==='completed'&&nd==='received');
    if(fin){stopPolling();setBadge(false)}else setBadge(true);
  }catch{setBadge(false,'Offline')}
}

function setBadge(live,txt){
  const b=document.getElementById('live-badge');if(!b)return;
  let lbl=b.querySelector('.lbl');
  if(!lbl){lbl=document.createElement('span');lbl.className='lbl';b.appendChild(lbl)}
  lbl.textContent=txt||(live?'Live':'Offline');
  const dot=b.querySelector('.live-dot');
  b.style.color=live?'var(--success)':'var(--text-2)';
  b.style.borderColor=live?'rgba(16,185,129,.3)':'var(--border-light)';
  if(dot){dot.style.background=live?'var(--success)':'var(--text-3)';dot.style.animationName=live?'pulse-live':'none'}
}

function toast(os,ds){
  const old=document.getElementById('_toast');if(old)old.remove();
  const lbl=ds?`Pengiriman: <b>${dlvLbl(ds)}</b>`:`Status: <b>${ordLbl(os)}</b>`;
  const t=document.createElement('div');t.id='_toast';
  t.style.cssText='position:fixed;bottom:22px;right:22px;z-index:999;background:var(--bg-card);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:13px;color:var(--text-1);box-shadow:0 8px 28px rgba(0,0,0,.5);animation:slIn .25s ease';
  t.innerHTML=`<i class="bi bi-bell-fill" style="color:var(--success);font-size:15px;flex-shrink:0"></i><span>${lbl}</span>`;
  document.body.appendChild(t);
  setTimeout(()=>{t.style.animation='slOut .25s ease forwards';setTimeout(()=>t.remove(),250)},4000);
}

window.addEventListener('visibilitychange',()=>{
  if(document.hidden)stopPolling();
  else if(currentOrderNum&&lastOrder){const f=lastOrder==='cancelled'||(lastOrder==='completed'&&lastDeliv==='received');if(!f)startPolling(currentOrderNum)}
});
window.addEventListener('beforeunload',stopPolling);

async function trackOrder(){
  const inp=document.getElementById('order-input'),btn=document.getElementById('btn-track'),res=document.getElementById('result');
  const num=inp.value.trim().toUpperCase();
  if(!num){inp.focus();return}
  stopPolling();lastOrder=null;lastDeliv=null;
  btn.disabled=true;
  res.className='';
  res.innerHTML='<div class="loading"><div class="spin"></div> Mencari pesanan...</div>';
  res.classList.add('visible');
  try{
    const r=await fetch(`api/deliveries.php?action=track&order_number=${encodeURIComponent(num)}`);
    const j=await r.json();
    if(!j.success){
      res.innerHTML=`<div class="err-box"><i data-feather="alert-circle"></i><div><div style="font-weight:700;margin-bottom:3px">Pesanan tidak ditemukan</div><div style="font-size:12px">Nomor order "<b>${num}</b>" tidak ada dalam sistem.</div></div></div>`;
      feather.replace();btn.disabled=false;return;
    }
    const d=j.data;lastOrder=d.order_status;lastDeliv=d.delivery_status||null;
    renderResult(d);
    const fin=d.order_status==='cancelled'||(d.order_status==='completed'&&d.delivery_status==='received');
    if(!fin)startPolling(num);
  }catch{
    res.innerHTML=`<div class="err-box"><i data-feather="wifi-off"></i><div><div style="font-weight:700;margin-bottom:3px">Koneksi bermasalah</div><div style="font-size:12px">Gagal terhubung ke server.</div></div></div>`;
    feather.replace();
  }
  btn.disabled=false;
}

function renderResult(d){
  const res=document.getElementById('result');
  const fin=d.order_status==='cancelled'||(d.order_status==='completed'&&d.delivery_status==='received');

  const liveBadge=!fin?`<div class="live-wrap"><div id="live-badge"><span class="live-dot"></span><span class="lbl">Live</span></div></div>`:'';

  // Info card
  const infoCard=`
    <div class="card">
      <div class="card-accent order"></div>
      <div class="card-body">
        <div class="order-head">
          <div><div class="order-num">${d.order_number}</div><div class="order-name">${d.title}</div></div>
          <span class="badge badge-${d.order_status}">${ordLbl(d.order_status)}</span>
        </div>
        <div class="info-grid">
          <div class="info-cell"><label>Pelanggan</label><span>${d.customer_name}</span></div>
          <div class="info-cell"><label>Jumlah</label><span>${d.quantity?parseInt(d.quantity).toLocaleString('id-ID')+' pcs':'—'}</span></div>
          <div class="info-cell"><label>Jatuh Tempo</label><span>${fmtDate(d.due_date)}</span></div>
          <div class="info-cell"><label>Total</label><span style="color:var(--success)">${fmtCur(d.grand_total)}</span></div>
          ${d.order_notes?`<div class="info-cell" style="grid-column:1/-1"><label>Catatan</label><span>${d.order_notes}</span></div>`:''}
        </div>
      </div>
    </div>`;

  // Stepper
  const steps=[
    {key:'pending',lbl:'Diterima',icon:'bi-file-text'},
    {key:'confirmed',lbl:'Dikonfirmasi',icon:'bi-check2-square'},
    {key:'in_progress',lbl:'Diproses',icon:'bi-gear'},
    {key:'quality_check',lbl:'Cek Kualitas',icon:'bi-shield-check'},
    {key:'completed',lbl:'Selesai',icon:'bi-box-seam'},
  ];
  const oi=steps.map(s=>s.key).indexOf(d.order_status);
  const stepHtml=steps.map((s,i)=>{
    const c=i<oi?'done':i===oi?'active':'';
    return `<div class="step ${c}">
      <div class="step-dot">${c==='done'?'<i class="bi bi-check-lg"></i>':`<i class="${s.icon}"></i>`}</div>
      <div class="step-lbl">${s.lbl}</div>
    </div>`;
  }).join('');
  const stepCard=`
    <div class="card">
      <div class="card-accent order"></div>
      <div class="card-body">
        <div class="sec-label"><i class="bi bi-activity"></i> Status Pesanan</div>
        <div class="stepper">${stepHtml}</div>
      </div>
    </div>`;

  // Delivery
  let delHtml='';
  if(d.order_status==='completed'||d.delivery_id){
    if(d.delivery_id){
      const ds=[
        {key:'prepared',lbl:'Disiapkan',desc:'Paket sedang disiapkan.',icon:'package'},
        {key:'shipping',lbl:'Dikirim',desc:'Paket dalam perjalanan.',icon:'truck'},
        {key:'arrived',lbl:'Tiba di Tujuan',desc:'Paket sampai di lokasi tujuan.',icon:'map-pin'},
        {key:'received',lbl:'Diterima',desc:'Paket diterima oleh penerima.',icon:'check-circle'},
      ];
      const di=['prepared','shipping','arrived','received'].indexOf(d.delivery_status);
      const tlHtml=ds.map((s,i)=>{
        const c=i<di?'done':i===di?'active':'';
        return `<div class="tl-item ${c}">
          <div class="tl-dot"><i data-feather="${s.icon}"></i></div>
          <div class="tl-title">${s.lbl}</div>
          <div class="tl-desc">${s.desc}</div>
          ${i===di&&s.key==='arrived'&&d.actual_arrival?`<div class="tl-time"><i class="bi bi-clock"></i> ${fmtDateTime(d.actual_arrival)}</div>`:''}
        </div>`;
      }).join('');
      const proofHtml=d.proof_image?`<div class="proof-wrap"><div class="proof-lbl"><i class="bi bi-camera-fill"></i> Foto Bukti Penerimaan</div><img src="uploads/proof/${d.proof_image}" onclick="this.style.maxWidth=this.style.maxWidth==='100%'?'240px':'100%'" /></div>`:'';
      delHtml=`
        <div class="card">
          <div class="card-accent delivery"></div>
          <div class="card-body">
            <div class="sec-label"><i class="bi bi-truck"></i> Status Pengiriman</div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px">
              <span class="badge badge-${d.delivery_status}">${dlvLbl(d.delivery_status)}</span>
              ${d.estimated_arrival?`<span style="font-size:12px;color:var(--text-2)"><i class="bi bi-calendar3" style="margin-right:4px"></i>Est. tiba: <b>${fmtDate(d.estimated_arrival)}</b></span>`:''}
            </div>
            <div class="timeline">${tlHtml}</div>
            <div class="del-grid">
              ${d.recipient_name?`<div class="info-cell"><label>Penerima</label><span>${d.recipient_name}</span></div>`:''}
              ${d.recipient_phone?`<div class="info-cell"><label>Telepon</label><span>${d.recipient_phone}</span></div>`:''}
              ${d.destination_city?`<div class="info-cell"><label>Kota Tujuan</label><span>${d.destination_city}</span></div>`:''}
              ${d.destination_address?`<div class="info-cell" style="grid-column:1/-1"><label>Alamat</label><span>${d.destination_address}</span></div>`:''}
            </div>
            ${proofHtml}
          </div>
        </div>`;
    }else{
      delHtml=`<div class="no-delivery"><i class="bi bi-truck"></i><p>Pesanan selesai diproduksi.<br>Informasi pengiriman akan tersedia segera.</p></div>`;
    }
  }

  res.innerHTML=liveBadge+infoCard+stepCard+delHtml;
  res.classList.add('visible');
  feather.replace();
}

function ordLbl(s){return{pending:'Menunggu',confirmed:'Dikonfirmasi',in_progress:'Diproses',quality_check:'Cek Kualitas',completed:'Selesai',cancelled:'Dibatalkan'}[s]||s}
function dlvLbl(s){return{prepared:'Disiapkan',shipping:'Dalam Pengiriman',arrived:'Tiba di Tujuan',received:'Diterima'}[s]||s}
function fmtDate(d){if(!d)return'—';return new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})}
function fmtDateTime(d){if(!d)return'—';return new Date(d).toLocaleString('id-ID',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}
function fmtCur(n){return'Rp '+(parseFloat(n)||0).toLocaleString('id-ID')}

const _s=document.createElement('style');
_s.textContent=`
  @keyframes slIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
  @keyframes slOut{to{opacity:0;transform:translateX(40px)}}
`;
document.head.appendChild(_s);
</script>
</body>
</html>
