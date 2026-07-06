// ============================================================
// PrintTrack — Main Application JS
// ============================================================

// Global error handler — tangkap semua JS error
window.onerror = (msg, src, line, col, err) => {
  console.error(`JS Error: ${msg} | ${src}:${line}:${col}`, err);
};
window.onunhandledrejection = (e) => {
  console.error('Unhandled Promise:', e.reason);
};

const API = 'api/';
let chartOrders = null;
let chartStock  = null;
let chartThroughput = null;
let currentPage = 'dashboard';
let currentReportType = 'stock';

// ---- Ref Data ----
let refCategories = [];
let refUnits      = [];
let allItems      = [];
let allOrders     = [];
let allDeliveries = [];

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', async () => {
  feather.replace();
  startClock();
  
  // Apply role-based UI restrictions
  applyRoleBasedUI();

  try {
    await loadRefData();
    populateFormSelects();
  } catch(e) {
    console.error('Init error:', e);
  }

  // Baca hash URL untuk navigasi langsung (misal dari order_baru.php#orders)
  const hashPage = window.location.hash.replace('#', '');
  const validPages = ['dashboard','monitoring','items','stock-mutation','order-input','orders','deliveries','customers','products','reports','manage-users'];
  navigate(validPages.includes(hashPage) ? hashPage : 'dashboard');

  // Report date defaults
  const today = new Date().toISOString().slice(0,10);
  const monthStart = today.slice(0,8) + '01';
  const rf = document.getElementById('report-from');
  const rt = document.getElementById('report-to');
  if (rf) rf.value = monthStart;
  if (rt) rt.value = today;

  // Polling badge sidebar secara realtime (setiap 10 detik)
  startBadgePolling();
});

// ============================================================
// ROLE-BASED ACCESS CONTROL
// ============================================================
function applyRoleBasedUI() {
  const role = window.APP_CONFIG?.userRole || 'operator';
  const perms = window.APP_CONFIG?.permissions || {};
  
  // Helper untuk hide element
  const hideElements = (selector) => {
    document.querySelectorAll(selector).forEach(el => {
      el.style.display = 'none';
    });
  };
  
  // Kalau bukan admin atau operator, hide semua tombol tambah/edit/hapus
  if (!perms.canCreate) {
    hideElements('.btn-primary[onclick*="openAdd"]');
    hideElements('.btn-primary[onclick*="Tambah"]');
    hideElements('#header-action-btn');
  }
  
  // Kalau operator, hide tombol hapus aja
  if (role === 'operator') {
    console.log('Mode Operator: Bisa tambah & edit, tidak bisa hapus');
    hideElements('button[onclick*="delete"]');
    hideElements('button[onclick*="Hapus"]');
    hideElements('.btn-danger[onclick*="delete"]');
  }
  
  // Kalau admin, tampilkan badge admin
  if (role === 'admin') {
    console.log('Mode Admin: Full Access');
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
      const badge = document.createElement('div');
      badge.className = 'badge badge-completed';
      badge.style.cssText = 'margin-right:12px;padding:6px 12px';
      badge.innerHTML = '<i data-feather="shield"></i> Admin';
      headerActions.insertBefore(badge, headerActions.firstChild);
      feather.replace();
    }
  }
}

// Cek permission sebelum action
function checkPermission(action) {
  const perms = window.APP_CONFIG?.permissions || {};
  const role = window.APP_CONFIG?.userRole || 'operator';
  
  const actionMap = {
    'create': perms.canCreate,
    'edit': perms.canEdit,
    'delete': perms.canDelete,
    'export': perms.canExport,
  };
  
  if (!actionMap[action]) {
    showToast(`Akses ditolak! Role "${role}" tidak punya hak akses untuk ${action}`, 'error');
    return false;
  }
  return true;
}

// ============================================================
// CLOCK
// ============================================================
function startClock() {
  const el = document.getElementById('realtime-clock');
  const tick = () => {
    el.textContent = new Date().toLocaleTimeString('id-ID');
  };
  tick();
  setInterval(tick, 1000);
}

// ============================================================
// BADGE POLLING — update sidebar badge secara realtime
// ============================================================
let _prevActiveOrders  = -1;
let _prevLowStock      = -1;
let _prevInTransit     = -1;

async function updateSidebarBadges() {
  const data = await apiFetch(API + 'dashboard.php?action=badges');
  if (!data?.success) return;

  const activeOrders = parseInt(data.active_orders) || 0;
  const lowStock     = parseInt(data.low_stock)     || 0;
  const inTransit    = parseInt(data.in_transit)    || 0;

  // Badge Tracking Realtime
  const badgeActive = document.getElementById('badge-active');
  if (badgeActive) {
    badgeActive.textContent = activeOrders;
    // Animasi flash kalau angka berubah naik
    if (_prevActiveOrders !== -1 && activeOrders !== _prevActiveOrders) {
      badgeActive.style.transition = 'none';
      badgeActive.style.transform  = 'scale(1.5)';
      badgeActive.style.background = activeOrders > _prevActiveOrders ? '#10b981' : '#ef4444';
      setTimeout(() => {
        badgeActive.style.transition = 'all .3s ease';
        badgeActive.style.transform  = 'scale(1)';
        badgeActive.style.background = '';
      }, 300);
    }
    _prevActiveOrders = activeOrders;
  }

  // Badge Stok Kritis
  const badgeLow = document.getElementById('badge-lowstock');
  if (badgeLow) {
    if (lowStock > 0) {
      badgeLow.style.display = 'inline';
      badgeLow.textContent   = lowStock;
    } else {
      badgeLow.style.display = 'none';
    }
    _prevLowStock = lowStock;
  }

  // Badge Pengiriman
  const badgeDel = document.getElementById('badge-deliveries');
  if (badgeDel) {
    badgeDel.textContent   = inTransit;
    badgeDel.style.display = inTransit > 0 ? 'inline' : 'none';
    _prevInTransit = inTransit;
  }

  // Kalau di dashboard, update stat cards juga tanpa reload penuh
  if (currentPage === 'dashboard') {
    const el = document.getElementById('stat-orders');
    if (el) el.textContent = data.active_orders;
  }
}

function startBadgePolling() {
  updateSidebarBadges();                          // langsung sekali saat load
  setInterval(updateSidebarBadges, 3000);        // lalu setiap 3 detik
}

// ============================================================
// NAVIGATION
// ============================================================
const pageTitles = {
  dashboard:        ['Dashboard', 'Overview semua aktivitas'],
  monitoring:       ['MES Monitoring', 'Antrian FIFO & pipeline produksi realtime'],
  items:            ['Bahan Baku', 'Kelola inventory material'],
  'stock-mutation': ['Mutasi Stok', 'Stok masuk & keluar'],
  'order-input':    ['Input Order Baru', 'Isi data pelanggan & pesanan, nota langsung tercetak'],
  orders:           ['Order Cetak', 'Manajemen pesanan pelanggan'],
  deliveries:       ['Pengiriman', 'Monitoring pengiriman ke pelanggan'],
  customers:        ['Pelanggan', 'Riwayat pelanggan & order'],
  products:         ['Produk & Harga', 'Daftar produk/jasa percetakan'],
  reports:          ['Laporan', 'Analisis & ekspor data'],
  'manage-users':   ['Kelola User', 'Manajemen akun & hak akses'],
};

function navigate(page) {
  currentPage = page;
  // Update hash URL supaya hard refresh kembali ke halaman yang sama
  history.replaceState(null, '', '#' + page);
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  const el = document.getElementById(`page-${page}`);
  if (el) el.classList.add('active');

  const nav = document.querySelector(`.nav-item[data-page="${page}"]`);
  if (nav) nav.classList.add('active');

  const [title, sub] = pageTitles[page] || [page, ''];
  document.getElementById('page-title').innerHTML = title + ` <span>${sub}</span>`;

  // Stop tracking interval kalau pindah halaman
  if (page !== 'monitoring' && trackingInterval) {
    clearInterval(trackingInterval);
    trackingInterval = null;
  }
  // Stop dashboard interval kalau pindah halaman
  if (page !== 'dashboard' && dashboardInterval) {
    clearInterval(dashboardInterval);
    dashboardInterval = null;
  }

  // Load page data
  switch (page) {
    case 'dashboard':      loadDashboard(); startDashboardInterval(); break;
    case 'monitoring':     loadMonitoring(); break;
    case 'items':          loadItems(); break;
    case 'stock-mutation': loadStockMutationPage(); break;
    case 'order-input':    loadOrderInputPage(); break;
    case 'orders':         currentOrdersTab = 'active'; loadOrders(); break;
    case 'deliveries':     currentDeliveriesTab = 'active'; loadDeliveries(); break;
    case 'customers':      loadCustomers(); break;
    case 'products':       loadProducts(); break;
    case 'reports':        loadReport(); break;
    case 'manage-users':   loadUsers(); break;
  }

  feather.replace();
}

function openQuickAdd() {
  const map = {
    items:         openAddItemModal,
    'order-input': niSimpanOrder,
    orders:        () => navigate('order-input'),
    deliveries:    () => showToast('Pilih order yang sudah selesai untuk membuat pengiriman', 'info'),
    customers:     openAddCustomerModal,
  };
  if (map[currentPage]) map[currentPage]();
  else showToast('Pilih halaman yang sesuai untuk menambah data', 'info');
}

// ============================================================
// REF DATA LOADER
// ============================================================
async function loadRefData() {
  const [cats, units] = await Promise.all([
    apiFetch('api/categories.php'),
    apiFetch('api/units.php'),
  ]);
  refCategories = cats?.data || [];
  refUnits      = units?.data || [];
}

function populateFormSelects() {
  const setSelect    = (id, html)  => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
  const appendSelect = (id, html)  => { const el = document.getElementById(id); if (el) el.innerHTML += html; };

  // Item form — category & unit selects
  setSelect('item-category', '<option value="">-- Pilih Kategori --</option>');
  refCategories.forEach(c => appendSelect('item-category', `<option value="${c.id_categories}">${c.name}</option>`));

  setSelect('item-unit', '<option value="">-- Pilih Satuan --</option>');
  refUnits.forEach(u => appendSelect('item-unit', `<option value="${u.id_units}">${u.name} (${u.symbol})</option>`));

  // Category filter on items page
  refCategories.forEach(c => appendSelect('items-cat-filter', `<option value="${c.id_categories}">${c.name}</option>`));
}

function populateItemSelects(items) {
  const opts = items.map(i => `<option value="${i.id_items}">${i.code} — ${i.name}</option>`).join('');
  document.getElementById('in-item-id').innerHTML = '<option value="">-- Pilih Item --</option>' + opts;
  document.getElementById('out-item-id').innerHTML = '<option value="">-- Pilih Item --</option>' + opts;
  document.getElementById('mutation-item-filter').innerHTML = '<option value="">Semua Item</option>' + opts;
}

// ============================================================
// API HELPERS
// ============================================================
async function apiFetch(url, opts = {}) {
  try {
    const res = await fetch(url, opts);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error('API error:', url, e);
    return null;
  }
}

async function apiPost(url, data) {
  return apiFetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
}

async function apiPut(url, data) {
  return apiFetch(url, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
}

// ============================================================
// DASHBOARD
// ============================================================
async function loadDashboard() {
  const data = await apiFetch(API + 'dashboard.php');
  if (!data?.success) return;

  // Stats
  const s = data.stats;
  document.getElementById('stat-items').textContent = s.total_items;
  document.getElementById('stat-items-sub').textContent = `${s.low_stock} item stok kritis`;
  document.getElementById('stat-lowstock').textContent = s.low_stock;
  document.getElementById('stat-lowstock-sub').textContent = s.low_stock > 0 ? 'Perlu restock segera' : 'Semua stok aman';
  document.getElementById('stat-orders').textContent = s.active_orders;
  document.getElementById('stat-orders-sub').textContent = `${s.orders_today} order masuk hari ini`;
  document.getElementById('stat-revenue').textContent = formatCurrency(s.monthly_revenue);
  document.getElementById('stat-revenue-sub').textContent = 'Bulan ini';

  // Badge — sinkron juga ke state polling supaya tidak trigger animasi palsu
  document.getElementById('badge-active').textContent = s.active_orders;
  _prevActiveOrders = parseInt(s.active_orders) || 0;
  if (s.low_stock > 0) {
    document.getElementById('badge-lowstock').style.display = 'inline';
    document.getElementById('badge-lowstock').textContent = s.low_stock;
  }

  // Low Stock
  renderDashboardLowStock(data.low_stock);

  // Orders
  renderDashboardOrders(data.recent_orders);

  // Charts
  renderOrderChart(data.chart_orders);
  renderStockChart(data.chart_stock);
  renderThroughputChart(data.chart_throughput);

  feather.replace();
}

function renderDashboardLowStock(items) {
  const el = document.getElementById('dashboard-lowstock');
  if (!items?.length) {
    el.innerHTML = `
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px 16px;gap:12px">
        <div style="width:52px;height:52px;border-radius:50%;background:rgba(16,185,129,0.12);border:2px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center">
          <i class="bi bi-check-lg" style="font-size:22px;color:var(--success)"></i>
        </div>
        <div style="text-align:center">
          <div style="font-weight:700;color:var(--success);font-size:14px;margin-bottom:4px">Semua Stok Aman</div>
          <div style="font-size:12px;color:var(--text-muted)">Tidak ada item yang perlu restock</div>
        </div>
      </div>`;
    return;
  }

  el.innerHTML = items.map((i, idx) => {
    const pct     = i.min_stock > 0 ? Math.min(100, (i.stock / i.min_stock) * 100) : 100;
    const isEmpty = parseFloat(i.stock) === 0;
    const isCrit  = pct <= 25;
    const isWarn  = pct > 25 && pct <= 75;

    const barColor  = isEmpty ? '#ef4444' : isCrit ? '#ef4444' : isWarn ? '#f59e0b' : '#10b981';
    const bgColor   = isEmpty ? 'rgba(239,68,68,0.08)' : isCrit ? 'rgba(239,68,68,0.08)' : 'rgba(245,158,11,0.08)';
    const bdColor   = isEmpty ? 'rgba(239,68,68,0.2)'  : isCrit ? 'rgba(239,68,68,0.2)'  : 'rgba(245,158,11,0.2)';
    const label     = isEmpty ? 'HABIS' : isCrit ? 'KRITIS' : 'RENDAH';
    const labelColor= isEmpty ? '#f87171' : isCrit ? '#f87171' : '#fbbf24';
    const icon      = isEmpty ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill';

    return `
      <div style="
        display:flex;align-items:center;gap:12px;
        padding:12px 14px;margin-bottom:8px;
        background:${bgColor};
        border:1px solid ${bdColor};
        border-radius:10px;
        transition:all 0.2s
      " onmouseover="this.style.borderColor='${barColor}';this.style.transform='translateX(3px)'"
         onmouseout="this.style.borderColor='${bdColor}';this.style.transform='translateX(0)'">

        <!-- Icon -->
        <div style="
          width:36px;height:36px;flex-shrink:0;
          border-radius:8px;
          background:${isEmpty||isCrit?'rgba(239,68,68,0.15)':'rgba(245,158,11,0.15)'};
          display:flex;align-items:center;justify-content:center
        ">
          <i class="bi ${icon}" style="font-size:16px;color:${labelColor}"></i>
        </div>

        <!-- Info -->
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
            <div style="font-size:13px;font-weight:700;color:var(--text-primary);
              white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px"
              title="${i.name}">${i.name}</div>
            <span style="
              font-size:10px;font-weight:700;letter-spacing:0.5px;
              padding:2px 7px;border-radius:20px;flex-shrink:0;margin-left:6px;
              background:${isEmpty||isCrit?'rgba(239,68,68,0.15)':'rgba(245,158,11,0.15)'};
              color:${labelColor};border:1px solid ${bdColor}
            ">${label}</span>
          </div>

          <!-- Progress bar -->
          <div style="height:5px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden;margin-bottom:5px">
            <div style="
              height:100%;width:${Math.max(pct,2)}%;
              background:${barColor};
              border-radius:3px;
              box-shadow:0 0 6px ${barColor}80;
              transition:width 0.6s ease
            "></div>
          </div>

          <!-- Stock numbers -->
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:11px;color:var(--text-muted)">
              Stok: <strong style="color:${labelColor}">${parseFloat(i.stock)} ${i.unit}</strong>
            </span>
            <span style="font-size:11px;color:var(--text-muted)">
              Min: ${parseFloat(i.min_stock)} ${i.unit}
            </span>
          </div>
        </div>
      </div>`;
  }).join('');
}

function renderDashboardOrders(orders) {
  const tbody = document.getElementById('dashboard-orders');
  if (!orders?.length) { tbody.innerHTML = '<tr><td colspan="7" class="text-muted text-sm" style="text-align:center;padding:24px">Belum ada order</td></tr>'; return; }
  tbody.innerHTML = orders.map(o => `
    <tr onclick="navigate('orders')" style="cursor:pointer">
      <td style="color:var(--accent);font-family:monospace;font-size:12px">${o.order_number}</td>
      <td>${o.title}</td>
      <td>${o.customer}</td>
      <td><span class="badge badge-${o.status}">${statusLabel(o.status)}</span></td>
      <td><span class="badge badge-${o.priority}">${priorityLabel(o.priority)}</span></td>
      <td style="font-size:12px;color:${isDueSoon(o.due_date)?'var(--danger)':'var(--text-muted)'}">${formatDate(o.due_date)}</td>
      <td style="font-weight:600;color:var(--success)">${formatCurrency(o.grand_total)}</td>
    </tr>
  `).join('');
}

function renderOrderChart(data) {
  const ctx = document.getElementById('chartOrders').getContext('2d');
  if (chartOrders) chartOrders.destroy();
  const labels = data?.map(d => formatDateShort(d.date)) || [];
  const values = data?.map(d => parseInt(d.count)) || [];
  chartOrders = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Jumlah Order',
        data: values,
        backgroundColor: 'rgba(99,102,241,0.4)',
        borderColor: '#6366f1',
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 }, beginAtZero: true }
      }
    }
  });
}

