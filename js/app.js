// ============================================================
// PrintTrack — Main Application JS
// ============================================================

const API = 'api/';
let chartOrders = null;
let chartStock  = null;
let monitorInterval = null;
let currentPage = 'dashboard';
let currentReportType = 'stock';

// ---- Ref Data ----
let refCategories = [];
let refUnits      = [];
let refSuppliers  = [];
let refCustomers  = [];
let refMachines   = [];
let refOperators  = [];
let allItems      = [];
let allOrders     = [];
let allSuppliers  = [];

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', async () => {
  feather.replace();
  startClock();
  
  // Apply role-based UI restrictions
  applyRoleBasedUI();
  
  await loadRefData();
  populateFormSelects();
  navigate('dashboard');

  // Sidebar nav
  document.querySelectorAll('.nav-item[data-page]').forEach(el => {
    el.addEventListener('click', () => navigate(el.dataset.page));
  });

  // Report date defaults
  const today = new Date().toISOString().slice(0,10);
  const monthStart = today.slice(0,8) + '01';
  document.getElementById('report-from').value = monthStart;
  document.getElementById('report-to').value   = today;
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
  dashboard: ['Dashboard', 'Overview semua aktivitas'],
  monitoring: ['Monitoring Realtime', 'Status mesin & order live'],
  items:      ['Bahan Baku', 'Kelola inventory material'],
  'stock-mutation': ['Mutasi Stok', 'Stok masuk & keluar'],
  orders:     ['Order Cetak', 'Manajemen pesanan'],
  machines:   ['Mesin', 'Status & log mesin'],
  suppliers:  ['Supplier', 'Data pemasok bahan'],
  customers:  ['Pelanggan', 'Data pelanggan'],
  purchases:  ['Pembelian', 'Purchase Order'],
  reports:    ['Laporan', 'Analisis & ekspor data'],
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
    case 'orders':         loadOrders(); break;
    case 'machines':       loadMachinesPage(); break;
    case 'suppliers':      loadSuppliers(); break;
    case 'customers':      loadCustomers(); break;
  }

  feather.replace();
}

function openQuickAdd() {
  const map = { items: openAddItemModal, orders: openAddOrderModal, suppliers: openAddSupplierModal };
  if (map[currentPage]) map[currentPage]();
  else showToast('Pilih halaman yang sesuai untuk menambah data', 'info');
}

// ============================================================
// REF DATA LOADER
// ============================================================
async function loadRefData() {
  const [cats, units, sups, custs, machs] = await Promise.all([
    apiFetch('api/categories.php'),
    apiFetch('api/units.php'),
    apiFetch('api/suppliers.php'),
    apiFetch('api/customers.php'),
    apiFetch('api/machines.php'),
  ]);
  refCategories = cats?.data || [];
  refUnits      = units?.data || [];
  refSuppliers  = sups?.data || [];
  refCustomers  = custs?.data || [];
  refMachines   = machs?.data || [];
}

