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
║  SISTEM INVENTORY & MONITORING PERCETAKAN                            ║
║                                                                          ║
║  Deskripsi:                                                              ║
║  Website untuk mengelola inventory bahan baku dan monitoring produksi    ║
║  percetakan secara realtime.                                             ║
║                                                                          ║
║  Fitur Utama:                                                            ║
║  ✓ Dashboard dengan statistik & chart                                   ║
║  ✓ Tracking realtime progress order (kanban)                            ║
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
  <title>Ranum Indocraft — Inventory & Monitoring Percetakan</title>
  <link rel="icon" type="image/png" href="logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/feather-icons"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/app.css?v=<?= time() ?>" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <style>
    /* Date picker icon white */
    input[type="date"] {
      color-scheme: dark;
      color: var(--text-primary);
    }
    input[type="date"]::-webkit-calendar-picker-indicator {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: center;
      background-size: 16px;
      width: 20px;
      height: 20px;
      opacity: 0.8;
      cursor: pointer;
    }
    input[type="date"]::-webkit-calendar-picker-indicator:hover {
      opacity: 1;
    }
  </style>
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
      <div class="logo-icon"><img src="logo.png" alt="Logo" style="width:44px;height:44px;object-fit:contain;border-radius:6px"></div>
      <div class="logo-text">
        Ranum Indocraft
        <span>Inventory & Monitoring</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <div class="nav-group-label">Utama</div>
        <div class="nav-item active" data-page="dashboard" onclick="navigate('dashboard')">
          <i data-feather="grid"></i> Dashboard
        </div>
        <div class="nav-item" data-page="monitoring" onclick="navigate('monitoring')">
          <i data-feather="radio"></i> MES Monitoring
          <span class="nav-badge" id="badge-active">0</span>
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Inventory</div>
        <div class="nav-item" data-page="items" onclick="navigate('items')">
          <i data-feather="package"></i> Bahan Baku
          <span class="nav-badge" id="badge-lowstock" style="display:none">!</span>
        </div>
        <div class="nav-item" data-page="stock-mutation" onclick="navigate('stock-mutation')">
          <i data-feather="repeat"></i> Mutasi Stok
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Produksi</div>
        <div class="nav-item" data-page="order-input" onclick="navigate('order-input')">
          <i data-feather="plus-circle"></i> Input Order Baru
        </div>
        <div class="nav-item" data-page="orders" onclick="navigate('orders')">
          <i data-feather="file-text"></i> Order Cetak
        </div>
        <div class="nav-item" data-page="deliveries" onclick="navigate('deliveries')">
          <i data-feather="truck"></i> Pengiriman
          <span class="nav-badge" id="badge-deliveries" style="display:none">0</span>
        </div>
        <div class="nav-item" data-page="customers" onclick="navigate('customers')">
          <i data-feather="users"></i> Pelanggan
        </div>
        <div class="nav-item" data-page="products" onclick="navigate('products')">
          <i data-feather="tag"></i> Produk & Harga
        </div>
      </div>

      <div class="nav-group">
        <div class="nav-group-label">Laporan</div>
        <div class="nav-item" data-page="reports" onclick="navigate('reports')">
          <i data-feather="bar-chart-2"></i> Laporan
        </div>
      </div>

      <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
      <div class="nav-group">
        <div class="nav-group-label">Admin</div>
        <div class="nav-item" data-page="manage-users" onclick="navigate('manage-users')">
          <i data-feather="user-check"></i> Kelola User
        </div>
      </div>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
          <div class="user-role"><?= ucfirst($_SESSION['user_role']) ?></div>
        </div>
      </div>
      <button onclick="openModal('modal-confirm-logout');feather.replace()" class="btn btn-secondary btn-sm" style="margin-top:12px;width:100%;text-align:center">
        <i data-feather="log-out"></i> Logout
      </button>
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

        <!-- Chart Throughput MES -->
        <div class="card mb-6">
          <div class="card-header">
            <div>
              <div class="card-title">
                <i class="bi bi-graph-up-arrow" style="color:var(--success);margin-right:6px"></i>
                Throughput Produksi — 14 Hari Terakhir
              </div>
              <div class="card-subtitle">Jumlah order yang diselesaikan per hari (MES KPI)</div>
            </div>
          </div>
          <div class="chart-container" style="height:200px">
            <canvas id="chartThroughput"></canvas>
          </div>
        </div>

        <!-- Stok Kritis -->
        <div class="card mb-6">
          <div class="card-header">
            <div class="card-title"><i class="bi bi-exclamation-triangle-fill" style="color:var(--warning)"></i> Stok Kritis</div>
            <button class="btn btn-secondary btn-sm" onclick="navigate('items')">Kelola Stok</button>
          </div>
          <div id="dashboard-lowstock">
            <div class="skeleton" style="height:60px;margin-bottom:8px"></div>
            <div class="skeleton" style="height:60px;margin-bottom:8px"></div>
            <div class="skeleton" style="height:60px"></div>
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

      <!-- ========== TRACKING REALTIME / MES MONITORING PAGE ========== -->
      <div class="page" id="page-monitoring">
        <div id="kanban-board">
          <!-- Skeleton loading awal -->
          <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
            <div class="skeleton" style="flex:1;min-width:120px;height:80px;border-radius:10px"></div>
            <div class="skeleton" style="flex:1;min-width:120px;height:80px;border-radius:10px"></div>
            <div class="skeleton" style="flex:1;min-width:120px;height:80px;border-radius:10px"></div>
            <div class="skeleton" style="flex:1;min-width:120px;height:80px;border-radius:10px"></div>
          </div>
          <div class="skeleton" style="height:220px;border-radius:var(--radius);margin-bottom:24px"></div>
          <div style="display:flex;gap:16px">
            <div class="skeleton" style="flex:0 0 230px;height:280px;border-radius:var(--radius)"></div>
            <div class="skeleton" style="flex:0 0 230px;height:280px;border-radius:var(--radius)"></div>
            <div class="skeleton" style="flex:0 0 230px;height:280px;border-radius:var(--radius)"></div>
            <div class="skeleton" style="flex:0 0 230px;height:280px;border-radius:var(--radius)"></div>
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
              <div class="card-title text-success"><i class="bi bi-box-arrow-in-down" style="color:var(--success)"></i> Stok Masuk</div>
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
              <div class="card-title text-danger"><i class="bi bi-box-arrow-up" style="color:var(--danger)"></i> Stok Keluar</div>
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
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:24px">Memuat riwayat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========== ORDER INPUT PAGE ========== -->
      <div class="page" id="page-order-input">
        <!-- Two-column: form kiri, nota kanan -->
        <div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start" id="order-input-grid">

          <!-- KOLOM KIRI: FORM -->
          <div>
            <!-- Data Pelanggan -->
            <div class="card" style="margin-bottom:20px">
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <i data-feather="user"></i> Data Pelanggan
              </div>
              <div class="form-group" style="position:relative">
                <label class="form-label">Cari Pelanggan Lama (opsional)</label>
                <div class="search-box" style="margin-bottom:0">
                  <i data-feather="search"></i>
                  <input type="text" id="ni-cust-search" placeholder="Ketik nama atau no. HP..."
                    oninput="niSearchCustomer(this.value)" autocomplete="off" />
                </div>
                <div id="ni-cust-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:200;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);max-height:200px;overflow-y:auto;box-shadow:var(--shadow);margin-top:4px"></div>
              </div>
              <div class="divider"></div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Nama Pelanggan *</label>
                  <input class="form-control" id="ni-cust-name" placeholder="Budi Santoso" />
                </div>
                <div class="form-group">
                  <label class="form-label">No. HP *</label>
                  <input class="form-control" id="ni-cust-phone" placeholder="0812-3456-7890"
                    oninput="niCekDuplikatHP(this.value)" />
                  <div id="ni-phone-warning" style="display:none;margin-top:6px;padding:8px 12px;
                    background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.3);
                    border-radius:8px;font-size:12px;color:#fbbf24;align-items:center;gap:8px">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="ni-phone-warning-text"></span>
                  </div>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Kota</label>
                  <input class="form-control" id="ni-cust-city" placeholder="Jakarta" />
                </div>
                <div class="form-group">
                  <label class="form-label">Alamat</label>
                  <input class="form-control" id="ni-cust-address" placeholder="Jl. Merdeka No. 1..." />
                </div>
              </div>
            </div>

            <!-- Detail Pesanan -->
            <div class="card" style="margin-bottom:20px">
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <i data-feather="shopping-cart"></i> Item Pesanan
              </div>

              <!-- Baris tambah item: dropdown + tombol -->
              <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px">
                <div style="flex:1">
                  <label class="form-label">Pilih Produk</label>
                  <select class="form-control" id="ni-product-select">
                    <option value="">-- Pilih produk --</option>
                  </select>
                </div>
                <button class="btn btn-primary btn-sm" onclick="niTambahItem()"
                  style="padding:9px 14px;white-space:nowrap;flex-shrink:0">
                  <i data-feather="plus"></i> Tambah
                </button>
              </div>

              <!-- Atau input manual -->
              <div style="font-size:11px;color:var(--text-muted);margin-bottom:12px">
                Tidak ada di daftar?
                <button onclick="niTambahManual()" style="background:none;border:none;color:var(--primary-light);font-size:11px;cursor:pointer;text-decoration:underline;padding:0">
                  Tambah item manual
                </button>
              </div>

              <!-- Tabel item pesanan -->
              <div id="ni-items-empty" style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;border:1px dashed var(--border);border-radius:var(--radius-sm);margin-bottom:12px">
                <i data-feather="inbox" style="width:28px;height:28px;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto"></i>
                Belum ada item. Pilih produk lalu klik "+ Tambah".
              </div>

              <div id="ni-items-table-wrap" style="display:none;margin-bottom:12px">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                  <thead>
                    <tr style="border-bottom:1px solid var(--border)">
                      <th style="padding:8px 6px;text-align:left;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase">#</th>
                      <th style="padding:8px 6px;text-align:left;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase">Produk / Keterangan</th>
                      <th style="padding:8px 6px;text-align:center;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase;width:70px">Qty</th>
                      <th style="padding:8px 6px;text-align:right;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase;width:120px">Harga</th>
                      <th style="padding:8px 6px;text-align:right;color:var(--text-muted);font-size:11px;font-weight:600;text-transform:uppercase;width:110px">Subtotal</th>
                      <th style="width:36px"></th>
                    </tr>
                  </thead>
                  <tbody id="ni-items-tbody"></tbody>
                </table>
              </div>

              <!-- Kalkulasi total -->
              <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:14px">
                <div class="flex justify-between mb-4" style="font-size:13px">
                  <span style="color:var(--text-muted)">Subtotal</span>
                  <span id="ni-c-subtotal" style="font-weight:600">Rp 0</span>
                </div>
                <div class="divider"></div>
                <div class="flex justify-between">
                  <span style="font-weight:700;font-size:15px">TOTAL</span>
                  <span id="ni-c-total" style="font-weight:800;font-size:20px;color:var(--accent)">Rp 0</span>
                </div>
              </div>
            </div>

            <!-- Produksi -->
            <div class="card" style="margin-bottom:20px">
              <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
                <i data-feather="settings"></i> Produksi
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Prioritas</label>
                  <select class="form-control" id="ni-priority">
                    <option value="low">Rendah</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">Tinggi</option>
                    <option value="urgent">Urgent</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Jatuh Tempo *</label>
                  <input type="date" class="form-control" id="ni-due" />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Operator</label>
                <select class="form-control" id="ni-operator">
                  <option value="">-- Pilih Operator --</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea class="form-control" id="ni-notes" rows="2" placeholder="Instruksi khusus..."></textarea>
              </div>
            </div>

            <div style="display:flex;gap:12px">
              <button class="btn btn-primary" style="flex:1;justify-content:center;padding:12px"
                onclick="niSimpanOrder()" id="ni-btn-simpan">
                <i data-feather="save"></i> Simpan & Tampilkan Nota
              </button>
              <button class="btn btn-secondary" onclick="niResetForm()" style="padding:12px 20px">
                <i data-feather="refresh-cw"></i> Reset
              </button>
            </div>
          </div>

          <!-- KOLOM KANAN: NOTA -->
          <div id="ni-nota-wrapper">
            <div id="ni-nota-placeholder" class="card" style="text-align:center;padding:48px 24px;position:sticky;top:90px">
              <i data-feather="printer" style="width:48px;height:48px;stroke:var(--text-muted);margin-bottom:16px"></i>
              <div style="font-size:15px;font-weight:600;color:var(--text-secondary);margin-bottom:8px">Nota Belum Tersedia</div>
              <div style="font-size:13px;color:var(--text-muted)">Isi form & klik "Simpan" untuk generate nota otomatis</div>
            </div>

            <div id="ni-nota-content" style="display:none;position:sticky;top:90px">
              <div class="card" id="ni-nota-print" style="padding:24px;font-size:13px">
                <div style="text-align:center;border-bottom:2px solid var(--border);padding-bottom:14px;margin-bottom:14px">
                <div style="font-size:18px;font-weight:800;color:var(--primary)"><img src="logo.png" alt="Logo" style="width:22px;height:22px;object-fit:contain;vertical-align:middle;margin-right:6px;border-radius:4px"> Ranum Indocraft</div>
                  <div style="font-size:11px;color:var(--text-muted)">Sistem Inventory & Monitoring Percetakan</div>
                </div>
                <div style="text-align:center;margin-bottom:16px">
                  <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px">NOTA PESANAN</div>
                  <div id="ni-nota-num" style="font-size:24px;font-weight:800;font-family:monospace;color:var(--accent);margin-top:4px"></div>
                  <div id="ni-nota-tgl" style="font-size:11px;color:var(--text-muted);margin-top:2px"></div>
                </div>
                <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px">
                  <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">Data Pelanggan</div>
                  <table style="width:100%;font-size:12px;border-collapse:collapse">
                    <tr><td style="color:var(--text-muted);width:80px;padding:2px 0">Nama</td><td style="font-weight:600">: <span id="ni-nota-cust-name"></span></td></tr>
                    <tr><td style="color:var(--text-muted);padding:2px 0">No. HP</td><td>: <span id="ni-nota-cust-phone"></span></td></tr>
                    <tr><td style="color:var(--text-muted);padding:2px 0">Kota</td><td>: <span id="ni-nota-cust-city"></span></td></tr>
                  </table>
                </div>
                <div style="margin-bottom:12px">
                  <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px">Detail Pesanan</div>
                  <table style="width:100%;font-size:12px;border-collapse:collapse">
                    <tr><td style="color:var(--text-muted);width:80px;padding:2px 0">Pesanan</td><td style="font-weight:600">: <span id="ni-nota-title"></span></td></tr>
                    <tr><td style="color:var(--text-muted);padding:2px 0">Jumlah</td><td>: <span id="ni-nota-qty"></span></td></tr>
                    <tr><td style="color:var(--text-muted);padding:2px 0">Harga/pcs</td><td>: <span id="ni-nota-price"></span></td></tr>
                    <tr id="ni-nota-items-row" style="display:none">
                      <td colspan="2" style="padding-top:6px">
                        <div id="ni-nota-items-list"></div>
                      </td>
                    </tr>
                  </table>
                </div>
                <div style="border-top:1px dashed var(--border);padding-top:10px;margin-bottom:12px">
                  <div class="flex justify-between" style="font-size:15px;font-weight:800;border-top:2px solid var(--border);padding-top:8px">
                    <span>TOTAL</span><span id="ni-nota-total" style="color:var(--success)"></span>
                  </div>
                </div>
                <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:10px;margin-bottom:12px;font-size:11px">
                  <div class="flex justify-between" style="margin-bottom:3px"><span style="color:var(--text-muted)">Jatuh Tempo</span><span id="ni-nota-due" style="font-weight:600"></span></div>
                  <div class="flex justify-between"><span style="color:var(--text-muted)">Prioritas</span><span id="ni-nota-priority"></span></div>
                </div>
                <div style="border:1px dashed rgba(99,102,241,0.4);border-radius:var(--radius-sm);padding:10px;text-align:center;margin-bottom:16px">
                  <div style="font-size:10px;color:var(--text-muted);margin-bottom:3px">Cek status pesanan di:</div>
                  <div style="font-size:11px;font-weight:600;color:var(--accent)">localhost/inventory_monitoring/track.php</div>
                  <div style="font-size:10px;color:var(--text-muted);margin-top:3px">Nomor Order:</div>
                  <div id="ni-nota-num2" style="font-size:15px;font-weight:800;font-family:monospace;color:var(--primary);margin-top:2px"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:11px">
                  <div style="text-align:center">
                    <div style="color:var(--text-muted);margin-bottom:30px">Kasir</div>
                    <div style="border-top:1px solid var(--border);padding-top:4px;min-width:90px">(<?= htmlspecialchars($_SESSION['user_name']) ?>)</div>
                  </div>
                  <div style="text-align:center">
                    <div style="color:var(--text-muted);margin-bottom:30px">Pelanggan</div>
                    <div style="border-top:1px solid var(--border);padding-top:4px;min-width:90px">(________________)</div>
                  </div>
                </div>
              </div>
              <div style="display:flex;gap:10px;margin-top:12px">
                <button class="btn btn-primary" style="flex:1;justify-content:center" onclick="cetakNota()">
                  <i data-feather="printer"></i> Cetak Nota
                </button>
                <button class="btn btn-secondary" onclick="niResetForm()" style="flex:1;justify-content:center">
                  <i data-feather="plus"></i> Order Baru
                </button>
              </div>
            </div>
          </div>

        </div><!-- /order-input-grid -->
      </div>

      <!-- ========== ORDERS PAGE ========== -->
      <div class="page" id="page-orders">
        <!-- Tab Aktif / Riwayat -->
        <div class="filter-bar" style="margin-bottom:16px">
          <div class="tabs">
            <div class="tab-pill active" id="orders-tab-active" onclick="switchOrdersTab('active')">
              <i data-feather="activity" style="width:13px;height:13px"></i> Aktif
              <span class="nav-badge" id="badge-orders-active" style="margin-left:4px;background:rgba(99,102,241,0.3);color:#a5b4fc">0</span>
            </div>
            <div class="tab-pill" id="orders-tab-history" onclick="switchOrdersTab('history')">
              <i data-feather="archive" style="width:13px;height:13px"></i> Riwayat
            </div>
          </div>
          <div class="search-box" style="flex:1;min-width:200px">
            <i data-feather="search"></i>
            <input type="text" id="orders-search" placeholder="Cari nomor order, judul, pelanggan..." oninput="filterOrders()" />
          </div>
          <button class="btn btn-primary btn-sm" onclick="navigate('order-input')">
            <i data-feather="plus-circle"></i> Input Order Baru
          </button>
        </div>

        <!-- Tab content: Aktif -->
        <div id="orders-tab-content-active">
          <div id="orders-list">
            <div class="skeleton" style="height:120px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:120px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:120px;border-radius:12px"></div>
          </div>
        </div>

        <!-- Tab content: Riwayat -->
        <div id="orders-tab-content-history" style="display:none">
          <div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap">
            <select class="form-control" id="orders-history-filter" onchange="loadOrdersHistory()" style="width:auto">
              <option value="completed">Selesai</option>
              <option value="cancelled">Dibatalkan</option>
              <option value="">Semua Riwayat</option>
            </select>
          </div>
          <div id="orders-history-list">
            <div class="skeleton" style="height:100px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:100px;border-radius:12px"></div>
          </div>
        </div>
      </div>

      <!-- ========== CUSTOMERS PAGE ========== -->
      <div class="page" id="page-customers">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="customers-search" placeholder="Cari pelanggan..." oninput="filterCustomers()" />
          </div>
          <button class="btn btn-primary btn-sm" onclick="openAddCustomerModal()">
            <i data-feather="plus"></i> Tambah Pelanggan
          </button>
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
            <!-- Ringkasan statistik pelanggan -->
            <div id="history-stats" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:0 0 16px 0;margin-bottom:16px;border-bottom:1px solid var(--border)"></div>
            <div class="table-wrapper">
              <table class="table-compact">
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

      <!-- ========== PRODUCTS PAGE ========== -->
      <div class="page" id="page-products">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="products-search" placeholder="Cari nama atau kode produk..." oninput="filterProducts()" />
          </div>
          <button class="btn btn-primary btn-sm" onclick="openAddProductModal()" id="btn-add-product">
            <i data-feather="plus"></i> Tambah Produk
          </button>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Kode</th>
                  <th>Nama Produk</th>
                  <th>Kategori</th>
                  <th>Satuan</th>
                  <th>Harga Default</th>
                  <th>Keterangan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="products-tbody">
                <tr><td colspan="7"><div class="skeleton" style="height:36px"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ========== DELIVERIES PAGE ========== -->
      <div class="page" id="page-deliveries">
        <div class="filter-bar" style="margin-bottom:16px">
          <div class="tabs">
            <div class="tab-pill active" id="deliveries-tab-active" onclick="switchDeliveriesTab('active')">
              <i data-feather="truck" style="width:13px;height:13px"></i> Aktif
              <span class="nav-badge" id="badge-deliveries-active" style="margin-left:4px;background:rgba(6,182,212,0.3);color:#22d3ee">0</span>
            </div>
            <div class="tab-pill" id="deliveries-tab-history" onclick="switchDeliveriesTab('history')">
              <i data-feather="archive" style="width:13px;height:13px"></i> Riwayat
            </div>
          </div>
          <div class="search-box" style="flex:1;min-width:200px">
            <i data-feather="search"></i>
            <input type="text" id="deliveries-search" placeholder="Cari no. order, pelanggan, kota tujuan..." oninput="filterDeliveries()" />
          </div>
          <a href="track.php" target="_blank" class="btn btn-secondary btn-sm">
            <i data-feather="external-link"></i> Halaman Tracking
          </a>
        </div>

        <!-- Tab: Aktif -->
        <div id="deliveries-tab-content-active">
          <div id="deliveries-list">
            <div class="skeleton" style="height:140px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:140px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:140px;border-radius:12px"></div>
          </div>
        </div>

        <!-- Tab: Riwayat -->
        <div id="deliveries-tab-content-history" style="display:none">
          <div id="deliveries-history-list">
            <div class="skeleton" style="height:100px;margin-bottom:12px;border-radius:12px"></div>
            <div class="skeleton" style="height:100px;border-radius:12px"></div>
          </div>
        </div>
      </div>

      <!-- ========== REPORTS PAGE ========== -->
      <div class="page" id="page-reports">
        <div class="tabs mb-6">
          <div class="tab-pill active" onclick="switchReportTab(this,'stock')">Laporan Stok</div>
          <div class="tab-pill" onclick="switchReportTab(this,'transactions')">Mutasi Stok</div>
          <div class="tab-pill" onclick="switchReportTab(this,'orders')">Laporan Order</div>
          <div class="tab-pill" onclick="switchReportTab(this,'deliveries')">Pengiriman</div>
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
          <button class="btn btn-secondary btn-sm" onclick="exportReport('csv')">
            <i data-feather="download"></i> Export CSV
          </button>
          <button class="btn btn-danger btn-sm" onclick="exportReport('pdf')">
            <i data-feather="file-text"></i> Export PDF
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

      <!-- ========== MANAGE USERS PAGE (Admin Only) ========== -->
      <div class="page" id="page-manage-users">
        <div class="filter-bar">
          <div class="search-box">
            <i data-feather="search"></i>
            <input type="text" id="users-search" placeholder="Cari nama atau email..." oninput="filterUsers()" />
          </div>
          <select class="form-control" id="users-role-filter" onchange="filterUsers()" style="width:auto">
            <option value="">Semua Role</option>
            <option value="admin">Admin</option>
            <option value="operator">Operator</option>
          </select>
          <button class="btn btn-primary btn-sm" onclick="openAddUserModal()">
            <i data-feather="user-plus"></i> Tambah User
          </button>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Terakhir Login</th>
                  <th>Terdaftar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="users-tbody">
                <tr><td colspan="7"><div class="skeleton" style="height:36px"></div></td></tr>
              </tbody>
            </table>
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

