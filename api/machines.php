<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
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
            $stmt = $pdo->query("SELECT m.*, o.order_number, o.title as current_job, o.priority, u.name as operator_name FROM machines m LEFT JOIN orders o ON m.id=o.machine_id AND o.status='in_progress' LEFT JOIN users u ON o.operator_id=u.id ORDER BY m.code");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'timestamp' => date('H:i:s')]);
        } elseif ($action === 'logs') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT ml.*, u.name as operator_name, o.order_number FROM machine_logs ml LEFT JOIN users u ON ml.operator_id=u.id LEFT JOIN orders o ON ml.order_id=o.id WHERE ml.machine_id=? ORDER BY ml.created_at DESC LIMIT 20");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        $pdo->prepare("UPDATE machines SET status=?, notes=? WHERE id=?")->execute([$data['status'], $data['notes'] ?? '', $id]);
        // Log the event
        $eventMap = ['active'=>'start','idle'=>'stop','maintenance'=>'maintenance_start','offline'=>'stop'];
        $event = $eventMap[$data['status']] ?? 'stop';
        $pdo->prepare("INSERT INTO machine_logs (machine_id, order_id, event, description, operator_id) VALUES (?,?,?,?,?)")
            ->execute([$id, $data['order_id'] ?? null, $event, $data['description'] ?? '', $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Status mesin diupdate']);
        break;
}
