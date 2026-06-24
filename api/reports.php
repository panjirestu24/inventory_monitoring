<?php
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
$type = $_GET['type'] ?? 'stock';
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

switch ($type) {
    case 'stock':
        $stmt = $pdo->query("SELECT i.code, i.name, i.stock, i.min_stock, i.max_stock, u.symbol, c.name as category, (i.stock * i.purchase_price) as value, CASE WHEN i.stock <= i.min_stock THEN 'kritis' WHEN i.stock <= i.min_stock*1.5 THEN 'rendah' ELSE 'aman' END as status FROM items i JOIN units u ON i.unit_id=u.id JOIN categories c ON i.category_id=c.id WHERE i.is_active=1 ORDER BY c.name, i.name");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'transactions':
        $stmt = $pdo->prepare("SELECT st.created_at, i.code, i.name as item_name, st.type, st.reference_type, st.quantity, u.symbol, st.stock_before, st.stock_after, st.unit_price, st.notes, usr.name as user_name FROM stock_transactions st JOIN items i ON st.item_id=i.id JOIN units u ON i.unit_id=u.id LEFT JOIN users usr ON st.created_by=usr.id WHERE DATE(st.created_at) BETWEEN ? AND ? ORDER BY st.created_at DESC");
        $stmt->execute([$from, $to]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'orders':
        $stmt = $pdo->prepare("SELECT o.order_number, o.title, o.status, o.priority, c.name as customer, m.name as machine, u.name as operator, o.quantity, o.grand_total, o.start_date, o.due_date, o.completed_date FROM orders o JOIN customers c ON o.customer_id=c.id LEFT JOIN machines m ON o.machine_id=m.id LEFT JOIN users u ON o.operator_id=u.id WHERE DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at DESC");
        $stmt->execute([$from, $to]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'machine_utilization':
        $stmt = $pdo->prepare("SELECT m.name as machine, ml.event, COUNT(*) as count, SUM(ml.duration_minutes) as total_minutes FROM machine_logs ml JOIN machines m ON ml.machine_id=m.id WHERE DATE(ml.created_at) BETWEEN ? AND ? GROUP BY m.id, ml.event ORDER BY m.code, ml.event");
        $stmt->execute([$from, $to]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;
}
