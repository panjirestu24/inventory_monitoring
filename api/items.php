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
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $low_stock = $_GET['low_stock'] ?? '';
            $sql = "SELECT i.*, u.symbol as unit_symbol, c.name as category_name, s.name as supplier_name
                    FROM items i
                    JOIN units u ON i.unit_id = u.id
                    JOIN categories c ON i.category_id = c.id
                    LEFT JOIN suppliers s ON i.supplier_id = s.id
                    WHERE i.is_active = 1";
            $params = [];
            if ($search) {
                $sql .= " AND (i.name LIKE ? OR i.code LIKE ?)";
                $params[] = "%$search%"; $params[] = "%$search%";
            }
            if ($category) {
                $sql .= " AND i.category_id = ?";
                $params[] = $category;
            }
            if ($low_stock === '1') {
                $sql .= " AND i.stock <= i.min_stock";
            }
            $sql .= " ORDER BY i.name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT i.*, u.symbol as unit_symbol, c.name as category_name FROM items i JOIN units u ON i.unit_id=u.id JOIN categories c ON i.category_id=c.id WHERE i.id=?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            echo json_encode(['success' => true, 'berhasil' => (bool)$item, 'data' => $item]);
        } elseif ($action === 'transactions') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT st.*, u.name as user_name FROM stock_transactions st LEFT JOIN users u ON st.created_by=u.id WHERE st.item_id=? ORDER BY st.created_at DESC LIMIT 20");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO items (code, name, category_id, unit_id, supplier_id, description, stock, min_stock, max_stock, purchase_price, selling_price, location) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['code'], $data['name'], $data['category_id'], $data['unit_id'],
                $data['supplier_id'] ?: null, $data['description'] ?? '',
                $data['stock'] ?? 0, $data['min_stock'] ?? 0, $data['max_stock'] ?? 0,
                $data['purchase_price'] ?? 0, $data['selling_price'] ?? 0, $data['location'] ?? ''
            ]);
            $newId = $pdo->lastInsertId();
            // Log initial stock
            if (($data['stock'] ?? 0) > 0) {
                $pdo->prepare("INSERT INTO stock_transactions (item_id, type, reference_type, quantity, stock_before, stock_after, notes, created_by) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$newId, 'in', 'initial', $data['stock'], 0, $data['stock'], 'Stok awal', $_SESSION['user_id']]);
            }
            echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Item berhasil ditambahkan', 'message' => 'Item berhasil ditambahkan', 'id' => $newId]);
        } elseif ($action === 'stock_in') {
            $itemId = (int)$data['item_id'];
            $qty = (float)$data['quantity'];
            $stmt = $pdo->prepare("SELECT stock FROM items WHERE id=?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            $before = (float)$item['stock'];
            $after = $before + $qty;
            $pdo->prepare("UPDATE items SET stock=? WHERE id=?")->execute([$after, $itemId]);
            $pdo->prepare("INSERT INTO stock_transactions (item_id, type, reference_type, quantity, stock_before, stock_after, unit_price, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$itemId, 'in', 'purchase', $qty, $before, $after, $data['unit_price'] ?? 0, $data['notes'] ?? '', $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Stok masuk berhasil dicatat', 'message' => 'Stok masuk berhasil dicatat']);
        } elseif ($action === 'stock_out') {
            $itemId = (int)$data['item_id'];
            $qty = (float)$data['quantity'];
            $stmt = $pdo->prepare("SELECT stock FROM items WHERE id=?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            $before = (float)$item['stock'];
            if ($qty > $before) {
                echo json_encode(['success' => false, 'berhasil' => false, 'pesan' => 'Stok tidak mencukupi', 'message' => 'Stok tidak mencukupi']);
                exit;
            }
            $after = $before - $qty;
            $pdo->prepare("UPDATE items SET stock=? WHERE id=?")->execute([$after, $itemId]);
            $pdo->prepare("INSERT INTO stock_transactions (item_id, type, reference_type, quantity, stock_before, stock_after, notes, created_by) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$itemId, 'out', 'order', $qty, $before, $after, $data['notes'] ?? '', $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Stok keluar berhasil dicatat', 'message' => 'Stok keluar berhasil dicatat']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE items SET name=?, category_id=?, unit_id=?, supplier_id=?, description=?, min_stock=?, max_stock=?, purchase_price=?, selling_price=?, location=? WHERE id=?");
        $stmt->execute([
            $data['name'], $data['category_id'], $data['unit_id'],
            $data['supplier_id'] ?: null, $data['description'] ?? '',
            $data['min_stock'] ?? 0, $data['max_stock'] ?? 0,
            $data['purchase_price'] ?? 0, $data['selling_price'] ?? 0,
            $data['location'] ?? '', $id
        ]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Item berhasil diupdate', 'message' => 'Item berhasil diupdate']);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE items SET is_active=0 WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true, 'berhasil' => true, 'pesan' => 'Item berhasil dihapus', 'message' => 'Item berhasil dihapus']);
        break;
}