function renderStockChart(data) {
  const ctx = document.getElementById('chartStock').getContext('2d');
  if (chartStock) chartStock.destroy();
  const labels = data?.map(d => d.name) || [];
  const values = data?.map(d => parseFloat(d.value)) || [];
  const colors = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444'];
  chartStock = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: colors, borderColor: 'transparent', borderWidth: 0, hoverOffset: 8 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { position: 'right', labels: { color: '#94a3b8', font: { size: 11 }, boxWidth: 12, padding: 12 } },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${formatCurrency(ctx.raw)}` } }
      }
    }
  });
}

function renderThroughputChart(data) {
  const ctx = document.getElementById('chartThroughput')?.getContext('2d');
  if (!ctx) return;
  if (chartThroughput) chartThroughput.destroy();

  // Generate 14 hari terakhir sebagai label (agar hari tanpa order tetap tampil)
  const labels = [];
  const values = [];
  for (let i = 13; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().slice(0, 10);
    labels.push(formatDateShort(key));
    const found = data?.find(r => r.date === key);
    values.push(found ? parseInt(found.selesai) : 0);
  }

  chartThroughput = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Order Selesai',
        data: values,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.08)',
        borderWidth: 2.5,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#10b981',
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: items => `${items[0].label}`,
            label: item => ` ${item.raw} order diselesaikan`,
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#94a3b8', font: { size: 10 } }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 },
          beginAtZero: true,
          suggestedMax: 5,
        }
      }
    }
  });
}

// ============================================================
// MES MONITORING + FIFO TRACKING
// ============================================================
let trackingInterval   = null;
let dashboardInterval  = null;

function startDashboardInterval() {
  if (dashboardInterval) clearInterval(dashboardInterval);
  // Auto-refresh dashboard setiap 30 detik
  dashboardInterval = setInterval(() => {
    if (currentPage === 'dashboard') loadDashboard();
  }, 30000);
}

async function loadMonitoring() {
  if (trackingInterval) clearInterval(trackingInterval);
  await renderMesMonitoring();
  trackingInterval = setInterval(renderMesMonitoring, 5000);
}

async function renderMesMonitoring() {
  const res = await apiFetch(API + 'orders.php?action=mes_monitoring');
  if (!res?.success) return;

  const { fifo_queue, pipeline, stats } = res;
  const board = document.getElementById('kanban-board');

  // ── Kartu KPI ──────────────────────────────────────────────
  const avgHours = stats.avg_minutes ? formatLeadTime(parseFloat(stats.avg_minutes) / 60) : '—';
  const kpiHtml = `
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:24px">
      ${[
        { label:'Order Aktif',       val: stats.active     || 0, icon:'activity',      color:'#6366f1', bg:'rgba(99,102,241,0.12)'  },
        { label:'Sedang Diproses',   val: stats.in_progress|| 0, icon:'zap',           color:'#fbbf24', bg:'rgba(251,191,36,0.12)'  },
        { label:'Selesai Hari Ini',  val: stats.done_today || 0, icon:'check-circle',  color:'#10b981', bg:'rgba(16,185,129,0.12)'  },
        { label:'Overdue',           val: stats.overdue    || 0, icon:'alert-triangle',color:'#ef4444', bg:'rgba(239,68,68,0.12)'   },
        { label:'Avg Lead Time',     val: avgHours,          icon:'clock',           color:'#06b6d4', bg:'rgba(6,182,212,0.12)'   },
      ].map(k => `
        <div style="background:${k.bg};border:1px solid ${k.color}33;border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:14px">
          <div style="width:38px;height:38px;border-radius:10px;background:${k.color}22;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i data-feather="${k.icon}" style="width:18px;height:18px;stroke:${k.color}"></i>
          </div>
          <div>
            <div style="font-size:20px;font-weight:800;color:${k.color};line-height:1">${k.val}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px">${k.label}</div>
          </div>
        </div>`).join('')}
    </div>`;

  // ── FIFO Queue Table ────────────────────────────────────────
  const fifoHtml = `
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px">
            <i data-feather="list" style="width:15px;height:15px;stroke:var(--accent)"></i>
            Antrian Produksi — FIFO
          </div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
            Order diurutkan dari yang paling lama masuk (First In = dikerjakan pertama)
          </div>
        </div>
        <div style="font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
          <div style="width:6px;height:6px;border-radius:50%;background:#34d399;animation:pulse-glow 2s infinite"></div>
          Live — auto refresh 5 detik
        </div>
      </div>
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:var(--bg-base)">
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap">#</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">No. Order</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">Pesanan</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">Pelanggan</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">Status</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">Prioritas</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap">Umur Order</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);white-space:nowrap">Jatuh Tempo</th>
              <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">FIFO</th>
            </tr>
          </thead>
          <tbody>
            ${fifo_queue.length ? fifo_queue.map((o, idx) => {
              const age    = fifoAgeLabel(o.age_minutes);
              const isOver = o.due_status === 'overdue';
              const isToday= o.due_status === 'today';
              // FIFO violation: order dengan prioritas urgent tapi bukan yang paling awal masuk
              const fifoOk = o.priority !== 'urgent' || idx === 0 || fifo_queue[idx-1]?.priority === 'urgent';
              const rowBg  = idx % 2 === 0 ? '' : 'background:var(--bg-base)';
              return `
                <tr style="border-top:1px solid var(--border);cursor:pointer;${rowBg}"
                    onclick="openOrderStatusModal(${o.id_orders})"
                    onmouseover="this.style.background='var(--bg-card-hover)'"
                    onmouseout="this.style.background='${idx%2===0?'':'var(--bg-base)'}'">
                  <td style="padding:12px 16px">
                    <div style="width:24px;height:24px;border-radius:50%;background:var(--bg-surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text-muted)">${idx+1}</div>
                  </td>
                  <td style="padding:12px 16px;font-family:monospace;font-size:12px;color:var(--accent)">${o.order_number}</td>
                  <td style="padding:12px 16px;font-weight:600;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${o.title}</td>
                  <td style="padding:12px 16px;color:var(--text-secondary)">${o.customer_name}</td>
                  <td style="padding:12px 16px"><span class="badge badge-${o.status}">${statusLabel(o.status)}</span></td>
                  <td style="padding:12px 16px"><span class="badge badge-${o.priority}">${priorityLabel(o.priority)}</span></td>
                  <td style="padding:12px 16px">
                    <div style="font-size:12px;font-weight:600;color:${o.age_minutes > 2880 ? '#f87171' : o.age_minutes > 1440 ? '#fbbf24' : 'var(--text-secondary)'}">${age}</div>
                    <div style="font-size:10px;color:var(--text-muted)">${formatDateTimeShort(o.created_at)}</div>
                  </td>
                  <td style="padding:12px 16px">
                    <span style="font-size:12px;font-weight:600;color:${isOver?'#f87171':isToday?'#fbbf24':'var(--text-secondary)'}">
                      ${isOver?'<i class="bi bi-exclamation-circle-fill" style="margin-right:3px"></i>':''}${formatDate(o.due_date)}
                    </span>
                  </td>
                  <td style="padding:12px 16px">
                    ${fifoOk
                      ? `<span style="font-size:11px;font-weight:700;color:#10b981;display:flex;align-items:center;gap:4px"><i data-feather="check" style="width:12px;height:12px"></i> OK</span>`
                      : `<span style="font-size:11px;font-weight:700;color:#f59e0b;display:flex;align-items:center;gap:4px"><i data-feather="alert-triangle" style="width:12px;height:12px"></i> Diprioritaskan</span>`
                    }
                  </td>
                </tr>`;
            }).join('') : `
              <tr>
                <td colspan="9" style="padding:40px;text-align:center;color:var(--text-muted)">
                  <i data-feather="inbox" style="width:32px;height:32px;margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;opacity:0.4"></i>
                  Tidak ada order aktif
                </td>
              </tr>`}
          </tbody>
        </table>
      </div>
    </div>`;

  // ── Pipeline Kanban ─────────────────────────────────────────
  const pipelineCols = {
    pending:       { label:'Pending',      color:'#94a3b8', icon:'clock'        },
    confirmed:     { label:'Dikonfirmasi', color:'#60a5fa', icon:'check-circle' },
    in_progress:   { label:'Diproses',     color:'#fbbf24', icon:'zap'          },
    quality_check: { label:'Quality Check',color:'#22d3ee', icon:'eye'          },
  };

  const pipelineHtml = `
    <div style="margin-bottom:8px">
      <div style="font-size:14px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px">
        <i data-feather="columns" style="width:15px;height:15px;stroke:var(--accent)"></i>
        Pipeline Produksi
      </div>
      <div style="font-size:11px;color:var(--text-muted)">Status setiap order beserta lama waktu di tahap tersebut</div>
    </div>
    <div class="kanban-board">
      ${Object.entries(pipelineCols).map(([key, c]) => {
        const cards = pipeline[key] || [];
        return `
          <div class="kanban-col" style="border-top:3px solid ${c.color}">
            <div class="kanban-col-header">
              <div style="display:flex;align-items:center;gap:7px">
                <i data-feather="${c.icon}" style="width:13px;height:13px;stroke:${c.color}"></i>
                <div class="kanban-col-title" style="color:${c.color}">${c.label}</div>
              </div>
              <div class="kanban-count">${cards.length}</div>
            </div>
            <div class="kanban-cards">
              ${cards.length ? cards.map(o => {
                const hrs      = parseInt(o.hours_in_status) || 0;
                const hrsColor = hrs > 24 ? '#f87171' : hrs > 8 ? '#fbbf24' : '#94a3b8';
                return `
                  <div class="kanban-card" onclick="openOrderStatusModal(${o.id_orders})" title="Klik untuk update status">
                    <div class="kanban-card-title">${o.title}</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                      <span class="badge badge-${o.priority}" style="font-size:10px">${priorityLabel(o.priority)}</span>
                      <span style="font-size:10px;font-weight:700;color:${hrsColor}" title="Lama di status ini">
                        <i data-feather="clock" style="width:9px;height:9px;margin-right:2px"></i>${hrs > 0 ? hrs+'j' : '<1j'}
                      </span>
                    </div>
                    <div class="kanban-card-meta">
                      <span class="kanban-card-customer">
                        <i data-feather="user" style="width:10px;height:10px;margin-right:2px"></i>${o.customer_name}
                      </span>
                      <span class="kanban-card-due" style="color:${isDueSoon(o.due_date)?'var(--danger)':'var(--text-muted)'}">
                        <i data-feather="calendar" style="width:10px;height:10px;margin-right:2px"></i>${formatDate(o.due_date)}
                      </span>
                    </div>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:6px;font-family:monospace">${o.order_number}</div>
                  </div>`;
              }).join('') : `
                <div style="text-align:center;padding:28px 16px;color:var(--text-muted)">
                  <i data-feather="inbox" style="width:22px;height:22px;margin-bottom:6px;display:block;margin-left:auto;margin-right:auto;opacity:0.35"></i>
                  <div style="font-size:11px">Kosong</div>
                </div>`}
            </div>
          </div>`;
      }).join('')}
    </div>`;

  board.innerHTML = kpiHtml + fifoHtml + pipelineHtml;
  feather.replace();
}

// Dari MES Monitoring — klik order langsung buka di halaman Orders
function openOrderStatusModal(orderId) {
  navigate('orders');
  // Highlight order setelah halaman load
  setTimeout(() => {
    const card = document.getElementById(`order-card-${orderId}`);
    if (card) {
      card.scrollIntoView({ behavior: 'smooth', block: 'center' });
      card.style.transition = 'box-shadow .3s ease';
      card.style.boxShadow  = '0 0 0 3px var(--primary)';
      setTimeout(() => { card.style.boxShadow = ''; }, 2000);
    }
  }, 600);
}

// Helper: format umur order jadi label manusiawi
function fifoAgeLabel(minutes) {
  if (minutes < 60)   return minutes + ' mnt';
  if (minutes < 1440) return Math.floor(minutes / 60) + ' jam';
  return Math.floor(minutes / 1440) + ' hari';
}

// Helper: format lead time dari satuan jam ke label manusiawi
function formatLeadTime(hours) {
  const totalMinutes = Math.round(hours * 60);
  if (totalMinutes < 1)   return '< 1 mnt';
  if (totalMinutes < 60)  return totalMinutes + ' mnt';
  const days  = Math.floor(totalMinutes / 1440);
  const jam   = Math.floor((totalMinutes % 1440) / 60);
  const menit = totalMinutes % 60;
  if (days > 0) return days + ' hari' + (jam > 0 ? ' ' + jam + ' jam' : '');
  return jam + ' jam' + (menit > 0 ? ' ' + menit + ' mnt' : '');
}

// Helper: format datetime pendek
function formatDateTimeShort(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toLocaleDateString('id-ID', { day:'2-digit', month:'short' })
       + ' ' + d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
}

// ============================================================
// ITEMS
// ============================================================
async function loadItems() {
  const search   = document.getElementById('items-search').value;
  const category = document.getElementById('items-cat-filter').value;
  const lowStock = document.getElementById('items-lowstock-filter').checked ? '1' : '';
  const params   = new URLSearchParams({ action: 'list', search, category, low_stock: lowStock });
  const data     = await apiFetch(`${API}items.php?${params}`);
  allItems       = data?.data || [];
  populateItemSelects(allItems);
  renderItemsTable(allItems);
  feather.replace();
}

function filterItems() { loadItems(); }

function renderItemsTable(items) {
  const tbody = document.getElementById('items-tbody');
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><h3>Tidak ada item ditemukan</h3><p>Coba ubah filter pencarian</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = items.map(i => {
    const pct = i.min_stock > 0 ? Math.min(100, (i.stock / i.min_stock) * 100) : 100;
    const status = parseFloat(i.stock) <= parseFloat(i.min_stock) ? 'kritis' : parseFloat(i.stock) <= parseFloat(i.min_stock)*1.5 ? 'rendah' : 'aman';
    return `
      <tr>
        <td style="font-family:monospace;font-size:12px;color:var(--accent)">${i.code}</td>
        <td style="font-weight:600">${i.name}</td>
        <td>${i.category_name}</td>
        <td>
          <div style="font-weight:700;font-size:14px">${parseFloat(i.stock)} ${i.unit_symbol}</div>
          <div class="progress-bar" style="width:80px"><div class="progress-fill ${pct<=25?'danger':pct<=75?'warning':'success'}" style="width:${pct}%"></div></div>
        </td>
        <td style="color:var(--text-muted)">${parseFloat(i.min_stock)} ${i.unit_symbol}</td>
        <td><span class="badge badge-${status}">${status.toUpperCase()}</span></td>
        <td>${formatCurrency(i.purchase_price)}</td>
        <td style="color:var(--text-muted);font-size:12px">${i.location || '—'}</td>
        <td>
          <div style="display:flex;gap:4px">
            <button class="btn btn-success btn-sm btn-icon" onclick="navigate('stock-mutation')" title="Stok Masuk"><i data-feather="arrow-down-circle"></i></button>
            <button class="btn btn-secondary btn-sm btn-icon" title="Edit" onclick="openEditItemModal(${i.id_items})"><i data-feather="edit-2"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" title="Hapus" onclick="deleteItem(${i.id_items})"><i data-feather="trash-2"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ID item yang akan dihapus (disimpan saat modal konfirmasi dibuka)
let pendingDeleteItemId = null;

async function deleteItem(id) {
  if (!checkPermission('delete')) return;
  // Cari nama item
  const item = allItems.find(i => i.id_items == id);
  const name = item ? item.name : 'item ini';
  // Simpan id, tampilkan modal konfirmasi
  pendingDeleteItemId = id;
  document.getElementById('confirm-delete-name').textContent = name;
  openModal('modal-confirm-delete');
}

async function confirmDeleteItem() {
  if (!pendingDeleteItemId) return;
  const btn = document.getElementById('confirm-delete-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Menghapus...';
  feather.replace();

  const res = await apiFetch(`${API}items.php?id=${pendingDeleteItemId}`, { method: 'DELETE' });

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="trash-2"></i> Hapus';
  feather.replace();
  pendingDeleteItemId = null;
  closeModal('modal-confirm-delete');

  if (res?.berhasil) {
    showToast('Item berhasil dihapus', 'success');
    loadItems();
  } else {
    showToast('Gagal menghapus item', 'error');
  }
}

function openAddItemModal() { openModal('modal-add-item'); }

async function openEditItemModal(id) {
  // Ambil data item dari server
  const data = await apiFetch(`${API}items.php?action=get&id=${id}`);
  if (!data?.berhasil) {
    showToast('Gagal mengambil data item', 'error');
    return;
  }
  
  const item = data.data;
  
  // Ubah judul modal
  document.getElementById('modal-item-title').textContent = 'Edit Bahan Baku';
  
  // Isi form dengan data item
  document.getElementById('item-id').value = item.id_items;
  document.getElementById('item-code').value = item.code;
  document.getElementById('item-code').disabled = true; // Kode tidak bisa diubah
  document.getElementById('item-name').value = item.name;
  document.getElementById('item-category').value = item.category_id;
  document.getElementById('item-unit').value = item.unit_id;
  document.getElementById('item-location').value = item.location || '';
  document.getElementById('item-stock').value = parseFloat(item.stock);
  document.getElementById('item-stock').disabled = true; // Stok tidak bisa diubah manual
  document.getElementById('item-min-stock').value = parseFloat(item.min_stock);
  document.getElementById('item-buy-price').value = parseFloat(item.purchase_price);
  document.getElementById('item-desc').value = item.description || '';
  
  openModal('modal-add-item');
  feather.replace();
}

async function submitAddItem(e) {
  e.preventDefault();
  
  const itemId = document.getElementById('item-id').value;
  const isEdit = itemId !== '';
  
  // Cek permission
  if (isEdit && !checkPermission('edit')) return;
  if (!isEdit && !checkPermission('create')) return;
  
  const data = {
    code: document.getElementById('item-code').value,
    name: document.getElementById('item-name').value,
    category_id: document.getElementById('item-category').value,
    unit_id: document.getElementById('item-unit').value,
    location: document.getElementById('item-location').value,
    stock: document.getElementById('item-stock').value,
    min_stock: document.getElementById('item-min-stock').value,
    max_stock: 0,
    purchase_price: document.getElementById('item-buy-price').value,
    selling_price: 0,
    description: document.getElementById('item-desc').value,
  };
  
  let res;
  if (isEdit) {
    // Update item
    data.id = itemId;
    res = await apiPut(`${API}items.php`, data);
  } else {
    // Tambah item baru
    res = await apiPost(`${API}items.php?action=add`, data);
  }
  
  if (res?.berhasil) {
    showToast(isEdit ? 'Item berhasil diupdate' : 'Item berhasil ditambahkan', 'success');
    closeModal('modal-add-item');
    
    // Reset form
    e.target.reset();
    document.getElementById('item-id').value = '';
    document.getElementById('item-code').disabled = false;
    document.getElementById('item-stock').disabled = false;
    document.getElementById('modal-item-title').textContent = 'Tambah Bahan Baku';
    
    loadItems();
    loadRefData();
  } else {
    showToast(res?.pesan || 'Gagal menyimpan item', 'error');
  }
}

// ============================================================
// STOCK MUTATION
// ============================================================
async function loadStockMutationPage() {
  if (!allItems.length) {
    const data = await apiFetch(`${API}items.php?action=list`);
    allItems = data?.data || [];
    populateItemSelects(allItems);
  }
  // Langsung load semua riwayat mutasi saat halaman dibuka
  loadMutations();
}

async function submitStockIn() {
  if (!checkPermission('create')) return;
  const itemId = document.getElementById('in-item-id').value;
  const qty    = document.getElementById('in-qty').value;
  const price  = document.getElementById('in-price').value;
  const notes  = document.getElementById('in-notes').value;
  if (!itemId || !qty || qty <= 0) { showToast('Pilih item dan masukkan jumlah yang valid', 'warning'); return; }
  const res = await apiPost(`${API}items.php?action=stock_in`, { item_id: itemId, quantity: qty, unit_price: price, notes });
  if (res?.berhasil) {
    showToast('Stok masuk berhasil dicatat', 'success');
    document.getElementById('in-qty').value   = '';
    document.getElementById('in-price').value = '';
    document.getElementById('in-notes').value = '';
    // Set filter ke item yang baru dimutasi lalu reload riwayat
    document.getElementById('mutation-item-filter').value = itemId;
    loadMutations();
    loadItems(); // update stok di tabel bahan baku
  } else showToast(res?.pesan || 'Gagal mencatat stok masuk', 'error');
}

async function submitStockOut() {
  if (!checkPermission('create')) return;
  const itemId = document.getElementById('out-item-id').value;
  const qty    = document.getElementById('out-qty').value;
  const notes  = document.getElementById('out-notes').value;
  if (!itemId || !qty || qty <= 0) { showToast('Pilih item dan masukkan jumlah yang valid', 'warning'); return; }
  const res = await apiPost(`${API}items.php?action=stock_out`, { item_id: itemId, quantity: qty, notes });
  if (res?.berhasil) {
    showToast('Stok keluar berhasil dicatat', 'success');
    document.getElementById('out-qty').value   = '';
    document.getElementById('out-notes').value = '';
    // Set filter ke item yang baru dimutasi lalu reload riwayat
    document.getElementById('mutation-item-filter').value = itemId;
    loadMutations();
    loadItems(); // update stok di tabel bahan baku
  } else showToast(res?.pesan || 'Gagal mencatat stok keluar', 'error');
}

async function loadMutations() {
  const itemId = document.getElementById('mutation-item-filter').value;
  const tbody  = document.getElementById('mutations-tbody');

  // Tampilkan loading
  tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Memuat...</td></tr>';

  // Fetch — kalau itemId kosong = semua item, kalau ada = filter item tertentu
  const url  = itemId
    ? `${API}items.php?action=transactions&id=${itemId}`
    : `${API}items.php?action=transactions_all`;
  const data = await apiFetch(url);

  if (!data?.data?.length) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Belum ada riwayat mutasi</td></tr>';
    return;
  }
  tbody.innerHTML = data.data.map(t => `
    <tr>
      <td style="font-size:12px">${formatDateTime(t.created_at)}</td>
      <td>${allItems.find(i => i.id_items == t.item_id)?.name || t.item_name || '—'}</td>
      <td><span class="badge badge-${t.type==='in'?'completed':'cancelled'}">${t.type==='in'?'MASUK':'KELUAR'}</span></td>
      <td style="font-weight:700;color:${t.type==='in'?'var(--success)':'var(--danger)'}">${t.type==='in'?'+':'−'}${parseFloat(t.quantity)}</td>
      <td>${parseFloat(t.stock_before)}</td>
      <td style="font-weight:600">${parseFloat(t.stock_after)}</td>
      <td style="font-size:11px;color:var(--text-muted)">${t.reference_type || '—'}</td>
      <td style="color:var(--text-muted);font-size:12px">${t.user_name || '—'}</td>
    </tr>
  `).join('');
}

// ============================================================
// PRODUCTS — Daftar Produk & Harga
// ============================================================
let allProducts = [];

async function loadProducts() {
  // Load semua produk termasuk nonaktif agar bisa di-toggle
  const data = await apiFetch(API + 'products.php?action=all');
  allProducts = data?.data || [];
  renderProductsTable(allProducts);
  feather.replace();
}

function filterProducts() {
  const q = document.getElementById('products-search').value.toLowerCase();
  renderProductsTable(allProducts.filter(p =>
    p.name.toLowerCase().includes(q) || p.code.toLowerCase().includes(q)
  ));
}

function renderProductsTable(products) {
  const tbody = document.getElementById('products-tbody');
  if (!tbody) return;
  if (!products.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
      <i data-feather="tag"></i><h3>Belum ada produk</h3>
      <p>Tambahkan produk/jasa yang tersedia di percetakan ini.</p>
    </div></td></tr>`;
    feather.replace(); return;
  }
  tbody.innerHTML = products.map(p => {
    const isActive = p.is_active == '1' || p.is_active === 1;
    return `
    <tr style="${!isActive ? 'opacity:0.55' : ''}">
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${p.code}</td>
      <td style="font-weight:600">
        ${p.name}
        ${!isActive ? '<span class="badge badge-cancelled" style="margin-left:8px;font-size:10px">Nonaktif</span>' : ''}
      </td>
      <td style="color:var(--text-muted)">${p.category_name || '—'}</td>
      <td style="color:var(--text-muted)">${p.unit_symbol   || '—'}</td>
      <td style="font-weight:600;color:var(--success)">${formatCurrency(p.default_price)}</td>
      <td style="color:var(--text-muted);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        ${p.description || '—'}
      </td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn btn-secondary btn-sm btn-icon" onclick="openEditProductModal(${p.id_products})" title="Edit">
            <i data-feather="edit-2"></i>
          </button>
          <button class="btn btn-warning btn-sm btn-icon" onclick="openBOMModal(${p.id_products},'${p.name.replace(/'/g,"\\'")}')" title="Kelola Bahan Baku">
            <i data-feather="layers"></i>
          </button>
          ${isActive
            ? `<button class="btn btn-danger btn-sm btn-icon" onclick="deleteProduct(${p.id_products})" title="Nonaktifkan">
                <i data-feather="eye-off"></i>
               </button>`
            : `<button class="btn btn-success btn-sm btn-icon" onclick="reactivateProduct(${p.id_products})" title="Aktifkan Kembali">
                <i data-feather="eye"></i>
               </button>`
          }
        </div>
      </td>
    </tr>`;
  }).join('');
  feather.replace();
}

function openAddProductModal() {
  if (!checkPermission('create')) return;
  document.getElementById('modal-product-title').textContent = 'Tambah Produk';
  document.getElementById('product-id').value    = '';
  document.getElementById('product-name').value  = '';
  document.getElementById('product-price').value = '';
  document.getElementById('product-desc').value  = '';
  document.getElementById('product-category').value = '';
  document.getElementById('product-unit').value  = '';
  document.getElementById('product-active-wrap').style.display = 'none';

  // Isi dropdown kategori & unit
  const catSel = document.getElementById('product-category');
  catSel.innerHTML = '<option value="">-- Pilih Kategori --</option>';
  refCategories.forEach(c => catSel.innerHTML += `<option value="${c.id_categories}">${c.name}</option>`);
  const unitSel = document.getElementById('product-unit');
  unitSel.innerHTML = '<option value="">-- Pilih Satuan --</option>';
  refUnits.forEach(u => unitSel.innerHTML += `<option value="${u.id_units}">${u.name} (${u.symbol})</option>`);

  openModal('modal-add-product');
  feather.replace();
}

async function openEditProductModal(id) {
  if (!checkPermission('edit')) return;
  const data = await apiFetch(`${API}products.php?action=get&id=${id}`);
  const p = data?.data;
  if (!p) { showToast('Gagal memuat data produk', 'error'); return; }

  document.getElementById('modal-product-title').textContent = 'Edit Produk';
  document.getElementById('product-id').value    = p.id_products;
  document.getElementById('product-name').value  = p.name;
  document.getElementById('product-price').value = parseFloat(p.default_price);
  document.getElementById('product-desc').value  = p.description || '';
  document.getElementById('product-active-wrap').style.display = 'block';
  document.getElementById('product-active').checked = p.is_active == 1;

  // Isi dropdown
  const catSel = document.getElementById('product-category');
  catSel.innerHTML = '<option value="">-- Pilih Kategori --</option>';
  refCategories.forEach(c => catSel.innerHTML += `<option value="${c.id_categories}">${c.name}</option>`);
  catSel.value = p.category_id || '';

  const unitSel = document.getElementById('product-unit');
  unitSel.innerHTML = '<option value="">-- Pilih Satuan --</option>';
  refUnits.forEach(u => unitSel.innerHTML += `<option value="${u.id_units}">${u.name} (${u.symbol})</option>`);
  unitSel.value = p.unit_id || '';

  openModal('modal-add-product');
  feather.replace();
}

async function submitProduct(e) {
  e.preventDefault();
  const id     = document.getElementById('product-id').value;
  const isEdit = id !== '';
  if (!checkPermission(isEdit ? 'edit' : 'create')) return;

  const payload = {
    name:          document.getElementById('product-name').value,
    default_price: document.getElementById('product-price').value,
    category_id:   document.getElementById('product-category').value || null,
    unit_id:       document.getElementById('product-unit').value     || null,
    description:   document.getElementById('product-desc').value,
    is_active:     document.getElementById('product-active').checked ? 1 : 0,
  };

  let res;
  if (isEdit) { payload.id = id; res = await apiPut(`${API}products.php`, payload); }
  else        { res = await apiPost(`${API}products.php`, payload); }

  if (res?.success) {
    showToast(isEdit ? 'Produk diupdate' : 'Produk ditambahkan', 'success');
    closeModal('modal-add-product');
    loadProducts();
  } else {
    showToast(res?.message || 'Gagal menyimpan produk', 'error');
  }
}

async function reactivateProduct(id) {
  if (!checkPermission('edit')) return;
  const product = allProducts.find(p => p.id_products == id);
  if (!product) { showToast('Produk tidak ditemukan', 'error'); return; }

  const res = await apiPut(`${API}products.php`, {
    id:            product.id_products,
    name:          product.name,
    category_id:   product.category_id || null,
    unit_id:       product.unit_id     || null,
    default_price: product.default_price || 0,
    description:   product.description  || '',
    is_active:     1,
  });

  if (res?.success) {
    showToast(`"${product.name}" berhasil diaktifkan kembali`, 'success');
    loadProducts();
  } else {
    showToast(res?.message || 'Gagal mengaktifkan produk', 'error');
  }
}

async function deleteProduct(id) {
  if (!checkPermission('delete')) return;
  const product = allProducts.find(p => p.id_products == id);
  const name    = product ? product.name : 'produk ini';
  pendingDeleteProductId = id;
  document.getElementById('confirm-delete-product-name').textContent = name;
  openModal('modal-confirm-delete-product');
}

async function confirmDeleteProduct() {
  if (!pendingDeleteProductId) return;
  const btn = document.getElementById('confirm-delete-product-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Memproses...';
  feather.replace();

  const res = await apiFetch(`${API}products.php?id=${pendingDeleteProductId}`, { method: 'DELETE' });

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="trash-2" style="width:14px;height:14px"></i> Nonaktifkan';
  feather.replace();
  pendingDeleteProductId = null;
  closeModal('modal-confirm-delete-product');

  if (res?.success) {
    showToast('Produk berhasil dinonaktifkan', 'success');
    loadProducts();
  } else {
    showToast('Gagal menonaktifkan produk', 'error');
  }
}

// ============================================================
// ORDER INPUT PAGE — inline di index.php
// ============================================================
let niSearchTimeout = null;

async function loadOrderInputPage() {
  // Set default due date = 7 hari dari sekarang
  const due = document.getElementById('ni-due');
  if (due && !due.value) {
    const d = new Date(); d.setDate(d.getDate() + 7);
    due.value = d.toISOString().slice(0, 10);
  }

  // Load operator kalau belum ada
  const opSel2 = document.getElementById('ni-operator');
  if (opSel2 && opSel2.options.length <= 1) {
    const ops = await apiFetch('api/dashboard.php?action=operators');
    (ops?.data || []).forEach(o => {
      opSel2.innerHTML += `<option value="${o.id_users}">${o.name}</option>`;
    });
  }

  // Load produk ke dropdown kalau belum ada
  const prodSel = document.getElementById('ni-product-select');
  if (prodSel && prodSel.options.length <= 1) {
    // Gunakan cache allProducts kalau sudah ada, kalau belum fetch
    if (!allProducts.length) {
      const dp = await apiFetch('api/products.php?action=list');
      allProducts = dp?.data || [];
    }
    if (allProducts.length) {
      // Kelompokkan per kategori
      const grouped = {};
      allProducts.forEach(p => {
        const cat = p.category_name || 'Lainnya';
        if (!grouped[cat]) grouped[cat] = [];
        grouped[cat].push(p);
      });
      Object.entries(grouped).forEach(([cat, items]) => {
        const og = document.createElement('optgroup');
        og.label = cat;
        items.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id_products;
          opt.textContent = `${p.name} — ${formatCurrency(p.default_price)}${p.unit_symbol ? ' / ' + p.unit_symbol : ''}`;
          opt.dataset.price = p.default_price;
          opt.dataset.name  = p.name;
          opt.dataset.desc  = p.description || '';
          og.appendChild(opt);
        });
        prodSel.appendChild(og);
      });
    }
  }

  niHitungTotal();
  feather.replace();

  // Tutup dropdown saat klik di luar
  document.addEventListener('click', e => {
    if (!e.target.closest('#ni-cust-search') && !e.target.closest('#ni-cust-dropdown')) {
      const dd = document.getElementById('ni-cust-dropdown');
      if (dd) dd.style.display = 'none';
    }
  }, { once: false });
}

// ---- Autocomplete pelanggan ----
function niSearchCustomer(q) {

// ---- Cek duplikat nomor HP ----
let _niPhoneCheckTimeout = null;
function niCekDuplikatHP(val) {
  const warning = document.getElementById('ni-phone-warning');
  const text    = document.getElementById('ni-phone-warning-text');
  if (!warning || !text) return;

  warning.style.display = 'none';

  // Minimal 8 digit angka
  const digits = val.replace(/[^0-9]/g, '');
  if (digits.length < 8) return;

  clearTimeout(_niPhoneCheckTimeout);
  _niPhoneCheckTimeout = setTimeout(async () => {
    const res = await apiFetch(`api/customers.php?action=check_phone&phone=${encodeURIComponent(val)}`);
    if (res?.found && res.data) {
      const c = res.data;
      text.textContent  = `No. HP sudah terdaftar atas nama "${c.name}"${c.city ? ' (' + c.city + ')' : ''} — order akan dikaitkan ke pelanggan ini.`;
      warning.style.display = 'flex';

      // Auto-isi nama kalau field nama masih kosong
      const nameField = document.getElementById('ni-cust-name');
      if (nameField && !nameField.value.trim()) {
        nameField.value = c.name;
      }
    } else {
      warning.style.display = 'none';
    }
  }, 600);
}
  clearTimeout(niSearchTimeout);
  const dd = document.getElementById('ni-cust-dropdown');
  if (q.length < 2) { dd.style.display = 'none'; return; }
  niSearchTimeout = setTimeout(async () => {
    const r    = await apiFetch(`api/customers.php?action=search&q=${encodeURIComponent(q)}`);
    const list = r?.data || [];
    if (!list.length) { dd.style.display = 'none'; return; }
    dd.innerHTML = list.map(c => `
      <div onclick="niPilihPelanggan(${c.id_customers},'${niEsc(c.name)}','${niEsc(c.phone)}','${niEsc(c.city||'')}','${niEsc(c.address||'')}')"
        style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center"
        onmouseover="this.style.background='var(--bg-card-hover)'"
        onmouseout="this.style.background=''">
        <div>
          <div style="font-weight:600;color:var(--text-primary)">${c.name}</div>
          <div style="font-size:11px;color:var(--text-muted)">${c.phone}${c.city ? ' · ' + c.city : ''}</div>
        </div>
        <div style="font-size:11px;color:var(--accent)">${c.total_orders} order</div>
      </div>`).join('');
    dd.style.display = 'block';
  }, 300);
}

function niPilihPelanggan(id, name, phone, city, address) {
  document.getElementById('ni-cust-name').value    = name;
  document.getElementById('ni-cust-phone').value   = phone;
  document.getElementById('ni-cust-city').value    = city;
  document.getElementById('ni-cust-address').value = address;
  document.getElementById('ni-cust-search').value  = name;
  document.getElementById('ni-cust-dropdown').style.display = 'none';
  showToast('Pelanggan dipilih: ' + name, 'success');
}

function niEsc(s) {
  return String(s).replace(/'/g, "\\'").replace(/\n/g, '');
}

// ---- Array items pesanan ----
let niItems = [];          // [{id, name, qty, price, note}]
let niOrderSubmitted = false; // flag anti double-submit

// ---- Tambah item dari dropdown ----
function niTambahItem() {
  const sel = document.getElementById('ni-product-select');
  const id  = sel.value;
  if (!id) { showToast('Pilih produk terlebih dahulu', 'warning'); return; }

  const opt = sel.querySelector(`option[value="${id}"]`);
  if (!opt) return;

  // Cek kalau sudah ada, tambah qty saja
  const exist = niItems.find(i => i.id_products == id);
  if (exist) {
    exist.qty++;
    niRenderItems();
    niHitungTotal();
    showToast(`Qty ${opt.dataset.name} +1`, 'info');
    sel.value = '';
    return;
  }

  niItems.push({
    id:    id,
    name:  opt.dataset.name,
    price: parseFloat(opt.dataset.price) || 0,
    qty:   1,
    note:  '',
  });
  niRenderItems();
  niHitungTotal();
  sel.value = '';
  feather.replace();
}

// ---- Tambah item manual (tanpa produk) ----
function niTambahManual() {
  niItems.push({ id: null, name: '', price: 0, qty: 1, note: '' });
  niRenderItems();
  niHitungTotal();
  // Fokus ke input nama item terakhir
  setTimeout(() => {
    const inputs = document.querySelectorAll('.ni-item-name');
    const last   = inputs[inputs.length - 1];
    if (last) last.focus();
  }, 50);
}

// ---- Render tabel item ----
function niRenderItems() {
  const tbody     = document.getElementById('ni-items-tbody');
  const empty     = document.getElementById('ni-items-empty');
  const tableWrap = document.getElementById('ni-items-table-wrap');
  if (!tbody) return;

  if (!niItems.length) {
    if (empty)     empty.style.display     = 'block';
    if (tableWrap) tableWrap.style.display = 'none';
    return;
  }
  if (empty)     empty.style.display     = 'none';
  if (tableWrap) tableWrap.style.display = 'block';

  tbody.innerHTML = niItems.map((it, idx) => {
    const sub = it.qty * it.price;
    return `
      <tr style="border-bottom:1px solid rgba(99,102,241,0.08)">
        <td style="padding:8px 6px;color:var(--text-muted);font-size:12px">${idx+1}</td>
        <td style="padding:6px">
          <input class="ni-item-name" value="${niEsc(it.name)}"
            placeholder="Nama produk / keterangan"
            oninput="niItems[${idx}].name=this.value"
            style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:6px;color:var(--text-primary);padding:5px 8px;font-size:13px;font-family:inherit" />
        </td>
        <td style="padding:6px;text-align:center">
          <input type="number" value="${it.qty}" min="1"
            oninput="niItems[${idx}].qty=parseFloat(this.value)||1;niRenderSubtotals();niHitungTotal()"
            style="width:60px;background:var(--bg-input);border:1px solid var(--border);border-radius:6px;color:var(--text-primary);padding:5px 6px;font-size:13px;text-align:center;font-family:inherit" />
        </td>
        <td style="padding:6px;text-align:right">
          <input type="number" value="${it.price}" min="0"
            oninput="niItems[${idx}].price=parseFloat(this.value)||0;niRenderSubtotals();niHitungTotal()"
            style="width:110px;background:var(--bg-input);border:1px solid var(--border);border-radius:6px;color:var(--text-primary);padding:5px 8px;font-size:13px;text-align:right;font-family:inherit" />
        </td>
        <td style="padding:8px 6px;text-align:right;font-weight:600;font-size:13px;color:var(--success)" id="ni-sub-${idx}">
          ${formatCurrency(sub)}
        </td>
        <td style="padding:6px;text-align:center">
          <button onclick="niHapusItem(${idx})"
            style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:var(--danger);border-radius:6px;padding:4px 7px;cursor:pointer;font-size:13px">
            <i data-feather="trash-2" style="width:13px;height:13px"></i>
          </button>
        </td>
      </tr>`;
  }).join('');
  feather.replace();
}

function niRenderSubtotals() {
  niItems.forEach((it, idx) => {
    const el = document.getElementById(`ni-sub-${idx}`);
    if (el) el.textContent = formatCurrency(it.qty * it.price);
  });
}

function niHapusItem(idx) {
  niItems.splice(idx, 1);
  niRenderItems();
  niHitungTotal();
  feather.replace();
}

// ---- Simpan order ---- (diperbarui untuk multi-item)
async function niSimpanOrder() {
  if (!checkPermission('create')) return;

  // Cegah double submit
  if (niOrderSubmitted) {
    showToast('Order sudah disimpan. Klik "Order Baru" untuk membuat order berikutnya.', 'info');
    return;
  }

  const name  = document.getElementById('ni-cust-name').value.trim();
  const phone = document.getElementById('ni-cust-phone').value.trim();
  const due   = document.getElementById('ni-due').value;

  if (!name)         { showToast('Nama pelanggan wajib diisi', 'warning'); return; }
  if (!phone)        { showToast('No. HP pelanggan wajib diisi', 'warning'); return; }
  if (!niItems.length){ showToast('Tambahkan minimal 1 item pesanan', 'warning'); return; }
  if (niItems.some(i => !i.name.trim())){ showToast('Nama item tidak boleh kosong', 'warning'); return; }
  if (niItems.some(i => i.price < 1))  { showToast('Harga item tidak boleh 0', 'warning'); return; }
  if (!due)          { showToast('Jatuh tempo wajib diisi', 'warning'); return; }

  // Blokir jika HP sudah terdaftar dengan nama berbeda
  const digits = phone.replace(/[^0-9]/g, '');
  if (digits.length >= 8) {
    const chk = await apiFetch(`api/customers.php?action=check_phone&phone=${encodeURIComponent(phone)}`);
    if (chk?.found && chk.data) {
      const existingName = chk.data.name.toLowerCase().trim();
      const inputName    = name.toLowerCase().trim();
      if (existingName !== inputName) {
        showToast(
          `No. HP sudah terdaftar atas nama "${chk.data.name}". Gunakan nama yang sama atau ganti nomor HP.`,
          'error'
        );
        // Tampilkan juga warning di field
        const w = document.getElementById('ni-phone-warning');
        const t = document.getElementById('ni-phone-warning-text');
        if (w && t) {
          t.textContent = `No. HP sudah terdaftar atas nama "${chk.data.name}" — gunakan nama yang sama atau ganti nomor HP.`;
          w.style.display = 'flex';
        }
        const btn2 = document.getElementById('ni-btn-simpan');
        if (btn2) { btn2.disabled = false; btn2.innerHTML = '<i data-feather="save"></i> Simpan & Tampilkan Nota'; feather.replace(); }
        return;
      }
    }
  }

  const btn = document.getElementById('ni-btn-simpan');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="refresh-cw"></i> Menyimpan...';
  feather.replace();

  // Hitung total dari items
  const sub   = niItems.reduce((s, i) => s + i.qty * i.price, 0);
  const disc  = parseFloat(document.getElementById('ni-discount').value) || 0;
  const tax   = parseFloat(document.getElementById('ni-tax').value)      || 11;
  const grand = (sub - disc) * (1 + tax / 100);

  // Judul = gabungan nama item
  const titleStr = niItems.length === 1
    ? niItems[0].name
    : niItems.map(i => i.name).join(', ');

  const payload = {
    customer_name:    name,
    customer_phone:   phone,
    customer_city:    document.getElementById('ni-cust-city').value,
    customer_address: document.getElementById('ni-cust-address').value,
    title:            titleStr,
    description:      niItems.map(i => `${i.name} x${i.qty}`).join(' | '),
    quantity:         niItems.reduce((s, i) => s + i.qty, 0),
    unit_price:       niItems[0].price, // harga item pertama
    discount:         disc,
    tax:              tax,
    grand_total_override: grand, // kirim grand total yang sudah dihitung
    items:            niItems,   // array semua item
    operator_id:      document.getElementById('ni-operator').value || null,
    priority:         document.getElementById('ni-priority').value,
    due_date:         due,
    notes:            document.getElementById('ni-notes').value,
  };

  const res = await apiPost('api/orders.php?action=create_with_customer', payload);

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="save"></i> Simpan & Tampilkan Nota';
  feather.replace();

  if (res?.success) {
    showToast('Order ' + res.order_number + ' berhasil disimpan!', 'success');
    if (res.stock_warnings?.length) {
      setTimeout(() => res.stock_warnings.forEach(w => showToast(w, 'warning')), 800);
    }
    // Set flag anti double-submit
    niOrderSubmitted = true;
    // Ubah tombol simpan jadi disabled dengan teks jelas
    const btnSimpan = document.getElementById('ni-btn-simpan');
    if (btnSimpan) {
      btnSimpan.disabled = true;
      btnSimpan.style.opacity = '0.5';
      btnSimpan.innerHTML = '<i data-feather="check-circle"></i> Order Tersimpan';
      feather.replace();
    }
    niTampilkanNota(res.data, res.order_number);
  } else {
    if (res?.stock_errors?.length) {
      niTampilkanErrorStok(res.message, res.stock_errors);
    } else {
      showToast(res?.message || 'Gagal menyimpan order', 'error');
    }
  }
}

// ---- Hitung total ----
function niHitungTotal() {
  // Hitung subtotal dari semua item
  const sub = niItems.reduce((s, it) => s + (it.qty * it.price), 0);
  const disc  = parseFloat(document.getElementById('ni-discount')?.value) || 0;
  const tax   = parseFloat(document.getElementById('ni-tax')?.value)      || 0;
  const taxAmt= (sub - disc) * (tax / 100);
  const total = sub - disc + taxAmt;
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('ni-c-subtotal', formatCurrency(sub));
  set('ni-c-total',    formatCurrency(total));
}

// ---- Tampilkan Nota ----
function niTampilkanNota(o, orderNum) {
  const disc  = parseFloat(o.discount) || 0;
  const tax   = parseFloat(o.tax)      || 0;
  const sub   = niItems.reduce((s, i) => s + i.qty * i.price, 0);
  const taxAmt= (sub - disc) * (tax / 100);
  const total = sub - disc + taxAmt;

  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('ni-nota-num',       orderNum);
  set('ni-nota-num2',      orderNum);
  set('ni-nota-tgl',       'Tanggal: ' + formatDate(new Date().toISOString()));
  set('ni-nota-cust-name', o.customer_name);
  set('ni-nota-cust-phone',o.customer_phone || '—');
  set('ni-nota-cust-city', o.customer_city  || '—');
  set('ni-nota-title',     niItems.map(i => `${i.name} (×${i.qty})`).join(', '));

  // Items detail di nota
  const qtyEl   = document.getElementById('ni-nota-qty');
  const priceEl = document.getElementById('ni-nota-price');
  if (niItems.length === 1) {
    if (qtyEl)   qtyEl.textContent   = niItems[0].qty + ' pcs';
    if (priceEl) priceEl.textContent = formatCurrency(niItems[0].price);
  } else {
    // Multi item — render list
    if (qtyEl)   qtyEl.closest && qtyEl.closest('tr') && (qtyEl.closest('tr').style.display = 'none');
    if (priceEl) priceEl.closest && priceEl.closest('tr') && (priceEl.closest('tr').style.display = 'none');
    const listEl = document.getElementById('ni-nota-items-list');
    const rowEl  = document.getElementById('ni-nota-items-row');
    if (listEl) {
      listEl.innerHTML = niItems.map(i =>
        `<div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px">
          <span>${i.name} ×${i.qty}</span>
          <span>${formatCurrency(i.qty * i.price)}</span>
        </div>`
      ).join('');
    }
    if (rowEl) rowEl.style.display = '';
  }

  set('ni-nota-subtotal', formatCurrency(sub));
  set('ni-nota-disc',     '- ' + formatCurrency(disc));
  set('ni-nota-tax',      '+ ' + formatCurrency(taxAmt));
  set('ni-nota-total',    formatCurrency(total));
  set('ni-nota-due',      formatDate(o.due_date));
  set('ni-nota-priority', {'low':'Rendah','normal':'Normal','high':'Tinggi','urgent':'URGENT'}[o.priority] || o.priority);

  document.getElementById('ni-nota-placeholder').style.display = 'none';
  document.getElementById('ni-nota-content').style.display     = 'block';
  feather.replace();
  if (window.innerWidth < 900) {
    document.getElementById('ni-nota-content').scrollIntoView({behavior:'smooth'});
  }
}

// Tampilkan notifikasi stok tidak cukup (modal inline)
function niTampilkanErrorStok(message, errors) {
  // Hapus alert lama kalau ada
  const old = document.getElementById('ni-stock-error');
  if (old) old.remove();

  const errHtml = errors.map(e => `
    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;
      background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);
      border-radius:8px;margin-bottom:8px">
      <i class="bi bi-x-circle-fill" style="color:var(--danger);flex-shrink:0;margin-top:1px"></i>
      <span style="font-size:13px;color:var(--text-primary)">${e}</span>
    </div>`).join('');

  const alertEl = document.createElement('div');
  alertEl.id = 'ni-stock-error';
  alertEl.style.cssText = `
    background:rgba(239,68,68,0.06);
    border:1.5px solid rgba(239,68,68,0.35);
    border-radius:var(--radius);
    padding:16px;
    margin-bottom:20px;
    animation:fadeIn 0.3s ease;
  `;
  alertEl.innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;color:var(--danger)"></i>
      <div>
        <div style="font-weight:700;color:var(--danger);font-size:14px">Order Gagal — Stok Tidak Mencukupi</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">${message}</div>
      </div>
      <button onclick="document.getElementById('ni-stock-error').remove()"
        style="margin-left:auto;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;line-height:1">×</button>
    </div>
    ${errHtml}
    <div style="font-size:12px;color:var(--text-muted);margin-top:10px">
      <i class="bi bi-info-circle"></i>
      Tambahkan stok bahan baku di halaman <strong>Mutasi Stok</strong> terlebih dahulu.
    </div>
  `;

  // Sisipkan di atas form bahan baku (sebelum form-nota-grid)
  const grid = document.getElementById('order-input-grid');
  if (grid) grid.parentNode.insertBefore(alertEl, grid);

  // Scroll ke atas agar terlihat
  alertEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  showToast('Order dibatalkan — stok bahan baku tidak mencukupi!', 'error');
}

