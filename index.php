<?php
// ════════════════════════════════════════════════════════════════
//  CEK LOGIN - Halaman Ini Harus Login Dulu
// ════════════════════════════════════════════════════════════════
require_once 'auth_check.php';
require_once 'helpers.php';  // Load helper functions

// Get user info untuk dipakai di JavaScript
$userRole = getUserRole();
$userInfo = getUserInfo();
?>
<!--
╔══════════════════════════════════════════════════════════════════════════╗
║                                                                          ║
║  🖨️  SISTEM INVENTORY & MONITORING PERCETAKAN                            ║
║                                                                          ║
║  Deskripsi:                                                              ║
║  Website untuk mengelola inventory bahan baku dan monitoring produksi    ║
║  percetakan secara realtime.                                             ║
║                                                                          ║
║  Fitur Utama:                                                            ║
║  ✓ Dashboard dengan statistik & chart                                   ║
║  ✓ Monitoring realtime status mesin                                      ║
║  ✓ Manajemen bahan baku (CRUD)                                           ║
║  ✓ Mutasi stok (stok masuk/keluar)                                       ║
║  ✓ Manajemen order cetak                                                 ║
║  ✓ Laporan & export CSV                                                  ║
║                                                                          ║
║  Teknologi:                                                              ║
║  - Frontend: HTML5, CSS3, JavaScript (Vanilla)                           ║
║  - Backend:  PHP 8+ (PDO), REST API                                      ║
║  - Database: MySQL 8+                                                    ║
║  - Chart:    Chart.js 4.4                                                ║
║  - Icons:    Feather Icons                                               ║
║                                                                          ║
║  Cara Install:                                                           ║
║  1. Import database: database/db_inventory.sql                           ║
║  2. Buka: http://localhost/inventory_monitoring/login.php                ║
║                                                                          ║
║  Dokumentasi Lengkap:                                                    ║
║  - MULAI_DARI_SINI.txt  → Panduan cepat                                 ║
║  - README.md            → Cara install & fitur                           ║
║  - PANDUAN_LENGKAP.md   → Tutorial step-by-step                          ║
║  - PENJELASAN_KODE.md   → Cara kerja kode (untuk belajar)               ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝
-->
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PrintTrack — Inventory & Monitoring Percetakan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="css/app.css?v=<?= time() ?>" />
  <script>
    // Pass PHP variables ke JavaScript
    window.APP_CONFIG = {
      userRole: '<?= $userRole ?>',
      userId: <?= $userInfo['id'] ?>,
      userName: '<?= addslashes($userInfo['name']) ?>',
      userEmail: '<?= $userInfo['email'] ?>',
      permissions: {
        canView: <?= canView() ? 'true' : 'false' ?>,
        canCreate: <?= canCreate() ? 'true' : 'false' ?>,
        canEdit: <?= canEdit() ? 'true' : 'false' ?>,
        canDelete: <?= canDelete() ? 'true' : 'false' ?>,
        canExport: <?= canExport() ? 'true' : 'false' ?>
      }
    };
  </script>
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
        <div class="nav-item active" data-page="dashboard">
          <i data-feather="grid"></i> Dashboard
        </div>
        <div class="nav-item" data-page="monitoring">
          <i data-feather="activity"></i> Monitoring Realtime
          <span class="nav-badge" id="badge-active">0</span>
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Inventory</div>
        <div class="nav-item" data-page="items">
          <i data-feather="package"></i> Bahan Baku
          <span class="nav-badge" id="badge-lowstock" style="display:none">!</span>
        </div>
        <div class="nav-item" data-page="stock-mutation">
          <i data-feather="repeat"></i> Mutasi Stok
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Produksi</div>
        <div class="nav-item" onclick="window.location.href='order_baru.php'" style="cursor:pointer">
          <i data-feather="plus-circle"></i> Input Order Baru
        </div>
        <div class="nav-item" data-page="orders">
          <i data-feather="file-text"></i> Order Cetak
        </div>
        <div class="nav-item" data-page="deliveries">
          <i data-feather="truck"></i> Pengiriman
          <span class="nav-badge" id="badge-deliveries" style="display:none">0</span>
        </div>
        <div class="nav-item" data-page="machines">
          <i data-feather="cpu"></i> Mesin
        </div>
        <div class="nav-item" data-page="customers">
          <i data-feather="users"></i> Pelanggan
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Laporan</div>
        <div class="nav-item" data-page="reports">
          <i data-feather="bar-chart-2"></i> Laporan
        </div>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
          <div class="user-role"><?= ucfirst($_SESSION['user_role']) ?></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-secondary btn-sm" style="margin-top:12px;width:100%;text-align:center;text-decoration:none">
        <i data-feather="log-out"></i> Logout
      </a>
    </div>
  </aside>

  <!-- ========== MAIN ========== -->
  <div class="main-wrapper">
    <header class="header">
      <div class="header-title" id="page-title">
        Dashboard <span id="page-subtitle">Selamat datang kembali</span>
      </div>
      <div class="header-actions">
        <div class="realtime-badge">
          <div class="pulse-dot"></div>
          <span id="realtime-clock">--:--:--</span>
        </div>
        <button class="btn btn-secondary btn-icon" onclick="refreshAll()" title="Refresh">
          <i data-feather="refresh-cw"></i>
        </button>
        <button class="btn btn-primary btn-sm" id="header-action-btn" onclick="openQuickAdd()">
          <i data-feather="plus"></i> Tambah
        </button>
      </div>
    </header>

    <main class="page-content">

      <!-- ========== DASHBOARD PAGE ========== -->
      <div class="page active" id="page-dashboard">
        <!-- Stats -->
        <div class="stats-grid" id="dashboard-stats">
          <div class="stat-card" style="--accent-color:#6366f1;--icon-bg:rgba(99,102,241,0.15)">
            <div class="stat-icon"><i data-feather="package"></i></div>
            <div>
              <div class="stat-value" id="stat-items">—</div>
              <div class="stat-label">Total Bahan Baku</div>
              <div class="stat-change up" id="stat-items-sub">Memuat...</div>
            </div>
          </div>
          <div class="stat-card" style="--accent-color:#ef4444;--icon-bg:rgba(239,68,68,0.15)">
            <div class="stat-icon"><i data-feather="alert-triangle"></i></div>
            <div>
              <div class="stat-value" id="stat-lowstock">—</div>
              <div class="stat-label">Stok Hampir Habis</div>
              <div class="stat-change down" id="stat-lowstock-sub">Memuat...</div>
            </div>
          </div>
          <div class="stat-card" style="--accent-color:#06b6d4;--icon-bg:rgba(6,182,212,0.15)">
            <div class="stat-icon"><i data-feather="file-text"></i></div>
            <div>
              <div class="stat-value" id="stat-orders">—</div>
              <div class="stat-label">Order Aktif</div>
              <div class="stat-change" id="stat-orders-sub">Memuat...</div>
            </div>
          </div>
          <div class="stat-card" style="--accent-color:#10b981;--icon-bg:rgba(16,185,129,0.15)">
            <div class="stat-icon"><i data-feather="trending-up"></i></div>
            <div>
              <div class="stat-value" id="stat-revenue">—</div>
              <div class="stat-label">Pendapatan Bulan Ini</div>
              <div class="stat-change up" id="stat-revenue-sub">Memuat...</div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="grid-2 mb-6">
          <div class="card">
            <div class="card-header">
              <div>
                <div class="card-title">Order 7 Hari Terakhir</div>
                <div class="card-subtitle">Tren penerimaan order</div>
              </div>
            </div>
            <div class="chart-container" style="height:220px">
              <canvas id="chartOrders"></canvas>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <div>
                <div class="card-title">Nilai Stok per Kategori</div>
                <div class="card-subtitle">Distribusi nilai inventory</div>
              </div>
            </div>
            <div class="chart-container" style="height:220px">
              <canvas id="chartStock"></canvas>
            </div>
          </div>
        </div>

        <!-- Machine Status + Low Stock -->
        <div class="grid-2 mb-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Status Mesin</div>
              <button class="btn btn-secondary btn-sm" onclick="navigate('monitoring')">Lihat Semua</button>
            </div>
            <div id="dashboard-machines">
              <div class="skeleton" style="height:80px;margin-bottom:8px"></div>
              <div class="skeleton" style="height:80px;margin-bottom:8px"></div>
              <div class="skeleton" style="height:80px"></div>
            </div>
          </div>
          <div class="card">
            <div class="card-header">
              <div class="card-title">⚠️ Stok Kritis</div>
              <button class="btn btn-secondary btn-sm" onclick="navigate('items')">Kelola Stok</button>
            </div>
            <div id="dashboard-lowstock">
              <div class="skeleton" style="height:60px;margin-bottom:8px"></div>
              <div class="skeleton" style="height:60px;margin-bottom:8px"></div>
              <div class="skeleton" style="height:60px"></div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Order Terbaru</div>
            <button class="btn btn-secondary btn-sm" onclick="navigate('orders')">Lihat Semua</button>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>No. Order</th>
                  <th>Pekerjaan</th>
                  <th>Pelanggan</th>
                  <th>Status</th>
                  <th>Prioritas</th>
                  <th>Jatuh Tempo</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody id="dashboard-orders">
                <tr><td colspan="7"><div class="skeleton" style="height:36px"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========== MONITORING PAGE ========== -->
      <div class="page" id="page-monitoring">
        <div class="flex items-center justify-between mb-6">
          <div>
            <div style="font-size:13px;color:var(--text-muted)">Update otomatis setiap 15 detik</div>
          </div>
          <div class="tabs">
            <div class="tab-pill active" onclick="switchMonitorTab(this,'cards')">Kartu Mesin</div>
            <div class="tab-pill" onclick="switchMonitorTab(this,'kanban')">Kanban Order</div>
          </div>
        </div>

        <div id="monitor-tab-cards">
          <div class="machine-grid" id="machine-grid">
            <!-- Loaded dynamically -->
          </div>
        </div>

        <div id="monitor-tab-kanban" style="display:none">
          <div class="kanban-board" id="kanban-board">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>

      <!-- ========== ITEMS PAGE ========== -->
      <div class="page" id="page-items">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="items-search" placeholder="Cari kode atau nama bahan..." oninput="filterItems()" />
          </div>
          <select class="form-control" id="items-cat-filter" onchange="filterItems()" style="width:auto">
            <option value="">Semua Kategori</option>
          </select>
          <label class="btn btn-warning btn-sm" style="cursor:pointer">
            <input type="checkbox" id="items-lowstock-filter" onchange="filterItems()" style="display:none" />
            <i data-feather="alert-triangle"></i> Stok Kritis
          </label>
          <button class="btn btn-primary btn-sm" onclick="openAddItemModal()">
            <i data-feather="plus"></i> Tambah Item
          </button>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Item</th>
                  <th>Kategori</th>
                  <th>Stok</th>
                  <th>Min Stok</th>
                  <th>Status</th>
                  <th>Harga Beli</th>
                  <th>Lokasi</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="items-tbody">
                <tr><td colspan="9"><div class="skeleton" style="height:36px"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========== STOCK MUTATION PAGE ========== -->
      <div class="page" id="page-stock-mutation">
        <div class="grid-2 mb-6">
          <div class="card" style="border-color:rgba(16,185,129,0.3)">
            <div class="card-header">
              <div class="card-title text-success">📦 Stok Masuk</div>
            </div>
            <div class="form-group">
              <label class="form-label">Pilih Item</label>
              <select class="form-control" id="in-item-id">
                <option value="">-- Pilih Item --</option>
              </select>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Jumlah</label>
                <input type="number" class="form-control" id="in-qty" min="0.01" step="0.01" placeholder="0" />
              </div>
              <div class="form-group">
                <label class="form-label">Harga Satuan</label>
                <input type="number" class="form-control" id="in-price" min="0" placeholder="0" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Catatan</label>
              <input type="text" class="form-control" id="in-notes" placeholder="Dari mana, referensi PO, dll..." />
            </div>
            <button class="btn btn-success w-full" onclick="submitStockIn()">
              <i data-feather="arrow-down-circle"></i> Catat Stok Masuk
            </button>
          </div>
          <div class="card" style="border-color:rgba(239,68,68,0.3)">
            <div class="card-header">
              <div class="card-title text-danger">📤 Stok Keluar</div>
            </div>
            <div class="form-group">
              <label class="form-label">Pilih Item</label>
              <select class="form-control" id="out-item-id">
                <option value="">-- Pilih Item --</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Jumlah</label>
              <input type="number" class="form-control" id="out-qty" min="0.01" step="0.01" placeholder="0" />
            </div>
            <div class="form-group">
              <label class="form-label">Catatan</label>
              <input type="text" class="form-control" id="out-notes" placeholder="Untuk order, keperluan, dll..." />
            </div>
            <button class="btn btn-danger w-full" onclick="submitStockOut()">
              <i data-feather="arrow-up-circle"></i> Catat Stok Keluar
            </button>
          </div>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-title">Riwayat Mutasi Stok</div>
            <div class="flex gap-2">
              <select class="form-control" id="mutation-item-filter" onchange="loadMutations()" style="width:200px">
                <option value="">Semua Item</option>
              </select>
            </div>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Item</th>
                  <th>Tipe</th>
                  <th>Jumlah</th>
                  <th>Stok Sebelum</th>
                  <th>Stok Sesudah</th>
                  <th>Referensi</th>
                  <th>Oleh</th>
                </tr>
              </thead>
              <tbody id="mutations-tbody">
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Pilih item untuk melihat riwayat</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========== ORDERS PAGE ========== -->
      <div class="page" id="page-orders">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="orders-search" placeholder="Cari nomor order, judul, pelanggan..." oninput="filterOrders()" />
          </div>
          <select class="form-control" id="orders-status-filter" onchange="filterOrders()" style="width:auto">
            <option value="">Semua Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Dikonfirmasi</option>
            <option value="in_progress">Proses</option>
            <option value="quality_check">QC</option>
            <option value="completed">Selesai</option>
            <option value="cancelled">Dibatalkan</option>
          </select>
          <a href="order_baru.php" class="btn btn-primary btn-sm">
            <i data-feather="plus-circle"></i> Input Order Baru
          </a>
        </div>
        <div id="orders-list">
          <div class="skeleton" style="height:120px;margin-bottom:12px;border-radius:12px"></div>
          <div class="skeleton" style="height:120px;margin-bottom:12px;border-radius:12px"></div>
          <div class="skeleton" style="height:120px;border-radius:12px"></div>
        </div>
      </div>

      <!-- ========== MACHINES PAGE ========== -->
      <div class="page" id="page-machines">
        <div class="machine-grid" id="machines-page-grid"></div>
      </div>

      <!-- ========== SUPPLIERS PAGE ========== -->
      <div class="page" id="page-suppliers" style="display:none"></div>

      <!-- ========== CUSTOMERS PAGE ========== -->
      <div class="page" id="page-customers">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="customers-search" placeholder="Cari pelanggan..." oninput="filterCustomers()" />
          </div>
        </div>
        <div id="customers-list">
          <div class="skeleton" style="height:80px;margin-bottom:10px;border-radius:12px"></div>
          <div class="skeleton" style="height:80px;margin-bottom:10px;border-radius:12px"></div>
          <div class="skeleton" style="height:80px;border-radius:12px"></div>
        </div>

        <!-- Panel riwayat order pelanggan -->
        <div id="customer-history-panel" style="display:none;margin-top:24px">
          <div class="card">
            <div class="card-header">
              <div>
                <div class="card-title" id="history-panel-name">Riwayat Order</div>
                <div style="font-size:12px;color:var(--text-muted)" id="history-panel-phone"></div>
              </div>
              <button class="btn btn-secondary btn-sm" onclick="tutupHistory()">
                <i data-feather="x"></i> Tutup
              </button>
            </div>
            <div class="table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th>No. Order</th>
                    <th>Pesanan</th>
                    <th>Status</th>
                    <th>Pengiriman</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                  </tr>
                </thead>
                <tbody id="history-tbody">
                  <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Memuat...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== DELIVERIES PAGE ========== -->
      <div class="page" id="page-deliveries">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="deliveries-search" placeholder="Cari no. order, pelanggan, kota tujuan..." oninput="filterDeliveries()" />
          </div>
          <select class="form-control" id="deliveries-status-filter" onchange="filterDeliveries()" style="width:auto">
            <option value="">Semua Status</option>
            <option value="prepared">Disiapkan</option>
            <option value="shipping">Dalam Pengiriman</option>
            <option value="arrived">Tiba di Tujuan</option>
            <option value="received">Diterima</option>
          </select>
          <a href="track.php" target="_blank" class="btn btn-secondary btn-sm">
            <i data-feather="external-link"></i> Halaman Tracking
          </a>
        </div>
        <div id="deliveries-list">
          <div class="skeleton" style="height:140px;margin-bottom:12px;border-radius:12px"></div>
          <div class="skeleton" style="height:140px;margin-bottom:12px;border-radius:12px"></div>
          <div class="skeleton" style="height:140px;border-radius:12px"></div>
        </div>
      </div>

      <!-- ========== REPORTS PAGE ========== -->
      <div class="page" id="page-reports">
        <div class="tabs mb-6">
          <div class="tab-pill active" onclick="switchReportTab(this,'stock')">Laporan Stok</div>
          <div class="tab-pill" onclick="switchReportTab(this,'transactions')">Mutasi Stok</div>
          <div class="tab-pill" onclick="switchReportTab(this,'orders')">Laporan Order</div>
        </div>

        <!-- Date filter -->
        <div class="filter-bar mb-4">
          <div class="form-group" style="margin:0">
            <input type="date" class="form-control" id="report-from" />
          </div>
          <span style="color:var(--text-muted)">s/d</span>
          <div class="form-group" style="margin:0">
            <input type="date" class="form-control" id="report-to" />
          </div>
          <button class="btn btn-primary btn-sm" onclick="loadReport()">
            <i data-feather="filter"></i> Tampilkan
          </button>
          <button class="btn btn-secondary btn-sm" onclick="exportReport()">
            <i data-feather="download"></i> Export CSV
          </button>
        </div>

        <div class="card">
          <div class="table-wrapper" id="report-table-wrapper">
            <div class="empty-state">
              <i data-feather="bar-chart-2"></i>
              <h3>Pilih Jenis Laporan</h3>
              <p>Gunakan tab di atas untuk memilih laporan yang diinginkan</p>
            </div>
          </div>
        </div>
      </div>

    </main>
  </div><!-- /.main-wrapper -->
