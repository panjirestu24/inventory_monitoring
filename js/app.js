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
let monitorInterval = null;
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
  const validPages = ['dashboard','monitoring','items','stock-mutation','order-input','orders','deliveries','machines','customers','products','reports'];
  navigate(validPages.includes(hashPage) ? hashPage : 'dashboard');

  // Report date defaults
  const today = new Date().toISOString().slice(0,10);
  const monthStart = today.slice(0,8) + '01';
  const rf = document.getElementById('report-from');
  const rt = document.getElementById('report-to');
  if (rf) rf.value = monthStart;
  if (rt) rt.value = today;
});

// ============================================================
// ROLE-BASED ACCESS CONTROL
// ============================================================
function applyRoleBasedUI() {
  const role = window.APP_CONFIG?.userRole || 'viewer';
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
  
  // Kalau viewer, hide semua tombol edit & hapus
  if (role === 'viewer') {
    console.log('🔒 Mode Viewer: Hanya bisa lihat data');
    
    // Hide semua tombol aksi
    hideElements('button[onclick*="edit"]');
    hideElements('button[onclick*="Edit"]');
    hideElements('button[onclick*="delete"]');
    hideElements('button[onclick*="Hapus"]');
    hideElements('button[onclick*="submit"]');
    hideElements('button[onclick*="change"]');
    hideElements('.btn-danger');
    hideElements('.btn-success[onclick*="submit"]');
    
    // Disable semua form input
    setTimeout(() => {
      document.querySelectorAll('input, select, textarea').forEach(el => {
        if (!el.closest('.search-box')) { // Kecuali search box
          el.disabled = true;
        }
      });
    }, 1000);
    
    // Tampilkan badge viewer di header
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
      const badge = document.createElement('div');
      badge.className = 'badge badge-pending';
      badge.style.cssText = 'margin-right:12px;padding:6px 12px;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3)';
      badge.innerHTML = '<i data-feather="eye"></i> Mode Read-Only';
      headerActions.insertBefore(badge, headerActions.firstChild);
      feather.replace();
    }
  }
  
  // Kalau operator, hide tombol hapus aja
  if (role === 'operator') {
    console.log('⚙️ Mode Operator: Bisa tambah & edit, tidak bisa hapus');
    hideElements('button[onclick*="delete"]');
    hideElements('button[onclick*="Hapus"]');
    hideElements('.btn-danger[onclick*="delete"]');
  }
  
  // Kalau admin, tampilkan badge admin
  if (role === 'admin') {
    console.log('👑 Mode Admin: Full Access');
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
  const role = window.APP_CONFIG?.userRole || 'viewer';
  
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
// NAVIGATION
// ============================================================
const pageTitles = {
  dashboard:        ['Dashboard', 'Overview semua aktivitas'],
  monitoring:       ['Monitoring Realtime', 'Status mesin & order live'],
  items:            ['Bahan Baku', 'Kelola inventory material'],
  'stock-mutation': ['Mutasi Stok', 'Stok masuk & keluar'],
  'order-input':    ['Input Order Baru', 'Isi data pelanggan & pesanan, nota langsung tercetak'],
  orders:           ['Order Cetak', 'Manajemen pesanan pelanggan'],
  deliveries:       ['Pengiriman', 'Monitoring pengiriman ke pelanggan'],
  machines:         ['Mesin', 'Status & log mesin'],
  customers:        ['Pelanggan', 'Riwayat pelanggan & order'],
  products:         ['Produk & Harga', 'Daftar produk/jasa percetakan'],
  reports:          ['Laporan', 'Analisis & ekspor data'],
};

function navigate(page) {
  currentPage = page;
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  const el = document.getElementById(`page-${page}`);
  if (el) el.classList.add('active');

  const nav = document.querySelector(`.nav-item[data-page="${page}"]`);
  if (nav) nav.classList.add('active');

  const [title, sub] = pageTitles[page] || [page, ''];
  document.getElementById('page-title').innerHTML = title + ` <span>${sub}</span>`;

  // Stop monitoring interval if leaving
  if (page !== 'monitoring' && monitorInterval) {
    clearInterval(monitorInterval);
    monitorInterval = null;
  }

  // Load page data
  switch (page) {
    case 'dashboard':      loadDashboard(); break;
    case 'monitoring':     loadMonitoring(); startMonitorInterval(); break;
    case 'items':          loadItems(); break;
    case 'stock-mutation': loadStockMutationPage(); break;
    case 'order-input':    loadOrderInputPage(); break;
    case 'orders':         loadOrders(); break;
    case 'deliveries':     loadDeliveries(); break;
    case 'machines':       loadMachinesPage(); break;
    case 'customers':      loadCustomers(); break;
    case 'products':       loadProducts(); break;
  }

  feather.replace();
}

function openQuickAdd() {
  const map = {
    items:         openAddItemModal,
    'order-input': niSimpanOrder,
    orders:        () => navigate('order-input'),
    deliveries:    () => showToast('Pilih order yang sudah selesai untuk membuat pengiriman', 'info'),
    customers:     () => navigate('order-input'),
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
  refCategories.forEach(c => appendSelect('item-category', `<option value="${c.id}">${c.name}</option>`));

  setSelect('item-unit', '<option value="">-- Pilih Satuan --</option>');
  refUnits.forEach(u => appendSelect('item-unit', `<option value="${u.id}">${u.name} (${u.symbol})</option>`));

  // Category filter on items page
  refCategories.forEach(c => appendSelect('items-cat-filter', `<option value="${c.id}">${c.name}</option>`));
}

function populateItemSelects(items) {
  const opts = items.map(i => `<option value="${i.id}">${i.code} — ${i.name}</option>`).join('');
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

  // Badge
  document.getElementById('badge-active').textContent = s.active_orders;
  if (s.low_stock > 0) {
    document.getElementById('badge-lowstock').style.display = 'inline';
    document.getElementById('badge-lowstock').textContent = s.low_stock;
  }

  // Machines
  renderDashboardMachines(data.machines);

  // Low Stock
  renderDashboardLowStock(data.low_stock);

  // Orders
  renderDashboardOrders(data.recent_orders);

  // Charts
  renderOrderChart(data.chart_orders);
  renderStockChart(data.chart_stock);

  feather.replace();
}

function renderDashboardMachines(machines) {
  const el = document.getElementById('dashboard-machines');
  if (!machines?.length) { el.innerHTML = '<div class="text-muted text-sm" style="padding:16px">Tidak ada data mesin</div>'; return; }
  el.innerHTML = machines.map(m => `
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-base);border-radius:var(--radius-sm);border:1px solid var(--border);margin-bottom:8px">
      <div style="width:10px;height:10px;border-radius:50%;background:${machineStatusColor(m.status)};flex-shrink:0;box-shadow:0 0 6px ${machineStatusColor(m.status)}"></div>
      <div style="flex:1">
        <div style="font-size:13px;font-weight:600">${m.name}</div>
        <div style="font-size:11px;color:var(--text-muted)">${m.current_job ? `🔄 ${m.current_job}` : 'Tidak ada pekerjaan'}</div>
      </div>
      <span class="badge badge-${m.status}">${statusLabel(m.status)}</span>
    </div>
  `).join('');
}

function renderDashboardLowStock(items) {
  const el = document.getElementById('dashboard-lowstock');
  if (!items?.length) { el.innerHTML = '<div class="text-sm text-success" style="padding:16px">✅ Semua stok dalam kondisi aman</div>'; return; }
  el.innerHTML = items.map(i => {
    const pct = Math.min(100, (i.stock / i.min_stock) * 100);
    const cls = pct <= 25 ? 'danger' : pct <= 75 ? 'warning' : 'success';
    return `
      <div style="margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
          <span style="font-size:12px;font-weight:600">${i.name}</span>
          <span style="font-size:11px;color:var(--text-muted)">${parseFloat(i.stock)} / ${parseFloat(i.min_stock)} ${i.unit}</span>
        </div>
        <div class="progress-bar"><div class="progress-fill ${cls}" style="width:${pct}%"></div></div>
      </div>
    `;
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

// ============================================================
// MONITORING
// ============================================================
function startMonitorInterval() {
  loadMonitoring();
  monitorInterval = setInterval(loadMonitoring, 15000);
}

async function loadMonitoring() {
  const data = await apiFetch(API + 'machines.php?action=list');
  if (!data?.success) return;
  renderMachineGrid(data.machines || data.data, 'machine-grid');

  const kanban = await apiFetch(API + 'orders.php?action=kanban');
  if (kanban?.success) renderKanban(kanban.data);
  feather.replace();
}

function renderMachineGrid(machines, containerId) {
  const el = document.getElementById(containerId);
  if (!machines?.length) { el.innerHTML = '<div class="empty-state"><h3>Tidak ada data mesin</h3></div>'; return; }
  el.innerHTML = machines.map(m => `
    <div class="machine-card" data-status="${m.status}">
      <div class="machine-header">
        <div>
          <div class="machine-name">${m.name}</div>
          <div class="machine-type">${m.type || m.brand || '—'}</div>
        </div>
        <span class="badge badge-${m.status}">${statusLabel(m.status)}</span>
      </div>
      ${m.current_job ? `<div class="machine-job">🔄 <strong>${m.order_number}</strong><br><span>${m.current_job}</span></div>` : '<div class="machine-job" style="color:var(--text-muted)">Tidak ada pekerjaan aktif</div>'}
      ${m.operator_name ? `<div style="font-size:11px;color:var(--text-muted);margin-top:6px">👤 ${m.operator_name}</div>` : ''}
      <div style="display:flex;gap:8px;margin-top:14px">
        <button class="btn btn-secondary btn-sm" onclick="changeMachineStatus(${m.id},'active')">▶ Aktif</button>
        <button class="btn btn-warning btn-sm" onclick="changeMachineStatus(${m.id},'maintenance')">🔧 Maintenance</button>
        <button class="btn btn-secondary btn-sm" onclick="changeMachineStatus(${m.id},'idle')">⏸ Idle</button>
      </div>
    </div>
  `).join('');
}

function renderKanban(data) {
  const board = document.getElementById('kanban-board');
  const cols = {
    pending: 'Pending', confirmed: 'Dikonfirmasi', in_progress: 'Proses',
    quality_check: 'Quality Check', completed: 'Selesai'
  };
  const colColors = {
    pending:'#94a3b8', confirmed:'#60a5fa', in_progress:'#fbbf24',
    quality_check:'#22d3ee', completed:'#34d399'
  };
  board.innerHTML = Object.entries(cols).map(([key, label]) => {
    const cards = data[key] || [];
    return `
      <div class="kanban-col">
        <div class="kanban-col-header">
          <div class="kanban-col-title" style="color:${colColors[key]}">${label}</div>
          <div class="kanban-count">${cards.length}</div>
        </div>
        <div class="kanban-cards">
          ${cards.length ? cards.map(o => `
            <div class="kanban-card" onclick="openOrderStatusModal(${o.id})">
              <div class="kanban-card-title">${o.title}</div>
              <div style="margin-bottom:6px"><span class="badge badge-${o.priority}" style="font-size:10px">${priorityLabel(o.priority)}</span></div>
              <div class="kanban-card-meta">
                <span class="kanban-card-customer">${o.customer_name}</span>
                <span class="kanban-card-due">${formatDate(o.due_date)}</span>
              </div>
            </div>
          `).join('') : '<div style="color:var(--text-muted);font-size:12px;text-align:center;padding:16px">Kosong</div>'}
        </div>
      </div>
    `;
  }).join('');
}

function switchMonitorTab(el, tab) {
  document.querySelectorAll('.tabs .tab-pill').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('monitor-tab-cards').style.display = tab === 'cards' ? 'block' : 'none';
  document.getElementById('monitor-tab-kanban').style.display = tab === 'kanban' ? 'block' : 'none';
}

async function changeMachineStatus(id, status) {
  const res = await apiPut(API + 'machines.php', { id, status, description: `Status diubah ke ${status}` });
  if (res?.success) { showToast(`Status mesin diupdate ke ${statusLabel(status)}`, 'success'); loadMonitoring(); }
  else showToast('Gagal update status mesin', 'error');
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
            <button class="btn btn-secondary btn-sm btn-icon" title="Edit" onclick="openEditItemModal(${i.id})"><i data-feather="edit-2"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" title="Hapus" onclick="deleteItem(${i.id})"><i data-feather="trash-2"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

async function deleteItem(id) {
  // Cek permission
  if (!checkPermission('delete')) return;
  
  if (!confirm('Hapus item ini?')) return;
  const res = await apiFetch(`${API}items.php?id=${id}`, { method: 'DELETE' });
  if (res?.berhasil) { showToast('Item berhasil dihapus', 'success'); loadItems(); }
  else showToast('Gagal menghapus item', 'error');
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
  document.getElementById('item-id').value = item.id;
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
  document.getElementById('item-sell-price').value = parseFloat(item.selling_price);
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
    supplier_id: null,
    location: document.getElementById('item-location').value,
    stock: document.getElementById('item-stock').value,
    min_stock: document.getElementById('item-min-stock').value,
    max_stock: 0,
    purchase_price: document.getElementById('item-buy-price').value,
    selling_price: document.getElementById('item-sell-price').value,
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
}

async function submitStockIn() {
  // Cek permission
  if (!checkPermission('create')) return;
  
  const itemId = document.getElementById('in-item-id').value;
  const qty    = document.getElementById('in-qty').value;
  const price  = document.getElementById('in-price').value;
  const notes  = document.getElementById('in-notes').value;
  if (!itemId || !qty || qty <= 0) { showToast('Pilih item dan masukkan jumlah yang valid', 'warning'); return; }
  const res = await apiPost(`${API}items.php?action=stock_in`, { item_id: itemId, quantity: qty, unit_price: price, notes });
  if (res?.berhasil) {
    showToast('Stok masuk berhasil dicatat', 'success');
    document.getElementById('in-qty').value = '';
    document.getElementById('in-price').value = '';
    document.getElementById('in-notes').value = '';
    loadMutations();
  } else showToast(res?.pesan || 'Gagal mencatat stok masuk', 'error');
}

async function submitStockOut() {
  // Cek permission
  if (!checkPermission('create')) return;
  
  const itemId = document.getElementById('out-item-id').value;
  const qty    = document.getElementById('out-qty').value;
  const notes  = document.getElementById('out-notes').value;
  if (!itemId || !qty || qty <= 0) { showToast('Pilih item dan masukkan jumlah yang valid', 'warning'); return; }
  const res = await apiPost(`${API}items.php?action=stock_out`, { item_id: itemId, quantity: qty, notes });
  if (res?.berhasil) {
    showToast('Stok keluar berhasil dicatat', 'success');
    document.getElementById('out-qty').value = '';
    document.getElementById('out-notes').value = '';
    loadMutations();
  } else showToast(res?.pesan || 'Gagal mencatat stok keluar', 'error');
}

async function loadMutations() {
  const itemId = document.getElementById('mutation-item-filter').value;
  if (!itemId) { document.getElementById('mutations-tbody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Pilih item untuk melihat riwayat</td></tr>'; return; }
  const data = await apiFetch(`${API}items.php?action=transactions&id=${itemId}`);
  const tbody = document.getElementById('mutations-tbody');
  if (!data?.data?.length) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Belum ada mutasi</td></tr>'; return; }
  tbody.innerHTML = data.data.map(t => `
    <tr>
      <td style="font-size:12px">${formatDateTime(t.created_at)}</td>
      <td>${allItems.find(i=>i.id==t.item_id)?.name || '—'}</td>
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
  tbody.innerHTML = products.map(p => `
    <tr>
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${p.code}</td>
      <td style="font-weight:600">
        ${p.name}
        ${!p.is_active || p.is_active == '0'
          ? '<span class="badge badge-cancelled" style="margin-left:8px;font-size:10px">Nonaktif</span>'
          : ''}
      </td>
      <td style="color:var(--text-muted)">${p.category_name || '—'}</td>
      <td style="color:var(--text-muted)">${p.unit_symbol   || '—'}</td>
      <td style="font-weight:600;color:var(--success)">${formatCurrency(p.default_price)}</td>
      <td style="color:var(--text-muted);font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        ${p.description || '—'}
      </td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn btn-secondary btn-sm btn-icon" onclick="openEditProductModal(${p.id})" title="Edit">
            <i data-feather="edit-2"></i>
          </button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteProduct(${p.id})" title="Nonaktifkan">
            <i data-feather="trash-2"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
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
  refCategories.forEach(c => catSel.innerHTML += `<option value="${c.id}">${c.name}</option>`);
  const unitSel = document.getElementById('product-unit');
  unitSel.innerHTML = '<option value="">-- Pilih Satuan --</option>';
  refUnits.forEach(u => unitSel.innerHTML += `<option value="${u.id}">${u.name} (${u.symbol})</option>`);

  openModal('modal-add-product');
  feather.replace();
}

async function openEditProductModal(id) {
  if (!checkPermission('edit')) return;
  const data = await apiFetch(`${API}products.php?action=get&id=${id}`);
  const p = data?.data;
  if (!p) { showToast('Gagal memuat data produk', 'error'); return; }

  document.getElementById('modal-product-title').textContent = 'Edit Produk';
  document.getElementById('product-id').value    = p.id;
  document.getElementById('product-name').value  = p.name;
  document.getElementById('product-price').value = parseFloat(p.default_price);
  document.getElementById('product-desc').value  = p.description || '';
  document.getElementById('product-active-wrap').style.display = 'block';
  document.getElementById('product-active').checked = p.is_active == 1;

  // Isi dropdown
  const catSel = document.getElementById('product-category');
  catSel.innerHTML = '<option value="">-- Pilih Kategori --</option>';
  refCategories.forEach(c => catSel.innerHTML += `<option value="${c.id}">${c.name}</option>`);
  catSel.value = p.category_id || '';

  const unitSel = document.getElementById('product-unit');
  unitSel.innerHTML = '<option value="">-- Pilih Satuan --</option>';
  refUnits.forEach(u => unitSel.innerHTML += `<option value="${u.id}">${u.name} (${u.symbol})</option>`);
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

async function deleteProduct(id) {
  if (!checkPermission('delete')) return;
  if (!confirm('Nonaktifkan produk ini?')) return;
  const res = await apiFetch(`${API}products.php?id=${id}`, { method: 'DELETE' });
  if (res?.success) { showToast('Produk dinonaktifkan', 'success'); loadProducts(); }
  else showToast('Gagal', 'error');
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

  // Load mesin & operator kalau belum ada
  const machSel = document.getElementById('ni-machine');
  if (machSel && machSel.options.length <= 1) {
    const [machs, ops] = await Promise.all([
      apiFetch('api/machines.php?action=list'),
      apiFetch('api/dashboard.php?action=operators'),
    ]);
    (machs?.data || []).forEach(m => {
      machSel.innerHTML += `<option value="${m.id}">${m.name}</option>`;
    });
    const opSel = document.getElementById('ni-operator');
    (ops?.data || []).forEach(o => {
      opSel.innerHTML += `<option value="${o.id}">${o.name}</option>`;
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
          opt.value = p.id;
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
  clearTimeout(niSearchTimeout);
  const dd = document.getElementById('ni-cust-dropdown');
  if (q.length < 2) { dd.style.display = 'none'; return; }
  niSearchTimeout = setTimeout(async () => {
    const r    = await apiFetch(`api/customers.php?action=search&q=${encodeURIComponent(q)}`);
    const list = r?.data || [];
    if (!list.length) { dd.style.display = 'none'; return; }
    dd.innerHTML = list.map(c => `
      <div onclick="niPilihPelanggan(${c.id},'${niEsc(c.name)}','${niEsc(c.phone)}','${niEsc(c.city||'')}','${niEsc(c.address||'')}')"
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

// ---- Pilih produk dari dropdown → isi form ----
function niPilihProduk(productId) {
  if (!productId) return;
  const sel = document.getElementById('ni-product-select');
  const opt = sel.querySelector(`option[value="${productId}"]`);
  if (!opt) return;

  // Isi judul & harga dari produk
  const titleEl = document.getElementById('ni-title');
  const priceEl = document.getElementById('ni-price');
  const descEl  = document.getElementById('ni-desc');

  if (titleEl) titleEl.value = opt.dataset.name  || '';
  if (priceEl) priceEl.value = opt.dataset.price  || 0;
  if (descEl && !descEl.value) descEl.value = opt.dataset.desc || '';

  niHitungTotal();
  showToast(`Produk "${opt.dataset.name}" dipilih — harga bisa diubah`, 'info');
}

// ---- Hitung total ----
function niHitungTotal() {
  const qty  = parseFloat(document.getElementById('ni-qty')?.value)      || 0;
  const price= parseFloat(document.getElementById('ni-price')?.value)    || 0;
  const disc = parseFloat(document.getElementById('ni-discount')?.value) || 0;
  const tax  = parseFloat(document.getElementById('ni-tax')?.value)      || 0;
  const sub  = qty * price;
  const taxAmt = (sub - disc) * (tax / 100);
  const total  = sub - disc + taxAmt;
  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('ni-c-subtotal', formatCurrency(sub));
  set('ni-c-discount', '- ' + formatCurrency(disc));
  set('ni-c-tax',      '+ ' + formatCurrency(taxAmt));
  set('ni-c-total',    formatCurrency(total));
}

// ---- Simpan order ----
async function niSimpanOrder() {
  if (!checkPermission('create')) return;

  const name  = document.getElementById('ni-cust-name').value.trim();
  const phone = document.getElementById('ni-cust-phone').value.trim();
  const title = document.getElementById('ni-title').value.trim();
  const qty   = parseFloat(document.getElementById('ni-qty').value)   || 0;
  const price = parseFloat(document.getElementById('ni-price').value) || 0;
  const due   = document.getElementById('ni-due').value;

  if (!name)   { showToast('Nama pelanggan wajib diisi', 'warning'); return; }
  if (!phone)  { showToast('No. HP pelanggan wajib diisi', 'warning'); return; }
  if (!title)  { showToast('Judul pesanan wajib diisi', 'warning'); return; }
  if (qty < 1) { showToast('Jumlah minimal 1', 'warning'); return; }
  if (price < 1){ showToast('Harga satuan wajib diisi', 'warning'); return; }
  if (!due)    { showToast('Jatuh tempo wajib diisi', 'warning'); return; }

  const btn = document.getElementById('ni-btn-simpan');
  btn.disabled = true;
  btn.innerHTML = '<i data-feather="refresh-cw"></i> Menyimpan...';
  feather.replace();

  const payload = {
    customer_name:    name,
    customer_phone:   phone,
    customer_city:    document.getElementById('ni-cust-city').value,
    customer_address: document.getElementById('ni-cust-address').value,
    title,
    description:  document.getElementById('ni-desc').value,
    quantity:     qty,
    unit_price:   price,
    discount:     parseFloat(document.getElementById('ni-discount').value) || 0,
    tax:          parseFloat(document.getElementById('ni-tax').value)      || 11,
    machine_id:   document.getElementById('ni-machine').value  || null,
    operator_id:  document.getElementById('ni-operator').value || null,
    priority:     document.getElementById('ni-priority').value,
    due_date:     due,
    notes:        document.getElementById('ni-notes').value,
  };

  const res = await apiPost('api/orders.php?action=create_with_customer', payload);

  btn.disabled = false;
  btn.innerHTML = '<i data-feather="save"></i> Simpan & Tampilkan Nota';
  feather.replace();

  if (res?.success) {
    showToast('Order ' + res.order_number + ' berhasil disimpan!', 'success');
    niTampilkanNota(res.data, res.order_number);
  } else {
    showToast(res?.message || 'Gagal menyimpan order', 'error');
  }
}

// ---- Tampilkan Nota ----
function niTampilkanNota(o, orderNum) {
  const qty   = parseFloat(o.quantity)   || 0;
  const price = parseFloat(o.unit_price) || 0;
  const disc  = parseFloat(o.discount)   || 0;
  const tax   = parseFloat(o.tax)        || 0;
  const sub   = qty * price;
  const taxAmt= (sub - disc) * (tax / 100);
  const total = sub - disc + taxAmt;

  const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
  set('ni-nota-num',       orderNum);
  set('ni-nota-num2',      orderNum);
  set('ni-nota-tgl',       'Tanggal: ' + formatDate(new Date().toISOString()));
  set('ni-nota-cust-name', o.customer_name);
  set('ni-nota-cust-phone',o.customer_phone || '—');
  set('ni-nota-cust-city', o.customer_city  || '—');
  set('ni-nota-title',     o.title);
  set('ni-nota-qty',       qty + ' pcs');
  set('ni-nota-price',     formatCurrency(price));
  set('ni-nota-subtotal',  formatCurrency(sub));
  set('ni-nota-disc',      '- ' + formatCurrency(disc));
  set('ni-nota-tax',       '+ ' + formatCurrency(taxAmt));
  set('ni-nota-total',     formatCurrency(total));
  set('ni-nota-due',       formatDate(o.due_date));
  set('ni-nota-priority',  {'low':'Rendah','normal':'Normal','high':'Tinggi','urgent':'URGENT'}[o.priority] || o.priority);

  document.getElementById('ni-nota-placeholder').style.display = 'none';
  document.getElementById('ni-nota-content').style.display     = 'block';
  feather.replace();

  // Scroll ke nota di layar kecil
  if (window.innerWidth < 900) {
    document.getElementById('ni-nota-content').scrollIntoView({behavior:'smooth'});
  }
}

function niResetForm() {
  ['ni-cust-name','ni-cust-phone','ni-cust-city','ni-cust-address',
   'ni-cust-search','ni-title','ni-desc','ni-notes'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  ['ni-qty','ni-price','ni-discount','ni-tax'].forEach((id, i) => {
    const el = document.getElementById(id);
    if (el) el.value = [1,0,0,11][i];
  });
  const machSel = document.getElementById('ni-machine');
  if (machSel) machSel.value = '';
  const opSel = document.getElementById('ni-operator');
  if (opSel) opSel.value = '';
  const prioSel = document.getElementById('ni-priority');
  if (prioSel) prioSel.value = 'normal';
  const prodSel = document.getElementById('ni-product-select');
  if (prodSel) prodSel.value = '';

  // Reset due date
  const due = document.getElementById('ni-due');
  if (due) { const d = new Date(); d.setDate(d.getDate()+7); due.value = d.toISOString().slice(0,10); }

  // Reset nota
  const ph = document.getElementById('ni-nota-placeholder');
  const ct = document.getElementById('ni-nota-content');
  if (ph) ph.style.display = 'block';
  if (ct) ct.style.display = 'none';

  niHitungTotal();
  window.scrollTo({top:0, behavior:'smooth'});
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

async function loadOrders() {
  const searchEl = document.getElementById('orders-search');
  const statusEl = document.getElementById('orders-status-filter');
  const search = searchEl ? searchEl.value : '';
  const status = statusEl ? statusEl.value : '';
  const params = new URLSearchParams({ action: 'list', search, status });
  const data   = await apiFetch(`${API}orders.php?${params}`);
  allOrders    = data?.data || [];
  renderOrdersList(allOrders);
  feather.replace();
}

function filterOrders() { loadOrders(); }

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
    const onclick   = isNext ? `onclick="updateOrderStatus(${o.id},'${step.key}',${o.machine_id||'null'})"` : '';

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
         onclick="updateOrderStatus(${o.id},'cancelled',${o.machine_id||'null'})">
         <i data-feather="x"></i> Batalkan
       </button>`
    : '';

  // Delivery badge if completed
  const deliveryBtn = o.status === 'completed'
    ? `<button class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px"
         onclick="openNewDeliveryModal(${o.id})">
         <i data-feather="truck"></i> Kirim
       </button>`
    : '';

  return `
    <div class="order-card" id="order-card-${o.id}">
      <div class="order-card-header">
        <div class="order-card-left">
          <div class="order-card-num">${o.order_number}</div>
          <div class="order-card-title">${o.title}</div>
          <div class="order-card-meta">
            <span><i data-feather="user"></i>${o.customer_name}</span>
            ${o.machine_name ? `<span><i data-feather="cpu"></i>${o.machine_name}</span>` : ''}
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
      ${isCancelled ? '<div style="font-size:12px;color:var(--danger);margin-top:8px">⛔ Order dibatalkan</div>' : ''}
    </div>`;
}

async function updateOrderStatus(orderId, newStatus, machineId) {
  if (!checkPermission('edit')) return;
  const label = statusLabel(newStatus);
  const res = await apiPut(`${API}orders.php`, {
    id: orderId, status: newStatus,
    machine_id: machineId, status_only: true
  });
  if (res?.success) {
    showToast(`Status order → ${label}`, 'success');
    // Update hanya card ini tanpa reload semua
    const order = allOrders.find(o => o.id == orderId);
    if (order) {
      order.status = newStatus;
      const card = document.getElementById(`order-card-${orderId}`);
      if (card) {
        card.outerHTML = renderOrderCard(order);
        feather.replace();
      }
    }
    // Jika selesai, tawarkan buat pengiriman
    if (newStatus === 'completed') {
      setTimeout(() => openNewDeliveryModal(orderId), 300);
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

async function loadDeliveries() {
  const searchEl = document.getElementById('deliveries-search');
  const statusEl = document.getElementById('deliveries-status-filter');
  const search = searchEl ? searchEl.value : '';
  const status = statusEl ? statusEl.value : '';
  const params = new URLSearchParams({ action: 'list', search, status });
  const data   = await apiFetch(`${API}deliveries.php?${params}`);
  allDeliveries = data?.data || [];
  renderDeliveriesList(allDeliveries);

  const inTransit = allDeliveries.filter(d => d.status === 'shipping').length;
  const badge = document.getElementById('badge-deliveries');
  if (badge) { badge.textContent = inTransit; badge.style.display = inTransit > 0 ? 'inline' : 'none'; }
  feather.replace();
}

function filterDeliveries() { loadDeliveries(); }

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
      ? `onclick="updateDeliveryStatus(${d.id},'${step.key}')"`
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
    <div class="proof-upload-zone" id="proof-zone-${d.id}">
      <label>
        <i data-feather="camera"></i> Pilih Foto Bukti
        <input type="file" accept="image/*" id="proof-file-${d.id}"
          onchange="previewProof(${d.id}, this)" />
      </label>
      <img class="proof-preview" id="proof-preview-${d.id}" />
      <div class="proof-upload-note">
        JPG / PNG / WEBP, maks. 5MB<br>
        <span style="color:var(--warning);font-size:10px">⚠️ Foto wajib sebelum konfirmasi diterima</span>
      </div>
      <button class="btn-confirm-received" id="btn-received-${d.id}"
        onclick="submitReceived(${d.id})" disabled>
        <i data-feather="check-circle"></i> Konfirmasi Diterima
      </button>
    </div>` : '';

  // Tampilkan foto jika sudah received
  const proofDisplay = d.proof_image ? `
    <div style="margin-top:10px;display:flex;align-items:center;gap:10px">
      <img src="uploads/proof/${d.proof_image}" class="proof-img-thumb"
        onclick="openLightbox('uploads/proof/${d.proof_image}')"
        title="Klik untuk perbesar" />
      <span style="font-size:12px;color:var(--success)">✅ Bukti pengiriman tersedia</span>
    </div>` : '';

  return `
    <div class="order-card" id="delivery-card-${d.id}">
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

async function updateDeliveryStatus(id, newStatus) {
  if (!checkPermission('edit')) return;
  const res = await apiPut(`${API}deliveries.php`, { id, status: newStatus });
  if (res?.success) {
    showToast(`Pengiriman → ${deliveryStatusLabel(newStatus)}`, 'success');
    loadDeliveries();
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
      showToast('Pengiriman dikonfirmasi diterima ✅', 'success');
      loadDeliveries();
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
// MACHINES (Page)
// ============================================================
async function loadMachinesPage() {
  const data = await apiFetch(API + 'machines.php?action=list');
  if (!data?.success) return;
  renderMachineGrid(data.data, 'machines-page-grid');
  feather.replace();
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
      <p>Pelanggan akan otomatis terdaftar saat order baru dibuat.</p>
      <a href="order_baru.php" class="btn btn-primary mt-4">
        <i data-feather="plus-circle"></i> Input Order Baru
      </a>
    </div></div>`;
    feather.replace(); return;
  }

  el.innerHTML = customers.map(c => `
    <div class="order-card" style="cursor:pointer" onclick="lihatHistory(${c.id})">
      <div class="order-card-header">
        <div class="order-card-left">
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
        <div class="order-card-right">
          <span style="font-size:12px;color:var(--text-muted)">Lihat Riwayat</span>
          <i data-feather="chevron-right" style="width:16px;height:16px;stroke:var(--text-muted)"></i>
        </div>
      </div>
    </div>
  `).join('');
  feather.replace();
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
    stock: ['Kode','Nama','Kategori','Stok','Min','Maks','Satuan','Nilai','Status'],
    transactions: ['Waktu','Kode','Nama Item','Tipe','Jumlah','Satuan','Sblm','Sesudah','Harga','Referensi'],
    orders: ['No. Order','Judul','Status','Prioritas','Customer','Mesin','Qty','Total','Jatuh Tempo','Selesai'],
  };

  const cols = headers[type] || [];
  let html = `<table><thead><tr>${cols.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>`;

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
        <td>${r.machine||'—'}</td>
        <td>${r.quantity}</td>
        <td style="color:var(--success);font-weight:600">${formatCurrency(r.grand_total)}</td>
        <td style="font-size:12px">${formatDate(r.due_date)}</td>
        <td style="font-size:12px">${r.completed_date?formatDateTime(r.completed_date):'—'}</td>
      </tr>`;
    }
  });

  html += '</tbody></table>';
  wrapper.innerHTML = html;
}

let reportCache = [];
function exportReport() {
  // Simple CSV export
  const table = document.querySelector('#report-table-wrapper table');
  if (!table) { showToast('Tidak ada data untuk diekspor', 'warning'); return; }
  let csv = '';
  table.querySelectorAll('tr').forEach(row => {
    const cells = [...row.querySelectorAll('th,td')].map(c => `"${c.innerText.replace(/"/g,'""')}"`);
    csv += cells.join(',') + '\n';
  });
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = `laporan_${currentReportType}_${new Date().toISOString().slice(0,10)}.csv`;
  a.click(); URL.revokeObjectURL(url);
  showToast('Laporan berhasil diekspor', 'success');
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

function machineStatusColor(s) {
  const map = { active:'#10b981', idle:'#94a3b8', maintenance:'#f59e0b', offline:'#ef4444' };
  return map[s] || '#94a3b8';
}