function niResetForm() {  ['ni-cust-name','ni-cust-phone','ni-cust-city','ni-cust-address',
   'ni-cust-search','ni-notes'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  // Sembunyikan warning HP
  const w = document.getElementById('ni-phone-warning');
  if (w) w.style.display = 'none';
  ['ni-discount','ni-tax'].forEach((id, i) => {
    const el = document.getElementById(id); if (el) el.value = [0, 11][i];
  });
  const opSel = document.getElementById('ni-operator');
  if (opSel) opSel.value = '';
  const prioSel = document.getElementById('ni-priority');
  if (prioSel) prioSel.value = 'normal';
  const prodSel = document.getElementById('ni-product-select');
  if (prodSel) prodSel.value = '';

  // Reset items
  niItems = [];
  niRenderItems();
  niHitungTotal();

  // Reset due date
  const due = document.getElementById('ni-due');
  if (due) { const d = new Date(); d.setDate(d.getDate()+7); due.value = d.toISOString().slice(0,10); }

  // Reset nota
  const ph = document.getElementById('ni-nota-placeholder');
  const ct = document.getElementById('ni-nota-content');
  if (ph) ph.style.display = 'block';
  if (ct) ct.style.display = 'none';

  // Reset flag & tombol simpan
  niOrderSubmitted = false;
  const btnSimpan = document.getElementById('ni-btn-simpan');
  if (btnSimpan) {
    btnSimpan.disabled = false;
    btnSimpan.style.opacity = '1';
    btnSimpan.innerHTML = '<i data-feather="save"></i> Simpan & Tampilkan Nota';
    feather.replace();
  }

  window.scrollTo({top:0, behavior:'smooth'});
  // Hapus alert stok jika ada
  const errEl = document.getElementById('ni-stock-error');
  if (errEl) errEl.remove();
}

// ============================================================
// ORDERS  — realtime stepper card
// ============================================================
let ordersInterval = null;
const ORDER_STEPS = [
  { key: 'pending',       label: 'Pending',    icon: 'clock'       },
  { key: 'confirmed',     label: 'Konfirmasi', icon: 'check-square'},
  { key: 'in_progress',   label: 'Proses',     icon: 'settings'    },
  { key: 'quality_check', label: 'QC',         icon: 'shield'      },
  { key: 'completed',     label: 'Selesai',    icon: 'check-circle'},
];
const ORDER_STEP_KEYS = ORDER_STEPS.map(s => s.key);

// Tab aktif sekarang
let currentOrdersTab = 'active';

function switchOrdersTab(tab) {
  currentOrdersTab = tab;
  // Ganti active tab pill
  document.getElementById('orders-tab-active').classList.toggle('active', tab === 'active');
  document.getElementById('orders-tab-history').classList.toggle('active', tab === 'history');
  // Tampilkan konten yang sesuai
  document.getElementById('orders-tab-content-active').style.display  = tab === 'active'  ? 'block' : 'none';
  document.getElementById('orders-tab-content-history').style.display = tab === 'history' ? 'block' : 'none';
  if (tab === 'history') loadOrdersHistory();
  feather.replace();
}

async function loadOrders() {
  const search = document.getElementById('orders-search')?.value || '';
  // Hanya load status AKTIF (bukan completed/cancelled)
  const activeStatuses = ['pending', 'confirmed', 'in_progress', 'quality_check'];
  const params = new URLSearchParams({ action: 'list', search });
  // Tambahkan filter multi-status
  activeStatuses.forEach(s => params.append('statuses[]', s));
  const data = await apiFetch(`${API}orders.php?${params}`);
  allOrders  = data?.data || [];

  // Update badge jumlah aktif
  const badge = document.getElementById('badge-orders-active');
  if (badge) badge.textContent = allOrders.length;

  renderOrdersList(allOrders);
  feather.replace();
}

async function loadOrdersHistory() {
  const statusFilter = document.getElementById('orders-history-filter')?.value || '';
  const search       = document.getElementById('orders-search')?.value || '';
  const params       = new URLSearchParams({ action: 'list', search });
  if (statusFilter) {
    params.append('statuses[]', statusFilter);
  } else {
    params.append('statuses[]', 'completed');
    params.append('statuses[]', 'cancelled');
  }
  const data = await apiFetch(`${API}orders.php?${params}`);
  const orders = data?.data || [];
  const el = document.getElementById('orders-history-list');
  if (!el) return;
  if (!orders.length) {
    el.innerHTML = `<div class="card"><div class="empty-state"><i data-feather="archive"></i><h3>Belum ada riwayat</h3><p>Order yang selesai atau dibatalkan akan muncul di sini</p></div></div>`;
    feather.replace(); return;
  }
  el.innerHTML = orders.map(o => renderOrderCard(o)).join('');
  feather.replace();
}

function filterOrders() {
  if (currentOrdersTab === 'history') loadOrdersHistory();
  else loadOrders();
}

function renderOrdersList(orders) {
  const el = document.getElementById('orders-list');
  if (!el) return;
  if (!orders.length) {
    el.innerHTML = `<div class="card"><div class="empty-state"><i data-feather="file-text"></i><h3>Tidak ada order ditemukan</h3></div></div>`;
    feather.replace(); return;
  }
  el.innerHTML = orders.map(o => renderOrderCard(o)).join('');
  feather.replace();
}

function renderOrderCard(o) {
  const isCancelled = o.status === 'cancelled';
  const curIdx      = ORDER_STEP_KEYS.indexOf(o.status);

  // Build stepper
  let stepperHtml = '';
  ORDER_STEPS.forEach((step, idx) => {
    const isDone    = !isCancelled && idx < curIdx;
    const isActive  = !isCancelled && idx === curIdx;
    const isNext    = !isCancelled && idx === curIdx + 1 && o.status !== 'completed';
    const cls       = isCancelled ? 'cancelled' : isDone ? 'done' : isActive ? 'active' : isNext ? 'next-btn' : '';
    const labelCls  = isDone ? 'done-label' : isActive ? 'active-label' : '';
    const title     = isNext ? `Klik → ${step.label}` : step.label;
    const onclick   = isNext ? `onclick="askUpdateStatus(${o.id_orders},'${o.order_number}','${step.key}','${step.label}')"` : '';

    if (idx > 0) {
      stepperHtml += `<div class="step-connector ${isDone ? 'done' : ''}"></div>`;
    }
    stepperHtml += `
      <div class="step-wrapper">
        <div class="step-dot ${cls}" title="${title}" ${onclick}>
          <i data-feather="${step.icon}"></i>
        </div>
        <div class="step-label ${labelCls}">${step.label}</div>
      </div>`;
  });

  // Cancel button (only when not yet completed/cancelled)
  const canCancel = !['completed','cancelled'].includes(o.status);
  const cancelBtn = canCancel
    ? `<button class="btn btn-danger btn-sm" style="font-size:11px;padding:4px 10px"
         onclick="askCancelOrder(${o.id_orders},'${o.order_number}')">
         <i data-feather="x"></i> Batalkan
       </button>`
    : '';

  // Delivery badge if completed
  const deliveryBtn = o.status === 'completed'
    ? `<button class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px"
         onclick="openNewDeliveryModal(${o.id_orders})">
         <i data-feather="truck"></i> Kirim
       </button>`
    : '';

  return `
    <div class="order-card" id="order-card-${o.id_orders}">
      <div class="order-card-header">
        <div class="order-card-left">
          <div class="order-card-num">${o.order_number}</div>
          <div class="order-card-title">${o.title}</div>
          <div class="order-card-meta">
            <span><i data-feather="user"></i>${o.customer_name}</span>
            ${o.operator_name ? `<span><i data-feather="tool"></i>${o.operator_name}</span>` : ''}
            <span><i data-feather="calendar"></i>${formatDate(o.due_date)}</span>
          </div>
        </div>
        <div class="order-card-right">
          <span class="badge badge-${o.priority}">${priorityLabel(o.priority)}</span>
          <span style="font-weight:700;color:var(--success);font-size:13px">${formatCurrency(o.grand_total)}</span>
          ${cancelBtn}
          ${deliveryBtn}
        </div>
      </div>
      <div class="status-stepper">${stepperHtml}</div>
      ${isCancelled ? '<div style="font-size:12px;color:var(--danger);margin-top:8px"><i class="bi bi-x-octagon-fill"></i> Order dibatalkan</div>' : ''}
    </div>`;
}

// Konfirmasi update status stepper
let _pendingStatusOrderId  = null;
let _pendingStatusNewStatus = null;

function askUpdateStatus(orderId, orderNum, newStatus, label) {
  _pendingStatusOrderId   = orderId;
  _pendingStatusNewStatus = newStatus;
  document.getElementById('confirm-status-order-num').textContent  = orderNum;
  document.getElementById('confirm-status-order-next').textContent = label;
  openModal('modal-confirm-status-order');
  feather.replace();
}

async function confirmUpdateStatus() {
  if (!_pendingStatusOrderId) return;
  const btn = document.getElementById('confirm-status-order-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Memproses...';
  feather.replace();

  await updateOrderStatus(_pendingStatusOrderId, _pendingStatusNewStatus);

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="check" style="width:14px;height:14px"></i> Ya, Update';
  feather.replace();
  closeModal('modal-confirm-status-order');
  _pendingStatusOrderId   = null;
  _pendingStatusNewStatus = null;
}

// Konfirmasi batalkan order
let _pendingCancelOrderId = null;
function askCancelOrder(id, orderNum) {
  _pendingCancelOrderId = id;
  document.getElementById('confirm-cancel-order-num').textContent = orderNum;
  openModal('modal-confirm-cancel-order');
  feather.replace();
}

async function confirmCancelOrder() {
  if (!_pendingCancelOrderId) return;
  const btn = document.getElementById('confirm-cancel-order-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Membatalkan...';
  feather.replace();

  await updateOrderStatus(_pendingCancelOrderId, 'cancelled');

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="x" style="width:14px;height:14px"></i> Ya, Batalkan';
  feather.replace();
  closeModal('modal-confirm-cancel-order');
  _pendingCancelOrderId = null;
}

async function updateOrderStatus(orderId, newStatus) {
  if (!checkPermission('edit')) return;
  const label = statusLabel(newStatus);
  const res = await apiPut(`${API}orders.php`, {
    id: orderId, status: newStatus,
    status_only: true
  });
  if (res?.success) {
    showToast(`Status order → ${label}`, 'success');
    const order = allOrders.find(o => o.id_orders == orderId);
    if (order) {
      order.status = newStatus;
      const card = document.getElementById(`order-card-${orderId}`);

      // Jika status final (completed/cancelled) — animasi fade lalu pindah ke riwayat
      const isFinal = newStatus === 'completed' || newStatus === 'cancelled';
      if (card && isFinal) {
        // Update tampilan stepper dulu
        card.outerHTML = renderOrderCard(order);
        feather.replace();

        const updatedCard = document.getElementById(`order-card-${orderId}`);
        if (updatedCard) {
          // Polling 2 detik lalu fade-out
          setTimeout(() => {
            updatedCard.classList.add('order-card-fadeout');
            // Setelah animasi selesai (1.8s), hapus dari DOM dan update allOrders
            setTimeout(() => {
              updatedCard.remove();
              // Hapus dari array aktif
              allOrders = allOrders.filter(o => o.id_orders != orderId);
              // Update badge tab
              const badgeTab = document.getElementById('badge-orders-active');
              if (badgeTab) badgeTab.textContent = allOrders.length;
              // Tampilkan pesan kalau sudah kosong
              const list = document.getElementById('orders-list');
              if (list && !list.querySelector('.order-card')) {
                list.innerHTML = `<div class="card"><div class="empty-state">
                  <i data-feather="check-circle" style="stroke:var(--success)"></i>
                  <h3>Semua order selesai diproses</h3>
                  <p>Lihat <button onclick="switchOrdersTab('history')" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:13px;text-decoration:underline">Tab Riwayat</button> untuk order yang sudah selesai</p>
                </div></div>`;
                feather.replace();
              }
            }, 1900);
          }, 2000); // Tunggu 2 detik baru mulai fade
        }
      } else if (card) {
        // Status belum final — update card biasa
        card.outerHTML = renderOrderCard(order);
        feather.replace();
      }

      // Jika selesai, tawarkan buat pengiriman
      if (newStatus === 'completed') {
        setTimeout(() => openNewDeliveryModal(orderId), 2200);
      }
    }
  } else {
    showToast(res?.message || 'Gagal update status', 'error');
  }
}

// ── Modal buat pengiriman baru ────────────────────────────────
async function openNewDeliveryModal(orderId) {
  const data  = await apiFetch(`${API}orders.php?action=get&id=${orderId}`);
  const order = data?.data;
  document.getElementById('new-delivery-order-id').value = orderId;
  const info = document.getElementById('new-delivery-order-info');
  if (order) {
    info.innerHTML = `<span style="font-family:monospace;color:var(--accent);font-weight:700">${order.order_number}</span>
      <span style="color:var(--text-primary);margin-left:8px">${order.order_title || order.title}</span>
      <span style="color:var(--text-muted);margin-left:8px;font-size:12px">${order.customer_name}</span>`;
    document.getElementById('nd-recipient-name').value = order.customer_name || '';
  }
  document.getElementById('nd-recipient-phone').value = '';
  document.getElementById('nd-city').value    = '';
  document.getElementById('nd-address').value = '';
  document.getElementById('nd-eta').value     = '';
  document.getElementById('nd-notes').value   = '';
  openModal('modal-new-delivery');
  feather.replace();
}

async function submitNewDelivery() {
  if (!checkPermission('create')) return;
  const orderId = document.getElementById('new-delivery-order-id').value;
  const payload = {
    order_id:            orderId,
    recipient_name:      document.getElementById('nd-recipient-name').value,
    recipient_phone:     document.getElementById('nd-recipient-phone').value,
    destination_city:    document.getElementById('nd-city').value,
    destination_address: document.getElementById('nd-address').value,
    estimated_arrival:   document.getElementById('nd-eta').value || null,
    notes:               document.getElementById('nd-notes').value,
  };
  if (!payload.recipient_name || !payload.destination_city || !payload.destination_address) {
    showToast('Nama penerima, kota, dan alamat wajib diisi', 'warning'); return;
  }
  const res = await apiPost(`${API}deliveries.php`, payload);
  if (res?.success) {
    showToast('Pengiriman berhasil dibuat', 'success');
    closeModal('modal-new-delivery');
    loadOrders();
  } else {
    showToast(res?.message || 'Gagal membuat pengiriman', 'error');
  }
}

// ============================================================
// DELIVERIES — realtime stepper card
// ============================================================
const DELIV_STEPS = [
  { key: 'prepared',  label: 'Disiapkan',        icon: 'package'     },
  { key: 'shipping',  label: 'Dikirim',           icon: 'truck'       },
  { key: 'arrived',   label: 'Tiba di Tujuan',   icon: 'map-pin'     },
  { key: 'received',  label: 'Diterima',          icon: 'check-circle'},
];
const DELIV_STEP_KEYS = DELIV_STEPS.map(s => s.key);

// Tab deliveries aktif sekarang
let currentDeliveriesTab = 'active';

function switchDeliveriesTab(tab) {
  currentDeliveriesTab = tab;
  document.getElementById('deliveries-tab-active').classList.toggle('active', tab === 'active');
  document.getElementById('deliveries-tab-history').classList.toggle('active', tab === 'history');
  document.getElementById('deliveries-tab-content-active').style.display  = tab === 'active'  ? 'block' : 'none';
  document.getElementById('deliveries-tab-content-history').style.display = tab === 'history' ? 'block' : 'none';
  if (tab === 'history') loadDeliveriesHistory();
  feather.replace();
}

async function loadDeliveries() {
  const search = document.getElementById('deliveries-search')?.value || '';
  // Hanya load status AKTIF (bukan received)
  const activeStatuses = ['prepared', 'shipping', 'arrived'];
  const params = new URLSearchParams({ action: 'list', search });
  activeStatuses.forEach(s => params.append('statuses[]', s));
  const data = await apiFetch(`${API}deliveries.php?${params}`);
  allDeliveries = data?.data || [];

  // Badge navbar dan tab
  const inTransit = allDeliveries.filter(d => d.status === 'shipping').length;
  const badgeNav = document.getElementById('badge-deliveries');
  if (badgeNav) { badgeNav.textContent = inTransit; badgeNav.style.display = inTransit > 0 ? 'inline' : 'none'; }
  const badgeTab = document.getElementById('badge-deliveries-active');
  if (badgeTab) badgeTab.textContent = allDeliveries.length;

  renderDeliveriesList(allDeliveries);
  feather.replace();
}

async function loadDeliveriesHistory() {
  const search = document.getElementById('deliveries-search')?.value || '';
  const params = new URLSearchParams({ action: 'list', search });
  params.append('statuses[]', 'received');
  const data   = await apiFetch(`${API}deliveries.php?${params}`);
  const delivs = data?.data || [];
  const el     = document.getElementById('deliveries-history-list');
  if (!el) return;
  if (!delivs.length) {
    el.innerHTML = `<div class="card"><div class="empty-state"><i data-feather="archive"></i><h3>Belum ada riwayat pengiriman</h3><p>Pengiriman yang sudah diterima akan muncul di sini</p></div></div>`;
    feather.replace(); return;
  }
  el.innerHTML = delivs.map(d => renderDeliveryCard(d)).join('');
  feather.replace();
}

function filterDeliveries() {
  if (currentDeliveriesTab === 'history') loadDeliveriesHistory();
  else loadDeliveries();
}

function renderDeliveriesList(deliveries) {
  const el = document.getElementById('deliveries-list');
  if (!el) return;
  if (!deliveries.length) {
    el.innerHTML = `<div class="card"><div class="empty-state"><i data-feather="truck"></i><h3>Belum ada data pengiriman</h3><p>Pengiriman akan muncul setelah order selesai diproduksi</p></div></div>`;
    feather.replace(); return;
  }
  el.innerHTML = deliveries.map(d => renderDeliveryCard(d)).join('');
  feather.replace();
}

function renderDeliveryCard(d) {
  const curIdx = DELIV_STEP_KEYS.indexOf(d.status);
  const isDone = d.status === 'received';

  // Stepper
  let stepperHtml = '';
  DELIV_STEPS.forEach((step, idx) => {
    const stepDone   = idx < curIdx;
    const stepActive = idx === curIdx;
    const stepNext   = idx === curIdx + 1 && !isDone;
    const cls        = stepDone ? 'done' : stepActive ? 'active' : stepNext ? 'next-btn' : '';
    const labelCls   = stepDone ? 'done-label' : stepActive ? 'active-label' : '';

    // "next" untuk arrived → received butuh foto, handle khusus
    const isToReceived = stepNext && step.key === 'received';
    const onclick = stepNext && !isToReceived
      ? `onclick="askUpdateDeliveryStatus(${d.id_deliveries},'${d.order_number}','${step.key}','${step.label}')"`
      : '';

    if (idx > 0) {
      stepperHtml += `<div class="step-connector ${stepDone ? 'done' : ''}"></div>`;
    }
    stepperHtml += `
      <div class="step-wrapper">
        <div class="step-dot ${cls}" title="${stepNext ? 'Klik → ' + step.label : step.label}" ${onclick}>
          <i data-feather="${step.icon}"></i>
        </div>
        <div class="step-label ${labelCls}">${step.label}</div>
      </div>`;
  });

  // Zona upload foto jika status = arrived
  const showProofZone = d.status === 'arrived';
  const proofZone = showProofZone ? `
    <div class="proof-upload-zone" id="proof-zone-${d.id_deliveries}">
      <label>
        <i data-feather="camera"></i> Pilih Foto Bukti
        <input type="file" accept="image/*" id="proof-file-${d.id_deliveries}"
          onchange="previewProof(${d.id_deliveries}, this)" />
      </label>
      <img class="proof-preview" id="proof-preview-${d.id_deliveries}" />
      <div class="proof-upload-note">
        JPG / PNG / WEBP, maks. 5MB<br>
      <div style="font-size:11px;color:var(--warning)"><i class="bi bi-exclamation-triangle-fill"></i> Foto wajib sebelum konfirmasi diterima</div>
      </div>
      <button class="btn-confirm-received" id="btn-received-${d.id_deliveries}"
        onclick="submitReceived(${d.id_deliveries})" disabled>
        <i data-feather="check-circle"></i> Konfirmasi Diterima
      </button>
    </div>` : '';

  // Tampilkan foto jika sudah received
  const proofDisplay = d.proof_image ? `
    <div style="margin-top:10px;display:flex;align-items:center;gap:10px">
      <img src="uploads/proof/${d.proof_image}" class="proof-img-thumb"
        onclick="openLightbox('uploads/proof/${d.proof_image}')"
        title="Klik untuk perbesar" />
      <span style="font-size:12px;color:var(--success)"><i class="bi bi-check-circle-fill"></i> Bukti pengiriman tersedia</span>
    </div>` : '';

  return `
    <div class="order-card" id="delivery-card-${d.id_deliveries}">
      <div class="order-card-header">
        <div class="order-card-left">
          <div class="order-card-num">${d.order_number}</div>
          <div class="order-card-title">${d.order_title}</div>
          <div class="order-card-meta">
            <span><i data-feather="user"></i>${d.customer_name}</span>
            <span><i data-feather="map-pin"></i>${d.destination_city || '—'}</span>
            <span><i data-feather="phone"></i>${d.recipient_name || '—'}</span>
            ${d.estimated_arrival ? `<span><i data-feather="calendar"></i>Est. ${formatDate(d.estimated_arrival)}</span>` : ''}
          </div>
        </div>
        <div class="order-card-right">
          <span class="badge badge-delivery-${d.status}">${deliveryStatusLabel(d.status)}</span>
        </div>
      </div>
      <div class="status-stepper">${stepperHtml}</div>
      ${proofZone}
      ${proofDisplay}
    </div>`;
}

// Konfirmasi update status stepper pengiriman
let _pendingDeliveryId     = null;
let _pendingDeliveryStatus = null;

function askUpdateDeliveryStatus(id, orderNum, newStatus, label) {
  _pendingDeliveryId     = id;
  _pendingDeliveryStatus = newStatus;
  document.getElementById('confirm-status-delivery-num').textContent  = orderNum;
  document.getElementById('confirm-status-delivery-next').textContent = label;
  openModal('modal-confirm-status-delivery');
  feather.replace();
}

async function confirmUpdateDeliveryStatus() {
  if (!_pendingDeliveryId) return;
  const btn = document.getElementById('confirm-status-delivery-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Memproses...';
  feather.replace();

  await updateDeliveryStatus(_pendingDeliveryId, _pendingDeliveryStatus);

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="check" style="width:14px;height:14px"></i> Ya, Update';
  feather.replace();
  closeModal('modal-confirm-status-delivery');
  _pendingDeliveryId     = null;
  _pendingDeliveryStatus = null;
}

async function updateDeliveryStatus(id, newStatus) {
  if (!checkPermission('edit')) return;
  const res = await apiPut(`${API}deliveries.php`, { id, status: newStatus });
  if (res?.success) {
    showToast(`Pengiriman → ${deliveryStatusLabel(newStatus)}`, 'success');
    const deliv = allDeliveries.find(d => d.id_deliveries == id);
    if (deliv) {
      deliv.status = newStatus;
      const card = document.getElementById(`delivery-card-${id}`);
      // received = status final → animasi fade lalu pindah riwayat
      if (card && newStatus === 'received') {
        card.outerHTML = renderDeliveryCard(deliv);
        feather.replace();
        const updated = document.getElementById(`delivery-card-${id}`);
        if (updated) {
          setTimeout(() => {
            updated.classList.add('order-card-fadeout');
            setTimeout(() => {
              updated.remove();
              allDeliveries = allDeliveries.filter(d => d.id_deliveries != id);
              const badgeTab = document.getElementById('badge-deliveries-active');
              if (badgeTab) badgeTab.textContent = allDeliveries.length;
              const list = document.getElementById('deliveries-list');
              if (list && !list.querySelector('.order-card')) {
                list.innerHTML = `<div class="card"><div class="empty-state">
                  <i data-feather="check-circle" style="stroke:var(--success)"></i>
                  <h3>Semua pengiriman selesai</h3>
                  <p>Lihat <button onclick="switchDeliveriesTab('history')" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:13px;text-decoration:underline">Tab Riwayat</button> untuk pengiriman yang sudah diterima</p>
                </div></div>`;
                feather.replace();
              }
            }, 1900);
          }, 2000);
        }
      } else if (card) {
        card.outerHTML = renderDeliveryCard(deliv);
        feather.replace();
      }
    }
  } else {
    showToast(res?.message || 'Gagal update status pengiriman', 'error');
  }
}

function previewProof(id, input) {
  const file    = input.files[0];
  const preview = document.getElementById(`proof-preview-${id}`);
  const btn     = document.getElementById(`btn-received-${id}`);
  if (file) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
    btn.disabled = false;
  } else {
    preview.style.display = 'none';
    btn.disabled = true;
  }
}

async function submitReceived(id) {
  if (!checkPermission('edit')) return;
  const fileInput = document.getElementById(`proof-file-${id}`);
  if (!fileInput?.files[0]) {
    showToast('Pilih foto bukti pengiriman terlebih dahulu', 'warning'); return;
  }

  const btn = document.getElementById(`btn-received-${id}`);
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="refresh-cw"></i> Menyimpan...';
  feather.replace();

  const formData = new FormData();
  formData.append('id',          id);
  formData.append('proof_image', fileInput.files[0]);

  try {
    // Gunakan POST + action=confirm_received karena PHP tidak baca $_FILES di PUT
    const res  = await fetch(`${API}deliveries.php?action=confirm_received`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      showToast('Pengiriman dikonfirmasi diterima', 'success');
      // Update card dulu, lalu fade-out setelah 2 detik
      const deliv = allDeliveries.find(d => d.id_deliveries == id);
      if (deliv) {
        deliv.status   = 'received';
        deliv.proof_image = data.proof_image;
        const card = document.getElementById(`delivery-card-${id}`);
        if (card) {
          card.outerHTML = renderDeliveryCard(deliv);
          feather.replace();
          const updated = document.getElementById(`delivery-card-${id}`);
          if (updated) {
            setTimeout(() => {
              updated.classList.add('order-card-fadeout');
              setTimeout(() => {
                updated.remove();
                allDeliveries = allDeliveries.filter(d => d.id_deliveries != id);
                const badgeTab = document.getElementById('badge-deliveries-active');
                if (badgeTab) badgeTab.textContent = allDeliveries.length;
                const list = document.getElementById('deliveries-list');
                if (list && !list.querySelector('.order-card')) {
                  list.innerHTML = `<div class="card"><div class="empty-state">
                    <i data-feather="check-circle" style="stroke:var(--success)"></i>
                    <h3>Semua pengiriman selesai</h3>
                    <p>Lihat <button onclick="switchDeliveriesTab('history')" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:13px;text-decoration:underline">Tab Riwayat</button></p>
                  </div></div>`;
                  feather.replace();
                }
              }, 1900);
            }, 2000);
          }
        }
      }
    } else {
      showToast(data.message || 'Gagal konfirmasi', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i data-feather="check-circle"></i> Konfirmasi Diterima';
      feather.replace();
    }
  } catch(e) {
    console.error(e);
    showToast('Koneksi bermasalah', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-feather="check-circle"></i> Konfirmasi Diterima';
    feather.replace();
  }
}

function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.add('open');
}

function deliveryStatusLabel(s) {
  const map = { prepared:'Disiapkan', shipping:'Dalam Pengiriman', arrived:'Tiba di Tujuan', received:'Diterima' };
  return map[s] || s;
}

// ============================================================
// CUSTOMERS
// ============================================================
let allCustomers = [];

async function loadCustomers() {
  const data = await apiFetch(API + 'customers.php');
  allCustomers = data?.data || [];
  renderCustomersList(allCustomers);
  feather.replace();
}

function filterCustomers() {
  const q = document.getElementById('customers-search').value.toLowerCase();
  renderCustomersList(allCustomers.filter(c =>
    c.name.toLowerCase().includes(q) ||
    (c.phone && c.phone.includes(q)) ||
    (c.city && c.city.toLowerCase().includes(q))
  ));
}

function renderCustomersList(customers) {
  const el = document.getElementById('customers-list');
  if (!el) return;
  if (!customers.length) {
    el.innerHTML = `<div class="card"><div class="empty-state">
      <i data-feather="users"></i>
      <h3>Belum ada pelanggan</h3>
      <p>Tambah pelanggan baru atau buat order untuk mendaftarkan pelanggan secara otomatis.</p>
      <button onclick="openAddCustomerModal()" class="btn btn-primary mt-4">
        <i data-feather="plus"></i> Tambah Pelanggan
      </button>
    </div></div>`;
    feather.replace(); return;
  }

  el.innerHTML = customers.map(c => `
    <div class="order-card">
      <div class="order-card-header">
        <div class="order-card-left" style="cursor:pointer" onclick="lihatHistory(${c.id_customers})">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));
                        display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:white;flex-shrink:0">
              ${c.name.charAt(0).toUpperCase()}
            </div>
            <div>
              <div style="font-size:15px;font-weight:700;color:var(--text-primary)">${c.name}</div>
              <div class="order-card-meta">
                <span><i data-feather="phone"></i>${c.phone || '—'}</span>
                ${c.city ? `<span><i data-feather="map-pin"></i>${c.city}</span>` : ''}
                <span style="font-family:monospace;font-size:11px;color:var(--text-muted)">${c.code}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="order-card-right" style="display:flex;align-items:center;gap:8px">
          <button class="btn btn-secondary btn-sm btn-icon" title="Edit" onclick="openEditCustomerModal(${c.id_customers})">
            <i data-feather="edit-2"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-icon" title="Hapus" onclick="deleteCustomer(${c.id_customers},'${c.name.replace(/'/g,"\\'") }')">
            <i data-feather="trash-2"></i>
          </button>
          <button class="btn btn-secondary btn-sm" onclick="lihatHistory(${c.id_customers})" style="font-size:11px">
            <i data-feather="clock"></i> Riwayat
          </button>
        </div>
      </div>
    </div>
  `).join('');
  feather.replace();
}

// ── Tambah pelanggan ──
function openAddCustomerModal() {
  document.getElementById('modal-customer-title').textContent = 'Tambah Pelanggan';
  document.getElementById('customer-id').value      = '';
  document.getElementById('customer-name').value    = '';
  document.getElementById('customer-phone').value   = '';
  document.getElementById('customer-city').value    = '';
  document.getElementById('customer-email').value   = '';
  document.getElementById('customer-address').value = '';
  document.getElementById('customer-notes').value   = '';
  openModal('modal-add-customer');
  feather.replace();
}

// ── Edit pelanggan ──
async function openEditCustomerModal(id) {
  const res = await apiFetch(`api/customers.php?action=get&id=${id}`);
  if (!res?.berhasil) { showToast('Gagal memuat data pelanggan', 'error'); return; }
  const c = res.data;
  document.getElementById('modal-customer-title').textContent = 'Edit Pelanggan';
  document.getElementById('customer-id').value      = c.id_customers;
  document.getElementById('customer-name').value    = c.name || '';
  document.getElementById('customer-phone').value   = c.phone || '';
  document.getElementById('customer-city').value    = c.city || '';
  document.getElementById('customer-email').value   = c.email || '';
  document.getElementById('customer-address').value = c.address || '';
  document.getElementById('customer-notes').value   = c.notes || '';
  openModal('modal-add-customer');
  feather.replace();
}

// ── Submit tambah / edit ──
async function submitCustomer(e) {
  e.preventDefault();
  const id     = document.getElementById('customer-id').value;
  const isEdit = id !== '';
  const payload = {
    id:              id || undefined,
    name:            document.getElementById('customer-name').value.trim(),
    phone:           document.getElementById('customer-phone').value.trim(),
    city:            document.getElementById('customer-city').value.trim(),
    email:           document.getElementById('customer-email').value.trim(),
    address:         document.getElementById('customer-address').value.trim(),
    notes:           document.getElementById('customer-notes').value.trim(),
    contact_person:  '',
    code:            isEdit ? undefined : 'CUS-AUTO',
  };

  if (!payload.name) { showToast('Nama pelanggan wajib diisi', 'warning'); return; }
  if (!payload.phone) { showToast('No. HP wajib diisi', 'warning'); return; }

  const res = isEdit
    ? await apiPut('api/customers.php', payload)
    : await apiPost('api/customers.php', payload);

  if (res?.berhasil || res?.success) {
    showToast(isEdit ? 'Pelanggan berhasil diupdate' : 'Pelanggan berhasil ditambahkan', 'success');
    closeModal('modal-add-customer');
    loadCustomers();
  } else {
    showToast(res?.message || res?.pesan || 'Gagal menyimpan pelanggan', 'error');
  }
}

// ── Hapus pelanggan ──
let _pendingDeleteCustomerId = null;
function deleteCustomer(id, name) {
  _pendingDeleteCustomerId = id;
  document.getElementById('confirm-delete-customer-name').textContent = name;
  openModal('modal-confirm-delete-customer');
  feather.replace();
}

async function confirmDeleteCustomer() {
  if (!_pendingDeleteCustomerId) return;
  const btn = document.getElementById('confirm-delete-customer-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Menghapus...';
  feather.replace();

  const res = await apiFetch(`api/customers.php?id=${_pendingDeleteCustomerId}`, { method: 'DELETE' });

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="trash-2" style="width:14px;height:14px"></i> Hapus';
  feather.replace();
  closeModal('modal-confirm-delete-customer');
  _pendingDeleteCustomerId = null;

  if (res?.berhasil || res?.success) {
    showToast('Pelanggan berhasil dihapus', 'success');
    loadCustomers();
  } else {
    showToast(res?.message || 'Gagal menghapus pelanggan', 'error');
  }
}

async function lihatHistory(id) {
  const panel = document.getElementById('customer-history-panel');
  const tbody = document.getElementById('history-tbody');
  panel.style.display = 'block';
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Memuat...</td></tr>';
  panel.scrollIntoView({behavior:'smooth', block:'start'});

  const data = await apiFetch(`${API}customers.php?action=history&id=${id}`);
  if (!data?.success) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--danger)">Gagal memuat data</td></tr>'; return; }

  document.getElementById('history-panel-name').textContent  = data.customer?.name || '';
  document.getElementById('history-panel-phone').textContent = data.customer?.phone || '';

  const orders = data.orders || [];
  if (!orders.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Belum ada order</td></tr>';
    feather.replace(); return;
  }

  tbody.innerHTML = orders.map(o => `
    <tr>
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${o.order_number}</td>
      <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${o.title}</td>
      <td><span class="badge badge-${o.status}">${statusLabel(o.status)}</span></td>
      <td>${o.delivery_status
        ? `<span class="badge badge-delivery-${o.delivery_status}">${deliveryStatusLabel(o.delivery_status)}</span>`
        : '<span style="color:var(--text-muted);font-size:12px">—</span>'}</td>
      <td style="font-weight:600;color:var(--success)">${formatCurrency(o.grand_total)}</td>
      <td style="font-size:12px;color:var(--text-muted)">${formatDate(o.created_at)}</td>
    </tr>
  `).join('');
  feather.replace();
}

function tutupHistory() {
  document.getElementById('customer-history-panel').style.display = 'none';
}

// ============================================================
// REPORTS
// ============================================================
function switchReportTab(el, type) {
  document.querySelectorAll('.tabs .tab-pill').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  currentReportType = type;
  loadReport();
}

async function loadReport() {
  const from = document.getElementById('report-from').value;
  const to   = document.getElementById('report-to').value;
  const data = await apiFetch(`${API}reports.php?type=${currentReportType}&from=${from}&to=${to}`);
  renderReportTable(data?.data || [], currentReportType);
}

function renderReportTable(rows, type) {
  const wrapper = document.getElementById('report-table-wrapper');
  if (!rows.length) { wrapper.innerHTML = '<div class="empty-state"><h3>Tidak ada data</h3><p>Coba ubah rentang tanggal</p></div>'; return; }

  const headers = {
    stock:       ['Kode','Nama','Kategori','Stok','Min','Maks','Satuan','Nilai','Status'],
    transactions:['Waktu','Kode','Nama Item','Tipe','Jumlah','Satuan','Sblm','Sesudah','Harga','Referensi'],
    orders:      ['No. Order','Judul','Status','Prioritas','Customer','Qty','Total','Jatuh Tempo','Selesai'],
    deliveries:  ['No. Order','Pesanan','Pelanggan','Kota Tujuan','Penerima','Tgl. Diterima','Bukti','Total'],
  };

  const cols = headers[type] || [];
  let html = `<table class="table-compact"><thead><tr>${cols.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>`;

  rows.forEach(r => {
    if (type === 'stock') {
      html += `<tr>
        <td style="font-family:monospace;font-size:12px">${r.code}</td>
        <td>${r.name}</td>
        <td>${r.category}</td>
        <td style="font-weight:700">${parseFloat(r.stock)}</td>
        <td>${parseFloat(r.min_stock)}</td>
        <td>${parseFloat(r.max_stock)}</td>
        <td>${r.symbol}</td>
        <td style="color:var(--success)">${formatCurrency(r.value)}</td>
        <td><span class="badge badge-${r.status}">${r.status}</span></td>
      </tr>`;
    } else if (type === 'transactions') {
      html += `<tr>
        <td style="font-size:11px">${formatDateTime(r.created_at)}</td>
        <td style="font-family:monospace;font-size:12px">${r.code}</td>
        <td>${r.item_name}</td>
        <td><span class="badge badge-${r.type==='in'?'completed':'cancelled'}">${r.type==='in'?'MASUK':'KELUAR'}</span></td>
        <td style="color:${r.type==='in'?'var(--success)':'var(--danger)'};font-weight:700">${r.type==='in'?'+':'−'}${parseFloat(r.quantity)}</td>
        <td>${r.symbol}</td>
        <td>${parseFloat(r.stock_before)}</td>
        <td style="font-weight:600">${parseFloat(r.stock_after)}</td>
        <td>${formatCurrency(r.unit_price)}</td>
        <td style="font-size:11px;color:var(--text-muted)">${r.reference_type||'—'}</td>
      </tr>`;
    } else if (type === 'orders') {
      html += `<tr>
        <td style="font-family:monospace;font-size:12px;color:var(--accent)">${r.order_number}</td>
        <td>${r.title}</td>
        <td><span class="badge badge-${r.status}">${statusLabel(r.status)}</span></td>
        <td><span class="badge badge-${r.priority}">${priorityLabel(r.priority)}</span></td>
        <td>${r.customer}</td>
        <td>${r.quantity}</td>
        <td style="color:var(--success);font-weight:600">${formatCurrency(r.grand_total)}</td>
        <td style="font-size:12px">${formatDate(r.due_date)}</td>
        <td style="font-size:12px">${r.completed_date?formatDateTime(r.completed_date):'—'}</td>
      </tr>`;
    } else if (type === 'deliveries') {
      const aktTiba = r.actual_arrival ? formatDate(r.actual_arrival) : '—';
      const bukti   = r.proof_image
        ? `<img src="uploads/proof/${r.proof_image}"
               onclick="openLightbox('uploads/proof/${r.proof_image}')"
               title="Klik untuk perbesar"
               style="width:36px;height:36px;object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid var(--border);vertical-align:middle" />`
        : `<span style="color:var(--text-muted);font-size:11px">—</span>`;
      html += `<tr>
        <td style="font-family:monospace;font-size:11px;color:var(--accent);white-space:nowrap"
            title="Dibuat: ${formatDate(r.created_at)}">${r.order_number}</td>
        <td style="font-size:12px;font-weight:600;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
            title="${r.order_title}">${r.order_title}</td>
        <td style="font-size:12px;max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
            title="${r.customer}">${r.customer}</td>
        <td style="font-size:12px;white-space:nowrap">${r.destination_city || '—'}</td>
        <td style="font-size:12px;white-space:nowrap">${r.recipient_name || '—'}</td>
        <td style="font-size:11px;white-space:nowrap;color:var(--success);font-weight:600">${aktTiba}</td>
        <td style="text-align:center">${bukti}</td>
        <td style="font-size:12px;color:var(--success);font-weight:600;white-space:nowrap;text-align:right">${formatCurrency(r.grand_total)}</td>
      </tr>`;
    }
  });

  html += '</tbody></table>';
  wrapper.innerHTML = html;
}

let reportCache = [];
function exportReport(format = 'csv') {
  const table = document.querySelector('#report-table-wrapper table');
  if (!table) { showToast('Tidak ada data untuk diekspor', 'warning'); return; }

  const reportLabels = { stock: 'Laporan Stok', transactions: 'Mutasi Stok', orders: 'Laporan Order', deliveries: 'Laporan Pengiriman' };
  const title  = reportLabels[currentReportType] || 'Laporan';
  const from   = document.getElementById('report-from').value;
  const to     = document.getElementById('report-to').value;
  const period = from && to ? `${formatDate(from)} — ${formatDate(to)}` : '';
  const filename = `laporan_${currentReportType}_${new Date().toISOString().slice(0,10)}`;

  if (format === 'csv') {
    let csv = '';
    table.querySelectorAll('tr').forEach(row => {
      const cells = [...row.querySelectorAll('th,td')].map(c => `"${c.innerText.replace(/"/g,'""')}"`);
      csv += cells.join(',') + '\n';
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = filename + '.csv';
    a.click(); URL.revokeObjectURL(url);
    showToast('Laporan CSV berhasil diekspor', 'success');
    return;
  }

  if (format === 'pdf') {
    if (!window.jspdf) { showToast('Library PDF belum siap, coba lagi', 'warning'); return; }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // ── Header ──
    doc.setFillColor(15, 15, 25);
    doc.rect(0, 0, 297, 30, 'F');
    doc.setTextColor(165, 180, 252);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('Ranum Indocraft', 14, 12);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(200, 200, 220);
    doc.text('Sistem Inventory & Monitoring Percetakan', 14, 19);

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(13);
    doc.setFont('helvetica', 'bold');
    doc.text(title, 297 - 14, 12, { align: 'right' });
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(180, 180, 200);
    if (period) doc.text('Periode: ' + period, 297 - 14, 19, { align: 'right' });
    doc.text('Dicetak: ' + new Date().toLocaleString('id-ID'), 297 - 14, 25, { align: 'right' });

    // ── Ambil header & rows dari tabel ──
    const headers = [...table.querySelectorAll('thead th')].map(th => th.innerText.trim());
    const rows    = [...table.querySelectorAll('tbody tr')].map(tr =>
      [...tr.querySelectorAll('td')].map(td => td.innerText.trim())
    );

    // ── autoTable ──
    doc.autoTable({
      startY: 34,
      head: [headers],
      body: rows,
      styles: {
        fontSize: 8,
        cellPadding: 3,
        overflow: 'linebreak',
        textColor: [220, 220, 230],
        fillColor: [22, 22, 35],
        lineColor: [50, 50, 70],
        lineWidth: 0.2,
      },
      headStyles: {
        fillColor: [99, 102, 241],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
        fontSize: 8,
      },
      alternateRowStyles: {
        fillColor: [28, 28, 45],
      },
      columnStyles: { 0: { fontStyle: 'bold' } },
      margin: { left: 14, right: 14 },
    });

    // ── Footer ──
    const pageCount = doc.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      doc.setFontSize(8);
      doc.setTextColor(120, 120, 150);
      doc.text(
        `Halaman ${i} dari ${pageCount}   •   Ranum Indocraft © ${new Date().getFullYear()}`,
        297 / 2, doc.internal.pageSize.height - 6,
        { align: 'center' }
      );
    }

    doc.save(filename + '.pdf');
    showToast('Laporan PDF berhasil diekspor', 'success');
  }
}

// ============================================================
// MODAL HELPERS
// ============================================================
function openModal(id) {
  document.getElementById(id).classList.add('open');
  feather.replace();
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ============================================================
// TOAST
// ============================================================
function showToast(message, type = 'info') {
  const icons = { success:'check-circle', error:'x-circle', warning:'alert-triangle', info:'info' };
  const colors= { success:'var(--success)', error:'var(--danger)', warning:'var(--warning)', info:'var(--accent)' };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<i data-feather="${icons[type]||'info'}" style="width:16px;height:16px;stroke:${colors[type]||colors.info};flex-shrink:0"></i><span>${message}</span>`;
  document.getElementById('toast-container').appendChild(el);
  feather.replace();
  setTimeout(() => { el.style.animation = 'slideOut 0.3s ease forwards'; setTimeout(() => el.remove(), 300); }, 3500);
}

// ============================================================
// LOGOUT TRANSITION
// ============================================================
function doLogout() {
  const overlay   = document.getElementById('logout-overlay');
  const backdrop  = document.getElementById('logout-backdrop');
  const grid      = document.getElementById('logout-grid');
  const orb       = document.getElementById('logout-orb');
  const content   = document.getElementById('logout-content');
  const icon      = document.getElementById('logout-icon');
  const progress  = document.getElementById('logout-progress');
  const stars     = document.getElementById('logout-stars');

  if (!overlay) { window.location.href = 'logout.php'; return; }

  // Generate bintang
  const colors = ['#a5b4fc','#c4b5fd','#67e8f9','#f0abfc','#ffffff'];
  for (let i = 0; i < 60; i++) {
    const s = document.createElement('div');
    const sz  = Math.random() * 3 + 1;
    const col = colors[Math.floor(Math.random() * colors.length)];
    s.style.cssText = `
      position:absolute;border-radius:50%;
      width:${sz}px;height:${sz}px;background:${col};
      left:${Math.random()*100}%;bottom:${Math.random()*20}%;
      box-shadow:0 0 ${sz*2}px ${col};opacity:0;
      animation:starFloat ${Math.random()*4+2}s linear ${Math.random()*2}s infinite;
    `;
    stars.appendChild(s);
  }

  // Tampilkan overlay
  overlay.style.opacity       = '1';
  overlay.style.pointerEvents = 'all';

  setTimeout(() => {
    backdrop.style.background          = 'rgba(8,8,24,0.96)';
    backdrop.style.backdropFilter      = 'blur(20px)';
    backdrop.style.webkitBackdropFilter= 'blur(20px)';
    grid.style.opacity    = '1';
    orb.style.transform   = 'translate(-50%,-50%) scale(1)';
    content.style.opacity = '1';
    content.style.transform = 'translateY(0) scale(1)';
    icon.style.transform  = 'scale(1) rotate(0deg)';
    progress.style.width  = '100%';
  }, 50);

  // Redirect ke logout.php setelah animasi selesai
  setTimeout(() => {
    window.location.href = 'logout.php';
  }, 2400);
}

// ============================================================
// REFRESH
// ============================================================
function refreshAll() {
  showToast('Data diperbarui', 'success');
  navigate(currentPage);
}

// ============================================================
// FORMAT HELPERS
// ============================================================
function formatCurrency(n) {
  const num = parseFloat(n) || 0;
  return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' });
}

function formatDateShort(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', { day:'2-digit', month:'short' });
}

function formatDateTime(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function isDueSoon(d) {
  if (!d) return false;
  const diff = new Date(d) - new Date();
  return diff > 0 && diff < 2 * 86400000;
}

function statusLabel(s) {
  const map = { pending:'Pending', confirmed:'Konfirmasi', in_progress:'Proses', quality_check:'QC', completed:'Selesai', cancelled:'Batal', active:'Aktif', idle:'Idle', maintenance:'Maintenance', offline:'Offline' };
  return map[s] || s;
}

function priorityLabel(p) {
  const map = { low:'Rendah', normal:'Normal', high:'Tinggi', urgent:'Urgent' };
  return map[p] || p;
}

// ============================================================
// MANAGE USERS (Admin Only)
// ============================================================
let allUsers = [];

async function loadUsers() {
  const search = document.getElementById('users-search')?.value || '';
  const role   = document.getElementById('users-role-filter')?.value || '';
  const params = new URLSearchParams({ action: 'list', search, role });
  const data   = await apiFetch(`api/users.php?${params}`);
  if (!data) {
    showToast('Akses ditolak atau gagal memuat data user', 'error');
    return;
  }
  allUsers = data?.data || [];
  renderUsersTable(allUsers);
  feather.replace();
}

function filterUsers() { loadUsers(); }

function renderUsersTable(users) {
  const tbody = document.getElementById('users-tbody');
  if (!users.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
      <i data-feather="users"></i><h3>Tidak ada user ditemukan</h3>
    </div></td></tr>`;
    feather.replace(); return;
  }

  const roleColors = {
    admin:    { bg: 'rgba(99,102,241,0.15)',  color: '#a5b4fc', border: 'rgba(99,102,241,0.3)'  },
    operator: { bg: 'rgba(6,182,212,0.15)',   color: '#22d3ee', border: 'rgba(6,182,212,0.3)'   },
  };
  const roleIcons = { admin: '<i class="bi bi-shield-fill-check"></i>', operator: '<i class="bi bi-gear-fill"></i>' };
  const selfId = window.APP_CONFIG?.userId;

  tbody.innerHTML = users.map(u => {
    const rc    = roleColors[u.role] || roleColors.operator;
    const isSelf = u.id_users == selfId;
    return `
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:50%;
              background:linear-gradient(135deg,var(--primary),var(--secondary));
              display:flex;align-items:center;justify-content:center;
              font-size:13px;font-weight:700;color:white;flex-shrink:0">
              ${u.name.charAt(0).toUpperCase()}
            </div>
            <div>
              <div style="font-weight:600;color:var(--text-primary)">${u.name}
                ${isSelf ? '<span style="font-size:10px;color:var(--accent);margin-left:6px">(Anda)</span>' : ''}
              </div>
            </div>
          </div>
        </td>
        <td style="color:var(--text-muted);font-size:12px">${u.email}</td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
            border-radius:20px;font-size:11px;font-weight:600;
            background:${rc.bg};color:${rc.color};border:1px solid ${rc.border}">
            ${roleIcons[u.role] || ''} ${u.role.charAt(0).toUpperCase() + u.role.slice(1)}
          </span>
        </td>
        <td>
          <span class="badge badge-completed">Aktif</span>
        </td>
        <td style="font-size:12px;color:var(--text-muted)">${u.last_login ? formatDateTime(u.last_login) : '—'}</td>
        <td style="font-size:12px;color:var(--text-muted)">${formatDate(u.created_at)}</td>
        <td>
          <div style="display:flex;gap:4px">
            <button class="btn btn-secondary btn-sm btn-icon" title="Edit"
              onclick="openEditUserModal(${u.id_users})"><i data-feather="edit-2"></i></button>
            ${!isSelf ? `<button class="btn btn-danger btn-sm btn-icon" title="Hapus User"
              onclick="confirmDeleteUser(${u.id_users}, '${u.name.replace(/'/g, "\\'")}')">
              <i data-feather="trash-2"></i></button>` : ''}
          </div>
        </td>
      </tr>`;
  }).join('');
  feather.replace();
}

function openAddUserModal() {
  document.getElementById('modal-user-title').textContent = 'Tambah User Baru';
  document.getElementById('user-id').value        = '';
  document.getElementById('user-name').value      = '';
  document.getElementById('user-email').value     = '';
  document.getElementById('user-email').disabled  = false;
  document.getElementById('user-role').value      = 'operator';
  document.getElementById('user-password').value  = '';
  document.getElementById('user-password').required = true;
  document.getElementById('user-pass-label').textContent = 'Password *';
  document.getElementById('user-pass-hint').style.display = 'none';
  document.getElementById('user-status-wrap').style.display = 'none';
  openModal('modal-add-user');
}

async function openEditUserModal(id) {
  const data = await apiFetch(`api/users.php?action=get&id=${id}`);
  if (!data?.berhasil) { showToast('Gagal mengambil data user', 'error'); return; }
  const u = data.data;

  document.getElementById('modal-user-title').textContent  = 'Edit User';
  document.getElementById('user-id').value                 = u.id_users;
  document.getElementById('user-name').value               = u.name;
  document.getElementById('user-email').value              = u.email;
  document.getElementById('user-email').disabled           = false;
  document.getElementById('user-role').value               = u.role;
  document.getElementById('user-password').value           = '';
  document.getElementById('user-password').required        = false;
  document.getElementById('user-pass-label').textContent   = 'Password Baru';
  document.getElementById('user-pass-hint').style.display  = 'block';
  document.getElementById('user-status-wrap').style.display = 'block';
  document.getElementById('user-is-active').value          = u.is_active;
  openModal('modal-add-user');
}

async function submitAddUser(e) {
  e.preventDefault();
  const userId = document.getElementById('user-id').value;
  const isEdit = userId !== '';

  const payload = {
    name:      document.getElementById('user-name').value.trim(),
    email:     document.getElementById('user-email').value.trim(),
    role:      document.getElementById('user-role').value,
    password:  document.getElementById('user-password').value,
  };
  if (isEdit) {
    payload.id        = userId;
    payload.is_active = document.getElementById('user-is-active').value;
  }

  const res = isEdit
    ? await apiPut('api/users.php', payload)
    : await apiPost('api/users.php', payload);

  if (res?.success || res?.berhasil) {
    showToast(isEdit ? 'User berhasil diupdate' : 'User berhasil ditambahkan', 'success');
    closeModal('modal-add-user');
    loadUsers();
  } else {
    showToast(res?.message || res?.pesan || 'Gagal menyimpan user', 'error');
  }
}

let _pendingDeleteUserId = null;

function confirmDeleteUser(id, name) {
  _pendingDeleteUserId = id;
  document.getElementById('modal-toggle-user-name').textContent    = name;
  document.getElementById('modal-toggle-user-action').textContent  = 'dihapus permanen dari sistem';
  document.getElementById('modal-toggle-user-title').textContent   = 'Hapus User?';
  document.getElementById('confirm-toggle-user-label').textContent = 'Hapus';

  const iconEl   = document.getElementById('modal-toggle-user-icon-i');
  const btnEl    = document.getElementById('confirm-toggle-user-btn');
  const iconWrap = document.getElementById('modal-toggle-user-icon');
  iconEl.setAttribute('data-feather', 'trash-2');
  btnEl.className          = 'btn btn-danger';
  btnEl.style.minWidth     = '110px';
  iconWrap.style.background = 'rgba(239,68,68,0.12)';
  iconWrap.style.border     = '2px solid rgba(239,68,68,0.3)';

  // Ganti teks keterangan
  const infoEl = document.querySelector('#modal-confirm-toggle-user [style*="font-size:12px"]');
  if (infoEl) infoEl.textContent = 'Tindakan ini tidak dapat dibatalkan.';

  openModal('modal-confirm-toggle-user');
  feather.replace();
}

async function confirmToggleUser() {
  if (!_pendingDeleteUserId) return;

  const btn = document.getElementById('confirm-toggle-user-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="loader"></i> Menghapus...';
  feather.replace();

  const res = await apiFetch(`api/users.php?id=${_pendingDeleteUserId}`, { method: 'DELETE' });

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="trash-2"></i> Hapus';
  feather.replace();

  closeModal('modal-confirm-toggle-user');
  _pendingDeleteUserId = null;

  if (res?.success || res?.berhasil) {
    showToast('User berhasil dihapus', 'success');
    loadUsers();
  } else {
    showToast(res?.message || 'Gagal menghapus user', 'error');
  }
}

// ============================================================
// BOM — Bill of Materials (Bahan Baku per Produk)
// ============================================================
let bomRows   = [];   // [{item_id, item_name, unit_symbol, qty_per_unit, notes}]
let bomItemsRef = []; // cache daftar bahan baku untuk dropdown

async function openBOMModal(productId, productName) {
  document.getElementById('bom-product-id').value     = productId;
  document.getElementById('bom-product-name').textContent = productName;
  bomRows = [];

  // Load daftar items untuk dropdown (gunakan cache kalau sudah ada)
  if (!bomItemsRef.length) {
    const d = await apiFetch('api/items.php?action=list');
    bomItemsRef = d?.data || [];
  }

  // Load BOM yang sudah ada
  const data = await apiFetch(`api/products.php?action=get_materials&id=${productId}`);
  const existing = data?.data || [];

  if (existing.length) {
    bomRows = existing.map(m => ({
      item_id:      m.item_id,
      item_name:    m.item_name,
      unit_symbol:  m.unit_symbol,
      qty_per_unit: parseFloat(m.qty_per_unit),
      notes:        m.notes || '',
    }));
  }

  renderBOMList();
  openModal('modal-bom');
  feather.replace();
}

function bomTambahBaris() {
  bomRows.push({ item_id: '', item_name: '', unit_symbol: '', qty_per_unit: 1, notes: '' });
  renderBOMList();
  feather.replace();
}

function renderBOMList() {
  const el = document.getElementById('bom-list');
  if (!bomRows.length) {
    el.innerHTML = `
      <div style="text-align:center;padding:24px;color:var(--text-muted);
        border:1px dashed var(--border);border-radius:var(--radius-sm);font-size:13px">
        <i data-feather="inbox" style="width:32px;height:32px;margin-bottom:8px;display:block;margin:0 auto 8px"></i>
        Belum ada bahan baku.<br>Klik "+ Tambah Bahan" untuk menambahkan.
      </div>`;
    feather.replace();
    return;
  }

  // Build dropdown options
  const opts = bomItemsRef.map(i =>
    `<option value="${i.id_items}" data-unit="${i.unit_symbol}">${i.code} — ${i.name} (${i.unit_symbol})</option>`
  ).join('');

  el.innerHTML = `
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="border-bottom:1px solid var(--border)">
          <th style="padding:8px 6px;text-align:left;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase">Bahan Baku</th>
          <th style="padding:8px 6px;text-align:center;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase;width:130px">Qty / 1 pcs</th>
          <th style="padding:8px 6px;text-align:left;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase;width:80px">Satuan</th>
          <th style="width:36px"></th>
        </tr>
      </thead>
      <tbody>
        ${bomRows.map((row, idx) => `
          <tr style="border-bottom:1px solid rgba(99,102,241,0.06)">
            <td style="padding:6px">
              <select onchange="bomUpdateItem(${idx}, this)"
                style="width:100%;background:var(--bg-input);border:1px solid var(--border);
                  border-radius:6px;color:var(--text-primary);padding:6px 8px;font-size:12px;font-family:inherit">
                <option value="">-- Pilih Bahan --</option>
                ${opts.replace(`value="${row.item_id}"`, `value="${row.item_id}" selected`)}
              </select>
            </td>
            <td style="padding:6px;text-align:center">
              <input type="number" value="${row.qty_per_unit}" min="0.0001" step="0.001"
                oninput="bomRows[${idx}].qty_per_unit=parseFloat(this.value)||0"
                style="width:110px;background:var(--bg-input);border:1px solid var(--border);
                  border-radius:6px;color:var(--text-primary);padding:6px 8px;font-size:12px;
                  text-align:right;font-family:inherit" />
            </td>
            <td style="padding:6px;font-size:12px;color:var(--text-muted)">
              <span id="bom-unit-${idx}">${row.unit_symbol || '—'}</span>
            </td>
            <td style="padding:6px;text-align:center">
              <button onclick="bomHapusBaris(${idx})"
                style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);
                  color:var(--danger);border-radius:6px;padding:4px 7px;cursor:pointer">
                <i data-feather="trash-2" style="width:13px;height:13px"></i>
              </button>
            </td>
          </tr>`).join('')}
      </tbody>
    </table>`;
  feather.replace();
}

function bomUpdateItem(idx, sel) {
  const opt = sel.options[sel.selectedIndex];
  bomRows[idx].item_id     = sel.value;
  bomRows[idx].unit_symbol = opt.dataset.unit || '';
  const unitEl = document.getElementById(`bom-unit-${idx}`);
  if (unitEl) unitEl.textContent = opt.dataset.unit || '—';
}

function bomHapusBaris(idx) {
  bomRows.splice(idx, 1);
  renderBOMList();
  feather.replace();
}

async function simpanBOM() {
  const productId = document.getElementById('bom-product-id').value;

  // Validasi
  for (const r of bomRows) {
    if (!r.item_id) { showToast('Pilih bahan baku untuk semua baris', 'warning'); return; }
    if (r.qty_per_unit <= 0) { showToast('Qty harus lebih dari 0', 'warning'); return; }
  }

  // Cek duplikat item
  const ids = bomRows.map(r => r.item_id);
  if (new Set(ids).size !== ids.length) {
    showToast('Ada bahan baku yang duplikat', 'warning'); return;
  }

  const res = await apiPost(`api/products.php?action=save_materials&id=${productId}`, {
    materials: bomRows.map(r => ({
      item_id:      r.item_id,
      qty_per_unit: r.qty_per_unit,
      notes:        r.notes || '',
    }))
  });

  if (res?.success) {
    showToast('BOM berhasil disimpan', 'success');
    closeModal('modal-bom');
  } else {
    showToast(res?.message || 'Gagal menyimpan BOM', 'error');
  }
}