<!-- Modal: Edit Order -->
<div class="modal-overlay" id="modal-edit-order">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i data-feather="edit-2" style="width:16px;height:16px;stroke:var(--accent)"></i> Edit Order</div>
      <button class="modal-close" onclick="closeModal('modal-edit-order')"><i data-feather="x"></i></button>
    </div>
    <input type="hidden" id="eo-id" />
    <div id="eo-order-info" style="background:var(--bg-base);border-radius:var(--radius-sm);padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--text-muted)"></div>
    <div class="form-group">
      <label class="form-label">Judul / Nama Pesanan *</label>
      <input class="form-control" id="eo-title" required placeholder="Contoh: Brosur A4 2 sisi" />
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Prioritas</label>
        <select class="form-control" id="eo-priority">
          <option value="low">Rendah</option>
          <option value="normal" selected>Normal</option>
          <option value="high">Tinggi</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Operator</label>
        <select class="form-control" id="eo-operator">
          <option value="">-- Tidak Ditugaskan --</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Jumlah (Qty)</label>
        <input type="number" class="form-control" id="eo-qty" min="1" value="1" oninput="recalcEditOrder()" />
      </div>
      <div class="form-group">
        <label class="form-label">Harga Satuan (Rp)</label>
        <input type="number" class="form-control" id="eo-unit-price" min="0" value="0" oninput="recalcEditOrder()" />
      </div>
    </div>
    <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:13px;color:var(--text-muted)">Grand Total</span>
      <span id="eo-grand-total-display" style="font-size:16px;font-weight:700;color:var(--success)">Rp 0</span>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Tanggal Mulai</label>
        <input type="date" class="form-control" id="eo-start-date" />
      </div>
      <div class="form-group">
        <label class="form-label">Jatuh Tempo</label>
        <input type="date" class="form-control" id="eo-due-date" />
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Catatan</label>
      <textarea class="form-control" id="eo-notes" rows="2" placeholder="Instruksi khusus..."></textarea>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-order')">Batal</button>
      <button class="btn btn-primary" onclick="submitEditOrder()"><i data-feather="save"></i> Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- Modal: Edit Pengiriman -->
