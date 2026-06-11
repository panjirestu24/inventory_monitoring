<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/database.php';
$pdo = db();
$stmt = $pdo->query("SELECT * FROM units ORDER BY name");
echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
