<?php
// ============================================================
// API DELIVERIES - Manajemen Pengiriman Order
// ============================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$action = $_GET['action'] ?? 'list';

// Track endpoint tidak butuh auth (publik)
if ($action !== 'track') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

header('Content-Type: application/json');
require_once '../config/database.php';
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET ──────────────────────────────────────────────────
    case 'GET':
        if ($action === 'list') {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $sql = "SELECT d.*, o.order_number, o.title as order_title, o.status as order_status,
                           c.name as customer_name, c.phone as customer_phone,
                           u.name as created_by_name
                    FROM deliveries d
                    JOIN orders o ON d.order_id = o.id_orders
                    JOIN customers c ON o.customer_id = c.id_customers
                    LEFT JOIN users u ON d.created_by = u.id_users
                    WHERE 1=1";
            $params = [];
            // Support multi-status: statuses[] = ['prepared','shipping',...]
            $multiStatuses = $_GET['statuses'] ?? [];
            if (!empty($multiStatuses) && is_array($multiStatuses)) {
                $placeholders = implode(',', array_fill(0, count($multiStatuses), '?'));
                $sql .= " AND d.status IN ($placeholders)";
                $params = array_merge($params, $multiStatuses);
            } elseif ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }
            if ($search) {
                $sql .= " AND (o.order_number LIKE ? OR c.name LIKE ? OR d.destination_address LIKE ?)";
                $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
            }
            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare(
                "SELECT d.*, o.order_number, o.title as order_title,
                        c.name as customer_name, c.phone as customer_phone
                 FROM deliveries d
                 JOIN orders o ON d.order_id = o.id_orders
                 JOIN customers c ON o.customer_id = c.id_customers
                 WHERE d.id_deliveries = ?"
            );
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

        } elseif ($action === 'by_order') {
            $orderId = (int)($_GET['order_id'] ?? 0);
            $stmt = $pdo->prepare(
                "SELECT d.* FROM deliveries d WHERE d.order_id = ? ORDER BY d.created_at DESC LIMIT 1"
            );
            $stmt->execute([$orderId]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);

        } elseif ($action === 'track') {
            // PUBLIC: cek status pesanan tanpa login
            $orderNum = trim($_GET['order_number'] ?? '');
            if (!$orderNum) {
                echo json_encode(['success' => false, 'message' => 'Nomor order diperlukan']);
                exit;
            }
            $stmt = $pdo->prepare(
                "SELECT o.order_number, o.title, o.status as order_status, o.priority,
                        o.quantity, o.grand_total, o.start_date, o.due_date, o.completed_date,
                        o.notes as order_notes,
                        c.name as customer_name,
                        d.id_deliveries as delivery_id, d.status as delivery_status,
                        d.destination_address, d.destination_city,
                        d.recipient_name, d.recipient_phone,
                        d.estimated_arrival, d.actual_arrival,
                        d.proof_image,
                        d.notes as delivery_notes, d.created_at as delivery_created
                 FROM orders o
                 JOIN customers c ON o.customer_id = c.id_customers
                 LEFT JOIN deliveries d ON d.order_id = o.id_orders
                 WHERE o.order_number = ?"
            );
            $stmt->execute([$orderNum]);
            $row = $stmt->fetch();
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Nomor order tidak ditemukan']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $row]);
            exit;
        }
        break;

    // ── POST ─────────────────────────────────────────────────
    case 'POST':
        // Upload bukti foto + konfirmasi diterima
        if ($action === 'confirm_received') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT status, proof_image FROM deliveries WHERE id_deliveries = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();

            if (!$current) {
                echo json_encode(['success' => false, 'message' => 'Data pengiriman tidak ditemukan']);
                exit;
            }
            if ($current['status'] !== 'arrived') {
                echo json_encode(['success' => false, 'message' => 'Status harus Tiba di Tujuan sebelum dikonfirmasi diterima (saat ini: ' . $current['status'] . ')']);
                exit;
            }

            if (empty($_FILES['proof_image']['name'])) {
                echo json_encode(['success' => false, 'message' => 'Foto bukti wajib diupload']);
                exit;
            }

            $file    = $_FILES['proof_image'];
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024;

            if (!in_array($file['type'], $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Format foto harus JPG, PNG, atau WEBP']);
                exit;
            }
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'Ukuran foto maksimal 5MB']);
                exit;
            }

            $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename  = 'proof_' . $id . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/proof/';

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan foto. Periksa permission folder uploads/proof/']);
                exit;
            }

            // Hapus foto lama jika ada
            if (!empty($current['proof_image'])) {
                $old = $uploadDir . $current['proof_image'];
                if (file_exists($old)) @unlink($old);
            }

            $stmt = $pdo->prepare(
                "UPDATE deliveries
                 SET status = 'received', proof_image = ?,
                     actual_arrival = CASE WHEN actual_arrival IS NULL THEN NOW() ELSE actual_arrival END,
                     updated_at = NOW()
                 WHERE id_deliveries = ?"
            );
            $stmt->execute([$filename, $id]);
            echo json_encode(['success' => true, 'message' => 'Pengiriman dikonfirmasi diterima', 'proof_image' => $filename]);
            exit;
        }

        // Buat pengiriman baru
        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $orderId = (int)($data['order_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT id_orders FROM orders WHERE id_orders = ?");
        $stmt->execute([$orderId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id_deliveries FROM deliveries WHERE order_id = ?");
        $stmt->execute([$orderId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Pengiriman untuk order ini sudah dibuat']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO deliveries
             (order_id, status, destination_address, destination_city,
              recipient_name, recipient_phone, estimated_arrival, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $orderId, 'prepared',
            $data['destination_address'] ?? '',
            $data['destination_city']    ?? '',
            $data['recipient_name']      ?? '',
            $data['recipient_phone']     ?? '',
            $data['estimated_arrival']   ?? null,
            $data['notes']               ?? '',
            $_SESSION['user_id'],
        ]);
        echo json_encode(['success' => true, 'message' => 'Pengiriman berhasil dibuat', 'id' => $pdo->lastInsertId()]);
        break;

    // ── PUT: update status biasa (prepared/shipping/arrived) ──
    case 'PUT':
        $data      = json_decode(file_get_contents('php://input'), true) ?? [];
        $id        = (int)($data['id'] ?? 0);
        $newStatus = $data['status'] ?? '';

        if (!in_array($newStatus, ['prepared', 'shipping', 'arrived'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            exit;
        }

        $arrivalSql = $newStatus === 'arrived'
            ? ", actual_arrival = CASE WHEN actual_arrival IS NULL THEN NOW() ELSE actual_arrival END"
            : "";

        $stmt = $pdo->prepare("UPDATE deliveries SET status = ?, updated_at = NOW() $arrivalSql WHERE id_deliveries = ?");
        $stmt->execute([$newStatus, $id]);
        echo json_encode(['success' => true, 'message' => 'Status diupdate ke ' . $newStatus]);
        break;
}