</div><!-- /.app-layout -->

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Add/Edit Item Modal -->
<div class="modal-overlay" id="modal-add-item">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-item-title">Tambah Bahan Baku</div>
      <button class="modal-close" onclick="closeModal('modal-add-item')"><i data-feather="x"></i></button>
    </div>
    <form onsubmit="submitAddItem(event)">
      <input type="hidden" id="item-id" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kode Item *</label>
          <input class="form-control" id="item-code" required placeholder="Contoh: ITM013" />
        </div>
        <div class="form-group">
          <label class="form-label">Nama Item *</label>
          <input class="form-control" id="item-name" required placeholder="Nama bahan baku" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori *</label>
          <select class="form-control" id="item-category" required></select>
        </div>
        <div class="form-group">
          <label class="form-label">Satuan *</label>
          <select class="form-control" id="item-unit" required></select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Lokasi Penyimpanan</label>
          <input class="form-control" id="item-location" placeholder="Contoh: Gudang A-1" />
        </div>
        <div class="form-group">
          <label class="form-label">Stok Awal</label>
          <input type="number" class="form-control" id="item-stock" value="0" min="0" step="0.01" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Stok Minimum *</label>
          <input type="number" class="form-control" id="item-min-stock" value="0" min="0" step="0.01" required />
        </div>
        <div class="form-group">
          <label class="form-label">Harga Beli (Rp)</label>
          <input type="number" class="form-control" id="item-buy-price" value="0" min="0" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Deskripsi</label>
        <textarea class="form-control" id="item-desc" placeholder="Keterangan tambahan..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-item')">Batal</button>
        <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Buat Pengiriman Baru (dari order selesai) -->
