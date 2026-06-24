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
        // ── Buat order + customer sekaligus (dari halaman kasir) ──
        if ($action === 'create_with_customer') {
            $data = json_decode(file_get_contents('php://input'), true);

            $pdo->beginTransaction();
            try {
                // Cari pelanggan berdasarkan HP, jika belum ada buat baru
                $phone = preg_replace('/[^0-9]/', '', $data['customer_phone'] ?? '');
                $stmt  = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$phone]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $customerId = $existing['id'];
                    // Update nama jika berbeda
                    $pdo->prepare("UPDATE customers SET name=?, city=?, updated_at=NOW() WHERE id=?")
                        ->execute([$data['customer_name'], $data['customer_city'] ?? '', $customerId]);
                } else {
                    // Auto-generate kode pelanggan
                    $countStmt = $pdo->query("SELECT COUNT(*)+1 FROM customers");
                    $custCode  = 'CUS' . str_pad($countStmt->fetchColumn(), 4, '0', STR_PAD_LEFT);
                    $pdo->prepare(
                        "INSERT INTO customers (code, name, phone, city, address, notes, is_active)
                         VALUES (?,?,?,?,?,?,1)"
                    )->execute([
                        $custCode,
                        $data['customer_name'],
                        $phone,
                        $data['customer_city'] ?? '',
                        $data['customer_address'] ?? '',
                        $data['customer_notes'] ?? '',
                    ]);
                    $customerId = $pdo->lastInsertId();
                }

                // Generate nomor order
                $prefix   = 'ORD';
                $seqStmt  = $pdo->query("SELECT COUNT(*)+1 FROM orders WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())");
                $seq      = str_pad($seqStmt->fetchColumn(), 4, '0', STR_PAD_LEFT);
                $orderNum = $prefix . '-' . date('ym') . '-' . $seq;

                $qty      = (float)($data['quantity']   ?? 1);
                $price    = (float)($data['unit_price']  ?? 0);
                $discount = (float)($data['discount']    ?? 0);
                $tax      = (float)($data['tax']         ?? 11);
                $items    = $data['items'] ?? [];

                // Kalau ada multi-item, hitung dari items
                if (!empty($items)) {
                    $total = array_sum(array_map(fn($i) => (float)($i['qty'] ?? 1) * (float)($i['price'] ?? 0), $items));
                    $qty   = array_sum(array_map(fn($i) => (float)($i['qty'] ?? 1), $items));
                    $price = $qty > 0 ? $total / $qty : 0;
                } else {
                    $total = $qty * $price;
                }

                // Gunakan grand_total_override kalau ada (dikirim dari frontend)
                if (!empty($data['grand_total_override'])) {
                    $grand = (float)$data['grand_total_override'];
                } else {
                    $grand = ($total - $discount) * (1 + $tax / 100);
                }

                $pdo->prepare(
                    "INSERT INTO orders
                     (order_number, customer_id, machine_id, operator_id, title, description,
                      status, priority, quantity, unit_price, total_price, discount, tax,
                      grand_total, start_date, due_date, notes, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([
                    $orderNum, $customerId,
                    isset($data['machine_id'])  && $data['machine_id']  ? $data['machine_id']  : null,
                    isset($data['operator_id']) && $data['operator_id'] ? $data['operator_id'] : null,
                    $data['title'],
                    $data['description'] ?? '',
                    'pending',
                    $data['priority'] ?? 'normal',
                    $qty, $price, $total, $discount, $tax, $grand,
                    $data['start_date'] ?? null,
                    $data['due_date']   ?? null,
                    $data['notes']      ?? '',
                    $_SESSION['user_id'],
                ]);
                $orderId = $pdo->lastInsertId();

                $pdo->commit();

                // Ambil data lengkap untuk nota
                $stmt = $pdo->prepare(
                    "SELECT o.*, c.name as customer_name, c.phone as customer_phone,
                            c.city as customer_city, c.address as customer_address,
                            m.name as machine_name, u.name as operator_name
                     FROM orders o
                     JOIN customers c ON o.customer_id = c.id
                     LEFT JOIN machines m ON o.machine_id = m.id
                     LEFT JOIN users u ON o.operator_id = u.id
                     WHERE o.id = ?"
                );
                $stmt->execute([$orderId]);
                $orderData = $stmt->fetch();

                echo json_encode([
                    'success'      => true,
                    'message'      => 'Order berhasil dibuat',
                    'order_number' => $orderNum,
                    'order_id'     => $orderId,
                    'customer_id'  => $customerId,
                    'data'         => $orderData,
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
            exit;
        }

        // ── POST order biasa ──
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

        // Update status saja (realtime stepper)
        if (isset($data['status_only']) && $data['status_only']) {
            $newStatus = $data['status'];
            $stmt = $pdo->prepare(
                "UPDATE orders SET status=?,
                 completed_date = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_date END
                 WHERE id=?"
            );
            $stmt->execute([$newStatus, $newStatus, $id]);
            // Update machine jika ada
            if (!empty($data['machine_id'])) {
                $machStatus = $newStatus === 'in_progress' ? 'active' : 'idle';
                $pdo->prepare("UPDATE machines SET status=? WHERE id=?")->execute([$machStatus, $data['machine_id']]);
            }
            echo json_encode(['success' => true, 'message' => 'Status diupdate ke ' . $newStatus]);
            break;
        }

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