<div class="modal-overlay" id="modal-edit-delivery">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i data-feather="edit-2" style="width:16px;height:16px;stroke:var(--accent)"></i> Edit Data Pengiriman</div>
      <button class="modal-close" onclick="closeModal('modal-edit-delivery')"><i data-feather="x"></i></button>
    </div>
    <input type="hidden" id="ed-id" />
    <div id="ed-delivery-info" style="background:var(--bg-base);border-radius:var(--radius-sm);padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--text-muted)"></div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nama Penerima *</label>
        <input class="form-control" id="ed-recipient-name" required placeholder="Nama penerima" />
      </div>
      <div class="form-group">
        <label class="form-label">No. Telepon Penerima</label>
        <input class="form-control" id="ed-recipient-phone" placeholder="08xx-xxxx-xxxx" />
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Kota Tujuan *</label>
        <input class="form-control" id="ed-city" required placeholder="Jakarta" />
      </div>
      <div class="form-group">
        <label class="form-label">Estimasi Tiba</label>
        <input type="date" class="form-control" id="ed-eta" />
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Alamat Lengkap Tujuan</label>
      <textarea class="form-control" id="ed-address" rows="2" placeholder="Jl. Merdeka No. 10..."></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Catatan</label>
      <textarea class="form-control" id="ed-notes" rows="2" placeholder="Instruksi pengiriman..."></textarea>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-delivery')">Batal</button>
      <button class="btn btn-primary" onclick="submitEditDelivery()"><i data-feather="save"></i> Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- Modal: Buat Pengiriman Baru (dari order selesai) -->
