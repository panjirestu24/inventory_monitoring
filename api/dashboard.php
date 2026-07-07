<?php
// ============================================================
// API DASHBOARD - Menampilkan Ringkasan Data Utama
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$pdo = db();
$action = $_GET['action'] ?? 'dashboard';

// Khusus untuk mendapatkan list operators
if ($action === 'operators') {
    $stmt = $pdo->query("SELECT id_users, name, email, role FROM users WHERE role IN ('admin', 'operator') AND is_active=1 ORDER BY name");
    echo json_encode(['berhasil' => true, 'success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// Endpoint ringan khusus untuk update badge sidebar (polling setiap 10 detik)
if ($action === 'badges') {
    $activeOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','in_progress','quality_check')")->fetchColumn();
    $lowStock     = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE stock <= min_stock AND is_active=1")->fetchColumn();
    $inTransit    = (int)$pdo->query("SELECT COUNT(*) FROM deliveries WHERE status='shipping'")->fetchColumn();
    echo json_encode([
        'success'       => true,
        'active_orders' => $activeOrders,
        'low_stock'     => $lowStock,
        'in_transit'    => $inTransit,
        'ts'            => time(),
    ]);
    exit;
}

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

// Pendapatan bulan ini (dari order yang selesai)
$stmt = $pdo->query("SELECT COALESCE(SUM(grand_total),0) as revenue FROM orders WHERE status='completed' AND MONTH(completed_date)=MONTH(NOW()) AND YEAR(completed_date)=YEAR(NOW())");
$stats['monthly_revenue'] = (float)$stmt->fetchColumn();

// --- ITEM STOK RENDAH (10 Teratas) ---
$stmt = $pdo->query("SELECT i.code, i.name, i.stock, i.min_stock, u.symbol as unit, c.name as category FROM items i JOIN units u ON i.unit_id=u.id_units JOIN categories c ON i.category_id=c.id_categories WHERE i.stock <= i.min_stock AND i.is_active=1 ORDER BY (i.stock/NULLIF(i.min_stock,0)) ASC LIMIT 10");
$lowStock = $stmt->fetchAll();

// --- ORDER TERBARU (8 Terakhir) ---
$stmt = $pdo->query("SELECT o.order_number, o.title, o.status, o.priority, o.due_date, o.grand_total, c.name as customer FROM orders o JOIN customers c ON o.customer_id=c.id_customers ORDER BY o.created_at DESC LIMIT 8");
$recentOrders = $stmt->fetchAll();

// --- CHART: Order per Hari (7 Hari Terakhir) ---
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date");
$chartOrders = $stmt->fetchAll();

// --- CHART: Nilai Stok per Kategori ---
$stmt = $pdo->query("SELECT c.name, SUM(i.stock * i.purchase_price) as value FROM items i JOIN categories c ON i.category_id=c.id_categories WHERE i.is_active=1 GROUP BY c.id_categories,c.name ORDER BY value DESC");
$chartStock = $stmt->fetchAll();

// --- CHART: Throughput Produksi — Order Selesai & Masuk per Hari (14 Hari) ---
$stmt = $pdo->query(
    "SELECT
        DATE(created_at) as date,
        COUNT(*) as masuk,
        SUM(CASE WHEN status='completed' AND DATE(completed_date) = DATE(created_at) THEN 1 ELSE 0 END) as selesai_hari_itu
     FROM orders
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date"
);
$chartThroughput = $stmt->fetchAll();

// Order selesai per hari (14 hari) — pakai completed_date
$stmt = $pdo->query(
    "SELECT DATE(completed_date) as date, COUNT(*) as selesai
     FROM orders
     WHERE status = 'completed'
     AND completed_date >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     GROUP BY DATE(completed_date)
     ORDER BY date"
);
$chartCompleted = $stmt->fetchAll();

// --- NOTIFIKASI TERBARU (5 Teratas) ---
$notifications = [];

// Kirim semua data dalam format JSON
echo json_encode([
    'success'        => true,
    'berhasil'       => true,
    'stats'          => $stats,
    'low_stock'      => $lowStock,
    'recent_orders'  => $recentOrders,
    'chart_orders'   => $chartOrders,
    'chart_stock'    => $chartStock,
    'chart_throughput' => $chartCompleted,
    'notifications'  => $notifications,
    'timestamp'      => date('Y-m-d H:i:s'),
]);

