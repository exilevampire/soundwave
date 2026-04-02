<?php
require_once 'includes/config.php';
requireLogin();

$user = currentUser();
$db   = getDB();

// Get songs
$songs = $db->query("SELECT * FROM songs ORDER BY created_at DESC")->fetchAll();

// Get playlists
$playlists = $db->prepare("SELECT * FROM playlists WHERE user_id = ?");
$playlists->execute([$user['id']]);
$playlists = $playlists->fetchAll();

// Get liked songs IDs
$likedStmt = $db->prepare("SELECT song_id FROM liked_songs WHERE user_id = ?");
$likedStmt->execute([$user['id']]);
$likedIds = array_column($likedStmt->fetchAll(), 'song_id');

function formatDuration(int $secs): string {
    return sprintf('%d:%02d', floor($secs/60), $secs%60);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0D0F1A; --bg2: #13152A; --card: #181B2E; --hover: #1E2240;
            --accent: #4F6EF7; --glow: rgba(79,110,247,0.3); --pink: #F74F8E;
            --text: #E8ECFF; --sub: #6B7299; --muted: #3D4266;
            --border: rgba(255,255,255,0.05);
            --sidebar: 220px; --queue: 270px; --player: 72px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text);
            height:100vh; overflow:hidden;
            display:grid;
            grid-template-columns: var(--sidebar) 1fr var(--queue);
            grid-template-rows: 1fr var(--player);
        }
        ::-webkit-scrollbar { width:4px; }
        ::-webkit-scrollbar-thumb { background:var(--muted); border-radius:4px; }

        /* ── SIDEBAR ── */
        .sidebar {
            grid-row:1/2; background:var(--bg2); border-right:1px solid var(--border);
            display:flex; flex-direction:column; padding:24px 0; overflow-y:auto;
            transition: transform 0.3s ease;
        }
        .profile { display:flex; align-items:center; gap:12px; padding:0 18px 20px; border-bottom:1px solid var(--border); }
        .avatar { width:42px; height:42px; border-radius:50%; border:2px solid var(--accent); object-fit:cover; flex-shrink:0; background:var(--card); display:flex; align-items:center; justify-content:center; font-size:18px; }
        .uname { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; }
        .badge { display:inline-block; background:linear-gradient(135deg,var(--accent),var(--pink)); color:#fff; font-size:9px; font-weight:700; padding:2px 8px; border-radius:20px; margin-top:3px; }
        .nav { padding:18px 0; }
        .nav-item {
            display:flex; align-items:center; gap:10px; padding:9px 18px;
            color:var(--sub); font-size:13px; font-weight:500; cursor:pointer;
            border-left:3px solid transparent; transition:all 0.15s; text-decoration:none;
        }
        .nav-item:hover { color:var(--text); background:var(--hover); }
        .nav-item.active { color:var(--accent); border-left-color:var(--accent); background:rgba(79,110,247,0.07); }
        .section-head { padding:18px 18px 8px; font-size:10px; color:var(--muted); letter-spacing:1.2px; text-transform:uppercase; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
        .pl-item { display:flex; justify-content:space-between; align-items:center; padding:7px 18px; cursor:pointer; transition:background 0.15s; }
        .pl-item:hover { background:var(--hover); }
        .pl-name { font-size:12px; color:var(--sub); }
        .pl-count { font-size:11px; color:var(--muted); background:var(--hover); padding:2px 7px; border-radius:10px; }

        /* ── MAIN ── */
        .main { grid-row:1/2; overflow-y:auto; display:flex; flex-direction:column; min-width:0; }
        .topbar {
            position:sticky; top:0; z-index:10;
            background:rgba(13,15,26,0.9); backdrop-filter:blur(20px);
            padding:14px 24px; display:flex; align-items:center; justify-content:space-between;
            border-bottom:1px solid var(--border); gap:12px;
        }
        .search-wrap { position:relative; flex:1; max-width:320px; }
        .search-wrap input {
            background:var(--card); border:1px solid var(--border); border-radius:24px;
            padding:9px 16px 9px 38px; color:var(--text); font-family:'DM Sans',sans-serif;
            font-size:13px; width:100%; outline:none; transition:all 0.2s;
        }
        .search-wrap input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--glow); }
        .search-wrap input::placeholder { color:var(--muted); }
        .search-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }
        .search-results {
            position:absolute; top:calc(100% + 8px); left:0; width:100%;
            background:var(--bg2); border:1px solid var(--border); border-radius:14px;
            overflow:hidden; box-shadow:0 16px 48px rgba(0,0,0,0.5);
            display:none; z-index:100; max-height:320px; overflow-y:auto;
        }
        .search-results.show { display:block; }
        .sr-item { display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; transition:background 0.15s; }
        .sr-item:hover { background:var(--hover); }
        .sr-thumb { width:36px; height:36px; border-radius:8px; object-fit:cover; background:var(--card); flex-shrink:0; }
        .sr-name { font-size:13px; font-weight:500; }
        .sr-artist { font-size:11px; color:var(--muted); }
        .topbar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
        .icon-btn { width:36px; height:36px; border-radius:50%; background:var(--card); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--sub); font-size:15px; transition:all 0.2s; text-decoration:none; }
        .icon-btn:hover { background:var(--hover); color:var(--text); }

        /* hamburger — hidden on desktop */
        .hamburger { display:none; background:none; border:none; color:var(--text); font-size:22px; cursor:pointer; padding:4px; }

        /* sidebar overlay */
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:40; }
        .sidebar-overlay.show { display:block; }

        /* ── CONTENT ── */
        .content { padding:20px; }
        .sec-header { display:flex; align-items:baseline; justify-content:space-between; margin-bottom:14px; }
        .sec-label { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; }
        .sec-title { font-family:'Syne',sans-serif; font-size:20px; font-weight:800; margin-top:2px; }
        .see-all { font-size:12px; color:var(--accent); cursor:pointer; }

        /* Albums */
        .albums { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:32px; }
        .album-card { cursor:pointer; transition:transform 0.2s; }
        .album-card:hover { transform:translateY(-3px); }
        .album-cover { width:100%; aspect-ratio:1; border-radius:12px; object-fit:cover; box-shadow:0 8px 20px rgba(0,0,0,0.4); background:var(--card); display:flex; align-items:center; justify-content:center; font-size:32px; margin-bottom:8px; }
        .album-cover img { width:100%; height:100%; border-radius:12px; object-fit:cover; }
        .album-title { font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .album-artist { font-size:11px; color:var(--muted); margin-top:2px; }

        /* Track Table */
        .track-head { display:grid; grid-template-columns:36px 1fr 140px 80px 60px 36px; gap:10px; padding:0 10px 8px; font-size:10px; color:var(--muted); letter-spacing:.5px; text-transform:uppercase; border-bottom:1px solid var(--border); }
        .track-row { display:grid; grid-template-columns:36px 1fr 140px 80px 60px 36px; gap:10px; padding:8px 10px; border-radius:10px; cursor:pointer; transition:background 0.12s; align-items:center; }
        .track-row:hover { background:var(--hover); }
        .track-row.playing { background:rgba(79,110,247,0.1); }
        .t-no { font-size:12px; color:var(--muted); text-align:center; }
        .track-row.playing .t-no { color:var(--accent); }
        .t-info { display:flex; align-items:center; gap:10px; min-width:0; }
        .t-thumb { width:34px; height:34px; border-radius:7px; object-fit:cover; background:var(--card); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:14px; }
        .t-thumb img { width:100%; height:100%; border-radius:7px; object-fit:cover; }
        .t-name { font-size:13px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .t-artist { font-size:12px; color:var(--sub); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .t-album { font-size:12px; color:var(--sub); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .t-dur { font-size:12px; color:var(--muted); text-align:right; }
        .like-btn { background:none; border:none; cursor:pointer; font-size:16px; opacity:0.4; transition:all 0.2s; padding:0; }
        .like-btn.liked { opacity:1; }
        .like-btn:hover { transform:scale(1.2); opacity:1; }

        /* ── QUEUE ── */
        .queue { grid-row:1/2; background:var(--bg2); border-left:1px solid var(--border); display:flex; flex-direction:column; overflow:hidden; transition:transform 0.3s ease; }
        .q-header { display:flex; align-items:center; gap:10px; padding:18px; border-bottom:1px solid var(--border); }
        .q-title { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; }
        .q-list { overflow-y:auto; flex:1; padding:8px 10px; }
        .q-item { display:flex; align-items:center; gap:8px; padding:9px 8px; border-radius:10px; cursor:pointer; transition:background 0.12s; }
        .q-item:hover { background:var(--hover); }
        .q-item.active { background:rgba(79,110,247,0.1); }
        .q-thumb { width:36px; height:36px; border-radius:7px; object-fit:cover; background:var(--card); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:14px; }
        .q-thumb img { width:100%; height:100%; border-radius:7px; object-fit:cover; }
        .q-info { flex:1; min-width:0; }
        .q-name { font-size:12px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .q-artist { font-size:11px; color:var(--muted); }
        .q-dur { font-size:11px; color:var(--muted); flex-shrink:0; }

        /* ── PLAYER ── */
        .player {
            grid-column:1/4; grid-row:2/3;
            background:var(--bg2); border-top:1px solid var(--border);
            display:grid; grid-template-columns:260px 1fr 200px;
            align-items:center; padding:0 20px; gap:16px;
        }
        .p-track { display:flex; align-items:center; gap:12px; min-width:0; }
        .p-thumb { width:44px; height:44px; border-radius:10px; object-fit:cover; background:var(--card); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .p-thumb img { width:100%; height:100%; border-radius:10px; object-fit:cover; }
        .p-title { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .p-artist { font-size:11px; color:var(--muted); margin-top:2px; }
        .p-actions { display:flex; align-items:center; gap:10px; margin-top:6px; }
        .pa-btn { background:none; border:none; cursor:pointer; color:var(--muted); font-size:14px; transition:all 0.2s; padding:0; }
        .pa-btn:hover { color:var(--text); }
        .pa-btn.liked { color:var(--pink); }
        .p-controls { display:flex; flex-direction:column; align-items:center; gap:8px; }
        .ctrl-row { display:flex; align-items:center; gap:18px; }
        .c-btn { background:none; border:none; cursor:pointer; color:var(--sub); font-size:18px; transition:all 0.2s; line-height:1; }
        .c-btn:hover { color:var(--text); }
        .play-btn {
            width:38px; height:38px; border-radius:50%; background:var(--accent);
            border:none; color:#fff; font-size:16px; cursor:pointer; display:flex;
            align-items:center; justify-content:center;
            box-shadow:0 0 18px var(--glow); transition:all 0.2s;
        }
        .play-btn:hover { transform:scale(1.07); box-shadow:0 0 28px var(--glow); }
        .progress { display:flex; align-items:center; gap:8px; width:100%; }
        .time { font-size:11px; color:var(--muted); min-width:30px; }
        .time.r { text-align:right; }
        .bar { flex:1; height:3px; background:var(--hover); border-radius:4px; cursor:pointer; position:relative; }
        .fill { height:100%; background:linear-gradient(90deg,var(--accent),var(--pink)); border-radius:4px; width:0%; position:relative; transition:width 0.3s linear; }
        .dot { width:10px; height:10px; background:#fff; border-radius:50%; position:absolute; right:-5px; top:-3.5px; box-shadow:0 0 6px rgba(255,255,255,0.4); }
        .p-right { display:flex; align-items:center; justify-content:flex-end; gap:10px; }
        .vol { display:flex; align-items:center; gap:7px; }
        .vol-bar { width:72px; height:3px; background:var(--hover); border-radius:4px; cursor:pointer; }
        .vol-fill { height:100%; background:var(--sub); border-radius:4px; width:65%; }
        .shuffle-btn { background:none; border:none; cursor:pointer; color:var(--muted); font-size:14px; transition:color 0.2s; }
        .shuffle-btn.on { color:var(--accent); }

        /* ════════════════════════════════
           RESPONSIVE — Tablet (≤1024px)
        ════════════════════════════════ */
        @media (max-width: 1024px) {
            :root { --queue: 0px; }
            body { grid-template-columns: var(--sidebar) 1fr; }
            .queue { display:none; }
            .player { grid-column:1/3; grid-template-columns:200px 1fr; }
            .p-right { display:none; }
            .albums { grid-template-columns:repeat(4,1fr); }
            .track-head,
            .track-row { grid-template-columns:36px 1fr 100px 60px 36px; }
            .t-album { display:none; }
        }

        /* ════════════════════════════════
           RESPONSIVE — Mobile (≤768px)
        ════════════════════════════════ */
        @media (max-width: 768px) {
            :root { --sidebar: 0px; --player: 120px; }
            body {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr var(--player);
                overflow: hidden;
            }

            /* Sidebar becomes drawer */
            .sidebar {
                position: fixed;
                top: 0; left: 0;
                width: 260px;
                height: 100vh;
                z-index: 50;
                transform: translateX(-100%);
                padding-top: 60px;
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay { display:none; }
            .sidebar-overlay.show { display:block; }

            /* Main takes full width */
            .main { grid-column:1/2; }

            /* Show hamburger */
            .hamburger { display:flex; }

            /* Topbar compact */
            .topbar { padding:10px 16px; }
            .search-wrap { max-width:none; flex:1; }

            /* Content compact */
            .content { padding:14px; }
            .sec-title { font-size:17px; }

            /* Albums 2 columns */
            .albums { grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:20px; }

            /* Track table simplified */
            .track-head { grid-template-columns:28px 1fr 50px 28px; gap:8px; padding:0 8px 8px; }
            .track-row  { grid-template-columns:28px 1fr 50px 28px; gap:8px; padding:7px 8px; }
            .t-album { display:none; }
            .t-artist-col { display:none; }

            /* Player stacked */
            .player {
                grid-column:1/2;
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                padding:10px 16px;
                gap:6px;
                height: var(--player);
            }
            .p-track { gap:10px; }
            .p-thumb { width:38px; height:38px; font-size:16px; }
            .p-actions { display:none; }
            .p-right { display:none; }
            .p-controls { gap:5px; width:100%; }
            .ctrl-row { gap:14px; }
            .play-btn { width:34px; height:34px; font-size:14px; }
            .c-btn { font-size:16px; }
            .progress { gap:6px; }

            /* Queue hidden on mobile */
            .queue { display:none; }
        }

        /* ════════════════════════════════
           RESPONSIVE — Small Mobile (≤480px)
        ════════════════════════════════ */
        @media (max-width: 480px) {
            .albums { grid-template-columns:repeat(2,1fr); gap:8px; }
            .track-head { grid-template-columns:24px 1fr 44px 24px; gap:6px; }
            .track-row  { grid-template-columns:24px 1fr 44px 24px; gap:6px; padding:6px 6px; }
            .t-no { font-size:11px; }
            .t-name { font-size:12px; }
            .t-dur { font-size:11px; }
            .topbar { padding:8px 12px; }
            .content { padding:12px; }
        }
    </style>
</head>
<body>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar">
    <div class="profile">
        <div class="avatar">
            <?php if ($user['avatar']): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($user['avatar']) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover">
            <?php else: ?>
                <?= strtoupper(substr($user['username'],0,1)) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="uname"><?= htmlspecialchars($user['username']) ?></div>
            <div class="badge"><?= ucfirst($user['plan']) ?></div>
        </div>
    </div>

    <nav class="nav">
        <div class="nav-item active">🏠 Your Zone</div>
        <div class="nav-item" onclick="filterView('all')">🎵 Music</div>
        <div class="nav-item" onclick="filterView('liked')">❤️ Liked</div>
        <a href="pages/upload.php" class="nav-item">⬆️ Upload</a>
    </nav>

    <div>
        <div class="section-head">
            Your Playlists
            <span style="font-size:18px;cursor:pointer;color:var(--muted)">+</span>
        </div>
        <?php foreach ($playlists as $pl): ?>
        <div class="pl-item">
            <span class="pl-name"><?= htmlspecialchars($pl['name']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:auto;padding:16px 18px 0;border-top:1px solid var(--border)">
        <a href="#" onclick="logout()" class="nav-item" style="padding:9px 0">🚪 ออกจากระบบ</a>
    </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="main">
    <div class="topbar">
        <button class="hamburger" onclick="toggleSidebar()">☰</button>
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput" placeholder="ค้นหาเพลง ศิลปิน อัลบั้ม..." autocomplete="off">
            <div class="search-results" id="searchResults"></div>
        </div>
        <div class="topbar-right">
            <a href="pages/upload.php" class="icon-btn" title="Upload เพลง">⬆️</a>
            <div class="icon-btn" onclick="logout()" title="ออกจากระบบ">🚪</div>
        </div>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="content">
        <!-- Albums -->
        <div class="sec-header">
            <div><div class="sec-label">Featured</div><div class="sec-title">Albums</div></div>
        </div>
        <div class="albums" id="albumsGrid">
            <?php
            $albumMap = [];
            foreach ($songs as $s) {
                $key = $s['album'] ?: $s['artist'];
                if ($key && !isset($albumMap[$key])) $albumMap[$key] = $s;
            }
            $emojis = ['🎵','🎸','🎹','🎺','🥁','🎻'];
            $i = 0;
            foreach (array_slice($albumMap, 0, 5) as $albumName => $s):
            ?>
            <div class="album-card" onclick="playSong(<?= $s['id'] ?>)">
                <div class="album-cover">
                    <?php if ($s['cover_path']): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($s['cover_path']) ?>" alt="">
                    <?php else: ?>
                        <?= $emojis[$i % count($emojis)] ?>
                    <?php endif; ?>
                </div>
                <div class="album-title"><?= htmlspecialchars($albumName) ?></div>
                <div class="album-artist"><?= htmlspecialchars($s['artist']) ?></div>
            </div>
            <?php $i++; endforeach; ?>
        </div>

        <!-- Track List -->
        <div class="sec-header">
            <div><div class="sec-label">All Songs</div><div class="sec-title">เพลงทั้งหมด</div></div>
            <span class="see-all"><?= count($songs) ?> เพลง</span>
        </div>

        <div class="track-head">
            <span>#</span><span>Title</span><span>Artist</span><span>Album</span><span style="text-align:right">Time</span><span></span>
        </div>
        <div id="trackList">
        <?php foreach ($songs as $idx => $song):
            $liked = in_array($song['id'], $likedIds);
            $dur = $song['duration'] > 0 ? formatDuration($song['duration']) : '--:--';
        ?>
        <div class="track-row" id="track-<?= $song['id'] ?>" onclick="playSong(<?= $song['id'] ?>)"
             data-id="<?= $song['id'] ?>"
             data-title="<?= htmlspecialchars($song['title'], ENT_QUOTES) ?>"
             data-artist="<?= htmlspecialchars($song['artist'], ENT_QUOTES) ?>"
             data-file="<?= BASE_URL . '/' . htmlspecialchars($song['file_path']) ?>"
             data-cover="<?= $song['cover_path'] ? BASE_URL . '/' . htmlspecialchars($song['cover_path']) : '' ?>">
            <div class="t-no"><?= $idx + 1 ?></div>
            <div class="t-info">
                <div class="t-thumb">
                    <?php if ($song['cover_path']): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($song['cover_path']) ?>" alt="">
                    <?php else: ?>🎵<?php endif; ?>
                </div>
                <span class="t-name"><?= htmlspecialchars($song['title']) ?></span>
            </div>
            <div class="t-artist"><?= htmlspecialchars($song['artist']) ?></div>
            <div class="t-album"><?= htmlspecialchars($song['album'] ?? '') ?></div>
            <div class="t-dur"><?= $dur ?></div>
            <button class="like-btn <?= $liked ? 'liked' : '' ?>"
                    onclick="toggleLike(event, <?= $song['id'] ?>)">
                <?= $liked ? '❤️' : '🤍' ?>
            </button>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- ═══ QUEUE ═══ -->
<aside class="queue">
    <div class="q-header">
        <div class="q-title">🎵 Play Queue</div>
    </div>
    <div class="q-list" id="queueList">
        <?php foreach ($songs as $i => $song): ?>
        <div class="q-item" id="qi-<?= $song['id'] ?>" onclick="playSong(<?= $song['id'] ?>)">
            <div class="q-thumb">
                <?php if ($song['cover_path']): ?>
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($song['cover_path']) ?>" alt="">
                <?php else: ?>🎵<?php endif; ?>
            </div>
            <div class="q-info">
                <div class="q-name"><?= htmlspecialchars($song['title']) ?></div>
                <div class="q-artist"><?= htmlspecialchars($song['artist']) ?></div>
            </div>
            <div class="q-dur"><?= $song['duration'] > 0 ? formatDuration($song['duration']) : '' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</aside>

<!-- ═══ PLAYER ═══ -->
<footer class="player">
    <div class="p-track">
        <div class="p-thumb" id="pThumb">🎵</div>
        <div>
            <div class="p-title" id="pTitle">เลือกเพลงที่ต้องการเล่น</div>
            <div class="p-artist" id="pArtist">—</div>
            <div class="p-actions">
                <button class="pa-btn" id="pLikeBtn" onclick="toggleLike(event, currentSongId)">🤍</button>
            </div>
        </div>
    </div>

    <div class="p-controls">
        <div class="ctrl-row">
            <button class="c-btn" id="shuffleBtn" onclick="toggleShuffle()">🔀</button>
            <button class="c-btn" onclick="prevSong()">⏮</button>
            <button class="play-btn" id="playBtn" onclick="togglePlay()">▶</button>
            <button class="c-btn" onclick="nextSong()">⏭</button>
            <button class="c-btn" onclick="toggleRepeat()" id="repeatBtn">🔁</button>
        </div>
        <div class="progress">
            <span class="time" id="tCurrent">0:00</span>
            <div class="bar" id="progressBar" onclick="seekTo(event)">
                <div class="fill" id="progressFill"><div class="dot"></div></div>
            </div>
            <span class="time r" id="tTotal">0:00</span>
        </div>
    </div>

    <div class="p-right">
        <div class="vol">
            <span style="font-size:14px;color:var(--muted)">🔉</span>
            <div class="vol-bar" onclick="setVolume(event)">
                <div class="vol-fill" id="volFill"></div>
            </div>
        </div>
    </div>
</footer>

<audio id="audioPlayer" preload="metadata"></audio>

<script>
const audio = document.getElementById('audioPlayer');
const BASE  = '<?= BASE_URL ?>';

// ─── Song Data from PHP ───
const allSongs = <?= json_encode(array_values($songs)) ?>;
let queue      = [...allSongs];
let currentIdx = -1;
let currentSongId = null;
let shuffle    = false;
let repeat     = false;
let volume     = 0.65;
audio.volume   = volume;

function getSongById(id) {
    return allSongs.find(s => s.id == id);
}

// ─── Play Song ───
function playSong(id) {
    const song = getSongById(id);
    if (!song) return;

    currentSongId = id;
    currentIdx = queue.findIndex(s => s.id == id);

    // Update audio
    audio.src = BASE + '/' + song.file_path;
    audio.load();
    audio.play();

    // Update player UI
    document.getElementById('pTitle').textContent = song.title;
    document.getElementById('pArtist').textContent = song.artist;

    const thumb = document.getElementById('pThumb');
    thumb.innerHTML = song.cover_path
        ? `<img src="${BASE}/${song.cover_path}" alt="" style="width:100%;height:100%;border-radius:10px;object-fit:cover">`
        : '🎵';

    // Like button
    const liked = document.querySelector(`#track-${id} .like-btn`)?.classList.contains('liked');
    const pLike = document.getElementById('pLikeBtn');
    pLike.textContent = liked ? '❤️' : '🤍';
    pLike.classList.toggle('liked', !!liked);

    // Highlight track row
    document.querySelectorAll('.track-row').forEach(r => r.classList.remove('playing'));
    const row = document.getElementById('track-' + id);
    if (row) row.classList.add('playing');

    // Highlight queue
    document.querySelectorAll('.q-item').forEach(r => r.classList.remove('active'));
    const qi = document.getElementById('qi-' + id);
    if (qi) { qi.classList.add('active'); qi.scrollIntoView({block:'nearest'}); }

    // Increment play count
    fetch(`${BASE}/includes/api.php?action=play`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'song_id=' + id
    });
}

// ─── Play/Pause ───
function togglePlay() {
    if (!audio.src) return;
    if (audio.paused) {
        audio.play();
        document.getElementById('playBtn').textContent = '⏸';
    } else {
        audio.pause();
        document.getElementById('playBtn').textContent = '▶';
    }
}

audio.addEventListener('play', () => document.getElementById('playBtn').textContent = '⏸');
audio.addEventListener('pause', () => document.getElementById('playBtn').textContent = '▶');

// ─── Progress ───
audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    document.getElementById('progressFill').style.width = pct + '%';
    document.getElementById('tCurrent').textContent = formatTime(audio.currentTime);
    document.getElementById('tTotal').textContent   = formatTime(audio.duration);
});

function formatTime(s) {
    if (isNaN(s)) return '0:00';
    return Math.floor(s/60) + ':' + String(Math.floor(s%60)).padStart(2,'0');
}

function seekTo(e) {
    if (!audio.duration) return;
    const bar = document.getElementById('progressBar');
    const pct = e.offsetX / bar.offsetWidth;
    audio.currentTime = pct * audio.duration;
}

// ─── Next / Prev ───
audio.addEventListener('ended', () => {
    if (repeat) { audio.play(); return; }
    nextSong();
});

function nextSong() {
    if (!queue.length) return;
    if (shuffle) {
        currentIdx = Math.floor(Math.random() * queue.length);
    } else {
        currentIdx = (currentIdx + 1) % queue.length;
    }
    playSong(queue[currentIdx].id);
}

function prevSong() {
    if (!queue.length) return;
    currentIdx = (currentIdx - 1 + queue.length) % queue.length;
    playSong(queue[currentIdx].id);
}

// ─── Shuffle / Repeat ───
function toggleShuffle() {
    shuffle = !shuffle;
    document.getElementById('shuffleBtn').style.color = shuffle ? 'var(--accent)' : '';
}

function toggleRepeat() {
    repeat = !repeat;
    document.getElementById('repeatBtn').style.color = repeat ? 'var(--accent)' : '';
}

// ─── Volume ───
function setVolume(e) {
    const bar = e.currentTarget;
    volume = Math.min(1, Math.max(0, e.offsetX / bar.offsetWidth));
    audio.volume = volume;
    document.getElementById('volFill').style.width = (volume * 100) + '%';
}

// ─── Like ───
function toggleLike(e, id) {
    e.stopPropagation();
    if (!id) return;

    fetch(`${BASE}/includes/api.php?action=like`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'song_id=' + id
    }).then(r => r.json()).then(data => {
        const btn = document.querySelector(`#track-${id} .like-btn`);
        if (btn) {
            btn.classList.toggle('liked', data.liked);
            btn.textContent = data.liked ? '❤️' : '🤍';
        }
        if (currentSongId == id) {
            const pLike = document.getElementById('pLikeBtn');
            pLike.classList.toggle('liked', data.liked);
            pLike.textContent = data.liked ? '❤️' : '🤍';
        }
    });
}

// ─── Search ───
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    const results = document.getElementById('searchResults');

    if (!q) { results.classList.remove('show'); return; }

    searchTimeout = setTimeout(() => {
        fetch(`${BASE}/includes/api.php?action=search&q=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(songs => {
                if (!songs.length) {
                    results.innerHTML = '<div style="padding:16px;text-align:center;color:var(--muted);font-size:13px">ไม่พบเพลงที่ค้นหาครับ</div>';
                } else {
                    results.innerHTML = songs.map(s => `
                        <div class="sr-item" onclick="playSong(${s.id}); document.getElementById('searchResults').classList.remove('show')">
                            <div class="sr-thumb" style="background:var(--card);display:flex;align-items:center;justify-content:center;font-size:16px">
                                ${s.cover_path ? `<img src="${BASE}/${s.cover_path}" alt="" style="width:100%;height:100%;border-radius:8px;object-fit:cover">` : '🎵'}
                            </div>
                            <div>
                                <div class="sr-name">${s.title}</div>
                                <div class="sr-artist">${s.artist}</div>
                            </div>
                        </div>
                    `).join('');
                }
                results.classList.add('show');
            });
    }, 300);
});

document.addEventListener('click', e => {
    if (!e.target.closest('.search-wrap')) {
        document.getElementById('searchResults').classList.remove('show');
    }
});

// ─── Sidebar toggle (mobile) ───
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
}

// ─── Logout ───
function logout() {
    fetch(`${BASE}/includes/api.php?action=logout`)
        .then(() => window.location.href = `${BASE}/pages/login.php`);
}
</script>
</body>
</html>
