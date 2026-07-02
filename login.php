<!--
════════════════════════════════════════════════════════════════
  HALAMAN LOGIN - Sistem Inventory Percetakan
════════════════════════════════════════════════════════════════
-->
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error      = '';
$loginOk    = false;
$userName   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $pdo      = db();
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id_users'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id_users = ?")->execute([$user['id_users']]);
            $loginOk  = true;
            $userName = $user['name'];
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
    <title>Login — Ranum Indocraft</title>
    <link rel="icon" type="image/png" href="logo.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #080818;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        /* ── Aurora Background ──────────────────────────────── */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 15% 0%,   rgba(99,102,241,0.22) 0%, transparent 55%),
                radial-gradient(ellipse 60% 50% at 85% 10%,  rgba(139,92,246,0.16) 0%, transparent 55%),
                radial-gradient(ellipse 70% 55% at 50% 100%, rgba(6,182,212,0.12)  0%, transparent 60%);
            animation: aurora 15s ease-in-out infinite alternate;
            pointer-events: none;
        }
        @keyframes aurora {
            0%   { opacity: .7; transform: scale(1); }
            50%  { opacity: 1;  transform: scale(1.04); }
            100% { opacity: .8; transform: scale(1); }
        }

        /* ── Floating particles ─────────────────────────────── */
        .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            background: rgba(99,102,241,0.6);
            border-radius: 50%;
            animation: float linear infinite;
        }
        @keyframes float {
            from { transform: translateY(100vh) translateX(0); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: .4; }
            to   { transform: translateY(-10vh) translateX(60px); opacity: 0; }
        }

        /* ── Login Card ─────────────────────────────────────── */
        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 380px;
        }

        .login-container {
            background: rgba(19,19,42,0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 20px;
            border: 1px solid rgba(99,102,241,0.18);
            padding: 32px 32px 28px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.7), 0 0 60px rgba(99,102,241,0.1);
            animation: cardIn 0.7s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
        .login-container.shake {
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%     { transform: translateX(-8px); }
            40%     { transform: translateX(8px); }
            60%     { transform: translateX(-6px); }
            80%     { transform: translateX(6px); }
        }

        /* ── Logo ───────────────────────────────────────────── */
        .logo { text-align: center; margin-bottom: 24px; }
        .logo-icon {
            width: 64px; height: 64px;
            margin: 0 auto 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1));
            border: 1px solid rgba(99,102,241,0.2);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 40px rgba(99,102,241,0.2), inset 0 1px 0 rgba(255,255,255,0.05);
            overflow: hidden;
        }
        .logo h1 {
            color: #f1f5f9; font-size: 20px; font-weight: 800;
            letter-spacing: -0.3px; margin-bottom: 3px;
        }
        .logo p { color: #475569; font-size: 12px; }

        /* ── Alert ──────────────────────────────────────────── */
        .alert {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex; align-items: center; gap: 8px;
            animation: alertIn .3s ease;
        }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Form ───────────────────────────────────────────── */
        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block; color: #64748b;
            font-size: 11px; font-weight: 700;
            margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #475569; font-size: 15px; pointer-events: none;
            transition: color .3s;
        }
        .form-control {
            width: 100%;
            background: rgba(8,8,24,0.7);
            border: 1px solid rgba(99,102,241,0.15);
            border-radius: 10px;
            color: #f1f5f9;
            padding: 13px 16px 13px 42px;
            font-size: 14px; font-family: inherit;
            transition: all .3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15), 0 0 20px rgba(99,102,241,0.08);
            background: rgba(8,8,24,0.9);
        }
        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: #818cf8; }
        .form-control::placeholder { color: #334155; }

        /* ── Toggle password ────────────────────────────────── */
        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #475569; cursor: pointer; font-size: 15px;
            transition: color .2s; background: none; border: none; padding: 0;
        }
        .toggle-pw:hover { color: #818cf8; }

        /* ── Submit Button ──────────────────────────────────── */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white; border: none; border-radius: 10px;
            padding: 14px; font-size: 15px; font-weight: 700;
            cursor: pointer; margin-top: 8px;
            transition: all .3s ease;
            box-shadow: 0 4px 20px rgba(99,102,241,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            letter-spacing: 0.2px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            position: relative; overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            opacity: 0; transition: opacity .3s;
        }
        .btn-login:hover { box-shadow: 0 6px 30px rgba(99,102,241,0.6); transform: translateY(-2px); }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn-login .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white; border-radius: 50%;
            animation: spin .6s linear infinite; display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .footer { text-align: center; margin-top: 20px; color: #334155; font-size: 12px; }

        /* ── SUCCESS TRANSITION OVERLAY ─────────────────────── */
        #login-overlay {
            position: fixed; inset: 0; z-index: 9999;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 20px;
            opacity: 0; pointer-events: none;
            transition: opacity .4s ease;
        }
        #login-overlay.show { opacity: 1; pointer-events: all; }

        /* Ripple circle expansion */
        .overlay-ripple {
            position: absolute; inset: 0;
            background: radial-gradient(circle at center, #6366f1 0%, #4f46e5 30%, #080818 70%);
            transform: scale(0);
            border-radius: 50%;
            transition: transform 0.8s cubic-bezier(0.4,0,0.2,1);
        }
        #login-overlay.show .overlay-ripple {
            transform: scale(4);
            border-radius: 0;
        }

        /* Content inside overlay */
        .overlay-content {
            position: relative; z-index: 1;
            text-align: center;
            opacity: 0; transform: translateY(16px);
            transition: opacity .4s ease .5s, transform .4s ease .5s;
        }
        #login-overlay.show .overlay-content { opacity: 1; transform: translateY(0); }

        .overlay-icon {
            width: 72px; height: 72px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            border: 2px solid rgba(255,255,255,0.3);
            animation: none;
        }
        #login-overlay.show .overlay-icon { animation: iconPop .5s ease .4s both; }
        @keyframes iconPop {
            from { transform: scale(0.5); opacity: 0; }
            60%  { transform: scale(1.15); }
            to   { transform: scale(1);   opacity: 1; }
        }
        .overlay-icon i { font-size: 34px; color: white; }

        .overlay-welcome {
            font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 6px;
        }
        .overlay-name {
            font-size: 28px; font-weight: 900; color: white;
            letter-spacing: -0.5px;
        }
        .overlay-sub { font-size: 13px; color: rgba(255,255,255,0.5); margin-top: 6px; }

        /* Loading dots */
        .overlay-dots { display: flex; gap: 6px; justify-content: center; margin-top: 24px; }
        .overlay-dots span {
            width: 6px; height: 6px; border-radius: 50%;
            background: rgba(255,255,255,0.4);
            animation: dotPulse 1.2s ease-in-out infinite;
        }
        .overlay-dots span:nth-child(2) { animation-delay: .2s; }
        .overlay-dots span:nth-child(3) { animation-delay: .4s; }
        @keyframes dotPulse {
            0%,80%,100% { transform: scale(.7); opacity: .4; }
            40%          { transform: scale(1);  opacity: 1; }
        }
    </style>
</head>
<body>

<!-- Floating particles -->
<div class="particles" id="particles"></div>

<!-- Login Card -->
<div class="login-wrap">
    <div class="login-container" id="login-card">
        <div class="logo">
            <div class="logo-icon">
                <img src="logo.png" alt="Logo" style="width:52px;height:52px;object-fit:contain;border-radius:10px">
            </div>
            <h1>Ranum Indocraft</h1>
            <p>Sistem Inventory &amp; Monitoring</p>
        </div>

        <?php if ($error): ?>
        <div class="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="login-form" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-wrap">
                    <input type="email" name="email" class="form-control"
                        placeholder="nama@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required autofocus id="input-email">
                    <i class="bi bi-envelope input-icon" style="left:14px;top:50%;transform:translateY(-50%);position:absolute;pointer-events:none"></i>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" class="form-control"
                        placeholder="Masukkan password"
                        required id="input-password">
                    <i class="bi bi-lock input-icon" style="left:14px;top:50%;transform:translateY(-50%);position:absolute;pointer-events:none"></i>
                    <button type="button" class="toggle-pw" onclick="togglePassword()" id="toggle-pw-btn">
                        <i class="bi bi-eye" id="pw-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btn-login">
                <div class="spinner" id="btn-spinner"></div>
                <i class="bi bi-box-arrow-in-right" id="btn-icon"></i>
                <span id="btn-text">Login Sekarang</span>
            </button>
        </form>

        <div class="footer">&copy; <?= date('Y') ?> Ranum Indocraft</div>
    </div>
</div>

<!-- Success Transition Overlay -->
<div id="login-overlay">
    <div class="overlay-ripple"></div>
    <div class="overlay-content">
        <div class="overlay-icon">
            <i class="bi bi-check-lg"></i>
        </div>
        <div class="overlay-welcome">Selamat datang kembali</div>
        <div class="overlay-name" id="overlay-name"><?= htmlspecialchars($userName) ?></div>
        <div class="overlay-sub">Mengarahkan ke dashboard...</div>
        <div class="overlay-dots">
            <span></span><span></span><span></span>
        </div>
    </div>
</div>

<script>
// ── Floating particles ────────────────────────────────────
(function() {
    const container = document.getElementById('particles');
    const count = 18;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            left: ${Math.random() * 100}%;
            width: ${Math.random() * 3 + 1}px;
            height: ${Math.random() * 3 + 1}px;
            opacity: ${Math.random() * 0.6 + 0.2};
            animation-duration: ${Math.random() * 12 + 10}s;
            animation-delay: ${Math.random() * -15}s;
        `;
        container.appendChild(p);
    }
})();

// ── Toggle password ───────────────────────────────────────
function togglePassword() {
    const input = document.getElementById('input-password');
    const icon  = document.getElementById('pw-eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// ── Handle login submit ───────────────────────────────────
function handleLogin(e) {
    const btn     = document.getElementById('btn-login');
    const spinner = document.getElementById('btn-spinner');
    const icon    = document.getElementById('btn-icon');
    const text    = document.getElementById('btn-text');

    btn.disabled        = true;
    spinner.style.display = 'block';
    icon.style.display    = 'none';
    text.textContent      = 'Memproses...';
    // Form submit berlanjut normal (tidak preventDefault)
}

// ── Login success transition ──────────────────────────────
<?php if ($loginOk): ?>
window.addEventListener('DOMContentLoaded', () => {
    // Kecil delay agar halaman render dulu
    setTimeout(() => {
        const overlay = document.getElementById('login-overlay');
        overlay.classList.add('show');
        // Redirect setelah animasi selesai
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 1800);
    }, 150);
});
<?php elseif ($error): ?>
// Shake card jika error
window.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('login-card');
    card.classList.add('shake');
    setTimeout(() => card.classList.remove('shake'), 500);
});
<?php endif; ?>
</script>
</body>
</html>
