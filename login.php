<!--
════════════════════════════════════════════════════════════════
  HALAMAN LOGIN - Sistem Inventory Percetakan
════════════════════════════════════════════════════════════════
-->
<?php
session_start();

// Kalau sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $pdo = db();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Cek password (untuk demo, kita pakai password sederhana)
        // Password default semua user: "password"
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            // Redirect ke dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Harap isi email dan password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventory Percetakan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            background: #1e1e35;
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.6);
            border: 1px solid rgba(99,102,241,0.15);
            padding: 40px;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(99,102,241,0.4);
        }
        
        .logo h1 {
            color: #f1f5f9;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .logo p {
            color: #94a3b8;
            font-size: 13px;
        }
        
        .alert {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            background: #12121f;
            border: 1px solid rgba(99,102,241,0.15);
            border-radius: 8px;
            color: #f1f5f9;
            padding: 12px 16px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        
        .form-control::placeholder {
            color: #475569;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        .btn-login:hover {
            box-shadow: 0 8px 24px rgba(99,102,241,0.5);
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .divider {
            height: 1px;
            background: rgba(99,102,241,0.15);
            margin: 32px 0;
        }
        
        .demo-info {
            background: rgba(6,182,212,0.1);
            border: 1px solid rgba(6,182,212,0.2);
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
        }
        
        .demo-info h3 {
            color: #22d3ee;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .demo-account {
            background: rgba(0,0,0,0.2);
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .demo-account:last-child {
            margin-bottom: 0;
        }
        
        .demo-account strong {
            color: #60a5fa;
        }
        
        .demo-account span {
            color: #94a3b8;
        }
        
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🖨️</div>
            <h1>Sistem Inventory</h1>
            <p>Percetakan Modern</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Masukkan password"
                    required
                >
            </div>
            
            <button type="submit" class="btn-login">
                🔐 Login Sekarang
            </button>
        </form>
        
        <div class="divider"></div>
        
        <div class="demo-info">
            <h3>🔑 Akun Demo</h3>
            <div class="demo-account">
                <strong>Admin:</strong> 
                <span>admin@percetakan.com</span>
            </div>
            <div class="demo-account">
                <strong>Operator:</strong> 
                <span>operator1@percetakan.com</span>
            </div>
            <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(6,182,212,0.2); color:#94a3b8; font-size:11px;">
                💡 Password semua akun: <strong style="color:#22d3ee">password</strong>
            </div>
        </div>
        
        <div class="footer">
            © 2024 Sistem Inventory Percetakan
        </div>
    </div>
</body>
</html>
