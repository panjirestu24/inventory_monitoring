<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {
    case 'GET':
        if ($action === 'list' || $action === '') {
            $stmt = $pdo->query("SELECT * FROM customers WHERE is_active=1 ORDER BY name");
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            echo json_encode(['success' => true, 'berhasil' => (bool)$customer, 'data' => $customer]);
        } elseif ($action === 'search') {
            // Cari pelanggan by nama atau HP (untuk autocomplete di form order baru)
            $q = '%' . ($_GET['q'] ?? '') . '%';
            $stmt = $pdo->prepare(
                "SELECT c.*, COUNT(o.id) as total_orders
                 FROM customers c
                 LEFT JOIN orders o ON o.customer_id = c.id
                 WHERE c.is_active = 1 AND (c.name LIKE ? OR c.phone LIKE ?)
                 GROUP BY c.id
                 ORDER BY c.name LIMIT 10"
            );
            $stmt->execute([$q, $q]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'history') {
            // Riwayat order per pelanggan
            $id = (int)($_GET['id'] ?? 0);
            $custStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $custStmt->execute([$id]);
            $customer = $custStmt->fetch();

            $ordStmt = $pdo->prepare(
                "SELECT o.*, m.name as machine_name, u.name as operator_name,
                        d.status as delivery_status
                 FROM orders o
                 LEFT JOIN machines m ON o.machine_id = m.id
                 LEFT JOIN users u ON o.operator_id = u.id
                 LEFT JOIN deliveries d ON d.order_id = o.id
                 WHERE o.customer_id = ?
                 ORDER BY o.created_at DESC"
            );
            $ordStmt->execute([$id]);
            $orders = $ordStmt->fetchAll();

            echo json_encode(['success' => true, 'customer' => $customer, 'orders' => $orders]);
        }
        break;
    
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO customers (code, name, contact_person, phone, email, city, address, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $data['code'], 
            $data['name'], 
            $data['contact_person'] ?? '', 
            $data['phone'] ?? '', 
            $data['email'] ?? '', 
            $data['city'] ?? '', 
            $data['address'] ?? '', 
            $data['notes'] ?? ''
        ]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Pelanggan berhasil ditambahkan', 'message' => 'Pelanggan berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
        break;
    
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE customers SET name=?, contact_person=?, phone=?, email=?, city=?, address=?, notes=? WHERE id=?");
        $stmt->execute([
            $data['name'], 
            $data['contact_person'] ?? '', 
            $data['phone'] ?? '', 
            $data['email'] ?? '', 
            $data['city'] ?? '', 
            $data['address'] ?? '', 
            $data['notes'] ?? '', 
            $id
        ]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Pelanggan berhasil diupdate', 'message' => 'Pelanggan berhasil diupdate']);
        break;
    
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE customers SET is_active=0 WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Pelanggan berhasil dihapus', 'message' => 'Pelanggan berhasil dihapus']);
        break;
}
