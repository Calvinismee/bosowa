# 🚕 Taksi App - Sistem Manajemen Operasional Taksi

Aplikasi web untuk mengelola driver dan transaksi layanan taksi dengan fitur login driver dan dashboard yang informatif.

## 📋 Struktur Proyek

```
bosowa/
├── auth/                 # Modul Autentikasi Driver
│   ├── login.php        # Halaman login driver
│   ├── dashboard.php    # Dashboard utama driver
│   ├── logout.php       # Logout driver
│   └── session.php      # Manajemen session
├── transaksi/           # Modul Transaksi Driver
│   ├── read.php         # Daftar transaksi
│   ├── create.php       # Tambah transaksi
│   ├── edit.php         # Edit transaksi
│   └── delete.php       # Hapus transaksi
├── driver/              # Modul Admin - Kelola Driver
│   ├── read.php         # Daftar driver
│   ├── create.php       # Tambah driver
│   ├── edit.php         # Edit driver
│   └── delete.php       # Hapus driver
├── config/
│   ├── database.php     # Konfigurasi database
│   └── notification.php # Sistem notifikasi
├── layout/
│   ├── header.php       # Header template
│   └── footer.php       # Footer template
└── index.php            # Homepage
```

## 🚀 Instalasi & Setup

### 1. Prerequisites
- PHP 7.4 atau lebih tinggi
- PostgreSQL 10 atau lebih tinggi (sudah setup dengan database)
- XAMPP atau web server lainnya

### 2. Verifikasi Database
Pastikan database `bosowa` sudah dibuat dengan tabel:
- `DRIVER` - untuk data driver
- `TRANSAKSI` - untuk data transaksi

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
$host = 'localhost';
$db   = 'bosowa';
$user = 'postgres';           // sesuaikan dengan username PostgreSQL Anda
$pass = 'postgres';           // sesuaikan dengan password PostgreSQL Anda
$port = "5432";
```

### 4. Setup XAMPP
1. Copy folder `bosowa` ke `C:\xampp\htdocs\`
2. Pastikan Apache dan PostgreSQL berjalan
3. Akses aplikasi di `http://localhost/bosowa/`

## 🔐 Login & Akses

### Driver Login
- **URL**: `http://localhost/bosowa/auth/login.php`
- **Demo Credentials**:
  - Username: `driver1`
  - Password: `12345`

### Admin Panel
- **URL**: `http://localhost/bosowa/driver/read.php`
- Untuk mengelola daftar driver

## 📱 Fitur Utama

### 1. **Autentikasi Driver**
- Login dengan username dan password
- Session management
- Logout otomatis

### 2. **Dashboard Driver**
- Statistik transaksi real-time
- Total pendapatan
- Transaksi menunggu dan selesai
- Daftar transaksi terbaru

### 3. **Manajemen Transaksi**
- ✅ Tambah transaksi baru
- ✅ Lihat daftar transaksi dengan filter
- ✅ Edit transaksi
- ✅ Hapus transaksi
- ✅ Filter berdasarkan status dan lokasi

### 4. **Admin - Kelola Driver**
- ✅ Tambah driver baru
- ✅ Lihat daftar driver
- ✅ Edit data driver
- ✅ Hapus driver
- ✅ Notifikasi untuk setiap aksi

### 5. **Sistem Notifikasi**
- Alert Bootstrap untuk pesan sukses/error
- Flash message menggunakan session
- Responsive dan user-friendly

## 📊 Database Schema

### Tabel DRIVER
```
id_user        | SERIAL PRIMARY KEY
nama_driver    | VARCHAR(255)
username       | VARCHAR(100) UNIQUE
password       | VARCHAR(255)
jenis_kelamin  | VARCHAR(20)
status         | VARCHAR(20) - 'aktif' atau 'nonaktif'
created_at     | TIMESTAMP
```

### Tabel TRANSAKSI
```
id_transaksi   | SERIAL PRIMARY KEY
id_driver      | INTEGER (FK ke DRIVER)
tanggal        | TIMESTAMP
lokasi_awal    | VARCHAR(255)
lokasi_akhir   | VARCHAR(255)
tarif          | DECIMAL(10, 2)
status         | VARCHAR(50) - 'menunggu', 'selesai', 'batal'
created_at     | TIMESTAMP
updated_at     | TIMESTAMP
```

## 🎨 UI/UX Features

- **Responsive Design**: Optimal untuk desktop dan mobile
- **Bootstrap 5**: Framework CSS modern
- **Font Awesome Icons**: Ikon profesional
- **Gradient Colors**: Design yang menarik
- **Toast Notifications**: Notifikasi real-time
- **Data Tables**: Tabel interaktif dengan hover effect

## 🔒 Keamanan

- ✅ Prepared statements (SQL injection protection)
- ✅ Input validation
- ✅ Session-based authentication
- ✅ Password storage (perlu ditingkatkan dengan hash)
- ✅ Ownership verification (driver hanya lihat transaksi sendiri)

## ⚠️ Catatan Penting

### Security Improvements (TODO)
1. Hash password menggunakan `password_hash()` bukan plaintext
2. CSRF token validation
3. Rate limiting untuk login
4. Input sanitization lebih ketat

### Database Password
⚠️ Saat ini password disimpan sebagai plaintext. Untuk production:
```php
// Saat membuat password
$hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Saat verifikasi
password_verify($_POST['password'], $db_password)
```

## 📝 API Endpoints

### Driver Authentication
- `POST /auth/login.php` - Login driver
- `GET /auth/logout.php` - Logout
- `GET /auth/dashboard.php` - Dashboard (protected)

### Transaksi Management
- `GET /transaksi/read.php` - Daftar transaksi
- `POST /transaksi/create.php` - Buat transaksi
- `POST /transaksi/edit.php?id=X` - Edit transaksi
- `GET /transaksi/delete.php?id=X` - Hapus transaksi

### Admin - Driver Management
- `GET /driver/read.php` - Daftar driver
- `POST /driver/create.php` - Buat driver
- `POST /driver/edit.php?id=X` - Edit driver
- `GET /driver/delete.php?id=X` - Hapus driver

## 🐛 Troubleshooting

### Error: "Koneksi gagal"
- Pastikan PostgreSQL running
- Cek credentials di `config/database.php`
- Cek nama database `bosowa` sudah dibuat

### Error: "Table does not exist"
- Pastikan tabel `DRIVER` dan `TRANSAKSI` sudah dibuat
- Hubungi database administrator

### Session tidak berguna
- Pastikan `session.save_path` writable
- Cek `php.ini` untuk session configuration

### Login tidak berfungsi
- Pastikan data driver sudah ada di tabel `DRIVER`
- Cek password belum diedit di database
- Pastikan status driver = 'aktif'

## 📞 Support

Untuk pertanyaan atau issue, silakan hubungi tim development.

## 📄 License

MIT License - Bebas digunakan dan dimodifikasi

---

**Version**: 1.0.0  
**Last Updated**: November 2025