function populateFormSelects() {
  // Category filter on items page
  const catFilter = document.getElementById('items-cat-filter');
  refCategories.forEach(c => {
    catFilter.innerHTML += `<option value="${c.id}">${c.name}</option>`;
  });

  // Add item modal
  const itemCat = document.getElementById('item-category');
  itemCat.innerHTML = '<option value="">-- Pilih Kategori --</option>';
  refCategories.forEach(c => itemCat.innerHTML += `<option value="${c.id}">${c.name}</option>`);

  const itemUnit = document.getElementById('item-unit');
  itemUnit.innerHTML = '<option value="">-- Pilih Satuan --</option>';
  refUnits.forEach(u => itemUnit.innerHTML += `<option value="${u.id}">${u.name} (${u.symbol})</option>`);

  const itemSup = document.getElementById('item-supplier');
  refSuppliers.forEach(s => itemSup.innerHTML += `<option value="${s.id}">${s.name}</option>`);

  // Add order modal
  const orderCust = document.getElementById('order-customer');
  orderCust.innerHTML = '<option value="">-- Pilih Pelanggan --</option>';
  refCustomers.forEach(c => orderCust.innerHTML += `<option value="${c.id}">${c.name}</option>`);

  const orderMach = document.getElementById('order-machine');
  orderMach.innerHTML = '<option value="">-- Pilih Mesin --</option>';
  refMachines.forEach(m => orderMach.innerHTML += `<option value="${m.id}">${m.name}</option>`);

  const statusMach = document.getElementById('status-machine');
  statusMach.innerHTML = '<option value="">-- Tanpa Mesin --</option>';
  refMachines.forEach(m => statusMach.innerHTML += `<option value="${m.id}">${m.name}</option>`);

  // Stock mutation selects
  const inItem = document.getElementById('in-item-id');
  const outItem = document.getElementById('out-item-id');
  const mutFilter = document.getElementById('mutation-item-filter');

  // Will be populated after items load
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
  document.getElementById('item-supplier').value = item.supplier_id || '';
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
    supplier_id: document.getElementById('item-supplier').value,
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
// ORDERS
// ============================================================
async function loadOrders() {
  const search = document.getElementById('orders-search').value;
  const status = document.getElementById('orders-status-filter').value;
  const params = new URLSearchParams({ action: 'list', search, status });
  const data   = await apiFetch(`${API}orders.php?${params}`);
  allOrders    = data?.data || [];
  renderOrdersTable(allOrders);
  feather.replace();
}

function filterOrders() { loadOrders(); }

function renderOrdersTable(orders) {
  const tbody = document.getElementById('orders-tbody');
  if (!orders.length) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><h3>Tidak ada order ditemukan</h3></div></td></tr>`;
    return;
  }
  tbody.innerHTML = orders.map(o => `
    <tr>
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${o.order_number}</td>
      <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${o.title}</td>
      <td>${o.customer_name}</td>
      <td style="color:var(--text-muted);font-size:12px">${o.machine_name || '—'}</td>
      <td><span class="badge badge-${o.status}">${statusLabel(o.status)}</span></td>
      <td><span class="badge badge-${o.priority}">${priorityLabel(o.priority)}</span></td>
      <td style="font-size:12px;color:${isDueSoon(o.due_date)?'var(--danger)':'var(--text-muted)'}">${formatDate(o.due_date)}</td>
      <td style="font-weight:600;color:var(--success)">${formatCurrency(o.grand_total)}</td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="openOrderStatusModal(${o.id})">
          <i data-feather="edit"></i> Update
        </button>
      </td>
    </tr>
  `).join('');
}

function openAddOrderModal() { openModal('modal-add-order'); calcOrderTotal(); }

function calcOrderTotal() {
  const qty  = parseFloat(document.getElementById('order-qty').value) || 0;
  const price= parseFloat(document.getElementById('order-price').value) || 0;
  const disc = parseFloat(document.getElementById('order-discount').value) || 0;
  const tax  = parseFloat(document.getElementById('order-tax').value) || 0;
  const sub  = qty * price;
  const taxAmt = (sub - disc) * (tax / 100);
  const total  = sub - disc + taxAmt;
  document.getElementById('calc-subtotal').textContent = formatCurrency(sub);
  document.getElementById('calc-discount').textContent = '- ' + formatCurrency(disc);
  document.getElementById('calc-tax').textContent = '+ ' + formatCurrency(taxAmt);
  document.getElementById('calc-total').textContent = formatCurrency(total);
}

async function submitAddOrder(e) {
  e.preventDefault();
  const data = {
    customer_id: document.getElementById('order-customer').value,
    machine_id: document.getElementById('order-machine').value,
    operator_id: '',
    title: document.getElementById('order-title').value,
    priority: document.getElementById('order-priority').value,
    quantity: document.getElementById('order-qty').value,
    unit_price: document.getElementById('order-price').value,
    discount: document.getElementById('order-discount').value,
    tax: document.getElementById('order-tax').value,
    start_date: document.getElementById('order-start').value,
    due_date: document.getElementById('order-due').value,
    notes: document.getElementById('order-notes').value,
  };
  const res = await apiPost(`${API}orders.php`, data);
  if (res?.success) {
    showToast(`Order ${res.order_number} berhasil dibuat`, 'success');
    closeModal('modal-add-order');
    e.target.reset();
    loadOrders();
  } else showToast(res?.message || 'Gagal membuat order', 'error');
}

function openOrderStatusModal(id) {
  document.getElementById('status-order-id').value = id;
  const order = allOrders.find(o => o.id == id);
  if (order) {
    document.getElementById('status-new').value = order.status;
    document.getElementById('status-machine').value = order.machine_id || '';
  }
  openModal('modal-order-status');
}

async function submitUpdateOrderStatus() {
  const id     = document.getElementById('status-order-id').value;
  const status = document.getElementById('status-new').value;
  const machId = document.getElementById('status-machine').value;
  const notes  = document.getElementById('status-notes').value;
  const res    = await apiPut(`${API}orders.php`, { id, status, machine_id: machId, notes });
  if (res?.success) {
    showToast('Status order diupdate', 'success');
    closeModal('modal-order-status');
    loadOrders();
    if (currentPage === 'monitoring') loadMonitoring();
  } else showToast('Gagal update order', 'error');
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
  renderCustomersTable(allCustomers);
  feather.replace();
}

function filterCustomers() {
  const q = document.getElementById('customers-search').value.toLowerCase();
  renderCustomersTable(allCustomers.filter(c => 
    c.name.toLowerCase().includes(q) || 
    c.code.toLowerCase().includes(q) ||
    (c.city && c.city.toLowerCase().includes(q))
  ));
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById('customers-tbody');
  if (!customers.length) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Tidak ada data pelanggan</td></tr>';
    return;
  }
  tbody.innerHTML = customers.map(c => `
    <tr>
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${c.code}</td>
      <td style="font-weight:600">${c.name}</td>
      <td style="color:var(--text-muted)">${c.contact_person || '—'}</td>
      <td style="color:var(--text-muted)">${c.phone || '—'}</td>
      <td style="color:var(--text-muted)">${c.city || '—'}</td>
      <td>
        <div style="display:flex;gap:4px">
          <button class="btn btn-secondary btn-sm btn-icon" onclick="openEditCustomerModal(${c.id})" title="Edit"><i data-feather="edit-2"></i></button>
          <button class="btn btn-danger btn-sm btn-icon" onclick="deleteCustomer(${c.id})" title="Hapus"><i data-feather="trash-2"></i></button>
        </div>
      </td>
    </tr>
  `).join('');
  feather.replace();
}

function openAddCustomerModal() {
  document.getElementById('modal-customer-title').textContent = 'Tambah Pelanggan';
  document.getElementById('customer-id').value = '';
  document.getElementById('cust-code').disabled = false;
  document.querySelector('#modal-add-customer form').reset();
  openModal('modal-add-customer');
}

async function openEditCustomerModal(id) {
  const data = await apiFetch(`${API}customers.php?action=get&id=${id}`);
  if (!data?.berhasil) {
    showToast('Gagal mengambil data pelanggan', 'error');
    return;
  }
  
  const customer = data.data;
  document.getElementById('modal-customer-title').textContent = 'Edit Pelanggan';
  document.getElementById('customer-id').value = customer.id;
  document.getElementById('cust-code').value = customer.code;
  document.getElementById('cust-code').disabled = true;
  document.getElementById('cust-name').value = customer.name;
  document.getElementById('cust-contact').value = customer.contact_person || '';
  document.getElementById('cust-phone').value = customer.phone || '';
  document.getElementById('cust-email').value = customer.email || '';
  document.getElementById('cust-city').value = customer.city || '';
  document.getElementById('cust-address').value = customer.address || '';
  document.getElementById('cust-notes').value = customer.notes || '';
  
  openModal('modal-add-customer');
  feather.replace();
}

async function submitAddCustomer(e) {
  e.preventDefault();
  
  const customerId = document.getElementById('customer-id').value;
  const isEdit = customerId !== '';
  
  const data = {
    code: document.getElementById('cust-code').value,
    name: document.getElementById('cust-name').value,
    contact_person: document.getElementById('cust-contact').value,
    phone: document.getElementById('cust-phone').value,
    email: document.getElementById('cust-email').value,
    city: document.getElementById('cust-city').value,
    address: document.getElementById('cust-address').value,
    notes: document.getElementById('cust-notes').value,
  };
  
  let res;
  if (isEdit) {
    data.id = customerId;
    res = await apiPut(`${API}customers.php`, data);
  } else {
    res = await apiPost(`${API}customers.php`, data);
  }
  
  if (res?.berhasil) {
    showToast(isEdit ? 'Pelanggan berhasil diupdate' : 'Pelanggan berhasil ditambahkan', 'success');
    closeModal('modal-add-customer');
    e.target.reset();
    document.getElementById('customer-id').value = '';
    document.getElementById('cust-code').disabled = false;
    loadCustomers();
    loadRefData();
  } else {
    showToast(res?.pesan || 'Gagal menyimpan pelanggan', 'error');
  }
}

async function deleteCustomer(id) {
  // Cek permission
  if (!checkPermission('delete')) return;
  
  if (!confirm('Hapus pelanggan ini?')) return;
  const res = await apiFetch(`${API}customers.php?id=${id}`, { method: 'DELETE' });
  if (res?.berhasil) {
    showToast('Pelanggan berhasil dihapus', 'success');
    loadCustomers();
  } else {
    showToast('Gagal menghapus pelanggan', 'error');
  }
}

// ============================================================
// SUPPLIERS
// ============================================================
async function loadSuppliers() {
  const data = await apiFetch(API + 'suppliers.php');
  allSuppliers = data?.data || [];
  renderSuppliersGrid(allSuppliers);
  feather.replace();
}

function filterSuppliers() {
  const q = document.getElementById('suppliers-search').value.toLowerCase();
  renderSuppliersGrid(allSuppliers.filter(s => s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q)));
}

function renderSuppliersGrid(suppliers) {
  const el = document.getElementById('suppliers-grid');
  if (!suppliers.length) { el.innerHTML = '<div class="empty-state"><i data-feather="truck"></i><h3>Tidak ada supplier</h3></div>'; feather.replace(); return; }
  el.innerHTML = suppliers.map(s => `
    <div class="card" style="cursor:default">
      <div class="flex justify-between mb-4">
        <div>
          <div style="font-family:monospace;font-size:11px;color:var(--accent);margin-bottom:4px">${s.code}</div>
          <div style="font-size:15px;font-weight:700">${s.name}</div>
        </div>
        <span class="badge badge-${s.is_active=='1'?'active':'offline'}">${s.is_active=='1'?'Aktif':'Nonaktif'}</span>
      </div>
      ${s.contact_person ? `<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">👤 ${s.contact_person}</div>` : ''}
      ${s.phone ? `<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px">📞 ${s.phone}</div>` : ''}
      ${s.city ? `<div style="font-size:12px;color:var(--text-muted)">📍 ${s.city}</div>` : ''}
    </div>
  `).join('');
}

function openAddSupplierModal() { openModal('modal-add-supplier'); }

async function submitAddSupplier(e) {
  e.preventDefault();
  showToast('Supplier berhasil ditambahkan (demo)', 'success');
  closeModal('modal-add-supplier');
  e.target.reset();
}

// ============================================================
// CUSTOMERS
// ============================================================
async function loadCustomers() {
  const data = await apiFetch(API + 'customers.php');
  const tbody = document.getElementById('customers-tbody');
  const rows = data?.data || [];
  tbody.innerHTML = rows.map(c => `
    <tr>
      <td style="font-family:monospace;font-size:12px;color:var(--accent)">${c.code}</td>
      <td style="font-weight:600">${c.name}</td>
      <td style="color:var(--text-muted)">${c.contact_person || '—'}</td>
      <td style="color:var(--text-muted)">${c.phone || '—'}</td>
      <td style="color:var(--text-muted)">${c.city || '—'}</td>
      <td><button class="btn btn-secondary btn-sm">Detail</button></td>
    </tr>
  `).join('') || '<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Tidak ada data</td></tr>';
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