<div class="modal-overlay" id="modal-new-delivery">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="bi bi-truck" style="color:var(--accent)"></i> Buat Data Pengiriman</div>
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

<!-- Add/Edit Product Modal -->
<div class="modal-overlay" id="modal-add-product">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-product-title">Tambah Produk</div>
      <button class="modal-close" onclick="closeModal('modal-add-product')"><i data-feather="x"></i></button>
    </div>
    <form onsubmit="submitProduct(event)">
      <input type="hidden" id="product-id" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Produk *</label>
          <input class="form-control" id="product-name" required placeholder="Cetak Spanduk" />
        </div>
        <div class="form-group">
          <label class="form-label">Harga Default (Rp) *</label>
          <input type="number" class="form-control" id="product-price" required min="0" placeholder="35000" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategori</label>
          <select class="form-control" id="product-category">
            <option value="">-- Pilih Kategori --</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Satuan</label>
          <select class="form-control" id="product-unit">
            <option value="">-- Pilih Satuan --</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <textarea class="form-control" id="product-desc" rows="2"
          placeholder="Ukuran, bahan, spesifikasi standar..."></textarea>
      </div>
      <div class="form-group" id="product-active-wrap" style="display:none">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" id="product-active" checked style="width:16px;height:16px;accent-color:var(--primary)" />
          <span class="form-label" style="margin:0">Produk Aktif</span>
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-product')">Batal</button>
        <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add/Edit User -->
