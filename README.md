# 🎵 SoundWave — Music Streaming App

## โครงสร้างโปรเจกต์
```
soundwave/
├── index.php              ← หน้าหลัก (Music Player)
├── database.sql           ← สร้าง Database ที่นี่
├── includes/
│   ├── config.php         ← ตั้งค่า DB + Session
│   └── api.php            ← API (search, like, play)
├── pages/
│   ├── login.php          ← Login / Register
│   └── upload.php         ← Upload เพลง
└── uploads/
    ├── songs/             ← ไฟล์เพลง .mp3
    └── covers/            ← ภาพปก
```

## วิธีติดตั้ง (XAMPP)

### 1. วางโฟลเดอร์
```
C:\xampp\htdocs\soundwave\
```

### 2. สร้าง Database
- เปิด phpMyAdmin → http://localhost/phpmyadmin
- คลิก "Import" → เลือกไฟล์ `database.sql`
- กด Go

### 3. ตั้งค่า config (ถ้าจำเป็น)
แก้ไฟล์ `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // ชื่อ user MySQL
define('DB_PASS', '');       // password (ปกติ XAMPP ว่าง)
define('DB_NAME', 'soundwave');
define('BASE_URL', 'http://localhost/soundwave');
```

### 4. เปิดเว็บ
```
http://localhost/soundwave
```

### 5. Login ด้วย default account
- Email: `admin@soundwave.com`
- Password: `password`

---

## ฟีเจอร์ที่มี
- ✅ Login / Register
- ✅ Upload เพลง (MP3, WAV, OGG, M4A) + ภาพปก
- ✅ Player จริง พร้อม Play/Pause/Next/Prev
- ✅ Progress bar กด seek ได้
- ✅ ปรับ Volume ได้
- ✅ Shuffle / Repeat
- ✅ ค้นหาเพลงแบบ real-time
- ✅ กดถูกใจเพลง ❤️
- ✅ Play Queue
- ✅ นับจำนวนการเล่น
- ✅ เชื่อม MySQL จริง

---

## ต้องการพัฒนาต่อ?
- Admin panel จัดการเพลง
- ระบบ Playlist สร้าง/แก้ไขได้
- ระบบ Follow ศิลปิน
- Equalizer
- Mobile responsive
