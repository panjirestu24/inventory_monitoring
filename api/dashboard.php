<?php
// ============================================================
// API DASHBOARD - Menampilkan Ringkasan Data Utama
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';

$pdo = db();

// --- STATISTIK RINGKASAN ---
$stats = [];

// Total item & stok rendah
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END) as low_stock FROM items WHERE is_active=1");
$r = $stmt->fetch();
$stats['total_items'] = (int)$r['total'];
$stats['low_stock'] = (int)$r['low_stock'];

// Total order hari ini
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['orders_today'] = (int)$stmt->fetchColumn();

// Order aktif (yang sedang dikerjakan)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('confirmed','in_progress','quality_check')");
$stats['active_orders'] = (int)$stmt->fetchColumn();

// Status mesin (aktif, idle, maintenance, offline)
$stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM machines GROUP BY status");
$machineStats = [];
while ($row = $stmt->fetch()) {
    $machineStats[$row['status']] = (int)$row['cnt'];
}
$stats['machines'] = $machineStats;

// Pendapatan bulan ini (dari order yang selesai)
$stmt = $pdo->query("SELECT COALESCE(SUM(grand_total),0) as revenue FROM orders WHERE status='completed' AND MONTH(completed_date)=MONTH(NOW()) AND YEAR(completed_date)=YEAR(NOW())");
$stats['monthly_revenue'] = (float)$stmt->fetchColumn();

// --- DAFTAR STATUS MESIN ---
$stmt = $pdo->query("SELECT m.*, o.order_number, o.title as current_job FROM machines m LEFT JOIN orders o ON m.id=o.machine_id AND o.status='in_progress' ORDER BY m.code");
$machines = $stmt->fetchAll();

// --- ITEM STOK RENDAH (10 Teratas) ---
$stmt = $pdo->query("SELECT i.code, i.name, i.stock, i.min_stock, u.symbol as unit, c.name as category FROM items i JOIN units u ON i.unit_id=u.id JOIN categories c ON i.category_id=c.id WHERE i.stock <= i.min_stock AND i.is_active=1 ORDER BY (i.stock/NULLIF(i.min_stock,0)) ASC LIMIT 10");
$lowStock = $stmt->fetchAll();

// --- ORDER TERBARU (8 Terakhir) ---
$stmt = $pdo->query("SELECT o.order_number, o.title, o.status, o.priority, o.due_date, o.grand_total, c.name as customer FROM orders o JOIN customers c ON o.customer_id=c.id ORDER BY o.created_at DESC LIMIT 8");
$recentOrders = $stmt->fetchAll();

// --- CHART: Order per Hari (7 Hari Terakhir) ---
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
$chartOrders = $stmt->fetchAll();

// --- CHART: Nilai Stok per Kategori ---
$stmt = $pdo->query("SELECT c.name, SUM(i.stock * i.purchase_price) as value FROM items i JOIN categories c ON i.category_id=c.id WHERE i.is_active=1 GROUP BY c.id,c.name ORDER BY value DESC");
$chartStock = $stmt->fetchAll();

// --- NOTIFIKASI TERBARU (5 Teratas) ---
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
$notifications = $stmt->fetchAll();

// Kirim semua data dalam format JSON
echo json_encode([
    'berhasil'       => true,
    'stats'          => $stats,
    'mesin'          => $machines,
    'stok_rendah'    => $lowStock,
    'order_terbaru'  => $recentOrders,
    'chart_orders'   => $chartOrders,
    'chart_stock'    => $chartStock,
    'notifikasi'     => $notifications,
    'timestamp'      => date('Y-m-d H:i:s'),
]);
