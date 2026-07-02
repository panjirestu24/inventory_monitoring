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
            $sql = "SELECT o.*, c.name as customer_name, u.name as operator_name
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id_customers
                    LEFT JOIN users u ON o.operator_id = u.id_users
                    WHERE 1=1";
            $params = [];
            // Support multi-status filter: statuses[] = ['pending','confirmed',...]
            $multiStatuses = $_GET['statuses'] ?? [];
            if (!empty($multiStatuses) && is_array($multiStatuses)) {
                $placeholders = implode(',', array_fill(0, count($multiStatuses), '?'));
                $sql .= " AND o.status IN ($placeholders)";
                $params = array_merge($params, $multiStatuses);
            } elseif ($status) {
                $sql .= " AND o.status=?";
                $params[] = $status;
            }
            if ($search) { $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR c.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT o.*, c.name as customer_name FROM orders o JOIN customers c ON o.customer_id=c.id_customers WHERE o.id_orders=?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            $stmt2 = $pdo->prepare("SELECT oi.*, i.name as item_name, u.symbol FROM order_items oi JOIN items i ON oi.item_id=i.id_items JOIN units u ON i.unit_id=u.id_units WHERE oi.order_id=?");
            $stmt2->execute([$id]);
            $order['items'] = $stmt2->fetchAll();
            echo json_encode(['success' => true, 'data' => $order]);
        } elseif ($action === 'kanban') {
            $statuses = ['pending','confirmed','in_progress','quality_check','completed'];
            $kanban = [];
            foreach ($statuses as $s) {
                $stmt = $pdo->prepare("SELECT o.id_orders, o.order_number, o.title, o.priority, o.due_date, c.name as customer_name FROM orders o JOIN customers c ON o.customer_id=c.id_customers WHERE o.status=? ORDER BY FIELD(o.priority,'urgent','high','normal','low'), o.due_date LIMIT 10");
                $stmt->execute([$s]);
                $kanban[$s] = $stmt->fetchAll();
            }
            echo json_encode(['success' => true, 'data' => $kanban]);

        } elseif ($action === 'mes_monitoring') {
            // ── FIFO Queue: order aktif diurutkan waktu masuk (FIFO) ──
            $fifoStmt = $pdo->query(
                "SELECT o.id_orders, o.order_number, o.title, o.status, o.priority,
                        o.quantity, o.due_date, o.created_at, o.updated_at,
                        c.name as customer_name,
                        u.name as operator_name,
                        TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as age_minutes,
                        TIMESTAMPDIFF(MINUTE, o.updated_at, NOW()) as idle_minutes,
                        CASE
                            WHEN o.due_date < CURDATE() THEN 'overdue'
                            WHEN o.due_date = CURDATE() THEN 'today'
                            WHEN o.due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'tomorrow'
                            ELSE 'normal'
                        END as due_status
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id_customers
                 LEFT JOIN users u ON o.operator_id = u.id_users
                 WHERE o.status NOT IN ('completed','cancelled')
                 ORDER BY o.created_at ASC"   /* FIFO: terlama masuk = paling atas */
            );
            $fifoOrders = $fifoStmt->fetchAll();

            // ── Kanban pipeline dengan durasi per status ──
            $kanbanStmt = $pdo->prepare(
                "SELECT o.id_orders, o.order_number, o.title, o.priority, o.due_date,
                        o.created_at, o.updated_at,
                        c.name as customer_name,
                        TIMESTAMPDIFF(HOUR, o.updated_at, NOW()) as hours_in_status
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id_customers
                 WHERE o.status = ?
                 ORDER BY o.created_at ASC
                 LIMIT 15"
            );
            $pipeline = [];
            foreach (['pending','confirmed','in_progress','quality_check'] as $s) {
                $kanbanStmt->execute([$s]);
                $pipeline[$s] = $kanbanStmt->fetchAll();
            }

            // ── Statistik throughput hari ini ──
            $statsStmt = $pdo->query(
                "SELECT
                    SUM(CASE WHEN status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'completed' AND DATE(completed_date) = CURDATE() THEN 1 ELSE 0 END) as done_today,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN due_date < CURDATE() AND status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) as overdue,
                    AVG(CASE WHEN status = 'completed'
                        THEN TIMESTAMPDIFF(MINUTE, created_at,
                            COALESCE(completed_date, updated_at)) END) as avg_minutes
                 FROM orders"
            );
            $stats = $statsStmt->fetch();

            echo json_encode([
                'success'      => true,
                'fifo_queue'   => $fifoOrders,
                'pipeline'     => $pipeline,
                'stats'        => $stats,
            ]);
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
                $stmt  = $pdo->prepare("SELECT id_customers FROM customers WHERE phone = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$phone]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $customerId = $existing['id_customers'];
                    // Update nama jika berbeda
                    $pdo->prepare("UPDATE customers SET name=?, city=?, updated_at=NOW() WHERE id_customers=?")
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

                // ── VALIDASI STOK SEBELUM ORDER DIBUAT ──────────────
                $stockErrors = [];
                $tableCheck  = $pdo->query("SHOW TABLES LIKE 'product_materials'")->fetchColumn();
                if (!empty($items) && $tableCheck) {
                    $stmtCheckBom = $pdo->prepare(
                        "SELECT pm.qty_per_unit,
                                i.id_items as item_id, i.name as item_name,
                                i.stock, u.symbol
                         FROM product_materials pm
                         JOIN items i ON pm.item_id = i.id_items
                         JOIN units u ON i.unit_id  = u.id_units
                         WHERE pm.product_id = ?"
                    );
                    // Kumpulkan total kebutuhan per item (bisa dari beberapa produk)
                    $needed = []; // item_id => ['name','symbol','stock','needed']
                    foreach ($items as $orderItem) {
                        $productId = (int)($orderItem['id'] ?? $orderItem['product_id'] ?? 0);
                        if ($productId <= 0) continue;
                        $orderQty = (float)($orderItem['qty'] ?? 1);
                        $stmtCheckBom->execute([$productId]);
                        foreach ($stmtCheckBom->fetchAll() as $bom) {
                            $iid = $bom['item_id'];
                            if (!isset($needed[$iid])) {
                                $needed[$iid] = [
                                    'name'   => $bom['item_name'],
                                    'symbol' => $bom['symbol'],
                                    'stock'  => (float)$bom['stock'],
                                    'needed' => 0,
                                ];
                            }
                            $needed[$iid]['needed'] += (float)$bom['qty_per_unit'] * $orderQty;
                        }
                    }
                    // Cek apakah ada yang tidak cukup
                    foreach ($needed as $iid => $info) {
                        if ($info['stock'] <= 0) {
                            $stockErrors[] = "Stok {$info['name']} HABIS (0 {$info['symbol']})";
                        } elseif ($info['needed'] > $info['stock']) {
                            $stockErrors[] = "Stok {$info['name']} tidak cukup "
                                . "(butuh {$info['needed']} {$info['symbol']}, "
                                . "tersedia {$info['stock']} {$info['symbol']})";
                        }
                    }
                }
                // Batalkan jika ada stok habis
                if (!empty($stockErrors)) {
                    $pdo->rollBack();
                    echo json_encode([
                        'success'       => false,
                        'message'       => 'Order dibatalkan karena stok bahan baku tidak mencukupi.',
                        'stock_errors'  => $stockErrors,
                    ]);
                    exit;
                }
                // ── END VALIDASI STOK ────────────────────────────────

                // Gunakan grand_total_override kalau ada (dikirim dari frontend)
                if (!empty($data['grand_total_override'])) {
                    $grand = (float)$data['grand_total_override'];
                } else {
                    $grand = ($total - $discount) * (1 + $tax / 100);
                }

                $pdo->prepare(
                    "INSERT INTO orders
                     (order_number, customer_id, operator_id, title, description,
                      status, priority, quantity, unit_price, total_price, discount, tax,
                      grand_total, start_date, due_date, notes, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([
                    $orderNum, $customerId,
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

                // ── Kurangi stok bahan baku berdasarkan BOM produk ──
                $stockWarnings = [];
                if (!empty($items)) {
                    // Cek dulu apakah tabel product_materials ada
                    $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_materials'")->fetchColumn();
                    if ($tableCheck) {
                        $stmtBom = $pdo->prepare(
                            "SELECT pm.item_id, pm.qty_per_unit,
                                    i.stock, i.name as item_name, u.symbol
                             FROM product_materials pm
                             JOIN items i ON pm.item_id = i.id_items
                             JOIN units u ON i.unit_id = u.id_units
                             WHERE pm.product_id = ?"
                        );
                        $stmtUpdateStock = $pdo->prepare(
                            "UPDATE items SET stock = stock - ? WHERE id_items = ? AND stock >= ?"
                        );
                        $stmtLogTx = $pdo->prepare(
                            "INSERT INTO stock_transactions
                             (item_id, type, reference_type, reference_id, quantity,
                              stock_before, stock_after, notes, created_by)
                             VALUES (?,?,?,?,?,?,?,?,?)"
                        );
                        $stmtGetStock = $pdo->prepare("SELECT stock FROM items WHERE id_items = ?");

                        foreach ($items as $orderItem) {
                            // Ambil product_id — bisa dari key 'id' atau 'product_id'
                            $productId = (int)($orderItem['id'] ?? $orderItem['product_id'] ?? 0);
                            if ($productId <= 0) continue; // skip item manual

                            $orderQty = (float)($orderItem['qty'] ?? 1);

                            $stmtBom->execute([$productId]);
                            $boms = $stmtBom->fetchAll();

                            foreach ($boms as $bom) {
                                $itemId      = (int)$bom['item_id'];
                                $qtyNeeded   = (float)$bom['qty_per_unit'] * $orderQty;
                                $stockBefore = (float)$bom['stock'];

                                if ($qtyNeeded <= 0) continue;

                                $stmtUpdateStock->execute([$qtyNeeded, $itemId, $qtyNeeded]);
                                if ($stmtUpdateStock->rowCount() > 0) {
                                    $stmtGetStock->execute([$itemId]);
                                    $stockAfter = (float)$stmtGetStock->fetchColumn();
                                    $stmtLogTx->execute([
                                        $itemId, 'out', 'order', $orderId,
                                        $qtyNeeded, $stockBefore, $stockAfter,
                                        "Order #{$orderNum} — {$orderItem['name']} ×{$orderQty}",
                                        $_SESSION['user_id']
                                    ]);
                                } else {
                                    // Stok tidak cukup — kurangi sampai 0 dan catat warning
                                    if ($stockBefore > 0) {
                                        $pdo->prepare("UPDATE items SET stock = 0 WHERE id_items = ?")->execute([$itemId]);
                                        $stmtLogTx->execute([
                                            $itemId, 'out', 'order', $orderId,
                                            $stockBefore, $stockBefore, 0,
                                            "Order #{$orderNum} — stok tidak cukup, dikurangi hingga 0",
                                            $_SESSION['user_id']
                                        ]);
                                    }
                                    $stockWarnings[] = "{$bom['item_name']}: stok tidak cukup (butuh {$qtyNeeded} {$bom['symbol']}, tersedia {$stockBefore})";
                                }
                            }
                        }
                    }
                }

                $pdo->commit();

                // Ambil data lengkap untuk nota
                $stmt = $pdo->prepare(
                    "SELECT o.*, c.name as customer_name, c.phone as customer_phone,
                            c.city as customer_city, c.address as customer_address,
                            u.name as operator_name
                     FROM orders o
                     JOIN customers c ON o.customer_id = c.id_customers
                     LEFT JOIN users u ON o.operator_id = u.id_users
                     WHERE o.id_orders = ?"
                );
                $stmt->execute([$orderId]);
                $orderData = $stmt->fetch();

                echo json_encode([
                    'success'        => true,
                    'message'        => 'Order berhasil dibuat',
                    'order_number'   => $orderNum,
                    'order_id'       => $orderId,
                    'customer_id'    => $customerId,
                    'data'           => $orderData,
                    'stock_warnings' => $stockWarnings ?? [],
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

        $stmt = $pdo->prepare("INSERT INTO orders (order_number, customer_id, operator_id, title, description, status, priority, quantity, unit_price, total_price, discount, tax, grand_total, start_date, due_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $total = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);
        $discount = $data['discount'] ?? 0;
        $tax = $data['tax'] ?? 11;
        $grand = ($total - $discount) * (1 + $tax / 100);
        $stmt->execute([
            $orderNum, $data['customer_id'], $data['operator_id'] ?: null,
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
                 WHERE id_orders=?"
            );
            $stmt->execute([$newStatus, $newStatus, $id]);
            echo json_encode(['success' => true, 'message' => 'Status diupdate ke ' . $newStatus]);
            break;
        }

        $stmt = $pdo->prepare("UPDATE orders SET status=?, operator_id=?, notes=?, completed_date = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_date END WHERE id_orders=?");
        $stmt->execute([$data['status'], $data['operator_id'] ?: null, $data['notes'] ?? '', $data['status'], $id]);
        echo json_encode(['success' => true, 'message' => 'Order berhasil diupdate']);
        break;
}
