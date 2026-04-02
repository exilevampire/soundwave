<?php
require_once '../includes/config.php';
requireLogin();

$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $album  = trim($_POST['album'] ?? '');
    $genre  = trim($_POST['genre'] ?? '');

    if (!$title || !$artist) {
        $error = 'กรุณากรอกชื่อเพลงและศิลปินครับ';
    } elseif (empty($_FILES['song_file']['name'])) {
        $error = 'กรุณาเลือกไฟล์เพลงครับ';
    } else {
        // ─── Upload Song File ───
        $songFile = $_FILES['song_file'];
        $songExt  = strtolower(pathinfo($songFile['name'], PATHINFO_EXTENSION));

        if (!in_array($songExt, ALLOWED_AUDIO)) {
            $error = 'รองรับไฟล์ mp3, wav, ogg, m4a เท่านั้นครับ';
        } elseif ($songFile['size'] > MAX_FILE_SIZE) {
            $error = 'ไฟล์ใหญ่เกิน 50MB ครับ';
        } else {
            $songName = uniqid('song_') . '.' . $songExt;
            $songPath = UPLOAD_SONGS . $songName;

            if (!move_uploaded_file($songFile['tmp_name'], $songPath)) {
                $error = 'อัพโหลดไฟล์เพลงไม่สำเร็จครับ';
            } else {
                // ─── Upload Cover (optional) ───
                $coverName = null;
                if (!empty($_FILES['cover_file']['name'])) {
                    $coverFile = $_FILES['cover_file'];
                    $coverExt  = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                    if (in_array($coverExt, ALLOWED_IMAGE)) {
                        $coverName = uniqid('cover_') . '.' . $coverExt;
                        move_uploaded_file($coverFile['tmp_name'], UPLOAD_COVERS . $coverName);
                    }
                }

                // ─── Save to DB ───
                $db = getDB();
                $stmt = $db->prepare("
                    INSERT INTO songs (title, artist, album, genre, file_path, cover_path, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title, $artist, $album, $genre,
                    'uploads/songs/' . $songName,
                    $coverName ? 'uploads/covers/' . $coverName : null,
                    $user['id']
                ]);

                $success = "อัพโหลดเพลง \"{$title}\" สำเร็จแล้วครับ! 🎵";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Upload เพลง — <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0D0F1A; --card: #13152A; --accent: #4F6EF7;
            --accent-pink: #F74F8E; --text: #E8ECFF; --muted: #6B7299;
            --border: rgba(255,255,255,0.07); --glow: rgba(79,110,247,0.25);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; padding:40px 20px; }
        .wrap { max-width:560px; margin:0 auto; }
        .back { display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; font-size:13px; margin-bottom:28px; }
        .back:hover { color:var(--text); }
        h1 { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; margin-bottom:4px; }
        p.sub { color:var(--muted); font-size:13px; margin-bottom:32px; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:32px; }
        .form-group { margin-bottom:18px; }
        label { display:block; font-size:12px; color:var(--muted); margin-bottom:6px; font-weight:500; }
        input, select {
            width:100%; background:rgba(255,255,255,0.04); border:1px solid var(--border);
            border-radius:12px; padding:12px 16px; color:var(--text);
            font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:all 0.2s;
        }
        input:focus, select:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--glow); }
        select option { background:var(--card); }
        .file-zone {
            border:2px dashed var(--border); border-radius:14px; padding:28px;
            text-align:center; cursor:pointer; transition:all 0.2s; position:relative;
        }
        .file-zone:hover { border-color:var(--accent); background:rgba(79,110,247,0.04); }
        .file-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
        .file-icon { font-size:32px; margin-bottom:8px; }
        .file-label { font-size:13px; color:var(--muted); }
        .file-label span { color:var(--accent); }
        .file-name { font-size:12px; color:var(--accent); margin-top:6px; display:none; }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .btn {
            width:100%; padding:14px; background:linear-gradient(135deg,var(--accent),#6B8AF9);
            border:none; border-radius:12px; color:white; font-family:'DM Sans',sans-serif;
            font-size:15px; font-weight:600; cursor:pointer; margin-top:8px;
            box-shadow:0 8px 24px var(--glow); transition:all 0.2s;
        }
        .btn:hover { transform:translateY(-1px); }
        .alert { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; }
        .alert.error { background:rgba(247,79,142,0.12); color:#F74F8E; border:1px solid rgba(247,79,142,0.2); }
        .alert.success { background:rgba(16,185,129,0.12); color:#10B981; border:1px solid rgba(16,185,129,0.2); }
    </style>
</head>
<body>
<div class="wrap">
    <a href="<?= BASE_URL ?>/index.php" class="back">← กลับหน้าหลัก</a>
    <h1>🎵 Upload เพลง</h1>
    <p class="sub">เพิ่มเพลงของคุณเข้าสู่ระบบ รองรับ MP3, WAV, OGG, M4A</p>

    <?php if ($error): ?><div class="alert error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">

            <!-- Song File -->
            <div class="form-group">
                <label>ไฟล์เพลง *</label>
                <div class="file-zone" id="songZone">
                    <input type="file" name="song_file" accept=".mp3,.wav,.ogg,.m4a" id="songInput">
                    <div class="file-icon">🎵</div>
                    <div class="file-label">คลิกหรือลากไฟล์มาวางที่นี่<br><span>MP3, WAV, OGG, M4A</span> (สูงสุด 50MB)</div>
                    <div class="file-name" id="songName"></div>
                </div>
            </div>

            <!-- Cover Image -->
            <div class="form-group">
                <label>ภาพปก (ไม่บังคับ)</label>
                <div class="file-zone" id="coverZone">
                    <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp" id="coverInput">
                    <div class="file-icon">🖼️</div>
                    <div class="file-label">คลิกหรือลากไฟล์ภาพมาวาง<br><span>JPG, PNG, WEBP</span></div>
                    <div class="file-name" id="coverName"></div>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label>ชื่อเพลง *</label>
                    <input type="text" name="title" placeholder="Golden Sands" required>
                </div>
                <div class="form-group">
                    <label>ศิลปิน *</label>
                    <input type="text" name="artist" placeholder="Imagine Dragons" required>
                </div>
            </div>
            <div class="row">
                <div class="form-group">
                    <label>อัลบั้ม</label>
                    <input type="text" name="album" placeholder="Night Visions">
                </div>
                <div class="form-group">
                    <label>แนวเพลง</label>
                    <select name="genre">
                        <option value="">เลือกแนวเพลง</option>
                        <option>Pop</option><option>Rock</option><option>R&B</option>
                        <option>Hip-Hop</option><option>Electronic</option><option>Jazz</option>
                        <option>Classical</option><option>Country</option><option>Indie</option>
                        <option>Other</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn">⬆️ อัพโหลดเพลง</button>
        </form>
    </div>
</div>

<script>
document.getElementById('songInput').addEventListener('change', function() {
    const name = this.files[0]?.name || '';
    const el = document.getElementById('songName');
    el.textContent = '✅ ' + name;
    el.style.display = name ? 'block' : 'none';
});
document.getElementById('coverInput').addEventListener('change', function() {
    const name = this.files[0]?.name || '';
    const el = document.getElementById('coverName');
    el.textContent = '✅ ' + name;
    el.style.display = name ? 'block' : 'none';
});
</script>
</body>
</html>
