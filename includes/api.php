<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ─── Search Songs ───
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 1) { echo json_encode([]); exit; }

        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, title, artist, album, genre, duration, cover_path, plays
            FROM songs
            WHERE title LIKE ? OR artist LIKE ? OR album LIKE ?
            ORDER BY plays DESC LIMIT 20
        ");
        $like = "%{$q}%";
        $stmt->execute([$like, $like, $like]);
        echo json_encode($stmt->fetchAll());
        break;

    // ─── Get All Songs ───
    case 'songs':
        $db = getDB();
        $stmt = $db->query("SELECT id, title, artist, album, genre, duration, file_path, cover_path, plays FROM songs ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
        break;

    // ─── Toggle Like ───
    case 'like':
        if (!isLoggedIn()) { echo json_encode(['error' => 'not logged in']); exit; }
        $songId = (int)($_POST['song_id'] ?? 0);
        $db = getDB();

        $check = $db->prepare("SELECT id FROM liked_songs WHERE user_id = ? AND song_id = ?");
        $check->execute([$_SESSION['user_id'], $songId]);

        if ($check->fetch()) {
            $db->prepare("DELETE FROM liked_songs WHERE user_id = ? AND song_id = ?")->execute([$_SESSION['user_id'], $songId]);
            echo json_encode(['liked' => false]);
        } else {
            $db->prepare("INSERT INTO liked_songs (user_id, song_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $songId]);
            echo json_encode(['liked' => true]);
        }
        break;

    // ─── Increment Play Count ───
    case 'play':
        $songId = (int)($_POST['song_id'] ?? 0);
        if ($songId > 0) {
            $db = getDB();
            $db->prepare("UPDATE songs SET plays = plays + 1 WHERE id = ?")->execute([$songId]);
        }
        echo json_encode(['ok' => true]);
        break;

    // ─── Get Liked Songs ───
    case 'liked':
        if (!isLoggedIn()) { echo json_encode([]); exit; }
        $db = getDB();
        $stmt = $db->prepare("
            SELECT s.id, s.title, s.artist, s.album, s.duration, s.cover_path
            FROM songs s
            JOIN liked_songs l ON s.id = l.song_id
            WHERE l.user_id = ?
            ORDER BY l.liked_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll());
        break;

    // ─── Logout ───
    case 'logout':
        session_destroy();
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'unknown action']);
}