<div class="modal-overlay" id="modal-new-delivery">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">🚚 Buat Data Pengiriman</div>
      <button class="modal-close" onclick="closeModal('modal-new-delivery')"><i data-feather="x"></i></button>
    </div>
    <input type="hidden" id="new-delivery-order-id" />
    <div id="new-delivery-order-info" style="background:var(--bg-base);border-radius:var(--radius-sm);padding:12px;margin-bottom:18px;font-size:13px;"></div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nama Penerima *</label>
        <input class="form-control" id="nd-recipient-name" required placeholder="Nama penerima" />
      </div>
      <div class="form-group">
        <label class="form-label">No. Telepon Penerima</label>
        <input class="form-control" id="nd-recipient-phone" placeholder="08xx-xxxx-xxxx" />
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Kota Tujuan *</label>
        <input class="form-control" id="nd-city" required placeholder="Jakarta" />
      </div>
      <div class="form-group">
        <label class="form-label">Estimasi Tiba</label>
        <input type="date" class="form-control" id="nd-eta" />
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Alamat Lengkap Tujuan *</label>
      <textarea class="form-control" id="nd-address" rows="2" required placeholder="Jl. Merdeka No. 10..."></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Catatan</label>
      <textarea class="form-control" id="nd-notes" rows="2" placeholder="Instruksi pengiriman..."></textarea>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-delivery')">Nanti</button>
      <button class="btn btn-primary" onclick="submitNewDelivery()"><i data-feather="truck"></i> Buat Pengiriman</button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Lightbox untuk foto bukti -->
<div id="lightbox" onclick="this.classList.remove('open')">
  <img id="lightbox-img" src="" alt="Bukti Pengiriman" />
</div>

<script src="js/app.js?v=<?= time() ?>"></script>
</body>
</html>
