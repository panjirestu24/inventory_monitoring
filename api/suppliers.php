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
            $stmt = $pdo->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name");
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);
        } elseif ($action === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id=?");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch();
            echo json_encode(['success' => true, 'berhasil' => (bool)$supplier, 'data' => $supplier]);
        }
        break;
    
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO suppliers (code, name, contact_person, phone, email, city, address, notes) VALUES (?,?,?,?,?,?,?,?)");
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
        echo json_encode(['berhasil' => true, 'success' => true, 'pesan' => 'Supplier berhasil ditambahkan', 'message' => 'Supplier berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
        break;
    
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, city=?, address=?, notes=? WHERE id=?");
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
        echo json_encode(['berhasil' => true, 'success' => true, 'pesan' => 'Supplier berhasil diupdate', 'message' => 'Supplier berhasil diupdate']);
        break;
    
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        $pdo->prepare("UPDATE suppliers SET is_active=0 WHERE id=?")->execute([$id]);
        echo json_encode(['berhasil' => true, 'success' => true, 'pesan' => 'Supplier berhasil dihapus', 'message' => 'Supplier berhasil dihapus']);
        break;
}
