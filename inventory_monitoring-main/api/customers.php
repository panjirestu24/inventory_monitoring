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
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id_customers=?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            echo json_encode(['success' => true, 'berhasil' => (bool)$customer, 'data' => $customer]);
        } elseif ($action === 'search') {
            // Cari pelanggan by nama atau HP (untuk autocomplete di form order baru)
            $q = '%' . ($_GET['q'] ?? '') . '%';
            $stmt = $pdo->prepare(
                "SELECT c.*, COUNT(o.id_orders) as total_orders
                 FROM customers c
                 LEFT JOIN orders o ON o.customer_id = c.id_customers
                 WHERE c.is_active = 1 AND (c.name LIKE ? OR c.phone LIKE ?)
                 GROUP BY c.id_customers
                 ORDER BY c.name LIMIT 10"
            );
            $stmt->execute([$q, $q]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'check_phone') {
            // Cek exact match nomor HP — hanya angka dibandingkan
            $raw   = $_GET['phone'] ?? '';
            $phone = preg_replace('/[^0-9]/', '', $raw);
            if (strlen($phone) < 8) {
                echo json_encode(['success' => true, 'found' => false]);
                exit;
            }
            $stmt = $pdo->prepare(
                "SELECT id_customers, name, phone, city
                 FROM customers
                 WHERE REGEXP_REPLACE(phone, '[^0-9]', '') = ? AND is_active = 1
                 LIMIT 1"
            );
            $stmt->execute([$phone]);
            $customer = $stmt->fetch();
            echo json_encode([
                'success' => true,
                'found'   => (bool)$customer,
                'data'    => $customer ?: null,
            ]);
        } elseif ($action === 'history') {
            // Riwayat order per pelanggan
            $id = (int)($_GET['id'] ?? 0);
            $custStmt = $pdo->prepare("SELECT * FROM customers WHERE id_customers = ?");
            $custStmt->execute([$id]);
            $customer = $custStmt->fetch();

            $ordStmt = $pdo->prepare(
                "SELECT o.*, u.name as operator_name,
                        d.status as delivery_status
                 FROM orders o
                 LEFT JOIN users u ON o.operator_id = u.id_users
                 LEFT JOIN deliveries d ON d.order_id = o.id_orders
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
        // Auto-generate kode pelanggan
        $countStmt = $pdo->query("SELECT COUNT(*)+1 FROM customers");
        $custCode  = 'CUS' . str_pad($countStmt->fetchColumn(), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO customers (code, name, contact_person, phone, email, city, address, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $custCode,
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
        $stmt = $pdo->prepare("UPDATE customers SET name=?, contact_person=?, phone=?, email=?, city=?, address=?, notes=? WHERE id_customers=?");
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
        // Cek apakah pelanggan punya order
        $cek = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
        $cek->execute([$id]);
        if ((int)$cek->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'berhasil' => false,
                'message' => 'Pelanggan tidak bisa dihapus karena memiliki riwayat order. Nonaktifkan saja jika tidak ingin ditampilkan.'
            ]);
            exit;
        }
        $pdo->prepare("DELETE FROM customers WHERE id_customers=?")->execute([$id]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Pelanggan berhasil dihapus', 'message' => 'Pelanggan berhasil dihapus']);
        break;
}
