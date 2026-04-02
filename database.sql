-- SoundWave Music Streaming App
-- Database: soundwave
-- Run this file in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS soundwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soundwave;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    plan ENUM('free', 'premium') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Songs table
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    artist VARCHAR(100) NOT NULL,
    album VARCHAR(200) DEFAULT NULL,
    genre VARCHAR(50) DEFAULT NULL,
    duration INT DEFAULT 0,  -- in seconds
    file_path VARCHAR(500) NOT NULL,
    cover_path VARCHAR(500) DEFAULT NULL,
    plays INT DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Playlists table
CREATE TABLE IF NOT EXISTS playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Playlist songs
CREATE TABLE IF NOT EXISTS playlist_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Liked songs
CREATE TABLE IF NOT EXISTS liked_songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    liked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, song_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Sample data
INSERT INTO users (username, email, password, plan) VALUES
('admin', 'admin@soundwave.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'premium');
-- Default password: "password"

INSERT INTO songs (title, artist, album, genre, duration, file_path, cover_path, plays, uploaded_by) VALUES
('Golden Sands', 'Imagine Dragons', 'Night Visions', 'Rock', 294, 'uploads/songs/sample1.mp3', 'uploads/covers/sample1.jpg', 1240, 1),
('Neon Lights', 'The Weeknd', 'Starboy', 'Pop', 276, 'uploads/songs/sample2.mp3', 'uploads/covers/sample2.jpg', 980, 1),
('River Flow', 'Lana Del Rey', 'Born To Die', 'Indie', 312, 'uploads/songs/sample3.mp3', 'uploads/covers/sample3.jpg', 756, 1),
('Thunder Road', 'Jack Chen', 'Never Give Up', 'Electronic', 337, 'uploads/songs/sample4.mp3', 'uploads/covers/sample4.jpg', 643, 1),
('Ocean Eyes', 'Billie Eilish', 'dont smile at me', 'Pop', 257, 'uploads/songs/sample5.mp3', 'uploads/covers/sample5.jpg', 2100, 1);

INSERT INTO playlists (user_id, name) VALUES
(1, 'Country Music'),
(1, 'The Jazz Music'),
(1, 'Pop Music');
