<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config/database.php';
$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM suppliers WHERE is_active=1 ORDER BY name");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO suppliers (code, name, contact_person, phone, email, address, city) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$data['code'], $data['name'], $data['contact_person']??'', $data['phone']??'', $data['email']??'', $data['address']??'', $data['city']??'']);
    echo json_encode(['success' => true, 'message' => 'Supplier berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
}
