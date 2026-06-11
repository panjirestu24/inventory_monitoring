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
        if ($action === 'list' || $action === '') {
            $stmt = $pdo->query("SELECT * FROM customers WHERE is_active=1 ORDER BY name");
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            echo json_encode(['success' => true, 'berhasil' => (bool)$customer, 'data' => $customer]);
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
