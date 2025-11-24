# 🚕 Bosowa Driver App

Sistem manajemen transaksi untuk driver taksi dengan fitur lengkap untuk mengelola perjalanan, biaya tambahan, dan metode pembayaran.

## 📋 Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Panduan Penggunaan](#panduan-penggunaan)
- [Struktur Database](#struktur-database)
- [Struktur Folder](#struktur-folder)
- [Teknologi](#teknologi)

## ✨ Fitur Utama

### Autentikasi Driver
- ✅ Login driver dengan username dan password
- ✅ Registrasi self-service untuk driver baru
- ✅ Session management dan logout
- ✅ Proteksi akses berbasis ownership

### Dashboard Driver
- 📊 Statistik real-time transaksi
- 💰 Total pendapatan
- 📋 Status setoran (Disetor/Belum Disetor) - khusus transaksi Tunai
- 🔄 Filter transaksi berdasarkan tanggal

### Manajemen Transaksi
- ➕ Buat transaksi baru dengan pemilihan rute tarif
- ✏️ Edit rute tarif, biaya tambahan, dan metode pembayaran dalam satu halaman (dengan tab)
- 📝 Hapus transaksi dengan proteksi ownership
- 🔍 Lihat detail transaksi dengan filter tanggal

### Biaya Tambahan (Detail Biaya)
- ➕ Tambah biaya tambahan (Tol, Parkir, dll)
- ✏️ Edit biaya yang sudah ada dengan modal form
- ❌ Hapus biaya dengan notifikasi
- 💵 Total biaya tambahan otomatis terhitung

### Metode Pembayaran
- 💵 **Tunai**: Dengan status setoran (Disetor/Belum Disetor) dan tanggal setoran
- 📱 **QRIS**: Untuk pembayaran digital
- 🔄 Mudah mengganti metode pembayaran

### Notifikasi & Feedback
- ✅ Sistem flash message untuk setiap aksi
- 📢 Notifikasi saat berhasil tambah/edit/hapus data
- ⚠️ Pesan error yang jelas

## 🛠️ Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- PostgreSQL 10 atau lebih tinggi
- Web Server (Apache/XAMPP)
- Bootstrap 5.3.0
- Font Awesome 6.4.0

## 📦 Instalasi

### 1. Prerequisites
- Pastikan Apache dan PostgreSQL sudah berjalan
- PHP 7.4+ dengan ekstensi PostgreSQL

### 2. Setup Database
Pastikan database `bosowa` sudah dibuat dengan tabel berikut:

```
DRIVER               - Data driver
TRANSAKSI            - Data transaksi
DETAIL_BIAYA         - Biaya tambahan per transaksi
TRANSAKSI_TUNAI      - Detail pembayaran tunai
TRANSAKSI_QRIS       - Detail pembayaran QRIS
RUTE_TARIF           - Tarif per rute
RUTE                 - Data rute
KATEGORI_TARIF       - Kategori tarif
```

### 3. Konfigurasi Database
Edit `config/database.php`:
```php
$dsn = "pgsql:host=localhost;port=5432;dbname=bosowa";
$username = "postgres";
$password = "your_password";  // Sesuaikan password Anda
```

### 4. Setup XAMPP
1. Copy folder `bosowa` ke `C:\xampp\htdocs\`
2. Start Apache dan PostgreSQL
3. Akses aplikasi di `http://localhost/bosowa/`

## 📖 Panduan Penggunaan

### Registrasi Driver
1. Klik tombol **Registrasi Driver** di homepage
2. Isi form dengan:
   - Username (minimal 3 karakter)
   - Password (minimal 5 karakter)
   - Konfirmasi password
3. Klik **Daftar** untuk membuat akun

### Login Driver
1. Klik tombol **Login Driver** di homepage
2. Masukkan username dan password
3. Klik **Masuk**

### Membuat Transaksi Baru

**Alur Lengkap:**
1. Dari dashboard, klik **Tambah Transaksi Baru**
2. Di tab **Rute Tarif**:
   - Pilih rute dari dropdown
   - Total otomatis terhitung
   - Klik **Simpan Transaksi**
3. Di tab **Biaya Tambahan** (opsional):
   - Isi **Jenis Biaya** (contoh: Tol, Parkir)
   - Isi **Jumlah (Rp)**
   - Klik **Tambah Biaya**
   - Untuk edit: klik **Edit**, ubah di modal, klik **Simpan**
   - Untuk hapus: klik **Hapus**, konfirmasi
4. Di tab **Metode Pembayaran**:
   - Pilih **Tunai** atau **QRIS**
   - Jika Tunai: atur status setoran dan tanggal
   - Klik **Selesai - Simpan Transaksi**

### Melihat Daftar Transaksi
1. Dari dashboard, klik **History Transaksi**
2. Gunakan filter:
   - **Tanggal Mulai** - mulai dari tanggal berapa
   - **Tanggal Akhir** - sampai tanggal berapa
3. Lihat status pembayaran:
   - 🟢 **Tunai** - dengan status setoran di bawahnya
   - 🔵 **QRIS** - pembayaran digital
   - 🔘 **Belum Ditentukan** - metode belum dipilih

### Edit Transaksi Existing
1. Di daftar transaksi, klik **Edit**
2. Gunakan tab untuk mengelola:
   - **Tab 1 - Rute Tarif**: Ubah pilihan rute/tarif
   - **Tab 2 - Biaya Tambahan**: Tambah/edit/hapus biaya
   - **Tab 3 - Metode Pembayaran**: Ubah metode atau status setoran
3. Sistem otomatis kembali ke tab yang Anda gunakan terakhir

## 🗄️ Struktur Database

### Tabel DRIVER
```
id_user          | SERIAL PRIMARY KEY
username         | VARCHAR(100) UNIQUE
password         | VARCHAR(255)
created_at       | TIMESTAMP
updated_at       | TIMESTAMP
```

### Tabel TRANSAKSI
```
id_transaksi     | SERIAL PRIMARY KEY
id_user          | INTEGER (FK ke DRIVER)
id_rute_tarif    | INTEGER (FK ke RUTE_TARIF)
tanggal_dibuat   | TIMESTAMP
tanggal_diupdate | TIMESTAMP
total            | DECIMAL(15, 2) - Auto-calculated
```

### Tabel DETAIL_BIAYA
```
id_detail        | SERIAL PRIMARY KEY
id_transaksi     | INTEGER (FK ke TRANSAKSI)
jenis_biaya      | VARCHAR(100)
jumlah           | DECIMAL(15, 2)
created_at       | TIMESTAMP
```

### Tabel TRANSAKSI_TUNAI
```
id_transaksi     | SERIAL PRIMARY KEY (FK ke TRANSAKSI)
status_setoran   | VARCHAR(50) - 'Disetor' atau 'Belum Disetor'
tanggal_setoran  | DATE
created_at       | TIMESTAMP
```

### Tabel TRANSAKSI_QRIS
```
id_transaksi     | SERIAL PRIMARY KEY (FK ke TRANSAKSI)
bukti_pembayaran | VARCHAR(255)
created_at       | TIMESTAMP
```

### Fitur Database
- ✅ Trigger otomatis menghitung total transaksi
- ✅ Foreign keys untuk integritas data
- ✅ Cascade delete untuk data terkait

## 📁 Struktur Folder

```
bosowa/
├── index.php                      # Homepage
├── README.md                      # Dokumentasi
├── config/
│   ├── database.php              # Database connection (PostgreSQL)
│   └── notification.php          # Flash message helper
├── auth/
│   ├── login.php                 # Login page
│   ├── register.php              # Registrasi self-service
│   ├── logout.php                # Logout handler
│   ├── session.php               # Session management
│   └── dashboard.php             # Driver dashboard
├── transaksi/
│   ├── create.php                # Create transaction
│   ├── read.php                  # List transactions dengan filter
│   ├── edit.php                  # Edit dengan 3 tab (Rute, Biaya, Payment)
│   ├── delete.php                # Delete transaction
│   ├── biaya.php                 # Manage additional costs
│   ├── edit_biaya.php            # Edit costs detail
│   ├── hapus_biaya.php           # Delete cost item
│   ├── payment.php               # Select initial payment method
│   └── edit_payment.php          # Edit payment method
├── layout/
│   ├── header.php                # Header template
│   └── footer.php                # Footer template
└── driver/                        # Folder legacy (tidak digunakan)
    └── ...
```

## 💻 Teknologi

### Backend
- **PHP 7.4+** - Server-side logic
- **PostgreSQL** - Database
- **PDO** - Database abstraction
- **Session-based authentication**

### Frontend
- **Bootstrap 5.3.0** - UI Framework
- **Font Awesome 6.4.0** - Icons
- **JavaScript** - Tab persistence, form validation, modal

### Key Features
- Flash message notification system
- Modal forms untuk edit biaya
- Responsive design
- Tab persistence via localStorage
- Date filtering
- Payment method separation (TUNAI vs QRIS)

## 🔒 Keamanan

- ✅ Session-based authentication
- ✅ Ownership validation (driver hanya akses transaksi miliknya)
- ✅ Prepared statements (pencegahan SQL injection)
- ✅ Conditional session_start() untuk error handling
- ⚠️ **TODO**: Password hashing dengan bcrypt

## 🚀 Workflow Lengkap

```
1. Homepage
   ├─→ Login Driver (existing user)
   │   └─→ Dashboard
   │       ├─→ Tambah Transaksi Baru
   │       │   ├─ Tab: Rute Tarif (pilih rute)
   │       │   ├─ Tab: Biaya Tambahan (add/edit/delete)
   │       │   ├─ Tab: Metode Pembayaran (tunai/qris)
   │       │   └─ Selesai (save transaksi)
   │       └─→ History Transaksi (dengan filter)
   │           └─→ Edit (ubah rute, biaya, payment)
   │
   └─→ Registrasi Driver (new user)
       └─→ Buat akun baru
           └─→ Login
```

## 📝 Catatan Pengembangan

### Yang Sudah Diimplementasikan ✅
- Sistem login/register driver
- CRUD transaksi lengkap
- Manajemen biaya tambahan dengan modal editing
- Metode pembayaran (Tunai & QRIS)
- Dashboard dengan statistik TUNAI-only untuk setoran
- Filter transaksi berdasarkan tanggal
- Notifikasi flash message
- Responsive UI dengan Bootstrap 5
- Tab-based interface dengan localStorage persistence

### Fitur Mendatang 🔲
- Password hashing (bcrypt)
- Upload bukti pembayaran QRIS
- Export transaksi ke PDF
- Laporan keuangan detail
- Integrasi payment gateway
- Email notifications

## 🐛 Troubleshooting

### Error: "Koneksi gagal"
- Pastikan PostgreSQL running
- Cek credentials di `config/database.php`
- Cek nama database `bosowa` sudah dibuat

### Error: "Table does not exist"
- Pastikan semua tabel sudah dibuat
- Run database setup script jika ada

### Session error "session already active"
- Error ini sudah ditangani dengan conditional check
- Verifikasi `config/session.php` digunakan di semua file

### Login tidak berfungsi
- Pastikan data driver sudah ada di tabel DRIVER
- Cek username dan password di database
- Coba register driver baru

## 📱 Responsive Design
- Desktop: Full layout dengan semua fitur
- Tablet: Responsive navbar dan forms
- Mobile: Touch-friendly buttons dan layouts

## 📞 Support

Untuk pertanyaan atau laporan bug, silakan hubungi tim development.

---

**Version**: 2.0.0  
**Last Updated**: November 24, 2025  
**Status**: Production Ready (dengan warning: passwords masih plaintext)
