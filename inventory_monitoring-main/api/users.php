<?php
// ============================================================
// API USERS - Kelola User (Admin Only)
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Akses ditolak. Hanya admin yang bisa mengelola user.']);
    exit;
}

require_once '../config/database.php';
$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($method) {

    // ── GET ──────────────────────────────────────────────────
    case 'GET':
        if ($action === 'list') {
            $search = $_GET['search'] ?? '';
            $role   = $_GET['role']   ?? '';
            $sql    = "SELECT id_users, name, email, role, is_active, last_login, created_at FROM users WHERE 1=1";
            $params = [];
            if ($search) {
                $sql .= " AND (name LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }
            $sql .= " ORDER BY role ASC, name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'berhasil' => true, 'data' => $stmt->fetchAll()]);

        } elseif ($action === 'get') {
            $id   = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT id_users, name, email, role, is_active FROM users WHERE id_users = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            echo json_encode(['success' => (bool)$user, 'berhasil' => (bool)$user, 'data' => $user]);
        }
        break;

    // ── POST: Tambah user baru ────────────────────────────────
    case 'POST':
        $data  = json_decode(file_get_contents('php://input'), true) ?? [];
        $name  = trim($data['name']  ?? '');
        $email = trim($data['email'] ?? '');
        $pass  = trim($data['password'] ?? '');
        $role  = $data['role'] ?? 'operator';

        if (!$name || !$email || !$pass) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Nama, email, dan password wajib diisi']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Format email tidak valid']);
            exit;
        }
        if (strlen($pass) < 6) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Password minimal 6 karakter']);
            exit;
        }
        if (!in_array($role, ['admin', 'operator'])) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Role tidak valid']);
            exit;
        }

        // Cek email duplikat
        $chk = $pdo->prepare("SELECT id_users FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Email sudah terdaftar']);
            exit;
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $role]);
        echo json_encode(['success' => true, 'berhasil' => true, 'message' => 'User berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
        break;

    // ── PUT: Edit user ────────────────────────────────────────
    case 'PUT':
        $data  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int)($data['id'] ?? 0);
        $name  = trim($data['name']  ?? '');
        $email = trim($data['email'] ?? '');
        $role  = $data['role'] ?? '';
        $pass  = trim($data['password'] ?? '');
        $active = isset($data['is_active']) ? (int)$data['is_active'] : null;

        if (!$id) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'ID tidak valid']);
            exit;
        }

        // Cari user yang sedang diedit
        $cur = $pdo->prepare("SELECT id_users, role FROM users WHERE id_users = ?");
        $cur->execute([$id]);
        $curUser = $cur->fetch();
        if (!$curUser) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'User tidak ditemukan']);
            exit;
        }

        // Cegah admin menonaktifkan atau mengubah role dirinya sendiri
        if ($id === (int)$_SESSION['user_id']) {
            if ($active === 0) {
                echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Tidak bisa menonaktifkan akun sendiri']);
                exit;
            }
        }

        // Cek duplikat email (selain dirinya)
        if ($email) {
            $chk = $pdo->prepare("SELECT id_users FROM users WHERE email = ? AND id_users != ?");
            $chk->execute([$email, $id]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Email sudah digunakan user lain']);
                exit;
            }
        }

        // Build query dinamis
        $sets   = [];
        $params = [];
        if ($name)  { $sets[] = "name = ?";     $params[] = $name; }
        if ($email) { $sets[] = "email = ?";    $params[] = $email; }
        if ($role && in_array($role, ['admin','operator'])) {
            $sets[] = "role = ?"; $params[] = $role;
        }
        if ($active !== null) { $sets[] = "is_active = ?"; $params[] = $active; }
        if ($pass && strlen($pass) >= 6) {
            $sets[] = "password = ?";
            $params[] = password_hash($pass, PASSWORD_BCRYPT);
        }

        if (empty($sets)) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Tidak ada data yang diubah']);
            exit;
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id_users = ?";
        $pdo->prepare($sql)->execute($params);
        echo json_encode(['success' => true, 'berhasil' => true, 'message' => 'User berhasil diupdate']);
        break;

    // ── DELETE: Hard delete ───────────────────────────────────
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);

        // Cegah hapus diri sendiri
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success' => false, 'berhasil' => false, 'message' => 'Tidak bisa menghapus akun sendiri']);
            exit;
        }

        $pdo->prepare("DELETE FROM users WHERE id_users = ?")->execute([$id]);
        echo json_encode(['success' => true, 'berhasil' => true, 'message' => 'User berhasil dihapus permanen']);
        break;
}