<div class="modal-overlay" id="modal-bom">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div>
        <div class="modal-title"><i data-feather="layers"></i> Kelola Bahan Baku Produk</div>
        <div id="bom-product-name" style="font-size:12px;color:var(--text-muted);margin-top:4px"></div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-bom')"><i data-feather="x"></i></button>
    </div>
    <input type="hidden" id="bom-product-id">
    <div style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:var(--radius-sm);padding:12px 14px;margin-bottom:16px;font-size:12px;color:var(--text-secondary)">
      <i data-feather="info" style="width:14px;height:14px;vertical-align:middle;margin-right:6px"></i>
      Isi jumlah bahan baku yang dipakai <strong>per 1 pcs</strong> produk ini.
      Stok akan otomatis berkurang saat order dibuat.
    </div>
    <div id="bom-list" style="margin-bottom:16px">
      <!-- Diisi JS -->
    </div>
    <button class="btn btn-secondary btn-sm" onclick="bomTambahBaris()" style="margin-bottom:16px">
      <i data-feather="plus"></i> Tambah Bahan
    </button>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('modal-bom')">Batal</button>
      <button class="btn btn-primary" onclick="simpanBOM()"><i data-feather="save"></i> Simpan BOM</button>
    </div>
  </div>
</div>

<!-- Modal Add/Edit User -->
<div class="modal-overlay" id="modal-add-user">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-user-title">Tambah User</div>
      <button class="modal-close" onclick="closeModal('modal-add-user')"><i data-feather="x"></i></button>
    </div>
    <form onsubmit="submitAddUser(event)">
      <input type="hidden" id="user-id" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Lengkap *</label>
          <input class="form-control" id="user-name" required placeholder="Budi Santoso" />
        </div>
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" class="form-control" id="user-email" required placeholder="budi@percetakan.com" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select class="form-control" id="user-role" required>
            <option value="operator">Operator</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="user-pass-label">Password *</label>
          <input type="password" class="form-control" id="user-password" placeholder="Min. 6 karakter" autocomplete="new-password" />
          <div id="user-pass-hint" style="font-size:11px;color:var(--text-muted);margin-top:4px;display:none">
            Kosongkan jika tidak ingin mengubah password
          </div>
        </div>
      </div>
      <div id="user-status-wrap" style="display:none">
        <div class="form-group">
          <label class="form-label">Status Akun</label>
          <select class="form-control" id="user-is-active">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </div>
      </div>
      <div style="background:var(--bg-base);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px;font-size:12px;color:var(--text-muted)">
        <strong style="color:var(--text-secondary)">Info Hak Akses:</strong>
        <div style="margin-top:6px;display:grid;gap:4px">
          <div><i class="bi bi-shield-fill-check" style="color:#a5b4fc"></i> <strong>Admin</strong> — Akses penuh termasuk kelola user</div>
          <div><i class="bi bi-gear-fill" style="color:#22d3ee"></i> <strong>Operator</strong> — Bisa tambah &amp; edit data, tidak bisa hapus</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-user')">Batal</button>
        <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Item -->
