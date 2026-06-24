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
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            $stmt = $pdo->query(
                "SELECT p.*, c.name as category_name, u.symbol as unit_symbol
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 WHERE p.is_active = 1
                 ORDER BY c.name, p.name"
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'get') {
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

        } elseif ($action === 'all') {
            // Termasuk yang nonaktif (untuk halaman manajemen admin)
            $stmt = $pdo->query(
                "SELECT p.*, c.name as category_name, u.symbol as unit_symbol
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 ORDER BY c.name, p.name"
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        // Hanya admin
        if ($_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang bisa menambah produk']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);

        // Auto-generate kode
        $seq  = $pdo->query("SELECT COUNT(*)+1 FROM products")->fetchColumn();
        $code = $data['code'] ?? ('PRD' . str_pad($seq, 3, '0', STR_PAD_LEFT));

        $stmt = $pdo->prepare(
            "INSERT INTO products (code, name, category_id, unit_id, default_price, description)
             VALUES (?,?,?,?,?,?)"
        );
        $stmt->execute([
            $code,
            $data['name'],
            $data['category_id'] ?: null,
            $data['unit_id']     ?: null,
            $data['default_price'] ?? 0,
            $data['description']   ?? '',
        ]);
        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        if ($_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang bisa mengubah produk']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        $stmt = $pdo->prepare(
            "UPDATE products SET name=?, category_id=?, unit_id=?, default_price=?, description=?, is_active=? WHERE id=?"
        );
        $stmt->execute([
            $data['name'],
            $data['category_id'] ?: null,
            $data['unit_id']     ?: null,
            $data['default_price'] ?? 0,
            $data['description']   ?? '',
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $id,
        ]);
        echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
        break;

    case 'DELETE':
        if ($_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Hanya admin yang bisa menghapus produk']);
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Produk berhasil dinonaktifkan']);
        break;
}
