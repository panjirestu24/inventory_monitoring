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
                 LEFT JOIN categories c ON p.category_id = c.id_categories
                 LEFT JOIN units u ON p.unit_id = u.id_units
                 WHERE p.is_active = 1
                 ORDER BY c.name, p.name"
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'all') {
            $stmt = $pdo->query(
                "SELECT p.*, c.name as category_name, u.symbol as unit_symbol
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id_categories
                 LEFT JOIN units u ON p.unit_id = u.id_units
                 ORDER BY c.name, p.name"
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'get') {
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id_products = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

        } elseif ($action === 'get_materials') {
            $id = (int)($_GET['id'] ?? 0);
            try {
                $stmt = $pdo->prepare(
                    "SELECT pm.*, i.name as item_name, i.code as item_code,
                            u.symbol as unit_symbol, i.stock as current_stock
                     FROM product_materials pm
                     JOIN items i ON pm.item_id = i.id_items
                     JOIN units u ON i.unit_id = u.id_units
                     WHERE pm.product_id = ?
                     ORDER BY i.name"
                );
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            } catch (Exception $e) {
                // Tabel belum ada — return kosong
                echo json_encode(['success' => true, 'data' => []]);
            }

        }
        break;

    case 'POST':
        // Simpan BOM (tidak butuh role admin check karena sudah di GET handler)
        if ($action === 'save_materials') {
            $id   = (int)($_GET['id'] ?? 0);
            $body = json_decode(file_get_contents('php://input'), true);
            $mats = $body['materials'] ?? [];

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']); exit;
            }

            try {
                // Buat tabel jika belum ada (tanpa FK agar tidak error)
                $pdo->exec("CREATE TABLE IF NOT EXISTS `product_materials` (
                    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id`   INT(11) UNSIGNED NOT NULL,
                    `item_id`      INT(11) UNSIGNED NOT NULL,
                    `qty_per_unit` DECIMAL(12,4) NOT NULL DEFAULT 1,
                    `notes`        VARCHAR(200) DEFAULT NULL,
                    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_product_item` (`product_id`, `item_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->prepare("DELETE FROM product_materials WHERE product_id = ?")->execute([$id]);

                // Insert BOM baru
                $ins = $pdo->prepare(
                    "INSERT INTO product_materials (product_id, item_id, qty_per_unit, notes) VALUES (?,?,?,?)"
                );
                $count = 0;
                foreach ($mats as $m) {
                    $itemId = (int)($m['item_id'] ?? 0);
                    $qty    = (float)($m['qty_per_unit'] ?? 0);
                    if ($itemId <= 0 || $qty <= 0) continue;
                    $ins->execute([$id, $itemId, $qty, $m['notes'] ?? '']);
                    $count++;
                }

                echo json_encode([
                    'success' => true,
                    'message' => "BOM berhasil disimpan ($count bahan)",
                    'count'   => $count
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ]);
            }
            exit;
        }

        // Hanya admin untuk tambah produk baru
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
            "UPDATE products SET name=?, category_id=?, unit_id=?, default_price=?, description=?, is_active=? WHERE id_products=?"
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
        $pdo->prepare("UPDATE products SET is_active = 0 WHERE id_products = ?")->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Produk berhasil dinonaktifkan']);
        break;
}
