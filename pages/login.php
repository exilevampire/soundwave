<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login or register

// ─── Handle Login ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'กรุณากรอกข้อมูลให้ครบครับ';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            } else {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้องครับ';
            }
        }
    }

    if ($_POST['action'] === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (!$username || !$email || !$password) {
            $error = 'กรุณากรอกข้อมูลให้ครบครับ';
        } elseif ($password !== $confirm) {
            $error = 'รหัสผ่านไม่ตรงกันครับ';
        } elseif (strlen($password) < 6) {
            $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษรครับ';
        } else {
            $db = getDB();
            // Check duplicate
            $check = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check->execute([$email, $username]);
            if ($check->fetch()) {
                $error = 'อีเมลหรือชื่อผู้ใช้นี้มีอยู่แล้วครับ';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $insert->execute([$username, $email, $hashed]);
                $success = 'สมัครสมาชิกสำเร็จแล้วครับ! กรุณาเข้าสู่ระบบ';
                $mode = 'login';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — <?= $mode === 'login' ? 'เข้าสู่ระบบ' : 'สมัครสมาชิก' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0D0F1A;
            --card: #13152A;
            --accent: #4F6EF7;
            --accent-pink: #F74F8E;
            --text: #E8ECFF;
            --muted: #6B7299;
            --border: rgba(255,255,255,0.07);
            --glow: rgba(79,110,247,0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(ellipse at 20% 50%, rgba(79,110,247,0.08) 0%, transparent 60%),
                              radial-gradient(ellipse at 80% 20%, rgba(247,79,142,0.06) 0%, transparent 50%);
        }
        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logo p { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .tabs {
            display: flex;
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 28px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            color: var(--muted);
            text-decoration: none;
            transition: all 0.2s;
        }
        .tab.active {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 12px var(--glow);
        }
        .form-group { margin-bottom: 16px; }
        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--glow);
        }
        input::placeholder { color: var(--muted); }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent), #6B8AF9);
            border: none;
            border-radius: 12px;
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            box-shadow: 0 8px 24px var(--glow);
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 12px 32px var(--glow); }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .alert.error { background: rgba(247,79,142,0.12); color: #F74F8E; border: 1px solid rgba(247,79,142,0.2); }
        .alert.success { background: rgba(16,185,129,0.12); color: #10B981; border: 1px solid rgba(16,185,129,0.2); }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <h1>🎵 SoundWave</h1>
        <p>Your personal music universe</p>
    </div>
    <div class="card">
        <div class="tabs">
            <a href="?mode=login" class="tab <?= $mode==='login'?'active':'' ?>">เข้าสู่ระบบ</a>
            <a href="?mode=register" class="tab <?= $mode==='register'?'active':'' ?>">สมัครสมาชิก</a>
        </div>

        <?php if ($error): ?><div class="alert error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if ($mode === 'login'): ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>อีเมล</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">เข้าสู่ระบบ →</button>
        </form>

        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username" placeholder="yourname" required>
            </div>
            <div class="form-group">
                <label>อีเมล</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="password" placeholder="อย่างน้อย 6 ตัว" required>
            </div>
            <div class="form-group">
                <label>ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">สมัครสมาชิก →</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