<div class="modal-overlay" id="modal-confirm-delete">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="trash-2" style="width:28px;height:28px;stroke:#ef4444"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Hapus Item?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Item <strong id="confirm-delete-name" style="color:var(--text-primary)"></strong> akan dihapus permanen.</div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Tindakan ini tidak dapat dibatalkan.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-delete')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-delete-btn" onclick="confirmDeleteItem()" class="btn btn-danger" style="min-width:110px;justify-content:center">
        <i data-feather="trash-2" style="width:14px;height:14px"></i> Hapus
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Nonaktifkan Produk -->
<div class="modal-overlay" id="modal-confirm-delete-product">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(249,115,22,0.12);border:2px solid rgba(249,115,22,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="eye-off" style="width:28px;height:28px;stroke:#f97316"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Nonaktifkan Produk?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Produk <strong id="confirm-delete-product-name" style="color:var(--text-primary)"></strong> akan disembunyikan dari daftar.</div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Bisa diaktifkan kembali kapan saja.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-delete-product')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-delete-product-btn" onclick="confirmDeleteProduct()" class="btn btn-warning" style="min-width:110px;justify-content:center">
        <i data-feather="eye-off" style="width:14px;height:14px"></i> Nonaktifkan
      </button>
    </div>
  </div>
</div>

<!-- Modal Tambah/Edit Pelanggan -->
<div class="modal-overlay" id="modal-add-customer">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <div class="modal-title" id="modal-customer-title">Tambah Pelanggan</div>
      <button class="modal-close" onclick="closeModal('modal-add-customer')"><i data-feather="x"></i></button>
    </div>
    <form onsubmit="submitCustomer(event)">
      <input type="hidden" id="customer-id" value="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama *</label>
          <input class="form-control" id="customer-name" placeholder="Nama pelanggan / perusahaan" required />
        </div>
        <div class="form-group">
          <label class="form-label">No. HP *</label>
          <input class="form-control" id="customer-phone" placeholder="0812-3456-7890" required />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kota</label>
          <input class="form-control" id="customer-city" placeholder="Jakarta" />
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" id="customer-email" type="email" placeholder="email@contoh.com" />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Alamat</label>
        <input class="form-control" id="customer-address" placeholder="Jl. Merdeka No. 1..." />
      </div>
      <div class="form-group">
        <label class="form-label">Catatan</label>
        <textarea class="form-control" id="customer-notes" rows="2" placeholder="Catatan tambahan..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-customer')">Batal</button>
        <button type="submit" class="btn btn-primary"><i data-feather="save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi Hapus Pelanggan -->
<div class="modal-overlay" id="modal-confirm-delete-customer">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="user-x" style="width:28px;height:28px;stroke:#ef4444"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Hapus Pelanggan?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">
      Pelanggan <strong id="confirm-delete-customer-name" style="color:var(--text-primary)"></strong> akan dihapus permanen.
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Tindakan ini tidak dapat dibatalkan.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-delete-customer')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-delete-customer-btn" onclick="confirmDeleteCustomer()" class="btn btn-danger" style="min-width:110px;justify-content:center">
        <i data-feather="trash-2" style="width:14px;height:14px"></i> Hapus
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Hapus User -->
<div class="modal-overlay" id="modal-confirm-toggle-user">
  <div class="modal" style="max-width:360px;text-align:center">
    <div id="modal-toggle-user-icon" style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i id="modal-toggle-user-icon-i" data-feather="trash-2" style="width:28px;height:28px;stroke:#ef4444"></i>
    </div>
    <div id="modal-toggle-user-title" style="font-size:18px;font-weight:700;margin-bottom:8px">Hapus User?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">
      User <strong id="modal-toggle-user-name" style="color:var(--text-primary)"></strong>
      akan <span id="modal-toggle-user-action">dihapus permanen</span>.
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Tindakan ini tidak dapat dibatalkan.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-toggle-user')" class="btn btn-secondary" style="min-width:110px;justify-content:center">
        Batal
      </button>
      <button id="confirm-toggle-user-btn" onclick="confirmToggleUser()" class="btn btn-danger" style="min-width:110px;justify-content:center">
        <i data-feather="trash-2" style="width:14px;height:14px"></i>
        <span id="confirm-toggle-user-label">Hapus</span>
      </button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Lightbox untuk foto bukti -->
<div id="lightbox" onclick="this.classList.remove('open')">
  <img id="lightbox-img" src="" alt="Bukti Pengiriman" />
</div>

<!-- ===== PRINT ROOT — hanya elemen ini yang tampil saat Ctrl+P ===== -->
<div id="print-nota-root"></div>

<!-- Modal Nota Invoice -->
<div class="modal-overlay" id="modal-nota-invoice">
  <div class="modal" style="max-width:440px;width:100%;padding:0;display:flex;flex-direction:column;max-height:88vh">
    <div class="modal-header" style="padding:16px 20px;margin-bottom:0;border-bottom:1px solid var(--border);flex-shrink:0">
      <div class="modal-title" style="font-size:15px"><i data-feather="file-text" style="width:15px;height:15px;stroke:var(--accent)"></i> Nota Invoice</div>
      <button class="modal-close" onclick="closeModal('modal-nota-invoice')"><i data-feather="x"></i></button>
    </div>
    <div id="modal-nota-body" style="padding:24px;font-size:13px;overflow-y:auto;flex:1">
      <div style="text-align:center;padding:32px;color:var(--text-muted)">Memuat...</div>
    </div>
    <div style="border-top:1px solid var(--border);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px;flex-shrink:0;background:var(--bg-card)">
      <button class="btn btn-primary" onclick="cetakNotaInvoice()">
        <i data-feather="printer"></i> Cetak
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Update Status Pengiriman (Stepper) -->
<div class="modal-overlay" id="modal-confirm-status-delivery">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(6,182,212,0.12);border:2px solid rgba(6,182,212,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="truck" style="width:28px;height:28px;stroke:#22d3ee"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Update Status Pengiriman?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">
      Pengiriman order <strong id="confirm-status-delivery-num" style="color:var(--text-primary)"></strong>
      akan dipindahkan ke status <strong id="confirm-status-delivery-next" style="color:#22d3ee"></strong>.
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Pastikan tahap sebelumnya sudah selesai.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-status-delivery')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-status-delivery-btn" onclick="confirmUpdateDeliveryStatus()" class="btn btn-primary" style="min-width:110px;justify-content:center">
        <i data-feather="check" style="width:14px;height:14px"></i> Ya, Update
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Update Status Order (Stepper) -->
<div class="modal-overlay" id="modal-confirm-status-order">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(99,102,241,0.12);border:2px solid rgba(99,102,241,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="arrow-right-circle" style="width:28px;height:28px;stroke:#a5b4fc"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Update Status?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">
      Order <strong id="confirm-status-order-num" style="color:var(--text-primary)"></strong>
      akan dipindahkan ke status <strong id="confirm-status-order-next" style="color:#a5b4fc"></strong>.
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Pastikan tahap sebelumnya sudah selesai.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-status-order')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-status-order-btn" onclick="confirmUpdateStatus()" class="btn btn-primary" style="min-width:110px;justify-content:center">
        <i data-feather="check" style="width:14px;height:14px"></i> Ya, Update
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Batalkan Order -->
<div class="modal-overlay" id="modal-confirm-cancel-order">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="x-circle" style="width:28px;height:28px;stroke:#ef4444"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Batalkan Order?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">
      Order <strong id="confirm-cancel-order-num" style="color:var(--text-primary)"></strong> akan dibatalkan.
    </div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:28px">Order yang dibatalkan tidak dapat diproses kembali.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-cancel-order')" class="btn btn-secondary" style="min-width:110px;justify-content:center">Batal</button>
      <button id="confirm-cancel-order-btn" onclick="confirmCancelOrder()" class="btn btn-danger" style="min-width:110px;justify-content:center">
        <i data-feather="x" style="width:14px;height:14px"></i> Ya, Batalkan
      </button>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Logout -->
<div class="modal-overlay" id="modal-confirm-logout">
  <div class="modal" style="max-width:360px;text-align:center">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(99,102,241,0.12);border:2px solid rgba(99,102,241,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <i data-feather="log-out" style="width:28px;height:28px;stroke:#a5b4fc"></i>
    </div>
    <div style="font-size:18px;font-weight:700;margin-bottom:8px">Keluar dari Sistem?</div>
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:28px">Sesi kamu akan diakhiri. Pastikan semua pekerjaan sudah tersimpan.</div>
    <div style="display:flex;gap:10px;justify-content:center">
      <button onclick="closeModal('modal-confirm-logout')" class="btn btn-secondary" style="min-width:110px;justify-content:center">
        Batal
      </button>
      <button onclick="doLogout()" class="btn btn-primary" style="min-width:110px">
        <i data-feather="log-out" style="width:14px;height:14px"></i> Ya, Keluar
      </button>
    </div>
  </div>
</div>

<!-- Logout Transition Overlay -->
<div id="logout-overlay" style="
    position:fixed;inset:0;z-index:99999;
    display:flex;align-items:center;justify-content:center;
    pointer-events:none;overflow:hidden;
    opacity:0;transition:opacity .3s ease;
">
  <div id="logout-backdrop" style="
    position:absolute;inset:0;
    background:rgba(8,8,24,0);
    backdrop-filter:blur(0px);
    -webkit-backdrop-filter:blur(0px);
    transition:background .5s ease,backdrop-filter .5s ease;
  "></div>
  <div style="
    position:absolute;inset:0;
    background-image:linear-gradient(rgba(99,102,241,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(99,102,241,0.04) 1px,transparent 1px);
    background-size:60px 60px;opacity:0;transition:opacity .6s ease .3s;
  " id="logout-grid"></div>
  <div id="logout-orb" style="
    position:absolute;width:600px;height:600px;border-radius:50%;
    background:radial-gradient(circle,rgba(99,102,241,0.12) 0%,transparent 70%);
    top:50%;left:50%;transform:translate(-50%,-50%) scale(0);
    transition:transform .8s cubic-bezier(0.34,1.56,0.64,1) .2s;
  "></div>
  <div id="logout-stars"></div>
  <div id="logout-content" style="
    position:relative;z-index:1;text-align:center;
    opacity:0;transform:translateY(20px) scale(.95);
    transition:opacity .5s ease .45s,transform .5s cubic-bezier(0.34,1.2,0.64,1) .45s;
  ">
    <div style="
      width:80px;height:80px;
      background:linear-gradient(135deg,rgba(99,102,241,0.3),rgba(139,92,246,0.2));
      border-radius:24px;display:flex;align-items:center;justify-content:center;
      margin:0 auto 20px;
      border:1px solid rgba(99,102,241,0.4);
      box-shadow:0 0 40px rgba(99,102,241,0.3),inset 0 1px 0 rgba(255,255,255,0.1);
      transform:scale(0) rotate(-10deg);
      transition:transform .5s cubic-bezier(0.34,1.56,0.64,1) .6s;
    " id="logout-icon">
      <i class="bi bi-box-arrow-left" style="font-size:34px;color:#a5b4fc"></i>
    </div>
    <div style="font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(165,180,252,0.6);margin-bottom:8px">Sampai Jumpa</div>
    <div style="font-size:28px;font-weight:900;letter-spacing:-1px;line-height:1;margin-bottom:8px;background:linear-gradient(135deg,#a5b4fc,#c4b5fd,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></div>
    <div style="font-size:13px;color:rgba(148,163,184,0.6);margin-bottom:28px">Sesi kamu telah berakhir...</div>
    <div style="width:200px;height:2px;background:rgba(99,102,241,0.15);border-radius:2px;margin:0 auto;overflow:hidden">
      <div id="logout-progress" style="height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#a78bfa,#67e8f9);border-radius:2px;transition:width 1.4s cubic-bezier(0.4,0,0.2,1) .7s"></div>
    </div>
  </div>
</div>

<script src="js/app.js?v=<?= time() ?>"></script>
<script>
// Fungsi cetak nota — clone isi nota ke print-root lalu print
function cetakNota() {
  const nota = document.getElementById('ni-nota-print');
  if (!nota) { return; }
  const root = document.getElementById('print-nota-root');
  // Clone konten nota
  root.innerHTML = '';
  const clone = nota.cloneNode(true);
  clone.removeAttribute('id');
  clone.style.cssText = ''; // biarkan CSS @media print yang atur
  root.appendChild(clone);
  window.print();
  // Bersihkan setelah print dialog ditutup
  setTimeout(() => { root.innerHTML = ''; }, 1000);
}
</script>
</body>
</html>
