<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/database.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $sql = "SELECT o.*, c.name as customer_name, m.name as machine_name, u.name as operator_name
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    LEFT JOIN machines m ON o.machine_id = m.id
                    LEFT JOIN users u ON o.operator_id = u.id
                    WHERE 1=1";
            $params = [];
            if ($status) { $sql .= " AND o.status=?"; $params[] = $status; }
            if ($search) { $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR c.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT o.*, c.name as customer_name, m.name as machine_name FROM orders o JOIN customers c ON o.customer_id=c.id LEFT JOIN machines m ON o.machine_id=m.id WHERE o.id=?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            $stmt2 = $pdo->prepare("SELECT oi.*, i.name as item_name, u.symbol FROM order_items oi JOIN items i ON oi.item_id=i.id JOIN units u ON i.unit_id=u.id WHERE oi.order_id=?");
            $stmt2->execute([$id]);
            $order['items'] = $stmt2->fetchAll();
            echo json_encode(['success' => true, 'data' => $order]);
        } elseif ($action === 'kanban') {
            $statuses = ['pending','confirmed','in_progress','quality_check','completed'];
            $kanban = [];
            foreach ($statuses as $s) {
                $stmt = $pdo->prepare("SELECT o.id, o.order_number, o.title, o.priority, o.due_date, c.name as customer_name, m.name as machine_name FROM orders o JOIN customers c ON o.customer_id=c.id LEFT JOIN machines m ON o.machine_id=m.id WHERE o.status=? ORDER BY FIELD(o.priority,'urgent','high','normal','low'), o.due_date LIMIT 10");
                $stmt->execute([$s]);
                $kanban[$s] = $stmt->fetchAll();
            }
            echo json_encode(['success' => true, 'data' => $kanban]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        // Generate order number
        $prefix = 'ORD';
        $stmt = $pdo->query("SELECT COUNT(*)+1 FROM orders WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
        $seq = str_pad($stmt->fetchColumn(), 4, '0', STR_PAD_LEFT);
        $orderNum = $prefix . '-' . date('ym') . '-' . $seq;

        $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, machine_id, operator_id, title, description, status, priority, quantity, unit_price, total_price, discount, tax, grand_total, start_date, due_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $total = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);
        $discount = $data['discount'] ?? 0;
        $tax = $data['tax'] ?? 11;
        $grand = ($total - $discount) * (1 + $tax / 100);
        $stmt->execute([
            $orderNum, $data['customer_id'], $data['machine_id'] ?: null, $data['operator_id'] ?: null,
            $data['title'], $data['description'] ?? '', $data['status'] ?? 'pending',
            $data['priority'] ?? 'normal', $data['quantity'] ?? 1,
            $data['unit_price'] ?? 0, $total, $discount, $tax, $grand,
            $data['start_date'] ?? null, $data['due_date'] ?? null, $data['notes'] ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => 'Order berhasil dibuat', 'order_number' => $orderNum]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        $stmt = $pdo->prepare("UPDATE orders SET status=?, machine_id=?, operator_id=?, notes=?, completed_date = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_date END WHERE id=?");
        $stmt->execute([$data['status'], $data['machine_id'] ?: null, $data['operator_id'] ?: null, $data['notes'] ?? '', $data['status'], $id]);
        // Update machine status
        if ($data['machine_id']) {
            $machStatus = $data['status'] === 'in_progress' ? 'active' : 'idle';
            $pdo->prepare("UPDATE machines SET status=? WHERE id=?")->execute([$machStatus, $data['machine_id']]);
        }
        echo json_encode(['success' => true, 'message' => 'Order berhasil diupdate']);
        break;
}
